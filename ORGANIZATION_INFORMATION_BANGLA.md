# Organization Logo Filename — বাংলা ডকুমেন্টেশন

এই নথিতে organization logo-র filename প্রতিষ্ঠানের নাম অনুযায়ী করার পরিবর্তন এবং সংশ্লিষ্ট বর্তমান কাঠামো ব্যাখ্যা করা হয়েছে। এই পরিবর্তনে Controller-এর upload logic এবং তার feature test যোগ/পরিবর্তন করা হয়েছে। Migration, route, model ও Blade template-এ কোনো code পরিবর্তন করা হয়নি; নিচে সেগুলোর বর্তমান code কেবল প্রাসঙ্গিকতা বোঝাতে দেখানো হয়েছে।

## Controller (Code and Explanation)

**ফাইল:** `app/Http/Controllers/OrganizationController.php`

### Logo upload ও filename তৈরি

```php
if ($request->hasFile('logo')) {
    $logo = $request->file('logo');
    $directory = public_path('uploads/organizations');
    File::ensureDirectoryExists($directory);

    // প্রতিষ্ঠানের নামকে নিরাপদ ফাইলনেমে রূপান্তর করে logo সংরক্ষণ করে।
    $logoBaseName = preg_replace('/[^\pL\pN._-]+/u', '-', trim($validated['name']));
    $logoBaseName = trim($logoBaseName ?? '', '.-_');
    $logoBaseName = $logoBaseName !== '' ? $logoBaseName : 'organization';
    $logoName = $logoBaseName . '.' . $logo->extension();
    $logo->move($directory, $logoName);
    $validated['logo'] = $logoName;
}
```

- `$validated['name']` হলো form থেকে যাচাই করা প্রতিষ্ঠানের নাম; এই নাম থেকেই logo filename-এর মূল অংশ তৈরি হয়।
- বাংলা ও অন্যান্য Unicode অক্ষর এবং `.` / `_` / `-` রাখা হয়। নামের অন্য character-গুলো `-` দিয়ে বদলানো হয়, যাতে নামটি filesystem filename হিসেবে ব্যবহার করা যায়।
- নামটি sanitize করার পর ফাঁকা হলে `organization` fallback ব্যবহৃত হয়।
- আপলোড করা image-এর নির্ধারিত extension শেষে যোগ হয়। যেমন `Green Valley Ltd` নামের PNG logo হলে filename হবে `Green-Valley-Ltd.png`।
- ফাইল `public/uploads/organizations`-এ রাখা হয় এবং filename database-এ `logo` হিসেবে সংরক্ষিত হয়।

### পুরোনো logo সরানো

```php
if (
    isset($validated['logo'])
    && $oldLogo
    && $oldLogo !== $validated['logo']
    && File::exists(public_path('uploads/organizations/' . $oldLogo))
) {
    File::delete(public_path('uploads/organizations/' . $oldLogo));
}
```

নতুন filename পুরোনো filename থেকে আলাদা হলে এবং পুরোনো ফাইলটি থাকলেই সেটি মুছে ফেলা হয়। একই filename-এ logo বদলালে নতুন upload যেন save-এর পর মুছে না যায়, তাই filename তুলনা করা হয়।

### সংশ্লিষ্ট Feature Test

**ফাইল:** `tests/Feature/OrganizationLogoTest.php`

```php
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
```

Test-টি authenticated ও অনুমতিপ্রাপ্ত user দিয়ে logo upload করে database filename এবং public directory-তে ফাইল তৈরি হয়েছে কি না যাচাই করে। Test শেষে শুধু test-এর তৈরি নির্দিষ্ট ফাইলটি মুছে ফেলা হয়।

## Migration (Code and Explanation)

**ফাইল:** `database/migrations/2026_10_13_182123_create_organizations_table.php` — এই পরিবর্তনে অপরিবর্তিত।

```php
Schema::create('organizations', function (Blueprint $table) {
    $table->id();
    $table->string('name');
    $table->text('address')->nullable();
    $table->string('phone', 50)->nullable();
    $table->string('logo')->nullable();
    $table->timestamps();
});
```

এই migration organization-এর নাম, ঠিকানা, ফোন ও logo filename রাখার table তৈরি করে। Logo filename-টি `logo` column-এ থাকে; logo filename-এ organization name ব্যবহার করতে নতুন column বা schema পরিবর্তন প্রয়োজন হয়নি।

**ফাইল:** `database/migrations/2026_10_14_005500_add_email_to_organizations_table.php` — এই পরিবর্তনে অপরিবর্তিত।

```php
Schema::table('organizations', function (Blueprint $table) {
    $table->string('email')->nullable()->after('phone');
});
```

এটি বিদ্যমান organization table-এ nullable email column যোগ করে। Logo filename পরিবর্তনের সঙ্গে migration-টির কোনো আচরণগত পরিবর্তন নেই।

## Route (Code and Explanation)

**ফাইল:** `routes/web.php` — এই পরিবর্তনে অপরিবর্তিত।

```php
Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/organization', [OrganizationController::class, 'edit'])
        ->middleware('permission:organization info.access')
        ->name('organization.edit');
    Route::put('/organization', [OrganizationController::class, 'update'])
        ->middleware('permission:organization info.access')
        ->name('organization.update');
});
```

`GET /organization` সম্পাদনার form দেখায় এবং `PUT /organization` Controller-এর `update()` চালায়। দুই route-ই login, email verification ও `organization info.access` permission চায়। Filename তৈরির পরিবর্তনে route পরিবর্তনের প্রয়োজন হয়নি।

## Model (Code and Explanation)

**ফাইল:** `app/Models/Organization.php` — এই পরিবর্তনে অপরিবর্তিত।

```php
class Organization extends Model
{
    protected $fillable = [
        'name',
        'address',
        'phone',
        'email',
        'logo',
    ];
}
```

`logo` আগে থেকেই `$fillable`-এ আছে, তাই Controller-এ তৈরি filename organization record-এ সংরক্ষণ করা যায়। Model পরিবর্তনের প্রয়োজন হয়নি।

## Blade Template (Code and Explanation)

**ফাইল:** `resources/views/frontend/organization/addOrganization.blade.php` — এই পরিবর্তনে অপরিবর্তিত।

```blade
<form action="{{ route('organization.update') }}" method="POST" enctype="multipart/form-data">
    @csrf
    @method('PUT')

    <input type="file" name="logo" id="logo"
        class="form-control @error('logo') is-invalid @enderror"
        accept=".jpg,.jpeg,.png,.webp,image/jpeg,image/png,image/webp">

    @if ($organization->logo)
        <img src="{{ asset('uploads/organizations/' . $organization->logo) }}"
            alt="{{ $organization->name }} logo"
            style="max-width: 180px; max-height: 120px; object-fit: contain;">
    @endif
</form>
```

Form-টি `multipart/form-data` দিয়ে `logo` file পাঠায় এবং সংরক্ষিত filename দিয়ে বর্তমান logo দেখায়। নতুন filename তৈরির কাজ Controller-এ হওয়ায় Blade template-এ কোনো পরিবর্তন দরকার হয়নি।
