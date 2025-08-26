/**
 * Global JavaScript Helper Functions for User Roles Management
 * Include this file in your main layout or app.js
 */

// Global function to update modal content (if not already exists)
function updateModal(modalSelector, title, content, modalSize = 'modal-lg') {
    const $modal = $(modalSelector);
    const $modalDialog = $modal.find('.modal-dialog');
    
    // Clean up any existing namespaced event handlers to prevent duplicates
    $(document).off('.createRoleModal');
    $(document).off('.editRoleModal');
    $(document).off('.roleModal');
    
    // Update modal size
    $modalDialog.removeClass('modal-sm modal-lg modal-xl').addClass(modalSize);
    
    // Update title
    $modal.find('.modal-title').html(title);
    
    // Update content
    $modal.find('#content-example').html(content);
    
    // Show modal
    $modal.modal('show');
}

// Global function to show loading overlay
function showLoadingOverlay() {
    $('#loading-overlay').fadeIn();
}

// Global function to hide loading overlay
function hideLoadingOverlay() {
    $('#loading-overlay').fadeOut();
}

// Global function for standardized AJAX error handling
function handleAjaxError(xhr, defaultMessage = 'Terjadi kesalahan') {
    let errorMessage = defaultMessage;
    
    if (xhr.status === 422) {
        // Validation errors
        const errors = xhr.responseJSON?.errors;
        if (errors) {
            errorMessage = Object.values(errors).flat().join('<br>');
        }
    } else if (xhr.status === 404) {
        errorMessage = 'Data tidak ditemukan';
    } else if (xhr.status === 403) {
        errorMessage = 'Anda tidak memiliki izin untuk melakukan tindakan ini';
    } else if (xhr.status === 500) {
        errorMessage = 'Terjadi kesalahan server internal';
    } else if (xhr.responseJSON?.message) {
        errorMessage = xhr.responseJSON.message;
    }
    
    return errorMessage;
}

// Global function for standardized success notification
function showSuccessNotification(message, title = 'Berhasil!') {
    Swal.fire({
        icon: 'success',
        title: title,
        text: message,
        timer: 2000,
        showConfirmButton: false
    });
}

// Global function for standardized error notification
function showErrorNotification(message, title = 'Error!') {
    Swal.fire({
        icon: 'error',
        title: title,
        html: message
    });
}

// Global function for confirmation dialogs
function showConfirmationDialog(options = {}) {
    const defaultOptions = {
        title: 'Apakah Anda yakin?',
        text: 'Tindakan ini tidak dapat dibatalkan',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        cancelButtonColor: '#3085d6',
        confirmButtonText: 'Ya, lanjutkan!',
        cancelButtonText: 'Batal'
    };
    
    return Swal.fire({ ...defaultOptions, ...options });
}

// Global function to format date
function formatDate(dateString, format = 'DD MMM YYYY') {
    if (!dateString) return '-';
    
    const date = new Date(dateString);
    const months = [
        'Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun',
        'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'
    ];
    
    const day = date.getDate().toString().padStart(2, '0');
    const month = months[date.getMonth()];
    const year = date.getFullYear();
    const hours = date.getHours().toString().padStart(2, '0');
    const minutes = date.getMinutes().toString().padStart(2, '0');
    
    switch (format) {
        case 'DD MMM YYYY':
            return `${day} ${month} ${year}`;
        case 'DD MMM YYYY HH:mm':
            return `${day} ${month} ${year} ${hours}:${minutes}`;
        default:
            return date.toLocaleDateString('id-ID');
    }
}

// Global function to disable/enable button with loading state
function setButtonLoading($button, isLoading, originalText = null) {
    if (isLoading) {
        if (!originalText) {
            originalText = $button.html();
        }
        $button.data('original-text', originalText);
        $button.prop('disabled', true).html('<i class="bi bi-hourglass-split"></i> Loading...');
    } else {
        const storedText = $button.data('original-text') || originalText || $button.html();
        $button.prop('disabled', false).html(storedText);
    }
}

// Global function to validate form fields
function validateForm($form) {
    let isValid = true;
    const errors = [];
    
    // Check required fields
    $form.find('[required]').each(function() {
        const $field = $(this);
        const value = $field.val().trim();
        
        if (!value) {
            const label = $field.closest('.row').find('label').text().replace('*', '').trim();
            errors.push(`${label} wajib diisi`);
            $field.addClass('is-invalid');
            isValid = false;
        } else {
            $field.removeClass('is-invalid');
        }
    });
    
    // Show validation errors
    if (!isValid) {
        showErrorNotification(errors.join('<br>'), 'Validasi Error');
    }
    
    return isValid;
}

