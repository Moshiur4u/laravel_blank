<form id="product-subcategory-create-form" action="{{ route('subcategories.store') }}" method="POST">
    @csrf
    <div class="mb-3">
        <label for="subcategory_category_id" class="form-label">Category <span class="text-danger">*</span></label>
        <select name="category_id" id="subcategory_category_id" class="form-select" required>
            <option value="">Select Category</option>
            @foreach ($categories as $category)
                <option value="{{ $category->id }}" @selected((string) $selectedCategoryId === (string) $category->id)>
                    {{ $category->category_name }}
                </option>
            @endforeach
        </select>
        @error('category_id')
            <div class="mt-1 text-danger small">{{ $message }}</div>
        @enderror
    </div>
    <div class="mb-3">
        <label for="subcategory_name" class="form-label">Subcategory Name <span class="text-danger">*</span></label>
        <input type="text" name="name" id="subcategory_name" class="form-control" maxlength="255" required>
        @error('name')
            <div class="mt-1 text-danger small">{{ $message }}</div>
        @enderror
    </div>
    <div class="d-flex justify-content-end gap-2">
        @if ($isModal)
            <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
        @else
            <a href="{{ route('subcategories.index') }}" class="btn btn-light">Cancel</a>
        @endif
        <button type="submit" class="btn btn-primary">Create Subcategory</button>
    </div>
</form>
