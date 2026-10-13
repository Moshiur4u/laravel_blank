<?php

namespace App\Http\Controllers;

use App\Models\Organization;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\View\View;

class OrganizationController extends Controller
{
    /**
     * একক organization profile তৈরির/সম্পাদনার form দেখায়।
     */
    public function edit(): View
    {
        $organization = Organization::query()->find(1) ?? new Organization();

        return view('frontend.organization.addOrganization', compact('organization'));
    }

    /**
     * যাচাই করা organization তথ্য ও ঐচ্ছিক logo সংরক্ষণ করে।
     */
    public function update(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'address' => 'nullable|string|max:2000',
            'phone' => 'nullable|string|max:50',
            'email' => 'nullable|email|max:255',
            'logo' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:2048',
        ]);

        $organization = Organization::query()->firstOrNew(['id' => 1]);
        $oldLogo = $organization->logo;
        unset($validated['logo']);

        if ($request->hasFile('logo')) {
            $logo = $request->file('logo');
            $directory = public_path('uploads/organizations');
            File::ensureDirectoryExists($directory);

            $logoName = $logo->hashName();
            $logo->move($directory, $logoName);
            $validated['logo'] = $logoName;
        }

        $organization->fill($validated);
        $organization->save();

        if (
            isset($validated['logo'])
            && $oldLogo
            && File::exists(public_path('uploads/organizations/' . $oldLogo))
        ) {
            File::delete(public_path('uploads/organizations/' . $oldLogo));
        }

        return redirect()->route('organization.edit')
            ->with('success', 'Organization information saved successfully.');
    }
}
