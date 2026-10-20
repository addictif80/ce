<?php
$pageTitle = 'Fiche client 360°';
require_once __DIR__ . '/../../templates/header.php';
require_once __DIR__ . '/../../includes/fiche_client.php';
require_once __DIR__ . '/../../includes/suggestions.php';
require_once __DIR__ . '/../../includes/assist_dossier.php';

$db = getDB();
$userId = getCurrentUserId();
ensureFicheClientSchema();

/** Rend une section sous forme de carte + tableau. */
function ficheRenderSection($titre, $icone, $rows, callable $rowFn) {
    if (empty($rows)) return;
    echo '<div class="card mb-3"><div class="card-header d-flex justify-content-between align-items-center">'
        . '<strong><i class="' . $icone . '"></i> ' . e($titre) . '</strong>'
        . '<span class="badge bg-secondary">' . count($rows) . '</span></div>'
        . '<div class="table-responsive"><table class="table table-sm align-middle mb-0"><tbody>';
    foreach ($rows as $r) echo $rowFn($r);
    echo '</tbody></table></div></div>';
}

// ---------- Traitements POST ----------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $act = $_POST['action'] ?? '';
    $cle = trim((string)($_POST['cle'] ?? ''));
    $num = trim((string)($_POST['numero'] ?? ''));
    $nomIdent = trim((string)($_POST['nom_ident'] ?? ''));

    if ($act === 'add_note' && $cle !== '') {
        addFicheClientNote($db, $userId, $cle, $_POST['message'] ?? '');
    } elseif ($act === 'save_action' && $cle !== '') {
        addFicheClientAction(
            $db, $userId, $cle,
            (string)($_POST['action_label'] ?? ''),
            (string)($_POST['produit'] ?? ''),
            (string)($_POST['source'] ?? 'regle'),
            (string)($_POST['priorite'] ?? 'normale')
        );
    } elseif ($act === 'toggle_action') {
        $db->prepare("UPDATE fiche_client_actions SET statut = IF(statut='a_faire','fait','a_faire') WHERE id = ? AND user_id = ?")
           ->execute([(int)($_POST['id'] ?? 0), $userId]);
    } elseif ($act === 'delete_action') {
        $db->prepare("DELETE FROM fiche_client_actions WHERE id = ? AND user_id = ?")
           ->execute([(int)($_POST['id'] ?? 0), $userId]);
    }
    header('Location: index.php?numero=' . urlencode($num) . '&nom=' . urlencode($nomIdent));
    exit;
}

// ---------- Données ----------
$q      = trim((string)($_GET['q'] ?? ''));
$numero = trim((string)($_GET['numero'] ?? ''));
$nom    = trim((string)($_GET['nom'] ?? ''));

$results = ($q !== '' && $numero === '' && $nom === '') ? searchClients($db, $q, $userId) : [];
$fiche   = ($numero !== '' || $nom !== '') ? getFicheClient($db, $numero, $nom, $userId) : null;
$regles  = $fiche ? getSuggestionsRegles($fiche) : [];
$cle     = $fiche ? ($numero !== '' ? $numero : $nom) : '';
$s       = $fiche['stats'] ?? [];
$aiOn    = assistDossierEnabled();
?>

<div class="mb-3">
    <form class="row g-2" method="GET" action="index.php">
        <div class="col-md-8">
            <div class="input-group">
                <span class="input-group-text"><i class="fas fa-search"></i></span>
                <input type="text" class="form-control" name="q" value="<?= e($q) ?>"
                       placeholder="Rechercher un client par n° de personne ou par nom…" autofocus>
            </div>
        </div>
        <div class="col-md-2"><button class="btn btn-ce w-100" type="submit">Rechercher</button></div>
        <?php if ($fiche): ?><div class="col-md-2"><a class="btn btn-outline-secondary w-100" href="index.php">Nouvelle recherche</a></div><?php endif; ?>
    </form>
    <div class="form-text">La fiche regroupe les éléments que <strong>vous</strong> avez saisis dans les différents modules (instances, demandes, signatures, mobilités, crédit, phoning, envois, courriers…).</div>
</div>

