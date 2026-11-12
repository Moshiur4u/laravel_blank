<?php

use App\Models\Organization;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Permission;

uses(RefreshDatabase::class);

test('organization logo filename is based on the organization name', function () {
    $name = 'Green Valley Ltd ' . Str::uuid();
    $logoName = str_replace(' ', '-', $name) . '.png';
    $logoPath = public_path('uploads/organizations/' . $logoName);
    $user = User::factory()->create(['email_verified_at' => now()]);
    $user->givePermissionTo(Permission::findOrCreate('organization info.access', 'web'));

    try {
        $this->actingAs($user)
            ->put(route('organization.update'), [
                'name' => $name,
                'logo' => UploadedFile::fake()->image($logoName),
            ])
            ->assertRedirect(route('organization.edit'));

        expect(Organization::query()->value('logo'))->toBe($logoName)
            ->and(File::exists($logoPath))->toBeTrue();
    } finally {
        File::delete($logoPath);
    }
});
