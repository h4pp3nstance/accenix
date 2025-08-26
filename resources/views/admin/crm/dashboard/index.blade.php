@extends('layouts.app')

@section('content')
@push('styles')
<style>
    .stats-card {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        border-radius: 15px;
        padding: 1.5rem;
        margin-bottom: 1.5rem;
        box-shadow: 0 4px 15px rgba(0,0,0,0.1);
    }
    
    .stats-card.success {
        background: linear-gradient(135deg, #11998e 0%, #38ef7d 100%);
    }
    
    .stats-card.warning {
        background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
    }
    
    .stats-card.info {
        background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
    }
    
    .stats-value {
        font-size: 2.5rem;
        font-weight: bold;
        margin-bottom: 0.5rem;
    }
    
    .stats-label {
        font-size: 0.9rem;
        opacity: 0.9;
        text-transform: uppercase;
        letter-spacing: 1px;
    }
    
    .activity-item {
        border-left: 3px solid #007bff;
        padding-left: 1rem;
        margin-bottom: 1rem;
    }
    
    .pending-activation {
        background: #f8f9fa;
        border-radius: 8px;
        padding: 1rem;
        margin-bottom: 0.5rem;
    }
    
    .chart-container {
        height: 300px;
        margin: 1rem 0;
    }
</style>
@endpush

<div id="main-content">
    <div class="page-heading">
        <div class="page-title">
            <div class="row">
                <div class="col-12 col-md-6 order-md-1 order-last">
                    <h3><i class="bi bi-speedometer2"></i> CRM Dashboard</h3>
                    <p class="text-subtitle text-muted">
                        Lead and Organization Management Overview
                    </p>
                </div>
                <div class="col-12 col-md-6 order-md-2 order-first">
                    <nav aria-label="breadcrumb" class="breadcrumb-header float-start float-lg-end">
                        <ol class="breadcrumb">
                            <li class="breadcrumb-item">
                                <a href="/dashboard">Dashboard</a>
                            </li>
                            <li class="breadcrumb-item active" aria-current="page">
                                CRM Dashboard
                            </li>
                        </ol>
                    </nav>
                </div>
            </div>
        </div>
    </div>

    <section class="section">
        @include('alert.alert')
        
        <!-- Stats Cards Row -->
        <div class="row">
            <!-- Lead Statistics -->
            <div class="col-xl-3 col-md-6">
                <div class="stats-card">
                    <div class="stats-value">{{ $leadStats['total_leads'] ?? 0 }}</div>
                    <div class="stats-label">Total Leads</div>
                    <small>{{ $leadStats['this_month'] ?? 0 }} this month</small>
                </div>
            </div>
            
            <div class="col-xl-3 col-md-6">
                <div class="stats-card success">
                    <div class="stats-value">{{ $leadStats['converted_leads'] ?? 0 }}</div>
                    <div class="stats-label">Converted Leads</div>
                    <small>
                        @if(($leadStats['total_leads'] ?? 0) > 0)
                            {{ round(($leadStats['converted_leads'] ?? 0) / $leadStats['total_leads'] * 100, 1) }}% conversion rate
                        @else
                            0% conversion rate
                        @endif
                    </small>
                </div>
            </div>
            
            <div class="col-xl-3 col-md-6">
                <div class="stats-card warning">
                    <div class="stats-value">{{ $leadStats['new_leads'] ?? 0 }}</div>
                    <div class="stats-label">New Leads</div>
                    <small>Require attention</small>
                </div>
            </div>
            
            <div class="col-xl-3 col-md-6">
                <div class="stats-card info">
                    <div class="stats-value">{{ $orgStats['active_organizations'] ?? 0 }}</div>
                    <div class="stats-label">Active Organizations</div>
                    <small>{{ $orgStats['pending_organizations'] ?? 0 }} pending</small>
                </div>
            </div>
        </div>

        <!-- Main Content Row -->
        <div class="row">
            <!-- Lead Pipeline Chart -->
            <div class="col-xl-8">
                <div class="card">
                    <div class="card-header">
                        <h5><i class="bi bi-bar-chart"></i> Lead Pipeline</h5>
                    </div>
                    <div class="card-body">
                        <div class="chart-container">
                            <canvas id="leadPipelineChart"></canvas>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Quick Actions -->
            <div class="col-xl-4">
                <div class="card">
                    <div class="card-header">
                        <h5><i class="bi bi-lightning"></i> Quick Actions</h5>
                    </div>
                    <div class="card-body">
                        <div class="d-grid gap-2">
                            <a href="{{ route('admin.crm.leads.index') }}" class="btn btn-primary">
                                <i class="bi bi-people"></i> Manage Leads
                            </a>
                            <a href="{{ route('admin.crm.organizations.index') }}" class="btn btn-info">
                                <i class="bi bi-building"></i> Manage Organizations
                            </a>
                            <button class="btn btn-success" onclick="bulkActivateUsers()">
                                <i class="bi bi-check-circle"></i> Bulk Activate Users
                            </button>
                            <a href="#" class="btn btn-warning">
                                <i class="bi bi-envelope"></i> Send Campaign Email
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
</div>

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
$(document).ready(function() {
    // Initialize Lead Pipeline Chart
    const ctx = document.getElementById('leadPipelineChart').getContext('2d');
    const leadPipelineChart = new Chart(ctx, {
        type: 'doughnut',
        data: {
            labels: ['New', 'Contacted', 'Qualified', 'Converted', 'Rejected'],
            datasets: [{
                data: [
                    {{ $leadStats['new_leads'] ?? 0 }},
                    {{ $leadStats['active_leads'] ?? 0 }},
                    0, // Qualified - to be implemented
                    {{ $leadStats['converted_leads'] ?? 0 }},
                    0  // Rejected - to be implemented
                ],
                backgroundColor: [
                    '#ff6384',
                    '#36a2eb',
                    '#ffce56',
                    '#4bc0c0',
                    '#9966ff'
                ],
                borderWidth: 2,
                borderColor: '#ffffff'
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'bottom'
                }
            }
        }
    });
    
    // Auto-refresh data every 5 minutes
    setInterval(function() {
        location.reload();
    }, 300000); // 5 minutes
});

// Activate single user
function activateUser(userId) {
    if (!confirm('Are you sure you want to activate this user?')) {
        return;
    }
    
    $.ajax({
        url: '/admin/crm/leads/' + userId + '/activate',
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
                text: 'Failed to activate user. Please try again.'
            });
        }
    });
}

// Bulk activate users
function bulkActivateUsers() {
    Swal.fire({
        title: 'Bulk Activate Users',
        text: 'This will activate all pending users. Are you sure?',
        icon: 'question',
        showCancelButton: true,
        confirmButtonText: 'Yes, activate all',
        cancelButtonText: 'Cancel'
    }).then((result) => {
        if (result.isConfirmed) {
            // TODO: Implement bulk activation
            Swal.fire({
                icon: 'info',
                title: 'Feature Coming Soon',
                text: 'Bulk activation feature will be implemented soon.'
            });
        }
    });
}
</script>
@endpush
@endsection
