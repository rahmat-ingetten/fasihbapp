</main>

<div id="confirm-modal-overlay" class="confirm-overlay" style="display:none">
    <div class="confirm-modal">
        <p id="confirm-modal-message" class="confirm-modal-message"></p>
        <div class="confirm-modal-actions">
            <button type="button" id="confirm-modal-cancel" class="btn btn-secondary btn-sm">Batal</button>
            <button type="button" id="confirm-modal-ok" class="btn btn-danger-solid btn-sm">Ya, Lanjutkan</button>
        </div>
    </div>
</div>

<script>
(function () {
    var overlay = document.getElementById('confirm-modal-overlay');
    var messageEl = document.getElementById('confirm-modal-message');
    var okBtn = document.getElementById('confirm-modal-ok');
    var cancelBtn = document.getElementById('confirm-modal-cancel');
    var pendingForm = null;

    function openModal(message, form) {
        messageEl.textContent = message;
        pendingForm = form;
        overlay.style.display = 'flex';
    }
    function closeModal() {
        overlay.style.display = 'none';
        pendingForm = null;
    }

    okBtn.addEventListener('click', function () {
        var form = pendingForm;
        closeModal();
        if (form) form.submit();
    });
    cancelBtn.addEventListener('click', closeModal);
    overlay.addEventListener('click', function (e) {
        if (e.target === overlay) closeModal();
    });
    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape') closeModal();
    });

    // Ganti semua form yang pakai data-confirm="pesan..." supaya munculkan
    // modal custom ini, bukan popup confirm() bawaan browser.
    document.querySelectorAll('form[data-confirm]').forEach(function (form) {
        form.addEventListener('submit', function (e) {
            e.preventDefault();
            openModal(form.dataset.confirm, form);
        });
    });
})();
</script>

<script>
document.querySelectorAll('.password-toggle').forEach(function (btn) {
    btn.addEventListener('click', function () {
        var input = document.getElementById(btn.dataset.target);
        var isHidden = input.type === 'password';
        input.type = isHidden ? 'text' : 'password';
        btn.querySelector('.icon-eye').style.display = isHidden ? 'none' : '';
        btn.querySelector('.icon-eye-off').style.display = isHidden ? '' : 'none';
        btn.setAttribute('aria-label', isHidden ? 'Sembunyikan sandi' : 'Tampilkan sandi');
    });
});
</script>
</body>
</html>
