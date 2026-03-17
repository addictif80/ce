        </div><!-- /.page-content -->
    </div><!-- /.main-content -->

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <?php if (isset($extraJs)): foreach((array)$extraJs as $js): ?>
        <script src="<?= $js ?>"></script>
    <?php endforeach; endif; ?>
    <script>
    // Fermer sidebar sur mobile quand on clique un lien
    document.querySelectorAll('.sidebar-nav a').forEach(a => {
        a.addEventListener('click', () => {
            if (window.innerWidth <= 768) document.getElementById('sidebar').classList.remove('active');
        });
    });

    // Recherche dans les tableaux
    function filterTable(inputId, tableId) {
        const input = document.getElementById(inputId);
        if (!input) return;
        input.addEventListener('keyup', function() {
            const filter = this.value.toLowerCase();
            const rows = document.querySelectorAll('#' + tableId + ' tbody tr');
            rows.forEach(row => {
                const text = row.textContent.toLowerCase();
                row.style.display = text.includes(filter) ? '' : 'none';
            });
        });
    }

    // Toggle checkbox via AJAX
    function toggleStatus(url, id, field, cb) {
        const value = cb.checked ? 1 : 0;
        fetch(url, {
            method: 'POST',
            headers: {'Content-Type': 'application/x-www-form-urlencoded'},
            body: `id=${id}&field=${field}&value=${value}`
        }).then(r => r.json()).then(data => {
            if (!data.success) { cb.checked = !cb.checked; alert('Erreur'); }
            else { location.reload(); }
        });
    }
    </script>
</body>
</html>
