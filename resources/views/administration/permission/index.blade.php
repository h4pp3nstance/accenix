@extends('layouts.app')
@section('content')
    @push('styles')
        <style>
            .permission-badge {
                margin: 2px;
                font-size: 0.75rem;
            }
            .assigned-roles {
                max-height: 60px;
                overflow-y: auto;
            }
        </style>
    @endpush
    <div id="main-content">
        <div class="page-heading">
            <div class="page-title">
                <div class="row">
                    <div class="col-12 col-md-6 order-md-1 order-last">
                        <h3>Permission Management</h3>
                        <p class="text-subtitle text-muted">
                            View system permissions from WSO2 Identity Server.
                        </p>
                    </div>
                    <div class="col-12 col-md-6 order-md-2 order-first">
                        <nav aria-label="breadcrumb" class="breadcrumb-header float-start float-lg-end">
                            <ol class="breadcrumb">
                                <li class="breadcrumb-item">
                                    <a href="/dashboard">Dashboard</a>
                                </li>
                                <li class="breadcrumb-item active" aria-current="page">
                                    Permission Management
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
                        <div class="alert alert-info" role="alert">
                            <i class="bi bi-info-circle"></i> Permissions are managed by WSO2 Identity Server and synchronized automatically.
                        </div>
                        <div class="table-responsive">
                            <table class="table table-striped table-bordered mt-1" id="tablepermission">
                                <thead>
                                    <tr>
                                        <th>No</th>
                                        <th>Permission Name</th>
                                        <th>Display Name</th>
                                        <th>Resource</th>
                                        <th>Action</th>
                                        <th>Assigned Roles</th>
                                        <th>Actions</th>
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

        $('#tablepermission').DataTable({
                ajax: {
                    url: '{{ route('administration.permission.ajax') }}',
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
                        defaultContent: "-",
                        render: function(data, type, row) {
                            return '<code>' + data + '</code>';
                        }
                    },
                    {
                        data: 'display_name',
                        defaultContent: "-"
                    },
                    {
                        data: 'resource',
                        defaultContent: "-",
                        render: function(data, type, row) {
                            return '<span class="badge bg-secondary">' + data + '</span>';
                        }
                    },
                    {
                        data: 'action',
                        defaultContent: "-",
                        render: function(data, type, row) {
                            var badgeClass = 'bg-primary';
                            switch(data) {
                                case 'read': badgeClass = 'bg-info'; break;
                                case 'create': badgeClass = 'bg-success'; break;
                                case 'update': badgeClass = 'bg-warning'; break;
                                case 'delete': badgeClass = 'bg-danger'; break;
                                case 'approve': badgeClass = 'bg-dark'; break;
                                case 'export': badgeClass = 'bg-secondary'; break;
                            }
                            return '<span class="badge ' + badgeClass + '">' + data + '</span>';
                        }
                    },
                    {
                        data: 'assigned_roles',
                        defaultContent: "-",
                        render: function(data, type, row) {
                            if (!data || data.length === 0) {
                                return '<span class="text-muted">No roles</span>';
                            }
                            var html = '<div class="assigned-roles">';
                            data.forEach(function(role) {
                                html += '<span class="badge bg-primary permission-badge">' + role + '</span>';
                            });
                            html += '</div>';
                            return html;
                        }
                    },
                    // Action
                    {
                        data: null,
                        render: function(data, type, row) {
                            return `
                    <button type="button" class="btn btn-sm btn-info btn-permission-detail"
                        data-id="${row.name}" title="Detail">
                        <i class="bi bi-eye"></i>
                    </button>
                    `;
                        }
                    }
                ],
                order: [[1, 'asc']],
                pageLength: 10,
                language: {
                    processing: "Loading permissions from WSO2...",
                    emptyTable: "No permissions found."
                }
            });

            $(document).on('click', '.btn-permission-detail', function(e) {
                e.preventDefault();
                const permissionId = $(this).data('id');
                const url = '{{ route('administration.permission.detail', ':id') }}'.replace(':id', encodeURIComponent(permissionId));
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
                        updateModal('#modal-example', 'Permission Details', response,
                            'modal-lg');
                    },
                    error: function(xhr) {
                        let errorMsg = xhr.responseText ||
                            '<p>An error occurred while loading the content.</p>';
                        $('#content-example').html(errorMsg);
                    },
                    complete: function() {
                        $('#loading-overlay').fadeOut();
                        $button.prop("disabled", false).html('<i class="bi bi-eye"></i>');
                    }
                });
            });
        });
    </script>
@endpush
