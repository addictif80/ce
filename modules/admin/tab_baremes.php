<?php
// Onglet « Barèmes » de l'administration : formulaires libellés (sans JSON), sources à consulter et suivi du contrôle.
require_once __DIR__ . '/../../includes/baremes.php';
require_once __DIR__ . '/../../includes/baremes_forms.php';
require_once __DIR__ . '/../../includes/bareme_ia.php';
$iaOn = assistEnabled();
$cat = baremeCatalog();
$err = $_SESSION['bareme_err'] ?? null; unset($_SESSION['bareme_err']);
$badges = ['ok' => ['success', 'Contrôlé'], 'provisoire' => ['warning text-dark', 'À contrôler'], 'ancien' => ['danger', 'À revoir : plus de 12 mois']];
?>
<div class="alert alert-info">
    <strong><i class="fas fa-circle-info"></i> À quoi servent les barèmes ?</strong>
    Ce sont les chiffres réglementaires utilisés par les simulateurs (PTZ, frais de notaire, capacité d'emprunt) du portail et de /tools.
    Pour chaque barème : 1) ouvrez les sources indiquées, 2) comparez avec les valeurs du formulaire et corrigez si besoin, 3) cochez « J'ai contrôlé » et enregistrez.
    Tant que ce n'est pas fait, les simulateurs affichent « barème à confirmer ». Le barème repasse « à revoir » au bout de 12 mois.
</div>
<div class="data-table-container mb-4" id="bareme-ia-global">
    <div class="data-table-header"><h3><i class="fas fa-robot"></i> Vérification assistée par IA</h3>
        <?php if ($iaOn): ?><button type="button" class="btn btn-ce btn-sm" id="bia-all"><i class="fas fa-list-check"></i> Vérifier tous les barèmes</button><?php else: ?><span class="badge bg-secondary">Assistant IA non activé</span><?php endif; ?></div>
    <div class="p-3">
        <p class="text-muted mb-2">L'IA compare les valeurs de chaque barème à des extraits de sites officiels, <strong>service-public.fr en priorité</strong>, puis des sites réputés (liste ci-dessous). Elle ne s'appuie que sur ces extraits et doit les citer : le portail recontrôle chaque citation. C'est un <strong>avis</strong> : vous restez seul à pouvoir déclarer un barème « contrôlé ».</p>
        <?php if (!$iaOn): ?><div class="alert alert-warning py-2">Activez l'assistant et renseignez la clé API (onglet de l'assistant IA) pour utiliser cette fonction.</div><?php endif; ?>
        <div id="bia-progress" class="small mb-2" style="display:none"></div>
        <div id="bia-summary"></div>
        <details class="mt-2"><summary class="small text-primary" style="cursor:pointer">Sites autorisés pour la vérification</summary>
            <form method="post" class="mt-2"><input type="hidden" name="action" value="save_bareme_ia_domains">
                <textarea name="domains" class="form-control form-control-sm" rows="3" placeholder="<?= e(implode(' ', baremeIaDefaultDomains())) ?>"><?= e(trim((string)getToolsSetting('bareme_ia_domains', ''))) ?></textarea>
                <div class="small text-muted mt-1">Un domaine par ligne ou séparés par des espaces (les sous-domaines sont inclus). Vide = liste par défaut : <?= e(implode(', ', baremeIaDefaultDomains())) ?>. Seules des pages en https de ces sites sont consultées ; le portail ne lance pas de recherche libre sur internet.</div>
                <button class="btn btn-outline-secondary btn-sm mt-2">Enregistrer la liste</button></form></details>
    </div>
