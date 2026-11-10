<?php

namespace Tests\Feature;

use App\Http\Controllers\ProductInventoryReportController;
use App\Models\Product;
use App\Models\SaleItem;
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
            'purchase_price' => '15.00',
        ]);
        Product::create([
            'productName' => 'Expired Product',
            'unit' => '2',
            'expiry_date' => today()->subDay()->toDateString(),
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
        $returnedSaleItem = SaleItem::create([
            'product_id' => $product->id,
            'product_name' => 'Rice',
            'quantity' => 3,
            'purchase_price' => '10.00',
            'unit_price' => '20.00',
            'line_total' => '60.00',
        ]);
        \Illuminate\Support\Facades\DB::table('product_returns')->insert([
            'sale_invoice_id' => 1,
            'sale_item_id' => $returnedSaleItem->id,
            'product_id' => $product->id,
            'quantity' => 1,
            'return_date' => today()->toDateString(),
            'return_amount' => '33.00',
            'refund_amount' => '0.00',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        SaleItem::create([
            'product_id' => $product->id,
            'product_name' => 'Rice',
            'quantity' => 2,
            'purchase_price' => null,
            'unit_price' => '25.00',
            'line_total' => '50.00',
        ]);

        $view = app(ProductInventoryReportController::class)->index();
        $data = $view->getData();
        $row = $data['rows']->firstWhere('name', 'Rice');

        $this->assertSame('backend.reports.productinventory', $view->name());
        $this->assertSame($product->id, $row['id']);
        $this->assertEquals(15, $row['purchased_quantity']);
        $this->assertEquals(310, $row['purchase_amount']);
        $this->assertEquals(7, $row['stock']);
        $this->assertEquals(12.5, $row['purchase_price']);
        $this->assertEquals(22.5, $row['avg_selling_price']);
        $this->assertEquals(4, $row['quantity_sold']);
        $this->assertEquals(90, $row['revenue']);
        $this->assertEquals(50, $row['total_cost']);
        $this->assertEquals(40, $row['profit']);
        $this->assertSame('expiring', $row['expiry_status']);
        $this->assertCount(2, $row['suppliers']);
        $this->assertSame('01700000001', $row['suppliers'][0]['phone']);
        $this->assertEquals(2, $data['total_products']);
        $this->assertEquals(15, $data['total_purchased_quantity']);
        $this->assertSame(1, $data['unique_products_purchased']);
        $this->assertEquals(310, $data['total_purchase_amount']);
        $this->assertEquals(7, $data['total_stock']);
        $this->assertSame(1, $data['total_expired_products']);
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
            $table->decimal('purchase_price', 10, 2)->nullable();
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

        Schema::create('sale_items', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('sale_invoice_id')->nullable();
            $table->foreignId('product_id')->nullable();
            $table->string('product_name');
            $table->decimal('quantity', 15, 2);
            $table->decimal('purchase_price', 15, 2)->nullable();
            $table->decimal('unit_price', 15, 2);
            $table->decimal('line_total', 15, 2);
            $table->timestamps();
        });

        Schema::create('product_returns', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('sale_invoice_id');
            $table->unsignedBigInteger('sale_item_id');
            $table->unsignedBigInteger('product_id')->nullable();
            $table->unsignedInteger('quantity');
            $table->date('return_date');
            $table->decimal('return_amount', 15, 2)->default(0);
            $table->decimal('refund_amount', 15, 2)->default(0);
            $table->timestamps();
        });
    }
}