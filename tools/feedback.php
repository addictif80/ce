<?php
require_once __DIR__ . '/_layout.php';
requireToolsAccess(); // avant le traitement du formulaire

$catalog = getPublicToolsCatalog();
$types = ['bug' => 'Un problème', 'suggestion' => 'Une suggestion', 'question' => 'Une question', 'autre' => 'Autre'];
$error = '';
$form = ['type' => 'bug', 'tool' => $_GET['tool'] ?? '', 'message' => '', 'contact' => ''];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $form = [
        'type'    => $_POST['type'] ?? '',
        'tool'    => $_POST['tool'] ?? '',
        'message' => trim($_POST['message'] ?? ''),
        'contact' => trim($_POST['contact'] ?? ''),
    ];

    // Limitation : 5 retours par heure et par visiteur (aucune IP conservée en base)
    $rlFile = sys_get_temp_dir() . '/tools_fb_' . md5($_SERVER['REMOTE_ADDR'] ?? '');
    $now = time();
    $hits = is_file($rlFile) ? array_filter(array_map('intval', file($rlFile, FILE_IGNORE_NEW_LINES)), fn($t) => $t > $now - 3600) : [];

    if (!empty($_POST['website'])) {
        // champ piège rempli par les robots : on fait comme si c'était envoyé
        header('Location: feedback.php?sent=1'); exit;
    } elseif (!isset($types[$form['type']]) || ($form['tool'] !== '' && !isset($catalog[$form['tool']]))) {
        $error = 'Formulaire invalide.';
    } elseif (mb_strlen($form['message']) < 5 || mb_strlen($form['message']) > 2000) {
        $error = 'Votre message doit contenir entre 5 et 2000 caractères.';
    } elseif (mb_strlen($form['contact']) > 255) {
        $error = 'Le moyen de contact est trop long.';
    } elseif (count($hits) >= 5) {
        $error = 'Trop de retours envoyés récemment. Réessayez plus tard.';
    } else {
        ensureToolsFeedbackSchema();
        $db = getDB();
        $db->prepare("INSERT INTO tools_feedback (type, tool_key, message, contact) VALUES (?, ?, ?, ?)")
           ->execute([$form['type'], $form['tool'] ?: null, $form['message'], $form['contact'] ?: null]);
        $hits[] = $now;
        @file_put_contents($rlFile, implode("\n", $hits));

        // Prévenir les administrateurs dans leurs notifications
        $titre = 'Nouveau retour /tools : ' . $types[$form['type']];
        $detail = ($form['tool'] ? $catalog[$form['tool']]['label'] . ' – ' : '') . mb_substr($form['message'], 0, 80);
        foreach ($db->query("SELECT id FROM users WHERE is_admin = 1")->fetchAll() as $adm) {
            createNotification($adm['id'], 'feedback_tools', $titre, $detail, APP_URL . '/modules/admin/index.php?tab=outils_publics');
        }
        header('Location: feedback.php?sent=1'); exit;
    }
}

$GLOBALS['toolsNoFeedbackLink'] = true;
toolsHeader('Envoyer un retour');
?>
<div class="container my-3" style="max-width:720px">
    <?php if (isset($_GET['sent'])): ?>
        <div class="alert alert-success"><i class="fas fa-check-circle me-1"></i>Merci, votre retour a bien été transmis à l'administrateur.</div>
        <a href="./" class="btn btn-outline-secondary"><i class="fas fa-th-large me-1"></i>Retour aux outils</a>
    <?php else: ?>
    <div class="alert alert-warning no-print">
        <i class="fas fa-info-circle me-1"></i><strong>Ici, votre message est enregistré.</strong>
        Contrairement aux outils, l'envoi d'un retour conserve votre message (et le moyen de contact si vous en indiquez un) pour que l'administrateur puisse le lire.
        N'y mettez pas de données personnelles sensibles.
    </div>
    <?php if ($error): ?><div class="alert alert-danger"><?= e($error) ?></div><?php endif; ?>
    <form method="post" class="card"><div class="card-body">
        <div class="row g-3">
            <div class="col-md-6">
                <label class="form-label">Nature du retour</label>
                <select name="type" class="form-select">
                    <?php foreach ($types as $k => $l): ?><option value="<?= e($k) ?>" <?= $form['type'] === $k ? 'selected' : '' ?>><?= e($l) ?></option><?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-6">
                <label class="form-label">Outil concerné</label>
                <select name="tool" class="form-select">
                    <option value="">Général / autre</option>
                    <?php foreach ($catalog as $k => $t): ?><option value="<?= e($k) ?>" <?= $form['tool'] === $k ? 'selected' : '' ?>><?= e($t['label']) ?></option><?php endforeach; ?>
                </select>
            </div>
            <div class="col-12">
                <label class="form-label">Votre message <span class="text-danger">*</span></label>
                <textarea name="message" class="form-control" rows="6" maxlength="2000" required><?= e($form['message']) ?></textarea>
            </div>
            <div class="col-12">
                <label class="form-label">Moyen de vous répondre <small class="text-muted">(facultatif : e-mail ou téléphone)</small></label>
                <input type="text" name="contact" class="form-control" maxlength="255" value="<?= e($form['contact']) ?>" autocomplete="off">
            </div>
            <div style="position:absolute;left:-9999px" aria-hidden="true"><input type="text" name="website" tabindex="-1" autocomplete="off"></div>
            <div class="col-12 d-flex gap-2">
                <button type="submit" class="btn btn-danger"><i class="fas fa-paper-plane me-1"></i>Envoyer</button>
                <a href="./" class="btn btn-outline-secondary">Annuler</a>
            </div>
        </div>
    </div></form>
    <?php endif; ?>
</div>
<?php toolsFooter();
