<?php

namespace Database\Factories;

use App\Enums\VendorDocumentStatus;
use App\Enums\VendorDocumentType;
use App\Models\Vendor;
use App\Models\VendorDocument;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<VendorDocument>
 */
class VendorDocumentFactory extends Factory
{
    protected $model = VendorDocument::class;

    public function definition(): array
    {
        return [
            'vendor_id' => Vendor::factory(),
            'type' => fake()->randomElement(VendorDocumentType::cases()),
            'file_path' => 'vendor-documents/'.fake()->uuid().'.pdf',
            'status' => VendorDocumentStatus::Pending,
        ];
    }
}
