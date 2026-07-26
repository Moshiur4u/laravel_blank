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
                        <a href="{{ route('user.create') }}" class="btn btn-primary">Add New</a>
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
                                        <th>Photo</th>
                                        <th>Roles</th>
                                        <th>Email</th>
                                        <th>Action</th>
                                        <th>Status</th>
                                        <th>Remark</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <!-- এখানে আমরা ফর ইচ লুপ এর মাধ্যমে ইউজার দের নাম দেখাবো -->
                                    @foreach ($Users as $key => $User)
                                        <tr>
                                            <td>{{ $key + 1 }}</td>
                                            <td>{{ $User->name }}</td>
                                            <td>
                                                <!-- এখানে আমরা ইফ  কন্ডিশন লজিক ব্যবহার করে ব্লেডে ইমেজ দেখাবো -->
                                                @if ($User->image)
                                                    <img src="{{ asset('Users/' . $User->image) }}"alt="{{ $User->name }}"
                                                        class="img-thumbnail widgets-icons-2 msg-avatar">
                                                @else
                                                    <!-- যদি ইমেজ আপলোড করা না থাকে তাহলে ডিফল্ট ইমেজ দেখাবে -->
                                                    <img src="{{ asset('Users/Users.png') }}"
                                                        alt="{{ $User->name }}"class="img-thumbnail widgets-icons-2 msg-avatar" />
                                                @endif

                                            <td>
                                                <!-- এখানে আমরা ফর ইচ লুপ এর মাধ্যমে রোল নাম দেখাবো -->
                                                @foreach ($User->roles as $role)
                                                    <span class="badge bg-danger">{{ $role->name }}</span>
                                                @endforeach
                                            </td>
                                            <td>{{ $User->email }}</td>
                                            <td>
                                                <!-- এখানে আমরা বাটন ব্যবহার করে ইউজার দের ইনফরমেশন দেখাবো -->
                                                <a href="{{ route('user.edit', $User->id) }}"
                                                    class="btn btn-primary btn-small">Edit</a>

                                                {{-- <button type="submit" class="btn btn-danger btn-small">delete</button> --}}

                                                <a href="{{ route('user.destroy', $User->id) }}"
                                                    class="btn btn-danger btn-icon">Delete
                                                </a>
                                            </td>
                                            <td>
                                                <!-- স্ট্যাটাস বাটন  এখানে যখন রাউট বন্ধ থাকবে তখন এই বাটন কাজ করবে না
                                                                                 রাউট ব্যবহার করলে কন্ট্রলারে ফংশন ব্যবহার করে স্ট্যাটাস পরিবর্তন করা যাবে।
                                                                                  এখন শুধু রং পরিবর্তন হবে  -->
                                                {{-- <a href="{{ route('user.statusupdate', $User->id) }}"> --}}
                                                <a href="#" {{-- এখানে আমরা কন্ডিশন লজিক ব্যবহার করে বাটনে রং পরিবর্তন করবো  --}}
                                                    class = "btn btn-{{ $User->status == 1 ? 'success' : 'danger' }}">
                                                    {{ $User->status == 1 ? 'Active' : 'Inactive' }}</a>
                                            </td>
                                            <td>{{ $User->remark }}</td>
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
