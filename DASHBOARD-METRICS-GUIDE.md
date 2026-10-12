# Dashboard Metrics - শেখার নোট

ড্যাশবোর্ডে দৈনিক, সাপ্তাহিক ও মাসিক revenue, customer due, estimated profit, purchase order amount এবং দুটি Top-5 table দেখানো হয়। এই পরিবর্তনে existing business records লেখা বা পরিবর্তন করা হয় না; শুধু read-only query দিয়ে হিসাব করা হয়।

## তারিখের সীমা ও KPI

- **Daily:** আজকের calendar day, server/application timezone অনুযায়ী।
- **Weekly:** চলতি সপ্তাহ সোমবার থেকে রবিবার; সপ্তাহের শুরু ধরা হয়, পরের সপ্তাহের শুরুর আগ পর্যন্ত।
- **Monthly:** চলতি calendar month; পরের মাসের প্রথম দিনের আগ পর্যন্ত।
- প্রতিটি range-এ start অন্তর্ভুক্ত এবং end বাদ, ফলে দিনের শেষ সময়ের record-ও সঠিকভাবে ধরা হয়।
- **Total Revenue:** `subtotal - discount_amount + vat_amount`; আগের customer due নতুন sale revenue হিসেবে পুনরায় গণনা হয় না।
- **Total Due:** সময়সীমার sale invoice-গুলোর `due_amount` যোগফল।
- **Estimated Profit:** প্রতিটি sold item-এর জন্য `((products.price - products.purchase_price) × sale_items.quantity)` হিসাব করে period অনুযায়ী যোগ হয়। অর্থাৎ product-এর বর্তমান selling price থেকে purchase price বাদ দিয়ে ঐ সময়ে বিক্রি হওয়া quantity গুণ করা হয়। ওই period-এ বিক্রি থাকা যেকোনো item-এর purchase price ফাঁকা বা product মুছে গেলে monthly, daily বা weekly value-তে ভুল 0 না দেখিয়ে `Cost missing` দেখানো হয়। সব sold item-এর cost থাকলে profit দেখায়; ঐ period-এ sale না থাকলে ৳0.00 দেখায়।
- **Purchase Order Amount:** সময়সীমার মধ্যে তৈরি supplier ledger entry-র `total` যোগফল। Supplier ledger schema-তে আলাদা purchase-order date নেই, তাই entry-র `created_at` ব্যবহৃত হয়।

Profit একটি estimate: হিসাবটি Product table-এর বর্তমান selling `price` ও `purchase_price` ব্যবহার করে এবং invoice date অনুযায়ী দৈনিক, সাপ্তাহিক, মাসিক period-এ আলাদা যোগ হয়। invoice-level discount/VAT product line-এ ভাগ করা হয় না। কোনো period-এ sold item-এর cost missing থাকলে আংশিক profit-কে সম্পূর্ণ ফল হিসেবে না দেখিয়ে ঐ period-এ `Cost missing` দেখায়।

## Top tables

1. **Top Selling Products:** `sale_items`-এর snapshot product name ও quantity দিয়ে all-time sold quantity অনুযায়ী প্রথম ৫টি। Sales amount হলো ঐ পণ্যের `line_total`-এর যোগফল।
2. **Top Sales Employees:** invoice-এ থাকা employee ID অনুযায়ী all-time sales amount-এর প্রথম ৫ জন। Sales amount থেকে `previous_due` বাদ; employee মুছে গেলে বা ID না থাকলে `Unknown` দেখায়।

## Code path

- `DashboardConrtoller::dashboardbody()` period range বানায়, aggregate query চালায়, এবং metric ও ranking data view-তে পাঠায়।
- `resources/views/dashboard/dashboard-body.blade.php`-এ আগের চার-কলামের dashboard card layout-এ KPI render হয়। Responsive grid ছোট screen-এ এক column, মাঝারি screen-এ দুই column, এবং বড় screen-এ চার column ব্যবহার করে। Card-গুলো একই minimum height, stretch করা column এবং সমান footer space ব্যবহার করায় আকার সমান থাকে। Card-এর বড় value monthly, নিচের ছোট লাইনে daily ও weekly amount; দুইটি real-data table-ও একই view-তে থাকে এবং কোনো static demo amount রাখা হয়নি।
- Database queryগুলো dashboard দেখার সময় পড়া হয়, কোনো migration বা write operation লাগে না।

## সংশ্লিষ্ট ফাইল

- `app/Http/Controllers/DashboardConrtoller.php`
- `resources/views/dashboard/dashboard-body.blade.php`
