<?php if (empty($_GET['embedded'])): ?>
        <div class="text-center small mt-4 mb-2 no-print"><a href="#" class="text-muted" data-bs-toggle="modal" data-bs-target="#termsModal"><i class="fas fa-scale-balanced me-1"></i>Conditions d'utilisation</a></div>
        </div><!-- /#page-content-main -->
        </div><!-- /#win-content-wrapper -->
    </div><!-- /.main-content -->
<?php else: ?>
    </div><!-- /.page-content embedded -->
<?php endif; ?>

<?php
// Conditions d'utilisation : fenêtre bloquante tant que l'utilisateur ne les a pas acceptées (hors affichage embarqué)
if (empty($_GET['embedded']) && function_exists('getCurrentUserId') && getCurrentUserId()) {
    require_once __DIR__ . '/../includes/terms.php';
    renderTermsModal($B . '/terms_accept.php', $B . '/logout.php', !termsAccepted(getCurrentUserId()));
}
?>
    <?php if (isset($extraJs)): foreach((array)$extraJs as $js): ?>
        <script src="<?= $js ?>"></script>
    <?php endforeach; endif; ?>
    <script>
    // Formater un datetime MySQL (Europe/Paris) pour affichage
    function formatLocalDateTime(mysqlDatetime) {
        if (!mysqlDatetime) return '';
        const dt = new Date(mysqlDatetime.replace(' ', 'T'));
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
