<?php

namespace App\Http\Controllers;

use App\Actions\Settings\UpdateCompanySetting;
use App\Http\Requests\Settings\UpdateCompanySettingRequest;
use App\Models\CompanySetting;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class CompanySettingController extends Controller
{
    public function edit(): View
    {
        $this->authorize('update', CompanySetting::class);

        return view('settings.edit', [
            'setting' => CompanySetting::current(),
        ]);
    }

    public function update(UpdateCompanySettingRequest $request, UpdateCompanySetting $updateCompanySetting): RedirectResponse
    {
        $updateCompanySetting->execute(
            $request->safe()->only(['legal_name', 'tax_number', 'tax_office', 'address', 'footnote', 'notification_email']),
            $request->file('logo'),
        );

        return redirect()->route('settings.edit')->with('status', __('Company details saved.'));
    }
}
