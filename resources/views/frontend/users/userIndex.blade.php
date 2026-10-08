@extends('dashboard.dashboard')
@section('title')
    Employee & User List
@endsection

@section('content')
    <!--start page wrapper -->
    <div class="page-wrapper">
        <div class="page-content">

            <!--breadcrumb-->
            <div class="mb-3 page-breadcrumb d-none d-sm-flex align-items-center">
                <div class="breadcrumb-title pe-3">Employees</div>
                <div class="ps-3">
                    <nav aria-label="breadcrumb">
                        <ol class="p-0 mb-0 breadcrumb">
                            <li class="breadcrumb-item"><a href="javascript:;"><i class="bx bx-home-alt"></i></a></li>
                            <li class="breadcrumb-item active" aria-current="page">Employee & User List</li>
                        </ol>
                    </nav>
                </div>
                <div class="ms-auto">
                    <div class="btn-group">
                        <a href="{{ route('user.create') }}" class="btn btn-primary">
                            <i class="bx bx-plus-medical me-1"></i> Add New Employee
                        </a>
                    </div>
                </div>
            </div>
            <!--end breadcrumb-->

            {{-- Summary Cards --}}
            @php
                $totalUsers = $Users->count();
                $activeUsers = $Users->where('status', 1)->count();
                $inactiveUsers = $Users->where('status', 0)->count();
            @endphp
            <div class="row row-cols-1 row-cols-md-3 g-3 mb-3">
                <div class="col">
                    <div class="card radius-10 border-start border-0 border-3 border-primary mb-0">
                        <div class="card-body">
                            <div class="d-flex align-items-center">
                                <div>
                                    <p class="mb-0 text-secondary">Total Employees</p>
                                    <h4 class="my-1 text-primary">{{ $totalUsers }}</h4>
                                </div>
                                <div class="widgets-icons-2 rounded-circle bg-light-primary text-primary ms-auto">
                                    <i class="bx bxs-group"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col">
                    <div class="card radius-10 border-start border-0 border-3 border-success mb-0">
                        <div class="card-body">
                            <div class="d-flex align-items-center">
                                <div>
                                    <p class="mb-0 text-secondary">Active Accounts</p>
                                    <h4 class="my-1 text-success">{{ $activeUsers }}</h4>
                                </div>
                                <div class="widgets-icons-2 rounded-circle bg-light-success text-success ms-auto">
                                    <i class="bx bx-user-check"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col">
                    <div class="card radius-10 border-start border-0 border-3 border-danger mb-0">
                        <div class="card-body">
                            <div class="d-flex align-items-center">
                                <div>
                                    <p class="mb-0 text-secondary">Inactive Accounts</p>
                                    <h4 class="my-1 text-danger">{{ $inactiveUsers }}</h4>
                                </div>
                                <div class="widgets-icons-2 rounded-circle bg-light-danger text-danger ms-auto">
                                    <i class="bx bx-user-x"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card border-0 shadow-sm">
                <div class="card-header bg-transparent d-flex align-items-center justify-content-between py-3">
                    <h6 class="mb-0 text-uppercase fw-bold"><i class="bx bx-user-pin me-1"></i> All Employee & User Accounts</h6>
                    <span class="badge bg-primary">{{ $totalUsers }} Total</span>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <div id="example_wrapper" class="dataTables_wrapper dt-bootstrap5">
                            <table id="myTable" class="table display table-striped align-middle table-hover">
                                <thead class="table-light">
                                    <tr>
                                        <th>No</th>
                                        <th>Photo</th>
                                        <th>Name</th>
                                        <th>Roles</th>
                                        <th>Email</th>
                                        <th>Status</th>
                                        <th>Remark</th>
                                        <th class="text-center">Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($Users as $key => $User)
                                        <tr>
                                            <td>{{ $key + 1 }}</td>
                                            <td>
                                                @if ($User->image && file_exists(public_path('uploads/users/' . $User->image)))
                                                    <img src="{{ asset('uploads/users/' . $User->image) }}"
                                                        alt="{{ $User->name }}" class="product-img-2"
                                                        style="width: 45px; height: 45px; border-radius: 50%; object-fit: cover;" />
                                                @else
                                                    @php
                                                        $colors = [
                                                            '#0d6efd',
                                                            '#6f42c1',
                                                            '#d63384',
                                                            '#dc3545',
                                                            '#fd7e14',
                                                            '#198754',
                                                            '#0dcaf0',
                                                            '#6610f2',
                                                        ];
                                                        $colorIndex = $User->id % count($colors);
                                                    @endphp
                                                    <div
                                                        style="width: 45px; height: 45px; border-radius: 50%; background: {{ $colors[$colorIndex] }}; display: flex; align-items: center; justify-content: center; color: #fff; font-weight: 700; font-size: 16px; text-transform: uppercase;">
                                                        {{ strtoupper(substr($User->name, 0, 2)) }}
                                                    </div>
                                                @endif
                                            </td>
                                            <td>
                                                <h6 class="mb-0 fw-bold">{{ $User->name }}</h6>
                                            </td>
                                            <td>
                                                @forelse ($User->roles as $role)
                                                    <span class="badge bg-primary text-uppercase">{{ $role->name }}</span>
                                                @empty
                                                    <span class="badge bg-secondary">No Role</span>
                                                @endforelse
                                            </td>
                                            <td>
                                                <i class="bx bx-envelope text-muted me-1"></i>{{ $User->email }}
                                            </td>
                                            <td>
                                                <a href="{{ route('user.statusupdate', $User->id) }}"
                                                    class="badge {{ $User->status == 1 ? 'bg-success' : 'bg-danger' }} text-decoration-none px-2 py-1"
                                                    title="Click to toggle status">
                                                    <i class="bx {{ $User->status == 1 ? 'bx-check-circle' : 'bx-x-circle' }} me-1"></i>
                                                    {{ $User->status == 1 ? 'Active' : 'Inactive' }}
                                                </a>
                                            </td>
                                            <td>
                                                <span class="text-muted small">{{ $User->remark ?? '-' }}</span>
                                            </td>
                                            <td class="text-center">
                                                <div class="d-flex gap-1 justify-content-center">
                                                    <a href="{{ route('user.show', $User->id) }}"
                                                        class="btn btn-sm btn-info text-white" title="View Profile">
                                                        <i class="bx bx-show"></i>
                                                    </a>
                                                    <a href="{{ route('user.edit', $User->id) }}"
                                                        class="btn btn-sm btn-primary" title="Edit Employee">
                                                        <i class="bx bx-edit"></i>
                                                    </a>
                                                    <a href="{{ route('user.destroy', $User->id) }}"
                                                        class="btn btn-sm btn-danger"
                                                        onclick="return confirm('Are you sure you want to delete this user?')"
                                                        title="Delete Employee">
                                                        <i class="bx bx-trash"></i>
                                                    </a>
                                                </div>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
            <!-- end-content -->
        </div>
    </div>
    <!--end page wrapper -->
@endsection
