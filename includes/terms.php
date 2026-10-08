<?php
/**
 * Conditions d'utilisation du portail et avertissement « outils d'aide ».
 * - Portail : chaque utilisateur doit accepter les conditions (table user_terms_accepted, false par défaut) ; tant que ce n'est pas fait,
 *   une fenêtre bloquante s'affiche à chaque page. L'administrateur peut exiger une nouvelle acceptation après modification du texte.
 * - /tools : le même texte est affiché dans une carte en tête de la liste des outils.
 */
function termsDefaultTitle() { return "Outils d'aide : à utiliser avec discernement"; }
function termsDefaultText() {
    return "Les outils et informations proposés ici (simulateurs, calculateurs, listes, formulaires, barèmes…) sont des outils d'aide. Ils ne se substituent pas aux outils internes mis à disposition par le groupe BPCE, qui restent la référence.\n"
        . "Les données et résultats fournis (taux, barèmes, montants, listes de pièces…) doivent être vérifiés avant toute communication à un client.\n"
        . "L'administrateur du portail ne saurait être tenu pour responsable en cas d'erreur, d'omission ou de conseil inadapté résultant de l'utilisation de ces outils.";
}

function termsEnsureSchema() {
    $db = getDB();
    $db->exec("CREATE TABLE IF NOT EXISTS user_terms_accepted (
        user_id INT NOT NULL PRIMARY KEY,
        accepted TINYINT(1) NOT NULL DEFAULT 0,
        version INT NOT NULL DEFAULT 0,
        accepted_at DATETIME DEFAULT NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
}

function getTermsSettings() {
    $title = trim(getToolsSetting('terms_title', ''));
    $text = trim(getToolsSetting('terms_text', ''));
    return ['title' => $title !== '' ? $title : termsDefaultTitle(), 'text' => $text !== '' ? $text : termsDefaultText(), 'rev' => max(1, (int)getToolsSetting('terms_rev', '1'))];
}

function saveTermsSettings($title, $text, $askAgain) {
    setToolsSetting('terms_title', mb_substr(trim($title), 0, 150));
    setToolsSetting('terms_text', mb_substr(trim($text), 0, 4000));
    if ($askAgain) setToolsSetting('terms_rev', (string)(getTermsSettings()['rev'] + 1));
}

/** L'utilisateur a-t-il accepté la version en vigueur des conditions ? */
function termsAccepted($userId) {
    try {
        termsEnsureSchema();
        $db = getDB();
        $db->prepare("INSERT IGNORE INTO user_terms_accepted (user_id, accepted, version) VALUES (?, 0, 0)")->execute([(int)$userId]); // false par défaut
        $st = $db->prepare("SELECT accepted, version FROM user_terms_accepted WHERE user_id = ?");
        $st->execute([(int)$userId]);
        $r = $st->fetch();
        return $r && (int)$r['accepted'] === 1 && (int)$r['version'] >= getTermsSettings()['rev'];
    } catch (Exception $e) {
        return true; // en cas de problème technique, on ne bloque pas l'utilisateur
    }
}

function termsAccept($userId) {
    termsEnsureSchema();
    getDB()->prepare("REPLACE INTO user_terms_accepted (user_id, accepted, version, accepted_at) VALUES (?, 1, ?, NOW())")
        ->execute([(int)$userId, getTermsSettings()['rev']]);
}

/** [utilisateurs ayant accepté la version en vigueur, total des utilisateurs] */
function termsStats() {
    termsEnsureSchema();
    $rev = getTermsSettings()['rev'];
    $db = getDB();
    $total = (int)$db->query("SELECT COUNT(*) FROM users")->fetchColumn();
    $st = $db->prepare("SELECT COUNT(*) FROM user_terms_accepted t JOIN users u ON u.id = t.user_id WHERE t.accepted = 1 AND t.version >= ?");
    $st->execute([$rev]);
    return [(int)$st->fetchColumn(), $total];
}

/** Fenêtre des conditions : bloquante tant que non acceptées ; consultable ensuite via le lien « Conditions d'utilisation ». */
function renderTermsModal($acceptUrl, $logoutUrl, $pending) {
    $t = getTermsSettings();
    $paras = array_filter(array_map('trim', preg_split('/\R/', $t['text'])));
    ?>
<div class="modal fade" id="termsModal" tabindex="-1" aria-hidden="true" <?= $pending ? 'data-bs-backdrop="static" data-bs-keyboard="false"' : '' ?>>
  <div class="modal-dialog modal-dialog-centered modal-lg"><div class="modal-content">
    <div class="modal-header"><h5 class="modal-title"><i class="fas fa-triangle-exclamation text-warning me-2"></i><?= e($t['title']) ?></h5>
        <?php if (!$pending): ?><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fermer"></button><?php endif; ?></div>
    <div class="modal-body">
        <?php foreach ($paras as $p): ?><p><?= e($p) ?></p><?php endforeach; ?>
        <?php if ($pending): ?>
        <div class="form-check mt-3 border rounded p-3 bg-light"><input class="form-check-input ms-0 me-2" type="checkbox" id="termsCheck"><label class="form-check-label fw-semibold" for="termsCheck">J'ai lu ces conditions et je les accepte.</label></div>
        <div class="text-danger small mt-2 d-none" id="termsErr">Une erreur est survenue : réessayez.</div>
        <?php endif; ?>
    </div>
    <div class="modal-footer">
        <?php if ($pending): ?>
        <a href="<?= e($logoutUrl) ?>" class="btn btn-outline-secondary me-auto">Se déconnecter</a>
        <button type="button" class="btn btn-danger" id="termsOk" disabled>Accepter et continuer</button>
        <?php else: ?><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fermer</button><?php endif; ?>
    </div>
  </div></div>
</div>
<?php if ($pending): ?>
<script>
(function () {
    const el = document.getElementById('termsModal'), ok = document.getElementById('termsOk'), chk = document.getElementById('termsCheck');
    const modal = new bootstrap.Modal(el);
    chk.addEventListener('change', () => ok.disabled = !chk.checked);
    ok.addEventListener('click', async () => {
        ok.disabled = true;
        try {
            const r = await fetch(<?= json_encode($acceptUrl) ?>, {method: 'POST', credentials: 'same-origin'});
            const j = await r.json();
            if (j.ok) { modal.hide(); return; }
        } catch (e) {}
        document.getElementById('termsErr').classList.remove('d-none'); ok.disabled = false;
    });
    window.addEventListener('load', () => modal.show());
})();
</script>
<?php endif;
}

/** Carte d'avertissement placée en première position de la liste des outils de /tools */
function renderTermsToolsCard() {
    $t = getTermsSettings();
    $paras = array_filter(array_map('trim', preg_split('/\R/', $t['text'])));
    ?>
<div class="col-md-6 col-lg-4">
    <div class="card h-100 shadow-sm" style="border:2px solid #f0ad4e;background:#fffaf0">
        <div class="card-body">
            <div class="mb-3"><span style="color:#d98200;font-size:2rem"><i class="fas fa-triangle-exclamation"></i></span></div>
            <h2 class="h5"><?= e($t['title']) ?></h2>
            <?php foreach ($paras as $p): ?><p class="small mb-2"><?= e($p) ?></p><?php endforeach; ?>
        </div>
    </div>
</div>
<?php }
