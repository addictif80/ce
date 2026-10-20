<?php
// Onglet « Journal /tools » : journal d'audit immuable des clics, saisies et actions (voir includes/audit.php). Lecture seule.
require_once __DIR__ . '/../../includes/audit.php';
auditEnsureSchema();
$db = getDB();
$catalog = getPublicToolsCatalog();
$events = auditEvents();
$per = 100; $page = max(1, (int)($_GET['p'] ?? 1));
[$where, $params] = auditFilters($_GET);
$st = $db->prepare("SELECT COUNT(*) FROM " . AUDIT_TABLE . " $where"); $st->execute($params); $total = (int)$st->fetchColumn();
$pages = max(1, (int)ceil($total / $per)); $page = min($page, $pages);
$st = $db->prepare("SELECT * FROM " . AUDIT_TABLE . " $where ORDER BY id DESC LIMIT $per OFFSET " . (($page - 1) * $per)); $st->execute($params); $rows = $st->fetchAll(PDO::FETCH_ASSOC);
$all = (int)$db->query("SELECT COUNT(*) FROM " . AUDIT_TABLE)->fetchColumn();
$head = (string)($db->query("SELECT hash FROM " . AUDIT_TABLE . " ORDER BY id DESC LIMIT 1")->fetchColumn() ?: '');
$check = json_decode(getToolsSetting('audit_check', ''), true);
$qs = $_GET; unset($qs['p']); $base = '?' . http_build_query($qs + ['tab' => 'journal']);
?>
<div class="alert alert-info">
    <strong><i class="fas fa-shield-halved"></i> Journal immuable de /tools</strong>
    Chaque ouverture de page, clic, saisie ou choix, impression et copie effectués sur /tools est ajouté à ce journal, avec la date (heure du serveur), l'acteur
    (utilisateur connecté ou libellé du code d'accès), l'adresse IP, le navigateur et, pour les saisies, la valeur (500 caractères au plus).
    Sont toujours masqués : les mots de passe et les numéros sensibles (IBAN, RIB, BIC, carte, sécurité sociale, SIREN/SIRET) ; l'outil « Validateurs de numéros » ne conserve aucune valeur.
    <br><strong>Immuable :</strong> la base refuse toute modification ou suppression de ces lignes, et chaque ligne porte l'empreinte de la précédente : toute altération est détectée par la vérification ci-dessous. Aucune suppression n'est possible depuis l'application.
</div>
<div class="row g-3 mb-3">
    <div class="col-md-3"><div class="card"><div class="card-body py-2"><div class="small text-muted">Lignes enregistrées</div><div class="h4 mb-0"><?= number_format($all, 0, ',', ' ') ?></div></div></div></div>
    <div class="col-md-3"><div class="card"><div class="card-body py-2"><div class="small text-muted">Protection de la base</div>
        <?php if (auditTriggersOk()): ?><span class="badge bg-success">Déclencheurs actifs</span><?php else: ?><span class="badge bg-warning text-dark" title="Le compte MySQL n'a pas le privilège TRIGGER : seule la chaîne d'empreintes protège le journal">Déclencheurs absents</span><?php endif; ?></div></div></div>
    <div class="col-md-6" id="verif"><div class="card"><div class="card-body py-2 d-flex align-items-center gap-3 flex-wrap">
        <form method="post" class="mb-0"><input type="hidden" name="action" value="verify_audit"><button class="btn btn-sm btn-outline-secondary"><i class="fas fa-link me-1"></i>Vérifier l'intégrité</button></form>
        <div class="small">
        <?php if ($check): ?>
            <?php if (!empty($check['ok'])): ?><span class="badge bg-success">Chaîne intacte</span> <?= (int)$check['rows'] ?> lignes vérifiées le <?= e($check['at'] ?? '') ?>
            <?php else: ?><span class="badge bg-danger">Altération détectée</span> ligne n° <?= (int)$check['bad_id'] ?> — <?= e($check['reason']) ?> (<?= e($check['at'] ?? '') ?>)<?php endif; ?>
        <?php else: ?><span class="text-muted">Aucune vérification effectuée.</span><?php endif; ?>
        </div>
    </div></div></div>
</div>
<?php if ($head): ?><div class="small text-muted mb-3">Empreinte de la dernière ligne (à noter ailleurs pour prouver qu'aucune ligne récente n'a été retirée) : <code style="word-break:break-all"><?= e($head) ?></code></div><?php endif; ?>
<form method="get" class="card card-body mb-3"><input type="hidden" name="tab" value="journal">
    <div class="row g-2 align-items-end">
        <div class="col-md-2"><label class="form-label small mb-0">Du</label><input type="date" name="du" class="form-control form-control-sm" value="<?= e($_GET['du'] ?? '') ?>"></div>
        <div class="col-md-2"><label class="form-label small mb-0">Au</label><input type="date" name="au" class="form-control form-control-sm" value="<?= e($_GET['au'] ?? '') ?>"></div>
        <div class="col-md-2"><label class="form-label small mb-0">Outil</label><select name="outil" class="form-select form-select-sm"><option value="">Tous</option><option value="accueil" <?= ($_GET['outil'] ?? '') === 'accueil' ? 'selected' : '' ?>>Accueil de /tools</option><?php foreach ($catalog as $k => $t): ?><option value="<?= e($k) ?>" <?= ($_GET['outil'] ?? '') === $k ? 'selected' : '' ?>><?= e($t['label']) ?></option><?php endforeach; ?></select></div>
        <div class="col-md-2"><label class="form-label small mb-0">Événement</label><select name="ev" class="form-select form-select-sm"><option value="">Tous</option><?php foreach ($events as $k => $l): ?><option value="<?= e($k) ?>" <?= ($_GET['ev'] ?? '') === $k ? 'selected' : '' ?>><?= e($l) ?></option><?php endforeach; ?></select></div>
        <div class="col-md-2"><label class="form-label small mb-0">Acteur / IP</label><input type="text" name="acteur" class="form-control form-control-sm" value="<?= e($_GET['acteur'] ?? '') ?>"></div>
        <div class="col-md-2"><label class="form-label small mb-0">Texte (cible, valeur)</label><input type="text" name="texte" class="form-control form-control-sm" value="<?= e($_GET['texte'] ?? '') ?>"></div>
        <div class="col-12 d-flex gap-2"><button class="btn btn-sm btn-ce">Filtrer</button><a class="btn btn-sm btn-outline-secondary" href="?tab=journal">Réinitialiser</a>
            <a class="btn btn-sm btn-outline-secondary ms-auto" href="?<?= e(http_build_query(array_merge($qs, ['export' => 'audit']))) ?>"><i class="fas fa-file-csv me-1"></i>Exporter en CSV (<?= number_format($total, 0, ',', ' ') ?> lignes)</a></div>
    </div>
</form>
<div class="data-table-container"><div style="overflow-x:auto"><table class="data-table" style="font-size:12.5px">
    <thead><tr><th>N°</th><th>Date</th><th>Acteur</th><th>IP</th><th>Outil</th><th>Événement</th><th>Cible</th><th>Valeur</th></tr></thead>
    <tbody>
    <?php foreach ($rows as $r): ?>
    <tr title="<?= e($r['ua'] . ' — session ' . $r['sid'] . ' — ' . $r['page']) ?>">
        <td><?= (int)$r['id'] ?></td><td style="white-space:nowrap"><?= e($r['ts']) ?></td><td><?= e($r['actor']) ?></td><td><?= e($r['ip']) ?></td>
        <td><?= e($catalog[$r['tool_key']]['label'] ?? ($r['tool_key'] === 'accueil' ? 'Accueil' : $r['tool_key'])) ?></td>
        <td><span class="badge bg-secondary"><?= e($events[$r['event']] ?? $r['event']) ?></span></td>
        <td><?= e($r['target']) ?></td><td style="word-break:break-word;max-width:340px"><?= e((string)$r['value']) ?></td>
    </tr>
    <?php endforeach; ?>
    <?php if (!$rows): ?><tr><td colspan="8" class="text-center text-muted py-4">Aucune ligne.</td></tr><?php endif; ?>
    </tbody></table></div></div>
<?php if ($pages > 1): ?>
<nav class="mt-3 d-flex gap-2 align-items-center"><?php if ($page > 1): ?><a class="btn btn-sm btn-outline-secondary" href="<?= e($base . '&p=' . ($page - 1)) ?>">&laquo; Plus récentes</a><?php endif; ?>
    <span class="small text-muted">Page <?= $page ?> / <?= $pages ?></span>
    <?php if ($page < $pages): ?><a class="btn btn-sm btn-outline-secondary" href="<?= e($base . '&p=' . ($page + 1)) ?>">Plus anciennes &raquo;</a><?php endif; ?></nav>
<?php endif; ?>
