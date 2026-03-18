        </div><!-- /.page-content -->
    </div><!-- /.main-content -->

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
    </script>
</body>
</html>
