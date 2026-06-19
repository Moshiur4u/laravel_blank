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
                            <li class="breadcrumb-item active" aria-current="page">Create Role</li>
                        </ol>
                    </nav>
                </div>
                <div class="ms-auto">
                    <div class="btn-group">
                        <a href="roles-index.html" class="btn btn-primary">All Roles</a>
                    </div>
                </div>
            </div>
            <!--end breadcrumb-->
            <div class="row">
                <div class="mx-auto col-lg-8">
                    <div class="card">
                        <div class="p-4 card-body">
                            <h5 class="mb-4">Update Role</h5>
                            <form action="{{ route('roles.update', $role->id) }}" method="post">
                                @csrf
                                @method('PUT')
                                <div class="mb-3 form-group">
                                    <label for="name" class="col-sm-3 col-form-label">Role Name</label>
                                    <div class="col-sm-9">
                                        <input type="text" class="form-control" id="name" name="name"
                                            value="{{ $role->name }}" placeholder="Enter Your Role Name">
                                    </div>
                                </div>
                                <div class="mb-3 form-group">
                                    <div class="col-sm-9 form-check">
                                        @foreach ($permissions as $permission)
                                            <label class="form-check-label">
                                                <input type="checkbox" name="permission[]" value="{{ $permission->id }}"
                                                    class="form-check-input"
                                                    {{ in_array($permission->id, $rolewithpermission) ? 'Checked' : '' }}>
                                                {{ $permission->name }}
                                            </label>
                                            <br>
                                        @endforeach
                                    </div>
                                </div>
                                <div class="row">
                                    <label class="col-sm-3 col-form-label"></label>
                                    <div class="col-sm-9">
                                        <div class="gap-3 d-md-flex d-grid align-items-center">
                                            <button type="submit" class="px-4 btn btn-primary">Save change</button>
                                        </div>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
            <!-- end-content -->
        </div>
    </div>
    <!--end page wrapper -->
@endsection
