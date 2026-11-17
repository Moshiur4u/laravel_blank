# Product Index-এ দীর্ঘ নাম একাধিক লাইনে দেখানো

এই পরিবর্তনে শুধু product index-এর নামের display area-তে সীমিত প্রস্থ ও line-wrap
নিয়ম যোগ করা হয়েছে। Controller, database, route এবং model অপরিবর্তিত রাখা হয়েছে।
উদ্দেশ্য হলো দীর্ঘ নামকে একাধিক লাইনে দেখানো, কিন্তু product-এর নাম বা সংরক্ষিত
তথ্য পরিবর্তন না করা।

## Controller (Code and Explanation)

এই UI পরিবর্তনের জন্য কোনো controller code পরিবর্তন করা হয়নি। Product index-এ
আগের controller থেকেই product collection আসে।

## Migration (Code and Explanation)

কোনো database schema বা migration পরিবর্তন করা হয়নি; product name database-এ
আগের মতোই সংরক্ষিত থাকে।

## Route (Code and Explanation)

কোনো route যোগ বা পরিবর্তন করা হয়নি।

## Model (Code and Explanation)

কোনো model পরিবর্তন করা হয়নি। Product-এর নাম এবং model behavior আগের মতোই থাকে।

## Blade Template (Code and Explanation)

**ফাইল:** [indexProduct.blade.php](./resources/views/backend/Product/product/indexProduct.blade.php)

### পরিবর্তিত raw code

```blade
<div style="min-width: 0; max-width: 260px;">
    <h6 class="mb-0 fw-semibold text-dark"
        style="white-space: normal; overflow-wrap: anywhere;">
        {{ $product->productName }}
        @if ($product->isExpired())
            <span class="badge bg-danger ms-1">Expired</span>
        @elseif ($product->isExpiringSoon())
            <span class="badge bg-warning text-dark ms-1">Expires soon</span>
        @endif
    </h6>
    <span class="text-muted small">SKU:
        #PRD-{{ str_pad($product->id, 5, '0', STR_PAD_LEFT) }}</span>
</div>
```

### লাইন ধরে ব্যাখ্যা

1. `<div style="min-width: 0; max-width: 260px;">` product image-এর পাশের text area-কে
   সর্বোচ্চ 260px চওড়া রাখে। `min-width: 0` flex layout-কে area ছোট হতে দেয়, যাতে
   লম্বা text cell-কে অযথা চওড়া না করে।
2. `<h6 ...>` product নামের আগের heading style বজায় রাখে।
3. `white-space: normal` browser-কে প্রয়োজনমতো whitespace-এ লাইন ভাঙতে দেয়।
4. `overflow-wrap: anywhere` কোনো নামের মধ্যে whitespace না থাকলেও অতিদীর্ঘ অংশকে
   পরের লাইনে ভাঙে, তাই text cell-এর বাইরে গড়িয়ে যায় না।
5. `{{ $product->productName }}` আগের মতো escaped Blade output-এ product-এর আসল নাম
   দেখায়; নাম truncate বা database update করা হয় না।
6. `@if ($product->isExpired())` আগের expired status যাচাই করে।
7. `Expired` badge আগের মতো expired product চিহ্নিত করে।
8. `@elseif ($product->isExpiringSoon())` expired না হলে শিগগির মেয়াদ শেষ হবে কি না
   যাচাই করে।
9. `Expires soon` badge আগের মতো expiring product চিহ্নিত করে।
10. `@endif` status condition শেষ করে; badge-গুলো নামের সঙ্গে থাকায় প্রয়োজন হলে
    সেগুলোও পরের লাইনে যেতে পারে।
11. SKU-এর `<span>` আগের মতো product ID-কে padded SKU আকারে দেখায়; এতে SKU বা product
    data-র আচরণ বদলায় না।
12. শেষের `</div>` product নাম ও SKU-এর text wrapper বন্ধ করে।

### পরিবর্তনের কারণ

- আগে product নামের container-এর সর্বোচ্চ প্রস্থ ছিল না, তাই দীর্ঘ নাম table row-কে
  চওড়া করতে পারত।
- `max-width` ও wrapping style নামকে একই cell-এর মধ্যে একাধিক লাইনে রাখে।
- `overflow-wrap: anywhere` সাধারণ word-wrap-এর পাশাপাশি whitespace-বিহীন দীর্ঘ নামও
  ভাঙে।
- পরিবর্তনটি কেবল product index-এ দৃশ্যমান; অন্য পৃষ্ঠা, নামের সংরক্ষণ, SKU,
  category বা action button-এ কোনো পরিবর্তন আনা হয়নি।
