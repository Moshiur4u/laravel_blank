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

Route::middleware(['auth', 'verified'])->group(function () { // নির্দিষ্ট Activity Log permission অথবা আগের broad report access অনুমোদিত।
    Route::get('/user-activity-logs', [UserActivityLogController::class, 'index'])->middleware('permission:user.activity.logs') // dedicated Activity Log permission এবং broad report access।
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
        ->middleware('permission:voucher.report.miscellaneous|reports.all_access') // miscellaneous voucher report-এর নির্দিষ্ট permission।
        ->name('voucher-entries.report.export');
    Route::get('/voucher-entries/report', [VoucherEntryController::class, 'report'])
        ->middleware('permission:voucher.report.miscellaneous|reports.all_access') // miscellaneous voucher report দেখার নতুন permission।
        ->name('voucher-entries.report');
    Route::get('/voucher-report/combined/export', [CombinedVoucherReportController::class, 'export'])
        ->middleware('permission:voucher.report.view|reports.all_access') // combined report-এর নতুন permission।
        ->name('voucher-report.combined.export');
    Route::get('/voucher-report/combined', [CombinedVoucherReportController::class, 'index'])
        ->middleware('permission:voucher.report.view|reports.all_access') // combined report export-এর নতুন permission।
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
        ->middleware('permission:voucher.report.employee|reports.all_access') // employee payment report-এর নতুন permission।
        ->name('employee-payment.report.export');
    Route::get('/employee-payment-vouchers/report', [EmployeePaymentVoucherController::class, 'report'])
        ->middleware('permission:voucher.report.employee|reports.all_access') // employee payment report export-এর নতুন permission।
        ->name('employee-payment.report');
    Route::get('/employee-payment-vouchers/{employee}/ledger/export', [EmployeePaymentVoucherController::class, 'exportLedger'])
        ->whereNumber('employee')
        ->middleware('permission:voucher.report.employee|reports.all_access') // employee ledger report export-এর নতুন permission।
        ->name('employee-payment.ledger.export');
    Route::get('/employee-payment-vouchers/{employee}/ledger', [EmployeePaymentVoucherController::class, 'ledger'])
        ->whereNumber('employee')
        ->middleware('permission:voucher.report.employee|reports.all_access') // employee ledger report-এর নতুন permission।
        ->name('employee-payment.ledger');
});
// মিডলওয়্যার সহ ইউজার রাউট শুরু
Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/user/index', [UserController::class, 'index'])->middleware('permission:employee.index')->name('user.index');
    Route::get('/user/create', [UserController::class, 'create'])->middleware('permission:employee.create')->name('user.create');
    Route::post('/user/store', [UserController::class, 'store'])->middleware('permission:employee.create')->name('user.store');
    Route::get('/user/show/{id}', [UserController::class, 'show'])->middleware('permission:view-employee-details') // নতুন employee details permission।
        ->name('user.show');
    Route::get('/user/edit/{id}', [UserController::class, 'edit'])->middleware('permission:employee.edit|employee.role.assign')->name('user.edit');
    Route::put('/user/update/{id}', [UserController::class, 'update'])->middleware('permission:employee.edit|employee.role.assign')->name('user.update');
    Route::get('/user/statusupdate/{status_id}', [UserController::class, 'userstatusupdate'])->middleware('permission:employee.inactive')->name('user.statusupdate');
    Route::get('/user/destroy/{id}', [UserController::class, 'destroy'])->middleware('permission:employee.delete')->name('user.destroy');
});
// মিডলওয়্যার সহ রোল রাউট শুরু
Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/roles', [RoleController::class, 'index'])->middleware('permission:role.index') // role list-এর নতুন permission।
        ->name('roles.index');
    Route::get('/roles.create', [RoleController::class, 'create'])->middleware('permission:roles.create') // role তৈরির নতুন permission।
        ->name('roles.create');
    Route::post('/roles.store', [RoleController::class, 'store'])->middleware('permission:roles.create') // role সংরক্ষণেও create permission।
        ->name('roles.store');
    Route::get('/roles/{id}/edit', [RoleController::class, 'edit'])->middleware('permission:role.edit') // role সম্পাদনার নতুন permission।
        ->name('roles.edit');
    Route::put('/roles/{id}/update', [RoleController::class, 'update'])->middleware('permission:role.edit') // role update-এর নতুন permission।
        ->name('roles.update');
    Route::get('/roles.destroy/{id}', [RoleController::class, 'destroy'])->middleware('permission:role.delete') // role মুছতে নতুন permission।
        ->name('roles.destroy');
});
// মিডলওয়্যার সহ প্রোডক্ট ক্যাটাগরি রাউট শুরু
Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/productCategory/index', [ProductCategoryController::class, 'index'])->middleware('permission:category.index') // category তালিকার নতুন permission।
        ->name('category.index');
    Route::get('/productCategory/create', [ProductCategoryController::class, 'create'])->middleware('permission:product.category.create') // category তৈরির নতুন permission।
        ->name('category.create');
    Route::post('/productCategory/store', [ProductCategoryController::class, 'store'])->middleware('permission:product.category.create') // category সংরক্ষণেও create permission।
        ->name('category.store');
    Route::get('/productCategory/{id}', [ProductCategoryController::class, 'edit'])->middleware('permission:product.category.edit') // category সম্পাদনার নতুন permission।
        ->name('category.edit');
    Route::put('/productCategory/{id}/update', [ProductCategoryController::class, 'update'])->middleware('permission:product.category.update') // category update-এর নতুন permission।
        ->name('category.update');
    Route::get('/productCategory/destroy/{id}', [ProductCategoryController::class, 'destroy'])->middleware('permission:product.category.delete') // category মুছতে নতুন permission।
        ->name('category.destroy');
});

