<?php

use App\Models\Product;
use App\Models\QuotationItem;
use App\Models\SaleItem;

test('product display name includes quantity size only when present', function () {
    expect((new Product(['productName' => 'Toner']))->display_name)
        ->toBe('Toner')
        ->and((new Product(['productName' => 'Paper Rim', 'quantity_size' => ' RIM ']))->display_name)
        ->toBe('Paper Rim - RIM')
        ->and((new Product(['productName' => 'Paper Rim - RIM', 'quantity_size' => 'RIM']))->display_name)
        ->toBe('Paper Rim - RIM');
});

test('sale and quotation item display names append related product size without duplicating it', function () {
    $product = new Product(['productName' => 'Paper Rim', 'quantity_size' => 'RIM']);
    $saleItem = new SaleItem(['product_name' => 'Paper Rim']);
    $saleItem->setRelation('product', $product);
    $quotationItem = new QuotationItem(['product_name' => 'Paper Rim - RIM']);
    $quotationItem->setRelation('product', $product);

    expect($saleItem->display_product_name)
        ->toBe('Paper Rim - RIM')
        ->and($quotationItem->display_product_name)
        ->toBe('Paper Rim - RIM');
});

test('sale and quotation item display names remain compatible with missing product sizes', function () {
    $saleItem = new SaleItem(['product_name' => 'Legacy Product']);
    $saleItem->setRelation('product', null);
    $quotationItem = new QuotationItem(['product_name' => 'Custom Quotation Item']);
    $quotationItem->setRelation('product', null);

    expect($saleItem->display_product_name)
        ->toBe('Legacy Product')
        ->and($quotationItem->display_product_name)
        ->toBe('Custom Quotation Item');
});
