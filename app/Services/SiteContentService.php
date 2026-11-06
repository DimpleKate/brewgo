<?php

namespace App\Services;

use App\Models\SiteSetting;
use Illuminate\Database\QueryException;
use Illuminate\Support\Str;

class SiteContentService
{
    /**
     * @return array<string, string|null>
     */
    public function all(): array
    {
        $content = $this->defaults();

        try {
            $stored = SiteSetting::query()->pluck('value', 'key')->all();
            $content = array_merge($content, $stored);
        } catch (QueryException) {
        }

        foreach (array_keys(config('site_content.images', [])) as $key) {
            $content[$key.'_url'] = self::imageUrl($content[$key] ?? null);
        }

        return $content;
    }

    /**
     * @return array<string, string|null>
     */
    public function defaults(): array
    {
        $defaults = [];

        foreach (config('site_content.groups', []) as $group) {
            foreach ($group['fields'] as $key => $field) {
                $defaults[$key] = $field['default'] ?? '';
            }
        }

        return $defaults;
    }

    public static function imageUrl(?string $path): ?string
    {
        if (! is_string($path) || trim($path) === '') {
            return null;
        }

        return Str::startsWith($path, ['http://', 'https://'])
            ? $path
            : asset($path);
    }
}
