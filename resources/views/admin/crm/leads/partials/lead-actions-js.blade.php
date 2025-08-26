{{-- Lead Actions JavaScript - Reusable across Lead Management views --}}
<script>
// Lead Actions - Reusable functions for lead management
function convertLead(leadId) {
    console.log('Converting lead:', leadId);
    
    // Get lead data from current page
    const leadData = window.leadData || {};
    const organizationName = leadData.company_name || leadData.displayName || '';
    const customerName = leadData.contact_person || '';
    const customerEmail = leadData.email || '';
    
    // Set modal data
    $('#convertLeadId').val(leadId);
    $('#organizationName').val(organizationName);
    $('#customerName').val(customerName);
    $('#customerEmail').val(customerEmail);
    $('#conversionNotes').val('');
    
    // Show modal
    $('#convertModal').modal('show');
}

function updateStatus(leadId) {
    $("#leadId").val(leadId);
    $("#newStatus").val("");
    $("#statusNotes").val("");
    $("#statusModal").modal("show");
}

function activateLead(leadId) {
    if (confirm("Are you sure you want to activate this lead?")) {
        $.ajax({
            url: `/admin/crm/leads/${leadId}/activate`,
            type: "POST",
            headers: {
                "X-CSRF-TOKEN": $("meta[name='csrf-token']").attr("content")
            },
            success: function(response) {
                if (response.success) {
                    if (typeof $("#leadsTable").DataTable === "function") {
                        $("#leadsTable").DataTable().ajax.reload();
                    }
                    
                    if (typeof Swal !== "undefined") {
                        Swal.fire({
                            icon: "success",
                            title: "Success!",
                            text: "Lead activated successfully!",
                            timer: 3000
                        });
                    } else {
                        alert("Lead activated successfully!");
                    }
                } else {
                    alert("Error: " + (response.message || "Failed to activate lead"));
                }
            },
            error: function(xhr) {
                console.error("Activation error:", xhr);
                alert("Failed to activate lead. Please try again.");
            }
        });
    }
}

function viewLead(leadId) {
    window.location.href = "/admin/crm/leads/" + leadId;
}
</script>
