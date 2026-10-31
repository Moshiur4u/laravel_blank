<?php

use App\Http\Controllers\SaleController;

test('sale invoice total is converted to words with optional paisa', function () {
    $reflection = new ReflectionClass(SaleController::class);
    $controller = $reflection->newInstanceWithoutConstructor();
    $amountInWords = $reflection->getMethod('amountInWords');

    expect($amountInWords->invoke($controller, 123.45))
        ->toBe('Taka one hundred and twenty-three and forty-five Paisa only.')
        ->and($amountInWords->invoke($controller, 100.00))
        ->toBe('Taka one hundred only.')
        ->and($amountInWords->invoke($controller, 0.05))
        ->toBe('Taka zero and five Paisa only.');
});
