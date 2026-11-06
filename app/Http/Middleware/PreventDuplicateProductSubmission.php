<?php

namespace App\Http\Middleware;

use App\Models\Product;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

class PreventDuplicateProductSubmission
{
    public const SESSION_KEY = 'admin.products.create.token';

    public function handle(Request $request, Closure $next): Response
    {
        if (! $request->isMethod('post')) {
            return $next($request);
        }

        $providedToken = (string) $request->input('submission_token', '');

        if ($providedToken === '') {
            return $next($request);
        }

        $expectedToken = $request->session()->get(self::SESSION_KEY);

        if (is_string($expectedToken) && hash_equals($expectedToken, $providedToken)) {
            return $next($request);
        }

        $slug = Str::slug((string) ($request->input('slug') ?: $request->input('name')));
        $existingProduct = $slug !== '' ? Product::where('slug', $slug)->first() : null;

        if ($existingProduct) {
            $request->session()->forget(self::SESSION_KEY);

            return redirect()
                ->route('admin.products.create')
                ->with('error', 'This product was already saved, so a duplicate was not created. The form is ready for a new product.');
        }

        return redirect()
            ->route('admin.products.create')
            ->withInput($request->except(['image_file', '_token', 'submission_token']))
            ->with('error', 'That product form was already submitted. Please review your details and try again.');
    }
}
