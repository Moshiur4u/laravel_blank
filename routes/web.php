<?php

use App\Http\Controllers\BrandController;
use App\Http\Controllers\CombinedVoucherReportController;
use App\Http\Controllers\CustomerController;
use App\Http\Controllers\DashboardConrtoller;
use App\Http\Controllers\EmployeePaymentVoucherController;
use App\Http\Controllers\OrganizationController;
use App\Http\Controllers\ProductCategoryController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\ProductInventoryReportController;
use App\Http\Controllers\RoleController;
use App\Http\Controllers\SaleController;
use App\Http\Controllers\SalesReportController;
use App\Http\Controllers\SupplierController;
use App\Http\Controllers\SupplierledgerController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\UserActivityLogController;
use App\Http\Controllers\VoucherEntryController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified', 'permission:reports.all_access'])->group(function () {
    Route::get('/user-activity-logs', [UserActivityLogController::class, 'index'])
        ->name('user-activity-logs.index');
});

Route::get('/', function () {
    return view('frontend.loginpage.login');
});
Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/logout', [UserController::class, 'logout'])->name('logout');
});
// মিডলওয়্যার সহ ড্যাশবোর্ড রাউট শুরু
Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/dashboard', [DashboardConrtoller::class, 'dashboard'])->middleware('permission:Dashboard')->name('dashboard');
    Route::get('/dashboard-body', [DashboardConrtoller::class, 'dashboardbody'])->middleware('permission:Dashboard')->name('dashboardbody');
});
// organization profile শুধুমাত্র authenticated এবং verified user পরিবর্তন করতে পারে।
Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/organization', [OrganizationController::class, 'edit'])->middleware('permission:organization info.access')->name('organization.edit');
    Route::put('/organization', [OrganizationController::class, 'update'])->middleware('permission:organization info.access')->name('organization.update');
});
// সাধারণ Debit/Credit voucher entry ও দৈনিক report login এবং email verification চায়।
Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/voucher-entries', [VoucherEntryController::class, 'index'])->middleware('permission:voucher')->name('voucher-entries.index');
    Route::post('/voucher-entries', [VoucherEntryController::class, 'store'])->middleware('permission:voucher.entry')->name('voucher-entries.store');
    Route::get('/voucher-entries/report/export', [VoucherEntryController::class, 'exportReport'])
        ->middleware('permission:reports.vouchers|reports.all_access')
        ->name('voucher-entries.report.export');
    Route::get('/voucher-entries/report', [VoucherEntryController::class, 'report'])
        ->middleware('permission:reports.vouchers|reports.all_access')
        ->name('voucher-entries.report');
    Route::get('/voucher-report/combined/export', [CombinedVoucherReportController::class, 'export'])
        ->middleware('permission:reports.vouchers|reports.all_access')
        ->name('voucher-report.combined.export');
    Route::get('/voucher-report/combined', [CombinedVoucherReportController::class, 'index'])
        ->middleware('permission:reports.vouchers|reports.all_access')
        ->name('voucher-report.combined');
});
// কর্মী ব্যয়ের voucher দেখানো ও জমা দেওয়া—দুই route-ই login এবং email verification চায়।
Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/employee-payment-vouchers', [EmployeePaymentVoucherController::class, 'index'])
        ->middleware('permission:employee_payment_voucher.entry')
        ->name('employee-payment.index');
    Route::post('/employee-payment-vouchers', [EmployeePaymentVoucherController::class, 'store'])
        ->middleware('permission:employee_payment_voucher.entry')
        ->name('employee-payment.store');
    Route::get('/employee-payment-vouchers/report/export', [EmployeePaymentVoucherController::class, 'exportReport'])
        ->middleware('permission:reports.vouchers|reports.all_access')
        ->name('employee-payment.report.export');
    Route::get('/employee-payment-vouchers/report', [EmployeePaymentVoucherController::class, 'report'])
        ->middleware('permission:reports.vouchers|reports.all_access')
        ->name('employee-payment.report');
    Route::get('/employee-payment-vouchers/{employee}/ledger/export', [EmployeePaymentVoucherController::class, 'exportLedger'])
        ->whereNumber('employee')
        ->middleware('permission:reports.vouchers|reports.all_access')
        ->name('employee-payment.ledger.export');
    Route::get('/employee-payment-vouchers/{employee}/ledger', [EmployeePaymentVoucherController::class, 'ledger'])
        ->whereNumber('employee')
        ->middleware('permission:reports.vouchers|reports.all_access')
        ->name('employee-payment.ledger');
});
// মিডলওয়্যার সহ ইউজার রাউট শুরু
Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/user/index', [UserController::class, 'index'])->middleware('permission:employee.index')->name('user.index');
    Route::get('/user/create', [UserController::class, 'create'])->middleware('permission:employee.create')->name('user.create');
    Route::post('/user/store', [UserController::class, 'store'])->middleware('permission:employee.create')->name('user.store');
    Route::get('/user/show/{id}', [UserController::class, 'show'])->middleware('permission:employee.view')->name('user.show');
    Route::get('/user/edit/{id}', [UserController::class, 'edit'])->middleware('permission:employee.edit')->name('user.edit');
    Route::put('/user/update/{id}', [UserController::class, 'update'])->middleware('permission:employee.edit')->name('user.update');
    Route::get('/user/statusupdate/{status_id}', [UserController::class, 'userstatusupdate'])->middleware('permission:employee.inactive')->name('user.statusupdate');
    Route::get('/user/destroy/{id}', [UserController::class, 'destroy'])->middleware('permission:employee.delete')->name('user.destroy');
});
// মিডলওয়্যার সহ রোল রাউট শুরু
Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/roles', [RoleController::class, 'index'])->middleware('permission:role-menu')->name('roles.index');
    Route::get('/roles.create', [RoleController::class, 'create'])->middleware('permission:create-role')->name('roles.create');
    Route::post('/roles.store', [RoleController::class, 'store'])->middleware('permission:create-role')->name('roles.store');
    Route::get('/roles/{id}/edit', [RoleController::class, 'edit'])->middleware('permission:edit-role-permissions')->name('roles.edit');
    Route::put('/roles/{id}/update', [RoleController::class, 'update'])->middleware('permission:edit-role-permissions')->name('roles.update');
    Route::get('/roles.destroy/{id}', [RoleController::class, 'destroy'])->middleware('permission:delete-role')->name('roles.destroy');
});
// মিডলওয়্যার সহ প্রোডক্ট ক্যাটাগরি রাউট শুরু
Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/productCategory/index', [ProductCategoryController::class, 'index'])->middleware('permission:Categories')->name('category.index');
    Route::get('/productCategory/create', [ProductCategoryController::class, 'create'])->middleware('permission:brand_category.create')->name('category.create');
    Route::post('/productCategory/store', [ProductCategoryController::class, 'store'])->middleware('permission:brand_category.create')->name('category.store');
    Route::get('/productCategory/{id}', [ProductCategoryController::class, 'edit'])->middleware('permission:brand_category.edit')->name('category.edit');
    Route::put('/productCategory/{id}/update', [ProductCategoryController::class, 'update'])->middleware('permission:brand_category.update')->name('category.update');
    Route::get('/productCategory/destroy/{id}', [ProductCategoryController::class, 'destroy'])->middleware('permission:brand_category.delete')->name('category.destroy');
});

