@extends('layouts.app')

@section('content')
<div id="main-content">
    <div class="page-heading">
        <div class="page-title">
            <div class="row">
                <div class="col-12 col-md-6 order-md-1 order-last">
                    <h3><i class="bi bi-people"></i> Lead Management</h3>
                    <p class="text-subtitle text-muted">
                        Manage and track lead progression through sales pipeline
                    </p>
                </div>
                <div class="col-12 col-md-6 order-md-2 order-first">
                    <nav aria-label="breadcrumb" class="breadcrumb-header float-start float-lg-end">
                        <ol class="breadcrumb">
                            <li class="breadcrumb-item">
                                <a href="{{ route('admin.crm.dashboard') }}">CRM Dashboard</a>
                            </li>
                            <li class="breadcrumb-item active" aria-current="page">
                                Lead Management
                            </li>
                        </ol>
                    </nav>
                </div>
            </div>
        </div>
    </div>

    <section class="section">
        @include('alert.alert')
        
        <!-- Filters -->
        <div class="card mb-3">
            <div class="card-body">
                <div class="row align-items-end">
                    <div class="col-md-3">
                        <label for="statusFilter" class="form-label">Filter by Status</label>
                        <select id="statusFilter" class="form-select">
                            <option value="">All Statuses</option>
                            <option value="new">New</option>
                            <option value="contacted">Contacted</option>
                            <option value="qualified">Qualified</option>
                            <option value="converted">Converted</option>
                            <option value="rejected">Rejected</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label for="sourceFilter" class="form-label">Filter by Source</label>
                        <select id="sourceFilter" class="form-select">
                            <option value="">All Sources</option>
                            <option value="website">Website</option>
                            <option value="referral">Referral</option>
                            <option value="social_media">Social Media</option>
                            <option value="email">Email Campaign</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label for="dateFromFilter" class="form-label">Date Range</label>
                        <div class="input-group">
                            <input type="date" id="dateFromFilter" class="form-control" placeholder="From Date">
                            <span class="input-group-text">to</span>
                            <input type="date" id="dateToFilter" class="form-control" placeholder="To Date">
                        </div>
                    </div>
                    <div class="col-md-3">
                        <button type="button" class="btn btn-primary" onclick="applyFilters()">
                            <i class="bi bi-funnel"></i> Apply Filters
                        </button>
                        <button type="button" class="btn btn-secondary" onclick="clearFilters()">
                            <i class="bi bi-x-circle"></i> Clear
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Leads Table -->
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5><i class="bi bi-table"></i> Leads Overview</h5>
                <div>
                    <button class="btn btn-success" onclick="bulkActivateSelected()">
                        <i class="bi bi-check-circle"></i> Bulk Activate
                    </button>
                    <button class="btn btn-info" onclick="exportLeads()">
                        <i class="bi bi-download"></i> Export
                    </button>
                </div>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-striped table-hover" id="leadsTable">
                        <thead>
                            <tr>
                                <th><input type="checkbox" id="selectAll"></th>
                                <th>No</th>
                                <th>Company</th>
                                <th>Contact Person</th>
                                <th>Email</th>
                                <th>Status</th>
                                <th>Source</th>
                                <th>Registered</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <!-- DataTables will populate this -->
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </section>
</div>

