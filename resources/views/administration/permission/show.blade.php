<div class="modal-body">
    <h5>Permission Details</h5>
    <div class="row">
        <div class="col-md-6">
            <div class="form-group mb-3">
                <label><strong>Permission Name:</strong></label>
                <p><code>{{ $permission['name'] }}</code></p>
            </div>
        </div>
        <div class="col-md-6">
            <div class="form-group mb-3">
                <label><strong>Display Name:</strong></label>
                <p>{{ $permission['display_name'] }}</p>
            </div>
        </div>
    </div>
    <div class="row">
        <div class="col-md-6">
            <div class="form-group mb-3">
                <label><strong>Resource:</strong></label>
                <p><span class="badge bg-secondary">{{ $permission['resource'] }}</span></p>
            </div>
        </div>
        <div class="col-md-6">
            <div class="form-group mb-3">
                <label><strong>Action:</strong></label>
                @php
                    $action = $permission['action'];
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
                <p><span class="badge {{ $badgeClass }}">{{ $action }}</span></p>
            </div>
        </div>
    </div>
    <div class="form-group mb-3">
        <label><strong>Description:</strong></label>
        <p>{{ $permission['description'] }}</p>
    </div>
    <div class="form-group mb-3">
        <label><strong>Assigned to Roles:</strong></label>
        <div class="assigned-roles-container" style="max-height: 150px; overflow-y: auto; border: 1px solid #ddd; padding: 10px; border-radius: 5px;">
            @if(count($permission['assigned_roles']) > 0)
                @foreach($permission['assigned_roles'] as $role)
                    <span class="badge bg-primary me-1 mb-1">{{ $role }}</span>
                @endforeach
            @else
                <span class="text-muted">Not assigned to any roles</span>
            @endif
        </div>
    </div>
    <div class="row mt-3">
        <div class="col-md-12">
            <div class="alert alert-info" role="alert">
                <small><i class="bi bi-info-circle"></i> This permission is managed by WSO2 Identity Server. Role assignments are synchronized automatically.</small>
            </div>
        </div>
    </div>
</div>
<div class="modal-footer">
    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
</div>
