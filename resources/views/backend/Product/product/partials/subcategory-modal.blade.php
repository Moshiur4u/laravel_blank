@can('product.subcategory.create')
    <div class="modal fade" id="subcategoryCreateModal" tabindex="-1" aria-labelledby="subcategoryCreateModalLabel"
        aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="subcategoryCreateModalLabel">Create Subcategory</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body" id="subcategoryCreateModalBody">
                    <div class="py-4 text-center text-muted">
                        <span class="spinner-border spinner-border-sm me-2" role="status"></span>
                        Loading subcategory form...
                    </div>
                </div>
            </div>
        </div>
    </div>
@endcan
