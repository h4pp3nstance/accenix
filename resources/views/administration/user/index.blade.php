@extends('layouts.app')
@section('content')
    @push('styles')
        <style>
            /* CSS code here */
        </style>
    @endpush
    <div id="main-content">
        <div class="page-heading">
            <div class="page-title">
                <div class="row">
                    <div class="col-12 col-md-6 order-md-1 order-last">
                        <h3>User Management</h3>
                        {{-- <p class="text-subtitle text-muted">
                            Easily manage and adjust product prices.
                        </p> --}}
                    </div>
                    <div class="col-12 col-md-6 order-md-2 order-first">
                        <nav aria-label="breadcrumb" class="breadcrumb-header float-start float-lg-end">
                            <ol class="breadcrumb">
                                <li class="breadcrumb-item">
                                    <a href="/dashboard">Dashboard</a>
                                </li>
                                <li class="breadcrumb-item active" aria-current="page">
                                    User Management
                                </li>
                            </ol>
                        </nav>
                    </div>
                </div>
            </div>
            <section class="section">
                <div class="card">
                    <div class="card-body">
                        @include('alert.alert')
                        @if ($permissions['create'])
                        <a href="{{ route('administration.user.create') }}" class="btn btn-primary me-2 fw-bold">+ Tambah User</a>
                        @endif
                        @if ($permissions['delete'])
                        <button id="btn-bulk-delete" class="btn btn-danger me-2 fw-bold" disabled>Bulk Delete</button>
                        @endif
                        <div class="table-responsive">
                            <table class="table table-striped table-bordered mt-1" id="tableuser">
                                <thead>
                                    <tr>
                                        <th style="width:1%"><input type="checkbox" id="select-all-users"/></th>
                                        <th>No</th>
                                        <th>Nama Lengkap</th>
                                        <th>Username</th>
                                        <th>Email</th>
                                        <th>Role</th>
                                        <th>Action</th>
                                    </tr>
                                </thead>
                            </table>
                        </div>
                    </div>
                </div>
            </section>
        </div>
    </div>
    @include('modal.modal')
    @include('components.ajax-error-modal')
