<div class="modal-body">
    <h5>Edit Permission</h5>
    <form id="permissionEditForm" action="{{ route('administration.permission.update', $id) }}" method="POST">
        @csrf
        @method('PUT')
        <div class="row">
            <div class="col-md-6">
                <div class="form-group">
                    <label for="edit_name">Permission Name</label>
                    <input type="text" class="form-control" id="edit_name" name="name" value="user:read" required>
                </div>
            </div>
            <div class="col-md-6">
                <div class="form-group">
                    <label for="edit_display_name">Display Name</label>
                    <input type="text" class="form-control" id="edit_display_name" name="display_name" value="Read Users" required>
                </div>
            </div>
        </div>
        <div class="row">
            <div class="col-md-6">
                <div class="form-group">
                    <label for="edit_resource">Resource</label>
                    <input type="text" class="form-control" id="edit_resource" name="resource" value="user" required>
                </div>
            </div>
            <div class="col-md-6">
                <div class="form-group">
                    <label for="edit_action">Action</label>
                    <select class="form-control" id="edit_action" name="action" required>
                        <option value="">Select Action</option>
                        <option value="read" selected>Read</option>
                        <option value="create">Create</option>
                        <option value="update">Update</option>
                        <option value="delete">Delete</option>
                        <option value="manage">Manage</option>
                    </select>
                </div>
            </div>
        </div>
        <div class="form-group">
            <label for="edit_description">Description</label>
            <textarea class="form-control" id="edit_description" name="description" rows="3">Allows reading user information and data</textarea>
        </div>
    </form>
</div>
<div class="modal-footer">
    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
    <button type="submit" form="permissionEditForm" class="btn btn-primary">Update Permission</button>
</div>
