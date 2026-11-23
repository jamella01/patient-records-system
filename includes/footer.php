<?php
/**
 * footer.php
 * ------------------------------------------------------------
 * Closes the page shell, holds the shared delete-confirmation
 * modal and loads Bootstrap JS + small helper scripts.
 * ------------------------------------------------------------
 */
?>
        </main>

        <footer class="app-footer d-print-none">
            &copy; <?= date('Y') ?> <?= e(APP_NAME) ?> &middot; PHP &amp; MySQL CRUD Project
        </footer>
    </div>
</div>

<!-- Shared delete confirmation modal.
     Any button with  data-delete-url="..."  data-delete-id="..."  data-delete-name="..."
     opens it and submits a POST request (deletes are never plain GET links). -->
<div class="modal fade" id="deleteModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <form method="post" class="modal-content" id="deleteForm">
            <?= csrf_field() ?>
            <input type="hidden" name="id" id="deleteId">
            <div class="modal-body text-center p-4">
                <div class="delete-icon mb-3"><i class="bi bi-trash3"></i></div>
                <h5 class="mb-2">Delete this record?</h5>
                <p class="text-muted mb-0">
                    You are about to delete <strong id="deleteName"></strong>.
                    <span id="deleteExtra"></span> This action cannot be undone.
                </p>
            </div>
            <div class="modal-footer border-0 justify-content-center pt-0 pb-4">
                <button type="button" class="btn btn-light px-4" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" class="btn btn-danger px-4">Yes, delete</button>
            </div>
        </form>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
    // Fill and open the delete modal from any [data-delete-url] button.
    document.addEventListener('click', function (ev) {
        const btn = ev.target.closest('[data-delete-url]');
        if (!btn) return;
        document.getElementById('deleteForm').action = btn.dataset.deleteUrl;
        document.getElementById('deleteId').value = btn.dataset.deleteId;
        document.getElementById('deleteName').textContent = btn.dataset.deleteName || 'this record';
        document.getElementById('deleteExtra').textContent = btn.dataset.deleteExtra || '';
        bootstrap.Modal.getOrCreateInstance('#deleteModal').show();
    });

    // Live search: auto-submit a filter form shortly after the user stops typing.
    document.querySelectorAll('[data-live-search]').forEach(function (input) {
        let timer;
        input.addEventListener('input', function () {
            clearTimeout(timer);
            timer = setTimeout(() => input.form.submit(), 450);
        });
        // Keep the cursor at the end of the text after the page reloads.
        if (input.value) {
            input.focus();
            input.setSelectionRange(input.value.length, input.value.length);
        }
    });

    // Auto-hide success alerts after a few seconds.
    setTimeout(function () {
        document.querySelectorAll('.alert-success').forEach(a => bootstrap.Alert.getOrCreateInstance(a).close());
    }, 4000);
</script>
<?php if (!empty($pageScripts)) echo $pageScripts; ?>
</body>
</html>
