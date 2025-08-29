@extends('layouts.app')
@section('content')
    @push('styles')
        <style>
            .role-permissions-badge {
                display: inline-block;
                margin: 2px;
                font-size: 0.75rem;
            }
            .permissions-container {
                max-height: 100px;
                overflow-y: auto;
            }
            .table th, .table td {
                text-align: center !important;
                vertical-align: middle !important;
            }
        </style>
    @endpush
    <div id="main-content">
        <div class="page-heading">
            <div class="page-title">
                <div class="row">
                    <div class="col-12 col-md-6 order-md-1 order-last">
                        <h3>Role Management</h3>
                        <p class="text-subtitle text-muted">
                            Manage user roles from WSO2 Identity Server.
                        </p>
                    </div>
                    <div class="col-12 col-md-6 order-md-2 order-first">
                        <nav aria-label="breadcrumb" class="breadcrumb-header float-start float-lg-end">
                            <ol class="breadcrumb">
                                <li class="breadcrumb-item">
                                    <a href="/dashboard">Dashboard</a>
                                </li>
                                <li class="breadcrumb-item active" aria-current="page">
                                    Role Management
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
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <div>
                                <button type="button" class="btn btn-primary fw-bold btn-add-role">+ Tambah Role</button>
                                <button id="refreshPermissions" class="btn btn-success ms-2 fw-bold">
                                    <i class="bi bi-arrow-clockwise"></i> Refresh from WSO2
                                </button>
                            </div>
                        </div>
                        <div class="table-responsive">
                            <table class="table table-striped table-bordered mt-1" id="tablerole">
                                <thead>
                                    <tr>
                                        <th>No</th>
                                        <th>Role Name</th>
                                        <th>Users Count</th>
                                        <th>Permissions Count</th>
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
@endsection
@push('scripts')
    <script>
        $(document).ready(function() {
        // Override DataTables default error alert
        $.fn.dataTable.ext.errMode = function(settings, techNote, message) {
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

        var table = $('#tablerole').DataTable({
                ajax: {
                    url: '{{ route('administration.role.ajax') }}',
                    type: 'GET',
                },
                columns: [{
                        data: null,
                        render: function(data, type, row, meta) {
                            return meta.row + 1;
                        }
                    },
                    {
                        data: 'name',
                        defaultContent: "-"
                    },
                    {
                        data: 'user_count',
                        defaultContent: "0",
                        render: function(data, type, row) {
                            return '<span class="badge bg-info">' + data + ' users</span>';
                        }
                    },
                    {
                        data: 'permissions_count',
                        defaultContent: "0",
                        render: function(data, type, row) {
                            return '<span class="badge bg-primary">' + data + '</span>';
                        }
                    },
                    // Action
                    {
                        data: null,
                        render: function(data, type, row) {
                            // Define protected roles that cannot be deleted or edited
                            const protectedRoles = ['everyone', 'admin', 'application/everyone', 'application/admin', 'system'];
                            const isProtected = protectedRoles.includes(row.name.toLowerCase());
                            
                            let actions = `
                                <button type="button" class="btn btn-sm btn-info btn-role-detail"
                                    data-id="${row.name}" title="Detail">
                                    <i class="bi bi-eye"></i>
                                </button>
                            `;
                            
                            // Edit button: disable for protected roles
                            if (!isProtected) {
                                actions += `
                                <button type="button" class="btn btn-sm btn-warning btn-role-edit"
                                    data-id="${row.name}" title="Edit">
                                    <i class="bi bi-pencil"></i>
                                </button>
                                `;
                            } else {
                                actions += `
                                <button type="button" class="btn btn-sm btn-secondary" disabled
                                    title="Protected system role - cannot be edited">
                                    <i class="bi bi-shield-lock"></i>
                                </button>
                                `;
                            }
                            
                            // Delete button: disable for protected roles
                            if (!isProtected) {
                                actions += `
                                <button type="button" class="btn btn-sm btn-danger btn-role-delete"
                                    data-id="${row.name}" title="Delete">
                                    <i class="bi bi-trash"></i>
                                </button>
                                `;
                            } else {
                                actions += `
                                <button type="button" class="btn btn-sm btn-secondary" disabled
                                    title="Protected system role - cannot be deleted">
                                    <i class="bi bi-shield-lock"></i>
                                </button>
                                `;
                            }
                            
                            return actions;
                        }
                    }
                ],
                order: [[1, 'asc']],
                pageLength: 10,
                language: {
                    processing: "Loading roles from WSO2...",
                    emptyTable: "No roles found. Click 'Refresh from WSO2' to sync data."
                }
            });

            // Add Role Handler
            $(document).on('click', '.btn-add-role', function(e) {
                e.preventDefault();
                const url = '{{ route('administration.role.create') }}';
                window.location.href = url;
            });

            // Detail Role Handler
            $(document).on('click', '.btn-role-detail', function(e) {
                e.preventDefault();
                const roleId = $(this).data('id');
                const url = '{{ route('administration.role.detail', ':id') }}'.replace(':id', roleId);
                window.location.href = url;
            });

            // Edit Role Handler
            $(document).on('click', '.btn-role-edit', function(e) {
                e.preventDefault();
                const roleId = $(this).data('id');
                const url = '{{ route('administration.role.edit', ':id') }}'.replace(':id', roleId);
                window.location.href = url;
            });

            // Delete Role Handler
            $(document).on('click', '.btn-role-delete', function(e) {
                e.preventDefault();
                const roleId = $(this).data('id');
                const $button = $(this);

                // Double-check protection on frontend
                const protectedRoles = ['everyone', 'admin', 'application/everyone', 'application/admin'];
                if (protectedRoles.includes(roleId.toLowerCase())) {
                    alert('Cannot delete protected system role: ' + roleId);
                    return;
                }

                if (confirm('Are you sure you want to delete this role? This action cannot be undone.')) {
                    $button.prop("disabled", true).html('<i class="bi bi-hourglass-split"></i>');

                    $.ajax({
                        url: '{{ route('administration.role.destroy', ':id') }}'.replace(':id', roleId),
                        type: 'DELETE',
                        data: {
                            _token: '{{ csrf_token() }}'
                        },
                        success: function(response) {
                            if (response.success) {
                                table.ajax.reload();
                                alert('Role deleted successfully!');
                            } else {
                                alert('Failed to delete role: ' + response.message);
                            }
                        },
                        error: function(xhr) {
                            const response = xhr.responseJSON;
                            const message = response && response.message ? response.message : 'Failed to delete role. Please try again.';
                            alert(message);
                        },
                        complete: function() {
                            $button.prop("disabled", false).html('<i class="bi bi-trash"></i>');
                        }
                    });
                }
            });

            // Refresh permissions from WSO2
            $('#refreshPermissions').on('click', function(e) {
                e.preventDefault();
                const $button = $(this);
                
                $button.prop('disabled', true).html('<i class="bi bi-hourglass-split"></i> Refreshing...');
                
                $.ajax({
                    url: '{{ route('administration.role.refresh') }}',
                    type: 'POST',
                    data: {
                        _token: '{{ csrf_token() }}'
                    },
                    success: function(response) {
                        if (response.success) {
                            table.ajax.reload();
                            alert('Permissions refreshed successfully!');
                        } else {
                            alert('Failed to refresh permissions: ' + response.message);
                        }
                    },
                    error: function(xhr) {
                        alert('Failed to refresh permissions. Please try again.');
                    },
                    complete: function() {
                        $button.prop('disabled', false).html('<i class="bi bi-arrow-clockwise"></i> Refresh from WSO2');
                    }
                });
            });
        });
    </script>
@endpush
