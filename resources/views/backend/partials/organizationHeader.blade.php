@php
    $hasOrganizationDetails = $organization
        && ($organization->name || $organization->logo || $organization->phone || $organization->email || $organization->address);
@endphp

@if (isset($reportTitle))
    <div class="d-none d-print-flex justify-content-between align-items-start gap-3 mb-4 organization-report-header">
        <div class="d-flex align-items-center gap-3">
            @if ($hasOrganizationDetails && $organization->logo)
                <img src="{{ asset('uploads/organizations/' . $organization->logo) }}"
                    alt="{{ $organization->name }} logo"
                    style="max-width: 100px; max-height: 75px; object-fit: contain;">
            @endif
            @if ($hasOrganizationDetails)
                <div>
                    @if ($organization->name)
                        <h2 class="mb-1 fw-bold">{{ $organization->name }}</h2>
                    @endif
                    @if ($organization->phone)
                        <div>Phone: {{ $organization->phone }}</div>
                    @endif
                    @if ($organization->email)
                        <div>Email: {{ $organization->email }}</div>
                    @endif
                    @if ($organization->address)
                        <div>{{ $organization->address }}</div>
                    @endif
                </div>
            @endif
        </div>
        <div class="text-end">
            <h2 class="mb-1 fw-bold">{{ $reportTitle }}</h2>
            @if (!empty($reportSubtitle))
                <div>{{ $reportSubtitle }}</div>
            @endif
            @if (!empty($reportGeneratedAt))
                <div class="small text-muted">Report Generated: {{ $reportGeneratedAt }}</div>
            @endif
        </div>
    </div>
@elseif ($hasOrganizationDetails)
    {{-- সব invoice/report-এ একই প্রতিষ্ঠানের logo ও contact details দেখায়। --}}
    <div class="d-none d-print-flex align-items-center gap-3 mb-4 organization-report-header">
        @if ($organization->logo)
            <img src="{{ asset('uploads/organizations/' . $organization->logo) }}"
                alt="{{ $organization->name }} logo"
                style="max-width: 100px; max-height: 75px; object-fit: contain;">
        @endif
        <div>
            @if ($organization->name)
                <h2 class="mb-1 fw-bold">{{ $organization->name }}</h2>
            @endif
            @if ($organization->phone)
                <div>Phone: {{ $organization->phone }}</div>
            @endif
            @if ($organization->email)
                <div>Email: {{ $organization->email }}</div>
            @endif
            @if ($organization->address)
                <div>{{ $organization->address }}</div>
            @endif
        </div>
    </div>
@endif