<?php if ($results): ?>
    <div class="data-table-container">
        <div class="data-table-header"><h3><i class="fas fa-users"></i> <?= count($results) ?> client(s) trouvé(s)</h3></div>
        <div class="table-responsive">
            <table class="table align-middle mb-0">
                <thead><tr><th>N° de personne</th><th>Nom</th><th>Présent dans</th><th></th></tr></thead>
                <tbody>
                <?php foreach ($results as $r): ?>
                    <tr>
                        <td><strong><?= e($r['numero'] !== '' ? $r['numero'] : '—') ?></strong></td>
                        <td><?= e($r['nom'] !== '' ? $r['nom'] : '—') ?></td>
                        <td>
                            <?php foreach ($r['sources'] as $src): ?>
                                <span class="badge bg-light text-dark border"><?= e(ficheSourceLabels()[$src] ?? $src) ?></span>
                            <?php endforeach; ?>
                        </td>
                        <td class="text-end">
                            <a class="btn btn-sm btn-ce" href="index.php?numero=<?= urlencode($r['numero']) ?>&nom=<?= urlencode($r['nom']) ?>">
                                <i class="fas fa-address-card"></i> Ouvrir la fiche
                            </a>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
<?php elseif ($q !== ''): ?>
    <div class="alert alert-info">Aucun client trouvé pour « <?= e($q) ?> ».</div>
<?php endif; ?>

