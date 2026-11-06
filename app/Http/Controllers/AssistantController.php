<?php

namespace App\Http\Controllers;

use App\Enums\OrderStatus;
use App\Models\Product;
use App\Models\User;
use App\Services\OrderSettingsService;
use App\Services\SiteContentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class AssistantController extends Controller
{
    public function __construct(
        private readonly SiteContentService $siteContent,
        private readonly OrderSettingsService $orderSettings,
    ) {}

    public function reply(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'message' => ['required', 'string', 'min:1', 'max:300'],
        ]);

        $message = Str::of($validated['message'])->lower()->squish()->toString();

        [$reply, $links] = $this->answer($message, $request->user());

        return response()->json([
            'reply' => $reply,
            'links' => $links,
        ]);
    }

    /**
     * @return array{0:string, 1:array<int, array{label:string, url:string}>}
     */
    private function answer(string $message, ?User $user): array
    {
        if (preg_match('/\b(hi|hello|hey|good (morning|afternoon|evening))\b/', $message) === 1) {
            return [
                "Hi! I'm Brew Buddy, the Brew & Go store assistant.\nI can help with the menu, pickup orders, payments, and store details. What would you like to know?",
                $this->primaryLinks($user),
            ];
        }

        if (Str::contains($message, ['cancel', 'refund'])) {
            $policy = $this->orderSettings->policies()['cancellation'] ?? '';

            return [
                "Here's how cancellations and refunds work:\n".($policy ?: 'You may request a cancellation before preparation begins. Verified payments on cancelled orders are reviewed by our team.'),
                [['label' => 'View my orders', 'url' => route('orders.index')], ['label' => 'Contact the team', 'url' => route('contact')]],
            ];
        }

        if (Str::contains($message, ['my order', 'my orders', 'order status', 'track', 'status of', 'ready for pickup', 'pickup time'])) {
            return $this->orderAnswer($user);
        }

        if (Str::contains($message, ['pay', 'payment', 'deposit', 'down payment', 'downpayment', 'gcash', 'qr', 'receipt'])) {
            return [
                $this->paymentAnswer(),
                [['label' => 'Go to checkout', 'url' => route('checkout.create')], ['label' => 'View my orders', 'url' => route('orders.index')]],
            ];
        }

        if (Str::contains($message, ['pickup', 'pick up', 'collect'])) {
            return [
                $this->pickupAnswer(),
                [['label' => 'Start an order', 'url' => route('menu.index')], ['label' => 'View my orders', 'url' => route('orders.index')]],
            ];
        }

        if (Str::contains($message, ['hour', 'open', 'close', 'schedule', 'time'])) {
            return [$this->hoursAnswer(), [['label' => 'Contact us', 'url' => route('contact')]]];
        }

        if (Str::contains($message, ['where', 'location', 'address', 'direction', 'find you'])) {
            return [$this->locationAnswer(), [['label' => 'Contact us', 'url' => route('contact')]]];
        }

        if (Str::contains($message, ['contact', 'phone', 'email', 'call', 'reach', 'message'])) {
            return [$this->contactAnswer(), [['label' => 'Open contact page', 'url' => route('contact')]]];
        }

        if (Str::contains($message, ['menu', 'price', 'product', 'food', 'drink', 'coffee', 'order'])) {
            return $this->menuAnswer($user);
        }

        if (Str::contains($message, ['account', 'profile', 'password', 'sign in', 'login', 'register'])) {
            return [
                'You can update your name, phone, address, profile picture, and password from your profile page.',
                [['label' => 'Open my profile', 'url' => route('profile.edit')], ['label' => 'Sign in', 'url' => route('login')]],
            ];
        }

        if (Str::contains($message, ['thank', 'thanks'])) {
            return [
                "You're welcome! If you need anything else, I'm right here.",
                $this->primaryLinks($user),
            ];
        }

        if (preg_match('/\b(bye|goodbye|see you)\b/', $message) === 1) {
            return [
                'See you at Brew & Go. Take care!',
                [['label' => 'Back to home', 'url' => route('home')]],
            ];
        }

        return [
            'I can help with opening hours, the menu, pickup orders, payments, cancellations, and your account. Try asking about one of those, or reach the team directly.',
            [['label' => 'Contact the team', 'url' => route('contact')], ['label' => 'Browse the menu', 'url' => route('menu.index')]],
        ];
    }

    /**
     * @return array{0:string, 1:array<int, array{label:string, url:string}>}
     */
    private function orderAnswer(?User $user): array
    {
        if ($user === null) {
            return [
                'Sign in to see your orders and their live status. Once you are signed in, I can tell you where your latest order is.',
                [['label' => 'Sign in', 'url' => route('login')], ['label' => 'Create an account', 'url' => route('register')]],
            ];
        }

        $order = $user->orders()
            ->whereNotIn('status', [
                OrderStatus::Completed->value,
                OrderStatus::Cancelled->value,
                OrderStatus::Rejected->value,
            ])
            ->latest()
            ->first();

        if ($order === null) {
            return [
                "You don't have an active order right now. Once you place an order, I can check its status for you.",
                [['label' => 'Browse the menu', 'url' => route('menu.index')], ['label' => 'View my orders', 'url' => route('orders.index')]],
            ];
        }

        $details = "Your latest order {$order->order_number} is currently marked {$order->statusLabel()}.";

        if ($order->isPickup() && $order->pickup_at !== null) {
            $details .= "\nScheduled pickup: ".$order->pickup_at->format('M d, Y · h:i A').'.';
        }

        if ($order->isPickup() && $order->pickup_deadline_at !== null) {
            $details .= "\nPickup deadline: ".$order->pickup_deadline_at->format('M d, Y · h:i A').'.';
        }

        return [
            $details,
            [['label' => 'View order', 'url' => route('orders.show', $order)], ['label' => 'All my orders', 'url' => route('orders.index')]],
        ];
    }

    private function paymentAnswer(): string
    {
        if (! $this->orderSettings->pickupAdvanceRequired()) {
            return 'Pickup orders do not require an advance payment right now. You can settle the full amount when you collect your order.';
        }

        if (! $this->orderSettings->depositEnabled()) {
            $answer = 'Pickup orders require the full order total in advance.';
        } else {
            $value = $this->orderSettings->depositValue();
            $amount = $this->orderSettings->depositType() === 'fixed'
                ? '₱'.number_format($value, 2)
                : number_format($value, 0).'% of the order total';

            $answer = "Pickup orders require a {$amount} down payment. The remaining balance is settled when you collect your order.";
        }

        return $answer.' Pay through the QR code shown at checkout, upload a screenshot or photo of your confirmation, and our team verifies it before preparation starts. A payment is never treated as paid until it is verified.';
    }

    private function pickupAnswer(): string
    {
        $prep = $this->orderSettings->prepMinutes();
        $deadline = $this->orderSettings->deadlineMinutes();
        $notice = $this->orderSettings->minNoticeMinutes();

        return "Pickup orders are prepared after the required advance payment is verified. We start preparing about {$prep} minutes before your chosen pickup time, and we hold the order for {$deadline} minutes after it is marked ready. Please give us at least {$notice} minutes of notice.";
    }

    private function hoursAnswer(): string
    {
        $site = $this->siteContent->all();
        $hours = [];

        if (filled($site['contact_hours_weekday'] ?? null)) {
            $hours[] = filled($site['contact_hours_weekday_label'] ?? null)
                ? $site['contact_hours_weekday_label'].': '.$site['contact_hours_weekday']
                : 'Weekdays: '.$site['contact_hours_weekday'];
        }

        if (filled($site['contact_hours_weekend'] ?? null)) {
            $hours[] = filled($site['contact_hours_weekend_label'] ?? null)
                ? $site['contact_hours_weekend_label'].': '.$site['contact_hours_weekend']
                : 'Weekends: '.$site['contact_hours_weekend'];
        }

        if ($hours === []) {
            return 'Our opening hours are being updated. Please contact the team and we will confirm the schedule for you.';
        }

        $answer = "We're open:\n".implode("\n", $hours);

        if (filled($site['contact_hours_note'] ?? null)) {
            $answer .= "\n".$site['contact_hours_note'];
        }

        return $answer;
    }

    private function locationAnswer(): string
    {
        $address = $this->siteContent->all()['contact_address'] ?? null;

        return filled($address)
            ? "You'll find us at {$address}. You can also open the contact page for our phone number and email."
            : 'Our complete address is being updated. Please contact the team and we will send you directions.';
    }

    private function contactAnswer(): string
    {
        $site = $this->siteContent->all();
        $parts = [];

        if (filled($site['contact_phone'] ?? null)) {
            $parts[] = 'Phone: '.$site['contact_phone'];
        }

        if (filled($site['contact_email'] ?? null)) {
            $parts[] = 'Email: '.$site['contact_email'];
        }

        if ($parts === []) {
            return 'Our contact details are being updated. Please try the contact page again shortly.';
        }

        return "Here's how to reach us:\n".implode("\n", $parts);
    }

    /**
     * @return array{0:string, 1:array<int, array{label:string, url:string}>}
     */
    private function menuAnswer(?User $user): array
    {
        $count = Product::active()->count();

        if ($user === null) {
            return [
                "We currently have {$count} items on the menu. Sign in to browse the full menu and place an order.",
                [['label' => 'Sign in', 'url' => route('login')], ['label' => 'Create an account', 'url' => route('register')]],
            ];
        }

        return [
            "We currently have {$count} drinks and dishes on the menu. You can filter by category and choose sizes and add-ons before adding items to your cart.",
            [['label' => 'Open the menu', 'url' => route('menu.index')], ['label' => 'View my cart', 'url' => route('cart.index')]],
        ];
    }

    /**
     * @return array<int, array{label:string, url:string}>
     */
    private function primaryLinks(?User $user): array
    {
        if ($user === null) {
            return [
                ['label' => 'Browse the menu', 'url' => route('menu.index')],
                ['label' => 'Contact us', 'url' => route('contact')],
            ];
        }

        return [
            ['label' => 'Browse the menu', 'url' => route('menu.index')],
            ['label' => 'View my orders', 'url' => route('orders.index')],
        ];
    }
}
