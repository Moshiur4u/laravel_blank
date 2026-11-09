// Delegation keeps the confirmation working for rows added after page load.
document.addEventListener('click', function (event) {
    if (!(event.target instanceof Element)) {
        return;
    }

    const deleteControl = event.target.closest('[data-swal-delete]');
    if (!deleteControl) {
        return;
    }

    event.preventDefault();

    if (typeof window.Swal?.fire !== 'function') {
        console.error('SweetAlert2 is unavailable; delete navigation was cancelled.');
        window.alert('Delete confirmation is unavailable. Please reload the page and try again.');
        return;
    }

    window.Swal.fire({
        title: 'Do you want to delete?',
        text: deleteControl.dataset.deleteMessage || 'This action cannot be undone.',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Yes',
        cancelButtonText: 'No',
        reverseButtons: true,
        focusCancel: true,
    }).then(function (result) {
        if (result.isConfirmed) {
            if (deleteControl instanceof HTMLFormElement) {
                deleteControl.requestSubmit();
                return;
            }

            window.location.assign(deleteControl.href);
        }
    }).catch(function (error) {
        console.error('Unable to display the delete confirmation.', error);
        window.alert('Delete confirmation failed. Please reload the page and try again.');
    });
});
