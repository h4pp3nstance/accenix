@extends('layouts.app')

@section('content')
@push('styles')
<style>
    .permission-badge {
        font-size: 0.75rem;
        padding: 0.4rem 0.8rem;
        border-radius: 12px;
        margin: 0.2rem;
        display: inline-block;
    }
    .permissions-container {
        max-height: 300px;
        overflow-y: auto;
    }
    .role-info-card {
        background: #f8f9fa;
        border: 1px solid #dee2e6;
        border-radius: 8px;
        padding: 1.5rem;
    }
    .role-info-card h6 {
        color: #495057;
        margin-bottom: 0.5rem;
        font-weight: 600;
    }
    .role-info-card .info-value {
        color: #212529;
        font-weight: 500;
    }
    .collapsible {
        cursor: pointer;
        user-select: none;
    }
    .collapsible-content {
        display: none;
        padding: 0.5rem 1rem;
        background: #f4f6fa;
        border-radius: 0 0 8px 8px;
    }
    .collapsible.active + .collapsible-content {
        display: block;
    }
</style>
@endpush

<div id="main-content">
    <div class="page-heading">
        <div class="page-title">
            <div class="row">
                <div class="col-12 col-md-6 order-md-1 order-last">
                    <h3>Role Details: {{ $role['name'] }}</h3>
                    <p class="text-subtitle text-muted">
                        View role information, permissions, and assigned users.
                    </p>
                </div>
                <div class="col-12 col-md-6 order-md-2 order-first">
                    <nav aria-label="breadcrumb" class="breadcrumb-header float-start float-lg-end">
                        <ol class="breadcrumb">
                            <li class="breadcrumb-item">
                                <a href="/dashboard">Dashboard</a>
                            </li>
                            <li class="breadcrumb-item">
                                <a href="{{ route('administration.role.index') }}">Role Management</a>
                            </li>
                            <li class="breadcrumb-item active" aria-current="page">
                                Role Details
                            </li>
                        </ol>
                    </nav>
                </div>
            </div>
        </div>
        <section class="section">
            <div class="card">
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-12">
                            <div class="row mb-3 align-items-center">
                                <div class="col-md-3">
                                    <label class="form-label fw-bold mb-0">Role Name</label>
                                </div>
                                <div class="col-md-9">
                                    <input type="text" class="form-control" value="{{ $role['name'] }}" disabled>
                                </div>
                            </div>
                            <div class="row mb-3 align-items-center">
                                <div class="col-md-3">
                                    <label class="form-label fw-bold mb-0">Users</label>
                                </div>
                                <div class="col-md-9">
                                    <button class="btn btn-outline-primary btn-sm collapsible" type="button">
                                        Show/Hide Assigned Users ({{ $role['user_count'] ?? 0 }})
                                    </button>
                                    <div class="collapsible-content mt-2">
                                        @if(isset($role['users']) && count($role['users']) > 0)
                                            <ul class="list-group">
                                                @foreach($role['users'] as $user)
                                                    <li class="list-group-item d-flex align-items-center">
                                                        <i class="bi bi-person-circle me-2"></i>
                                                        {{ is_array($user) ? ($user['name'] ?? $user['id'] ?? 'Unknown') : $user }}
                                                    </li>
                                                @endforeach
                                            </ul>
                                        @else
                                            <div class="text-muted">No users assigned</div>
                                        @endif
                                    </div>
                                </div>
                            </div>
                            <div class="row mb-3 align-items-center">
                                <div class="col-md-3">
                                    <label class="form-label fw-bold mb-0">Permissions</label>
                                </div>
                                <div class="col-md-9">
                                    <div class="border rounded p-3 permissions-container">
                                        @if(count($role['permissions']) > 0)
                                            @php
                                                // Group permissions by category/module (prefix before ':')
                                                $grouped = [];
                                                foreach($role['permissions'] as $perm) {
                                                    $permStr = is_array($perm) ? ($perm['name'] ?? $perm['id'] ?? $perm) : $perm;
                                                    $parts = explode(':', $permStr);
                                                    $cat = $parts[0] ?? 'Other';
                                                    $grouped[$cat][] = $permStr;
                                                }
                                            @endphp
                                            @foreach($grouped as $cat => $perms)
                                                <div class="mb-2">
                                                    <span class="fw-bold text-primary">{{ ucfirst($cat) }}</span>:
                                                    @foreach($perms as $permission)
                                                        @php
                                                            $parts = explode(':', $permission);
                                                            $action = $parts[1] ?? 'unknown';
                                                            $badgeClass = 'bg-primary';
                                                            switch($action) {
                                                                case 'read': $badgeClass = 'bg-info'; break;
                                                                case 'create': $badgeClass = 'bg-success'; break;
                                                                case 'update': $badgeClass = 'bg-warning'; break;
                                                                case 'delete': $badgeClass = 'bg-danger'; break;
                                                                case 'approve': $badgeClass = 'bg-dark'; break;
                                                                case 'export': $badgeClass = 'bg-secondary'; break;
                                                            }
                                                        @endphp
                                                        <span class="badge {{ $badgeClass }} permission-badge">{{ $permission }}</span>
                                                    @endforeach
                                                </div>
                                            @endforeach
                                        @else
                                            <div class="text-muted">No permissions assigned</div>
                                        @endif
                                    </div>
                                    <small class="form-text text-muted">Total: {{ count($role['permissions']) }} permissions</small>
                                </div>
                            </div>
                            <div class="row mb-3 align-items-center">
                                <div class="col-md-3">
                                    <label class="form-label fw-bold mb-0">Role Status</label>
                                </div>
                                <div class="col-md-9">
                                    <span class="badge bg-success">Active</span>
                                    <small class="form-text text-muted d-block">This role is active in WSO2 Identity Server</small>
                                </div>
                            </div>
                            <div class="row mb-3 align-items-center">
                                <div class="col-md-3">
                                    <label class="form-label fw-bold mb-0">Audit Info</label>
                                </div>
                                <div class="col-md-9">
                                    <span class="text-muted">Last updated: {{ $role['updated_at'] ?? '-' }}</span>
                                    <span class="mx-2">|</span>
                                    <span class="text-muted">Last synced: {{ $role['synced_at'] ?? '-' }}</span>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-12">
                                    <hr>
                                    <div class="d-flex justify-content-end gap-2">
                                        <a href="{{ route('administration.role.index') }}" class="btn btn-secondary">
                                            <i class="bi bi-arrow-left"></i> Back to Roles
                                        </a>
                                        <a href="{{ route('administration.role.edit', $role['name']) }}" class="btn btn-warning">
                                            <i class="bi bi-pencil"></i> Edit Role
                                        </a>
                                        <form method="POST" action="{{ route('administration.role.destroy', $role['name']) }}" style="display:inline;">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-danger" onclick="return confirm('Are you sure you want to delete this role?')">
                                                <i class="bi bi-trash"></i> Delete
                                            </button>
                                        </form>
                                        <form method="POST" action="{{ route('administration.role.refresh') }}" style="display:inline;">
                                            @csrf
                                            <button type="submit" class="btn btn-info">
                                                <i class="bi bi-arrow-repeat"></i> Sync from WSO2
                                            </button>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    </div>
</div>

@push('scripts')
<script>
$(document).ready(function() {
    $('.collapsible').on('click', function() {
        $(this).toggleClass('active');
        $(this).next('.collapsible-content').slideToggle(200);
    });
});
</script>
@endpush
@endsection
