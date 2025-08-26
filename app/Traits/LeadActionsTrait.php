<?php

namespace App\Traits;

trait LeadActionsTrait
{
    /**
     * Generate common Lead Actions JavaScript functions
     * This trait provides reusable JavaScript functions for lead actions
     * that can be used across different views (index, show, etc.)
     */
    public function getLeadActionsJavaScript()
    {
        return '
// Lead Actions - Reusable functions for lead management
function convertLead(leadId) {
    if (confirm("Are you sure you want to convert this lead to customer? This will create an active user account.")) {
        $.ajax({
            url: `/admin/crm/leads/${leadId}/convert`,
            type: "POST",
            headers: {
                "X-CSRF-TOKEN": $("meta[name=\"csrf-token\"]").attr("content")
            },
            success: function(response) {
                console.log("Convert response:", response);
                if (response.success) {
                    // Show success message with user creation info
                    let successMessage = response.message || "Lead converted to customer successfully!";
                    if (response.user_created) {
                        successMessage += "\\n\\nUser account has been created and activated.";
                        if (response.username) {
                            successMessage += "\\nUsername: " + response.username;
                        }
                        if (response.temp_password) {
                            successMessage += "\\nTemporary Password: " + response.temp_password;
                        }
                    }
                    
                    if (typeof Swal !== "undefined") {
                        Swal.fire({
                            icon: "success",
                            title: "Conversion Successful!",
                            text: successMessage,
                            timer: 8000,
                            showConfirmButton: true,
                            confirmButtonText: "OK"
                        });
                    } else {
                        alert(successMessage);
                    }
                    
                    // Reload page or table if exists
                    if (typeof $("#leadsTable").DataTable === "function") {
                        $("#leadsTable").DataTable().ajax.reload();
                    } else {
                        // For detail page, update status badge
                        const statusBadge = document.querySelector(".badge");
                        if (statusBadge) {
                            statusBadge.className = "badge bg-success px-3 py-2";
                            statusBadge.innerHTML = \'<i class="fas fa-circle me-1" style="font-size: 0.5rem;"></i>Converted\';
                        }
                        
                        // Update last modified time
                        const lastModifiedSpan = document.querySelector(".text-muted");
                        if (lastModifiedSpan) {
                            lastModifiedSpan.innerHTML = \'<i class="fas fa-clock me-1"></i>Updated \' + new Date().toLocaleDateString("en-US", { month: "short", day: "numeric", year: "numeric" }) + " at " + new Date().toLocaleTimeString("en-US", { hour: "2-digit", minute: "2-digit" });
                        }
                    }
                    
                } else {
                    if (typeof Swal !== "undefined") {
                        Swal.fire({
                            icon: "error",
                            title: "Conversion Failed!",
                            text: response.message || "Failed to convert lead"
                        });
                    } else {
                        alert("Error: " + (response.message || "Failed to convert lead"));
                    }
                }
            },
            error: function(xhr) {
                console.error("Convert error:", xhr);
                let errorMessage = "Failed to convert lead. Please try again.";
                if (xhr.responseJSON && xhr.responseJSON.message) {
                    errorMessage = xhr.responseJSON.message;
                }
                
                if (typeof Swal !== "undefined") {
                    Swal.fire({
                        icon: "error",
                        title: "Error!",
                        text: errorMessage
                    });
                } else {
                    alert(errorMessage);
                }
            }
        });
    }
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
                "X-CSRF-TOKEN": $("meta[name=\"csrf-token\"]").attr("content")
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
        ';
    }
}
