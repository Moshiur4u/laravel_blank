@extends('dashboard.dashboard')
@section('content')
    <!--start page wrapper -->
    <div class="page-wrapper">
        <div class="page-content">
            <!-- start-content -->

            <!--breadcrumb-->
            <div class="mb-3 page-breadcrumb d-none d-sm-flex align-items-center">
                <div class="breadcrumb-title pe-3">Tables</div>
                <div class="ps-3">
                    <nav aria-label="breadcrumb">
                        <ol class="p-0 mb-0 breadcrumb">
                            <li class="breadcrumb-item"><a href="javascript:;"><i class="bx bx-home-alt"></i></a>
                            </li>
                            <li class="breadcrumb-item active" aria-current="page">Roles Table</li>
                        </ol>
                    </nav>
                </div>
                <div class="ms-auto">
                    <div class="btn-group">
                        <a href="{{ route('roles.create') }}" class="btn btn-primary">Add New</a>
                    </div>
                </div>
            </div>
            <!--end breadcrumb-->
            <h6 class="mb-0 text-uppercase">Role and Permissions</h6>
            <hr>

            <div class="card">
                <div class="card-body">
                    <div class="table-responsive">
                        <div id="example_wrapper" class="dataTables_wrapper dt-bootstrap5">
                            <table id="myTable" class="table display table-striped">
                                <thead>
                                    <tr>
                                        <th>No</th>
                                        <th>Name</th>
                                        <th>Action</th>
                                        <th>Permissions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($roles as $key => $role)
                                        <tr>
                                            <td>{{ $key + 1 }}</td>
                                            <td>{{ $role->name }}</td>
                                            <td class="gap-2 d-flex">

                                                <a href="{{ route('roles.edit', $role->id) }}"
                                                    class="btn btn-primary btn-small">edit</a>

                                                {{-- <button type="submit" class="btn btn-danger btn-small">delete</button> --}}

                                                <a href="{{ route('roles.destroy', $role->id) }}"
                                                    class="btn btn-danger btn-icon">Delete
                                                </a>
                                            </td>
                                            <td>
                                                {{-- এখানে রোল এর সাথে পারমিশন দেখানো হলো --}}
                                                @foreach ($role->permissions as $permission)
                                                {{-- এখানে ব্যাডজ আকারে পারমিশনগুলো  নাম দেখানো হলো  --}}
                                                    <span class="badge bg-danger">{{ $permission->name }}</span>
                                                    {{-- <br> --}}
                                                @endforeach
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
