@extends('layouts.app')

@section('content')
@push('styles')
<style>
    .org-status-badge {
        padding: 0.375rem 0.75rem;
        border-radius: 50px;
        font-size: 0.75rem;
        font-weight: 600;
        text-transform: uppercase;
    }
    
    .org-status-active { background-color: #00b894; color: white; }
    .org-status-disabled { background-color: #e17055; color: white; }
    .org-status-pending { background-color: #fdcb6e; color: #2d3436; }
    
    .org-type-badge {
        padding: 0.25rem 0.5rem;
        border-radius: 20px;
        font-size: 0.7rem;
        font-weight: 500;
    }
    
    .org-type-lead { background-color: #ff7675; color: white; }
    .org-type-active { background-color: #74b9ff; color: white; }
    
    .organization-info {
        border-left: 4px solid #007bff;
        padding-left: 1rem;
    }
    
    .org-actions .btn {
        margin-right: 0.25rem;
        margin-bottom: 0.25rem;
    }
</style>
@endpush

<div id="main-content">
    <div class="page-heading">
        <div class="page-title">
            <div class="row">
                <div class="col-12 col-md-6 order-md-1 order-last">
                    <h3><i class="bi bi-building"></i> Organization Management</h3>
                    <p class="text-subtitle text-muted">
                        Manage lead organizations and active organizations
                    </p>
                </div>
                <div class="col-12 col-md-6 order-md-2 order-first">
                    <nav aria-label="breadcrumb" class="breadcrumb-header float-start float-lg-end">
                        <ol class="breadcrumb">
                            <li class="breadcrumb-item">
                                <a href="{{ route('admin.crm.dashboard') }}">CRM Dashboard</a>
                            </li>
                            <li class="breadcrumb-item active" aria-current="page">
                                Organization Management
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
                        <label for="typeFilter" class="form-label">Filter by Type</label>
                        <select id="typeFilter" class="form-select">
                            <option value="">All Organizations</option>
                            <option value="leads">Lead Organizations</option>
                            <option value="active">Active Organizations</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label for="statusFilter" class="form-label">Filter by Status</label>
                        <select id="statusFilter" class="form-select">
                            <option value="">All Statuses</option>
                            <option value="ACTIVE">Active</option>
                            <option value="DISABLED">Disabled</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label for="searchFilter" class="form-label">Search Organization</label>
                        <input type="text" id="searchFilter" class="form-control" placeholder="Search by name...">
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

        <!-- Statistics Cards -->
        <div class="row mb-4">
            <div class="col-md-3">
                <div class="card text-center" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white;">
                    <div class="card-body">
                        <h4 id="totalOrgs">-</h4>
                        <p>Total Organizations</p>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card text-center" style="background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%); color: white;">
                    <div class="card-body">
                        <h4 id="leadOrgs">-</h4>
                        <p>Lead Organizations</p>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card text-center" style="background: linear-gradient(135deg, #11998e 0%, #38ef7d 100%); color: white;">
                    <div class="card-body">
                        <h4 id="activeOrgs">-</h4>
                        <p>Active Organizations</p>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card text-center" style="background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%); color: white;">
                    <div class="card-body">
                        <h4 id="conversionRate">-</h4>
                        <p>Conversion Rate</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Organizations Table -->
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5><i class="bi bi-table"></i> Organizations Overview</h5>
                <div>
                    <button class="btn btn-success" onclick="bulkConvertSelected()">
                        <i class="bi bi-arrow-up-circle"></i> Bulk Convert
                    </button>
                    <button class="btn btn-info" onclick="syncFromWSO2()">
                        <i class="bi bi-arrow-clockwise"></i> Sync WSO2
                    </button>
                </div>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-striped table-hover" id="organizationsTable">
                        <thead>
                            <tr>
                                <th width="5%">
                                    <input type="checkbox" id="selectAll">
                                </th>
                                <th width="5%">No</th>
                                <th width="25%">Organization Name</th>
                                <th width="15%">Type</th>
                                <th width="10%">Status</th>
                                <!-- <th width="10%">Users</th> -->
                                <!-- <th width="15%">Created</th> -->
                                <th width="15%">Actions</th>
                            </tr>
                        </thead>
                    </table>
                </div>
            </div>
        </div>
    </section>
</div>

<!-- Convert Organization Modal -->
<div class="modal fade" id="convertModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Convert Lead Organization</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="convertForm">
                <div class="modal-body">
                    <input type="hidden" id="orgId">
                    <div class="alert alert-info">
                        <i class="bi bi-info-circle"></i>
                        <strong>Converting Lead Organization:</strong>
                        <ul class="mb-0 mt-2">
                            <li>Remove "lead-" prefix from organization name</li>
                            <li>Change status to Active</li>
                            <li>Activate associated users</li>
                            <li>Update organization description</li>
                        </ul>
                    </div>
                    <div class="mb-3">
                        <label for="newOrgName" class="form-label">New Organization Name</label>
                        <input type="text" id="newOrgName" class="form-control" readonly>
                        <div class="form-text">The "lead-" prefix will be automatically removed</div>
                    </div>
                    <div class="mb-3">
                        <label for="conversionNotes" class="form-label">Conversion Notes</label>
                        <textarea id="conversionNotes" class="form-control" rows="3" 
                                  placeholder="Add notes about this conversion..."></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-success">Convert Organization</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Edit Organization Modal -->
<div class="modal fade" id="editModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Edit Organization</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="editForm">
                <div class="modal-body">
                    <input type="hidden" id="editOrgId">
                    <div class="mb-3">
                        <label for="editOrgName" class="form-label">Organization Name</label>
                        <input type="text" id="editOrgName" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label for="editOrgDescription" class="form-label">Description</label>
                        <textarea id="editOrgDescription" class="form-control" rows="3"></textarea>
                    </div>
                    <div class="mb-3">
                        <label for="editOrgStatus" class="form-label">Status</label>
                        <select id="editOrgStatus" class="form-select" required>
                            <option value="ACTIVE">Active</option>
                            <option value="DISABLED">Disabled</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Update Organization</button>
                </div>
            </form>
        </div>
    </div>
</div>

@push('scripts')
<script>
$(document).ready(function() {
    // Initialize DataTable
    const table = $('#organizationsTable').DataTable({
        processing: true,
        serverSide: true,
        ajax: {
            url: '{{ route('admin.crm.organizations.data') }}',
            data: function(d) {
                d.type = $('#typeFilter').val();
                d.status = $('#statusFilter').val();
                d.search = $('#searchFilter').val();
            }
        },
        columns: [
            {
                data: null,
                orderable: false,
                render: function(data, type, row) {
                    if (row.type === 'lead') {
                        return `<input type="checkbox" class="org-checkbox" value="${row.id}">`;
                    }
                    return '';
                }
            },
            {
                data: null,
                render: function(data, type, row, meta) {
                    return meta.row + meta.settings._iDisplayStart + 1;
                }
            },
            {
                data: null,
                render: function(data, type, row) {
                    return `
                        <div class="organization-info">
                            <strong>${row.displayName || row.name}</strong><br>
                            <small class="text-muted">${row.description || 'No description'}</small>
                        </div>
                    `;
                }
            },
            {
                data: 'type',
                render: function(data, type, row) {
                    return `<span class="org-type-badge org-type-${data}">${data.toUpperCase()}</span>`;
                }
            },
            {
                data: 'status',
                render: function(data, type, row) {
                    const status = data || 'ACTIVE';
                    return `<span class="org-status-badge org-status-${status.toLowerCase()}">${status}</span>`;
                }
            },
            // {
            //     data: 'userCount',
            //     defaultContent: '0'
            // },
            // {
            //     data: 'created',
            //     render: function(data, type, row) {
            //         if (data) {
            //             const date = new Date(data);
            //             return date.toLocaleDateString();
            //         }
            //         return 'N/A';
            //     }
            // },
            {
                data: null,
                orderable: false,
                render: function(data, type, row) {
                    let actions = `
                        <div class="org-actions">
                            <a href="{{ route('admin.crm.organizations.show', ':id') }}" 
                               class="btn btn-sm btn-info" title="View Details">
                                <i class="bi bi-eye"></i>
                            </a>
                            <button onclick="editOrganization('${row.id}')" 
                                    class="btn btn-sm btn-warning" title="Edit">
                                <i class="bi bi-pencil"></i>
                            </button>
                    `.replace(':id', row.id);
                    
                    if (row.type === 'lead') {
                        actions += `
                            <button onclick="convertOrganization('${row.id}', '${row.name}')" 
                                    class="btn btn-sm btn-success" title="Convert to Active">
                                <i class="bi bi-arrow-up-circle"></i>
                            </button>
                        `;
                    }
                    
                    actions += '</div>';
                    return actions;
                }
            }
        ],
        order: [[6, 'desc']], // Order by created date
        pageLength: 25,
        responsive: true,
        drawCallback: function() {
            updateStatistics();
        }
    });
    
    // Select all checkbox
    $('#selectAll').on('change', function() {
        $('.org-checkbox').prop('checked', this.checked);
    });
    
    // Convert form submission
    $('#convertForm').on('submit', function(e) {
        e.preventDefault();
        
        const orgId = $('#orgId').val();
        const notes = $('#conversionNotes').val();
        
        $.ajax({
            url: `/admin/crm/organizations/${orgId}/convert`,
            type: 'POST',
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            },
            data: {
                notes: notes
            },
            success: function(response) {
                if (response.success) {
                    $('#convertModal').modal('hide');
                    table.ajax.reload();
                    Swal.fire({
                        icon: 'success',
                        title: 'Success',
                        text: response.message
                    });
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: response.message
                    });
                }
            },
            error: function(xhr) {
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: 'Failed to convert organization. Please try again.'
                });
            }
        });
    });
    
    // Edit form submission
    $('#editForm').on('submit', function(e) {
        e.preventDefault();
        
        const orgId = $('#editOrgId').val();
        const name = $('#editOrgName').val();
        const description = $('#editOrgDescription').val();
        const status = $('#editOrgStatus').val();
        
        $.ajax({
            url: `/admin/crm/organizations/${orgId}`,
            type: 'PATCH',
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            },
            data: {
                name: name,
                description: description,
                status: status
            },
            success: function(response) {
                if (response.success) {
                    $('#editModal').modal('hide');
                    table.ajax.reload();
                    Swal.fire({
                        icon: 'success',
                        title: 'Success',
                        text: response.message
                    });
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: response.message
                    });
                }
            },
            error: function(xhr) {
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: 'Failed to update organization. Please try again.'
                });
            }
        });
    });
});

