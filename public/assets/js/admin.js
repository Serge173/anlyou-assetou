document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('.alert-success').forEach(el => {
        setTimeout(() => el.remove(), 4000);
    });

    const brideInput = document.getElementById('bride_name');
    const groomInput = document.getElementById('groom_name');
    const previewBride = document.getElementById('couplePreviewBride');
    const previewGroom = document.getElementById('couplePreviewGroom');
    const previewMonogram = document.getElementById('couplePreviewMonogram');

    if (!brideInput || !groomInput || !previewBride || !previewGroom || !previewMonogram) {
        return;
    }

    const initialFromName = (name) => {
        const trimmed = name.trim();
        return trimmed ? trimmed.charAt(0).toUpperCase() : '';
    };

    const updateCouplePreview = () => {
        const bride = brideInput.value.trim();
        const groom = groomInput.value.trim();

        previewBride.textContent = bride || '…';
        previewGroom.textContent = groom || '…';

        const brideInitial = initialFromName(bride);
        const groomInitial = initialFromName(groom);
        previewMonogram.textContent = brideInitial || groomInitial
            ? `${brideInitial}&${groomInitial}`
            : '…';
    };

    brideInput.addEventListener('input', updateCouplePreview);
    groomInput.addEventListener('input', updateCouplePreview);
});