// মিডলওয়্যার সহ প্রোডক্ট ব্রান্ড রাউট শুরু
Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/product/brand/index', [BrandController::class, 'index'])->middleware('permission:brand.index') // brand তালিকার নতুন permission।
        ->name('brand.index');
    Route::get('/product/brand/create', [BrandController::class, 'create'])->middleware('permission:brand.create') // brand তৈরির নতুন permission।
        ->name('brand.create');
    Route::Post('/product/brand/store', [BrandController::class, 'store'])->middleware('permission:brand.create') // brand সংরক্ষণেও create permission।
        ->name('brand.store');
    Route::get('/product/brand/{id}/edit', [BrandController::class, 'edit'])->middleware('permission:brand.edit') // brand সম্পাদনার নতুন permission।
        ->name('brand.edit');
    Route::put('/product/brand/{id}/update', [BrandController::class, 'update'])->middleware('permission:brand.update') // brand update-এর নতুন permission।
        ->name('brand.update');
    Route::get('/product/brand/{id}/destroy', [BrandController::class, 'destroy'])->middleware('permission:brand.delete') // brand মুছতে নতুন permission।
        ->name('brand.destroy');
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
    Route::post('/supplier/ledger/{id}/payments', [SupplierledgerController::class, 'storePayment'])->middleware('permission:Supplier.ledger.payment') // নতুন casing-এর supplier ledger payment permission।
        ->name('supplier.ledger.payments.store');
    Route::post('/supplier/ledger/store', [SupplierledgerController::class, 'store'])->middleware('permission:Supplier.ledger.create') // supplier ledger তৈরি করার নতুন permission।
        ->name('supplier.ledger.store');
    Route::get('/supplier/ledger/{id}/edit', [SupplierledgerController::class, 'edit'])->middleware('permission:Supplier.ledger.edit') // নতুন casing-এর supplier ledger edit permission।
        ->name('supplier.ledger.edit');
    Route::put('/supplier/ledger/{id}/update', [SupplierledgerController::class, 'update'])->middleware('permission:Supplier.ledger.edit') // update-এ একই নতুন edit permission।
        ->name('supplier.ledger.update');
    Route::get('/supplier/ledger/{id}/destroy', [SupplierledgerController::class, 'destroy'])->middleware('permission:Supplier.ledger.delete') // নতুন casing-এর supplier ledger delete permission।
        ->name('supplier.ledger.destroy');
});

