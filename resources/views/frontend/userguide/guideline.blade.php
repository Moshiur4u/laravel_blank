@extends('dashboard.dashboard')
@section('title', 'ব্যবহারকারী নির্দেশিকা')

@section('content')
    <div class="page-wrapper">
        <div class="page-content">
        @section('content')
            <div class="page-wrapper">
                <div class="page-content">
                    <div id="user-guide">
                        <!DOCTYPE html>
                        <html lang="bn" class="scroll-smooth">

                        <head>
                            <meta charset="UTF-8">
                            <meta name="viewport" content="width=device-width, initial-scale=1.0">

                            <!-- Google Fonts: Hind Siliguri (Bengali) & Inter (English/UI) -->
                            <link rel="preconnect" href="https://fonts.googleapis.com">
                            {{-- <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin> --}}
                            <link {{-- href="https://fonts.googleapis.com/css2?family=Hind+Siliguri:wght@300;400;500;600;700&family=Inter:wght@400;500;600;700&display=swap" --}} rel="stylesheet">

                            <!-- Tailwind CSS -->
                            <script src="https://cdn.tailwindcss.com"></script>
                            <script>
                                tailwind.config = {
                                    darkMode: 'class',
                                    theme: {
                                        extend: {
                                            fontFamily: {
                                                sans: ['"Bangli"', '"Inter"', 'sans-serif'],
                                                mono: ['"Inter"', 'monospace'],
                                            },
                                            colors: {
                                                brand: {
                                                    50: '#eef2ff',
                                                    100: '#e0e7ff',
                                                    500: '#6366f1',
                                                    600: '#4f46e5',
                                                    700: '#4338ca',
                                                    900: '#312e81',
                                                }
                                            }
                                        }
                                    }
                                }
                            </script>

                            <!-- Lucide Icons -->
                            <script src="https://unpkg.com/lucide@latest"></script>

                            <style>
                                body {
                                    font-family: 'Bangli', 'Inter', sans-serif;
                                    -webkit-font-smoothing: antialiased;
                                }

                                code,
                                .code-badge {
                                    font-family: 'Inter', monospace;
                                }

                                /* Custom Scrollbar */
                                ::-webkit-scrollbar {
                                    width: 6px;
                                    height: 6px;
                                }

                                ::-webkit-scrollbar-track {
                                    background: transparent;
                                }

                                ::-webkit-scrollbar-thumb {
                                    background: rgba(148, 163, 184, 0.4);
                                    border-radius: 9999px;
                                }

                                ::-webkit-scrollbar-thumb:hover {
                                    background: rgba(100, 116, 139, 0.7);
                                }

                                /* Hide scrollbar for compact horizontal nav */
                                .no-scrollbar::-webkit-scrollbar {
                                    display: none;
                                }

                                .no-scrollbar {
                                    -ms-overflow-style: none;
                                    scrollbar-width: none;
                                }

                                /* Search Highlight */
                                mark.search-highlight {
                                    background-color: #fef08a;
                                    color: #1e293b;
                                    padding: 0 2px;
                                    border-radius: 3px;
                                    font-weight: 600;
                                }

                                .dark mark.search-highlight {
                                    background-color: #eab308;
                                    color: #0f172a;
                                }

                                /* Print Styles */
                                @media print {
                                    .no-print {
                                        display: none !important;
                                    }

                                    body {
                                        background: white !important;
                                        color: black !important;
                                    }

                                    main {
                                        margin-left: 0 !important;
                                        padding: 0 !important;
                                        max-width: 100% !important;
                                    }

                                    .shadow-sm,
                                    .shadow-md,
                                    .shadow-lg {
                                        box-shadow: none !important;
                                    }

                                    section {
                                        break-inside: avoid-page;
                                    }
                                }
                            </style>
                        </head>

                        <body
                            class="transition-colors duration-200 bg-slate-50 text-slate-800 dark:bg-slate-950 dark:text-slate-200">

                            <!-- Minimal Sticky Top Utility Bar -->
                            <header>
                                <div
                                    class="flex items-center justify-between max-w-5xl gap-3 px-4 mx-auto sm:px-6 lg:px-8 h-14">
                                    <!-- Right: Dark Mode & Print -->
                                    <div class="flex items-center gap-2 shrink-0">
                                        <!-- Dark Mode Toggle -->
                                        <button id="theme-toggle" type="button"></button>
                                    </div>
                            </header>

                            <!-- Main Container -->
                            <div class="max-w-5xl px-4 py-6 mx-auto sm:px-6 lg:px-8">

                                <!-- Main Content Area -->
                                <main id="manual-content" class="pb-16 space-y-8">

                                    <!-- Subtle Role & Permission Notice -->
                                    <div
                                        class="p-3.5 rounded-2xl bg-amber-50/80 dark:bg-amber-950/30 border border-amber-200/80 dark:border-amber-800/40 flex items-start gap-2.5 text-xs sm:text-sm text-amber-900 dark:text-amber-200">
                                        <i data-lucide="shield-alert"
                                            class="w-4 h-4 text-amber-600 dark:text-amber-400 shrink-0 mt-0.5"></i>
                                        <p class="leading-relaxed">
                                            এই handout-এ অ্যাপের <strong>Login</strong>, sidebar-এর সব
                                            <strong>menu/module</strong>,
                                            <strong>Dashboard</strong> এবং <strong>Sales</strong>-এর সাধারণ কাজগুলো ব্যাখ্যা
                                            করা হয়েছে।
                                            {{-- ব্যবহারকারীর <code class="text-xs font-semibold">role</code> ও <code
                                        class="text-xs font-semibold">permission</code> অনুযায়ী কিছু menu বা button
                                    দেখা নাও যেতে পারে। --}}
                                        </p>
                                    </div>
                                    <!--==========================================-->
                                    <!-- SECTION 1: LOGIN PAGE                      -->
                                    <!-- ========================================== -->
                                    <section id="section-login"
                                        class="p-6 space-y-6 bg-white border shadow-sm searchable-section scroll-mt-24 dark:bg-slate-900 rounded-3xl border-slate-200 dark:border-slate-800 sm:p-8">
                                        <div
                                            class="flex items-center gap-3 pb-4 border-b border-slate-100 dark:border-slate-800">
                                            <div
                                                class="flex items-center justify-center w-10 h-10 text-lg font-bold text-blue-600 rounded-2xl bg-blue-50 dark:bg-blue-950/60 dark:text-blue-400 shrink-0">
                                                ১
                                            </div>
                                            <div>
                                                <h2 class="text-xl font-bold sm:text-2xl text-slate-900 dark:text-white">
                                                    Login
                                                    Page</h2>
                                                <p class="text-sm text-slate-500 dark:text-slate-400">অ্যাকাউন্টে
                                                    প্রবেশের নিয়ম
                                                    ও লগইন ত্রুটি সমাধান</p>
                                            </div>
                                        </div>

                                        <!-- লগইন করার নিয়ম -->
                                        <div class="space-y-3">
                                            <h3
                                                class="flex items-center gap-2 text-lg font-semibold text-slate-800 dark:text-slate-100">
                                                <i data-lucide="log-in"
                                                    class="w-5 h-5 text-blue-600 dark:text-blue-400"></i>
                                                লগইন করার নিয়ম
                                            </h3>
                                            <div class="grid grid-cols-1 gap-4 md:grid-cols-3">
                                                <div
                                                    class="flex items-start gap-3 p-4 border rounded-2xl bg-slate-50 dark:bg-slate-800/60 border-slate-200/70 dark:border-slate-700/70">
                                                    <span
                                                        class="w-7 h-7 rounded-full bg-blue-600 text-white text-sm font-bold flex items-center justify-center shrink-0 mt-0.5">1</span>
                                                    <p class="text-sm leading-relaxed sm:text-base">Login page-এ আপনার
                                                        account-এর <strong>Email</strong> লিখুন।</p>
                                                </div>
                                                <div
                                                    class="flex items-start gap-3 p-4 border rounded-2xl bg-slate-50 dark:bg-slate-800/60 border-slate-200/70 dark:border-slate-700/70">
                                                    <span
                                                        class="w-7 h-7 rounded-full bg-blue-600 text-white text-sm font-bold flex items-center justify-center shrink-0 mt-0.5">2</span>
                                                    <p class="text-sm leading-relaxed sm:text-base">
                                                        <strong>Password</strong>
                                                        লিখুন। চোখের আইকনে ক্লিক করলে password দেখা বা লুকানো যায়।
                                                    </p>
                                                </div>
                                                <div
                                                    class="flex items-start gap-3 p-4 border rounded-2xl bg-slate-50 dark:bg-slate-800/60 border-slate-200/70 dark:border-slate-700/70">
                                                    <span
                                                        class="w-7 h-7 rounded-full bg-blue-600 text-white text-sm font-bold flex items-center justify-center shrink-0 mt-0.5">3</span>
                                                    <p class="text-sm leading-relaxed sm:text-base"><strong>Sign
                                                            in</strong>
                                                        চাপুন। তথ্য সঠিক এবং account সক্রিয় হলে
                                                        <strong>Dashboard</strong>
                                                        খুলবে।
                                                    </p>
                                                </div>
                                            </div>
                                        </div>

                                        <!-- Login ব্যর্থ হলে যে বার্তাগুলো আসতে পারে -->
                                        <div class="pt-2 space-y-3">
                                            <h3
                                                class="flex items-center gap-2 text-lg font-semibold text-slate-800 dark:text-slate-100">
                                                <i data-lucide="alert-triangle" class="w-5 h-5 text-rose-500"></i>
                                                Login ব্যর্থ হলে যে বার্তাগুলো আসতে পারে
                                            </h3>
                                            <div
                                                class="overflow-x-auto border rounded-2xl border-slate-200 dark:border-slate-800">
                                                <table class="w-full text-left border-collapse">
                                                    <thead>
                                                        <tr
                                                            class="text-sm font-semibold border-b bg-slate-100/80 dark:bg-slate-800/90 text-slate-700 dark:text-slate-200 border-slate-200 dark:border-slate-700">
                                                            <th class="py-3.5 px-4 w-2/5">সমস্যা</th>
                                                            <th class="py-3.5 px-4">করণীয়</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody
                                                        class="text-sm divide-y divide-slate-200 dark:divide-slate-800 sm:text-base">
                                                        <tr
                                                            class="transition-colors hover:bg-slate-50/80 dark:hover:bg-slate-800/40">
                                                            <td
                                                                class="py-3.5 px-4 font-medium text-rose-600 dark:text-rose-400">
                                                                Email ফাঁকা রাখা হয়েছে</td>
                                                            <td class="py-3.5 px-4">Email লিখে আবার চেষ্টা করুন।</td>
                                                        </tr>
                                                        <tr
                                                            class="transition-colors hover:bg-slate-50/80 dark:hover:bg-slate-800/40">
                                                            <td
                                                                class="py-3.5 px-4 font-medium text-rose-600 dark:text-rose-400">
                                                                Email-এর format সঠিক নয়</td>
                                                            <td class="py-3.5 px-4">সঠিক format-এর email দিন, যেমন
                                                                <code
                                                                    class="px-1.5 py-0.5 rounded bg-slate-100 dark:bg-slate-800 text-xs font-semibold">name@example.com</code>।
                                                            </td>
                                                        </tr>
                                                        <tr
                                                            class="transition-colors hover:bg-slate-50/80 dark:hover:bg-slate-800/40">
                                                            <td
                                                                class="py-3.5 px-4 font-medium text-rose-600 dark:text-rose-400">
                                                                Email account পাওয়া যায়নি অথবা password মেলেনি</td>
                                                            <td class="py-3.5 px-4">Email ও password যাচাই করুন;
                                                                প্রয়োজনে
                                                                administrator-এর সঙ্গে যোগাযোগ করুন।</td>
                                                        </tr>
                                                        <tr
                                                            class="transition-colors hover:bg-slate-50/80 dark:hover:bg-slate-800/40">
                                                            <td
                                                                class="py-3.5 px-4 font-medium text-amber-600 dark:text-amber-400">
                                                                Account নিষ্ক্রিয়</td>
                                                            <td class="py-3.5 px-4">Administrator-কে account সক্রিয়
                                                                করতে বলুন।
                                                            </td>
                                                        </tr>
                                                        <tr
                                                            class="transition-colors hover:bg-slate-50/80 dark:hover:bg-slate-800/40">
                                                            <td
                                                                class="py-3.5 px-4 font-medium text-amber-600 dark:text-amber-400">
                                                                একই email ও device/network থেকে বারবার ব্যর্থ চেষ্টা
                                                            </td>
                                                            <td class="py-3.5 px-4">দেখানো সময় অপেক্ষা করে আবার চেষ্টা
                                                                করুন।
                                                            </td>
                                                        </tr>
                                                        <tr
                                                            class="transition-colors hover:bg-slate-50/80 dark:hover:bg-slate-800/40">
                                                            <td
                                                                class="py-3.5 px-4 font-medium text-rose-600 dark:text-rose-400">
                                                                Password ফাঁকা</td>
                                                            <td class="py-3.5 px-4">Password লিখে আবার চেষ্টা করুন।
                                                            </td>
                                                        </tr>
                                                    </tbody>
                                                </table>
                                            </div>

                                            <!-- Important Callout -->
                                            <div
                                                class="flex items-start gap-3 p-4 text-sm text-blue-900 border border-blue-200 rounded-2xl bg-blue-50 dark:bg-blue-950/40 dark:border-blue-800/60 dark:text-blue-200">
                                                <i data-lucide="info"
                                                    class="w-5 h-5 text-blue-600 dark:text-blue-400 shrink-0 mt-0.5"></i>
                                                <p class="leading-relaxed">
                                                    <strong>লক্ষ্য করুন:</strong> ভুল credential-এর বার্তাটি email-এর
                                                    নিচে
                                                    দেখানো হয়, যদিও সমস্যাটি password-ও হতে পারে। তাই বার্তাটি দেখে একা
                                                    email-কে ভুল ধরে নেবেন না।
                                                </p>
                                            </div>
                                        </div>
                                    </section>

                                    <!-- ========================================== -->
                                    <!-- SECTION 2: সব MENU ও MODULE পরিচিতি       -->
                                    <!-- ========================================== -->
                                    <section id="section-modules"
                                        class="p-6 space-y-6 bg-white border shadow-sm searchable-section scroll-mt-28 dark:bg-slate-900 rounded-3xl border-slate-200 dark:border-slate-800 sm:p-8">
                                        <div
                                            class="flex flex-col justify-between gap-4 pb-4 border-b sm:flex-row sm:items-center border-slate-100 dark:border-slate-800">
                                            <div class="flex items-center gap-3">
                                                <div
                                                    class="flex items-center justify-center w-10 h-10 text-lg font-bold text-indigo-600 rounded-2xl bg-indigo-50 dark:bg-indigo-950/60 dark:text-indigo-400 shrink-0">
                                                    ২
                                                </div>
                                                <div>
                                                    <h2
                                                        class="text-xl font-bold sm:text-2xl text-slate-900 dark:text-white">
                                                        সব
                                                        Menu ও Module পরিচিতি</h2>
                                                    <p class="text-sm text-slate-500 dark:text-slate-400">Sidebar-এর
                                                        menu-গুলো
                                                        user role ও permission অনুযায়ী দেখা যায়।</p>
                                                </div>
                                            </div>
                                        </div>

                                        <p class="text-sm sm:text-base text-slate-600 dark:text-slate-300">
                                            Sidebar-এর menu-গুলো user role ও permission অনুযায়ী দেখা যায়। তাই নিচের
                                            কোনো
                                            submenu আপনার পর্দায় না থাকলে administrator-এর কাছে access চাইতে পারেন।
                                        </p>

                                        <!-- Category Filter Tabs -->
                                        <div class="flex flex-wrap gap-2 no-print" id="module-tabs">
                                            <button data-filter="all"
                                                class="module-tab active px-3.5 py-2 rounded-xl text-xs sm:text-sm font-semibold bg-brand-600 text-white shadow-xs transition-all">
                                                সবগুলো দেখুন
                                            </button>
                                            <button data-filter="regular"
                                                class="module-tab px-3.5 py-2 rounded-xl text-xs sm:text-sm font-semibold bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300 hover:bg-slate-200 dark:hover:bg-slate-700 transition-all">
                                                Regular Operation
                                            </button>
                                            <button data-filter="managerial"
                                                class="module-tab px-3.5 py-2 rounded-xl text-xs sm:text-sm font-semibold bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300 hover:bg-slate-200 dark:hover:bg-slate-700 transition-all">
                                                Managerial Operations
                                            </button>
                                            <button data-filter="administration"
                                                class="module-tab px-3.5 py-2 rounded-xl text-xs sm:text-sm font-semibold bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300 hover:bg-slate-200 dark:hover:bg-slate-700 transition-all">
                                                Administration Operations
                                            </button>
                                            <button data-filter="reports"
                                                class="module-tab px-3.5 py-2 rounded-xl text-xs sm:text-sm font-semibold bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300 hover:bg-slate-200 dark:hover:bg-slate-700 transition-all">
                                                Reports
                                            </button>
                                            <button data-filter="header"
                                                class="module-tab px-3.5 py-2 rounded-xl text-xs sm:text-sm font-semibold bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300 hover:bg-slate-200 dark:hover:bg-slate-700 transition-all">
                                                Header-এর সুবিধা
                                            </button>
                                        </div>

                                        <!-- Module Groups Container -->
                                        <div class="space-y-8" id="module-groups">

                                            <!-- Group 1: Regular Operation -->
                                            <div class="space-y-3 module-group" data-category="regular">
                                                <div class="flex items-center gap-2">
                                                    <span class="w-2.5 h-2.5 rounded-full bg-emerald-500"></span>
                                                    <h3 class="text-lg font-bold text-slate-800 dark:text-slate-100">
                                                        Regular
                                                        Operation</h3>
                                                </div>
                                                <div
                                                    class="overflow-x-auto border rounded-2xl border-slate-200 dark:border-slate-800">
                                                    <table class="w-full text-left border-collapse">
                                                        <thead>
                                                            <tr
                                                                class="text-sm font-semibold border-b bg-slate-100/80 dark:bg-slate-800/90 text-slate-700 dark:text-slate-200 border-slate-200 dark:border-slate-700">
                                                                <th class="w-1/3 px-4 py-3">Menu / Submenu</th>
                                                                <th class="px-4 py-3">কী কাজে ব্যবহার করবেন</th>
                                                            </tr>
                                                        </thead>
                                                        <tbody
                                                            class="text-sm divide-y divide-slate-200 dark:divide-slate-800 sm:text-base">
                                                            <tr class="hover:bg-slate-50/80 dark:hover:bg-slate-800/40">
                                                                <td
                                                                    class="py-3.5 px-4 font-semibold text-brand-700 dark:text-brand-400">
                                                                    Dashboard</td>
                                                                <td class="py-3.5 px-4">ব্যবসার দৈনিক/সাপ্তাহিক/মাসিক
                                                                    সারাংশ,
                                                                    লাভের trend এবং top-selling তালিকা দেখুন।</td>
                                                            </tr>
                                                            <tr class="hover:bg-slate-50/80 dark:hover:bg-slate-800/40">
                                                                <td
                                                                    class="py-3.5 px-4 font-semibold text-brand-700 dark:text-brand-400">
                                                                    Sales → Create Sale</td>
                                                                <td class="py-3.5 px-4">নতুন বিক্রি তৈরি, payment
                                                                    নেওয়া এবং
                                                                    invoice তৈরি/print করুন। বিস্তারিত ধাপ নিচের Sales
                                                                    অংশে আছে।
                                                                </td>
                                                            </tr>
                                                            <tr class="hover:bg-slate-50/80 dark:hover:bg-slate-800/40">
                                                                <td
                                                                    class="py-3.5 px-4 font-semibold text-brand-700 dark:text-brand-400">
                                                                    Sales page shortcuts → Print Blank Invoice / Print
                                                                    Money
                                                                    Receipt</td>
                                                                <td class="py-3.5 px-4">ফাঁকা sales invoice অথবা money
                                                                    receipt
                                                                    print করুন; permission থাকলে Create Sale page-এর
                                                                    উপরের
                                                                    shortcut-এ পাওয়া যায়।</td>
                                                            </tr>
                                                            <tr class="hover:bg-slate-50/80 dark:hover:bg-slate-800/40">
                                                                <td
                                                                    class="py-3.5 px-4 font-semibold text-brand-700 dark:text-brand-400">
                                                                    Sales → Sales Return</td>
                                                                <td class="py-3.5 px-4">customer বা invoice খুঁজে
                                                                    বিক্রি করা
                                                                    পণ্যের return রেকর্ড করুন। Invoice-এর পণ্য, আগে ফেরত
                                                                    হওয়া
                                                                    quantity ও ফেরতযোগ্য quantity যাচাই করে return
                                                                    quantity,
                                                                    date ও optional reason দিয়ে নিশ্চিত করুন। Return
                                                                    Report-ও
                                                                    এই পৃষ্ঠা থেকে খোলা যায়।</td>
                                                            </tr>
                                                            <tr class="hover:bg-slate-50/80 dark:hover:bg-slate-800/40">
                                                                <td
                                                                    class="py-3.5 px-4 font-semibold text-brand-700 dark:text-brand-400">
                                                                    Quotation → Get Quotation</td>
                                                                <td class="py-3.5 px-4">customer ও পণ্য নির্বাচন করে
                                                                    quotation
                                                                    তৈরি করুন; quotation invoice নয়, এটি বিক্রির
                                                                    প্রস্তাব/মূল্যপত্র।</td>
                                                            </tr>
                                                            <tr class="hover:bg-slate-50/80 dark:hover:bg-slate-800/40">
                                                                <td
                                                                    class="py-3.5 px-4 font-semibold text-brand-700 dark:text-brand-400">
                                                                    Quotation → All Quotations</td>
                                                                <td class="py-3.5 px-4">আগের quotation-গুলোর তালিকা
                                                                    দেখুন;
                                                                    সেখান থেকে quotation খুলে দেখা/print করা বা অনুমতি
                                                                    থাকলে
                                                                    মুছতে পারেন।</td>
                                                            </tr>
                                                        </tbody>
                                                    </table>
                                                </div>
                                            </div>

                                            <!-- Group 2: Managerial Operations -->
                                            <div class="space-y-3 module-group" data-category="managerial">
                                                <div class="flex items-center gap-2">
                                                    <span class="w-2.5 h-2.5 rounded-full bg-blue-500"></span>
                                                    <h3 class="text-lg font-bold text-slate-800 dark:text-slate-100">
                                                        Managerial
                                                        Operations</h3>
                                                </div>
                                                <div
                                                    class="overflow-x-auto border rounded-2xl border-slate-200 dark:border-slate-800">
                                                    <table class="w-full text-left border-collapse">
                                                        <thead>
                                                            <tr
                                                                class="text-sm font-semibold border-b bg-slate-100/80 dark:bg-slate-800/90 text-slate-700 dark:text-slate-200 border-slate-200 dark:border-slate-700">
                                                                <th class="w-1/3 px-4 py-3">Menu / Submenu</th>
                                                                <th class="px-4 py-3">কী কাজে ব্যবহার করবেন</th>
                                                            </tr>
                                                        </thead>
                                                        <tbody
                                                            class="text-sm divide-y divide-slate-200 dark:divide-slate-800 sm:text-base">
                                                            <tr class="hover:bg-slate-50/80 dark:hover:bg-slate-800/40">
                                                                <td
                                                                    class="py-3.5 px-4 font-semibold text-blue-700 dark:text-blue-400">
                                                                    Product → All Product</td>
                                                                <td class="py-3.5 px-4">পণ্যের তালিকা, stock, price ও
                                                                    বিস্তারিত
                                                                    দেখুন; access থাকলে edit/manage করুন।</td>
                                                            </tr>
                                                            <tr class="hover:bg-slate-50/80 dark:hover:bg-slate-800/40">
                                                                <td
                                                                    class="py-3.5 px-4 font-semibold text-blue-700 dark:text-blue-400">
                                                                    Product → Create Product</td>
                                                                <td class="py-3.5 px-4">নতুন পণ্য, category/brand,
                                                                    price, stock
                                                                    quantity, unit, supplier, purchase price এবং
                                                                    অন্যান্য বিবরণ
                                                                    যোগ করুন।</td>
                                                            </tr>
                                                            <tr class="hover:bg-slate-50/80 dark:hover:bg-slate-800/40">
                                                                <td
                                                                    class="py-3.5 px-4 font-semibold text-blue-700 dark:text-blue-400">
                                                                    Product → Purchase Return</td>
                                                                <td class="py-3.5 px-4">Supplier বা purchase invoice
                                                                    খুঁজে কেনা
                                                                    পণ্যের return রেকর্ড করুন। কেনা, আগে ফেরত ও অবশিষ্ট
                                                                    quantity
                                                                    দেখে return নিশ্চিত করুন। Return amount আগে supplier
                                                                    due-তে
                                                                    সমন্বয় হয়; due ছাড়ালে অতিরিক্ত টাকা cash refund
                                                                    হিসেবে
                                                                    নথিভুক্ত হয়।</td>
                                                            </tr>
                                                            <tr class="hover:bg-slate-50/80 dark:hover:bg-slate-800/40">
                                                                <td
                                                                    class="py-3.5 px-4 font-semibold text-blue-700 dark:text-blue-400">
                                                                    Product Categories → All Category / Create Category
                                                                </td>
                                                                <td class="py-3.5 px-4">Product category তালিকা দেখুন
                                                                    বা নতুন
                                                                    category তৈরি করুন।</td>
                                                            </tr>
                                                            <tr class="hover:bg-slate-50/80 dark:hover:bg-slate-800/40">
                                                                <td
                                                                    class="py-3.5 px-4 font-semibold text-blue-700 dark:text-blue-400">
                                                                    Customers → All Customer / Create Customer</td>
                                                                <td class="py-3.5 px-4">Customer record দেখুন/তৈরি
                                                                    করুন।
                                                                    Customer detail/ledger-এ sale, payment ও বকেয়া
                                                                    পর্যালোচনা
                                                                    করা যায়।</td>
                                                            </tr>
                                                            <tr class="hover:bg-slate-50/80 dark:hover:bg-slate-800/40">
                                                                <td
                                                                    class="py-3.5 px-4 font-semibold text-blue-700 dark:text-blue-400">
                                                                    Brand → Brand List / New Brand Entry</td>
                                                                <td class="py-3.5 px-4">Product-এর brand তালিকা দেখুন
                                                                    বা নতুন
                                                                    brand যোগ করুন।</td>
                                                            </tr>
                                                            <tr class="hover:bg-slate-50/80 dark:hover:bg-slate-800/40">
                                                                <td
                                                                    class="py-3.5 px-4 font-semibold text-blue-700 dark:text-blue-400">
                                                                    Qty/Size → Qty/Size List / New Qty/Size</td>
                                                                <td class="py-3.5 px-4">Product-এর quantity/size unit
                                                                    বা option
                                                                    তালিকা দেখুন এবং নতুন option যোগ করুন।</td>
                                                            </tr>
                                                            <tr class="hover:bg-slate-50/80 dark:hover:bg-slate-800/40">
                                                                <td
                                                                    class="py-3.5 px-4 font-semibold text-blue-700 dark:text-blue-400">
                                                                    Supplier → Supplier List / New Supplier Entry</td>
                                                                <td class="py-3.5 px-4">Supplier তালিকা, supplier-এর
                                                                    contact ও
                                                                    purchase/ledger বিবরণ দেখুন; নতুন supplier যোগ করুন।
                                                                    Supplier ledger থেকে purchase/payment ও due অনুসরণ
                                                                    করা যায়।
                                                                </td>
                                                            </tr>
                                                        </tbody>
                                                    </table>
                                                </div>
                                            </div>

                                            <!-- Group 3: Administration Operations -->
                                            <div class="space-y-3 module-group" data-category="administration">
                                                <div class="flex items-center gap-2">
                                                    <span class="w-2.5 h-2.5 rounded-full bg-purple-500"></span>
                                                    <h3 class="text-lg font-bold text-slate-800 dark:text-slate-100">
                                                        Administration Operations</h3>
                                                </div>
                                                <div
                                                    class="overflow-x-auto border rounded-2xl border-slate-200 dark:border-slate-800">
                                                    <table class="w-full text-left border-collapse">
                                                        <thead>
                                                            <tr
                                                                class="text-sm font-semibold border-b bg-slate-100/80 dark:bg-slate-800/90 text-slate-700 dark:text-slate-200 border-slate-200 dark:border-slate-700">
                                                                <th class="w-1/3 px-4 py-3">Menu / Submenu</th>
                                                                <th class="px-4 py-3">কী কাজে ব্যবহার করবেন</th>
                                                            </tr>
                                                        </thead>
                                                        <tbody
                                                            class="text-sm divide-y divide-slate-200 dark:divide-slate-800 sm:text-base">
                                                            <tr class="hover:bg-slate-50/80 dark:hover:bg-slate-800/40">
                                                                <td
                                                                    class="py-3.5 px-4 font-semibold text-purple-700 dark:text-purple-400">
                                                                    Accounts → Voucher Entries</td>
                                                                <td class="py-3.5 px-4">সাধারণ business আয়/ব্যয়ের
                                                                    Debit বা
                                                                    Credit voucher তৈরি করুন। Account, date, amount
                                                                    দিয়ে
                                                                    optional reference/description যোগ করে Save করুন;
                                                                    Recent
                                                                    Vouchers-এ এন্ট্রি দেখা যায়।</td>
                                                            </tr>
                                                            <tr class="hover:bg-slate-50/80 dark:hover:bg-slate-800/40">
                                                                <td
                                                                    class="py-3.5 px-4 font-semibold text-purple-700 dark:text-purple-400">
                                                                    Accounts → Employee Payment</td>
                                                                <td class="py-3.5 px-4">Employee নির্বাচন করে
                                                                    salary/advance/অন্যান্য employee expense-এর payment
                                                                    voucher
                                                                    তৈরি করুন। Expense type, date, amount, payment
                                                                    method এবং
                                                                    প্রয়োজনে reference/notes দিন। Employee ledger-ও
                                                                    খোলা যায়।
                                                                </td>
                                                            </tr>
                                                            <tr class="hover:bg-slate-50/80 dark:hover:bg-slate-800/40">
                                                                <td
                                                                    class="py-3.5 px-4 font-semibold text-purple-700 dark:text-purple-400">
                                                                    Accounts → Voucher Account Add</td>
                                                                <td class="py-3.5 px-4">Voucher তৈরিতে ব্যবহারের জন্য
                                                                    account
                                                                    যোগ করুন। Voucher Entry form-এর Account তালিকায়
                                                                    এগুলো
                                                                    পাওয়া যায়।</td>
                                                            </tr>
                                                            <tr class="hover:bg-slate-50/80 dark:hover:bg-slate-800/40">
                                                                <td
                                                                    class="py-3.5 px-4 font-semibold text-purple-700 dark:text-purple-400">
                                                                    Employee → All Empolyee / Create Empolyee</td>
                                                                <td class="py-3.5 px-4">Employee/user record তালিকা
                                                                    দেখুন বা
                                                                    নতুন employee account তৈরি করুন। Employee
                                                                    detail/profile ও
                                                                    login access role অনুযায়ী সীমিত হতে পারে।</td>
                                                            </tr>
                                                            <tr class="hover:bg-slate-50/80 dark:hover:bg-slate-800/40">
                                                                <td
                                                                    class="py-3.5 px-4 font-semibold text-purple-700 dark:text-purple-400">
                                                                    Organization → Organization Information</td>
                                                                <td class="py-3.5 px-4">প্রতিষ্ঠানের নাম, যোগাযোগের
                                                                    তথ্য ও
                                                                    logo-সহ organization profile হালনাগাদ করুন।
                                                                    Invoice/report-এর header বা print watermark-এ এসব
                                                                    তথ্য
                                                                    ব্যবহৃত হতে পারে।</td>
                                                            </tr>
                                                        </tbody>
                                                    </table>
                                                </div>
                                            </div>

                                            <!-- Group 4: Reports -->
                                            <div class="space-y-3 module-group" data-category="reports">
                                                <div class="flex items-center gap-2">
                                                    <span class="w-2.5 h-2.5 rounded-full bg-amber-500"></span>
                                                    <h3 class="text-lg font-bold text-slate-800 dark:text-slate-100">
                                                        Reports
                                                    </h3>
                                                </div>
                                                <div
                                                    class="overflow-x-auto border rounded-2xl border-slate-200 dark:border-slate-800">
                                                    <table class="w-full text-left border-collapse">
                                                        <thead>
                                                            <tr
                                                                class="text-sm font-semibold border-b bg-slate-100/80 dark:bg-slate-800/90 text-slate-700 dark:text-slate-200 border-slate-200 dark:border-slate-700">
                                                                <th class="w-1/3 px-4 py-3">Menu / Submenu</th>
                                                                <th class="px-4 py-3">কী দেখায় এবং কীভাবে ব্যবহার
                                                                    করবেন</th>
                                                            </tr>
                                                        </thead>
                                                        <tbody
                                                            class="text-sm divide-y divide-slate-200 dark:divide-slate-800 sm:text-base">
                                                            <tr class="hover:bg-slate-50/80 dark:hover:bg-slate-800/40">
                                                                <td
                                                                    class="py-3.5 px-4 font-semibold text-amber-700 dark:text-amber-400">
                                                                    Accounts → Cashbook</td>
                                                                <td class="py-3.5 px-4">একত্রিত accounts/voucher লেনদেন
                                                                    দেখুন;
                                                                    সময়সীমা বা তারিখ দিয়ে filter করে প্রয়োজনমতো print
                                                                    করুন।
                                                                </td>
                                                            </tr>
                                                            <tr class="hover:bg-slate-50/80 dark:hover:bg-slate-800/40">
                                                                <td
                                                                    class="py-3.5 px-4 font-semibold text-amber-700 dark:text-amber-400">
                                                                    Accounts → Employee Payment</td>
                                                                <td class="py-3.5 px-4">Employee payment voucher-গুলোর
                                                                    report
                                                                    দেখুন; employee payment history যাচাই করুন।</td>
                                                            </tr>
                                                            <tr class="hover:bg-slate-50/80 dark:hover:bg-slate-800/40">
                                                                <td
                                                                    class="py-3.5 px-4 font-semibold text-amber-700 dark:text-amber-400">
                                                                    Accounts → Miscellaneous Voucher</td>
                                                                <td class="py-3.5 px-4">সাধারণ Debit/Credit voucher-এর
                                                                    report
                                                                    দেখুন ও filter করুন।</td>
                                                            </tr>
                                                            <tr class="hover:bg-slate-50/80 dark:hover:bg-slate-800/40">
                                                                <td
                                                                    class="py-3.5 px-4 font-semibold text-amber-700 dark:text-amber-400">
                                                                    Sale Reports → Sales Report</td>
                                                                <td class="py-3.5 px-4">From/To date, category,
                                                                    employee ও
                                                                    customer দিয়ে sale filter করুন। Sale amount, due,
                                                                    invoice
                                                                    count এবং product-wise sales/profit দেখুন; Export
                                                                    Excel বা
                                                                    Print Report ব্যবহার করুন।</td>
                                                            </tr>
                                                            {{-- <tr class="hover:bg-slate-50/80 dark:hover:bg-slate-800/40">
                                                                <td
                                                                    class="py-3.5 px-4 font-semibold text-amber-700 dark:text-amber-400">
                                                                    Sale Reports → Sale Return Report</td>
                                                                <td class="py-3.5 px-4">Customer sale থেকে ফেরত হওয়া
                                                                    পণ্যের
                                                                    report দেখুন এবং return date অনুযায়ী তথ্য যাচাই
                                                                    করুন।</td>
                                                            </tr> --}}
                                                            <tr class="hover:bg-slate-50/80 dark:hover:bg-slate-800/40">
                                                                <td
                                                                    class="py-3.5 px-4 font-semibold text-amber-700 dark:text-amber-400">
                                                                    Inventory Reports → Product Inventory</td>
                                                                <td class="py-3.5 px-4">Category ও stock status দিয়ে
                                                                    inventory
                                                                    filter করুন। Purchased/sold quantity, purchase rate,
                                                                    sales/profit, বর্তমান stock, expiry ও supplier তথ্য
                                                                    দেখুন;
                                                                    Excel export বা print করুন।</td>
                                                            </tr>
                                                            {{-- <tr class="hover:bg-slate-50/80 dark:hover:bg-slate-800/40">
                                                                <td
                                                                    class="py-3.5 px-4 font-semibold text-amber-700 dark:text-amber-400">
                                                                    Inventory Reports → Purchase Return Report</td>
                                                                <td class="py-3.5 px-4">Supplier purchase থেকে ফেরত
                                                                    হওয়া
                                                                    পণ্যের report দেখুন ও return date অনুযায়ী filter
                                                                    করুন।</td>
                                                            </tr> --}}
                                                            {{-- <tr class="hover:bg-slate-50/80 dark:hover:bg-slate-800/40">
                                                                <td
                                                                    class="py-3.5 px-4 font-semibold text-amber-700 dark:text-amber-400">
                                                                    User Activity Log</td>
                                                                <td class="py-3.5 px-4">ব্যবহারকারীর activity history
                                                                    দেখুন;
                                                                    access থাকলে প্রয়োজনে date/user filter দিয়ে
                                                                    নির্দিষ্ট
                                                                    কার্যকলাপ অনুসন্ধান করুন।</td>
                                                            </tr> --}}
                                                        </tbody>
                                                    </table>
                                                </div>
                                            </div>

                                            <!-- Group 5: Header-এর সুবিধা -->
                                            <div class="space-y-3 module-group" data-category="header">
                                                <div class="flex items-center gap-2">
                                                    <span class="w-2.5 h-2.5 rounded-full bg-teal-500"></span>
                                                    <h3 class="text-lg font-bold text-slate-800 dark:text-slate-100">
                                                        Header-এর
                                                        সুবিধা</h3>
                                                </div>
                                                <div class="grid grid-cols-1 gap-4 md:grid-cols-2 lg:grid-cols-3">
                                                    <div
                                                        class="p-4 rounded-2xl bg-slate-50 dark:bg-slate-800/60 border border-slate-200/80 dark:border-slate-700/70 space-y-1.5">
                                                        <div
                                                            class="flex items-center gap-2 font-semibold text-slate-900 dark:text-white">
                                                            <i data-lucide="moon" class="w-4 h-4 text-indigo-500"></i>
                                                            <span>Dark Mode icon</span>
                                                        </div>
                                                        <p class="text-sm text-slate-600 dark:text-slate-300">পর্দার
                                                            light/dark
                                                            theme বদলান।</p>
                                                    </div>
                                                    <div
                                                        class="p-4 rounded-2xl bg-slate-50 dark:bg-slate-800/60 border border-slate-200/80 dark:border-slate-700/70 space-y-1.5">
                                                        <div
                                                            class="flex items-center gap-2 font-semibold text-slate-900 dark:text-white">
                                                            <i data-lucide="bell" class="w-4 h-4 text-amber-500"></i>
                                                            <span>Expiry Notifications (bell)</span>
                                                        </div>
                                                        <p class="text-sm text-slate-600 dark:text-slate-300">
                                                            মেয়াদোত্তীর্ণ বা
                                                            এক মাসের মধ্যে মেয়াদ শেষ হবে এমন পণ্যের notification। পণ্য
                                                            নির্বাচন
                                                            করলে তার details/edit page খোলে।</p>
                                                    </div>
                                                    <div
                                                        class="p-4 rounded-2xl bg-slate-50 dark:bg-slate-800/60 border border-slate-200/80 dark:border-slate-700/70 space-y-1.5">
                                                        <div
                                                            class="flex items-center gap-2 font-semibold text-slate-900 dark:text-white">
                                                            <i data-lucide="shopping-bag"
                                                                class="w-4 h-4 text-rose-500"></i>
                                                            <span>Stock Alert (shopping bag)</span>
                                                        </div>
                                                        <p class="text-sm text-slate-600 dark:text-slate-300">১০ বা তার
                                                            কম
                                                            stock থাকা পণ্যের সতর্কতা; stock না থাকলে Out of Stock
                                                            দেখায়। পণ্য
                                                            নির্বাচন করে inventory record দেখুন।</p>
                                                    </div>
                                                    <div
                                                        class="p-4 rounded-2xl bg-slate-50 dark:bg-slate-800/60 border border-slate-200/80 dark:border-slate-700/70 space-y-1.5">
                                                        <div
                                                            class="flex items-center gap-2 font-semibold text-slate-900 dark:text-white">
                                                            <i data-lucide="user-circle"
                                                                class="w-4 h-4 text-blue-500"></i>
                                                            <span>Profile menu</span>
                                                        </div>
                                                        <p class="text-sm text-slate-600 dark:text-slate-300">অনুমতি
                                                            থাকলে
                                                            Profile খুলুন; Dashboard-এ ফিরুন বা Logout করে বের হয়ে যান।
                                                            Settings link বর্তমানে কোনো নির্দিষ্ট settings page-এ নিয়ে
                                                            যায় না।
                                                        </p>
                                                    </div>
                                                    <div
                                                        class="p-4 rounded-2xl bg-slate-50 dark:bg-slate-800/60 border border-slate-200/80 dark:border-slate-700/70 space-y-1.5">
                                                        <div
                                                            class="flex items-center gap-2 font-semibold text-slate-900 dark:text-white">
                                                            <i data-lucide="phone-call"
                                                                class="w-4 h-4 text-emerald-500"></i>
                                                            <span>Help Line</span>
                                                        </div>
                                                        <p class="text-sm text-slate-600 dark:text-slate-300">Header-এ
                                                            প্রদর্শিত নম্বরে business support-এর জন্য যোগাযোগ করুন।</p>
                                                    </div>
                                                </div>
                                            </div>

                                        </div>
                                    </section>

                                    <!-- ========================================== -->
                                    <!-- SECTION 3: DASHBOARD                       -->
                                    <!-- ========================================== -->
                                    <section id="section-dashboard"
                                        class="p-6 space-y-6 bg-white border shadow-sm searchable-section scroll-mt-28 dark:bg-slate-900 rounded-3xl border-slate-200 dark:border-slate-800 sm:p-8">
                                        <div
                                            class="flex items-center gap-3 pb-4 border-b border-slate-100 dark:border-slate-800">
                                            <div
                                                class="flex items-center justify-center w-10 h-10 text-lg font-bold rounded-2xl bg-emerald-50 dark:bg-emerald-950/60 text-emerald-600 dark:text-emerald-400 shrink-0">
                                                ৩
                                            </div>
                                            <div>
                                                <h2 class="text-xl font-bold sm:text-2xl text-slate-900 dark:text-white">
                                                    Dashboard</h2>
                                                <p class="text-sm text-slate-500 dark:text-slate-400">ব্যবসার আর্থিক
                                                    সারসংক্ষেপ, হিসাবের উৎস ও Profit Trend চার্ট</p>
                                            </div>
                                        </div>

                                        <p class="text-sm leading-relaxed sm:text-base text-slate-600 dark:text-slate-300">
                                            Dashboard-এর পাঁচটি card-এ বড় অঙ্কটি <strong>চলতি মাসের</strong>; তার নিচে
                                            <strong>Weekly</strong> ও <strong>Daily</strong> অঙ্ক দেখানো হয়। অঙ্কগুলো
                                            টাকা
                                            হিসেবে দেখানো হয় এবং card-এর মান বর্তমান সময়সীমার রেকর্ড থেকে হিসাব হয়।
                                        </p>

                                        <!-- 5 Summary Cards -->
                                        <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                                            <!-- Total Purchase -->
                                            <div
                                                class="p-5 rounded-2xl border border-slate-200 dark:border-slate-800 bg-slate-50/70 dark:bg-slate-800/40 space-y-2.5">
                                                <div class="flex items-center justify-between">
                                                    <span
                                                        class="text-base font-bold sm:text-lg text-slate-900 dark:text-white">Total
                                                        Purchase</span>
                                                    <span
                                                        class="p-2 text-blue-600 bg-blue-100 rounded-xl dark:bg-blue-900/50 dark:text-blue-400">
                                                        <i data-lucide="shopping-cart" class="w-4 h-4"></i>
                                                    </span>
                                                </div>
                                                <p class="text-sm text-slate-700 dark:text-slate-300">
                                                    <strong>কী বোঝায়:</strong> নির্বাচিত সময়সীমায় supplier ledger-এ
                                                    নথিভুক্ত
                                                    মোট purchase।
                                                </p>
                                                <div
                                                    class="pt-2 text-xs border-t border-slate-200/70 dark:border-slate-700/70 sm:text-sm text-slate-600 dark:text-slate-400">
                                                    <strong class="text-slate-800 dark:text-slate-200">হিসাবের মূল
                                                        উৎস:</strong> <code
                                                        class="text-xs bg-slate-200/70 dark:bg-slate-700 px-1.5 py-0.5 rounded">supplierledgers</code>-এর
                                                    total; রেকর্ড তৈরির তারিখ (<code
                                                        class="text-xs bg-slate-200/70 dark:bg-slate-700 px-1.5 py-0.5 rounded">created_at</code>)
                                                    অনুযায়ী।
                                                </div>
                                            </div>

                                            <!-- Total Revenue -->
                                            <div
                                                class="p-5 rounded-2xl border border-slate-200 dark:border-slate-800 bg-slate-50/70 dark:bg-slate-800/40 space-y-2.5">
                                                <div class="flex items-center justify-between">
                                                    <span
                                                        class="text-base font-bold sm:text-lg text-slate-900 dark:text-white">Total
                                                        Revenue</span>
                                                    <span
                                                        class="p-2 rounded-xl bg-emerald-100 dark:bg-emerald-900/50 text-emerald-600 dark:text-emerald-400">
                                                        <i data-lucide="trending-up" class="w-4 h-4"></i>
                                                    </span>
                                                </div>
                                                <p class="text-sm text-slate-700 dark:text-slate-300">
                                                    <strong>কী বোঝায়:</strong> নির্বাচিত সময়সীমার বিক্রি ও গণ্য
                                                    business
                                                    income।
                                                </p>
                                                <div
                                                    class="pt-2 text-xs border-t border-slate-200/70 dark:border-slate-700/70 sm:text-sm text-slate-600 dark:text-slate-400">
                                                    <strong class="text-slate-800 dark:text-slate-200">হিসাবের মূল
                                                        উৎস:</strong> Sales invoice-এর subtotal থেকে discount বাদ দিয়ে
                                                    VAT ও
                                                    AIT যোগ; invoice-এর সঙ্গে যুক্ত নয় এমন customer-ledger sale এবং
                                                    customer
                                                    payment নয় এমন credit voucher-ও যুক্ত হয়। আগের বকেয়া নতুন revenue
                                                    হিসেবে
                                                    পুনরায় যোগ হয় না।
                                                </div>
                                            </div>

                                            <!-- Total Due -->
                                            <div
                                                class="p-5 rounded-2xl border border-slate-200 dark:border-slate-800 bg-slate-50/70 dark:bg-slate-800/40 space-y-2.5">
                                                <div class="flex items-center justify-between">
                                                    <span
                                                        class="text-base font-bold sm:text-lg text-slate-900 dark:text-white">Total
                                                        Due</span>
                                                    <span
                                                        class="p-2 rounded-xl bg-amber-100 dark:bg-amber-900/50 text-amber-600 dark:text-amber-400">
                                                        <i data-lucide="clock" class="w-4 h-4"></i>
                                                    </span>
                                                </div>
                                                <p class="text-sm text-slate-700 dark:text-slate-300">
                                                    <strong>কী বোঝায়:</strong> সময়সীমার বিক্রি/লেনদেনে তৈরি বকেয়া।
                                                </p>
                                                <div
                                                    class="pt-2 text-xs border-t border-slate-200/70 dark:border-slate-700/70 sm:text-sm text-slate-600 dark:text-slate-400">
                                                    <strong class="text-slate-800 dark:text-slate-200">হিসাবের মূল
                                                        উৎস:</strong> <code
                                                        class="text-xs bg-slate-200/70 dark:bg-slate-700 px-1.5 py-0.5 rounded">sale_invoices</code>-এর
                                                    due এবং invoice-এর সঙ্গে যুক্ত নয় এমন <code
                                                        class="text-xs bg-slate-200/70 dark:bg-slate-700 px-1.5 py-0.5 rounded">customer_ledgers</code>-এর
                                                    sale due; invoice date বা sale date অনুযায়ী।
                                                </div>
                                            </div>

                                            <!-- Total Expense -->
                                            <div
                                                class="p-5 rounded-2xl border border-slate-200 dark:border-slate-800 bg-slate-50/70 dark:bg-slate-800/40 space-y-2.5">
                                                <div class="flex items-center justify-between">
                                                    <span
                                                        class="text-base font-bold sm:text-lg text-slate-900 dark:text-white">Total
                                                        Expense</span>
                                                    <span
                                                        class="p-2 rounded-xl bg-rose-100 dark:bg-rose-900/50 text-rose-600 dark:text-rose-400">
                                                        <i data-lucide="receipt" class="w-4 h-4"></i>
                                                    </span>
                                                </div>
                                                <p class="text-sm text-slate-700 dark:text-slate-300">
                                                    <strong>কী বোঝায়:</strong> সময়সীমার employee payment ও debit
                                                    voucher-এর
                                                    মোট।
                                                </p>
                                                <div
                                                    class="pt-2 text-xs border-t border-slate-200/70 dark:border-slate-700/70 sm:text-sm text-slate-600 dark:text-slate-400">
                                                    <strong class="text-slate-800 dark:text-slate-200">হিসাবের মূল
                                                        উৎস:</strong> <code
                                                        class="text-xs bg-slate-200/70 dark:bg-slate-700 px-1.5 py-0.5 rounded">employee_payment_vouchers.amount</code>
                                                    (payment date) এবং <code
                                                        class="text-xs bg-slate-200/70 dark:bg-slate-700 px-1.5 py-0.5 rounded">voucher_entries.amount</code>-এর
                                                    debit রেকর্ড (voucher date)।
                                                </div>
                                            </div>

                                            <!-- Net Profit (Full width on md) -->
                                            <div
                                                class="md:col-span-2 p-5 rounded-2xl border border-emerald-200 dark:border-emerald-800/60 bg-emerald-50/50 dark:bg-emerald-950/20 space-y-2.5">
                                                <div class="flex items-center justify-between">
                                                    <span
                                                        class="text-base font-bold sm:text-lg text-emerald-900 dark:text-emerald-300">Net
                                                        Profit</span>
                                                    <span class="p-2 text-white rounded-xl bg-emerald-500">
                                                        <i data-lucide="wallet" class="w-4 h-4"></i>
                                                    </span>
                                                </div>
                                                <p class="text-sm text-slate-800 dark:text-slate-200">
                                                    <strong>কী বোঝায়:</strong> revenue থেকে purchase ও ব্যয়-সংক্রান্ত
                                                    খরচ বাদ
                                                    দেওয়ার পরের dashboard হিসাব।
                                                </p>
                                                <div
                                                    class="pt-2 text-xs border-t border-emerald-200/70 dark:border-emerald-800/50 sm:text-sm text-slate-700 dark:text-slate-300">
                                                    <strong class="text-emerald-900 dark:text-emerald-300">হিসাবের মূল
                                                        উৎস:</strong> Revenue থেকে supplier ledger purchase, employee
                                                    payment
                                                    এবং supplier payment হিসেবে গণনা না হওয়া debit voucher বাদ দিয়ে
                                                    হিসাব করা
                                                    হয়। এটি dashboard-এর business summary; হিসাবের source record ও
                                                    সময়সীমার
                                                    ওপর নির্ভরশীল।
                                                </div>
                                            </div>
                                        </div>

                                        <!-- সময় গণনা & Profit Trend -->
                                        <div class="grid grid-cols-1 gap-5 pt-2 lg:grid-cols-2">
                                            <div
                                                class="p-5 rounded-2xl bg-slate-50 dark:bg-slate-800/60 border border-slate-200 dark:border-slate-700 space-y-2.5">
                                                <h3
                                                    class="flex items-center gap-2 text-base font-bold text-slate-900 dark:text-white">
                                                    <i data-lucide="calendar"
                                                        class="w-4 h-4 text-brand-600 dark:text-brand-400"></i>
                                                    সময় গণনা
                                                </h3>
                                                <ul
                                                    class="pl-5 space-y-2 text-sm list-disc text-slate-700 dark:text-slate-300">
                                                    <li><strong>Daily:</strong> আজকের দিন।</li>
                                                    <li><strong>Weekly:</strong> সোমবার থেকে পরের সোমবারের আগ পর্যন্ত
                                                        চলতি
                                                        সপ্তাহ।</li>
                                                    <li><strong>মাসিক card-এর অঙ্ক:</strong> চলতি calendar month।</li>
                                                    <li>Dashboard-এর হিসাব server/application-এর তারিখ ব্যবহার করে।</li>
                                                </ul>
                                            </div>

                                            <div
                                                class="p-5 rounded-2xl bg-slate-50 dark:bg-slate-800/60 border border-slate-200 dark:border-slate-700 space-y-2.5">
                                                <h3
                                                    class="flex items-center gap-2 text-base font-bold text-slate-900 dark:text-white">
                                                    <i data-lucide="bar-chart-3"
                                                        class="w-4 h-4 text-brand-600 dark:text-brand-400"></i>
                                                    Profit Trend ও অন্যান্য তথ্য
                                                </h3>
                                                <ul
                                                    class="pl-5 space-y-2 text-sm list-disc text-slate-700 dark:text-slate-300">
                                                    <li><strong>Profit Trend chart-এ</strong> Revenue column এবং Profit
                                                        line
                                                        হিসেবে দেখায়।</li>
                                                    <li>Chart-এর selector দিয়ে <strong>Daily, Weekly, Monthly</strong>
                                                        বা
                                                        <strong>Yearly</strong> সময়সীমা বেছে নেওয়া যায়। Daily-তে আজ,
                                                        Weekly ও
                                                        Monthly-তে সংশ্লিষ্ট দিনগুলো, আর Yearly-তে মাসভিত্তিক মান দেখা
                                                        যায়।
                                                    </li>
                                                    <li>নিচের <strong>Top Selling Products</strong> এবং <strong>Top
                                                            Sales
                                                            Employees</strong> অংশে সর্বোচ্চ বিক্রির পণ্য ও কর্মীদের
                                                        তালিকা
                                                        থাকে।</li>
                                                </ul>
                                            </div>
                                        </div>

                                        <!-- Dashboard Note -->
                                        <div
                                            class="flex items-start gap-3 p-4 text-sm border rounded-2xl bg-amber-50 dark:bg-amber-950/30 border-amber-200 dark:border-amber-800/50 text-amber-900 dark:text-amber-200">
                                            <i data-lucide="lightbulb"
                                                class="w-5 h-5 text-amber-600 dark:text-amber-400 shrink-0 mt-0.5"></i>
                                            <p class="leading-relaxed">
                                                <strong>নোট:</strong> Dashboard-এর card-গুলোর অঙ্ক গোল করে দেখানো হতে
                                                পারে।
                                                Card-এর সময়সীমা ও chart-এর selector এক জিনিস নয়—chart-এর selector
                                                বদলালে
                                                card-এর Daily/Weekly/Monthly মান বদলায় না।
                                            </p>
                                        </div>
                                    </section>

                                    <!-- ========================================== -->
                                    <!-- SECTION 4: SALES MENU: SALE -> CREATE SALE -->
                                    <!-- ========================================== -->
                                    <section id="section-sales"
                                        class="p-6 space-y-8 bg-white border shadow-sm searchable-section scroll-mt-28 dark:bg-slate-900 rounded-3xl border-slate-200 dark:border-slate-800 sm:p-8">
                                        <div
                                            class="flex items-center gap-3 pb-4 border-b border-slate-100 dark:border-slate-800">
                                            <div
                                                class="flex items-center justify-center w-10 h-10 text-lg font-bold text-purple-600 rounded-2xl bg-purple-50 dark:bg-purple-950/60 dark:text-purple-400 shrink-0">
                                                ৪
                                            </div>
                                            <div>
                                                <h2 class="text-xl font-bold sm:text-2xl text-slate-900 dark:text-white">
                                                    Sales
                                                    Menu: Sale → Create Sale</h2>
                                                <p class="text-sm text-slate-500 dark:text-slate-400">নতুন বিক্রি তৈরি,
                                                    পেমেন্ট
                                                    গ্রহণ এবং ইনভয়েস প্রিন্ট ও আপডেটের ধাপসমূহ</p>
                                            </div>
                                        </div>

                                        <!-- Stepper Timeline -->
                                        <div
                                            class="relative space-y-8 before:absolute before:inset-0 before:ml-5 before:-translate-x-px md:before:ml-6 before:h-full before:w-0.5 before:bg-gradient-to-b before:from-brand-500 before:via-slate-200 dark:before:via-slate-700 before:to-transparent">

                                            <!-- ধাপ ১ -->
                                            <div class="relative flex items-start gap-4 sm:gap-6">
                                                <div
                                                    class="z-10 flex items-center justify-center w-10 h-10 text-sm font-bold text-white shadow-sm md:w-12 md:h-12 rounded-2xl bg-brand-600 shrink-0 md:text-base">
                                                    ধাপ ১
                                                </div>
                                                <div
                                                    class="flex-1 bg-slate-50 dark:bg-slate-800/50 rounded-2xl p-5 border border-slate-200/80 dark:border-slate-700/70 space-y-2.5">
                                                    <h3 class="text-lg font-bold text-slate-900 dark:text-white">Create
                                                        Sale
                                                        খোলা</h3>
                                                    <ol
                                                        class="list-decimal pl-5 space-y-1.5 text-sm sm:text-base text-slate-700 dark:text-slate-300">
                                                        <li>Menu-তে <strong>Sales</strong> খুলুন।</li>
                                                        <li><strong>Create Sale</strong> নির্বাচন করুন।</li>
                                                    </ol>
                                                    <p class="pt-1 text-sm text-slate-600 dark:text-slate-400">
                                                        Customer, Sale Items এবং Invoice & Payment অংশ পূরণ করুন।
                                                        প্রয়োজনীয়
                                                        permission না থাকলে এই menu বা form দেখা যাবে না।
                                                    </p>
                                                </div>
                                            </div>

                                            <!-- ধাপ ২ -->
                                            <div class="relative flex items-start gap-4 sm:gap-6">
                                                <div
                                                    class="z-10 flex items-center justify-center w-10 h-10 text-sm font-bold text-white shadow-sm md:w-12 md:h-12 rounded-2xl bg-brand-600 shrink-0 md:text-base">
                                                    ধাপ ২
                                                </div>
                                                <div
                                                    class="flex-1 p-5 space-y-3 border bg-slate-50 dark:bg-slate-800/50 rounded-2xl border-slate-200/80 dark:border-slate-700/70">
                                                    <h3 class="text-lg font-bold text-slate-900 dark:text-white">
                                                        Customer খোঁজা
                                                    </h3>
                                                    <ol
                                                        class="pl-5 space-y-2 text-sm list-decimal sm:text-base text-slate-700 dark:text-slate-300">
                                                        <li><strong>Customer Name or Phone</strong> ঘরে গ্রাহকের নাম বা
                                                            ফোন
                                                            নম্বর লিখুন।</li>
                                                        <li>নাম লিখলে মিলে যাওয়া customer suggestion দেখুন এবং সঠিক
                                                            গ্রাহকের
                                                            suggestion নির্বাচন করুন। ফোন নম্বর লিখলে বা
                                                            <strong>Find</strong>
                                                            চাপলে customer lookup হয়।
                                                        </li>
                                                        <li>আগে থেকে থাকা customer পাওয়া গেলে তার নাম ও ledger load
                                                            হবে।
                                                            <strong>Outstanding</strong>-এ বর্তমান বকেয়া এবং ledger-এ
                                                            সংশ্লিষ্ট
                                                            লেনদেন দেখা যায়।
                                                        </li>
                                                        <li><strong>Add outstanding due to this invoice</strong>
                                                            checkbox-এ টিক
                                                            দিলে আগের বকেয়াটি এই invoice total-এ যোগ হবে। টিক না দিলে
                                                            আগের
                                                            বকেয়া নতুন invoice total-এ যোগ হবে না।</li>
                                                        <li>Customer না পাওয়া গেলে saved customer balance দেখানো হবে
                                                            না।
                                                            Checkout-এর সময় নতুন customer save করা অথবা walk-in হিসেবে
                                                            বিক্রি
                                                            করার বিকল্প আসবে।</li>
                                                    </ol>
                                                    <div
                                                        class="p-3.5 rounded-xl bg-amber-50 dark:bg-amber-950/40 border border-amber-200 dark:border-amber-800/50 text-xs sm:text-sm text-amber-900 dark:text-amber-200">
                                                        <strong>গুরুত্বপূর্ণ:</strong> Customer বাছাই হয় name/phone
                                                        search ও
                                                        suggestion দিয়ে। Checkbox-টি customer select করার জন্য নয়; এটি
                                                        আগের
                                                        বকেয়া invoice-এ যোগ করার জন্য।
                                                    </div>
                                                </div>
                                            </div>

                                            <!-- ধাপ ৩ -->
                                            <div class="relative flex items-start gap-4 sm:gap-6">
                                                <div
                                                    class="z-10 flex items-center justify-center w-10 h-10 text-sm font-bold text-white shadow-sm md:w-12 md:h-12 rounded-2xl bg-brand-600 shrink-0 md:text-base">
                                                    ধাপ ৩
                                                </div>
                                                <div
                                                    class="flex-1 p-5 space-y-3 border bg-slate-50 dark:bg-slate-800/50 rounded-2xl border-slate-200/80 dark:border-slate-700/70">
                                                    <h3 class="text-lg font-bold text-slate-900 dark:text-white">Sale
                                                        Items যোগ
                                                        করা</h3>
                                                    <ol
                                                        class="pl-5 space-y-2 text-sm list-decimal sm:text-base text-slate-700 dark:text-slate-300">
                                                        <li><strong>Product</strong> ঘরে পণ্যের নাম বা code লিখুন।</li>
                                                        <li>Inventory-তে থাকা এবং বিক্রির জন্য stock থাকা পণ্য
                                                            suggestion-এ
                                                            আসবে; সঠিক পণ্যটি নির্বাচন করুন।</li>
                                                        <li>পণ্য নির্বাচন হলে inventory-র selling price ও unit বসে এবং
                                                            quantity
                                                            সাধারণত <strong>1</strong> দিয়ে শুরু হয়।</li>
                                                        <li><strong>Quantity</strong> লিখুন। প্রতিটি লাইনের
                                                            <strong>LineTotal</strong> স্বয়ংক্রিয়ভাবে <code
                                                                class="text-xs bg-slate-200 dark:bg-slate-700 px-1.5 py-0.5 rounded">UnitPrice
                                                                × Quantity</code> হিসেবে আপডেট হবে।
                                                            <strong>Subtotal</strong>-ও
                                                            সঙ্গে সঙ্গে পুনর্গণনা হয়।
                                                        </li>
                                                        <li>আরও পণ্য যোগ করতে <strong>Add Product</strong> চাপুন। কোনো
                                                            পণ্য
                                                            সরাতে তার সারির delete আইকন ব্যবহার করুন।</li>
                                                    </ol>
                                                    <p
                                                        class="text-xs sm:text-sm text-rose-600 dark:text-rose-400 font-medium flex items-center gap-1.5">
                                                        <i data-lucide="alert-circle" class="w-4 h-4 shrink-0"></i>
                                                        Stock-এর বেশি quantity দিলে সতর্কতা দেখাবে; প্রয়োজনমতো quantity
                                                        কমান।
                                                    </p>
                                                </div>
                                            </div>

                                            <!-- ধাপ ৪ -->
                                            <div class="relative flex items-start gap-4 sm:gap-6">
                                                <div
                                                    class="z-10 flex items-center justify-center w-10 h-10 text-sm font-bold text-white shadow-sm md:w-12 md:h-12 rounded-2xl bg-brand-600 shrink-0 md:text-base">
                                                    ধাপ ৪
                                                </div>
                                                <div
                                                    class="flex-1 p-5 space-y-3 border bg-slate-50 dark:bg-slate-800/50 rounded-2xl border-slate-200/80 dark:border-slate-700/70">
                                                    <h3 class="text-lg font-bold text-slate-900 dark:text-white">
                                                        Invoice &
                                                        Payment</h3>
                                                    <ol
                                                        class="list-decimal pl-5 space-y-2.5 text-sm sm:text-base text-slate-700 dark:text-slate-300">
                                                        <li><strong>Discount Type</strong> থেকে <strong>Amount</strong>
                                                            (নির্দিষ্ট টাকা) অথবা <strong>Percent</strong> বেছে নিন;
                                                            পাশের ঘরে
                                                            discount লিখুন।</li>
                                                        <li><strong>VAT (%)</strong> ও <strong>AIT (%)</strong>-এ
                                                            শতাংশের হার
                                                            দিন। Discount বাদ দেওয়ার পরের অঙ্কের ওপর এগুলোর টাকা
                                                            স্বয়ংক্রিয়ভাবে হিসাব হয়।</li>
                                                        <li><strong>Amount Received</strong>-এ এখন যে টাকা নিচ্ছেন তা
                                                            লিখুন।
                                                            <strong>Invoice Total</strong> ও <strong>Balance
                                                                Due</strong>
                                                            স্বয়ংক্রিয়ভাবে আপডেট হবে।
                                                        </li>
                                                        <li>টাকা গ্রহণ করলে <strong>Payment Method</strong> নির্বাচন
                                                            করুন: <span
                                                                class="font-medium text-slate-900 dark:text-white">Cash,
                                                                Bank,
                                                                Nagad, bKash, Rocket</span> বা <span
                                                                class="font-medium text-slate-900 dark:text-white">Upay</span>।
                                                        </li>
                                                        <li>
                                                            <strong>Checkout</strong> চাপলে নতুন customer-এর ক্ষেত্রে
                                                            একটি
                                                            confirmation আসবে:
                                                            <ul
                                                                class="mt-2 space-y-1.5 pl-4 border-l-2 border-brand-400 text-sm">
                                                                <li><strong>Yes, save customer:</strong> গ্রাহকের নাম
                                                                    লিখুন;
                                                                    Email ও Address ঐচ্ছিক। এরপর checkout সম্পন্ন হবে।
                                                                </li>
                                                                <li><strong>No, walk-in:</strong> customer record
                                                                    সংরক্ষণ হবে
                                                                    না। Amount Received-এ invoice-এর পুরো মোট টাকা বসবে;
                                                                    Payment
                                                                    Method নির্বাচন করে checkout করুন। Walk-in sale-এ
                                                                    পুরো
                                                                    invoice amount পরিশোধ করতে হয়।</li>
                                                                <li><strong>Cancel sale:</strong> checkout বাতিল হবে।
                                                                </li>
                                                            </ul>
                                                        </li>
                                                        <li><strong>Checkout & Print Invoice</strong> চাপুন। Sale
                                                            সংরক্ষণের পর
                                                            invoice page খুলবে এবং print dialog চালু হতে পারে। Sale-এর
                                                            পরে stock
                                                            কমে যায়।</li>
                                                    </ol>
                                                    <div
                                                        class="p-3.5 rounded-xl bg-slate-100 dark:bg-slate-900/80 border border-slate-200 dark:border-slate-700 text-xs sm:text-sm text-slate-700 dark:text-slate-300">
                                                        Invoice-এর মোট অঙ্কে Subtotal, discount, VAT, AIT এবং checkbox
                                                        দিয়ে যোগ
                                                        করা হলে Previous Due থাকে। <strong>Balance Due</strong> হলো
                                                        Invoice
                                                        Total থেকে Amount Received বাদ দেওয়ার পরের বাকি টাকা।
                                                    </div>
                                                </div>
                                            </div>

                                            <!-- ধাপ ৫ -->
                                            <div class="relative flex items-start gap-4 sm:gap-6">
                                                <div
                                                    class="z-10 flex items-center justify-center w-10 h-10 text-sm font-bold text-white shadow-sm md:w-12 md:h-12 rounded-2xl bg-brand-600 shrink-0 md:text-base">
                                                    ধাপ ৫
                                                </div>
                                                <div
                                                    class="flex-1 p-5 space-y-3 border bg-slate-50 dark:bg-slate-800/50 rounded-2xl border-slate-200/80 dark:border-slate-700/70">
                                                    <h3 class="text-lg font-bold text-slate-900 dark:text-white">
                                                        Invoice দেখা,
                                                        update ও print</h3>
                                                    <p class="text-sm sm:text-base text-slate-700 dark:text-slate-300">
                                                        Invoice page-এ permission অনুযায়ী নিচের action-গুলো থাকতে পারে:
                                                    </p>
                                                    <ul
                                                        class="space-y-2.5 text-sm sm:text-base text-slate-700 dark:text-slate-300">
                                                        <li class="flex items-start gap-2">
                                                            <span
                                                                class="px-2 py-0.5 rounded-md bg-emerald-100 dark:bg-emerald-900/50 text-emerald-800 dark:text-emerald-300 font-semibold text-xs shrink-0 mt-1">New
                                                                Sale</span>
                                                            <span>Create Sale page-এ ফেরে।</span>
                                                        </li>
                                                        <li class="flex items-start gap-2">
                                                            <span
                                                                class="px-2 py-0.5 rounded-md bg-blue-100 dark:bg-blue-900/50 text-blue-800 dark:text-blue-300 font-semibold text-xs shrink-0 mt-1">Update
                                                                Invoice</span>
                                                            <span>Invoice edit form খোলে; items, quantity, unit price,
                                                                discount,
                                                                VAT/AIT, payment received, notes বা terms সংশোধন করে
                                                                Update
                                                                Invoice চাপুন। সংরক্ষণের পর stock, customer ledger,
                                                                voucher ও
                                                                dashboard-সংশ্লিষ্ট totals সমন্বয় হয়। গ্রাহকের পরিচয়
                                                                update
                                                                form-এ অপরিবর্তিত থাকে।</span>
                                                        </li>
                                                        <li class="flex items-start gap-2">
                                                            <span
                                                                class="px-2 py-0.5 rounded-md bg-rose-100 dark:bg-rose-900/50 text-rose-800 dark:text-rose-300 font-semibold text-xs shrink-0 mt-1">Void
                                                                Invoice</span>
                                                            <span>confirmation-এর পর invoice বাতিল করে সংশ্লিষ্ট stock,
                                                                ledger,
                                                                payment/voucher ও due entries reverse করে। এটি সাধারণ
                                                                edit নয়;
                                                                প্রয়োজন নিশ্চিত হয়ে তবেই ব্যবহার করুন।</span>
                                                        </li>
                                                        <li class="flex items-start gap-2">
                                                            <span
                                                                class="px-2 py-0.5 rounded-md bg-purple-100 dark:bg-purple-900/50 text-purple-800 dark:text-purple-300 font-semibold text-xs shrink-0 mt-1">Print
                                                                logo watermark</span>
                                                            <span>সংস্থার logo থাকলে এবং অনুমতি থাকলে printout-এ logo
                                                                watermark
                                                                চালু/বন্ধ করে।</span>
                                                        </li>
                                                        <li class="flex items-start gap-2">
                                                            <span
                                                                class="px-2 py-0.5 rounded-md bg-slate-200 dark:bg-slate-700 text-slate-800 dark:text-slate-200 font-semibold text-xs shrink-0 mt-1">Print
                                                                Invoice</span>
                                                            <span>invoice print dialog খোলে। Watermark চাইলে আগে Print
                                                                logo
                                                                watermark নির্বাচন করুন।</span>
                                                        </li>
                                                    </ul>
                                                    <p class="pt-1 text-xs sm:text-sm text-slate-500 dark:text-slate-400">
                                                        Update Invoice এবং Void Invoice role/permission এবং invoice-এর
                                                        তারিখ
                                                        অনুযায়ী দেখা যায়। কোনো button না থাকলে administrator-এর সঙ্গে
                                                        যোগাযোগ
                                                        করুন।
                                                    </p>
                                                </div>
                                            </div>

                                        </div>
                                    </section>

                                    <!-- ========================================== -->
                                    <!-- SECTION 5: RECENT INVOICES                 -->
                                    <!-- ========================================== -->
                                    <section id="section-invoices"
                                        class="p-6 space-y-6 bg-white border shadow-sm searchable-section scroll-mt-28 dark:bg-slate-900 rounded-3xl border-slate-200 dark:border-slate-800 sm:p-8">
                                        <div
                                            class="flex items-center gap-3 pb-4 border-b border-slate-100 dark:border-slate-800">
                                            <div
                                                class="flex items-center justify-center w-10 h-10 text-lg font-bold text-teal-600 rounded-2xl bg-teal-50 dark:bg-teal-950/60 dark:text-teal-400 shrink-0">
                                                ৫
                                            </div>
                                            <div>
                                                <h2 class="text-xl font-bold sm:text-2xl text-slate-900 dark:text-white">
                                                    Recent
                                                    Invoices</h2>
                                                <p class="text-sm text-slate-500 dark:text-slate-400">সর্বশেষ ১০টি
                                                    ইনভয়েসের
                                                    তালিকা ও কলাম পরিচিতি</p>
                                            </div>
                                        </div>

                                        <p class="text-sm sm:text-base text-slate-600 dark:text-slate-300">
                                            Recent Invoices টেবিলে সর্বশেষ ১০টি invoice থাকে। প্রতিটি কলামের অর্থ:
                                        </p>

                                        <!-- Table -->
                                        <div
                                            class="overflow-x-auto border rounded-2xl border-slate-200 dark:border-slate-800">
                                            <table class="w-full text-left border-collapse">
                                                <thead>
                                                    <tr
                                                        class="text-sm font-semibold border-b bg-slate-100/80 dark:bg-slate-800/90 text-slate-700 dark:text-slate-200 border-slate-200 dark:border-slate-700">
                                                        <th class="py-3.5 px-4 w-1/3">কলাম</th>
                                                        <th class="py-3.5 px-4">অর্থ</th>
                                                    </tr>
                                                </thead>
                                                <tbody
                                                    class="text-sm divide-y divide-slate-200 dark:divide-slate-800 sm:text-base">
                                                    <tr class="hover:bg-slate-50/80 dark:hover:bg-slate-800/40">
                                                        <td
                                                            class="py-3.5 px-4 font-semibold text-slate-900 dark:text-white">
                                                            Invoice</td>
                                                        <td class="py-3.5 px-4">Invoice নম্বর</td>
                                                    </tr>
                                                    <tr class="hover:bg-slate-50/80 dark:hover:bg-slate-800/40">
                                                        <td
                                                            class="py-3.5 px-4 font-semibold text-slate-900 dark:text-white">
                                                            Date</td>
                                                        <td class="py-3.5 px-4">Invoice তৈরির তারিখ</td>
                                                    </tr>
                                                    <tr class="hover:bg-slate-50/80 dark:hover:bg-slate-800/40">
                                                        <td
                                                            class="py-3.5 px-4 font-semibold text-slate-900 dark:text-white">
                                                            Customer Phone</td>
                                                        <td class="py-3.5 px-4">Invoice-এ সংরক্ষিত customer phone</td>
                                                    </tr>
                                                    <tr class="hover:bg-slate-50/80 dark:hover:bg-slate-800/40">
                                                        <td
                                                            class="py-3.5 px-4 font-semibold text-slate-900 dark:text-white">
                                                            Employee</td>
                                                        <td class="py-3.5 px-4">Sale তৈরি করা employee; পাওয়া না গেলে
                                                            <code
                                                                class="text-xs bg-slate-100 dark:bg-slate-800 px-1.5 py-0.5 rounded">N/A</code>
                                                        </td>
                                                    </tr>
                                                    <tr class="hover:bg-slate-50/80 dark:hover:bg-slate-800/40">
                                                        <td
                                                            class="py-3.5 px-4 font-semibold text-slate-900 dark:text-white">
                                                            Total</td>
                                                        <td class="py-3.5 px-4">Invoice-এর মোট টাকা</td>
                                                    </tr>
                                                    <tr class="hover:bg-slate-50/80 dark:hover:bg-slate-800/40">
                                                        <td
                                                            class="py-3.5 px-4 font-semibold text-slate-900 dark:text-white">
                                                            Due</td>
                                                        <td class="py-3.5 px-4">Invoice-এর বকেয়া টাকা</td>
                                                    </tr>
                                                </tbody>
                                            </table>
                                        </div>

                                        <!-- Action Buttons Info -->
                                        <div class="pt-1 space-y-3">
                                            <h3 class="text-base font-bold sm:text-lg text-slate-900 dark:text-white">
                                                প্রতিটি
                                                row-এর action button:</h3>
                                            <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                                                <div
                                                    class="flex items-start gap-3 p-4 border rounded-2xl bg-slate-50 dark:bg-slate-800/60 border-slate-200 dark:border-slate-700">
                                                    <div
                                                        class="p-2 rounded-xl bg-brand-100 dark:bg-brand-900/50 text-brand-600 dark:text-brand-400 shrink-0">
                                                        <i data-lucide="eye" class="w-5 h-5"></i>
                                                    </div>
                                                    <div>
                                                        <h4
                                                            class="text-sm font-bold text-slate-900 dark:text-white sm:text-base">
                                                            View Invoice (চোখের আইকন)</h4>
                                                        <p class="text-sm text-slate-600 dark:text-slate-300 mt-0.5">
                                                            সম্পূর্ণ
                                                            invoice ও তার print/update/void action-গুলো খোলে।</p>
                                                    </div>
                                                </div>
                                                <div
                                                    class="flex items-start gap-3 p-4 border rounded-2xl bg-slate-50 dark:bg-slate-800/60 border-slate-200 dark:border-slate-700">
                                                    <div
                                                        class="p-2 text-teal-600 bg-teal-100 rounded-xl dark:bg-teal-900/50 dark:text-teal-400 shrink-0">
                                                        <i data-lucide="truck" class="w-5 h-5"></i>
                                                    </div>
                                                    <div>
                                                        <h4
                                                            class="text-sm font-bold text-slate-900 dark:text-white sm:text-base">
                                                            Delivery Challan</h4>
                                                        <p class="text-sm text-slate-600 dark:text-slate-300 mt-0.5">ওই
                                                            sale-এর
                                                            পণ্য ও delivery-সংক্রান্ত challan খোলে।</p>
                                                    </div>
                                                </div>
                                            </div>

                                            <div
                                                class="p-4 space-y-1 text-sm border rounded-2xl bg-slate-100/80 dark:bg-slate-800/80 border-slate-200 dark:border-slate-700 text-slate-700 dark:text-slate-300">
                                                <p>
                                                    <strong>দ্রষ্টব্য:</strong> Update/Void button Recent Invoices
                                                    row-তে নেই;
                                                    সেগুলো invoice খুললে permission অনুযায়ী দেখা যায়।
                                                </p>
                                                <p>
                                                    নতুন invoice না থাকলে <code
                                                        class="text-xs bg-white dark:bg-slate-900 px-2 py-0.5 rounded border border-slate-200 dark:border-slate-700 font-medium">No
                                                        invoices have been created yet.</code> বার্তাটি দেখানো হয়।
                                                </p>
                                            </div>
                                        </div>
                                    </section>

                                </main>
                            </div>

                            <!-- Interactive Script -->
                            <script>
                                document.addEventListener('DOMContentLoaded', () => {
                                    // Initialize Lucide icons
                                    if (window.lucide) {
                                        lucide.createIcons();
                                    }

                                    // 1. Dark Mode Toggle
                                    const themeToggle = document.getElementById('theme-toggle');
                                    const htmlEl = document.documentElement;

                                    // Check saved preference or system preference
                                    if (localStorage.getItem('manual-theme') === 'dark' ||
                                        (!localStorage.getItem('manual-theme') && window.matchMedia('(prefers-color-scheme: dark)').matches)
                                    ) {
                                        htmlEl.classList.add('dark');
                                    }

                                    themeToggle.addEventListener('click', () => {
                                        htmlEl.classList.toggle('dark');
                                        localStorage.setItem('manual-theme', htmlEl.classList.contains('dark') ? 'dark' : 'light');
                                    });

                                    // 2. Module Category Filter Tabs
                                    const moduleTabs = document.querySelectorAll('.module-tab');
                                    const moduleGroups = document.querySelectorAll('.module-group');

                                    moduleTabs.forEach(tab => {
                                        tab.addEventListener('click', () => {
                                            const filter = tab.getAttribute('data-filter');

                                            // Update active tab styling
                                            moduleTabs.forEach(t => {
                                                t.classList.remove('bg-brand-600', 'text-white', 'shadow-xs');
                                                t.classList.add('bg-slate-100', 'dark:bg-slate-800',
                                                    'text-slate-700', 'dark:text-slate-300');
                                            });
                                            tab.classList.remove('bg-slate-100', 'dark:bg-slate-800', 'text-slate-700',
                                                'dark:text-slate-300');
                                            tab.classList.add('bg-brand-600', 'text-white', 'shadow-xs');

                                            // Show/Hide module groups
                                            moduleGroups.forEach(group => {
                                                if (filter === 'all' || group.getAttribute('data-category') ===
                                                    filter) {
                                                    group.classList.remove('hidden');
                                                } else {
                                                    group.classList.add('hidden');
                                                }
                                            });
                                        });
                                    });

                                    // 3. Live Search & Highlighting
                                    const searchInput = document.getElementById('search-input');
                                    const clearSearchBtn = document.getElementById('clear-search');
                                    const searchStatus = document.getElementById('search-status');
                                    const searchCount = document.getElementById('search-count');
                                    const manualContent = document.getElementById('manual-content');

                                    // Store original HTML of searchable blocks to cleanly restore
                                    const searchableBlocks = document.querySelectorAll(
                                        '#manual-content p, #manual-content td, #manual-content li, #manual-content h2, #manual-content h3, #manual-content h4'
                                    );
                                    searchableBlocks.forEach(el => {
                                        el.dataset.originalHtml = el.innerHTML;
                                    });

                                    function escapeRegExp(string) {
                                        return string.replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
                                    }

                                    function performSearch() {
                                        const query = searchInput.value.trim();

                                        if (!query) {
                                            clearSearchBtn.classList.add('hidden');
                                            searchStatus.classList.add('hidden');
                                            searchableBlocks.forEach(el => {
                                                el.innerHTML = el.dataset.originalHtml;
                                            });
                                            return;
                                        }

                                        clearSearchBtn.classList.remove('hidden');
                                        // Ensure all module tabs are visible when searching so matches aren't hidden
                                        moduleGroups.forEach(g => g.classList.remove('hidden'));

                                        const regex = new RegExp(`(${escapeRegExp(query)})`, 'gi');
                                        let matchCount = 0;

                                        searchableBlocks.forEach(el => {
                                            const original = el.dataset.originalHtml;
                                            // Avoid breaking HTML tags by only replacing text outside of <...>
                                            const parts = original.split(/(<[^>]+>)/g);
                                            let hasMatch = false;

                                            const highlighted = parts.map(part => {
                                                if (part.startsWith('<')) return part;
                                                if (regex.test(part)) {
                                                    hasMatch = true;
                                                    return part.replace(regex, '<mark class="search-highlight">$1</mark>');
                                                }
                                                return part;
                                            }).join('');

                                            el.innerHTML = highlighted;
                                            if (hasMatch) matchCount++;
                                        });

                                        searchCount.textContent = matchCount;
                                        searchStatus.classList.remove('hidden');
                                    }

                                    searchInput.addEventListener('input', performSearch);
                                    clearSearchBtn.addEventListener('click', () => {
                                        searchInput.value = '';
                                        performSearch();
                                        searchInput.focus();
                                    });

                                    // 4. Scroll-Spy for Compact Horizontal Navigation Menu
                                    const navItems = document.querySelectorAll('.toc-nav-item');
                                    const sections = document.querySelectorAll('.searchable-section');

                                    function setActiveNavItem(targetId) {
                                        navItems.forEach(link => {
                                            if (link.getAttribute('data-target') === targetId) {
                                                link.classList.remove('bg-white', 'dark:bg-slate-800', 'text-slate-700',
                                                    'dark:text-slate-300', 'border', 'border-slate-200/80',
                                                    'dark:border-slate-700');
                                                link.classList.add('bg-brand-600', 'text-white', 'shadow-xs');
                                                // Smoothly keep active pill visible in mobile horizontal scroll
                                                link.scrollIntoView({
                                                    behavior: 'smooth',
                                                    block: 'nearest',
                                                    inline: 'center'
                                                });
                                            } else {
                                                link.classList.remove('bg-brand-600', 'text-white', 'shadow-xs');
                                                link.classList.add('bg-white', 'dark:bg-slate-800', 'text-slate-700',
                                                    'dark:text-slate-300', 'border', 'border-slate-200/80',
                                                    'dark:border-slate-700');
                                            }
                                        });
                                    }

                                    // Click feedback
                                    navItems.forEach(link => {
                                        link.addEventListener('click', () => {
                                            setActiveNavItem(link.getAttribute('data-target'));
                                        });
                                    });

                                    // IntersectionObserver for automatic scroll-spy
                                    const observerOptions = {
                                        root: null,
                                        rootMargin: '-120px 0px -60% 0px',
                                        threshold: 0
                                    };

                                    const sectionObserver = new IntersectionObserver((entries) => {
                                        entries.forEach(entry => {
                                            if (entry.isIntersecting) {
                                                setActiveNavItem(entry.target.id);
                                            }
                                        });
                                    }, observerOptions);

                                    sections.forEach(sec => sectionObserver.observe(sec));
                                });
                            </script>
                        </body>

                        </html>
                    </div>
                </div>
            </div>
        @endsection
    </div>
</div>
@endsection