// মিডলওয়্যার সহ প্রোডক্ট ব্রান্ড রাউট শুরু
Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/product/brand/index', [BrandController::class, 'index'])->middleware('permission:Brands')->name('brand.index');
    Route::get('/product/brand/create', [BrandController::class, 'create'])->middleware('permission:brand_category.create')->name('brand.create');
    Route::Post('/product/brand/store', [BrandController::class, 'store'])->middleware('permission:brand_category.create')->name('brand.store');
    Route::get('/product/brand/{id}/edit', [BrandController::class, 'edit'])->middleware('permission:brand_category.edit')->name('brand.edit');
    Route::put('/product/brand/{id}/update', [BrandController::class, 'update'])->middleware('permission:brand_category.update')->name('brand.update');
    Route::get('/product/brand/{id}/destroy', [BrandController::class, 'destroy'])->middleware('permission:brand_category.delete')->name('brand.destroy');
});
// মিডলওয়্যার সহ সাপ্লায়ার রাউট শুরু
Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/supplier/index', [SupplierController::class, 'index'])->middleware('permission:supplier.index')->name('supplier.index');
    Route::get('/supplier/create', [SupplierController::class, 'create'])->middleware('permission:supplier.create')->name('supplier.create');
    Route::post('/supplier/store', [SupplierController::class, 'store'])->middleware('permission:supplier.create')->name('supplier.store');
    Route::get('/supplier/{id}/show', [SupplierController::class, 'show'])->middleware('permission:supplier.index')->name('supplier.show');
    Route::get('/supplier/{id}/edit', [SupplierController::class, 'edit'])->middleware('permission:supplier.edit')->name('supplier.edit');
    Route::put('/supplier/{id}/update', [SupplierController::class, 'update'])->middleware('permission:supplier.edit')->name('supplier.update');
    Route::get('/supplier/{id}/destroy', [SupplierController::class, 'destroy'])->middleware('permission:supplier.delete')->name('supplier.destroy');

    // সাপ্লায়ার লেজার রাউট
    Route::get('/supplier/{id}/ledger', [SupplierledgerController::class, 'index'])->middleware('permission:supplier.ledger')->name('supplier.ledger');
    // নির্দিষ্ট লেজার এন্ট্রির জন্য প্রিন্টযোগ্য সাপ্লায়ার ইনভয়েস দেখায়; কোনো ডেটা পরিবর্তন করে না।
    Route::get('/supplier/ledger/{id}/invoice', [SupplierledgerController::class, 'invoice'])->middleware('permission:supplier.ledger')->name('supplier.ledger.invoice');
    // তারিখসহ supplier payment জমা ও ledger-এর payment/due update করে।
    Route::post('/supplier/ledger/{id}/payments', [SupplierledgerController::class, 'storePayment'])->middleware('permission:ledger.payment')->name('supplier.ledger.payments.store');
    Route::post('/supplier/ledger/store', [SupplierledgerController::class, 'store'])->middleware('permission:supplier.ledger.create')->name('supplier.ledger.store');
    Route::get('/supplier/ledger/{id}/edit', [SupplierledgerController::class, 'edit'])->middleware('permission:supplier.ledger.edit')->name('supplier.ledger.edit');
    Route::put('/supplier/ledger/{id}/update', [SupplierledgerController::class, 'update'])->middleware('permission:supplier.ledger.edit')->name('supplier.ledger.update');
    Route::get('/supplier/ledger/{id}/destroy', [SupplierledgerController::class, 'destroy'])->middleware('permission:supplier.ledger.delete')->name('supplier.ledger.destroy');
});

