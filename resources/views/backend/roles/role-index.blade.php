@extends('dashboard.dashboard')
@section('title')
    Roles & Permissions
@endsection

@section('content')
    <div class="page-wrapper">
        <div class="page-content">

            <!-- Breadcrumb -->
            <div class="mb-3 page-breadcrumb d-none d-sm-flex align-items-center">
                <div class="breadcrumb-title pe-3">User & Access</div>
                <div class="ps-3">
                    <nav aria-label="breadcrumb">
                        <ol class="p-0 mb-0 breadcrumb">
                            <li class="breadcrumb-item">
                                <a href="{{ route('dashboardbody') }}"><i class="bx bx-home-alt"></i></a>
                            </li>
                            <li class="breadcrumb-item">
                                <a href="javascript:;">Access Control</a>
                            </li>
                            <li class="breadcrumb-item active" aria-current="page">Roles & Permissions</li>
                        </ol>
                    </nav>
                </div>
                <div class="ms-auto">
                    <a href="{{ route('roles.create') }}" class="btn btn-primary px-3 shadow-sm">
                        <i class="bx bx-shield-plus me-1"></i> Add New Role
                    </a>
                </div>
            </div>
            <!-- End Breadcrumb -->

            <!-- Summary KPI Cards -->
            @php
                $totalRoles = $roles->count();
                $totalRolePermissions = $roles->sum(function($r) {
                    return $r->permissions ? $r->permissions->count() : 0;
                });
            @endphp
            <div class="row row-cols-1 row-cols-md-3 g-3 mb-4">
                <div class="col">
                    <div class="card radius-10 border-start border-0 border-3 border-primary shadow-sm mb-0">
                        <div class="card-body">
                            <div class="d-flex align-items-center">
                                <div>
                                    <p class="mb-0 text-secondary fw-semibold">Defined Roles</p>
                                    <h4 class="my-1 text-primary fw-bold">{{ $totalRoles }}</h4>
                                    <p class="mb-0 font-13 text-muted">System security roles</p>
                                </div>
                                <div class="widgets-icons-2 rounded-circle bg-light-primary text-primary ms-auto">
                                    <i class="bx bx-shield-quarter"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col">
                    <div class="card radius-10 border-start border-0 border-3 border-info shadow-sm mb-0">
                        <div class="card-body">
                            <div class="d-flex align-items-center">
                                <div>
                                    <p class="mb-0 text-secondary fw-semibold">Total Assignments</p>
                                    <h4 class="my-1 text-info fw-bold">{{ $totalRolePermissions }}</h4>
                                    <p class="mb-0 font-13 text-muted">Permissions assigned to roles</p>
                                </div>
                                <div class="widgets-icons-2 rounded-circle bg-light-info text-info ms-auto">
                                    <i class="bx bx-key"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col">
                    <div class="card radius-10 border-start border-0 border-3 border-success shadow-sm mb-0">
                        <div class="card-body">
                            <div class="d-flex align-items-center">
                                <div>
                                    <p class="mb-0 text-secondary fw-semibold">Access Security</p>
                                    <h4 class="my-1 text-success fw-bold">Active</h4>
                                    <p class="mb-0 font-13 text-muted">Spatie RBAC protection</p>
                                </div>
                                <div class="widgets-icons-2 rounded-circle bg-light-success text-success ms-auto">
                                    <i class="bx bx-check-shield"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <!-- End Summary Cards -->

            <!-- Role Table Card -->
            <div class="card radius-10 shadow-sm border-0">
                <div class="card-header bg-transparent border-bottom py-3 d-flex align-items-center justify-content-between">
                    <div>
                        <h5 class="mb-0 text-dark fw-bold">
                            <i class="bx bx-shield text-primary me-2"></i>Roles & Assigned Permissions
                        </h5>
                        <p class="text-muted small mb-0">Manage roles and authorize granular access rights</p>
                    </div>
                    <a href="{{ route('roles.create') }}" class="btn btn-sm btn-outline-primary">
                        <i class="bx bx-plus me-1"></i>New Role
                    </a>
                </div>

                <div class="card-body">
                    <div class="table-responsive">
                        <table id="myTable" class="table table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th class="text-center" style="width: 60px;">SL</th>
                                    <th style="width: 220px;">Role Name</th>
                                    <th>Assigned Permissions</th>
                                    <th class="text-center" style="width: 140px;">Total Perms</th>
                                    <th class="text-center" style="width: 160px;">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($roles as $key => $role)
                                    <tr>
                                        <td class="text-center text-muted fw-bold">{{ $key + 1 }}</td>
                                        <td>
                                            <div class="d-flex align-items-center">
                                                <div class="rounded-circle bg-light-primary text-primary d-flex align-items-center justify-content-center me-3"
                                                    style="width: 42px; height: 42px; font-weight: 700; font-size: 15px;">
                                                    <i class="bx bxs-user-badge fs-4"></i>
                                                </div>
                                                <div>
                                                    <h6 class="mb-0 fw-bold text-dark text-capitalize">{{ $role->name }}</h6>
                                                    <span class="text-muted small">ID: #ROL-{{ str_pad($role->id, 4, '0', STR_PAD_LEFT) }}</span>
                                                </div>
                                            </div>
                                        </td>
                                        <td>
                                            <div class="d-flex flex-wrap gap-1 align-items-center" style="max-height: 85px; overflow-y: auto;">
                                                @forelse ($role->permissions as $permission)
                                                    <span class="badge bg-light-primary text-primary border border-primary-subtle py-1 px-2 font-12 fw-medium">
                                                        <i class="bx bx-check me-1"></i>{{ $permission->name }}
                                                    </span>
                                                @empty
                                                    <span class="badge bg-light-warning text-warning py-1 px-2 font-12">
                                                        No permissions assigned
                                                    </span>
                                                @endforelse
                                            </div>
                                        </td>
                                        <td class="text-center">
                                            <span class="badge bg-light-success text-success px-3 py-2 rounded-pill font-13 fw-semibold">
                                                {{ $role->permissions->count() }} Perms
                                            </span>
                                        </td>
                                        <td class="text-center">
                                            <div class="d-inline-flex gap-2">
                                                <!-- View Role Modal Trigger -->
                                                <button type="button" class="btn btn-sm btn-outline-info"
                                                    data-bs-toggle="modal" data-bs-target="#viewRoleModal{{ $role->id }}"
                                                    title="View All Permissions">
                                                    <i class="bx bx-show"></i>
                                                </button>

                                                <!-- Edit Role -->
                                                <a href="{{ route('roles.edit', $role->id) }}"
                                                    class="btn btn-sm btn-outline-primary"
                                                    title="Edit Role">
                                                    <i class="bx bx-edit-alt"></i>
                                                </a>

                                                <!-- Delete Role -->
                                                <a href="{{ route('roles.destroy', $role->id) }}"
                                                    class="btn btn-sm btn-outline-danger"
                                                    onclick="return confirm('Are you sure you want to delete role: {{ $role->name }}?')"
                                                    title="Delete Role">
                                                    <i class="bx bx-trash"></i>
                                                </a>
                                            </div>

                                            <!-- Role Details Modal -->
                                            <div class="modal fade" id="viewRoleModal{{ $role->id }}" tabindex="-1" aria-hidden="true">
                                                <div class="modal-dialog modal-dialog-centered modal-lg">
                                                    <div class="modal-content text-start">
                                                        <div class="modal-header border-bottom">
                                                            <div class="d-flex align-items-center">
                                                                <div class="widgets-icons-2 rounded-circle bg-light-primary text-primary me-2">
                                                                    <i class="bx bx-shield-quarter"></i>
                                                                </div>
                                                                <div>
                                                                    <h5 class="modal-title fw-bold text-dark mb-0">Role: {{ ucfirst($role->name) }}</h5>
                                                                    <span class="text-muted small">Total {{ $role->permissions->count() }} Authorized Permissions</span>
                                                                </div>
                                                            </div>
                                                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                        </div>

                                                        <div class="modal-body p-4">
                                                            <div class="p-3 bg-light rounded mb-3">
                                                                <div class="row align-items-center">
                                                                    <div class="col-sm-6">
                                                                        <span class="text-muted small d-block">Role Name</span>
                                                                        <h5 class="fw-bold text-dark mb-0 text-capitalize">{{ $role->name }}</h5>
                                                                    </div>
                                                                    <div class="col-sm-6 text-sm-end mt-2 mt-sm-0">
                                                                        <span class="badge bg-primary px-3 py-2 font-13">
                                                                            {{ $role->permissions->count() }} Active Permissions
                                                                        </span>
                                                                    </div>
                                                                </div>
                                                            </div>

                                                            <h6 class="fw-bold text-secondary mb-3">
                                                                <i class="bx bx-key me-1"></i>Assigned Permission Keys:
                                                            </h6>
                                                            <div class="d-flex flex-wrap gap-2">
                                                                @forelse ($role->permissions as $permission)
                                                                    <div class="badge bg-light text-dark border p-2 font-13 fw-normal d-flex align-items-center">
                                                                        <i class="bx bx-check-circle text-success me-2 fs-6"></i>
                                                                        <span>{{ $permission->name }}</span>
                                                                    </div>
                                                                @empty
                                                                    <div class="alert alert-warning w-100 mb-0 py-2">
                                                                        <i class="bx bx-info-circle me-1"></i>No permissions currently assigned to this role.
                                                                    </div>
                                                                @endforelse
                                                            </div>
                                                        </div>

                                                        <div class="modal-footer border-top">
                                                            <a href="{{ route('roles.edit', $role->id) }}" class="btn btn-primary btn-sm">
                                                                <i class="bx bx-edit me-1"></i>Edit Permissions
                                                            </a>
                                                            <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Close</button>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                            <!-- End Role Modal -->
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="5" class="text-center py-4 text-muted">
                                            <i class="bx bx-info-circle fs-3 d-block mb-2"></i>
                                            No roles found. Click "Add New Role" to create one.
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            <!-- End Role Table Card -->

        </div>
    </div>
@endsection
