<?php

namespace App\Actions\Settings;

use App\Models\CompanySetting;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class UpdateCompanySetting
{
    /**
     * @param  array{legal_name: string, tax_number?: string|null, tax_office?: string|null, address: string, footnote: string}  $data
     */
    public function execute(array $data, ?UploadedFile $logo = null): CompanySetting
    {
        $setting = CompanySetting::current();
        $taxNumber = trim((string) ($data['tax_number'] ?? ''));
        $taxOffice = trim((string) ($data['tax_office'] ?? ''));

        $setting->fill([
            'legal_name' => trim($data['legal_name']),
            'tax_number' => $taxNumber === '' ? null : $taxNumber,
            'tax_office' => $taxOffice === '' ? null : $taxOffice,
            'address' => trim($data['address']),
            'footnote' => trim($data['footnote']),
        ]);

        if ($logo !== null) {
            if ($setting->logo_path !== null) {
                Storage::disk('public')->delete($setting->logo_path);
            }

            $setting->logo_path = $logo->store('settings', 'public');
        }

        $setting->save();

        return $setting;
    }
}
