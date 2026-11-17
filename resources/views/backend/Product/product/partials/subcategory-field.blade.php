<div class="col-md-6">
    <label for="subcategory_id" class="form-label fw-semibold text-secondary">
        Subcategory <span class="text-danger">*</span>
    </label>
    <div class="input-group">
        @can('product.subcategory.create')
            <button type="button" class="input-group-text bg-light text-secondary border-end-0"
                data-bs-toggle="modal" data-bs-target="#subcategoryCreateModal"
                aria-label="Create a subcategory" title="Create a subcategory">
                <i class="bx bx-category-alt"></i>
            </button>
        @else
            <span class="input-group-text bg-light text-secondary border-end-0" aria-hidden="true">
                <i class="bx bx-category-alt"></i>
            </span>
        @endcan
        <select name="subcategory_id" id="subcategory_id"
            class="form-select border-start-0 @error('subcategory_id') is-invalid @enderror"
            data-options-url="{{ route('product.subcategories', ['category' => '__CATEGORY__']) }}"
            data-selected="{{ $selectedSubcategoryId }}" required>
            <option value="">Select Subcategory</option>
        </select>
    </div>
    <div id="subcategoryLoadError" class="mt-1 text-danger small" role="alert"></div>
    @error('subcategory_id')
        <div class="mt-1 text-danger small">{{ $message }}</div>
    @enderror
</div>
