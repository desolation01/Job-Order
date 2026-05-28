document.addEventListener('DOMContentLoaded', () => {
    const otherInput = document.querySelector('input[name="other_category_text"]');
    const categoryBoxes = document.querySelectorAll('input[name="categories[]"]');

    function syncOtherField() {
        if (!otherInput) return;
        const enabled = Array.from(categoryBoxes).some((box) => box.checked && box.dataset.categoryName === 'Others');
        otherInput.closest('label').style.display = enabled ? 'flex' : 'none';
        if (!enabled) otherInput.value = '';
    }

    categoryBoxes.forEach((box) => box.addEventListener('change', syncOtherField));
    syncOtherField();

    document.querySelectorAll('[data-confirm]').forEach((button) => {
        button.addEventListener('click', (event) => {
            if (!confirm(button.dataset.confirm)) {
                event.preventDefault();
            }
        });
    });

    const upload = document.querySelector('input[type="file"][data-preview]');
    const preview = document.querySelector(upload?.dataset.preview || '');
    if (upload && preview) {
        upload.addEventListener('change', () => {
            const file = upload.files?.[0];
            if (!file) return;
            preview.src = URL.createObjectURL(file);
            preview.hidden = false;
        });
    }
});

async function savePdfFile(url, filename) {
    if (window.showSaveFilePicker) {
        const response = await fetch(url);
        const blob = await response.blob();
        const handle = await window.showSaveFilePicker({
            suggestedName: filename,
            types: [{
                description: 'PDF file',
                accept: { 'application/pdf': ['.pdf'] },
            }],
        });
        const writable = await handle.createWritable();
        await writable.write(blob);
        await writable.close();
        return;
    }

    const link = document.createElement('a');
    link.href = url;
    link.download = filename;
    document.body.appendChild(link);
    link.click();
    link.remove();
}
