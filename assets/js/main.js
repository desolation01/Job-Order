function addChecklistTask(button) {
    var panel = button.closest('.checklist-panel');
    var checklistContainer = panel ? panel.querySelector('[data-checklist]') : null;
    if (!checklistContainer) return;

    var row = document.createElement('div');
    row.className = 'checklist-row';
    row.innerHTML = '<input name="checklist_tasks[]" placeholder="Enter task item"><button type="button" class="button secondary compact" data-remove-task>Remove</button>';
    checklistContainer.appendChild(row);

    var input = row.querySelector('input');
    if (input) input.focus();
}

document.addEventListener('DOMContentLoaded', function () {
    var otherInput = document.querySelector('input[name="other_category_text"]');
    var categoryBoxes = document.querySelectorAll('input[name="categories[]"]');

    function syncOtherField() {
        if (!otherInput) return;

        var enabled = Array.prototype.some.call(categoryBoxes, function (box) {
            return box.checked && box.dataset.categoryName === 'Others';
        });

        otherInput.closest('label').style.display = enabled ? 'flex' : 'none';
        if (!enabled) otherInput.value = '';
    }

    Array.prototype.forEach.call(categoryBoxes, function (box) {
        box.addEventListener('change', syncOtherField);
    });
    syncOtherField();

    var confirmDialog = document.createElement('dialog');
    confirmDialog.className = 'confirm-dialog';
    confirmDialog.innerHTML = '<form method="dialog"><h2>Confirm Action</h2><p data-confirm-message></p><div class="dialog-actions"><button type="button" class="button secondary" data-confirm-cancel>Cancel</button><button type="button" data-confirm-ok>Continue</button></div></form>';
    document.body.appendChild(confirmDialog);

    var pendingConfirmButton = null;
    var confirmMessage = confirmDialog.querySelector('[data-confirm-message]');
    var confirmOk = confirmDialog.querySelector('[data-confirm-ok]');
    var confirmCancel = confirmDialog.querySelector('[data-confirm-cancel]');

    function closeConfirmDialog() {
        pendingConfirmButton = null;
        if (typeof confirmDialog.close === 'function') {
            confirmDialog.close();
        } else {
            confirmDialog.removeAttribute('open');
        }
    }

    Array.prototype.forEach.call(document.querySelectorAll('[data-confirm]'), function (button) {
        button.addEventListener('click', function (event) {
            event.preventDefault();
            pendingConfirmButton = button;
            confirmMessage.textContent = button.dataset.confirm || 'Continue with this action?';

            if (typeof confirmDialog.showModal === 'function') {
                confirmDialog.showModal();
            } else {
                confirmDialog.setAttribute('open', 'open');
            }
        });
    });

    confirmOk.addEventListener('click', function () {
        var button = pendingConfirmButton;
        closeConfirmDialog();
        if (!button) return;

        var form = button.closest('form');
        if (form) {
            form.submit();
            return;
        }

        if (button.href) {
            window.location.href = button.href;
        }
    });

    confirmCancel.addEventListener('click', closeConfirmDialog);

    var upload = document.querySelector('input[type="file"][data-preview]');
    var previewSelector = upload && upload.dataset ? upload.dataset.preview : '';
    var preview = previewSelector ? document.querySelector(previewSelector) : null;
    if (upload && preview) {
        upload.addEventListener('change', function () {
            var file = upload.files && upload.files[0] ? upload.files[0] : null;
            if (!file) return;
            preview.src = URL.createObjectURL(file);
            preview.hidden = false;
        });
    }

    var checklistContainer = document.querySelector('[data-checklist]');
    if (checklistContainer) {
        checklistContainer.addEventListener('click', function (event) {
            var button = event.target.closest('[data-remove-task]');
            if (!button) return;

            var rows = checklistContainer.querySelectorAll('.checklist-row');
            if (rows.length === 1) {
                var input = rows[0].querySelector('input[name="checklist_tasks[]"]');
                if (input) input.value = '';
                return;
            }

            button.closest('.checklist-row').remove();
        });
    }

    Array.prototype.forEach.call(document.querySelectorAll('[data-open-dialog]'), function (button) {
        button.addEventListener('click', function () {
            var dialog = document.getElementById(button.getAttribute('data-open-dialog'));
            if (!dialog) return;

            if (typeof dialog.showModal === 'function') {
                dialog.showModal();
            } else {
                dialog.setAttribute('open', 'open');
            }
        });
    });

    Array.prototype.forEach.call(document.querySelectorAll('[data-close-dialog]'), function (button) {
        button.addEventListener('click', function () {
            var dialog = button.closest('dialog');
            if (!dialog) return;

            if (typeof dialog.close === 'function') {
                dialog.close();
            } else {
                dialog.removeAttribute('open');
            }
        });
    });
});

function savePdfFile(url, filename) {
    if (window.showSaveFilePicker) {
        return fetch(url)
            .then(function (response) {
                return response.blob();
            })
            .then(function (blob) {
                return window.showSaveFilePicker({
                    suggestedName: filename,
                    types: [{
                        description: 'PDF file',
                        accept: { 'application/pdf': ['.pdf'] },
                    }],
                }).then(function (handle) {
                    return handle.createWritable().then(function (writable) {
                        return writable.write(blob).then(function () {
                            return writable.close();
                        });
                    });
                });
            });
    }

    var link = document.createElement('a');
    link.href = url;
    link.download = filename;
    document.body.appendChild(link);
    link.click();
    link.remove();
}
