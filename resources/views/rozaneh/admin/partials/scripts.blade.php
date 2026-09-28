<!-- Admin Common Scripts -->
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const btnToggleMenu = document.getElementById('btn-toggle-admin-menu');
        const sidebar = document.querySelector('aside');
        if (btnToggleMenu && sidebar) {
            btnToggleMenu.addEventListener('click', function() {
                sidebar.classList.toggle('hidden');
            });
        }
    });
</script>
