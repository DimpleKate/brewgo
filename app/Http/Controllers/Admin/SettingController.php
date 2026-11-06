<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SiteSetting;
use App\Services\OrderSettingsService;
use App\Services\PaymentQrService;
use App\Services\SiteContentService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class SettingController extends Controller
{
    public function edit(SiteContentService $siteContent, OrderSettingsService $orderSettings): View
    {
        return view('admin.settings.edit', [
            'content' => $siteContent->all(),
            'orderSettings' => $orderSettings->all(),
            'qrCodeUrl' => $orderSettings->qrCodeUrl(),
            'admin' => auth()->user(),
        ]);
    }

    public function update(Request $request, PaymentQrService $qrCodes): RedirectResponse
    {
        $admin = $request->user();

        $data = $request->validate([
            'brand_name' => ['required', 'string', 'max:80'],
            'brand_tagline' => ['nullable', 'string', 'max:100'],
            'contact_phone' => ['nullable', 'string', 'max:50'],
            'contact_email' => ['nullable', 'email', 'max:150'],
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', Rule::unique('users', 'email')->ignore($admin->id)],
            'current_password' => ['nullable', 'required_with:password', 'current_password'],
            'password' => ['nullable', 'string', 'min:8', 'confirmed'],
            'pickup_advance_payment_required' => ['sometimes', 'boolean'],
            'pickup_deposit_enabled' => ['sometimes', 'boolean'],
            'pickup_deposit_type' => ['sometimes', 'required', Rule::in(['percent', 'fixed'])],
            'pickup_deposit_value' => ['sometimes', 'required', 'numeric', 'min:0', 'max:100000'],
            'pickup_prep_minutes' => ['sometimes', 'required', 'integer', 'min:0', 'max:240'],
            'pickup_deadline_minutes' => ['sometimes', 'required', 'integer', 'min:1', 'max:720'],
            'pickup_min_notice_minutes' => ['sometimes', 'required', 'integer', 'min:0', 'max:10080'],
            'payment_qr_code' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:4096'],
            'payment_qr_instructions' => ['nullable', 'string', 'max:1000'],
            'remove_payment_qr_code' => ['sometimes', 'boolean'],
            'order_policy_payment' => ['sometimes', 'required', 'string', 'max:1000'],
            'order_policy_cancellation' => ['sometimes', 'required', 'string', 'max:1000'],
            'order_policy_refund' => ['sometimes', 'required', 'string', 'max:1000'],
            'order_policy_unclaimed' => ['sometimes', 'required', 'string', 'max:1000'],
        ]);

        if (($data['pickup_deposit_type'] ?? null) === 'percent' && (float) ($data['pickup_deposit_value'] ?? 0) > 100) {
            throw ValidationException::withMessages([
                'pickup_deposit_value' => 'A percentage deposit cannot be greater than 100%.',
            ]);
        }

        foreach (['brand_name', 'brand_tagline', 'contact_phone', 'contact_email'] as $key) {
            SiteSetting::updateOrCreate(
                ['key' => $key],
                ['value' => (string) ($data[$key] ?? '')],
            );
        }

        $orderSettings = [];

        if ($request->has('pickup_advance_payment_required')) {
            $orderSettings['pickup_advance_payment_required'] = $request->boolean('pickup_advance_payment_required') ? '1' : '0';
        }

        if ($request->has('pickup_deposit_enabled')) {
            $orderSettings['pickup_deposit_enabled'] = $request->boolean('pickup_deposit_enabled') ? '1' : '0';
        }

        foreach ([
            'pickup_deposit_type',
            'pickup_deposit_value',
            'pickup_prep_minutes',
            'pickup_deadline_minutes',
            'pickup_min_notice_minutes',
            'payment_qr_instructions',
            'order_policy_payment',
            'order_policy_cancellation',
            'order_policy_refund',
            'order_policy_unclaimed',
        ] as $key) {
            if (array_key_exists($key, $data)) {
                $orderSettings[$key] = (string) $data[$key];
            }
        }

        foreach ($orderSettings as $key => $value) {
            SiteSetting::updateOrCreate(
                ['key' => $key],
                ['value' => $value],
            );
        }

        $currentQrCode = SiteSetting::query()->where('key', 'payment_qr_code')->value('value');

        if ($request->hasFile('payment_qr_code')) {
            $qrCodes->delete($currentQrCode);

            SiteSetting::updateOrCreate(
                ['key' => 'payment_qr_code'],
                ['value' => $qrCodes->store($request->file('payment_qr_code'))],
            );
        } elseif ($request->boolean('remove_payment_qr_code')) {
            $qrCodes->delete($currentQrCode);

            SiteSetting::updateOrCreate(
                ['key' => 'payment_qr_code'],
                ['value' => ''],
            );
        }

        $admin->name = $data['name'];
        $admin->email = $data['email'];

        if (filled($data['password'] ?? null)) {
            $admin->password = $data['password'];
        }

        $admin->save();

        return redirect()
            ->route('admin.settings.edit')
            ->with('success', 'Settings updated.');
    }
}
