<?php if (empty($_GET['embedded'])): ?>
        </div><!-- /#page-content-main -->
        </div><!-- /#win-content-wrapper -->
    </div><!-- /.main-content -->
<?php else: ?>
    </div><!-- /.page-content embedded -->
<?php endif; ?>

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
    <?php if (empty($_GET['embedded'])): ?>
    // Fermer sidebar sur mobile quand on clique un lien
    document.querySelectorAll('.sidebar-nav a').forEach(a => {
        a.addEventListener('click', () => {
            if (window.innerWidth <= 768) document.getElementById('sidebar').classList.remove('active');
        });
    });
    <?php endif; ?>
    </script>
<?php if (empty($_GET['embedded'])): ?>
    <script src="<?= $B ?>/assets/js/window-manager.js?v=<?= filemtime(__DIR__ . '/../assets/js/window-manager.js') ?>"></script>
<?php endif; ?>
</body>
</html>
