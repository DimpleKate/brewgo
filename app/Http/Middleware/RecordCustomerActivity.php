<?php

namespace App\Http\Middleware;

use Illuminate\Support\Str;

class RecordCustomerActivity extends RecordActivity
{
    protected function actions(): array
    {
        return [
            'login.store' => 'Customer signed in',
            'register.store' => 'Created customer account',
            'logout' => 'Customer signed out',
            'cart.items.store' => 'Added item to cart',
            'cart.items.update' => 'Updated cart item',
            'cart.items.selection' => 'Updated cart selection',
            'cart.items.destroy' => 'Removed item from cart',
            'checkout.store' => 'Placed order',
            'orders.requests.store' => 'Submitted order request',
            'orders.reviews.store' => 'Submitted order review',
            'profile.update' => 'Updated profile',
            'profile.avatar.update' => 'Updated profile picture',
            'profile.avatar.destroy' => 'Removed profile picture',
            'notifications.read-all' => 'Marked notifications read',
        ];
    }

    protected function guestRole(): string
    {
        return 'customer';
    }

    protected function fallbackAction(?string $routeName): string
    {
        return $routeName ? Str::headline($routeName) : 'Customer activity';
    }
}