// মিডলওয়্যার সহ প্রোডক্ট রাউট শুরু
Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/product/index', [ProductController::class, 'index'])->middleware('permission:Products')->name('product.index');
    Route::get('/product/create', [ProductController::class, 'create'])->middleware('permission:create-product')->name('product.create');
    Route::Post('/product/store', [ProductController::class, 'store'])->middleware('permission:create-product')->name('product.store');
    Route::get('/product/edit/{id}', [ProductController::class, 'edit'])->middleware('permission:edit-product-details')->name('product.edit');
    Route::put('/product/update/{id}', [ProductController::class, 'update'])->middleware('permission:edit-product-details')->name('product.update');
    Route::get('/product/destroy/{id}', [ProductController::class, 'destroy'])->middleware('permission:archive-product')->name('product.destroy');

    // লগইন করা ও verified ব্যবহারকারীদের জন্য পণ্যভিত্তিক ক্রয় ও মজুত রিপোর্ট।
    Route::get('/reports/product-inventory', [ProductInventoryReportController::class, 'index'])
        ->middleware('permission:reports.all_access')
        ->name('product.inventory.report');
    Route::get('/reports/product-inventory/export', [ProductInventoryReportController::class, 'export'])
        ->middleware('permission:reports.all_access')
        ->name('product.inventory.report.export');
});

// বিক্রির ইনভয়েস, ফোন দিয়ে গ্রাহক খোঁজা এবং প্রিন্ট
Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/sales', [SaleController::class, 'index'])->middleware('permission:sales.create|sales.view')->name('sales.index');
    Route::get('/sales/customer-lookup', [SaleController::class, 'customerByPhone'])->middleware('permission:sales.create')->name('sales.customer.lookup');
    Route::post('/sales', [SaleController::class, 'store'])->middleware('permission:sales.create')->name('sales.store');
    Route::get('/sales/invoice/{id}', [SaleController::class, 'invoice'])->middleware('permission:sales.view|reports.all_access')->name('sales.invoice');
    Route::get('/sales/report/export', [SalesReportController::class, 'export'])->middleware('permission:reports.all_access')->name('sales.report.export');
    Route::get('/sales/report', [SalesReportController::class, 'index'])->middleware('permission:reports.all_access')->name('sales.report');
});

// মিডলওয়্যার সহ কাস্টমার রাউট শুরু
Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/customer/index', [CustomerController::class, 'index'])->middleware('permission:customer.index')->name('customer.index');
    Route::get('/customer/create', [CustomerController::class, 'create'])->middleware('permission:customer.create')->name('customer.create');
    Route::post('/customer/store', [CustomerController::class, 'store'])->middleware('permission:customer.create')->name('customer.store');
    Route::get('/customer/{id}/ledger', [CustomerController::class, 'ledger'])->middleware('permission:customer.ledger')->name('customer.ledger');
    Route::post('/customer/ledger/store', [CustomerController::class, 'ledgerStore'])->middleware('permission:customer.ledger.payment')->name('customer.ledger.store');
    Route::get('/customer/{id}/edit', [CustomerController::class, 'edit'])->middleware('permission:customer.edit')->name('customer.edit');
    Route::put('/customer/{id}/update', [CustomerController::class, 'update'])->middleware('permission:customer.update')->name('customer.update');
    Route::get('/customer/{id}/destroy', [CustomerController::class, 'destroy'])->middleware('permission:customer.delete')->name('customer.destroy');
});

// Route::get('/dashboard', function () {
//     return view('dashboard.dashboard');
// })->middleware(['auth', 'verified'])->name('dashboard');

// Route::middleware('auth')->group(function () {
//     Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
//     Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
//     Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
// });

require __DIR__ . '/auth.php';
