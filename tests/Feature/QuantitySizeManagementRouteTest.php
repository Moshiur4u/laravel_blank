<?php

namespace Tests\Feature;

use App\Http\Controllers\QuantitySizeController;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class QuantitySizeManagementRouteTest extends TestCase
{
    public function test_quantity_size_management_routes_are_registered_with_expected_actions(): void
    {
        $routes = [
            'quantity-size.index' => ['GET', QuantitySizeController::class.'@index'],
            'quantity-size.create' => ['GET', QuantitySizeController::class.'@create'],
            'quantity-size.store' => ['POST', QuantitySizeController::class.'@store'],
            'quantity-size.edit' => ['GET', QuantitySizeController::class.'@edit'],
            'quantity-size.update' => ['PUT', QuantitySizeController::class.'@update'],
            'quantity-size.destroy' => ['DELETE', QuantitySizeController::class.'@destroy'],
        ];

        foreach ($routes as $name => [$method, $action]) {
            $route = Route::getRoutes()->getByName($name);

            $this->assertNotNull($route, "Route {$name} should be registered.");
            $this->assertContains($method, $route->methods());
            $this->assertSame($action, $route->getActionName());
        }
    }
}
