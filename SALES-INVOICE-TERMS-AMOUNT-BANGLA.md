# Sales Invoice Terms ও Amount পরিবর্তনের নথি

## Controller (code and explanation)----

`app/Http/Controllers/SaleController.php`-এ Create Sale form-এর জন্য Create Quotation-এর একই default Terms & Conditions রাখা হয়েছে। Sale submit-এর সময় customer-এর terms invoice-এ সংরক্ষিত হয়। Invoice render-এ সংরক্ষিত terms না থাকলে default terms ব্যবহার হয়। একই controller-এ total amount-কে Taka/Paisa-সহ ইংরেজি কথায় রূপান্তরের helper যোগ হয়েছে।

```php
'terms_conditions' => 'nullable|string|max:5000',
...
'terms_conditions' => $validated['terms_conditions'] ?? null,
```

Amount in words helper decimal amount-কে paisa-তে round করে integer অংশ ও paisa অংশ আলাদাভাবে words-এ লেখে। Invoice-এ helper-টি এখন total amount নয়, `amount_paid` ব্যবহার করে; তাই “Amount Paid in Words” প্রকৃত পরিশোধিত অঙ্ক দেখায়।

## Migration (code and explanation)----

`database/migrations/2026_10_31_000003_add_terms_conditions_to_sale_invoices_table.php`-এ `sale_invoices.terms_conditions` nullable text field যোগ হয়েছে। এতে প্রতিটি invoice-এর নিজস্ব edited Terms & Conditions থাকে।

```php
$table->text('terms_conditions')->nullable();
```

## Model (code and explanation)----

`app/Models/SaleInvoice.php`-এর fillable fields-এ `terms_conditions` যোগ করা হয়েছে, যাতে sale transaction invoice-এর সঙ্গে তা save করতে পারে।

```php
'terms_conditions',
```

## Blade (code and explanation)----

`resources/views/backend/Sales/salesIndex.blade.php`-এ Create Quotation-এর মতো default text-সহ Terms & Conditions textarea যোগ হয়েছে। `resources/views/backend/Sales/salesInvoice.blade.php`-এ Subtotal, Discount, VAT, AIT, Previous Due (থাকলে), Total, Amount Paid, Due ও Payment Method একটি bordered table-এ দেখায়; table-এর নিচে Amount Paid কথায় দেখায়। Invoice-এ সংরক্ষিত Terms & Conditions-ও দেখায়। Sale Invoice ও Delivery Challan-এর customer name, phone এবং address-এর পাশে যথাক্রমে user, phone ও map icon দেখানো হয়।

```blade
<label for="terms_conditions">Terms & Conditions</label>
<textarea name="terms_conditions" id="terms_conditions">
    {{ old('terms_conditions', $defaultTerms) }}
</textarea>
```

## পরীক্ষা

`tests/Feature/SalePaymentTest.php`-এ sale invoice-এ custom terms save হওয়ার assertion যোগ হয়েছে। `tests/Feature/DeliveryChallanTest.php`-এর invoice test-এ amount-in-words output যাচাই করা হয়েছে। `tests/Unit/SaleInvoiceAmountInWordsTest.php` পূর্ণ টাকা, পয়সাসহ টাকা এবং শূন্য পূর্ণ টাকা—এই রূপান্তরগুলো পরীক্ষা করে।
