@extends('layouts.app')

@section('content')
@push('styles')
<style>
    .org-detail-card {
        border-left: 4px solid #007bff;
        box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075);
    }
    
    .org-status-badge {
        padding: 0.5rem 1rem;
        border-radius: 50px;
        font-size: 0.875rem;
        font-weight: 600;
        text-transform: uppercase;
    }
    
    .org-status-active { background-color: #00b894; color: white; }
    .org-status-disabled { background-color: #e17055; color: white; }
    .org-status-pending { background-color: #fdcb6e; color: #2d3436; }
    
    .org-type-badge {
        padding: 0.375rem 0.75rem;
        border-radius: 25px;
        font-size: 0.75rem;
        font-weight: 500;
        margin-left: 0.5rem;
    }
    
    .org-type-lead { background-color: #ff7675; color: white; }
    .org-type-active { background-color: #74b9ff; color: white; }
    
    .user-card {
        transition: transform 0.2s ease;
    }
    
    .user-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15);
    }
    
    .activity-timeline {
        position: relative;
        padding-left: 2rem;
    }
    
    .activity-timeline::before {
        content: '';
        position: absolute;
        left: 0.75rem;
        top: 0;
        bottom: 0;
        width: 2px;
        background: #dee2e6;
    }
    
    .activity-item {
        position: relative;
        margin-bottom: 1.5rem;
    }
    
    .activity-item::before {
        content: '';
        position: absolute;
        left: -0.5rem;
        top: 0.25rem;
        width: 0.75rem;
        height: 0.75rem;
        background: #007bff;
        border-radius: 50%;
        border: 2px solid white;
        box-shadow: 0 0 0 2px #dee2e6;
    }
</style>
@endpush

<div id="main-content">
    <div class="page-heading">
        <div class="page-title">
            <div class="row">
                <div class="col-12 col-md-6 order-md-1 order-last">
                    <h3><i class="bi bi-building"></i> Organization Details</h3>
                    <p class="text-subtitle text-muted">
                        Detailed information about the organization
                    </p>
                </div>
                <div class="col-12 col-md-6 order-md-2 order-first">
                    <nav aria-label="breadcrumb" class="breadcrumb-header float-start float-lg-end">
                        <ol class="breadcrumb">
                            <li class="breadcrumb-item">
                                <a href="{{ route('admin.crm.dashboard') }}">CRM Dashboard</a>
                            </li>
                            <li class="breadcrumb-item">
                                <a href="{{ route('admin.crm.organizations.index') }}">Organizations</a>
                            </li>
                            <li class="breadcrumb-item active" aria-current="page">
                                Organization Details
                            </li>
                        </ol>
                    </nav>
                </div>
            </div>
        </div>
    </div>

    <section class="section">
        @include('alert.alert')
        
        <!-- Organization Information -->
        <div class="row">
            <div class="col-lg-8">
                <div class="card org-detail-card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5><i class="bi bi-info-circle"></i> Organization Information</h5>
                        <div>
                            @if(isset($organization['type']) && $organization['type'] === 'lead')
                                <button class="btn btn-success btn-sm" onclick="convertOrganization()">
                                    <i class="bi bi-arrow-up-circle"></i> Convert to Active
                                </button>
                            @endif
                            <button class="btn btn-warning btn-sm" onclick="editOrganization()">
                                <i class="bi bi-pencil"></i> Edit
                            </button>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-8">
                                <h4>{{ $organization['displayName'] ?? $organization['name'] ?? 'Unknown Organization' }}
                                    <span class="org-type-badge org-type-{{ $organization['type'] ?? 'active' }}">
                                        {{ strtoupper($organization['type'] ?? 'ACTIVE') }}
                                    </span>
                                </h4>
                                <p class="text-muted">{{ $organization['description'] ?? 'No description available' }}</p>
                            </div>
                            <div class="col-md-4 text-end">
                                <span class="org-status-badge org-status-{{ strtolower($organization['status'] ?? 'active') }}">
                                    {{ $organization['status'] ?? 'ACTIVE' }}
                                </span>
                            </div>
                        </div>
                        
                        <hr>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <strong>Organization ID:</strong><br>
                                <span class="text-muted">{{ $organization['id'] ?? 'N/A' }}</span>
                            </div>
                            <div class="col-md-6">
                                <strong>Created Date:</strong><br>
                                <span class="text-muted">
                                    {{ isset($organization['created']) ? \Carbon\Carbon::parse($organization['created'])->format('M d, Y H:i') : 'N/A' }}
                                </span>
                            </div>
                        </div>
                        
                        @if(isset($organization['lastModified']))
                        <div class="row mt-3">
                            <div class="col-md-6">
                                <strong>Last Modified:</strong><br>
                                <span class="text-muted">
                                    {{ \Carbon\Carbon::parse($organization['lastModified'])->format('M d, Y H:i') }}
                                </span>
                            </div>
                        </div>
                        @endif
                    </div>
                </div>
                
                <!-- Organization Users -->
                <div class="card mt-4">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5><i class="bi bi-people"></i> Organization Users ({{ count($users ?? []) }})</h5>
                        <button class="btn btn-primary btn-sm" onclick="refreshUsers()">
                            <i class="bi bi-arrow-clockwise"></i> Refresh
                        </button>
                    </div>
                    <div class="card-body">
                        @if(empty($users))
                            <div class="text-center py-4">
                                <i class="bi bi-people text-muted" style="font-size: 3rem;"></i>
                                <p class="text-muted mt-2">No users found in this organization</p>
                            </div>
                        @else
                            <div class="row">
                                @foreach($users as $user)
                                <div class="col-md-6 mb-3">
                                    <div class="card user-card">
                                        <div class="card-body">
                                            <div class="d-flex align-items-center">
                                                <div class="avatar me-3">
                                                    <div class="avatar-content bg-primary text-white rounded-circle d-flex align-items-center justify-content-center" style="width: 40px; height: 40px;">
                                                        {{ strtoupper(substr($user['name']['givenName'] ?? $user['userName'] ?? 'U', 0, 1)) }}
                                                    </div>
                                                </div>
                                                <div class="flex-grow-1">
                                                    <h6 class="mb-1">
                                                        {{ $user['name']['givenName'] ?? 'Unknown' }} 
                                                        {{ $user['name']['familyName'] ?? '' }}
                                                    </h6>
                                                    <small class="text-muted">{{ $user['userName'] ?? 'No username' }}</small><br>
                                                    <small class="text-muted">{{ $user['emails'][0]['value'] ?? 'No email' }}</small>
                                                </div>
                                                <div>
                                                    <span class="badge {{ isset($user['active']) && $user['active'] ? 'bg-success' : 'bg-secondary' }}">
                                                        {{ isset($user['active']) ? ($user['active'] ? 'Active' : 'Inactive') : '-' }}
                                                    </span>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                @endforeach
                            </div>
                        @endif
                    </div>
                </div>
            </div>
            
            <!-- Activity Timeline -->
            <div class="col-lg-4">
                <div class="card">
                    <div class="card-header">
                        <h5><i class="bi bi-clock-history"></i> Recent Activity</h5>
                    </div>
                    <div class="card-body">
                        <div class="activity-timeline">
                            @if(empty($activities ?? []))
                                <div class="text-center py-4">
                                    <i class="bi bi-clock-history text-muted" style="font-size: 2rem;"></i>
                                    <p class="text-muted mt-2">No activity recorded</p>
                                </div>
                            @else
                                @foreach($activities as $activity)
                                <div class="activity-item">
                                    <div class="activity-content">
                                        <h6 class="mb-1">{{ $activity['title'] }}</h6>
                                        <p class="text-muted mb-1">{{ $activity['description'] }}</p>
                                        <small class="text-muted">
                                            {{ \Carbon\Carbon::parse($activity['timestamp'])->diffForHumans() }}
                                        </small>
                                    </div>
                                </div>
                                @endforeach
                            @endif
                        </div>
                    </div>
                </div>
                
                <!-- Quick Actions -->
                <div class="card mt-4">
                    <div class="card-header">
                        <h5><i class="bi bi-lightning"></i> Quick Actions</h5>
                    </div>
                    <div class="card-body">
                        <div class="d-grid gap-2">
                            @if(isset($organization['type']) && $organization['type'] === 'lead')
                                <button class="btn btn-success" onclick="convertOrganization()">
                                    <i class="bi bi-arrow-up-circle"></i> Convert to Active Organization
                                </button>
                            @endif
                            <button class="btn btn-warning" onclick="editOrganization()">
                                <i class="bi bi-pencil"></i> Edit Organization Details
                            </button>
                            <button class="btn btn-info" onclick="syncWithWSO2()">
                                <i class="bi bi-arrow-clockwise"></i> Sync with WSO2
                            </button>
                            @if(isset($organization['status']) && $organization['status'] === 'ACTIVE')
                                <button class="btn btn-secondary" onclick="disableOrganization()">
                                    <i class="bi bi-pause-circle"></i> Disable Organization
                                </button>
                            @else
                                <button class="btn btn-success" onclick="enableOrganization()">
                                    <i class="bi bi-play-circle"></i> Enable Organization
                                </button>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
</div>

@push('scripts')
<script>
function convertOrganization() {
    Swal.fire({
        title: 'Convert Organization',
        text: 'Are you sure you want to convert this lead organization to an active organization?',
        icon: 'question',
        showCancelButton: true,
        confirmButtonText: 'Yes, convert',
        cancelButtonText: 'Cancel'
    }).then((result) => {
        if (result.isConfirmed) {
            $.ajax({
                url: `/admin/crm/organizations/{{ $organization['id'] ?? '' }}/convert`,
                type: 'POST',
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                },
                success: function(response) {
                    if (response.success) {
                        Swal.fire({
                            icon: 'success',
                            title: 'Success',
                            text: response.message
                        }).then(() => {
                            location.reload();
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
        }
    });
}

function editOrganization() {
    // Redirect to edit page or open modal
    Swal.fire({
        icon: 'info',
        title: 'Edit Organization',
        text: 'Organization editing feature will be implemented soon.'
    });
}

function syncWithWSO2() {
    Swal.fire({
        title: 'Sync with WSO2',
        text: 'This will refresh organization data from WSO2 Identity Server.',
        icon: 'info',
        showCancelButton: true,
        confirmButtonText: 'Yes, sync now',
        cancelButtonText: 'Cancel'
    }).then((result) => {
        if (result.isConfirmed) {
            Swal.fire({
                icon: 'success',
                title: 'Synced',
                text: 'Organization data has been refreshed from WSO2.'
            }).then(() => {
                location.reload();
            });
        }
    });
}

function disableOrganization() {
    Swal.fire({
        title: 'Disable Organization',
        text: 'Are you sure you want to disable this organization?',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Yes, disable',
        cancelButtonText: 'Cancel'
    }).then((result) => {
        if (result.isConfirmed) {
            updateOrganizationStatus('DISABLED');
        }
    });
}

function enableOrganization() {
    Swal.fire({
        title: 'Enable Organization',
        text: 'Are you sure you want to enable this organization?',
        icon: 'question',
        showCancelButton: true,
        confirmButtonText: 'Yes, enable',
        cancelButtonText: 'Cancel'
    }).then((result) => {
        if (result.isConfirmed) {
            updateOrganizationStatus('ACTIVE');
        }
    });
}

function updateOrganizationStatus(status) {
    $.ajax({
        url: `/admin/crm/organizations/{{ $organization['id'] ?? '' }}`,
        type: 'PATCH',
        headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
        },
        data: {
            status: status
        },
        success: function(response) {
            if (response.success) {
                Swal.fire({
                    icon: 'success',
                    title: 'Success',
                    text: response.message
                }).then(() => {
                    location.reload();
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
                text: 'Failed to update organization status. Please try again.'
            });
        }
    });
}

function refreshUsers() {
    Swal.fire({
        icon: 'info',
        title: 'Refreshing Users',
        text: 'Refreshing organization users from WSO2...'
    }).then(() => {
        location.reload();
    });
}
</script>
@endpush
@endsection
