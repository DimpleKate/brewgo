<?php

namespace Tests\Feature;

use App\Enums\OrderStatus;
use App\Models\Order;
use App\Models\Product;
use App\Models\Review;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class ReviewTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
        $this->seedTestCatalog();

        $this->createCustomer();
    }

    public function test_order_owner_can_review_a_completed_order(): void
    {
        $customer = $this->customer();
        $product = $this->product('brew-and-go-signature');
        $order = $this->makeOrder($customer, OrderStatus::Completed, [$product]);

        $this->actingAs($customer)
            ->post(route('orders.reviews.store', $order), [
                'rating' => 5,
                'comment' => 'Excellent coffee and friendly service.',
            ])
            ->assertRedirect(route('orders.show', $order))
            ->assertSessionHas('success');

        $this->assertDatabaseHas('reviews', [
            'order_id' => $order->id,
            'user_id' => $customer->id,
            'rating' => 5,
            'comment' => 'Excellent coffee and friendly service.',
            'status' => Review::STATUS_PENDING,
        ]);
    }

    public function test_review_requires_the_order_owner_and_a_completed_order(): void
    {
        $customer = $this->customer();
        $otherCustomer = $this->makeCustomer('other@brewandgo.test');
        $product = $this->product('brew-and-go-signature');
        $completed = $this->makeOrder($customer, OrderStatus::Completed, [$product]);
        $pending = $this->makeOrder($customer, OrderStatus::Pending, [$product]);

        $this->actingAs($otherCustomer)
            ->post(route('orders.reviews.store', $completed), [
                'rating' => 4,
                'comment' => 'Not my order.',
            ])
            ->assertForbidden();

        $this->actingAs($customer)
            ->post(route('orders.reviews.store', $pending), [
                'rating' => 4,
                'comment' => 'Too early to review.',
            ])
            ->assertForbidden();

        $this->assertDatabaseCount('reviews', 0);
    }

    public function test_a_customer_can_only_review_an_order_once(): void
    {
        $customer = $this->customer();
        $product = $this->product('brew-and-go-signature');
        $order = $this->makeOrder($customer, OrderStatus::Completed, [$product]);
        $review = $this->makeReview($customer, $order, Review::STATUS_PENDING, 4, 'First impression.');

        $this->actingAs($customer)
            ->post(route('orders.reviews.store', $order), [
                'rating' => 1,
                'comment' => 'Second attempt.',
            ])
            ->assertRedirect(route('orders.show', $order))
            ->assertSessionHas('error');

        $this->assertDatabaseCount('reviews', 1);
        $this->assertSame(4, $review->refresh()->rating);
        $this->assertSame('First impression.', $review->comment);
    }

    public function test_review_rating_must_be_between_one_and_five(): void
    {
        $customer = $this->customer();
        $product = $this->product('brew-and-go-signature');
        $order = $this->makeOrder($customer, OrderStatus::Completed, [$product]);

        $this->actingAs($customer)
            ->post(route('orders.reviews.store', $order), [
                'rating' => 0,
                'comment' => 'Too low.',
            ])
            ->assertSessionHasErrors('rating');

        $this->actingAs($customer)
            ->post(route('orders.reviews.store', $order), [
                'rating' => 6,
                'comment' => 'Too high.',
            ])
            ->assertSessionHasErrors('rating');

        $this->assertDatabaseCount('reviews', 0);
    }

    public function test_customer_rating_form_is_shown_only_to_the_owner_of_a_completed_order(): void
    {
        $customer = $this->customer();
        $otherCustomer = $this->makeCustomer('other@brewandgo.test');
        $product = $this->product('brew-and-go-signature');
        $completed = $this->makeOrder($customer, OrderStatus::Completed, [$product]);
        $pending = $this->makeOrder($customer, OrderStatus::Pending, [$product]);

        $this->actingAs($customer)
            ->get(route('orders.show', $completed))
            ->assertOk()
            ->assertSee('Submit review');

        $this->actingAs($customer)
            ->get(route('orders.show', $pending))
            ->assertOk()
            ->assertDontSee('Submit review');

        $this->actingAs($otherCustomer)
            ->get(route('orders.show', $completed))
            ->assertForbidden();
    }

    public function test_admin_can_moderate_reviews_and_only_approved_reviews_are_public(): void
    {
        $customer = $this->customer();
        $admin = $this->admin();
        $signature = $this->product('brew-and-go-signature');
        $latte = $this->product('spanish-latte');
        $otherProduct = $this->product('matcha-cloud');
        $order = $this->makeOrder($customer, OrderStatus::Completed, [$signature, $latte]);
        $review = $this->makeReview($customer, $order, Review::STATUS_PENDING, 5, 'Great drinks and quick service.');

        $this->actingAs($customer)
            ->get(route('menu.show', $signature))
            ->assertOk()
            ->assertDontSee('Great drinks and quick service.');

        $this->actingAs($admin)
            ->get(route('admin.reviews.index'))
            ->assertOk()
            ->assertSee('Great drinks and quick service.');

        $this->actingAs($admin)
            ->patch(route('admin.reviews.update', $review), ['status' => Review::STATUS_APPROVED])
            ->assertRedirect()
            ->assertSessionHas('success');

        $review->refresh();

        $this->assertSame(Review::STATUS_APPROVED, $review->status);
        $this->assertSame($admin->id, $review->moderated_by);
        $this->assertNotNull($review->moderated_at);

        foreach ([$signature, $latte] as $product) {
            $this->actingAs($customer)
                ->get(route('menu.show', $product))
                ->assertOk()
                ->assertSee('Great drinks and quick service.')
                ->assertSee('Verified purchase')
                ->assertSee('Test C.');
        }

        $this->actingAs($customer)
            ->get(route('menu.show', $otherProduct))
            ->assertOk()
            ->assertDontSee('Great drinks and quick service.');

        $this->actingAs($admin)
            ->patch(route('admin.reviews.update', $review), ['status' => Review::STATUS_HIDDEN])
            ->assertRedirect();

        $this->actingAs($customer)
            ->get(route('menu.show', $signature))
            ->assertOk()
            ->assertDontSee('Great drinks and quick service.');

        $this->assertSame(Review::STATUS_HIDDEN, $review->refresh()->status);
    }

    public function test_review_moderation_validates_status_and_rejects_customers(): void
    {
        $customer = $this->customer();
        $admin = $this->admin();
        $product = $this->product('brew-and-go-signature');
        $order = $this->makeOrder($customer, OrderStatus::Completed, [$product]);
        $review = $this->makeReview($customer, $order, Review::STATUS_PENDING, 3, 'Average.');

        $this->actingAs($customer)
            ->get(route('admin.reviews.index'))
            ->assertForbidden();

        $this->actingAs($customer)
            ->patch(route('admin.reviews.update', $review), ['status' => Review::STATUS_APPROVED])
            ->assertForbidden();

        $this->actingAs($admin)
            ->patch(route('admin.reviews.update', $review), ['status' => 'featured'])
            ->assertSessionHasErrors('status');

        $this->assertSame(Review::STATUS_PENDING, $review->refresh()->status);
    }

    private function customer(): User
    {
        return User::where('email', 'customer@brewandgo.test')->firstOrFail();
    }

    private function admin(): User
    {
        return User::where('email', 'admin@brewandgo.test')->firstOrFail();
    }

    private function makeCustomer(string $email): User
    {
        $customer = User::create([
            'name' => 'Other Customer',
            'email' => $email,
            'phone' => '09181234568',
            'address' => '456 Test Street, Quezon City',
            'password' => Hash::make('Customer@12345'),
            'is_admin' => false,
            'is_active' => true,
        ]);

        return $customer;
    }

    private function product(string $slug): Product
    {
        return Product::where('slug', $slug)->firstOrFail();
    }

    /**
     * @param  array<int, Product>  $products
     */
    private function makeOrder(User $customer, OrderStatus $status, array $products): Order
    {
        $order = Order::create([
            'order_number' => Order::generateOrderNumber(),
            'user_id' => $customer->id,
            'service_type' => Order::SERVICE_DINE_IN,
            'people_count' => 2,
            'subtotal' => 200,
            'discount' => 0,
            'total' => 200,
            'status' => $status,
        ]);

        foreach ($products as $product) {
            $order->items()->create([
                'product_id' => $product->id,
                'product_name' => $product->name,
                'category_name' => $product->category?->name,
                'quantity' => 1,
                'unit_price' => $product->base_price,
                'line_total' => $product->base_price,
            ]);
        }

        return $order;
    }

    private function makeReview(
        User $customer,
        Order $order,
        string $status,
        int $rating,
        ?string $comment = null,
    ): Review {
        return Review::create([
            'order_id' => $order->id,
            'user_id' => $customer->id,
            'rating' => $rating,
            'comment' => $comment,
            'status' => $status,
        ]);
    }
}
