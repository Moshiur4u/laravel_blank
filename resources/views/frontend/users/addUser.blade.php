@extends('dashboard.dashboard')
@section('title')
    editCreate
@endsection
@section('content')
    <div class="page-wrapper">
        <div class="page-content">
            <div class="row">

                {{--  Here start User's Info Add  --}}
                <div class="col-lg-8">
                    <div class="card">
                        <div class="card-header">
                            <h3 class="text-center text-primary">Add-User-Info</h3>
                        </div>
                        <div class="card-body">
                            <form action="{{ route('user.store') }}" method="post" enctype="multipart/form-data">
                                @csrf
                                @method('POST')
                                <div class="mb-3">
                                    <label for="name"> User Name</label>
                                    <input type="text" name="name" class="form-control" value="">
                                    @error('photo')
                                        <strong class="text-danger">{{ $message }}</strong>
                                    @enderror
                                </div>

                                <div class="mb-3">
                                    <label for="category_id" class="col-sm-3 col-form-label">Select Roles</label>
                                    <select class="form-select" name="roles" id="roles" required>
                                        <option value="" selected disabled>Select
                                            Role
                                        </option>
                                        @foreach ($Roles as $role)
                                            <option value="{{ $role->name }}">{{ $role->name }}</option>
                                        @endforeach
                                    </select>
                                    @error('roles')
                                        <strong class="text-danger">{{ $message }}</strong>
                                    @enderror
                                </div>
                                <div class="mb-3">
                                    <label for="name"> User Email</label>
                                    <input type="email" name="email" class="form-control" value="">
                                    @error('email')
                                        <strong class="text-danger">{{ $message }}</strong>
                                    @enderror
                                </div>
                                <div class="mb-3">
                                    <label for="name"> Password</label>
                                    <input type="number" name="password" class="form-control" value="">
                                    @error('password')
                                        <strong class="text-danger">{{ $message }}</strong>
                                    @enderror
                                </div>
                                <div class="mb-3">
                                    <label for="name">Conform Password</label>
                                    <input type="number" name="confarmPassword" class="form-control" value="">
                                    @error('confarmPassword')
                                        <strong class="text-danger">{{ $message }}</strong>
                                    @enderror
                                </div>
                                <div class="mb-3">
                                    <label for="name">Change Photo </label>
                                    <input type="file" id="imageInput" name="image" class="form-control"
                                        value="">
                                    <img id="preview" style="max-width:200px; margin-top:10px;" />
                                    @error('image')
                                        <strong class="text-danger">{{ $message }}</strong>
                                    @enderror
                                </div>
                                {{-- <div class="mb-3">
                                    <label for="name" class="text-danger">User Status</label>
                                    <select class="mb-3">
                                        <option type="nubmer" name="status" class="form-control" value="">Select
                                            Status
                                        </option>
                                        <option>Active</option>
                                        <option>inActive</option>
                                    </select>
                                    <label for="name" class="mb-10 text-danger">Remark</label>
                                    <textarea type="text" name="remark" class="form-control" value=""> </textarea>
                                </div> --}}
                                <div class="gap-2 mb-3 text-center ">
                                    <button class="btn btn-primary" type="submit"> Save</button>
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
            if (file) {
                document.getElementById('preview').src = URL.createObjectURL(file);
            }
        };
    </script>
@endsection
