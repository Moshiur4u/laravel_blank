@extends('dashboard.dashboard')
@section('title')
    Edit Role
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
                                <a href="{{ route('roles.index') }}">Roles</a>
                            </li>
                            <li class="breadcrumb-item active" aria-current="page">Update Role</li>
                        </ol>
                    </nav>
                </div>
                <div class="ms-auto">
                    <a href="{{ route('roles.index') }}" class="btn btn-outline-secondary px-3 shadow-sm">
                        <i class="bx bx-arrow-back me-1"></i> Back to List
                    </a>
                </div>
            </div>
            <!-- End Breadcrumb -->

            <form action="{{ route('roles.update', $role->id) }}" method="POST">
                @csrf
                @method('PUT')

                <div class="row justify-content-center">
                    <div class="col-lg-10">
                        <!-- Role Info Card -->
                        <div class="card radius-10 border-0 shadow-sm mb-4">
                            <div class="card-header bg-transparent border-bottom py-3">
                                <div class="d-flex align-items-center justify-content-between">
                                    <div class="d-flex align-items-center">
                                        <div class="widgets-icons-2 rounded-circle bg-light-warning text-warning me-3">
                                            <i class="bx bx-edit-alt"></i>
                                        </div>
                                        <div>
                                            <h5 class="mb-0 text-dark fw-bold">Update Role Information</h5>
                                            <p class="text-muted small mb-0">Modify title and authorized access permissions</p>
                                        </div>
                                    </div>
                                    <span class="badge bg-light-primary text-primary px-3 py-2 rounded-pill font-13">
                                        Role ID: #ROL-{{ str_pad($role->id, 4, '0', STR_PAD_LEFT) }}
                                    </span>
                                </div>
                            </div>

                            <div class="card-body p-4">
                                <div class="mb-3">
                                    <label for="name" class="form-label fw-semibold text-secondary">
                                        Role Name <span class="text-danger">*</span>
                                    </label>
                                    <div class="input-group">
                                        <span class="input-group-text bg-light text-secondary border-end-0">
                                            <i class="bx bx-user-badge"></i>
                                        </span>
                                        <input type="text"
                                            name="name"
                                            id="name"
                                            class="form-control border-start-0 @error('name') is-invalid @enderror"
                                            placeholder="Enter Your Role Name"
                                            value="{{ old('name', $role->name) }}"
                                            required>
                                    </div>
                                    @error('name')
                                        <div class="text-danger small mt-1">
                                            <i class="bx bx-error-circle me-1"></i>{{ $message }}
                                        </div>
                                    @enderror
                                    <div class="form-text text-muted">
                                        Current assigned title: <span class="fw-medium text-dark">{{ $role->name }}</span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Permissions Card -->
                        <div class="card radius-10 border-0 shadow-sm mb-4">
                            <div class="card-header bg-transparent border-bottom py-3">
                                <div class="d-flex flex-wrap align-items-center justify-content-between gap-2">
                                    <div class="d-flex align-items-center">
                                        <div class="widgets-icons-2 rounded-circle bg-light-info text-info me-3">
                                            <i class="bx bx-key"></i>
                                        </div>
                                        <div>
                                            <h5 class="mb-0 text-dark fw-bold">Manage Authorized Permissions</h5>
                                            <p class="text-muted small mb-0">Check or uncheck privileges for this role</p>
                                        </div>
                                    </div>

                                    <div class="d-flex align-items-center gap-2">
                                        <div class="form-check form-switch m-0 d-flex align-items-center gap-2">
                                            <input class="form-check-input" type="checkbox" id="selectAll" style="cursor: pointer; width: 2.5em; height: 1.3em;">
                                            <label class="form-check-label fw-bold text-dark user-select-none" for="selectAll" style="cursor: pointer;">
                                                Select All Permissions
                                            </label>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="card-body p-4">
                                @error('permission')
                                    <div class="alert alert-danger d-flex align-items-center mb-3 py-2">
                                        <i class="bx bx-error-circle fs-5 me-2"></i>
                                        <div>Please select at least one permission for this role.</div>
                                    </div>
                                @enderror

                                <!-- Search Filter for Permissions -->
                                <div class="mb-3">
                                    <div class="input-group input-group-sm">
                                        <span class="input-group-text bg-light text-muted border-end-0">
                                            <i class="bx bx-search"></i>
                                        </span>
                                        <input type="text"
                                            id="searchPermission"
                                            class="form-control border-start-0"
                                            placeholder="Quick filter permissions (e.g. product, user, role, edit)...">
                                    </div>
                                </div>

                                <!-- Permission Items Grid -->
                                <div class="row g-3" id="permissionGrid">
                                    @forelse ($permissions as $permission)
                                        @php
                                            $isChecked = in_array($permission->id, $rolewithpermission);
                                        @endphp
                                        <div class="col-md-6 col-xl-4 permission-item" data-name="{{ strtolower($permission->name) }}">
                                            <div class="p-3 border rounded h-100 {{ $isChecked ? 'bg-light-primary border-primary-subtle' : 'bg-light-subtle' }} d-flex align-items-center hover-shadow transition-all"
                                                style="cursor: pointer; transition: 0.2s;"
                                                onclick="toggleCheckbox('perm_{{ $permission->id }}')">
                                                <div class="form-check m-0 d-flex align-items-center">
                                                    <input type="checkbox"
                                                        name="permission[]"
                                                        value="{{ $permission->id }}"
                                                        id="perm_{{ $permission->id }}"
                                                        class="form-check-input permission-checkbox me-2"
                                                        onclick="event.stopPropagation();"
                                                        {{ $isChecked ? 'checked' : '' }}
                                                        style="cursor: pointer;">
                                                    <label class="form-check-label text-dark fw-medium small mb-0 user-select-none"
                                                        for="perm_{{ $permission->id }}"
                                                        style="cursor: pointer;">
                                                        {{ $permission->name }}
                                                    </label>
                                                </div>
                                            </div>
                                        </div>
                                    @empty
                                        <div class="col-12 text-center py-4 text-muted">
                                            <i class="bx bx-info-circle fs-3 d-block mb-2"></i>
                                            No permissions defined in the database.
                                        </div>
                                    @endforelse
                                </div>
                            </div>

                            <div class="card-footer bg-transparent border-top py-3 d-flex align-items-center justify-content-between">
                                <span class="text-muted small" id="selectedCount">
                                    {{ count($rolewithpermission) }} of {{ count($permissions) }} permissions selected
                                </span>
                                <div class="d-flex gap-2">
                                    <a href="{{ route('roles.index') }}" class="btn btn-light px-4">
                                        <i class="bx bx-x me-1"></i>Cancel
                                    </a>
                                    <button type="submit" class="btn btn-primary px-4 shadow-sm">
                                        <i class="bx bx-save me-1"></i>Save Changes
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </form>

        </div>
    </div>

    <!-- JavaScript for Select All, Click Card Toggle, and Filter -->
    <script>
        function toggleCheckbox(id) {
            const cb = document.getElementById(id);
            if (cb) {
                cb.checked = !cb.checked;
                updateSelectedCount();
            }
        }

        function updateSelectedCount() {
            const allCheckboxes = document.querySelectorAll('.permission-checkbox');
            const checkedCount = document.querySelectorAll('.permission-checkbox:checked').length;
            const counter = document.getElementById('selectedCount');
            if (counter) {
                counter.innerText = `${checkedCount} of ${allCheckboxes.length} permissions selected`;
            }
            const selectAll = document.getElementById('selectAll');
            if (selectAll) {
                selectAll.checked = (allCheckboxes.length > 0 && checkedCount === allCheckboxes.length);
            }
        }

        document.addEventListener('DOMContentLoaded', function() {
            // Select All Toggle
            const selectAll = document.getElementById('selectAll');
            if (selectAll) {
                selectAll.addEventListener('change', function() {
                    const checkboxes = document.querySelectorAll('.permission-checkbox');
                    checkboxes.forEach((cb) => {
                        cb.checked = selectAll.checked;
                    });
                    updateSelectedCount();
                });
            }

            // Update on individual checkbox change
            document.querySelectorAll('.permission-checkbox').forEach(cb => {
                cb.addEventListener('change', updateSelectedCount);
            });

            // Search Filter
            const searchInput = document.getElementById('searchPermission');
            if (searchInput) {
                searchInput.addEventListener('input', function() {
                    const query = this.value.toLowerCase().trim();
                    document.querySelectorAll('.permission-item').forEach(item => {
                        const name = item.getAttribute('data-name');
                        if (name.includes(query)) {
                            item.style.display = '';
                        } else {
                            item.style.display = 'none';
                        }
                    });
                });
            }

            updateSelectedCount();
        });
    </script>
@endsection
