# DataTable-এর প্রতি পৃষ্ঠায় ১০টি এন্ট্রি ও স্থির Search/Showing — বাংলা নথি

## পরিবর্তিত ফাইল

`resources/views/dashboard/dashboard.blade.php`

এই layout-এ `#myTable` ID-সহ DataTable-গুলো এক জায়গা থেকে initialize হয়। তাই page
length ও controls-এর পরিবর্তন সব সংশ্লিষ্ট তালিকায় একইভাবে প্রযোজ্য।

## প্রতি পৃষ্ঠায় ১০টি এন্ট্রি

```javascript
pageLength: 10,
lengthMenu: [
    [10, 25, 50, 100, -1],
    [10, 25, 50, 100, 'All']
],
```

প্রথমবার DataTable খুললে ১০টি row দেখায়। আগের মতোই user চাইলে ২৫, ৫০, ১০০ বা
সবগুলো এন্ট্রি বেছে নিতে পারেন।

## Search, Showing ও page-size স্থির রাখা

DataTable layout-এ page-size, `Showing 1 to 10 of ... entries` এবং Search একই উপরের
control row-তে রাখা হয়েছে। Pagination নিচে থাকে:

```javascript
layout: {
    topStart: ['pageLength', 'info'],
    topEnd: 'search',
    bottomEnd: 'paging'
}
```

```css
#myTable_wrapper > .dt-layout-row:first-child {
    position: sticky;
    top: 60px;
    z-index: 9;
    padding: .5rem 0;
    background: var(--bs-body-bg, #fff);
}
```

Table header-এ horizontal sticky position দেওয়া হয়েছে, যাতে horizontal scrollbar
ব্যবহারকালে column heading দৃশ্যমান থাকে। বাংলা code comment-এ এই CSS-এর উদ্দেশ্য
উল্লেখ করা হয়েছে।

```css
#myTable thead {
    position: sticky;
    left: 0;
    z-index: 8;
}
```