</div>
<?php
require_once __DIR__ . '/../../includes/zonage.php';
$zst = zonageStats();
$zres = $_SESSION['zonage_res'] ?? null; unset($_SESSION['zonage_res']);
?>
<div class="data-table-container mb-4" id="zonage-card">
    <div class="data-table-header"><h3><i class="fas fa-map-location-dot"></i> Zonage A/B/C des communes</h3>
        <span class="badge bg-<?= $zst['count'] ? 'success' : 'warning text-dark' ?>"><?= $zst['count'] ? number_format($zst['count'], 0, ',', ' ') . ' communes' : 'Non importé' ?></span></div>
    <div class="p-3">
        <p class="text-muted">Permet au simulateur PTZ de <strong>déduire la zone (A, B1, B2, C) à partir du département et de la commune</strong>, au lieu de la demander à la main. Sans fichier importé, le simulateur garde le choix manuel de la zone.
            <?php if ($zst['count']): ?><br><strong>Liste actuelle :</strong> <?= e($zst['libelle'] ?: 'fichier importé') ?> — importée le <?= e($zst['date'] ? date('d/m/Y', strtotime($zst['date'])) : '?') ?> (<?= e($zst['source']) ?>).<?php endif; ?></p>
        <?php if ($zres): ?><div class="alert alert-<?= $zres['ok'] ? 'success' : 'danger' ?> py-2"><?= e($zres['message']) ?></div><?php endif; ?>
        <div class="row g-3 mb-3">
            <div class="col-lg-7"><div class="border rounded p-3 h-100 bg-light">
                <div class="fw-semibold mb-2"><i class="fas fa-magnifying-glass"></i> Où trouver le fichier</div>
                <div class="mb-2"><a href="https://www.data.gouv.fr/datasets/liste-des-communes-selon-le-zonage-abc" target="_blank" rel="noopener noreferrer">data.gouv.fr – Liste des communes selon le zonage ABC <i class="fas fa-arrow-up-right-from-square small"></i></a>
                    <div class="small text-muted">Page officielle du ministère : téléchargez le fichier <strong>CSV le plus volumineux (≈ 850 Ko, environ 34 900 communes)</strong>, celui du dernier arrêté en vigueur. Les autres fichiers de la page (≈ 20 Ko et ≈ 1 Ko) ne contiennent que les communes reclassées et ne conviennent pas pour un import complet.</div></div>
                <div class="small text-muted">Fichier de l'arrêté du 23 juin 2026 (en vigueur depuis le 26 juin 2026) : <a href="https://static.data.gouv.fr/resources/liste-des-communes-selon-le-zonage-abc/20260703-091314/liste-ensemble-des-communes-zonage-abc-en-vigueur-26-juin-2026.csv" target="_blank" rel="noopener noreferrer">téléchargement direct</a>. Le zonage est révisé par arrêté : réimportez le nouveau fichier à chaque révision.</div>
            </div></div>
            <div class="col-lg-5"><div class="border rounded p-3 h-100">
                <div class="fw-semibold mb-2"><i class="fas fa-clipboard-check"></i> Contrôle</div>
                <div class="small">Après import, comparez quelques communes avec le <a href="https://www.anil.org/outils/outils-de-calcul/votre-pret-a-taux-zero/" target="_blank" rel="noopener noreferrer">simulateur de l'ANIL</a>. Le fichier du 26 juin 2026 a été comparé avec l'ANIL sur les 586 communes de la Haute-Garonne : zones identiques.</div>
            </div></div>
        </div>
        <form method="post" enctype="multipart/form-data" class="row g-2 align-items-end">
            <input type="hidden" name="action" value="import_zonage">
            <div class="col-md-6"><label class="form-label">Fichier CSV</label><input type="file" name="zonage" accept=".csv,text/csv,text/plain" class="form-control" required></div>
            <div class="col-md-4"><div class="form-check"><input class="form-check-input" type="checkbox" name="remplacer" id="zrem" checked><label class="form-check-label" for="zrem">Remplacer toute la liste existante <span class="text-muted small">(à décocher pour un fichier partiel)</span></label></div></div>
            <div class="col-md-2 text-end"><button class="btn btn-ce btn-sm"><i class="fas fa-file-import"></i> Importer</button></div>
        </form>
        <?php if ($zst['count']): ?>
        <form method="post" class="mt-2" onsubmit="return confirm('Vider la liste des communes ? Le simulateur reviendra au choix manuel de la zone.')"><input type="hidden" name="action" value="clear_zonage"><button class="btn btn-outline-danger btn-sm"><i class="fas fa-trash"></i> Vider la liste</button></form>
        <?php endif; ?>
    </div>
