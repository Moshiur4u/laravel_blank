@extends('dashboard.dashboard')
@section('title')
    Employee Details - {{ $user->name ?? $Users->name }}
@endsection
@section('content')
    <!--start page wrapper -->
    <div class="page-wrapper">
        <div class="page-content">
            @php
                $employee = $user ?? $Users;
            @endphp

            <!--breadcrumb-->
            <div class="mb-3 page-breadcrumb d-none d-sm-flex align-items-center">
                <div class="breadcrumb-title pe-3">Employee</div>
                <div class="ps-3">
                    <nav aria-label="breadcrumb">
                        <ol class="p-0 mb-0 breadcrumb">
                            <li class="breadcrumb-item"><a href="{{ route('dashboardbody') }}"><i class="bx bx-home-alt"></i></a></li>
                            <li class="breadcrumb-item"><a href="{{ route('user.index') }}">Employee List</a></li>
                            <li class="breadcrumb-item active" aria-current="page">Employee Details</li>
                        </ol>
                    </nav>
                </div>
                <div class="ms-auto">
                    <div class="gap-2 btn-group">
                        <a href="{{ route('user.index') }}" class="btn btn-secondary">
                            <i class="bx bx-arrow-back me-1"></i> Back to List
                        </a>
                        <a href="{{ route('user.edit', $employee->id) }}" class="btn btn-primary">
                            <i class="bx bx-edit me-1"></i> Edit Profile
                        </a>
                    </div>
                </div>
            </div>
            <!--end breadcrumb-->

            <div class="container-fluid">
                <div class="main-body">
                    <div class="row">
                        <!-- Left Column: Employee Profile Card -->
                        <div class="col-lg-4">
                            <div class="shadow-sm card">
                                <div class="card-body">
                                    <div class="text-center d-flex flex-column align-items-center">
                                        <div class="mb-3 position-relative">
                                            @if ($employee->image)
                                                <img src="{{ asset('uploads/users/' . $employee->image) }}"
                                                     alt="{{ $employee->name }}"
                                                     class="p-1 rounded-circle bg-primary"
                                                     width="120" height="120"
                                                     style="object-fit: cover;">
                                            @else
                                                <img src="{{ asset('Users/Users.png') }}"
                                                     alt="{{ $employee->name }}"
                                                     class="p-1 rounded-circle bg-secondary"
                                                     width="120" height="120"
                                                     style="object-fit: cover;">
                                            @endif
                                            <span class="position-absolute bottom-0 end-0 p-2 border border-light rounded-circle {{ $employee->status == 1 ? 'bg-success' : 'bg-danger' }}"
                                                  title="{{ $employee->status == 1 ? 'Active' : 'Inactive' }}">
                                                <span class="visually-hidden">Status</span>
                                            </span>
                                        </div>

                                        <div class="mt-2 text-center">
                                            <h4 class="mb-1">{{ $employee->name }}</h4>
                                            <p class="mb-2 text-secondary">{{ $employee->email }}</p>

                                            <div class="mb-3">
                                                @forelse ($employee->roles as $role)
                                                    <span class="badge bg-primary fs-6 px-3 py-1">{{ $role->name }}</span>
                                                @empty
                                                    <span class="badge bg-secondary fs-6 px-3 py-1">No Role</span>
                                                @endforelse
                                            </div>

                                            <div>
                                                @if ($employee->status == 1)
                                                    <span class="badge bg-light-success text-success border border-success px-3 py-1">
                                                        <i class="bx bxs-circle me-1 font-10"></i>Active Account
                                                    </span>
                                                @else
                                                    <span class="badge bg-light-danger text-danger border border-danger px-3 py-1">
                                                        <i class="bx bxs-circle me-1 font-10"></i>Inactive Account
                                                    </span>
                                                @endif
                                            </div>
                                        </div>
                                    </div>

                                    <hr class="my-4">

                                    <ul class="list-group list-group-flush">
                                        <li class="px-0 list-group-item d-flex justify-content-between align-items-center bg-transparent">
                                            <span class="text-secondary"><i class="bx bx-id-card me-2 font-18 text-primary"></i>Employee ID</span>
                                            <span class="fw-bold">#{{ str_pad($employee->id, 4, '0', STR_PAD_LEFT) }}</span>
                                        </li>
                                        <li class="px-0 list-group-item d-flex justify-content-between align-items-center bg-transparent">
                                            <span class="text-secondary"><i class="bx bx-envelope me-2 font-18 text-primary"></i>Email</span>
                                            <span class="text-truncate" style="max-width: 200px;">{{ $employee->email }}</span>
                                        </li>
                                        <li class="px-0 list-group-item d-flex justify-content-between align-items-center bg-transparent">
                                            <span class="text-secondary"><i class="bx bx-calendar me-2 font-18 text-primary"></i>Joined Date</span>
                                            <span>{{ $employee->created_at ? $employee->created_at->format('d M, Y') : 'N/A' }}</span>
                                        </li>
                                        <li class="px-0 list-group-item d-flex justify-content-between align-items-center bg-transparent">
                                            <span class="text-secondary"><i class="bx bx-time-five me-2 font-18 text-primary"></i>Last Updated</span>
                                            <span>{{ $employee->updated_at ? $employee->updated_at->diffForHumans() : 'N/A' }}</span>
                                        </li>
                                    </ul>

                                    <div class="mt-4 d-grid gap-2">
                                        <a href="{{ route('user.statusupdate', $employee->id) }}"
                                           class="btn btn-{{ $employee->status == 1 ? 'outline-danger' : 'outline-success' }}">
                                            <i class="bx bx-power-off me-1"></i>
                                            {{ $employee->status == 1 ? 'Deactivate Employee' : 'Activate Employee' }}
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Right Column: Employee Details & Roles/Permissions -->
                        <div class="col-lg-8">
                            <!-- Basic Information Card -->
                            <div class="shadow-sm card mb-4">
                                <div class="card-header bg-transparent border-bottom">
                                    <h5 class="mb-0 text-primary py-1">
                                        <i class="bx bx-user me-2"></i> Employee Information
                                    </h5>
                                </div>
                                <div class="card-body">
                                    <div class="row mb-3">
                                        <div class="col-sm-4 text-secondary">
                                            <strong>Full Name</strong>
                                        </div>
                                        <div class="col-sm-8 text-dark fw-semibold">
                                            {{ $employee->name }}
                                        </div>
                                    </div>
                                    <div class="row mb-3">
                                        <div class="col-sm-4 text-secondary">
                                            <strong>Email Address</strong>
                                        </div>
                                        <div class="col-sm-8 text-dark">
                                            {{ $employee->email }}
                                            @if ($employee->email_verified_at)
                                                <span class="badge bg-success ms-2 font-12"><i class="bx bx-check"></i> Verified</span>
                                            @else
                                                <span class="badge bg-light-warning text-warning ms-2 font-12">Unverified</span>
                                            @endif
                                        </div>
                                    </div>
                                    <div class="row mb-3">
                                        <div class="col-sm-4 text-secondary">
                                            <strong>Designation / Role</strong>
                                        </div>
                                        <div class="col-sm-8">
                                            @forelse ($employee->roles as $role)
                                                <span class="badge bg-danger me-1">{{ $role->name }}</span>
                                            @empty
                                                <span class="text-muted">No roles assigned</span>
                                            @endforelse
                                        </div>
                                    </div>
                                    <div class="row mb-3">
                                        <div class="col-sm-4 text-secondary">
                                            <strong>Account Status</strong>
                                        </div>
                                        <div class="col-sm-8">
                                            @if ($employee->status == 1)
                                                <span class="badge bg-success px-3 py-1">Active</span>
                                            @else
                                                <span class="badge bg-danger px-3 py-1">Inactive</span>
                                            @endif
                                        </div>
                                    </div>
                                    <div class="row mb-3">
                                        <div class="col-sm-4 text-secondary">
                                            <strong>Photo File</strong>
                                        </div>
                                        <div class="col-sm-8 text-dark">
                                            {{ $employee->image ? $employee->image : 'No photo uploaded' }}
                                        </div>
                                    </div>
                                    <div class="row mb-3">
                                        <div class="col-sm-4 text-secondary">
                                            <strong>Remark / Note</strong>
                                        </div>
                                        <div class="col-sm-8 text-dark">
                                            @if ($employee->remark)
                                                <div class="p-3 bg-light rounded border">
                                                    {{ $employee->remark }}
                                                </div>
                                            @else
                                                <span class="text-muted fst-italic">No remarks available.</span>
                                            @endif
                                        </div>
                                    </div>
                                    <div class="row mb-3">
                                        <div class="col-sm-4 text-secondary">
                                            <strong>Member Since</strong>
                                        </div>
                                        <div class="col-sm-8 text-dark">
                                            {{ $employee->created_at ? $employee->created_at->format('F d, Y \a\t h:i A') : 'N/A' }}
                                        </div>
                                    </div>
                                    <div class="row">
                                        <div class="col-sm-4 text-secondary">
                                            <strong>Last Profile Update</strong>
                                        </div>
                                        <div class="col-sm-8 text-dark">
                                            {{ $employee->updated_at ? $employee->updated_at->format('F d, Y \a\t h:i A') : 'N/A' }}
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Roles & Permissions Card -->
                            <div class="shadow-sm card">
                                <div class="card-header bg-transparent border-bottom">
                                    <h5 class="mb-0 text-primary py-1">
                                        <i class="bx bx-shield-quarter me-2"></i> Roles & Assigned Permissions
                                    </h5>
                                </div>
                                <div class="card-body">
                                    @forelse ($employee->roles as $role)
                                        <div class="mb-4">
                                            <div class="d-flex align-items-center mb-2">
                                                <h6 class="mb-0 text-dark fw-bold">
                                                    <i class="bx bx-badge-check text-primary me-1"></i> Role: {{ $role->name }}
                                                </h6>
                                                <span class="badge bg-secondary ms-2">{{ $role->permissions->count() }} Permissions</span>
                                            </div>

                                            <div class="d-flex flex-wrap gap-2 pt-2">
                                                @forelse ($role->permissions as $permission)
                                                    <span class="badge bg-light text-dark border px-2 py-1">
                                                        <i class="bx bx-check text-success me-1"></i>{{ $permission->name }}
                                                    </span>
                                                @empty
                                                    <span class="text-muted small">No specific permissions attached to this role.</span>
                                                @endforelse
                                            </div>
                                        </div>
                                        @if (!$loop->last)
                                            <hr>
                                        @endif
                                    @empty
                                        <div class="text-center py-4">
                                            <i class="bx bx-shield-x text-muted font-48"></i>
                                            <p class="text-muted mt-2 mb-0">No roles or permissions assigned to this employee yet.</p>
                                            <a href="{{ route('user.edit', $employee->id) }}" class="btn btn-outline-primary btn-sm mt-3">
                                                <i class="bx bx-plus me-1"></i> Assign Role Now
                                            </a>
                                        </div>
                                    @endforelse
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </div>
    <!--end page wrapper -->
@endsection
