<!-- resources/views/components/ajax-error-modal.blade.php -->
<div class="modal fade" id="ajaxErrorModal" tabindex="-1" aria-labelledby="ajaxErrorModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header bg-danger text-white">
        <h5 class="modal-title fw-bold" id="ajaxErrorModalLabel" style="font-size: 1.3rem; letter-spacing: 0.5px; color: #fff;">
            <span class="me-2"><i class="bi bi-exclamation-circle-fill"></i></span>
            Terjadi Kesalahan
        </h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
      </div>
      <div class="modal-body" id="ajaxErrorModalBody">
        <!-- Pesan error akan ditampilkan di sini -->
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Tutup</button>
      </div>
    </div>
  </div>
</div>
