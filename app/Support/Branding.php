<?php

namespace App\Support;

use App\Models\SiteSetting;
use Illuminate\Database\QueryException;

class Branding
{
    public static function name(): string
    {
        try {
            $name = SiteSetting::query()->where('key', 'brand_name')->value('value');
        } catch (QueryException) {
            $name = null;
        }

        return filled($name) ? (string) $name : (string) config('app.name', 'Brew & Go');
    }

    public static function tagline(): string
    {
        try {
            return (string) (SiteSetting::query()->where('key', 'brand_tagline')->value('value') ?? '');
        } catch (QueryException) {
            return '';
        }
    }

    public static function contactEmail(): ?string
    {
        try {
            $email = SiteSetting::query()->where('key', 'contact_email')->value('value');
        } catch (QueryException) {
            $email = null;
        }

        return filled($email) ? (string) $email : null;
    }
}