<?php if ($fiche): ?>
    <?php
    $labels = ficheSourceLabels();
    $totalElements = count($fiche['instances']) + count($fiche['rappels']) + count($fiche['demandes'])
        + count($fiche['signatures']) + count($fiche['mobilites']) + count($fiche['credit_immo'])
        + count($fiche['envois']) + count($fiche['phoning']) + count($fiche['rappels_clients'])
        + count($fiche['portefeuille']) + count($fiche['interets']) + count($fiche['courriers']);
    ?>

    <div class="card mb-3">
        <div class="card-body d-flex flex-wrap justify-content-between align-items-center gap-3">
            <div>
                <h4 class="mb-1"><i class="fas fa-address-card"></i>
                    <?= e($fiche['nom'] !== '' ? $fiche['nom'] : 'Client') ?>
                    <?php if ($numero !== ''): ?><span class="text-muted">— n° <?= e($numero) ?></span><?php endif; ?>
                </h4>
                <div class="text-muted">
                    <?= count($fiche['instances']) ?> instance(s) ·
                    <?= count($fiche['demandes']) ?> demande(s) ·
                    <?= count($fiche['signatures']) ?> signature(s) ·
                    <?= count($fiche['mobilites']) ?> mobilité(s) ·
                    <?= count($fiche['credit_immo']) ?> crédit(s) ·
                    <?= count($fiche['phoning']) ?> appel(s)
                </div>
            </div>
            <div class="d-flex flex-wrap gap-2">
                <?php if ((int)($s['instance_retard'] ?? 0) > 0): ?><span class="badge bg-danger"><?= (int)$s['instance_retard'] ?> instance(s) en retard</span><?php endif; ?>
                <?php if ((int)($s['demandes_ouvertes'] ?? 0) > 0): ?><span class="badge bg-warning text-dark"><?= (int)$s['demandes_ouvertes'] ?> demande(s) ouverte(s)</span><?php endif; ?>
                <?php if ((int)($s['rappels_ouverts'] ?? 0) > 0): ?><span class="badge bg-warning text-dark"><?= (int)$s['rappels_ouverts'] ?> rappel(s) en attente</span><?php endif; ?>
                <?php if ((int)($s['signatures_en_cours'] ?? 0) > 0): ?><span class="badge bg-info text-dark"><?= (int)$s['signatures_en_cours'] ?> signature(s) en cours</span><?php endif; ?>
                <?php if (!empty($s['dernier_contact'])): ?><span class="badge bg-light text-dark border">Dernier contact : <?= e(formatDate($s['dernier_contact'])) ?></span><?php endif; ?>
            </div>
        </div>
    </div>

    <?php if ($totalElements === 0): ?>
        <div class="alert alert-secondary">
            <i class="fas fa-circle-info"></i> Aucun élément n'est encore rattaché à ce client dans vos modules.
        </div>
    <?php endif; ?>

    <div class="row g-3">
        <div class="col-lg-7">
            <?php
            ficheRenderSection('Instances', 'fas fa-tasks', $fiche['instances'], function ($i) {
                $retard = ($i['statut'] ?? '') === 'a_faire' && !empty($i['date_echeance']) && strtotime($i['date_echeance']) < time();
                return '<tr><td><span class="badge bg-' . (($i['statut'] ?? '') === 'fait' ? 'success' : 'secondary') . '">' . e($i['statut'] ?? '') . '</span></td>'
                    . '<td>' . e(excerpt((string)($i['details'] ?? ''), 120)) . '</td>'
                    . '<td class="text-nowrap">' . e(formatDate($i['date_ajout'] ?? '')) . '</td>'
                    . '<td class="text-nowrap">' . ($retard ? '<span class="badge bg-danger">Échue ' . e(formatDate($i['date_echeance'])) . '</span>' : ($i['date_echeance'] ? e(formatDate($i['date_echeance'])) : '—')) . '</td></tr>';
            });
            ficheRenderSection('Demandes clients', 'fas fa-headset', $fiche['demandes'], function ($d) {
                return '<tr><td><span class="badge bg-' . ((int)($d['traitee'] ?? 0) ? 'success' : 'warning text-dark') . '">' . ((int)($d['traitee'] ?? 0) ? 'traitée' : 'ouverte') . '</span></td>'
                    . '<td>' . e(excerpt((string)($d['details_demande'] ?? ''), 120)) . '<div class="small text-muted">' . e($d['service'] ?? '') . '</div></td>'
                    . '<td class="text-nowrap">' . e(formatDate($d['date_ajout'] ?? '')) . '</td></tr>';
            });
            ficheRenderSection('Demandes de rappel', 'fas fa-phone-alt', $fiche['rappels'], function ($r) {
                return '<tr><td><span class="badge bg-' . ((int)($r['traitee'] ?? 0) ? 'success' : 'warning text-dark') . '">' . ((int)($r['traitee'] ?? 0) ? 'traitée' : 'en attente') . '</span></td>'
                    . '<td>' . e(excerpt((string)($r['motif'] ?? ''), 120)) . '</td>'
                    . '<td class="text-nowrap">' . e(formatDate($r['date_ajout'] ?? '')) . '</td></tr>';
            });
            ficheRenderSection('Dossiers de signature', 'fas fa-file-signature', $fiche['signatures'], function ($sg) {
                return '<tr><td><span class="badge bg-' . (($sg['statut'] ?? '') === 'traite' ? 'success' : 'info text-dark') . '">' . e($sg['statut'] ?? '') . '</span></td>'
                    . '<td>' . e($sg['nom_client'] ?? '') . '</td>'
                    . '<td class="text-nowrap">envoyé le ' . e(formatDate($sg['date_envoi'] ?? '')) . '</td></tr>';
            });
            ficheRenderSection('Mobilités', 'fas fa-people-arrows', $fiche['mobilites'], function ($m) {
                return '<tr><td><span class="badge bg-secondary">' . e($m['etape'] ?? '') . '</span></td>'
                    . '<td>' . e($m['nom_client'] ?? '') . '</td>'
                    . '<td>' . e($m['banque_depart'] ?? '') . '</td>'
                    . '<td class="text-nowrap">' . e(formatDate($m['date_ajout'] ?? '')) . '</td></tr>';
            });
            ficheRenderSection('Crédits immobiliers', 'fas fa-house', $fiche['credit_immo'], function ($c) {
                return '<tr><td>' . e($c['type_credit'] ?? '') . '</td>'
                    . '<td class="text-nowrap">' . e(number_format((float)($c['montant_acquisition'] ?? 0), 0, ',', ' ') . ' €') . '</td>'
                    . '<td><span class="badge bg-' . ((int)($c['suivi_offre_signee'] ?? 0) ? 'success' : 'warning text-dark') . '">offre ' . ((int)($c['suivi_offre_signee'] ?? 0) ? 'signée' : 'à suivre') . '</span></td>'
                    . '<td class="text-nowrap">' . e(formatDate($c['date_ajout'] ?? '')) . '</td></tr>';
            });
            ficheRenderSection('Envois de documents', 'fas fa-paper-plane', $fiche['envois'], function ($e2) {
                return '<tr><td>' . e(trim(($e2['prenom_client'] ?? '') . ' ' . ($e2['nom_client'] ?? ''))) . '</td>'
                    . '<td class="text-nowrap">' . e(formatDate($e2['date_envoi'] ?? '')) . '</td>'
                    . '<td>' . e($e2['objet'] ?? '') . '</td></tr>';
            });
            ficheRenderSection('Appels sortants', 'fas fa-phone', $fiche['phoning'], function ($p) {
                $lbl = ['repondu' => 'Répondu', 'repondeur' => 'Répondeur', 'indisponible' => 'Indisponible', 'rdv' => 'RDV'];
                return '<tr><td><span class="badge bg-secondary">' . e($lbl[$p['resultat'] ?? ''] ?? ($p['resultat'] ?? '')) . '</span>'
                    . ((int)($p['is_anv'] ?? 0) ? ' <span class="badge bg-info text-dark">ANV</span>' : '') . '</td>'
                    . '<td>' . e(excerpt((string)($p['commentaire'] ?? ''), 100)) . '</td>'
                    . '<td class="text-nowrap">' . e(substr((string)($p['created_at'] ?? ''), 0, 10)) . '</td></tr>';
            });
            ficheRenderSection('Rappels clients (demandes en ligne)', 'fas fa-bell', $fiche['rappels_clients'], function ($r) {
                return '<tr><td><span class="badge bg-' . ((int)($r['traitee'] ?? 0) ? 'success' : 'warning text-dark') . '">' . ((int)($r['traitee'] ?? 0) ? 'traitée' : 'en attente') . '</span></td>'
                    . '<td>' . e(excerpt((string)($r['motif'] ?? ''), 120)) . '</td>'
                    . '<td class="text-nowrap">' . e(substr((string)($r['created_at'] ?? ''), 0, 10)) . '</td></tr>';
            });
            ficheRenderSection('Gestion de portefeuille', 'fas fa-briefcase', $fiche['portefeuille'], function ($p) {
                return '<tr><td>' . e($p['type_demande'] ?? '') . '</td>'
                    . '<td>' . e(excerpt((string)($p['motif'] ?? ''), 120)) . '</td>'
                    . '<td><span class="badge bg-' . ((int)($p['traitee'] ?? 0) ? 'success' : 'warning text-dark') . '">' . ((int)($p['traitee'] ?? 0) ? 'traitée' : 'en attente') . '</span></td></tr>';
            });
            ficheRenderSection('Intérêts clients', 'fas fa-star', $fiche['interets'], function ($it) {
                return '<tr><td><span class="badge bg-secondary">' . e($it['categorie'] ?? '') . '</span></td>'
                    . '<td>' . e($it['interet'] ?? '') . '<div class="small text-muted">' . e(excerpt((string)($it['details'] ?? ''), 120)) . '</div></td></tr>';
            });
            ficheRenderSection('Courriers', 'fas fa-envelope', $fiche['courriers'], function ($c) {
                return '<tr><td>' . e(substr((string)($c['date_courrier'] ?? ''), 0, 10)) . '</td>'
                    . '<td>' . e(excerpt((string)($c['objet'] ?? ''), 140)) . '</td></tr>';
            });
            ?>
        </div>

        <div class="col-lg-5">
            <!-- Résumé auto -->
            <div class="card mb-3">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <strong><i class="fas fa-wand-magic-sparkles"></i> Résumé du dossier</strong>
                    <button class="btn btn-sm btn-outline-danger" id="btnSummary" onclick="chargerResume()">
                        <i class="fas fa-rotate"></i> Générer
                    </button>
                </div>
                <div class="card-body" id="summaryBox">
                    <p class="text-muted mb-0">
                        <?php if ($aiOn): ?>
                            Générez une synthèse du dossier. Les données sont <strong>anonymisées</strong> avant tout traitement automatique.
                        <?php else: ?>
                            L'aide IA n'est pas activée : un résumé factuel pourra être généré à partir des éléments du dossier.
                        <?php endif; ?>
                    </p>
                </div>
            </div>

            <!-- Suggestions -->
            <div class="card mb-3">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <strong><i class="fas fa-lightbulb"></i> Prochaines actions</strong>
                    <?php if ($aiOn): ?>
                    <button class="btn btn-sm btn-outline-danger" onclick="enrichirIA()"><i class="fas fa-wand-magic-sparkles"></i> Enrichir (IA)</button>
                    <?php endif; ?>
                </div>
                <div class="list-group list-group-flush" id="suggestionsList">
                    <?php if (!$regles): ?>
                        <div class="list-group-item text-muted">Aucune alerte détectée par les règles pour ce dossier.</div>
                    <?php else: foreach ($regles as $sg): ?>
                        <div class="list-group-item">
                            <div class="d-flex justify-content-between align-items-start gap-2">
                                <div>
                                    <div class="fw-semibold">
                                        <?php
                                        $badge = ['haute' => 'danger', 'normale' => 'warning text-dark', 'basse' => 'secondary'][$sg['priorite']] ?? 'secondary';
                                        ?>
                                        <span class="badge bg-<?= $badge ?>"><?= e($sg['priorite']) ?></span>
                                        <?= e($sg['titre']) ?>
                                    </div>
                                    <div class="small text-muted"><?= e($sg['detail']) ?></div>
                                    <?php if ($sg['produit'] !== ''): ?><div class="small"><i class="fas fa-tag"></i> <?= e($sg['produit']) ?></div><?php endif; ?>
                                </div>
                                <form method="POST" class="d-inline">
                                    <input type="hidden" name="action" value="save_action">
                                    <input type="hidden" name="cle" value="<?= e($cle) ?>">
                                    <input type="hidden" name="numero" value="<?= e($numero) ?>">
                                    <input type="hidden" name="nom_ident" value="<?= e($nom) ?>">
                                    <input type="hidden" name="action_label" value="<?= e($sg['titre']) ?>">
                                    <input type="hidden" name="produit" value="<?= e($sg['produit']) ?>">
                                    <input type="hidden" name="priorite" value="<?= e($sg['priorite']) ?>">
                                    <input type="hidden" name="source" value="regle">
                                    <button class="btn btn-sm btn-outline-ce" title="Retenir cette action"><i class="fas fa-plus"></i></button>
                                </form>
                            </div>
                        </div>
                    <?php endforeach; endif; ?>
                    <div id="suggestionsIA"></div>
                </div>
            </div>

            <!-- Actions retenues -->
            <div class="card mb-3">
                <div class="card-header"><strong><i class="fas fa-list-check"></i> Actions retenues</strong></div>
                <div class="list-group list-group-flush">
                    <?php if (!$fiche['actions']): ?>
                        <div class="list-group-item text-muted">Aucune action enregistrée.</div>
                    <?php else: foreach ($fiche['actions'] as $a): ?>
                        <div class="list-group-item d-flex justify-content-between align-items-center gap-2">
                            <div class="<?= $a['statut'] === 'fait' ? 'text-decoration-line-through text-muted' : '' ?>">
                                <div class="fw-semibold"><?= e($a['action']) ?></div>
                                <?php if ($a['produit'] !== ''): ?><div class="small"><i class="fas fa-tag"></i> <?= e($a['produit']) ?></div><?php endif; ?>
                                <div class="small text-muted"><?= $a['source'] === 'ia' ? 'IA' : 'Règle' ?> · <?= e(formatDate($a['created_at'] ?? '')) ?></div>
                            </div>
                            <div class="d-flex gap-1">
                                <form method="POST"><input type="hidden" name="action" value="toggle_action"><input type="hidden" name="id" value="<?= (int)$a['id'] ?>"><input type="hidden" name="numero" value="<?= e($numero) ?>"><input type="hidden" name="nom_ident" value="<?= e($nom) ?>"><button class="btn btn-sm btn-outline-success" title="Marquer fait/à faire"><i class="fas fa-check"></i></button></form>
                                <form method="POST" onsubmit="return confirm('Supprimer cette action ?');"><input type="hidden" name="action" value="delete_action"><input type="hidden" name="id" value="<?= (int)$a['id'] ?>"><input type="hidden" name="numero" value="<?= e($numero) ?>"><input type="hidden" name="nom_ident" value="<?= e($nom) ?>"><button class="btn btn-sm btn-outline-secondary" title="Supprimer"><i class="fas fa-trash"></i></button></form>
                            </div>
                        </div>
                    <?php endforeach; endif; ?>
                </div>
            </div>

            <!-- Notes internes -->
            <div class="card mb-3">
                <div class="card-header"><strong><i class="fas fa-note-sticky"></i> Notes internes</strong></div>
                <div class="list-group list-group-flush">
                    <?php if (!$fiche['notes']): ?>
                        <div class="list-group-item text-muted">Aucune note.</div>
                    <?php else: foreach ($fiche['notes'] as $n): ?>
                        <div class="list-group-item">
                            <div class="small text-muted mb-1"><?= e(formatDate($n['created_at'] ?? '')) ?></div>
                            <?= nl2br(e($n['message'])) ?>
                        </div>
                    <?php endforeach; endif; ?>
                </div>
                <div class="card-body">
                    <form method="POST">
                        <input type="hidden" name="action" value="add_note">
                        <input type="hidden" name="cle" value="<?= e($cle) ?>">
                        <input type="hidden" name="numero" value="<?= e($numero) ?>">
                        <input type="hidden" name="nom_ident" value="<?= e($nom) ?>">
                        <textarea class="form-control mb-2" name="message" rows="2" placeholder="Ajouter une note interne…" required></textarea>
                        <button class="btn btn-ce btn-sm"><i class="fas fa-plus"></i> Ajouter</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script>
    const FICHE_NUM = <?= json_encode($numero, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;
    const FICHE_NOM = <?= json_encode($nom, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;

    function fichePost(url, extraUrl) {
        const body = new URLSearchParams();
        body.append('numero', FICHE_NUM);
        body.append('nom', FICHE_NOM);
        return fetch(url, { method: 'POST', headers: {'Content-Type': 'application/x-www-form-urlencoded'}, body: body.toString() })
            .then(r => r.json());
    }

    function chargerResume() {
        const box = document.getElementById('summaryBox');
        box.innerHTML = '<div class="text-muted"><i class="fas fa-spinner fa-spin"></i> Génération…</div>';
        fichePost('summary_ajax.php').then(d => {
            if (!d.ok) { box.innerHTML = '<div class="alert alert-warning py-2 mb-0">' + (d.error || 'Erreur') + '</div>'; return; }
            const tag = d.source === 'ia'
                ? '<span class="badge bg-info text-dark mb-2">Synthèse automatique (données anonymisées)</span>'
                : '<span class="badge bg-secondary mb-2">Synthèse locale (IA indisponible)</span>';
            box.innerHTML = tag + '<div style="white-space:pre-wrap">' + escapeHtml(d.text) + '</div>';
        }).catch(() => { box.innerHTML = '<div class="alert alert-danger py-2 mb-0">Erreur de communication.</div>'; });
    }

    function enrichirIA() {
        const zone = document.getElementById('suggestionsIA');
        zone.innerHTML = '<div class="list-group-item text-muted"><i class="fas fa-spinner fa-spin"></i> Analyse en cours…</div>';
        fichePost('suggest_ajax.php').then(d => {
            if (!d.ok) { zone.innerHTML = '<div class="list-group-item text-warning">' + (d.error || 'Erreur') + '</div>'; return; }
            if (!d.suggestions || !d.suggestions.length) { zone.innerHTML = '<div class="list-group-item text-muted">Aucune suggestion supplémentaire.</div>'; return; }
            zone.innerHTML = d.suggestions.map(sg => {
                const p = sg.priorite || 'normale';
                const badge = p === 'haute' ? 'danger' : (p === 'basse' ? 'secondary' : 'warning text-dark');
                return '<div class="list-group-item bg-light">'
                    + '<div class="d-flex justify-content-between align-items-start gap-2"><div>'
                    + '<div class="fw-semibold"><span class="badge bg-' + badge + '">' + p + '</span> <span class="badge bg-info text-dark">IA</span> ' + escapeHtml(sg.titre) + '</div>'
                    + '<div class="small text-muted">' + escapeHtml(sg.detail || '') + '</div>'
                    + (sg.produit ? '<div class="small"><i class="fas fa-tag"></i> ' + escapeHtml(sg.produit) + '</div>' : '')
                    + '</div>'
                    + '<form method="POST" class="d-inline">'
                    + '<input type="hidden" name="action" value="save_action">'
                    + '<input type="hidden" name="cle" value="' + escapeAttr(<?= json_encode($cle, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>) + '">'
                    + '<input type="hidden" name="numero" value="' + escapeAttr(FICHE_NUM) + '">'
                    + '<input type="hidden" name="nom_ident" value="' + escapeAttr(FICHE_NOM) + '">'
                    + '<input type="hidden" name="action_label" value="' + escapeAttr(sg.titre) + '">'
                    + '<input type="hidden" name="produit" value="' + escapeAttr(sg.produit || '') + '">'
                    + '<input type="hidden" name="priorite" value="' + escapeAttr(p) + '">'
                    + '<input type="hidden" name="source" value="ia">'
                    + '<button class="btn btn-sm btn-outline-ce" title="Retenir"><i class="fas fa-plus"></i></button>'
                    + '</form></div></div>';
            }).join('');
        }).catch(() => { zone.innerHTML = '<div class="list-group-item text-danger">Erreur de communication.</div>'; });
    }

    function escapeHtml(s) { const d = document.createElement('div'); d.textContent = s == null ? '' : s; return d.innerHTML; }
    function escapeAttr(s) { return escapeHtml(s).replace(/"/g, '&quot;'); }
    </script>
<?php endif; ?>

<?php require_once __DIR__ . '/../../templates/footer.php'; ?>