// Global function to reset form and remove validation classes
function resetForm($form) {
    $form[0].reset();
    $form.find('.is-invalid').removeClass('is-invalid');
    $form.find('.is-valid').removeClass('is-valid');
}

// Global function to create permission badge HTML
function createPermissionBadge(permission) {
    return `<span class="permission-badge bg-light text-primary border border-primary rounded-pill px-2 py-1 me-1 mb-1 d-inline-block" style="font-size: 0.75rem;">${permission.name}</span>`;
}

// Global function to create status badge HTML
function createStatusBadge(status) {
    const statusClass = status === 'active' ? 'success' : 'danger';
    const statusText = status === 'active' ? 'Active' : 'Inactive';
    const statusIcon = status === 'active' ? 'check-circle' : 'x-circle';
    
    return `<span class="badge bg-${statusClass}"><i class="bi bi-${statusIcon} me-1"></i>${statusText}</span>`;
}

// Global function to create action buttons HTML
function createActionButtons(item, options = {}) {
    const defaultOptions = {
        showView: true,
        showEdit: true,
        showDelete: true,
        customActions: []
    };
    
    const config = { ...defaultOptions, ...options };
    let buttonsHtml = '<div class="btn-group" role="group">';
    
    if (config.showView) {
        buttonsHtml += `
            <button type="button" class="btn btn-sm btn-info btn-view-item" 
                data-id="${item.id}" title="Detail">
                <i class="bi bi-eye"></i>
            </button>
        `;
    }
    
    if (config.showEdit) {
        buttonsHtml += `
            <button type="button" class="btn btn-sm btn-warning btn-edit-item" 
                data-id="${item.id}" title="Edit">
                <i class="bi bi-pencil"></i>
            </button>
        `;
    }
    
    if (config.showDelete) {
        buttonsHtml += `
            <button type="button" class="btn btn-sm btn-danger btn-delete-item" 
                data-id="${item.id}" title="Delete">
                <i class="bi bi-trash"></i>
            </button>
        `;
    }
    
    // Add custom actions
    config.customActions.forEach(action => {
        buttonsHtml += action.html;
    });
    
    buttonsHtml += '</div>';
    return buttonsHtml;
}

// Global function for AJAX calls that need loading indicators
function ajaxWithLoading(options = {}) {
    const defaultOptions = {
        showLoading: true,
        loadingText: 'Loading...',
        $button: null // Optional button to show loading state
    };
    
    const config = { ...defaultOptions, ...options };
    
    // Show loading if requested
    if (config.showLoading) {
        showLoadingOverlay();
    }
    
    // Set button loading state if button provided
    if (config.$button) {
        setButtonLoading(config.$button, true, config.loadingText);
    }
    
    // Wrap success and error callbacks to handle loading cleanup
    const originalSuccess = config.success || function() {};
    const originalError = config.error || function() {};
    const originalComplete = config.complete || function() {};
    
    config.success = function(response, textStatus, xhr) {
        originalSuccess(response, textStatus, xhr);
    };
    
    config.error = function(xhr, textStatus, errorThrown) {
        originalError(xhr, textStatus, errorThrown);
    };
    
    config.complete = function(xhr, textStatus) {
        // Clean up loading states
        if (config.showLoading) {
            hideLoadingOverlay();
        }
        
        if (config.$button) {
            setButtonLoading(config.$button, false);
        }
        
        originalComplete(xhr, textStatus);
    };
    
    // Remove our custom options before passing to jQuery
    delete config.showLoading;
    delete config.loadingText;
    delete config.$button;
    
    return $.ajax(config);
}

// Initialize common event listeners
$(document).ready(function() {
    // Global CSRF token setup for AJAX requests
    $.ajaxSetup({
        headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
        }
    });
    
    // Global modal event listeners
    $(document).on('hidden.bs.modal', '.modal', function() {
        // Reset modal content when closed
        $(this).find('.modal-body').scrollTop(0);
    });
    
    // Global form validation on submit
    $(document).on('submit', 'form[data-validate="true"]', function(e) {
        if (!validateForm($(this))) {
            e.preventDefault();
        }
    });
    
    // Note: Removed global AJAX loading overlay as it was too intrusive 
    // for background operations like token validation. 
    // Use setButtonLoading() or showLoadingOverlay() manually when needed.
});

// Export functions for ES6 modules (if using module bundler)
if (typeof module !== 'undefined' && module.exports) {
    module.exports = {
        updateModal,
        showLoadingOverlay,
        hideLoadingOverlay,
        handleAjaxError,
        showSuccessNotification,
        showErrorNotification,
        showConfirmationDialog,
        formatDate,
        setButtonLoading,
        validateForm,
        resetForm,
        createPermissionBadge,
        createStatusBadge,
        createActionButtons,
        ajaxWithLoading
    };
}
