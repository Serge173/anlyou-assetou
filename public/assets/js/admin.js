// Admin JS placeholder — extend as needed
document.addEventListener('DOMContentLoaded', () => {
    // Auto-dismiss alerts
    document.querySelectorAll('.alert-success').forEach(el => {
        setTimeout(() => el.remove(), 4000);
    });
});
