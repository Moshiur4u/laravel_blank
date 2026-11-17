<?php

namespace Tests\Feature;

use App\Http\Controllers\ProductSubcategoryController;
use App\Models\ProductCategory;
use App\Models\ProductSubcategory;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class ProductSubcategoryTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        if (! extension_loaded('pdo_sqlite')) {
            $this->markTestSkipped('The pdo_sqlite extension is required for subcategory tests.');
        }

        Schema::create('product_categories', function (Blueprint $table): void {
            $table->id();
            $table->string('category_name');
            $table->timestamps();
        });

        Schema::create('product_subcategories', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('category_id');
            $table->string('name');
            $table->timestamps();
            $table->unique(['category_id', 'name']);
        });

        Schema::create('products', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('product_categorie_id');
            $table->foreignId('subcategory_id')->nullable()
                ->constrained('product_subcategories')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function test_subcategory_can_be_created_updated_deleted_and_listed_for_its_category(): void
    {
        $category = ProductCategory::create(['category_name' => 'Food']);
        $newCategory = ProductCategory::create(['category_name' => 'Household']);
        $controller = app(ProductSubcategoryController::class);
        $storeRequest = Request::create(
            '/product/subcategories',
            'POST',
            ['category_id' => $category->id, 'name' => 'Dry Goods'],
            [],
            [],
            ['HTTP_ACCEPT' => 'application/json', 'HTTP_X_REQUESTED_WITH' => 'XMLHttpRequest']
        );

        $created = $controller->store($storeRequest);

        $this->assertSame(201, $created->getStatusCode());
        $subcategory = ProductSubcategory::query()->firstOrFail();
        $this->assertSame($category->id, $subcategory->category_id);
        $this->assertSame('Dry Goods', $created->getData(true)['subcategory']['name']);
        $productId = \Illuminate\Support\Facades\DB::table('products')->insertGetId([
            'product_categorie_id' => $category->id,
            'subcategory_id' => $subcategory->id,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $listed = $controller->options($category);
        $this->assertSame($subcategory->id, $listed->getData(true)['subcategories'][0]['id']);

        $controller->update(Request::create('/product/subcategories/'.$subcategory->id, 'PUT', [
            'category_id' => $newCategory->id,
            'name' => 'Pantry',
        ]), $subcategory);
        $this->assertDatabaseHas('product_subcategories', [
            'id' => $subcategory->id,
            'category_id' => $newCategory->id,
            'name' => 'Pantry',
        ]);
        $this->assertDatabaseHas('products', [
            'id' => $productId,
            'product_categorie_id' => $newCategory->id,
            'subcategory_id' => $subcategory->id,
        ]);

        $controller->destroy($subcategory);
        $this->assertDatabaseMissing('product_subcategories', ['id' => $subcategory->id]);
        $this->assertDatabaseHas('products', ['id' => $productId, 'subcategory_id' => null]);
    }
}