@push('styles')
<style>
    .lead-status-badge {
        padding: 0.375rem 0.75rem;
        border-radius: 50px;
        font-size: 0.75rem;
        font-weight: 600;
        text-transform: uppercase;
    }
    
    .lead-status-new { background-color: #ffeaa7; color: #2d3436; }
    .lead-status-contacted { background-color: #74b9ff; color: white; }
    .lead-status-qualified { background-color: #00b894; color: white; }
    .lead-status-converted { background-color: #00cec9; color: white; }
    .lead-status-rejected { background-color: #e17055; color: white; }
    
    .company-info {
        border-left: 4px solid #007bff;
        padding-left: 1rem;
    }
    
    .lead-actions .btn {
        margin-right: 0.25rem;
        margin-bottom: 0.25rem;
    }
</style>
@endpush

@push('scripts')
<script>
$(document).ready(function() {
    console.log('Starting fresh DataTable initialization...');
    
    // Simple test - destroy any existing DataTable first
    if ($.fn.DataTable.isDataTable('#leadsTable')) {
        $('#leadsTable').DataTable().destroy();
    }
    
    // Initialize with very basic configuration
    var table = $('#leadsTable').DataTable({
        processing: true,
        serverSide: true,
        ajax: {
            url: '{{ route("admin.crm.leads.data") }}',
            type: 'GET',
            data: function(d) {
                console.log('Request data:', d);
                d.status = $('#statusFilter').val();
                d.source = $('#sourceFilter').val();
                d.date_from = $('#dateFromFilter').val();
                d.date_to = $('#dateToFilter').val();
            },
            dataSrc: function(json) {
                console.log('Server response:', json);
                return json.data;
            },
            error: function(xhr, error, thrown) {
                console.error('AJAX Error:', error, thrown);
                console.error('Response:', xhr.responseText);
            }
        },
        columns: [
            { 
                data: null, 
                orderable: false,
                render: function(data, type, row) {
                    return '<input type="checkbox" class="lead-checkbox" value="' + row.id + '">';
                }
            },
            { 
                data: null,
                render: function(data, type, row, meta) {
                    return meta.row + meta.settings._iDisplayStart + 1;
                }
            },
            { 
                data: 'company_name',
                render: function(data, type, row) {
                    return '<div class="company-info"><strong>' + (data || 'N/A') + '</strong><br><small class="text-muted">' + (row.business_type || 'N/A') + '</small></div>';
                }
            },
            { 
                data: 'contact_person',
                render: function(data, type, row) {
                    return '<div><strong>' + (data || 'N/A') + '</strong><br><small class="text-muted">' + (row.contact_phone || 'N/A') + '</small></div>';
                }
            },
            { 
                data: 'email',
                render: function(data, type, row) {
                    return data ? '<a href="mailto:' + data + '">' + data + '</a>' : 'N/A';
                }
            },
            { 
                data: 'status',
                render: function(data, type, row) {
                    var status = data || 'new';
                    return '<span class="lead-status-badge lead-status-' + status + '">' + status + '</span>';
                }
            },
            { 
                data: 'lead_source',
                defaultContent: 'website'
            },
            { 
                data: 'created',
                render: function(data, type, row) {
                    if (data) {
                        var date = new Date(data);
                        return date.toLocaleDateString();
                    }
                    return 'N/A';
                }
            },
            { 
                data: null,
                orderable: false,
                render: function(data, type, row) {
                    return '<div class="lead-actions">' +
                           '<button class="btn btn-sm btn-info" onclick="viewLead(\'' + row.id + '\')" title="View"><i class="bi bi-eye"></i></button> ' +
                           '<button class="btn btn-sm btn-warning" onclick="updateStatus(\'' + row.id + '\')" title="Update Status"><i class="bi bi-arrow-up-circle"></i></button> ' +
                           '<button class="btn btn-sm btn-success" onclick="activateLead(\'' + row.id + '\')" title="Activate"><i class="bi bi-check-circle"></i></button>' +
                           '</div>';
                }
            }
        ],
        order: [[7, 'desc']],
        pageLength: 25,
        responsive: true,
        language: {
            processing: "Loading leads data...",
            emptyTable: "No leads found",
            zeroRecords: "No matching leads found"
        },
        drawCallback: function(settings) {
            console.log('Table drawn. Rows displayed:', this.api().rows().count());
        }
    });
    
    console.log('DataTable initialized successfully');
});

// Filter functions
function applyFilters() {
    console.log('Applying filters...');
    $('#leadsTable').DataTable().ajax.reload();
}

function clearFilters() {
    console.log('Clearing filters...');
    $('#statusFilter').val('');
    $('#sourceFilter').val('');
    $('#dateFromFilter').val('');
    $('#dateToFilter').val('');
    $('#leadsTable').DataTable().ajax.reload();
}

// Action functions
function viewLead(leadId) {
    window.location.href = '/admin/crm/leads/' + leadId;
}

function updateStatus(leadId) {
    console.log('Update status for lead:', leadId);
    // TODO: Implement status update modal
}

function activateLead(leadId) {
    console.log('Activate lead:', leadId);
    // TODO: Implement activation
}

function bulkActivateSelected() {
    var selectedLeads = $('.lead-checkbox:checked').map(function() {
        return this.value;
    }).get();
    
    console.log('Bulk activate leads:', selectedLeads);
    // TODO: Implement bulk activation
}

function exportLeads() {
    console.log('Export leads');
    // TODO: Implement export
}
</script>
@endpush
@endsection
