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
                        <div class="table-responsive">
                            <table class="table table-striped table-bordered mt-1" id="tableuser">
                                <thead>
                                    <tr>
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

            $('#tableuser').DataTable({
                ajax: {
                    url: '{{ route('administration.user.ajax') }}',
                    type: 'GET',
                },
                columns: [{
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
                    `;
                        }
                    }
                ]
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

        });
    </script>
@endpush