// Filter functions
function applyFilters() {
    $('#organizationsTable').DataTable().ajax.reload();
}

function clearFilters() {
    $('#typeFilter').val('');
    $('#statusFilter').val('');
    $('#searchFilter').val('');
    $('#organizationsTable').DataTable().ajax.reload();
}

// Action functions
function convertOrganization(orgId, orgName) {
    $('#orgId').val(orgId);
    $('#newOrgName').val(orgName.replace('lead-', ''));
    $('#convertModal').modal('show');
}

function editOrganization(orgId) {
    // Get organization details and populate form
    // This would require an API endpoint to get organization details
    $('#editOrgId').val(orgId);
    $('#editModal').modal('show');
}

function bulkConvertSelected() {
    const selectedOrgs = $('.org-checkbox:checked').map(function() {
        return this.value;
    }).get();
    
    if (selectedOrgs.length === 0) {
        Swal.fire({
            icon: 'warning',
            title: 'No Selection',
            text: 'Please select lead organizations to convert.'
        });
        return;
    }
    
    Swal.fire({
        title: 'Bulk Convert',
        text: `Are you sure you want to convert ${selectedOrgs.length} lead organizations to active?`,
        icon: 'question',
        showCancelButton: true,
        confirmButtonText: 'Yes, convert all',
        cancelButtonText: 'Cancel'
    }).then((result) => {
        if (result.isConfirmed) {
            // TODO: Implement bulk conversion
            Swal.fire({
                icon: 'info',
                title: 'Feature Coming Soon',
                text: 'Bulk conversion feature will be implemented soon.'
            });
        }
    });
}

