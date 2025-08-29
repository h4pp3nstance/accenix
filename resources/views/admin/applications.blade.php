@extends('layouts.app')

@section('content')
    @push('styles')
        <style>
            /* Small tweaks to match admin style */
            .table th, .table td { vertical-align: middle !important; }
        </style>
    @endpush

    <div id="main-content">
        <div class="page-heading">
            <div class="page-title">
                <div class="row">
                    <div class="col-12 col-md-6 order-md-1 order-last">
                        <h3>Applications</h3>
                        <p class="text-subtitle text-muted">Manage applications registered in WSO2 Identity Server.</p>
                    </div>
                    <div class="col-12 col-md-6 order-md-2 order-first">
                        <nav aria-label="breadcrumb" class="breadcrumb-header float-start float-lg-end">
                            <ol class="breadcrumb">
                                <li class="breadcrumb-item"><a href="/dashboard">Dashboard</a></li>
                                <li class="breadcrumb-item active" aria-current="page">Applications</li>
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
                                <button id="btn-refresh-apps" class="btn btn-success ms-2 fw-bold">
                                    <i class="bi bi-arrow-clockwise"></i> Refresh from WSO2
                                </button>
                            </div>
                            <div>
                                <form id="import-form" class="d-flex align-items-center" enctype="multipart/form-data">
                                    <input id="import-file" type="file" name="file" accept=".xml,.yaml,.yml,.json" class="form-control" />
                                    <button id="import-btn" class="btn btn-primary ms-2">Import</button>
                                </form>
                                <div id="import-status" class="text-muted small mt-1"></div>
                            </div>
                        </div>

                        <div class="table-responsive">
                            <table class="table table-striped table-bordered mt-1" id="table-apps">
                                <thead>
                                    <tr>
                                        <th>No</th>
                                        <th>Name</th>
                                        <th>Description</th>
                                        <th>Client ID / ID</th>
                                        <th>Action</th>
                                    </tr>
                                </thead>
                                <tbody></tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </section>
        </div>
    </div>

@endsection

