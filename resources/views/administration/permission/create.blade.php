@extends('layouts.app')

@section('content')
<div id="main-content">
    <div class="page-heading">
        <div class="page-title">
            <div class="row">
                <div class="col-12 col-md-6 order-md-1 order-last">
                    <h3>Create New Permission</h3>
                    <p class="text-subtitle text-muted">
                        Create a new permission for role assignment.
                    </p>
                </div>
                <div class="col-12 col-md-6 order-md-2 order-first">
                    <nav aria-label="breadcrumb" class="breadcrumb-header float-start float-lg-end">
                        <ol class="breadcrumb">
                            <li class="breadcrumb-item">
                                <a href="/dashboard">Dashboard</a>
                            </li>
                            <li class="breadcrumb-item">
                                <a href="{{ route('administration.permission.index') }}">Permission Management</a>
                            </li>
                            <li class="breadcrumb-item active" aria-current="page">
                                Create Permission
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
                    
                    <form id="permissionForm" action="{{ route('administration.permission.store') }}" method="POST">
                        @csrf
                        <div class="row g-3">
                            <div class="col-12">
                                <div class="row mb-3 align-items-center">
                                    <div class="col-md-3">
                                        <label for="name" class="form-label fw-bold mb-0">Permission Name</label>
                                    </div>
                                    <div class="col-md-9">
                                        <input type="text" class="form-control" id="name" name="name" required placeholder="e.g., user:read">
                                        <small class="form-text text-muted">Enter the unique permission identifier (format: resource:action)</small>
                                    </div>
                                </div>

                                <div class="row mb-3 align-items-center">
                                    <div class="col-md-3">
                                        <label for="display_name" class="form-label fw-bold mb-0">Display Name</label>
                                    </div>
                                    <div class="col-md-9">
                                        <input type="text" class="form-control" id="display_name" name="display_name" required placeholder="e.g., Read Users">
                                        <small class="form-text text-muted">Human-readable name for the permission</small>
                                    </div>
                                </div>

                                <div class="row mb-3 align-items-center">
                                    <div class="col-md-3">
                                        <label for="resource" class="form-label fw-bold mb-0">Resource</label>
                                    </div>
                                    <div class="col-md-9">
                                        <input type="text" class="form-control" id="resource" name="resource" required placeholder="e.g., user, role, permission">
                                        <small class="form-text text-muted">The resource this permission applies to</small>
                                    </div>
                                </div>

                                <div class="row mb-3 align-items-center">
                                    <div class="col-md-3">
                                        <label for="action" class="form-label fw-bold mb-0">Action</label>
                                    </div>
                                    <div class="col-md-9">
                                        <select class="form-control" id="action" name="action" required>
                                            <option value="">Select Action</option>
                                            <option value="read">Read</option>
                                            <option value="create">Create</option>
                                            <option value="update">Update</option>
                                            <option value="delete">Delete</option>
                                            <option value="manage">Manage</option>
                                        </select>
                                        <small class="form-text text-muted">The action this permission allows</small>
                                    </div>
                                </div>

                                <div class="row mb-3 align-items-start">
                                    <div class="col-md-3">
                                        <label for="description" class="form-label fw-bold mb-0">Description</label>
                                    </div>
                                    <div class="col-md-9">
                                        <textarea class="form-control" id="description" name="description" rows="3" placeholder="Describe what this permission allows"></textarea>
                                        <small class="form-text text-muted">Optional description of the permission</small>
                                    </div>
                                </div>

                                <div class="row">
                                    <div class="col-12">
                                        <hr>
                                        <div class="d-flex justify-content-end gap-2">
                                            <a href="{{ route('administration.permission.index') }}" class="btn btn-secondary">
                                                <i class="bi bi-arrow-left"></i> Back to Permissions
                                            </a>
                                            <button type="submit" class="btn btn-primary">
                                                <i class="bi bi-plus-circle"></i> Create Permission
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </section>
    </div>
</div>
@endsection
