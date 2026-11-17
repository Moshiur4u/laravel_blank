<script>
    document.addEventListener('DOMContentLoaded', function() {
        const categorySelect = document.getElementById('category_id');
        const subcategorySelect = document.getElementById('subcategory_id');
        const loadError = document.getElementById('subcategoryLoadError');
        const modal = document.getElementById('subcategoryCreateModal');
        const modalBody = document.getElementById('subcategoryCreateModalBody');
        const createUrl = @json(route('subcategories.create'));

        async function loadSubcategories(categoryId, selectedId = '') {
            subcategorySelect.replaceChildren(new Option(
                categoryId ? 'Loading Subcategories...' : 'Select Category First',
                ''
            ));
            subcategorySelect.disabled = !categoryId;
            loadError.textContent = '';

            if (!categoryId) {
                return;
            }

            try {
                const url = subcategorySelect.dataset.optionsUrl.replace('__CATEGORY__', encodeURIComponent(categoryId));
                const response = await fetch(url, {
                    headers: { 'Accept': 'application/json' }
                });
                const result = await response.json();

                if (!response.ok || !Array.isArray(result.subcategories)) {
                    throw new Error(result.message || `Unable to load subcategories (HTTP ${response.status}).`);
                }

                subcategorySelect.replaceChildren(new Option(
                    result.subcategories.length ? 'Select Subcategory' : 'No Subcategories Available',
                    ''
                ));
                result.subcategories.forEach((subcategory) => {
                    const option = new Option(subcategory.name, subcategory.id);
                    option.selected = String(subcategory.id) === String(selectedId);
                    subcategorySelect.add(option);
                });
                subcategorySelect.disabled = false;
            } catch (error) {
                subcategorySelect.replaceChildren(new Option('Unable to Load Subcategories', ''));
                subcategorySelect.disabled = true;
                loadError.textContent = error.message;
            }
        }

        categorySelect.addEventListener('change', function() {
            loadSubcategories(categorySelect.value);
        });
        loadSubcategories(categorySelect.value, subcategorySelect.dataset.selected);

        if (!modal) {
            return;
        }

        modal.addEventListener('show.bs.modal', async function() {
            modalBody.innerHTML = '<div class="py-4 text-center text-muted"><span class="spinner-border spinner-border-sm me-2" role="status"></span>Loading subcategory form...</div>';

            try {
                const url = new URL(createUrl, window.location.origin);
                if (categorySelect.value) {
                    url.searchParams.set('category_id', categorySelect.value);
                }
                const response = await fetch(url, {
                    headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'text/html' }
                });

                if (!response.ok) {
                    throw new Error(`Unable to load subcategory form (HTTP ${response.status}).`);
                }

                const page = new DOMParser().parseFromString(await response.text(), 'text/html');
                const form = page.getElementById('product-subcategory-create-form');
                if (!form) {
                    throw new Error('The subcategory form was not found in the response.');
                }
                modalBody.replaceChildren(form);
            } catch (error) {
                modalBody.innerHTML = '<div class="alert alert-danger" role="alert"></div>';
                modalBody.querySelector('.alert').textContent = error.message;
            }
        });

        modalBody.addEventListener('submit', async function(event) {
            const form = event.target.closest('#product-subcategory-create-form');
            if (!form) {
                return;
            }

            event.preventDefault();
            modalBody.querySelector('.quick-create-error')?.remove();
            const submitButton = form.querySelector('[type="submit"]');
            submitButton.disabled = true;

            try {
                const response = await fetch(form.action, {
                    method: 'POST',
                    body: new FormData(form),
                    headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' }
                });
                const result = await response.json();

                if (!response.ok || !result.subcategory) {
                    const messages = Object.values(result.errors || {}).flat();
                    throw new Error(messages.join(' ') || result.message || `Unable to create subcategory (HTTP ${response.status}).`);
                }

                categorySelect.value = result.subcategory.category_id;
                await loadSubcategories(result.subcategory.category_id, result.subcategory.id);
                bootstrap.Modal.getOrCreateInstance(modal).hide();
            } catch (error) {
                const alert = document.createElement('div');
                alert.className = 'alert alert-danger quick-create-error';
                alert.setAttribute('role', 'alert');
                alert.textContent = error.message;
                form.prepend(alert);
            } finally {
                submitButton.disabled = false;
            }
        });
    });
</script>