@push('scripts')
    <!-- Axios (CDN) -->
    <script src="https://cdn.jsdelivr.net/npm/axios/dist/axios.min.js"></script>
    <!-- DataTables (assume present across admin pages); fallback to simple rendering if not -->
    <script>
        (function () {
            // If DataTables is available, initialize similar to other admin pages
            function initDataTable() {
                if (typeof $ === 'undefined' || typeof $.fn?.DataTable === 'undefined') {
                    // Fallback: simple fetch + render
                    simpleFetchRender();
                    return;
                }

                var table = $('#table-apps').DataTable({
                    ajax: {
                        url: '/admin/api/applications',
                        type: 'GET',
                        dataSrc: 'data'
                    },
                    columns: [
                        { data: null, render: function (data, type, row, meta) { return meta.row + 1; } },
                        { data: 'name', defaultContent: '-' },
                        { data: 'description', defaultContent: '-' },
                        { data: 'id', defaultContent: '-' , render: function(d,t,row){ return d || row.clientId || ''; } },
                        { data: null, render: function (data, type, row) {
                            return `
                                <button class="btn btn-sm btn-info btn-view-app" data-id="${row.id}">View</button>
                                <button class="btn btn-sm btn-warning btn-regenerate" data-id="${row.id}">Regenerate Secret</button>
                            `;
                        } }
                    ],
                    order: [[1, 'asc']],
                    pageLength: 10,
                    stateSave: true,
                    language: {
                        processing: "Loading applications from WSO2...",
                        emptyTable: "No applications found. Click 'Refresh from WSO2' to sync data."
                    }
                });

                // Refresh handler
                $('#btn-refresh-apps').on('click', function (e) {
                    e.preventDefault();
                    $(this).prop('disabled', true).html('<i class="bi bi-hourglass-split"></i> Refreshing...');
                    $.ajax({
                        url: '/admin/api/applications?limit=10&offset=0',
                        type: 'GET',
                        success: function () {
                            table.ajax.reload();
                        },
                        error: function (xhr) {
                            const msg = xhr.responseJSON?.message || 'Failed to refresh applications.';
                            alert(msg);
                        },
                        complete: function () {
                            $('#btn-refresh-apps').prop('disabled', false).html('<i class="bi bi-arrow-clockwise"></i> Refresh from WSO2');
                        }
                    });
                });

                // Delegated actions
                $('#table-apps').on('click', '.btn-view-app', function () {
                    const id = $(this).data('id');
                    window.location.href = '/admin/applications/' + encodeURIComponent(id);
                });

                $('#table-apps').on('click', '.btn-regenerate', function () {
                    const id = $(this).data('id');
                    if (!confirm('Regenerate OIDC secret for this application?')) return;
                    axios.post('/admin/api/applications/' + encodeURIComponent(id) + '/inbound/oidc/regenerate-secret', {}, {
                        headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}' }
                    }).then(function (r) {
                        alert('Secret regenerated.');
                        table.ajax.reload();
                    }).catch(function (e) {
                        alert(e.response?.data?.message || 'Failed to regenerate secret.');
                    });
                });

                // Import form via AJAX
                $('#import-form').on('submit', function (e) {
                    e.preventDefault();
                    const fileInput = document.getElementById('import-file');
                    const statusEl = document.getElementById('import-status');
                    statusEl.innerText = '';
                    if (!fileInput.files || fileInput.files.length === 0) { statusEl.innerText = 'Select a file first.'; return; }
                    const fd = new FormData(); fd.append('file', fileInput.files[0]);

                    axios.post('/admin/api/applications/import', fd, { headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Content-Type': 'multipart/form-data' } })
                        .then(function () { statusEl.innerText = 'Import successful.'; table.ajax.reload(); })
                        .catch(function (err) { statusEl.innerText = 'Import failed: ' + (err.response?.data?.message || err.message); });
                });
            }

            function simpleFetchRender() {
                var $table = document.getElementById('table-apps').getElementsByTagName('tbody')[0];
                var refreshBtn = document.getElementById('btn-refresh-apps');
                var importForm = document.getElementById('import-form');
                var statusEl = document.getElementById('import-status');

                async function load() {
                    refreshBtn.disabled = true; refreshBtn.innerHTML = 'Refreshing...';
                    try {
                        var resp = await axios.get('/admin/api/applications');
                        var items = resp.data?.data || [];
                        $table.innerHTML = '';
                        items.forEach(function (row, idx) {
                            var tr = document.createElement('tr');
                            tr.innerHTML = `
                                <td>${idx + 1}</td>
                                <td>${escapeHtml(row.name || '')}</td>
                                <td>${escapeHtml(row.description || '')}</td>
                                <td>${escapeHtml(row.id || row.clientId || '')}</td>
                                <td><button class="btn btn-sm btn-info" data-id="${row.id}">View</button></td>
                            `;
                            $table.appendChild(tr);
                        });
                    } catch (e) {
                        alert(e.response?.data?.message || 'Failed to load applications.');
                    } finally {
                        refreshBtn.disabled = false; refreshBtn.innerHTML = '<i class="bi bi-arrow-clockwise"></i> Refresh from WSO2';
                    }
                }

                refreshBtn.addEventListener('click', function (e) { e.preventDefault(); load(); });
                importForm.addEventListener('submit', function (e) {
                    e.preventDefault(); statusEl.innerText = '';
                    var input = document.getElementById('import-file');
                    if (!input.files || input.files.length === 0) { statusEl.innerText = 'Select a file first.'; return; }
                    var fd = new FormData(); fd.append('file', input.files[0]);
                    axios.post('/admin/api/applications/import', fd, { headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Content-Type': 'multipart/form-data' } })
                        .then(function () { statusEl.innerText = 'Import successful.'; load(); })
                        .catch(function (err) { statusEl.innerText = 'Import failed: ' + (err.response?.data?.message || err.message); });
                });

                // initial load
                load();
            }

            // escapeHtml helper
            function escapeHtml(s) { if (!s) return ''; return String(s).replace(/[&<>"']/g, function (c) { return {'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":"&#39;"}[c]; }); }

            // Initialize on DOM ready
            if (document.readyState === 'loading') {
                document.addEventListener('DOMContentLoaded', initDataTable);
            } else {
                initDataTable();
            }
        })();
    </script>
@endpush
