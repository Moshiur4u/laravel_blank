<?php

namespace Tests\Feature;

use App\Http\Controllers\ProductInventoryReportController;
use App\Models\Product;
use App\Models\Supplier;
use App\Models\Supplierledger;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class ProductInventoryReportTest extends TestCase
{
    public function test_report_route_is_registered(): void
    {
        $route = Route::getRoutes()->getByName('product.inventory.report');

        $this->assertNotNull($route);
        $this->assertSame('reports/product-inventory', $route->uri());
        $this->assertSame(
            ProductInventoryReportController::class.'@index',
            $route->getActionName()
        );
    }

    public function test_report_calculates_purchase_totals_stock_expiry_and_suppliers(): void
    {
        if (! extension_loaded('pdo_sqlite')) {
            $this->markTestSkipped('The pdo_sqlite extension is required for the report data test.');
        }

        $this->createReportSchema();

        $supplierOne = Supplier::create([
            'name' => 'Supplier One',
            'phone' => '01700000001',
        ]);
        $supplierTwo = Supplier::create([
            'name' => 'Supplier Two',
            'phone' => '01700000002',
        ]);
        $product = Product::create([
            'productName' => 'Rice',
            'unit' => '7',
            'expiry_date' => today()->addDays(5)->toDateString(),
            'supplier_id' => $supplierOne->id,
        ]);

        Supplierledger::create([
            'supplier_id' => $supplierOne->id,
            'product_id' => (string) $product->id,
            'quantity' => '10',
            'total' => '200.00',
        ]);
        Supplierledger::create([
            'supplier_id' => $supplierTwo->id,
            'product_id' => (string) $product->id,
            'quantity' => '5',
            'total' => '110.00',
        ]);

        $view = app(ProductInventoryReportController::class)->index();
        $data = $view->getData();
        $row = $data['rows']->first();

        $this->assertSame('backend.reports.productinventory', $view->name());
        $this->assertEquals(15, $row['purchased_quantity']);
        $this->assertEquals(310, $row['purchase_amount']);
        $this->assertEquals(7, $row['stock']);
        $this->assertSame('expiring', $row['expiry_status']);
        $this->assertCount(2, $row['suppliers']);
        $this->assertSame('01700000001', $row['suppliers'][0]['phone']);
        $this->assertEquals(15, $data['total_purchased_quantity']);
        $this->assertEquals(310, $data['total_purchase_amount']);
        $this->assertEquals(7, $data['total_stock']);
    }

    private function createReportSchema(): void
    {
        Schema::create('suppliers', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->string('phone')->nullable();
            $table->timestamps();
        });

        Schema::create('products', function (Blueprint $table): void {
            $table->id();
            $table->string('productName');
            $table->string('unit');
            $table->date('expiry_date')->nullable();
            $table->foreignId('supplier_id')->nullable();
            $table->timestamps();
        });

        Schema::create('supplierledgers', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('supplier_id');
            $table->string('product_id')->nullable();
            $table->string('quantity')->nullable();
            $table->string('total')->nullable();
            $table->timestamps();
        });
    }
}