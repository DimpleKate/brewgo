<?php

namespace App\Http\Middleware;

use Illuminate\Support\Str;

class RecordAdminActivity extends RecordActivity
{
    protected function actions(): array
    {
        return [
            'admin.login.store' => 'Signed in',
            'admin.logout' => 'Signed out',
            'admin.categories.store' => 'Created category',
            'admin.categories.update' => 'Updated category',
            'admin.categories.destroy' => 'Deleted category',
            'admin.products.store' => 'Created product',
            'admin.products.update' => 'Updated product',
            'admin.products.destroy' => 'Deleted product',
            'admin.products.options.store' => 'Created product option',
            'admin.products.options.update' => 'Updated product option',
            'admin.products.options.destroy' => 'Deleted product option',
            'admin.addons.store' => 'Created add-on',
            'admin.addons.update' => 'Updated add-on',
            'admin.addons.destroy' => 'Deleted add-on',
            'admin.promotions.store' => 'Created promotion',
            'admin.promotions.update' => 'Updated promotion',
            'admin.promotions.destroy' => 'Deleted promotion',
            'admin.carousel.store-many' => 'Uploaded carousel images',
            'admin.carousel.destroy-many' => 'Deleted carousel images',
            'admin.carousel.destroy' => 'Deleted carousel image',
            'admin.orders.status' => 'Updated order status',
            'admin.orders.payments.verify' => 'Verified payment',
            'admin.orders.payments.reject' => 'Rejected payment',
            'admin.orders.payments.record' => 'Recorded payment',
            'admin.requests.review' => 'Reviewed order request',
            'admin.reviews.update' => 'Moderated review',
            'admin.customers.toggle' => 'Changed customer access',
            'admin.settings.update' => 'Updated store settings',
        ];
    }

    protected function guestRole(): string
    {
        return 'admin';
    }

    protected function fallbackAction(?string $routeName): string
    {
        return $routeName
            ? Str::headline(str_replace('admin.', '', $routeName))
            : 'Admin activity';
    }
}
