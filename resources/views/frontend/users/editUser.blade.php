@extends('dashboard.dashboard')
@section('title')
    Edit Employee / User
@endsection

@section('content')
    <div class="page-wrapper">
        <div class="page-content">

            <!--breadcrumb-->
            <div class="mb-3 page-breadcrumb d-none d-sm-flex align-items-center">
                <div class="breadcrumb-title pe-3">Employee</div>
                <div class="ps-3">
                    <nav aria-label="breadcrumb">
                        <ol class="p-0 mb-0 breadcrumb">
                            <li class="breadcrumb-item"><a href="javascript:;"><i class="bx bx-home-alt"></i></a></li>
                            <li class="breadcrumb-item"><a href="{{ route('user.index') }}">Employee List</a></li>
                            <li class="breadcrumb-item active" aria-current="page">Edit - {{ $Users->name }}</li>
                        </ol>
                    </nav>
                </div>
                <div class="ms-auto">
                    <div class="btn-group">
                        <a href="{{ route('user.index') }}" class="btn btn-outline-primary">
                            <i class="bx bx-arrow-back me-1"></i> Back To List
                        </a>
                    </div>
                </div>
            </div>
            <!--end breadcrumb-->

            <div class="row justify-content-center">
                <div class="col-lg-9 col-xl-8">
                    <div class="card border-0 shadow-sm radius-10">
                        <div class="card-header bg-transparent border-bottom py-3 d-flex align-items-center justify-content-between">
                            <div class="d-flex align-items-center gap-2">
                                <div class="widgets-icons-2 rounded-circle bg-light-primary text-primary" style="width: 40px; height: 40px; display: flex; align-items: center; justify-content: center;">
                                    <i class="bx bxs-user-detail fs-5"></i>
                                </div>
                                <div>
                                    <h5 class="mb-0 text-primary fw-bold">Update Employee Information</h5>
                                    <small class="text-muted">Modify employee credentials, roles, status, and avatar</small>
                                </div>
                            </div>
                            <a href="{{ route('user.index') }}" class="btn btn-sm btn-outline-secondary">
                                <i class="bx bx-list-ul me-1"></i> All Employees
                            </a>
                        </div>
                        <div class="card-body p-4">
                            <form action="{{ route('user.update', $Users->id) }}" method="POST" enctype="multipart/form-data">
                                @csrf
                                @method('PUT')

                                {{-- Name and Role --}}
                                <div class="row g-3 mb-3">
                                    <div class="col-md-6">
                                        <label for="name" class="form-label fw-semibold">
                                            User Name <span class="text-danger">*</span>
                                        </label>
                                        <div class="input-group">
                                            <span class="input-group-text bg-light"><i class="bx bx-user"></i></span>
                                            <input type="text" name="name" id="name"
                                                class="form-control @error('name') is-invalid @enderror"
                                                value="{{ old('name', $Users->name) }}" required>
                                        </div>
                                        @error('name')
                                            <span class="text-danger small d-block mt-1">{{ $message }}</span>
                                        @enderror
                                    </div>

                                    <div class="col-md-6">
                                        <label for="roles" class="form-label fw-semibold">
                                            Role <span class="text-danger">*</span>
                                        </label>
                                        <div class="input-group">
                                            <span class="input-group-text bg-light"><i class="bx bx-shield-quarter"></i></span>
                                            <select class="form-select @error('roles') is-invalid @enderror" name="roles" id="roles" required>
                                                <option value="" disabled>-- Select Role --</option>
                                                @foreach ($Roles as $role)
                                                    <option value="{{ $role->name }}"
                                                        {{ in_array($role->name, $userRole) ? 'selected' : '' }}>
                                                        {{ ucfirst($role->name) }}
                                                    </option>
                                                @endforeach
                                            </select>
                                        </div>
                                        @error('roles')
                                            <span class="text-danger small d-block mt-1">{{ $message }}</span>
                                        @enderror
                                    </div>
                                </div>

                                {{-- Email, Password, Confirm Password --}}
                                <div class="row g-3 mb-3">
                                    <div class="col-md-4">
                                        <label for="email" class="form-label fw-semibold">
                                            User Email <span class="text-danger">*</span>
                                        </label>
                                        <div class="input-group">
                                            <span class="input-group-text bg-light"><i class="bx bx-envelope"></i></span>
                                            <input type="email" name="email" id="email"
                                                class="form-control @error('email') is-invalid @enderror"
                                                value="{{ old('email', $Users->email) }}" required>
                                        </div>
                                        @error('email')
                                            <span class="text-danger small d-block mt-1">{{ $message }}</span>
                                        @enderror
                                    </div>

                                    <div class="col-md-4">
                                        <label for="password" class="form-label fw-semibold">
                                            New Password <small class="text-muted">(Leave empty to keep current)</small>
                                        </label>
                                        <div class="input-group">
                                            <span class="input-group-text bg-light"><i class="bx bx-lock-alt"></i></span>
                                            <input type="password" name="password" id="password"
                                                class="form-control @error('password') is-invalid @enderror"
                                                placeholder="••••••••">
                                        </div>
                                        @error('password')
                                            <span class="text-danger small d-block mt-1">{{ $message }}</span>
                                        @enderror
                                    </div>

                                    <div class="col-md-4">
                                        <label for="confirmPassword" class="form-label fw-semibold">
                                            Confirm Password
                                        </label>
                                        <div class="input-group">
                                            <span class="input-group-text bg-light"><i class="bx bx-lock"></i></span>
                                            <input type="password" name="confirmPassword" id="confirmPassword"
                                                class="form-control @error('confirmPassword') is-invalid @enderror"
                                                placeholder="••••••••">
                                        </div>
                                        @error('confirmPassword')
                                            <span class="text-danger small d-block mt-1">{{ $message }}</span>
                                        @enderror
                                    </div>
                                </div>

                                {{-- Status and Remark --}}
                                <div class="row g-3 mb-3">
                                    <div class="col-md-6">
                                        <label for="status" class="form-label fw-semibold">
                                            Account Status <span class="text-danger">*</span>
                                        </label>
                                        <div class="input-group">
                                            <span class="input-group-text bg-light"><i class="bx bx-toggle-left"></i></span>
                                            <select class="form-select @error('status') is-invalid @enderror" name="status" id="status" required>
                                                <option value="1" {{ $Users->status == 1 ? 'selected' : '' }}>Active</option>
                                                <option value="0" {{ $Users->status == 0 ? 'selected' : '' }}>Inactive</option>
                                            </select>
                                        </div>
                                        @error('status')
                                            <span class="text-danger small d-block mt-1">{{ $message }}</span>
                                        @enderror
                                    </div>

                                    <div class="col-md-6">
                                        <label for="remark" class="form-label fw-semibold">Remark / Note</label>
                                        <div class="input-group">
                                            <span class="input-group-text bg-light"><i class="bx bx-comment-detail"></i></span>
                                            <input type="text" name="remark" id="remark" class="form-control"
                                                value="{{ old('remark', $Users->remark) }}"
                                                placeholder="Remark or role description">
                                        </div>
                                    </div>
                                </div>

                                {{-- Change Photo --}}
                                <div class="mb-4">
                                    <label for="imageInput" class="form-label fw-semibold">Change Photo</label>
                                    <input type="file" id="imageInput" name="image"
                                        class="form-control @error('image') is-invalid @enderror"
                                        accept="image/*">
                                    <small class="text-muted d-block mt-1">Allowed formats: JPEG, PNG, JPG, GIF (Max: 2MB)</small>
                                    @error('image')
                                        <span class="text-danger small d-block mt-1">{{ $message }}</span>
                                    @enderror

                                    <div class="mt-3 p-3 border rounded bg-light text-center">
                                        <div class="row align-items-center justify-content-center g-4">
                                            @if ($Users->image)
                                                <div class="col-auto">
                                                    <p class="small text-muted mb-1">Current Photo</p>
                                                    <img src="{{ asset('uploads/users/' . $Users->image) }}"
                                                        alt="{{ $Users->name }}"
                                                        style="width: 100px; height: 100px; border-radius: 50%; object-fit: cover; border: 3px solid #dee2e6;" />
                                                </div>
                                            @endif
                                            <div class="col-auto" id="previewContainer" style="display: none;">
                                                <p class="small text-muted mb-1">New Photo Preview</p>
                                                <img id="preview" alt="New Preview"
                                                    style="width: 100px; height: 100px; border-radius: 50%; object-fit: cover; border: 3px solid #0d6efd; box-shadow: 0 4px 6px rgba(0,0,0,0.08);" />
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                {{-- Buttons --}}
                                <div class="d-flex align-items-center gap-2 pt-2 border-top">
                                    <button class="btn btn-primary px-4" type="submit">
                                        <i class="bx bx-check-double me-1"></i> Update Information
                                    </button>
                                    <a href="{{ route('user.index') }}" class="btn btn-outline-secondary px-3">
                                        Cancel
                                    </a>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </div>

    <script>
        document.getElementById('imageInput').onchange = function(evt) {
            const [file] = this.files;
            const preview = document.getElementById('preview');
            const previewContainer = document.getElementById('previewContainer');
            if (file) {
                preview.src = URL.createObjectURL(file);
                previewContainer.style.display = 'block';
            } else {
                previewContainer.style.display = 'none';
            }
        };
    </script>
@endsection
