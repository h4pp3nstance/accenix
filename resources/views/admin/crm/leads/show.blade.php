@extends('layouts.app')

@section('title', 'Lead Detail')

@push('styles')
<style>
/* Custom Variables */
:root {
    --border-radius: 12px;
    --box-shadow: 0 2px 12px rgba(0, 0, 0, 0.06);
    --box-shadow-hover: 0 4px 20px rgba(0, 0, 0, 0.1);
    --transition: all 0.3s ease;
    --bg-soft: #f8f9fa;
    --border-soft: rgba(0, 0, 0, 0.08);
}

/* Page Header */
.page-header {
    background: linear-gradient(135deg, #fff 0%, #f8f9fe 100%);
    border: 1px solid var(--border-soft);
}

/* Soft Background Colors */
.bg-primary-soft { 
    background: rgba(13, 110, 253, 0.1) !important; 
    color: #0d6efd !important; 
}

/* Cards */
.card {
    border-radius: var(--border-radius);
    box-shadow: var(--box-shadow);
    transition: var(--transition);
    border: 1px solid var(--border-soft);
}

.card:hover {
    transform: translateY(-2px);
    box-shadow: var(--box-shadow-hover);
}

.card-header {
    border-radius: var(--border-radius) var(--border-radius) 0 0 !important;
    background: #fff !important;
    border-bottom: 1px solid var(--border-soft) !important;
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
    border-bottom: 2px solid var(--border-soft);
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

/* Simple Lead Score Circle */
.score-circle-simple {
    position: relative;
    width: 100px;
    height: 100px;
    margin: 0 auto;
}

.score-content {
    position: absolute;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
    text-align: center;
    z-index: 2;
}

.score-number {
    font-size: 1.75rem;
    font-weight: bold;
    color: #ffc107;
    line-height: 1;
}

.score-label {
    font-size: 0.8rem;
    color: #6c757d;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    margin-top: 0.25rem;
}

.progress-ring {
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
}

.progress-ring svg {
    width: 100%;
    height: 100%;
    transform: rotate(-90deg);
}

/* Metrics */
.metric-item {
    padding: 1rem;
    background: var(--bg-soft);
    border-radius: 8px;
    border: 1px solid var(--border-soft);
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
    margin-top: 0.25rem;
}

/* Buttons */
.btn {
    border-radius: 8px;
    font-weight: 500;
    transition: var(--transition);
    padding: 0.75rem 1.25rem;
    border: 1px solid transparent;
}

.btn-action {
    justify-content: flex-start;
    text-align: left;
}

.btn:hover {
    transform: translateY(-1px);
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
}

.btn-outline-primary, .btn-outline-success {
    border-color: var(--border-soft);
    background: #fff;
}

.btn-outline-primary:hover {
    background: #0d6efd;
    border-color: #0d6efd;
}

.btn-outline-success:hover {
    background: #198754;
    border-color: #198754;
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
    box-shadow: 0 0 0 1px var(--border-soft);
}

.timeline-content {
    background: white;
    border-radius: 8px;
    padding: 1rem;
    box-shadow: var(--box-shadow);
    border: 1px solid var(--border-soft);
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
    color: #2c3e50;
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
    background: rgba(13, 110, 253, 0.1);
    color: #0d6efd;
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

@section('content')
@if(empty($lead))
    <div class="alert alert-danger" role="alert">
        <h4 class="alert-heading"><i class="fas fa-exclamation-triangle me-2"></i>Data Error</h4>
        <p>Lead data is not available. This could be due to:</p>
        <ul>
            <li>WSO2 Identity Server connection issues</li>
            <li>Invalid lead ID provided</li>
            <li>Lead data has been deleted or corrupted</li>
        </ul>
        <hr>
        <p class="mb-0">
            <a href="{{ route('admin.leads.index') }}" class="btn btn-outline-danger">
                <i class="fas fa-arrow-left me-1"></i>Back to Leads
            </a>
        </p>
    </div>
@else
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
                                <h1 class="h3 mb-1 text-dark fw-bold">
                                    @if(empty($lead['company_name']) && empty($lead['displayName']) && empty($lead['organization_name']))
                                        <span class="text-danger">
                                            <i class="fas fa-exclamation-triangle me-2"></i>
                                            Company Name Not Available
                                        </span>
                                    @else
                                        {{ $lead['company_name'] ?? $lead['displayName'] ?? $lead['organization_name'] }}
                                    @endif
                                </h1>
                                <div class="d-flex align-items-center gap-3">
                                    <span class="badge bg-{{ $lead['status'] === 'New' ? 'primary' : ($lead['status'] === 'Qualified' ? 'success' : 'warning') }} px-3 py-2">
                                        <i class="fas fa-circle me-1" style="font-size: 0.5rem;"></i>
                                        @if(empty($lead['status']))
                                            <span class="text-white">Status Unknown</span>
                                        @else
                                            {{ $lead['status'] }}
                                        @endif
                                    </span>
                                    <span class="text-muted">
                                        <i class="fas fa-clock me-1"></i>
                                        @if(empty($lead['lastModified']))
                                            <span class="text-danger">Last Modified Date Not Available</span>
                                        @else
                                            Updated {{ \Carbon\Carbon::parse($lead['lastModified'])->format('M d, Y \a\t H:i') }}
                                        @endif
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
                <div class="card-header bg-white border-bottom">
                    <div class="d-flex align-items-center">
                        <i class="fas fa-user-circle text-primary me-2"></i>
                        <h5 class="card-title mb-0 text-dark">Lead Overview</h5>
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
                                        <div class="info-value">
                                            @if(empty($lead['contact_person']) && empty($lead['contactPerson']))
                                                <span class="text-danger">
                                                    <i class="fas fa-exclamation-triangle me-1"></i>
                                                    Contact Person Not Available
                                                </span>
                                            @else
                                                {{ $lead['contact_person'] ?? $lead['contactPerson'] }}
                                            @endif
                                        </div>
                                    </div>
                                    <div class="info-item">
                                        <label class="info-label">Email Address</label>
                                        <div class="info-value">
                                            @if(!empty($lead['email']) || !empty($lead['contact_email']))
                                                <a href="mailto:{{ $lead['email'] ?? $lead['contact_email'] }}" class="text-primary text-decoration-none">
                                                    <i class="fas fa-envelope me-1"></i>
                                                    {{ $lead['email'] ?? $lead['contact_email'] }}
                                                </a>
                                            @else
                                                <span class="text-danger">
                                                    <i class="fas fa-exclamation-triangle me-1"></i>
                                                    Email Address Not Available
                                                </span>
                                            @endif
                                        </div>
                                    </div>
                                    <div class="info-item">
                                        <label class="info-label">Phone Number</label>
                                        <div class="info-value">
                                            @if(!empty($lead['phone']) || !empty($lead['contact_phone']))
                                                <a href="tel:{{ $lead['phone'] ?? $lead['contact_phone'] }}" class="text-primary text-decoration-none">
                                                    <i class="fas fa-phone me-1"></i>
                                                    {{ $lead['phone'] ?? $lead['contact_phone'] }}
                                                </a>
                                            @else
                                                <span class="text-danger">
                                                    <i class="fas fa-exclamation-triangle me-1"></i>
                                                    Phone Number Not Available
                                                </span>
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
                                                <span class="text-danger">
                                                    <i class="fas fa-exclamation-triangle me-1"></i>
                                                    Address Not Available
                                                </span>
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
                                        <div class="info-value">
                                            @if(empty($lead['businessType']) && empty($lead['business_type']))
                                                <span class="text-danger">
                                                    <i class="fas fa-exclamation-triangle me-1"></i>
                                                    Business Type Not Available
                                                </span>
                                            @else
                                                {{ $lead['businessType'] ?? $lead['business_type'] }}
                                            @endif
                                        </div>
                                    </div>
                                    <div class="info-item">
                                        <label class="info-label">Current System</label>
                                        <div class="info-value">
                                            @if(empty($lead['currentSystem']) && empty($lead['current_system']))
                                                <span class="text-danger">
                                                    <i class="fas fa-exclamation-triangle me-1"></i>
                                                    Current System Not Available
                                                </span>
                                            @else
                                                {{ $lead['currentSystem'] ?? $lead['current_system'] }}
                                            @endif
                                        </div>
                                    </div>
                                    <div class="info-item">
                                        <label class="info-label">Lead Source</label>
                                        <div class="info-value">
                                            @if(empty($lead['source']) && empty($lead['lead_source']))
                                                <span class="text-danger">
                                                    <i class="fas fa-exclamation-triangle me-1"></i>
                                                    Lead Source Not Available
                                                </span>
                                            @else
                                                <span class="badge bg-light text-dark">{{ $lead['source'] ?? $lead['lead_source'] }}</span>
                                            @endif
                                        </div>
                                    </div>
                                    <div class="info-item">
                                        <label class="info-label">Account Status</label>
                                        <div class="info-value">
                                            @if(empty($lead['account_status']))
                                                <span class="text-danger">
                                                    <i class="fas fa-exclamation-triangle me-1"></i>
                                                    Account Status Not Available
                                                </span>
                                            @else
                                                {{ $lead['account_status'] }}
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Lead Timeline -->
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-white border-bottom">
                    <div class="d-flex align-items-center">
                        <i class="fas fa-chart-line text-info me-2"></i>
                        <h5 class="card-title mb-0 text-dark">Lead Timeline & Progress</h5>
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
                                        <div class="info-value">{{ $lead['created'] ? \Carbon\Carbon::parse($lead['created'])->format('M d, Y \a\t H:i') : 'Aug 7, 2025 at 09:56' }}</div>
                                    </div>
                                    <div class="info-item">
                                        <label class="info-label">Last Modified</label>
                                        <div class="info-value">{{ $lead['lastModified'] ? \Carbon\Carbon::parse($lead['lastModified'])->format('M d, Y \a\t H:i') : 'Aug 7, 2025 at 14:56' }}</div>
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
                                        <div class="info-value">
                                            <span class="badge bg-{{ $lead['status'] === 'new' ? 'primary' : ($lead['status'] === 'contacted' ? 'warning' : ($lead['status'] === 'qualified' ? 'info' : ($lead['status'] === 'converted' ? 'success' : 'secondary'))) }}">
                                                {{ ucfirst($lead['status']) ?? 'New' }}
                                            </span>
                                        </div>
                                    </div>
                                    <div class="info-item">
                                        <label class="info-label">Priority Level</label>
                                        <div class="info-value">
                                            <span class="badge bg-danger">{{ $lead['priority'] ?? 'Normal Priority' }}</span>
                                        </div>
                                    </div>
                                    <div class="info-item">
                                        <label class="info-label">Next Action</label>
                                        <div class="info-value text-muted">{{ $lead['next_action'] ?? 'Follow-up required' }}</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Activity Timeline -->
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white border-bottom">
                    <div class="d-flex align-items-center">
                        <i class="fas fa-history text-success me-2"></i>
                        <h5 class="card-title mb-0 text-dark">Activity Timeline</h5>
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

        <!-- Sidebar -->
        <div class="col-xl-4">
            <!-- Lead Score -->
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-white border-bottom">
                    <div class="d-flex align-items-center">
                        <i class="fas fa-chart-pie text-primary me-2"></i>
                        <h5 class="card-title mb-0 text-dark">Lead Score</h5>
                    </div>
                </div>
                <div class="card-body text-center p-4">
                    <div class="score-display mb-4">
                        <div class="score-circle-simple mx-auto mb-3">
                            <div class="score-content">
                                <div class="score-number">20</div>
                                <div class="score-label">Score</div>
                            </div>
                            <div class="score-progress">
                                <div class="progress-ring">
                                    <svg width="100" height="100">
                                        <circle cx="50" cy="50" r="40" stroke="#e9ecef" stroke-width="8" fill="transparent"/>
                                        <circle cx="50" cy="50" r="40" stroke="#ffc107" stroke-width="8" 
                                                fill="transparent" stroke-dasharray="251.2" 
                                                stroke-dashoffset="200.96" stroke-linecap="round"/>
                                    </svg>
                                </div>
                            </div>
                        </div>
                        <div class="score-status">
                            <span class="badge bg-warning text-dark px-3 py-2">Needs Attention</span>
                        </div>
                    </div>
                    <div class="row text-center g-2">
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
                <div class="card-header bg-white border-bottom">
                    <div class="d-flex align-items-center">
                        <i class="fas fa-building text-secondary me-2"></i>
                        <h5 class="card-title mb-0 text-dark">Organization</h5>
                    </div>
                </div>
                <div class="card-body p-4">
                    <div class="info-grid">
                        <div class="info-item">
                            <label class="info-label">Organization ID</label>
                            <div class="info-value font-monospace">{{ $lead['id'] ?? $organization['id'] ?? '98f9e629-7046-44fb-9199-a22892d57359' }}</div>
                        </div>
                        <div class="info-item">
                            <label class="info-label">Organization Name</label>
                            <div class="info-value fw-bold">{{ $lead['company_name'] ?? $lead['displayName'] ?? $lead['organization_name'] ?? 'PT. Aneka Tambang' }}</div>
                        </div>
                        <div class="info-item">
                            <label class="info-label">Organization Status</label>
                            <div class="info-value">
                                <span class="badge bg-success">{{ ucfirst($lead['status']) ?? 'New' }}</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Quick Actions -->
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white border-bottom">
                    <div class="d-flex align-items-center">
                        <i class="fas fa-bolt text-dark me-2"></i>
                        <h5 class="card-title mb-0 text-dark">Quick Actions</h5>
                    </div>
                </div>
                <div class="card-body p-4">
                    <div class="d-grid gap-2">
                        <!-- <button type="button" class="btn btn-success btn-activate-lead" data-id="{{ $lead['id'] }}">
                            <i class="fas fa-user-check me-2"></i>Activate Lead
                        </button> -->
                        <button class="btn btn-success btn-action" id="btn-convert-lead" data-id="{{ $lead['id'] }}" onclick="simpleConvertLead('{{ $lead['id'] }}')">
                            <i class="fas fa-user-plus me-2"></i>Convert to Customer
                        </button>
                        <button class="btn btn-info btn-action" data-bs-toggle="modal" data-bs-target="#approveModal">
                            <i class="fas fa-check-circle me-2"></i>Approve Lead
                        </button>
                        <button class="btn btn-primary btn-action" data-bs-toggle="modal" data-bs-target="#followUpModal">
                            <i class="fas fa-calendar-plus me-2"></i>Schedule Follow-up
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
</div>
@endif

<!-- Include Modals -->
@include('admin.crm.leads.modals')

<!-- Simple Convert Lead Modal -->
<div class="modal fade" id="simpleConvertModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="fas fa-user-plus text-success me-2"></i>
                    Convert Lead to Customer
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="alert alert-info">
                    <i class="fas fa-info-circle me-2"></i>
                    This will convert the lead to a customer account and create user credentials.
                </div>
                
                <div class="mb-3">
                    <label for="simpleOrgName" class="form-label fw-bold">
                        <i class="fas fa-building me-1"></i>Organization Name
                    </label>
                    <input type="text" class="form-control" id="simpleOrgName" 
                           value="{{ $lead['company_name'] ?? $lead['displayName'] ?? '' }}" required>
                    <div class="form-text">
                        Current: <code>{{ $lead['displayName'] ?? $lead['name'] ?? 'N/A' }}</code>
                    </div>
                </div>
                
                <div class="mb-3">
                    <label for="simpleCustomerName" class="form-label fw-bold">Customer Name</label>
                    <input type="text" class="form-control" id="simpleCustomerName" 
                           value="{{ $lead['contact_person'] ?? '' }}" required>
                </div>
                
                <div class="mb-3">
                    <label for="simpleEmail" class="form-label fw-bold">Email</label>
                    <input type="email" class="form-control" id="simpleEmail" 
                           value="{{ $lead['email'] ?? '' }}">
                </div>
                
                <div class="mb-3">
                    <label for="simpleNotes" class="form-label fw-bold">Conversion Notes</label>
                    <textarea class="form-control" id="simpleNotes" rows="3" 
                              placeholder="Add any notes about this conversion..."></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-success" id="simpleConvertBtn">
                    <i class="fas fa-user-plus me-1"></i>Convert to Customer
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Modal untuk response lead -->
<div class="modal fade" id="modal-lead-response" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content" id="content-lead-response"></div>
    </div>
</div>
@endsection

@push('scripts')
<script>
$(document).ready(function() {
    const leadId = '{{ $lead["id"] }}';
    
    // Helper function for showing notifications
    function showNotification(type, title, message) {
        if (typeof Swal !== 'undefined') {
            Swal.fire({
                icon: type,
                title: title,
                text: message,
                timer: 3000
            });
        } else {
            alert(message);
        }
    }
    
    // Helper function for AJAX calls
    function makeAjaxCall(url, data, button, successCallback) {
        const originalText = button.html();
        const loadingText = button.hasClass('btn-activate-lead') ? 
            '<i class="fas fa-spinner fa-spin me-2"></i>Activating...' : 
            '<i class="fas fa-spinner fa-spin me-2"></i>Converting...';
        
        button.prop('disabled', true).html(loadingText);
        
        $.ajax({
            url: url,
            method: 'POST',
            data: {
                ...data,
                _token: $('meta[name="csrf-token"]').attr('content')
            },
            success: function(response) {
                console.log('Success:', response);
                showNotification('success', 'Success!', response.message);
                if (successCallback) successCallback(response);
            },
            error: function(xhr) {
                console.error('Error:', xhr);
                const errorMsg = xhr.responseJSON?.message || 'Operation failed';
                showNotification('error', 'Error!', errorMsg);
            },
            complete: function() {
                button.prop('disabled', false).html(originalText);
            }
        });
    }
    
    // Convert Lead Modal
    window.simpleConvertLead = function(leadId) {
        $('#simpleConvertModal').modal('show');
    };
    
    // Convert Submit Handler
    $('#simpleConvertBtn').click(function() {
        const formData = {
            organization_name: $('#simpleOrgName').val(),
            customer_name: $('#simpleCustomerName').val(),
            customer_email: $('#simpleEmail').val(),
            notes: $('#simpleNotes').val()
        };
        
        // Simple validation
        if (!formData.organization_name || !formData.customer_name) {
            showNotification('error', 'Validation Error', 'Organization name and customer name are required.');
            return;
        }
        
        makeAjaxCall(
            `/admin/crm/leads/${leadId}/convert`,
            formData,
            $(this),
            function(response) {
                $('#simpleConvertModal').modal('hide');
                $('.badge').first().removeClass().addClass('badge bg-success px-3 py-2')
                    .html('<i class="fas fa-circle me-1" style="font-size: 0.5rem;"></i>Converted');
            }
        );
    });

    // Activate Lead Handler
    $(document).on('click', '.btn-activate-lead', function(e) {
        e.preventDefault();
        const buttonLeadId = $(this).data('id');
        
        makeAjaxCall(
            `/admin/crm/leads/${buttonLeadId}/activate`,
            { orgName: '{{ $lead["company_name"] ?? $lead["displayName"] ?? "Qualified Lead" }}' },
            $(this),
            function(response) {
                $('.badge').first().removeClass().addClass('badge bg-info px-3 py-2')
                    .html('<i class="fas fa-circle me-1" style="font-size: 0.5rem;"></i>Qualified');
            }
        );
    });
});
</script>
@endpush
