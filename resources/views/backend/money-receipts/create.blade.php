@extends('dashboard.dashboard')
@section('title', 'Blank Money Receipt')

@section('content')
    <div class="page-wrapper">
        <div class="page-content">
            <div class="gap-2 mb-3 d-flex justify-content-end no-print">
                <a href="{{ route('sales.index') }}" class="btn btn-outline-secondary">
                    <i class="bx bx-arrow-back me-1"></i>Back to Sales
                </a>
                <button type="button" class="btn btn-primary" onclick="window.print()">
                    <i class="bx bx-printer me-1"></i>Print Money Receipt
                </button>
            </div>

            @for ($copy = 1; $copy <= 3; $copy++)
                <main class="money-receipt">
                    <div class="receipt-accent"></div>
                    <header class="receipt-heading">
                        <div class="receipt-organization">
                            @if ($organization?->logo)
                                <img src="{{ asset('uploads/organizations/' . $organization->logo) }}"
                                    alt="{{ $organization->name ?: 'Organization logo' }}">
                            @endif
                            <div>
                                <h2>{{ $organization?->name ?: config('app.name') }}</h2>
                                @if ($organization?->address)
                                    <div>{{ $organization->address }}</div>
                                @endif
                                @if ($organization?->phone)
                                    <div>Phone: {{ $organization->phone }}</div>
                                @endif
                                @if ($organization?->email)
                                    <div>{{ $organization->email }}</div>
                                @endif
                            </div>
                        </div>
                        <div class="receipt-title">
                            <span>PAYMENT RECORD</span>
                            <h1>MONEY RECEIPT</h1>
                        </div>
                    </header>

                    <div class="receipt-meta">
                        <div>Receipt No. <span class="write-line"></span></div>
                        <div>Date <span class="write-line"></span></div>
                    </div>

                    <section class="receipt-fields" aria-label="Receipt details">
                        <div class="receipt-field">
                            <span class="field-label">Received with thanks from</span>
                            <span class="field-line"></span>
                        </div>
                        <div class="receipt-field">
                            <span class="field-label">Amount in words</span>
                            <span class="field-line"></span>
                        </div>
                        <div class="receipt-field">
                            <span class="field-label">Payer contact number</span>
                            <span class="field-line"></span>
                        </div>
                    </section>

                    <div class="receipt-payment-row">
                        <div class="payment-options">
                            <span class="field-label">Payment method</span>
                            <span class="payment-option"><i></i>Cash</span>
                            <span class="payment-option"><i></i>Cheque</span>
                            <span class="payment-option"><i></i>Card</span>
                            <span class="payment-option"><i></i>Bank transfer</span>
                            <span class="payment-option"><i></i>Mobile wallet</span>
                        </div>
                        <div class="receipt-amount">
                            <span>Amount</span>
                            <strong>৳</strong>
                            <span class="amount-line"></span>
                        </div>
                    </div>

                    <div class="receipt-field receipt-reference">
                        <span class="field-label">Payment reference / bank details</span>
                        <span class="field-line"></span>
                    </div>
                    <div class="receipt-field receipt-purpose">
                        <span class="field-label">Payment received for</span>
                        <span class="field-line"></span>
                    </div>

                    <footer class="receipt-signatures">
                        <div><span></span>Received by</div>
                        <div><span></span>Authorized signature</div>
                    </footer>
                </main>
            @endfor
        </div>
    </div>

    <style>
        .money-receipt {
            position: relative;
            width: min(100%, 900px);
            margin: 0 auto;
            padding: 2rem 2.25rem 1.5rem;
            overflow: hidden;
            color: #23313d;
            background: #fff;
            border: 1px solid #d6e0e7;
            border-radius: .5rem;
            box-shadow: 0 4px 18px rgba(27, 49, 66, .08);
            font-size: .95rem;
        }

        .money-receipt:not(:first-of-type) {
            display: none;
        }

        .receipt-accent {
            position: absolute;
            inset: 0 auto 0 0;
            width: 9px;
            background: #167a78;
        }

        .receipt-heading {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 1.5rem;
            padding-bottom: 1.25rem;
            border-bottom: 2px solid #167a78;
        }

        .receipt-organization {
            display: flex;
            align-items: center;
            gap: 1rem;
            min-width: 0;
            color: #52616d;
            font-size: .82rem;
            overflow-wrap: anywhere;
        }

        .receipt-organization img {
            flex: 0 0 auto;
            max-width: 100px;
            max-height: 72px;
            object-fit: contain;
        }

        .receipt-organization h2 {
            margin: 0 0 .35rem;
            color: #23313d;
            font-size: 1.2rem;
            font-weight: 700;
        }

        .receipt-title {
            flex: 0 0 auto;
            text-align: right;
        }

        .receipt-title span {
            color: #167a78;
            font-size: .7rem;
            font-weight: 700;
            letter-spacing: .14em;
        }

        .receipt-title h1 {
            margin: .2rem 0 0;
            color: #23313d;
            font-size: 1.65rem;
            font-weight: 800;
            letter-spacing: .04em;
        }

        .receipt-meta {
            display: flex;
            justify-content: space-between;
            gap: 2rem;
            margin: 1rem 0 1.25rem;
            font-weight: 600;
        }

        .receipt-meta>div {
            display: flex;
            flex: 1;
            align-items: end;
            gap: .5rem;
        }

        .write-line,
        .field-line {
            flex: 1;
            min-width: 2rem;
            height: 1.35rem;
            border-bottom: 1px dotted #687782;
        }

        .receipt-fields {
            display: grid;
            gap: .85rem;
        }

        .receipt-field {
            display: flex;
            align-items: end;
            gap: .5rem;
        }

        .field-label {
            flex: 0 0 auto;
            color: #3e4d58;
            font-weight: 600;
        }

        .receipt-payment-row {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 1rem;
            margin: 1rem 0;
        }

        .payment-options {
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            gap: .45rem .8rem;
        }

        .payment-options .field-label {
            flex-basis: 100%;
        }

        .payment-option {
            display: inline-flex;
            align-items: center;
            gap: .3rem;
            white-space: nowrap;
        }

        .payment-option i {
            width: 13px;
            height: 13px;
            border: 1px solid #586975;
        }

        .receipt-amount {
            display: flex;
            flex: 0 0 210px;
            align-items: center;
            gap: .5rem;
            min-height: 48px;
            padding: .5rem .75rem;
            border: 1px solid #167a78;
            border-radius: .25rem;
            color: #167a78;
            font-weight: 700;
        }

        .receipt-amount strong {
            color: #23313d;
            font-size: 1.3rem;
        }

        .amount-line {
            flex: 1;
            height: 1.4rem;
            border-bottom: 1px dotted #687782;
        }

        .receipt-reference,
        .receipt-purpose {
            margin-top: .9rem;
        }

        .receipt-signatures {
            display: flex;
            justify-content: space-between;
            gap: 2rem;
            margin-top: 2.5rem;
        }

        .receipt-signatures>div {
            flex: 0 1 220px;
            padding-top: .4rem;
            border-top: 1px solid #52616d;
            text-align: center;
            font-size: .85rem;
        }

        @media (max-width: 575.98px) {
            .money-receipt {
                padding: 1.25rem 1rem 1rem 1.25rem;
            }

            .receipt-heading,
            .receipt-payment-row,
            .receipt-meta {
                align-items: stretch;
                flex-direction: column;
            }

            .receipt-title {
                text-align: left;
            }

            .receipt-amount {
                flex-basis: auto;
            }

            .receipt-field {
                align-items: flex-start;
                flex-direction: column;
                gap: .1rem;
            }

            .field-line {
                width: 100%;
                flex: none;
            }
        }

        @media print {
            @page {
                size: A4 portrait;
                margin: 5mm;
            }

            /* Dashboard-এর পরের print rule-কে ছাড়িয়ে A4 পাতায় তিনটি receipt রাখে। */
            @page :first {
                size: A4 portrait;
                margin: 5mm;
            }

            header:not(.receipt-heading),
            .topbar,
            .sidebar-wrapper,
            .page-footer,
            .footer,
            .no-print {
                display: none !important;
            }

            .wrapper,
            .page-wrapper,
            .page-content {
                width: 100% !important;
                margin: 0 !important;
                padding: 0 !important;
                overflow: visible !important;
            }

            body {
                margin: 0 !important;
                background: #fff !important;
            }

            body:has(.money-receipt) {
                margin: 0 !important;
            }

            .money-receipt {
                display: flex !important;
                flex-direction: column;
                gap: 0;
                box-sizing: border-box;
                width: 100%;
                height: 94mm;
                max-width: none;
                margin: 0;
                padding: 3mm 6mm 2mm 8mm;
                border: 1px solid #d6e0e7;
                border-radius: 0;
                box-shadow: none;
                font-size: 8pt;
                print-color-adjust: exact;
                -webkit-print-color-adjust: exact;
                break-inside: avoid;
                page-break-inside: avoid;
            }

            .money-receipt:not(:last-of-type) {
                border-bottom: 1.5px dashed #536b78;
                position: relative;
            }

            .money-receipt:not(:last-of-type)::after {
                content: '';
                position: absolute;
                right: 0;
                bottom: -2mm;
                left: 0;
                border-bottom: 1px dashed #8a9aa3;
                pointer-events: none;
            }

            .receipt-heading {
                flex: 0 0 17mm;
                gap: 3mm;
                padding-bottom: 2mm;
            }

            .receipt-organization {
                gap: 2mm;
                font-size: 6.5pt;
            }

            .receipt-organization h2 {
                margin-bottom: .5mm;
                font-size: 9pt;
            }

            .receipt-title span {
                font-size: 6pt;
            }

            .receipt-title h1 {
                font-size: 12pt;
            }

            .receipt-meta {
                flex: 0 0 8mm;
                gap: 8mm;
                margin: 1mm 0;
            }

            .receipt-fields {
                flex: 0 0 19mm;
                gap: 0;
            }

            .write-line,
            .field-line {
                height: 6mm;
            }

            .receipt-payment-row {
                flex: 0 0 14mm;
                gap: 3mm;
                margin: 1mm 0;
            }

            .payment-options {
                gap: 1mm 2mm;
            }

            .payment-options .field-label {
                flex-basis: auto;
            }

            .receipt-amount {
                flex-basis: 42mm;
                min-height: 10mm;
                padding: 1mm 2mm;
            }

            .receipt-reference,
            .receipt-purpose {
                flex: 0 0 7mm;
                margin-top: 0;
            }

            .receipt-signatures {
                flex: 1 1 auto;
                align-items: flex-end;
                gap: 12mm;
                margin-top: 1mm;
                padding-bottom: 1mm;
                break-inside: avoid;
                page-break-inside: avoid;
            }

            .receipt-organization {
                max-width: 65%;
            }

            .receipt-organization img {
                max-width: 16mm;
                max-height: 12mm;
            }

            .receipt-heading,
            .receipt-meta,
            .receipt-fields,
            .receipt-payment-row,
            .receipt-reference,
            .receipt-purpose {
                break-inside: avoid;
                page-break-inside: avoid;
            }
        }
    </style>
@endsection
