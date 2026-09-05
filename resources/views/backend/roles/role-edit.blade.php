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
                            <li class="breadcrumb-item active" aria-current="page">Update Role</li>
                        </ol>
                    </nav>
                </div>
                <div class="ms-auto">
                    <div class="btn-group">
                        <a href="{{ route('roles.index') }}" class="btn btn-primary">All Roles</a>
                    </div>
                </div>
            </div>
            <!--end breadcrumb-->
            <div class="row">
                <div class="mx-auto col-lg-8">
                    <div class="card">
                        <div class="p-4 card-body">
                            <h5 class="mb-4">Update Role</h5>
                            <!- এখানে রোল আপডেট করার ফর্ম তৈরি করা হলো->
                                <form action="{{ route('roles.update', $role->id) }}" method="post">
                                    @csrf
                                    @method('PUT')
                                    <div class="mb-3 form-group">

                                        <label for="name" class="col-sm-3 col-form-label">Role Name</label>
                                        <div class="col-sm-9">
                                            <input type="text" class="form-control" id="name" name="name"
                                                {{-- এখানে ভ্যালু হিসেবে রোলের নাম রাখা হলো --}} value="{{ $role->name }}"
                                                placeholder="Enter Your Role Name">
                                            @error('name')
                                                <div class="">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    </div>

                                    <div class="mb-3 form-group">
                                        <div class="col-sm-9 form-check">
                                            <label class="form-check-label fw-bold">
                                                <input type="checkbox" id="selectAll" class="form-check-input">
                                                Select All Permissions
                                            </label>
                                        </div>
                                        {{-- <div class="col-sm-9 form-check">
                                           
                                            @foreach ($permissions as $permission)
                                                <label class="form-check-label">
                                                    <input type="checkbox" name="permission[]" value="{{ $permission->id }}"
                                                        class="form-check-input" 
                                                        {{ in_array($permission->id, $rolewithpermission) ? 'Checked' : '' }}>
                                                    {{ $permission->name }}
                                                </label>
                                                <br>
                                            @endforeach
                                        </div> --}}
                                        <div class="col-sm-9 form-check">
                                            {{-- এখনে ফর ইচ লুপের মাধ্যমে পারমিশন দেখানো হলো  --}}
                                            @foreach ($permissions as $permission)
                                                <label class="form-check-label">
                                                    <input type="checkbox" name="permission[]" value="{{ $permission->id }}"
                                                        class="form-check-input permission-checkbox" {{-- এখানে permission-checkbox ক্লাসটি যোগ করা হলো --}}
                                                        {{ in_array($permission->id, $rolewithpermission) ? 'checked' : '' }}>
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
    <script>
        // Select All / Unselect All functionality
        document.getElementById('selectAll').addEventListener('change', function() {
            let checkboxes = document.querySelectorAll('.permission-checkbox');
            checkboxes.forEach((checkbox) => {
                checkbox.checked = this.checked;
            });
        });
    </script>
@endsection
