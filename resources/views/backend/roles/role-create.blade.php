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
                            <h5 class="mb-4">Create Role</h5>
                            <form action="{{ route('roles.store') }}" method="post">
                                @csrf
                                <div class="mb-3 form-group">
                                    <label for="name" class="col-sm-3 col-form-label">Role Name</label>
                                    <div class="col-sm-9">
                                        <input type="text" class="form-control" id="name" name="name"
                                            value="" placeholder="Enter Your Role Name">
                                    </div>

                                </div>
                                <div class="mb-3 form-group">
                                    @foreach ($permissions as $permission)
                                        <div class="col-sm-9 form-check">
                                            <label class="form-check-label">
                                                <input type="checkbox" name="permission[{{ $permission->id }}]"
                                                    value="{{ $permission->id }}" class="form-check-input">
                                                {{ $permission->name }}
                                            </label>
                                        </div>
                                    @endforeach
                                </div>
                                <div class="row">
                                    <label class="col-sm-3 col-form-label"></label>
                                    <div class="col-sm-9">
                                        <div class="gap-3 d-md-flex d-grid align-items-center">
                                            <button type="submit" class="px-4 btn btn-primary">Save</button>
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