function syncFromWSO2() {
    Swal.fire({
        title: 'Sync from WSO2',
        text: 'This will refresh organization data from WSO2 Identity Server.',
        icon: 'info',
        showCancelButton: true,
        confirmButtonText: 'Yes, sync now',
        cancelButtonText: 'Cancel'
    }).then((result) => {
        if (result.isConfirmed) {
            $('#organizationsTable').DataTable().ajax.reload();
            Swal.fire({
                icon: 'success',
                title: 'Synced',
                text: 'Organization data has been refreshed from WSO2.'
            });
        }
    });
}

function updateStatistics() {
    // Update statistics cards based on current data
    const table = $('#organizationsTable').DataTable();
    const data = table.rows({page: 'current'}).data();
    
    let totalOrgs = 0;
    let leadOrgs = 0;
    let activeOrgs = 0;
    
    for (let i = 0; i < data.length; i++) {
        totalOrgs++;
        if (data[i].type === 'lead') {
            leadOrgs++;
        } else {
            activeOrgs++;
        }
    }
    
    $('#totalOrgs').text(totalOrgs);
    $('#leadOrgs').text(leadOrgs);
    $('#activeOrgs').text(activeOrgs);
    
    const conversionRate = leadOrgs > 0 ? Math.round((activeOrgs / (leadOrgs + activeOrgs)) * 100) : 0;
    $('#conversionRate').text(conversionRate + '%');
}
</script>
@endpush
@endsection
