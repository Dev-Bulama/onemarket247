<?php

namespace App\Http\Requests\Vendor;

use App\Models\Store;
use App\Models\VendorApplication;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;

class VendorApplicationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            // Step 1 — applicant & business
            'full_name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email', 'unique:vendor_applications,email'],
            'phone' => ['nullable', 'string', 'max:30'],
            'business_name' => ['required', 'string', 'max:255'],
            'registration_number' => ['nullable', 'string', 'max:100'],
            'tax_identification_number' => ['nullable', 'string', 'max:100'],

            // Step 2 — store
            'store_name' => ['required', 'string', 'max:255'],
            'store_category' => ['nullable', 'string', 'max:100'],
            'store_description' => ['nullable', 'string', 'max:2000'],
            'country_id' => ['nullable', 'exists:countries,id'],
            'state_id' => ['nullable', 'exists:states,id'],
            'city_id' => ['nullable', 'exists:cities,id'],
            'postal_code' => ['nullable', 'string', 'max:20'],
            'address' => ['nullable', 'string', 'max:500'],
            'website' => ['nullable', 'url', 'max:255'],

            // Step 3 — banking
            'bank_name' => ['required', 'string', 'max:255'],
            'bank_account_name' => ['required', 'string', 'max:255'],
            'bank_account_number' => ['required', 'string', 'max:100'],

            // Step 4 — documents
            'identity_document' => ['required', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:5120'],
            'business_registration_document' => ['required', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:5120'],
            'tax_certificate_document' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:5120'],

            // Plan + terms
            'vendor_subscription_plan_id' => ['nullable', 'exists:vendor_subscription_plans,id'],
            'terms' => ['accepted'],
        ];
    }

    public function messages(): array
    {
        return [
            'terms.accepted' => 'You must accept the vendor terms to continue.',
        ];
    }

    public function storeSlug(): string
    {
        $base = Str::slug($this->string('store_name'));
        $slug = $base;
        $suffix = 1;

        while (Store::withoutGlobalScopes()->where('slug', $slug)->exists()
            || VendorApplication::where('store_slug', $slug)->exists()) {
            $slug = $base.'-'.$suffix++;
        }

        return $slug;
    }
}
