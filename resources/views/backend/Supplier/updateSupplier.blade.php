@extends('dashboard.dashboard')
@section('title')
    Edit Supplier
@endsection
@section('content')
    <div class="page-wrapper">
        <div class="page-content">
            <div class="row">
                <div class="col-lg-6">
                    <div class="card">
                        <div class="card-header">
                            <h3 class="text-primary">Edit Supplier Info</h3>
                            <div class="gap-2 mb-3">
                                <a href="{{ route('supplier.index') }}" class="btn btn-primary float-end">
                                    Back To List</a>
                            </div>
                        </div>
                        <div class="card-body">
                            <form action="{{ route('supplier.update', $supplier->id) }}" method="POST" enctype="multipart/form-data">
                                @csrf
                                @method('PUT')
                                <div class="mb-3">
                                    <label for="name"> Supplier Name</label>
                                    @error('name')
                                        <span class="text-danger d-block">{{ $message }}</span>
                                    @enderror
                                    <input type="text" name="name" class="form-control" value="{{ old('name', $supplier->name) }}">
                                </div>
                                <div class="mb-3">
                                    <label for="email"> Supplier Email</label>
                                    @error('email')
                                        <span class="text-danger d-block">{{ $message }}</span>
                                    @enderror
                                    <input type="email" name="email" class="form-control" value="{{ old('email', $supplier->email) }}">
                                </div>
                                <div class="mb-3">
                                    <label for="phone"> Supplier Phone</label>
                                    @error('phone')
                                        <span class="text-danger d-block">{{ $message }}</span>
                                    @enderror
                                    <input type="text" name="phone" class="form-control" value="{{ old('phone', $supplier->phone) }}">
                                </div>
                                <div class="mb-3">
                                    <label for="address"> Supplier Address</label>
                                    @error('address')
                                        <span class="text-danger d-block">{{ $message }}</span>
                                    @enderror
                                    <input type="text" name="address" class="form-control" value="{{ old('address', $supplier->address) }}">
                                </div>
                                <div class="mb-3">
                                    <label for="logo"> Supplier Logo</label>
                                    @error('logo')
                                        <span class="text-danger d-block">{{ $message }}</span>
                                    @enderror
                                    <input type="file" id="imageInput" name="logo" class="form-control">
                                    <div class="mt-2">
                                        @if ($supplier->logo)
                                            <p class="mb-1 text-muted">Current Logo:</p>
                                            <img id="preview" src="{{ asset('uploads/suppliers/' . $supplier->logo) }}" style="max-width:120px; border-radius: 8px;" />
                                        @else
                                            <img id="preview" style="max-width:120px; border-radius: 8px; display:none;" />
                                        @endif
                                    </div>
                                </div>
                                <div class="gap-2 mb-3 d-flex">
                                    <button class="btn btn-primary" type="submit"> Update Supplier</button>
                                    <a href="{{ route('supplier.index') }}" class="btn btn-secondary"> Cancel</a>
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
                const preview = document.getElementById('preview');
                preview.src = URL.createObjectURL(file);
                preview.style.display = 'block';
            }
        };
    </script>
@endsection
