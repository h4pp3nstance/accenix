@extends('layouts.app')

@section('title', 'Lead Detail')

@section('content')
<div class="container-fluid py-4">
    <!-- Page Header -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="page-header bg-white rounded-3 shadow-sm p-4">
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <div class="d-flex align-items-center mb-2">
                            <div class="avatar-lg bg-primary-soft rounded-circle me-3 d-flex align-items-center justify-content-center">
                                <i class="fas fa-user-tie text-primary fs-4"></i>
                            </div>
                            <div>
                                <h1 class="h3 mb-1 text-dark fw-bold">{{ $lead['name'] ?? 'Unknown Lead' }}</h1>
                                <div class="d-flex align-items-center gap-3">
                                    <span class="badge bg-{{ $lead['status'] === 'New' ? 'primary' : ($lead['status'] === 'Qualified' ? 'success' : 'warning') }} px-3 py-2">
                                        <i class="fas fa-circle me-1" style="font-size: 0.5rem;"></i>
                                        {{ $lead['status'] ?? 'Unknown' }}
                                    </span>
                                    <span class="text-muted">
                                        <i class="fas fa-clock me-1"></i>
                                        Updated {{ $lead['lastModified'] ?? 'N/A' }}
                                    </span>
                                </div>
                            </div>
                        </div>
                        <nav aria-label="breadcrumb">
                            <ol class="breadcrumb mb-0">
                                <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}" class="text-decoration-none">Dashboard</a></li>
                                <li class="breadcrumb-item"><a href="{{ route('admin.crm.leads.index') }}" class="text-decoration-none">Lead Management</a></li>
                                <li class="breadcrumb-item active text-muted">Lead Detail</li>
                            </ol>
                        </nav>
                    </div>
                    <div class="d-flex gap-2">
                        <button class="btn btn-outline-primary" onclick="window.print()">
                            <i class="fas fa-print me-2"></i>Print
                        </button>
                        <a href="{{ route('admin.crm.leads.index') }}" class="btn btn-secondary">
                            <i class="fas fa-arrow-left me-2"></i>Back to Leads
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-4">
        <!-- Main Content -->
        <div class="col-xl-8">
            <!-- Lead Overview -->
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-gradient-primary text-white border-0">
                    <div class="d-flex align-items-center">
                        <i class="fas fa-user-circle me-2"></i>
                        <h5 class="card-title mb-0">Lead Overview</h5>
                    </div>
                </div>
                <div class="card-body p-4">
                    <div class="row g-4">
                        <!-- Contact Information -->
                        <div class="col-md-6">
                            <div class="info-section">
                                <h6 class="section-title text-primary mb-3">
                                    <i class="fas fa-address-book me-2"></i>Contact Information
                                </h6>
                                <div class="info-grid">
                                    <div class="info-item">
                                        <label class="info-label">Contact Person</label>
                                        <div class="info-value">{{ $lead['contactPerson'] ?? 'Not specified' }}</div>
                                    </div>
                                    <div class="info-item">
                                        <label class="info-label">Email Address</label>
                                        <div class="info-value">
                                            @if(!empty($lead['email']))
                                                <a href="mailto:{{ $lead['email'] }}" class="text-primary text-decoration-none">
                                                    <i class="fas fa-envelope me-1"></i>
                                                    {{ $lead['email'] }}
                                                </a>
                                            @else
                                                <span class="text-muted">Not provided</span>
                                            @endif
                                        </div>
                                    </div>
                                    <div class="info-item">
                                        <label class="info-label">Phone Number</label>
                                        <div class="info-value">
                                            @if(!empty($lead['phone']))
                                                <a href="tel:{{ $lead['phone'] }}" class="text-primary text-decoration-none">
                                                    <i class="fas fa-phone me-1"></i>
                                                    {{ $lead['phone'] }}
                                                </a>
                                            @else
                                                <span class="text-muted">Not provided</span>
                                            @endif
                                        </div>
                                    </div>
                                    <div class="info-item">
                                        <label class="info-label">Address</label>
                                        <div class="info-value">
                                            @if(!empty($lead['address']))
                                                <i class="fas fa-map-marker-alt me-1 text-muted"></i>
                                                {{ $lead['address'] }}
                                            @else
                                                <span class="text-muted">Not provided</span>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Business Information -->
                        <div class="col-md-6">
                            <div class="info-section">
                                <h6 class="section-title text-success mb-3">
                                    <i class="fas fa-building me-2"></i>Business Information
                                </h6>
                                <div class="info-grid">
                                    <div class="info-item">
                                        <label class="info-label">Business Type</label>
                                        <div class="info-value">{{ $lead['businessType'] ?? 'Not specified' }}</div>
                                    </div>
                                    <div class="info-item">
                                        <label class="info-label">Current System</label>
                                        <div class="info-value">{{ $lead['currentSystem'] ?? 'Not specified' }}</div>
                                    </div>
                                    <div class="info-item">
                                        <label class="info-label">Lead Source</label>
                                        <div class="info-value">
                                            <span class="badge bg-light text-dark">{{ $lead['source'] ?? 'Unknown' }}</span>
                                        </div>
                                    </div>
                                    <div class="info-item">
                                        <label class="info-label">Account Status</label>
                                        <div class="info-value">{{ $lead['accountStatus'] ?? 'Not specified' }}</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Lead Timeline -->
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-gradient-info text-white border-0">
                    <div class="d-flex align-items-center">
                        <i class="fas fa-chart-line me-2"></i>
                        <h5 class="card-title mb-0">Lead Timeline & Progress</h5>
                    </div>
                </div>
                <div class="card-body p-4">
                    <div class="row g-4">
                        <div class="col-md-6">
                            <div class="timeline-section">
                                <h6 class="section-title text-info mb-3">
                                    <i class="fas fa-clock me-2"></i>Timeline
                                </h6>
                                <div class="info-grid">
                                    <div class="info-item">
                                        <label class="info-label">Date Created</label>
                                        <div class="info-value">{{ $lead['created'] ?? 'Not available' }}</div>
                                    </div>
                                    <div class="info-item">
                                        <label class="info-label">Last Modified</label>
                                        <div class="info-value">{{ $lead['lastModified'] ?? 'Not available' }}</div>
                                    </div>
                                    <div class="info-item">
                                        <label class="info-label">Days in Pipeline</label>
                                        <div class="info-value">
                                            <span class="badge bg-warning text-dark">{{ $lead['daysAsLead'] ?? '0' }} Days</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="status-section">
                                <h6 class="section-title text-warning mb-3">
                                    <i class="fas fa-flag me-2"></i>Current Status
                                </h6>
                                <div class="info-grid">
                                    <div class="info-item">
                                        <label class="info-label">Lead Status</label>
                                        <div class="info-value">{{ $lead['currentStatus'] ?? 'Not specified' }}</div>
                                    </div>
                                    <div class="info-item">
                                        <label class="info-label">Priority Level</label>
                                        <div class="info-value">
                                            <span class="badge bg-danger">Normal Priority</span>
                                        </div>
                                    </div>
                                    <div class="info-item">
                                        <label class="info-label">Next Action</label>
                                        <div class="info-value text-muted">Follow-up required</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Sidebar -->
        <div class="col-xl-4">
            <!-- Lead Score -->
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-gradient-warning text-white border-0">
                    <div class="d-flex align-items-center">
                        <i class="fas fa-star me-2"></i>
                        <h5 class="card-title mb-0">Lead Score</h5>
                    </div>
                </div>
                <div class="card-body text-center p-4">
                    <div class="score-display mb-4">
                        <div class="score-circle mx-auto mb-3" style="width: 120px; height: 120px;">
                            <div class="circle-progress" data-percentage="20">
                                <div class="circle-content">
                                    <div class="score-number">20</div>
                                    <div class="score-label">Score</div>
                                </div>
                            </div>
                        </div>
                        <div class="score-status">
                            <span class="badge bg-warning px-3 py-2">Needs Attention</span>
                        </div>
                    </div>
                    <div class="row text-center">
                        <div class="col-6">
                            <div class="metric-item">
                                <div class="metric-number text-primary">0</div>
                                <div class="metric-label">Interactions</div>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="metric-item">
                                <div class="metric-number text-success">0</div>
                                <div class="metric-label">Follow-ups</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Organization Info -->
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-gradient-secondary text-white border-0">
                    <div class="d-flex align-items-center">
                        <i class="fas fa-building me-2"></i>
                        <h5 class="card-title mb-0">Organization</h5>
                    </div>
                </div>
                <div class="card-body p-4">
                    <div class="info-grid">
                        <div class="info-item">
                            <label class="info-label">Organization ID</label>
                            <div class="info-value font-monospace">{{ $lead['organizationId'] ?? 'Not assigned' }}</div>
                        </div>
                        <div class="info-item">
                            <label class="info-label">Organization Name</label>
                            <div class="info-value fw-bold">{{ $lead['name'] ?? 'Not specified' }}</div>
                        </div>
                        <div class="info-item">
                            <label class="info-label">Organization Status</label>
                            <div class="info-value">
                                <span class="badge bg-success">{{ $lead['status'] ?? 'Active' }}</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Quick Actions -->
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-gradient-dark text-white border-0">
                    <div class="d-flex align-items-center">
                        <i class="fas fa-bolt me-2"></i>
                        <h5 class="card-title mb-0">Quick Actions</h5>
                    </div>
                </div>
                <div class="card-body p-4">
                    <div class="d-grid gap-2">
                        <button class="btn btn-success btn-action" data-bs-toggle="modal" data-bs-target="#convertModal">
                            <i class="fas fa-user-plus me-2"></i>Convert to Customer
                        </button>
                        <button class="btn btn-primary btn-action" data-bs-toggle="modal" data-bs-target="#followUpModal">
                            <i class="fas fa-calendar-plus me-2"></i>Schedule Follow-up
                        </button>
                        <button class="btn btn-info btn-action" data-bs-toggle="modal" data-bs-target="#addNoteModal">
                            <i class="fas fa-sticky-note me-2"></i>Add Note
                        </button>
                        <button class="btn btn-warning btn-action" data-bs-toggle="modal" data-bs-target="#updateStatusModal">
                            <i class="fas fa-edit me-2"></i>Update Status
                        </button>
                        <hr class="my-3">
                        <div class="row g-2">
                            <div class="col-6">
                                <button class="btn btn-outline-primary btn-sm w-100">
                                    <i class="fas fa-envelope me-1"></i>Send Email
                                </button>
                            </div>
                            <div class="col-6">
                                <button class="btn btn-outline-success btn-sm w-100">
                                    <i class="fas fa-phone me-1"></i>Make Call
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Activity Timeline (Full Width) -->
    <div class="row mt-4">
        <div class="col-12">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-gradient-success text-white border-0">
                    <div class="d-flex align-items-center">
                        <i class="fas fa-history me-2"></i>
                        <h5 class="card-title mb-0">Activity Timeline</h5>
                    </div>
                </div>
                <div class="card-body p-4">
                    @if(!empty($activities) && count($activities) > 0)
                        <div class="timeline-container">
                            @foreach($activities as $activity)
                                <div class="timeline-item">
                                    <div class="timeline-marker bg-success"></div>
                                    <div class="timeline-content">
                                        <div class="timeline-header">
                                            <h6 class="timeline-title">{{ $activity['title'] ?? 'Activity' }}</h6>
                                            <span class="timeline-time">{{ $activity['timestamp'] ?? 'Unknown time' }}</span>
                                        </div>
                                        <p class="timeline-description">{{ $activity['description'] ?? 'No description available' }}</p>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <div class="timeline-container">
                            <div class="timeline-item">
                                <div class="timeline-marker bg-primary"></div>
                                <div class="timeline-content">
                                    <div class="timeline-header">
                                        <h6 class="timeline-title">Lead Created</h6>
                                        <span class="timeline-time">07 Aug 2025, 09:56</span>
                                    </div>
                                    <p class="timeline-description">Lead was successfully created in the system</p>
                                    <span class="timeline-badge">System</span>
                                </div>
                            </div>
                            <div class="timeline-item">
                                <div class="timeline-marker bg-info"></div>
                                <div class="timeline-content">
                                    <div class="timeline-header">
                                        <h6 class="timeline-title">Lead Information Updated</h6>
                                        <span class="timeline-time">07 Aug 2025, 09:56</span>
                                    </div>
                                    <p class="timeline-description">Lead information was updated with current status</p>
                                    <span class="timeline-badge">System</span>
                                </div>
                            </div>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Include Modals -->
