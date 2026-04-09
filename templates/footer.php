        </div><!-- /.page-content -->
    </div><!-- /.main-content -->

    <?php if (isset($extraJs)): foreach((array)$extraJs as $js): ?>
        <script src="<?= $js ?>"></script>
    <?php endforeach; endif; ?>
    <script>
    // Convertir un datetime MySQL (UTC) en heure locale
    function formatLocalDateTime(mysqlDatetime) {
        if (!mysqlDatetime) return '';
        const dt = new Date(mysqlDatetime.replace(' ', 'T') + 'Z');
        return dt.toLocaleString('fr-FR', { day: '2-digit', month: '2-digit', year: 'numeric', hour: '2-digit', minute: '2-digit' });
    }
    // Fermer sidebar sur mobile quand on clique un lien
    document.querySelectorAll('.sidebar-nav a').forEach(a => {
        a.addEventListener('click', () => {
            if (window.innerWidth <= 768) document.getElementById('sidebar').classList.remove('active');
        });
    });
    </script>
</body>
</html>