@endsection
@push('scripts')
    <script>
        function showAjaxErrorModal(message) {
            var modalEl = document.getElementById('ajaxErrorModal');
            document.getElementById('ajaxErrorModalBody').innerHTML = message;
            // Pastikan modal ada di body agar Bootstrap bisa mendeteksi
            if (!document.body.contains(modalEl)) {
                document.body.appendChild(modalEl);
            }
            try {
                var errorModal = new bootstrap.Modal(modalEl);
                errorModal.show();
            } catch (e) {
                // Fallback jika Bootstrap Modal gagal
                modalEl.style.display = 'block';
            }
        }

        // Override DataTables default error alert
        $.fn.dataTable.ext.errMode = function(settings, techNote, message) {
            // Coba ambil response dari settings.jqXHR jika ada
            var customMsg = message;
            if (settings && settings.jqXHR && settings.jqXHR.responseText) {
                try {
                    var json = JSON.parse(settings.jqXHR.responseText);
                    if (json && json.error) {
                        customMsg = json.error;
                    }
                } catch (e) {}
            }
            showAjaxErrorModal(customMsg);
        };

        $(document).ready(function() {
            $('#tableuser').on('error.dt', function(e, settings, techNote, message) {
                showAjaxErrorModal(message);
            });

            var table = $('#tableuser').DataTable({
                ajax: {
                    url: '{{ route('administration.user.ajax') }}',
                    type: 'GET',
                },
                columns: [{
                        data: null,
                        orderable: false,
                        searchable: false,
                        render: function(data, type, row, meta) {
                            return `<input type="checkbox" class="select-user" data-id="${row.id}" />`;
                        }
                    },
                    {
                        data: null,
                        render: function(data, type, row, meta) {
                            return meta.row + 1;
                        }
                    },
                    {
                        data: null,
                        render: function(data, type, row) {
                            let givenName = row.name?.givenName || '';
                            let familyName = row.name?.familyName || '';
                            return givenName + " " + familyName;
                        }
                    },
                    {
                        data: 'userName'
                    },
                    {
                        data: "emails.0",
                        defaultContent: "-"
                    },
                    // Role
                    {
                        data: "roles",
                        render: function(data) {
                            return data && data.length ? data.map(role => role.display).join("<br>") : "-";
                        }
                    },
                    // Action
                    {
                        data: null,
                        render: function(data, type, row) {
                            return `
                    <button type="button" class="btn btn-sm btn-info btn-user-detail"
                        data-id="${row.id}" title="Detail">
                        <i class="bi bi-eye"></i>
                    </button>
                    @if ($permissions['update'])
                    <button type="button" class="btn btn-sm btn-warning btn-edit-item"
                        data-id="${row.id}" title="Edit">
                        <i class="bi bi-pencil"></i>
                    </button>
                    @endif
                    @if ($permissions['delete'])
                    <button type="button" class="btn btn-sm btn-danger btn-delete-item"
                        data-id="${row.id}" title="Delete">
                        <i class="bi bi-trash"></i>
                    </button>
                    @endif
                    `;
                        }
                    }
                ]
            });

            // Toggle select all
            $(document).on('change', '#select-all-users', function() {
                var checked = $(this).is(':checked');
                $('.select-user').prop('checked', checked);
                updateBulkButtonState();
            });

            // When table is redrawn, ensure select-all checkbox state and rebind events
            table.on('draw', function() {
                // clear select-all
                $('#select-all-users').prop('checked', false);
                updateBulkButtonState();
            });

            // Individual checkbox change
            $(document).on('change', '.select-user', function() {
                updateBulkButtonState();
            });

            function updateBulkButtonState() {
                var anyChecked = $('.select-user:checked').length > 0;
                $('#btn-bulk-delete').prop('disabled', !anyChecked);
            }

            // Bulk delete click - show modal with summary
            $(document).on('click', '#btn-bulk-delete', function(e) {
                e.preventDefault();
                var ids = $('.select-user:checked').map(function() { return $(this).data('id'); }).get();
                if (!ids.length) return;

                // Populate modal body with count and sample
                var modalEl = document.getElementById('modal-delete-confirm');
                var bsModal = new bootstrap.Modal(modalEl);
                document.getElementById('delete-confirm-body').innerText = `Are you sure you want to delete ${ids.length} selected users? This action cannot be undone.`;
                // Store pending ids on the confirm button element
                $('#confirm-delete-btn').data('bulk-ids', ids);
                bsModal.show();
            });

            // Confirm delete button - support single delete (existing flow) or bulk
            var originalConfirmHandler = document.getElementById('confirm-delete-btn').onclick;
            // Remove existing direct listener if present and replace with unified handler (we'll attach via event)
            document.getElementById('confirm-delete-btn').addEventListener('click', function () {
                var $btn = $(this);
                var bulkIds = $btn.data('bulk-ids');
                if (bulkIds && Array.isArray(bulkIds) && bulkIds.length) {
                    // Perform bulk delete
                    $btn.prop('disabled', true).text('Deleting...');
                    $.ajax({
                        url: '{{ route('administration.user.bulk') }}',
                        type: 'POST',
                        data: { ids: bulkIds },
                        headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
                        success: function(resp) {
                            if (resp && resp.results) {
                                // If any failed, show error modal
                                var failed = Object.keys(resp.results).filter(function(id) { return !resp.results[id].success; });
                                if (failed.length) {
                                    showAjaxErrorModal('Some users failed to delete. Check logs for details.');
                                }
                                $('#tableuser').DataTable().ajax.reload();
                                var modalEl = document.getElementById('modal-delete-confirm');
                                var bs = bootstrap.Modal.getInstance(modalEl);
                                if (bs) bs.hide();
                                // Clear stored ids
                                $btn.removeData('bulk-ids');
                            } else {
                                showAjaxErrorModal('Unexpected response from server');
                            }
                        },
                        error: function(xhr) {
                            const msg = xhr.responseJSON?.message || 'Failed to delete users';
                            showAjaxErrorModal(msg);
                        },
                        complete: function() {
                            $btn.prop('disabled', false).text('Delete');
                            // Uncheck all
                            $('#select-all-users').prop('checked', false);
                            $('.select-user').prop('checked', false);
                            updateBulkButtonState();
                        }
                    });
                    return;
                }

                // Fallback: if no bulk-ids, assume single delete flow handled elsewhere
            });

             // Single-delete flow is still supported: when modal opened via a single delete button we set pendingDeleteId
            document.getElementById('confirm-delete-btn').addEventListener('click', function () {
                var $confirmBtn = $(this);
                var bulkIds = $confirmBtn.data('bulk-ids');
                if (bulkIds && Array.isArray(bulkIds) && bulkIds.length) {
                    // bulk flow handled above from the same button; do nothing here
                    return;
                }
                if (!pendingDeleteId) return;
                var url = '{{ route('administration.user.destroy', ':id') }}'.replace(':id', pendingDeleteId);
                $confirmBtn.prop('disabled', true).text('Deleting...');

                $.ajax({
                    url: url,
                    type: 'DELETE',
                    headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
                    success: function(resp) {
                        if (resp && resp.success) {
                            $('#tableuser').DataTable().ajax.reload();
                            var modalEl = document.getElementById('modal-delete-confirm');
                            var bs = bootstrap.Modal.getInstance(modalEl);
                            if (bs) bs.hide();
                        } else {
                            showAjaxErrorModal(resp.message || 'Failed to delete user');
                        }
                    },
                    error: function(xhr) {
                        const msg = xhr.responseJSON?.message || 'Failed to delete user';
                        showAjaxErrorModal(msg);
                    },
                    complete: function() {
                        $confirmBtn.prop('disabled', false).text('Delete');
                        pendingDeleteId = null;
                    }
                });
            });

             $(document).on('click', '.btn-user-detail', function(e) {
                 e.preventDefault();
                 const itemid = $(this).data('id');
                 const url = '{{ route('administration.user.detail', ':itemid') }}'.replace(':itemid', itemid);
                 const $button = $(this);

                 $('#loading-overlay').fadeIn();
                 $button.prop("disabled", true).html('<i class="bi bi-hourglass-split"></i>');

                 $.ajax({
                     url: url,
                     type: 'GET',
                     dataType: 'html',
                     beforeSend: function() {
                         //
                     },
                     success: function(response) {
                         updateModal('#modal-example', 'Detail User', response,
                             'modal-lg');
                     },
                     error: function(xhr) {
                         let errorMsg = xhr.responseText || '<p>An error occurred while loading the content.</p>';
                         showAjaxErrorModal(errorMsg);
                     },
                     complete: function() {
                         $('#loading-overlay').fadeOut();
                         $button.prop("disabled", false).html('<i class="bi bi-eye"></i>');
                     }
                 });
             });

             $(document).on('click', '.btn-edit-item', function(e) {
                 e.preventDefault();
                 const itemId = $(this).data('id');
                 const url = '{{ route('administration.user.edit', ':id') }}'.replace(':id', itemId);
                 
                 // Navigate to the edit page instead of using modal
                 window.location.href = url;
             });

             // Delete handler (open confirmation modal)
             var pendingDeleteId = null;
             $(document).on('click', '.btn-delete-item', function(e) {
                 e.preventDefault();
                 pendingDeleteId = $(this).data('id');
                 var modalEl = document.getElementById('modal-delete-confirm');
                 var bsModal = new bootstrap.Modal(modalEl);
                 document.getElementById('delete-confirm-body').innerText = 'Are you sure you want to delete this user? This action cannot be undone.';
                 bsModal.show();
             });

         });
     </script>
@endpush