</div>
<?php foreach ($cat as $cle => $c):
    $m = baremeGet($cle); $st = baremeStatut($m);
    $data = ($err && $err[0] === $cle) ? json_decode(json_encode($err[2]), true) : $m['data'];
    if ($cle === 'notaire' && !isset($data['departements'])) $data['departements'] = [];
?>
<div class="data-table-container mb-4" id="bareme-<?= e($cle) ?>">
    <div class="data-table-header"><h3><i class="fas <?= e($c['icon']) ?>"></i> <?= e($c['label']) ?></h3>
        <span class="badge bg-<?= $badges[$st][0] ?>"><?= $badges[$st][1] ?></span></div>
    <div class="p-3">
        <p class="text-muted"><?= e($c['help']) ?><?= $m['date'] ? ' Dernier contrôle déclaré : <strong>' . e(date('d/m/Y', strtotime($m['date']))) . '</strong>.' : '' ?></p>

        <div class="row g-3 mb-3">
            <div class="col-lg-7"><div class="border rounded p-3 h-100 bg-light">
                <div class="fw-semibold mb-2"><i class="fas fa-magnifying-glass"></i> Où vérifier ces valeurs</div>
                <?php foreach ($c['sources'] as [$lib, $url, $quoi]): ?>
                    <div class="mb-2"><?php if ($url !== ''): ?><a href="<?= e($url) ?>" target="_blank" rel="noopener noreferrer"><?= e($lib) ?> <i class="fas fa-arrow-up-right-from-square small"></i></a><?php else: ?><strong><?= e($lib) ?></strong><?php endif; ?><div class="small text-muted"><?= e($quoi) ?></div></div>
                <?php endforeach; ?>
            </div></div>
            <div class="col-lg-5"><div class="border rounded p-3 h-100">
                <div class="fw-semibold mb-2"><i class="fas fa-clipboard-check"></i> État du contrôle</div>
                <?php $v = $c['verifie']; ?>
                <?php if ($v['ok']): ?><div class="small mb-2"><span class="text-success fw-semibold"><i class="fas fa-check"></i> Déjà comparé avec <?= e($v['source']) ?> le <?= e($v['date']) ?> :</span> <?= e($v['ok']) ?>.</div><?php endif; ?>
                <?php if ($v['ko']): ?><div class="small"><span class="text-danger fw-semibold"><i class="fas fa-triangle-exclamation"></i> Reste à contrôler :</span> <?= e($v['ko']) ?>.</div><?php endif; ?>
            </div></div>
        </div>

        <?php if ($iaOn && !baremeIsMemo($cle)): $ia = baremeIaLast($cle); ?>
        <div class="mb-3"><button type="button" class="btn btn-outline-primary btn-sm bia-one" data-cle="<?= e($cle) ?>"><i class="fas fa-robot"></i> Faire vérifier par l'IA</button>
            <span class="small text-muted ms-2 bia-wait" style="display:none"><i class="fas fa-spinner fa-spin"></i> Consultation des sources et comparaison en cours (jusqu'à une minute)…</span>
            <div class="mt-2 bia-res" id="bia-<?= e($cle) ?>"><?= $ia ? baremeIaRender($ia) : '' ?></div></div>
        <?php endif; ?>
        <?php if ($err && $err[0] === $cle): ?><div class="alert alert-danger py-2"><i class="fas fa-circle-xmark"></i> <?= e($err[1]) ?></div><?php endif; ?>
        <form method="post">
            <input type="hidden" name="action" value="save_bareme"><input type="hidden" name="cle" value="<?= e($cle) ?>">
            <?= baremeRenderForm($cle, $data) ?>
            <div class="border-top mt-4 pt-3 row g-2 align-items-center">
                <div class="col-auto"><label class="col-form-label">Date du contrôle</label></div>
                <div class="col-auto"><input type="date" name="date_reference" class="form-control form-control-sm" value="<?= e($m['date'] ?: date('Y-m-d')) ?>"></div>
                <div class="col-auto"><div class="form-check"><input class="form-check-input" type="checkbox" name="valide" id="bv-<?= e($cle) ?>" <?= $m['valide'] ? 'checked' : '' ?>><label class="form-check-label" for="bv-<?= e($cle) ?>"><strong>J'ai contrôlé ces valeurs</strong> sur les sources ci-dessus</label></div></div>
                <div class="col text-end">
                    <button class="btn btn-ce btn-sm"><i class="fas fa-save"></i> Enregistrer</button>
                    <button class="btn btn-outline-secondary btn-sm" name="action" value="reset_bareme" formnovalidate onclick="return confirm('Revenir aux valeurs proposées par défaut ?')">Valeurs par défaut</button>
                </div>
            </div>
        </form>
    </div>
</div>
<?php endforeach; ?>
<script>
// Vérification par IA : un barème, ou tous à la suite
function biaRun(cle) {
    const fd = new FormData(); fd.append('action', 'bareme_ia'); fd.append('cle', cle);
    return fetch('index.php?tab=baremes', { method: 'POST', body: fd, credentials: 'same-origin' }).then(r => r.json()).catch(() => ({ ok: false, html: '<div class="alert alert-danger py-2 mb-0">Échec de la vérification (réponse invalide ou délai dépassé).</div>' }));
}
document.querySelectorAll('.bia-one').forEach(b => b.addEventListener('click', async () => {
    b.disabled = true; const w = b.parentNode.querySelector('.bia-wait'); w.style.display = '';
    const r = await biaRun(b.dataset.cle); document.getElementById('bia-' + b.dataset.cle).innerHTML = r.html; w.style.display = 'none'; b.disabled = false;
}));
const biaAll = document.getElementById('bia-all');
if (biaAll) biaAll.addEventListener('click', async () => {
    const btns = [...document.querySelectorAll('.bia-one')], prog = document.getElementById('bia-progress'), sum = document.getElementById('bia-summary'), rows = [];
    biaAll.disabled = true; prog.style.display = ''; sum.innerHTML = '';
    for (let i = 0; i < btns.length; i++) {
        const b = btns[i], card = b.closest('.data-table-container'), titre = card.querySelector('h3').textContent.trim();
        prog.innerHTML = '<i class="fas fa-spinner fa-spin"></i> ' + (i + 1) + ' / ' + btns.length + ' : ' + titre;
        const r = await biaRun(b.dataset.cle); document.getElementById('bia-' + b.dataset.cle).innerHTML = r.html;
        const v = r.interne ? ['secondary', 'Interne : sans source publique'] : !r.ok ? ['danger', 'Échec'] : r.verdict === 'ecarts' ? ['danger', r.ecarts + ' écart(s)'] : r.verdict === 'concordant' ? ['success', 'Aucun écart'] : ['warning text-dark', 'Insuffisant'];
        rows.push('<tr><td><a href="#bareme-' + b.dataset.cle + '">' + titre.replace(/[<>&]/g, '') + '</a></td><td><span class="badge bg-' + v[0] + '">' + v[1] + '</span></td></tr>');
        sum.innerHTML = '<table class="table table-sm" style="max-width:520px"><thead><tr><th>Barème</th><th>Avis de l\'IA</th></tr></thead><tbody>' + rows.join('') + '</tbody></table>';
    }
    prog.style.display = 'none'; biaAll.disabled = false;
});
// Ajout d'une ligne dans un tableau dynamique (tranches d'émoluments, départements)
function bfAddRow(tableId, key, cols) {
    const tb = document.querySelector('#' + tableId + ' tbody');
    const i = Date.now() % 1000000;
    const tr = document.createElement('tr');
    tr.innerHTML = cols.map(([f, w, ph]) => `<td><input name="d[${key}][${i}][${f}]" placeholder="${ph}" class="form-control form-control-sm" style="width:${w}px"></td>`).join('')
        + '<td><button type="button" class="btn btn-sm btn-outline-danger" onclick="this.closest(\'tr\').remove()"><i class="fas fa-xmark"></i></button></td>';
    tb.appendChild(tr);
}
</script>
