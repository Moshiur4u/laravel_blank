@extends('dashboard.dashboard')

@section('title', 'User Activity Log')

@section('content')
    <style>
        @media print {
            @page {
                size: landscape;
                margin: 34mm 12mm 15mm;
            }

            body {
                background: #fff !important;
            }

            .sidebar-wrapper,
            header,
            .page-footer,
            .back-to-top,
            .overlay,
            .no-print {
                display: none !important;
            }

            .wrapper,
            .page-wrapper,
            .page-content {
                margin: 0 !important;
                padding: 0 !important;
                width: 100% !important;
            }

            .activity-report {
                border: 0 !important;
                box-shadow: none !important;
            }

            .table-responsive {
                overflow: visible !important;
            }

            .activity-report table {
                font-size: 9pt;
            }

            .activity-report th,
            .activity-report td {
                padding: 5px !important;
            }

            .activity-pagination {
                display: none !important;
            }

            details>pre {
                display: block !important;
            }
        }
    </style>

    <div class="page-wrapper">
        <div class="page-content">
            <div class="flex-wrap gap-2 mb-3 d-flex align-items-center justify-content-between no-print">
                <div>
                    <h5 class="mb-1">User Activity Log</h5>
                    <p class="mb-0 text-muted">Login history and recorded system changes</p>
                </div>
                <div class="flex-wrap gap-2 d-flex">
                    <button type="button" class="btn btn-outline-primary" onclick="window.print()">
                        <i class="bx bx-printer me-1"></i>Print
                    </button>
                    <button type="button" class="btn btn-primary" onclick="window.print()"
                        title="Choose Save as PDF in the print dialog">
                        <i class="bx bxs-file-pdf me-1"></i>Save as PDF
                    </button>
                </div>
            </div>

            <div class="mb-3">
                @include('backend.partials.organizationHeader')
                <h5 class="mb-1">
                    {{ $selectedUser ? 'Activity history: ' . $selectedUser->name : 'Activity history: All users' }}</h5>
                @if ($selectedUser?->email)
                    <div class="text-muted">{{ $selectedUser->email }}</div>
                @endif
                @if (($filters['from'] ?? null) || ($filters['to'] ?? null))
                    <div class="text-muted small">
                        Period: {{ $filters['from'] ?? 'Any date' }} to {{ $filters['to'] ?? 'Any date' }}
                    </div>
                @endif
                <div class="text-muted small">Generated: {{ now()->format('Y-m-d H:i:s') }}</div>
            </div>

            <div class="border-0 shadow-sm card radius-10 no-print">
                <div class="card-body">
                    <form method="GET" action="{{ route('user-activity-logs.index') }}"
                        class="mb-4 row g-3 align-items-end">
                        <div class="col-12 col-sm-6 col-lg-2">
                            <label for="from" class="form-label">From date</label>
                            <input type="date" id="from" name="from" value="{{ $filters['from'] ?? '' }}"
                                class="form-control">
                        </div>
                        <div class="col-12 col-sm-6 col-lg-2">
                            <label for="to" class="form-label">To date</label>
                            <input type="date" id="to" name="to" value="{{ $filters['to'] ?? '' }}"
                                class="form-control">
                        </div>
                        <div class="col-12 col-sm-6 col-lg-3">
                            <label for="user_id" class="form-label">User name</label>
                            <select id="user_id" name="user_id" class="form-select">
                                <option value="">All users</option>
                                @foreach ($users as $user)
                                    <option value="{{ $user->id }}" @selected((string) ($filters['user_id'] ?? '') === (string) $user->id)>
                                        {{ $user->name }}{{ $user->email ? ' - ' . $user->email : '' }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-12 col-sm-6 col-lg-3">
                            <label for="event" class="form-label">Activity</label>
                            <select id="event" name="event" class="form-select">
                                <option value="">All activities</option>
                                @foreach (['login' => 'Login', 'logout' => 'Logout', 'create' => 'Create', 'update' => 'Update', 'delete' => 'Delete'] as $value => $label)
                                    <option value="{{ $value }}" @selected(($filters['event'] ?? '') === $value)>{{ $label }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="gap-2 col-12 col-lg-2 d-flex">
                            <button type="submit" class="btn btn-primary flex-grow-1">Filter</button>
                            <a href="{{ route('user-activity-logs.index') }}" class="btn btn-outline-secondary">Reset</a>
                        </div>
                    </form>
                </div>
            </div>

            <div class="border-0 shadow-sm card radius-10 activity-report">
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table align-middle table-hover">
                            <thead class="table-light">
                                <tr>
                                    <th>Date and time</th>
                                    <th>User</th>
                                    <th>Activity</th>
                                    <th>View recorded details</th>
                                    <th>IP address</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($logs as $log)
                                    <tr>
                                        <td class="text-nowrap">{{ $log->created_at->format('Y-m-d H:i:s') }}</td>
                                        <td>
                                            <div class="fw-semibold">{{ $log->user_name }}</div>
                                            <small class="text-muted text-wrap">{{ $log->user_email }}</small>
                                        </td>
                                        <td><span class="border badge bg-light text-dark">{{ ucfirst($log->event) }}</span>
                                        </td>
                                        <td>
                                            <div>{{ $log->description }}</div>
                                            @if ($log->subject_id)
                                                <small class="text-muted">{{ $log->subject_type }}
                                                    #{{ $log->subject_id }}</small>
                                            @endif
                                            @if ($log->properties)
                                                {{-- <div class="mt-1 small text-primary">details</div> --}}
                                                <div class="mt-1 table-responsive">
                                                    <table class="table mb-0 table-sm table-bordered">
                                                        <tbody>
                                                            @foreach ($log->properties as $field => $value)
                                                                @continue(is_null($value) || $value === '' || $value === [])
                                                                <tr>
                                                                    <td class="text-break fw-semibold " style="width: 35%;">
                                                                        {{ $field }}</td>
                                                                    <td class="text-wrap">
                                                                        @if (is_array($value))
                                                                            {{ json_encode($value, JSON_UNESCAPED_UNICODE) }}
                                                                        @elseif (is_bool($value))
                                                                            {{ $value ? 'true' : 'false' }}
                                                                        @else
                                                                            {{ $value }}
                                                                        @endif
                                                                    </td>
                                                                </tr>
                                                            @endforeach
                                                        </tbody>
                                                    </table>
                                                </div>
                                            @endif
                                        </td>
                                        <td>{{ $log->ip_address ?: '—' }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="5" class="py-4 text-center text-muted">No activity found for these
                                            filters.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    <div class="mt-3 activity-pagination">{{ $logs->onEachSide(1)->links('pagination::bootstrap-5') }}
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
