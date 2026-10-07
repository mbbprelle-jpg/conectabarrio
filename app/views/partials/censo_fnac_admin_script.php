<script>
(function () {
    function edadCompleta(iso) {
        if (!/^\d{4}-\d{2}-\d{2}$/.test(iso)) return null;
        var p = iso.split('-');
        var y = parseInt(p[0], 10);
        var m = parseInt(p[1], 10);
        var d = parseInt(p[2], 10);
        var birth = new Date(y, m - 1, d);
        if (birth.getFullYear() !== y || birth.getMonth() !== m - 1 || birth.getDate() !== d) return null;
        var today = new Date();
        today.setHours(0, 0, 0, 0);
        birth.setHours(0, 0, 0, 0);
        if (birth.getTime() > today.getTime()) return -1;
        var age = today.getFullYear() - y;
        var monthDiff = (today.getMonth() + 1) - m;
        if (monthDiff < 0 || (monthDiff === 0 && today.getDate() < d)) age--;
        return age;
    }

    document.querySelectorAll('.cb-fnac-admin').forEach(function (input) {
        var preview = input.parentNode.querySelector('.cb-fnac-preview');
        var max = parseInt(input.getAttribute('data-max') || '8', 10);
        function sync() {
            if (!preview) return;
            var raw = String(input.value || '').trim();
            if (raw === '') {
                preview.textContent = '';
                preview.classList.remove('is-error');
                return;
            }
            var age = edadCompleta(raw);
            if (age === null || age < 0) {
                preview.textContent = 'Fecha no válida';
                preview.classList.add('is-error');
                return;
            }
            if (age > max) {
                preview.textContent = age + ' años, fuera de 0 a ' + max;
                preview.classList.add('is-error');
                return;
            }
            preview.textContent = age === 1 ? '1 año' : (age + ' años');
            preview.classList.remove('is-error');
        }
        input.addEventListener('input', sync);
        input.addEventListener('change', sync);
        sync();
    });
})();
</script>