// মিডলওয়্যার সহ প্রোডক্ট রাউট শুরু
Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/product/index', [ProductController::class, 'index'])->middleware('permission:product.index') // product তালিকার নতুন permission।
        ->name('product.index');
    Route::get('/product/create', [ProductController::class, 'create'])->middleware('permission:product.create') // product তৈরির নতুন permission।
        ->name('product.create');
    Route::Post('/product/store', [ProductController::class, 'store'])->middleware('permission:product.create') // product সংরক্ষণেও create permission।
        ->name('product.store');
    Route::get('/product/edit/{id}', [ProductController::class, 'edit'])->middleware('permission:product.edit') // product সম্পাদনার নতুন permission।
        ->name('product.edit');
    Route::put('/product/update/{id}', [ProductController::class, 'update'])->middleware('permission:product.update') // product update-এর নতুন permission।
        ->name('product.update');
    Route::get('/product/destroy/{id}', [ProductController::class, 'destroy'])->middleware('permission:product.delete') // product মুছতে নতুন permission।
        ->name('product.destroy');

    // লগইন করা ও verified ব্যবহারকারীদের জন্য পণ্যভিত্তিক ক্রয় ও মজুত রিপোর্ট।
    Route::get('/reports/product-inventory', [ProductInventoryReportController::class, 'index'])
        ->middleware('permission:product.report.inventory|reports.all_access') // inventory report-এর নতুন permission এবং broad access।
        ->name('product.inventory.report');
    Route::get('/reports/product-inventory/export', [ProductInventoryReportController::class, 'export'])
        ->middleware('permission:product.report.inventory|reports.all_access') // inventory export-এও একই access policy।
        ->name('product.inventory.report.export');
});

// বিক্রির ইনভয়েস, ফোন দিয়ে গ্রাহক খোঁজা এবং প্রিন্ট
Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/sales', [SaleController::class, 'index'])->middleware('permission:sales.create|sales.view')->name('sales.index');
    Route::get('/sales/customer-lookup', [SaleController::class, 'customerByPhone'])->middleware('permission:sales.create')->name('sales.customer.lookup');
    Route::post('/sales', [SaleController::class, 'store'])->middleware('permission:sales.create')->name('sales.store');
    Route::get('/sales/invoice/{id}', [SaleController::class, 'invoice'])->middleware('permission:sales.report')->name('sales.invoice');
    Route::get('/sales/report/export', [SalesReportController::class, 'export'])->middleware('permission:sales.view')->name('sales.report.export');
    Route::get('/sales/report', [SalesReportController::class, 'index'])->middleware('permission:reports.all_access|sales.report') // পুরোনো dashboard permission-এর বদলে sales.report।
        ->name('sales.report');
});

// মিডলওয়্যার সহ কাস্টমার রাউট শুরু
Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/customer/index', [CustomerController::class, 'index'])->middleware('permission:customer.index')->name('customer.index');
    Route::get('/customer/create', [CustomerController::class, 'create'])->middleware('permission:customer.create')->name('customer.create');
    Route::post('/customer/store', [CustomerController::class, 'store'])->middleware('permission:customer.create')->name('customer.store');
    Route::get('/customer/{id}/ledger', [CustomerController::class, 'ledger'])->middleware('permission:customer.ledger')->name('customer.ledger');
    Route::post('/customer/ledger/store', [CustomerController::class, 'ledgerStore'])->middleware('permission:Customer.ledger.payment') // seeder-এর নতুন casing অনুযায়ী ledger payment permission।
        ->name('customer.ledger.store');
    Route::get('/customer/ledger/{id}/invoice', [CustomerController::class, 'ledgerInvoice'])->middleware('permission:customer.ledger') // customer sale বা payment-এর printable invoice/receipt দেখায়।
        ->name('customer.ledger.invoice'); // invoice/receipt link-এর named route।
    Route::post('/customer/ledger/{id}/payments', [CustomerController::class, 'ledgerPaymentStore'])->middleware('permission:Customer.ledger.payment') // sale-এর due-তে date-সহ payment যোগের অনুমতি যাচাই।
        ->name('customer.ledger.payments.store'); // sale installment form-এর named route।
    Route::get('/customer/ledger/{id}/edit', [CustomerController::class, 'ledgerEdit'])->middleware('permission:Customer.ledger.edit') // customer ledger edit permission যাচাই।
        ->name('customer.ledger.edit'); // edit link-এর named route।
    Route::put('/customer/ledger/{id}/update', [CustomerController::class, 'ledgerUpdate'])->middleware('permission:Customer.ledger.edit') // একই edit permission দিয়ে validated ledger update করে।
        ->name('customer.ledger.update'); // edit form-এর update route।
    Route::delete('/customer/ledger/{id}', [CustomerController::class, 'ledgerDestroy'])->middleware('permission:Customer.ledger.delete') // ledger delete permission যাচাই এবং DELETE method বাধ্যতামূলক।
        ->name('customer.ledger.destroy'); // delete form-এর named route।
    Route::get('/customer/{id}/edit', [CustomerController::class, 'edit'])->middleware('permission:customer.edit')->name('customer.edit');
    Route::put('/customer/{id}/update', [CustomerController::class, 'update'])->middleware('permission:customer.edit') // update operation-এ seeder-এর customer.edit permission।
        ->name('customer.update');
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