@include('admin.crm.leads.modals')
@endsection

@push('styles')
<style>
/* Custom Variables */
:root {
    --primary-gradient: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    --success-gradient: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
    --warning-gradient: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
    --info-gradient: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
    --secondary-gradient: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    --dark-gradient: linear-gradient(135deg, #2c3e50 0%, #34495e 100%);
    --border-radius: 12px;
    --box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
    --transition: all 0.3s ease;
}

/* Background Gradients */
.bg-gradient-primary { background: var(--primary-gradient) !important; }
.bg-gradient-success { background: var(--success-gradient) !important; }
.bg-gradient-warning { background: var(--warning-gradient) !important; }
.bg-gradient-info { background: var(--info-gradient) !important; }
.bg-gradient-secondary { background: var(--secondary-gradient) !important; }
.bg-gradient-dark { background: var(--dark-gradient) !important; }

/* Soft Background Colors */
.bg-primary-soft { 
    background: rgba(102, 126, 234, 0.1) !important; 
    color: var(--bs-primary) !important; 
}

/* Page Header */
.page-header {
    background: linear-gradient(135deg, #fff 0%, #f8f9fe 100%);
    border: 1px solid rgba(0, 0, 0, 0.05);
}

/* Cards */
.card {
    border-radius: var(--border-radius);
    box-shadow: var(--box-shadow);
    transition: var(--transition);
    border: none;
}

.card:hover {
    transform: translateY(-4px);
    box-shadow: 0 8px 30px rgba(0, 0, 0, 0.12);
}

.card-header {
    border-radius: var(--border-radius) var(--border-radius) 0 0 !important;
    border: none;
    padding: 1.25rem 1.5rem;
}

.card-body {
    padding: 1.5rem;
}

/* Avatar */
.avatar-lg {
    width: 4rem;
    height: 4rem;
}

/* Info Grid System */
.info-section {
    height: 100%;
}

.section-title {
    font-weight: 600;
    font-size: 0.95rem;
    border-bottom: 2px solid rgba(0, 0, 0, 0.1);
    padding-bottom: 0.5rem;
    margin-bottom: 1rem !important;
}

.info-grid {
    display: grid;
    gap: 1rem;
}

.info-item {
    display: flex;
    flex-direction: column;
    gap: 0.25rem;
}

.info-label {
    font-size: 0.8rem;
    font-weight: 600;
    color: #6c757d;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    margin: 0;
}

.info-value {
    font-size: 0.95rem;
    font-weight: 500;
    color: #2c3e50;
    line-height: 1.4;
}

/* Lead Score Circle */
.score-circle {
    position: relative;
    background: conic-gradient(#ffc107 0deg 72deg, #e9ecef 72deg 360deg);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
}

.circle-content {
    background: white;
    border-radius: 50%;
    width: 80%;
    height: 80%;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
}

.score-number {
    font-size: 2rem;
    font-weight: bold;
    color: #ffc107;
}

.score-label {
    font-size: 0.8rem;
    color: #6c757d;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

/* Metrics */
.metric-item {
    padding: 0.75rem;
    background: rgba(0, 0, 0, 0.02);
    border-radius: 8px;
}

.metric-number {
    font-size: 1.5rem;
    font-weight: bold;
    line-height: 1;
}

.metric-label {
    font-size: 0.75rem;
    color: #6c757d;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

/* Buttons */
.btn {
    border-radius: 8px;
    font-weight: 500;
    transition: var(--transition);
    padding: 0.75rem 1.25rem;
}

.btn-action {
    justify-content: flex-start;
    text-align: left;
}

.btn:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
}

/* Timeline */
.timeline-container {
    position: relative;
    padding-left: 2rem;
}

.timeline-container::before {
    content: '';
    position: absolute;
    left: 0.75rem;
    top: 0;
    bottom: 0;
    width: 2px;
    background: linear-gradient(to bottom, #e9ecef, #dee2e6);
}

.timeline-item {
    position: relative;
    margin-bottom: 2rem;
    padding-left: 2rem;
}

.timeline-marker {
    position: absolute;
    left: -2rem;
    top: 0.25rem;
    width: 12px;
    height: 12px;
    border-radius: 50%;
    border: 3px solid white;
    box-shadow: 0 0 0 1px rgba(0, 0, 0, 0.1);
}

.timeline-content {
    background: white;
    border-radius: 8px;
    padding: 1rem;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
    border: 1px solid rgba(0, 0, 0, 0.05);
}

.timeline-header {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    margin-bottom: 0.5rem;
}

.timeline-title {
    font-weight: 600;
    margin: 0;
    font-size: 0.95rem;
}

.timeline-time {
    font-size: 0.8rem;
    color: #6c757d;
    white-space: nowrap;
}

.timeline-description {
    margin: 0;
    color: #495057;
    line-height: 1.5;
}

.timeline-badge {
    display: inline-block;
    padding: 0.25rem 0.5rem;
    background: rgba(0, 123, 255, 0.1);
    color: #007bff;
    border-radius: 4px;
    font-size: 0.75rem;
    font-weight: 500;
    margin-top: 0.5rem;
}

/* Badges */
.badge {
    font-weight: 500;
    font-size: 0.8rem;
    padding: 0.5rem 0.75rem;
    border-radius: 6px;
}

/* Breadcrumb */
.breadcrumb {
    background: none;
    padding: 0;
}

.breadcrumb-item + .breadcrumb-item::before {
    content: "›";
    color: #6c757d;
}

/* Responsive Adjustments */
@media (max-width: 768px) {
    .info-grid {
        gap: 0.75rem;
    }
    
    .card-body {
        padding: 1rem;
    }
    
    .timeline-item {
        padding-left: 1.5rem;
        margin-bottom: 1.5rem;
    }
    
    .timeline-marker {
        left: -1.5rem;
    }
    
    .page-header {
        padding: 1.5rem !important;
    }
}

/* Print Styles */
@media print {
    .btn, .page-header .d-flex:last-child {
        display: none !important;
    }
    
    .card {
        box-shadow: none !important;
        border: 1px solid #dee2e6 !important;
    }
    
    .card-header {
        background: #f8f9fa !important;
        color: #000 !important;
    }
}
</style>
@endpush

@push('scripts')
<script>
$(document).ready(function() {
    // Initialize tooltips
    var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });

    // Add Note Modal
    $('#addNoteForm').on('submit', function(e) {
        e.preventDefault();
        
        var formData = {
            lead_id: {{ $lead['id'] ?? 0 }},
            note: $('#noteContent').val(),
            _token: $('meta[name="csrf-token"]').attr('content')
        };

        $.ajax({
            url: '{{ route("admin.crm.leads.add-note") }}',
            method: 'POST',
            data: formData,
            beforeSend: function() {
                $('#addNoteSubmit').prop('disabled', true).html('<i class="fas fa-spinner fa-spin me-2"></i>Adding...');
            },
            success: function(response) {
                if(response.success) {
                    $('#addNoteModal').modal('hide');
                    $('#addNoteForm')[0].reset();
                    
                    Swal.fire({
                        icon: 'success',
                        title: 'Success!',
                        text: 'Note has been added successfully',
                        timer: 1500,
                        showConfirmButton: false
                    }).then(() => {
                        location.reload();
                    });
                }
            },
            error: function(xhr) {
                Swal.fire({
                    icon: 'error',
                    title: 'Error!',
                    text: 'Failed to add note. Please try again.'
                });
            },
            complete: function() {
                $('#addNoteSubmit').prop('disabled', false).html('<i class="fas fa-save me-2"></i>Add Note');
            }
        });
    });

    // Follow-up Modal
    $('#followUpForm').on('submit', function(e) {
        e.preventDefault();
        
        var formData = {
            lead_id: {{ $lead['id'] ?? 0 }},
            type: $('#followUpType').val(),
            date: $('#followUpDate').val(),
            time: $('#followUpTime').val(),
            notes: $('#followUpNotes').val(),
            _token: $('meta[name="csrf-token"]').attr('content')
        };

        $.ajax({
            url: '{{ route("admin.crm.leads.schedule-followup") }}',
            method: 'POST',
            data: formData,
            beforeSend: function() {
                $('#followUpSubmit').prop('disabled', true).html('<i class="fas fa-spinner fa-spin me-2"></i>Scheduling...');
            },
            success: function(response) {
                if(response.success) {
                    $('#followUpModal').modal('hide');
                    $('#followUpForm')[0].reset();
                    
                    Swal.fire({
                        icon: 'success',
                        title: 'Success!',
                        text: 'Follow-up has been scheduled successfully',
                        timer: 1500,
                        showConfirmButton: false
                    }).then(() => {
                        location.reload();
                    });
                }
            },
            error: function(xhr) {
                Swal.fire({
                    icon: 'error',
                    title: 'Error!',
                    text: 'Failed to schedule follow-up. Please try again.'
                });
            },
            complete: function() {
                $('#followUpSubmit').prop('disabled', false).html('<i class="fas fa-calendar-plus me-2"></i>Schedule Follow-up');
            }
        });
    });

    // Update Status Modal
    $('#updateStatusForm').on('submit', function(e) {
        e.preventDefault();
        
        var formData = {
            lead_id: {{ $lead['id'] ?? 0 }},
            status: $('#newStatus').val(),
            notes: $('#statusNotes').val(),
            _token: $('meta[name="csrf-token"]').attr('content')
        };

        $.ajax({
            url: '{{ route("admin.crm.leads.update-status") }}',
            method: 'POST',
            data: formData,
            beforeSend: function() {
                $('#updateStatusSubmit').prop('disabled', true).html('<i class="fas fa-spinner fa-spin me-2"></i>Updating...');
            },
            success: function(response) {
                if(response.success) {
                    $('#updateStatusModal').modal('hide');
                    $('#updateStatusForm')[0].reset();
                    
                    Swal.fire({
                        icon: 'success',
                        title: 'Success!',
                        text: 'Lead status has been updated successfully',
                        timer: 1500,
                        showConfirmButton: false
                    }).then(() => {
                        location.reload();
                    });
                }
            },
            error: function(xhr) {
                Swal.fire({
                    icon: 'error',
                    title: 'Error!',
                    text: 'Failed to update status. Please try again.'
                });
            },
            complete: function() {
                $('#updateStatusSubmit').prop('disabled', false).html('<i class="fas fa-save me-2"></i>Update Status');
            }
        });
    });

    // Convert Lead Modal
    $('#convertSubmit').on('click', function(e) {
        e.preventDefault();
        
        var formData = {
            lead_id: $('#convertLeadId').val(),
            organization_name: $('#organizationName').val(),
            customer_name: $('#customerName').val(),
            customer_email: $('#customerEmail').val(),
            notes: $('#conversionNotes').val(),
            _token: $('meta[name="csrf-token"]').attr('content')
        };

        // Validate required fields
        if (!formData.organization_name || !formData.customer_name) {
            Swal.fire({
                icon: 'error',
                title: 'Validation Error!',
                text: 'Organization name and customer name are required.'
            });
            return;
        }

        $.ajax({
            url: `/admin/crm/leads/${formData.lead_id}/convert`,
            method: 'POST',
            data: formData,
            beforeSend: function() {
                $('#convertSubmit').prop('disabled', true).html('<i class="fas fa-spinner fa-spin me-2"></i>Converting...');
            },
            success: function(response) {
                if(response.success) {
                    $('#convertModal').modal('hide');
                    $('#convertForm')[0].reset();
                    
                    Swal.fire({
                        icon: 'success',
                        title: 'Success!',
                        text: response.message || 'Lead has been converted to customer successfully! User credentials sent via email.',
                        timer: 3000,
                        showConfirmButton: false
                    }).then(() => {
                        location.reload();
                    });
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'Conversion Failed!',
                        text: response.message || 'Failed to convert lead'
                    });
                }
            },
            error: function(xhr) {
                console.error('Convert error:', xhr);
                let errorMessage = 'Failed to convert lead. Please try again.';
                if (xhr.responseJSON && xhr.responseJSON.message) {
                    errorMessage = xhr.responseJSON.message;
                }
                
                Swal.fire({
                    icon: 'error',
                    title: 'Error!',
                    text: errorMessage
                });
            },
            complete: function() {
                $('#convertSubmit').prop('disabled', false).html('<i class="fas fa-user-plus me-1"></i>Convert to Customer');
            }
        });
    });

    // Enhanced hover effects
    $('.card').hover(
        function() {
            $(this).addClass('shadow-lg');
        },
        function() {
            $(this).removeClass('shadow-lg');
        }
    );
});
</script>
@endpush
