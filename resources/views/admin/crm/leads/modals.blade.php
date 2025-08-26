<!-- Approve Lead Modal -->
<div class="modal fade" id="approveModal" tabindex="-1" aria-labelledby="approveModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="approveModalLabel">
                    <i class="fas fa-check-circle text-info me-2"></i>
                    Approve Lead
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="approveForm">
                <div class="modal-body">
                    <input type="hidden" id="approveLeadId" value="{{ $lead['id'] }}">
                    
                    <div class="alert alert-info">
                        <h6 class="alert-heading">
                            <i class="fas fa-info-circle me-2"></i>Lead Approval
                        </h6>
                        <p class="mb-2">This will approve the lead and change status to <strong>"Qualified"</strong>.</p>
                        <small class="text-muted">
                            • Lead will be marked as approved<br>
                            • Status will be updated to "Qualified"<br>
                            • Approval timestamp will be recorded
                        </small>
                    </div>
                    
                    <div class="mb-3">
                        <label for="approvalNotes" class="form-label fw-bold">Approval Notes (Optional)</label>
                        <textarea class="form-control" id="approvalNotes" name="notes" rows="3" 
                                  placeholder="Add any notes about this approval..."></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-info" id="approveSubmit">
                        <i class="fas fa-check-circle me-1"></i>Approve Lead
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Follow-up Modal -->
<div class="modal fade" id="followUpModal" tabindex="-1" aria-labelledby="followUpModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="followUpModalLabel">
                    <i class="fas fa-calendar-check text-primary me-2"></i>
                    Schedule Follow-up
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="followUpForm">
                <div class="modal-body">
                    <input type="hidden" id="followUpLeadId" value="{{ $lead['id'] }}">
                    
                    <div class="mb-3">
                        <label for="followUpType" class="form-label fw-bold">Follow-up Type</label>
                        <select class="form-select" id="followUpType" name="type" required>
                            <option value="">Select Type</option>
                            <option value="call">📞 Phone Call</option>
                            <option value="email">📧 Email</option>
                            <option value="meeting">🤝 Meeting</option>
                            <option value="demo">💻 Demo</option>
                        </select>
                    </div>
                    
                    <div class="mb-3">
                        <label for="followUpDate" class="form-label fw-bold">Follow-up Date</label>
                        <input type="date" class="form-control" id="followUpDate" name="date" required>
                    </div>
                    
                    <div class="mb-3">
                        <label for="followUpTime" class="form-label fw-bold">Follow-up Time</label>
                        <input type="time" class="form-control" id="followUpTime" name="time" required>
                    </div>
                    
                    <div class="mb-3">
                        <label for="followUpNotes" class="form-label fw-bold">Notes</label>
                        <textarea class="form-control" id="followUpNotes" name="notes" rows="3" 
                                  placeholder="Any notes for this follow-up..."></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-primary" id="followUpSubmit">
                        <i class="fas fa-calendar-check me-1"></i>Schedule
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Status Update Modal -->
<div class="modal fade" id="updateStatusModal" tabindex="-1" aria-labelledby="updateStatusModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="updateStatusModalLabel">
                    <i class="fas fa-edit text-success me-2"></i>
                    Update Lead Status
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="updateStatusForm">
                <div class="modal-body">
                    <input type="hidden" id="updateLeadId" value="{{ $lead['id'] }}">
                    <input type="hidden" id="oldStatus" value="{{ $lead['status'] }}">
                    
                    <div class="mb-3">
                        <label for="newStatus" class="form-label fw-bold">Status</label>
                        <select class="form-select" id="newStatus" name="status" required>
                            <option value="new" {{ $lead['status'] === 'new' ? 'selected' : '' }}>🆕 New</option>
                            <option value="contacted" {{ $lead['status'] === 'contacted' ? 'selected' : '' }}>📞 Contacted</option>
                            <option value="qualified" {{ $lead['status'] === 'qualified' ? 'selected' : '' }}>✅ Qualified</option>
                            <option value="converted" {{ $lead['status'] === 'converted' ? 'selected' : '' }}>🎉 Converted</option>
                            <option value="rejected" {{ $lead['status'] === 'rejected' ? 'selected' : '' }}>❌ Rejected</option>
                        </select>
                        <div class="form-text">
                            Current status: <span class="badge badge-{{ $lead['status'] === 'new' ? 'primary' : ($lead['status'] === 'contacted' ? 'warning' : ($lead['status'] === 'qualified' ? 'info' : ($lead['status'] === 'converted' ? 'success' : 'danger'))) }}">{{ ucfirst($lead['status']) }}</span>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="statusNotes" class="form-label fw-bold">Notes (Optional)</label>
                        <textarea class="form-control" id="statusNotes" name="notes" rows="3" placeholder="Add any notes about this status change..."></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-primary" id="updateStatusSubmit">
                        <i class="fas fa-save me-1"></i>Update Status
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Convert Lead Modal -->
<div class="modal fade" id="convertModal" tabindex="-1" aria-labelledby="convertModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="convertModalLabel">
                    <i class="fas fa-user-plus text-success me-2"></i>
                    Convert Lead to Customer
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="convertForm">
                <div class="modal-body">
                    <input type="hidden" id="convertLeadId" value="{{ $lead['id'] }}">
                    
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle me-2"></i>
                        This will convert the lead to a customer account and create a new customer profile with user credentials.
                    </div>
                    
                    <div class="mb-3">
                        <label for="organizationName" class="form-label fw-bold">
                            <i class="fas fa-building me-1"></i>Organization Name
                        </label>
                        <input type="text" class="form-control" id="organizationName" name="organization_name" 
                               value="{{ str_replace('lead-', '', $lead['displayName'] ?? $lead['name'] ?? '') }}" required>
                        <div class="form-text">
                            Current name: <code>{{ $lead['displayName'] ?? $lead['name'] ?? 'N/A' }}</code>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="customerName" class="form-label fw-bold">Customer Name</label>
                        <input type="text" class="form-control" id="customerName" name="customer_name" 
                               value="{{ $lead['company_name'] ?? '' }}" required>
                    </div>
                    
                    <div class="mb-3">
                        <label for="customerEmail" class="form-label fw-bold">Email</label>
                        <input type="email" class="form-control" id="customerEmail" name="customer_email" 
                               value="{{ $lead['email'] ?? '' }}">
                    </div>
                    
                    <div class="mb-3">
                        <label for="conversionNotes" class="form-label fw-bold">Conversion Notes</label>
                        <textarea class="form-control" id="conversionNotes" name="notes" rows="3" 
                                  placeholder="Add any notes about this conversion..."></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-success" id="convertSubmit">
                        <i class="fas fa-user-plus me-1"></i>Convert to Customer
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- JavaScript untuk modals sudah ada di show.blade.php -->
<!-- JavaScript untuk modals sudah ada di show.blade.php -->
