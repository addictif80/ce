<?php
// Calculateur de budget (reste à vivre), partagé par le module du portail (avec enregistrement) et la page publique /tools (sans enregistrement).
// Variables attendues : $budgetSave (bool), $budget (array|null : budget enregistré), $avec_conjoint (0|1).
require_once __DIR__ . '/fields.php';
$budget = $budget ?? null;
$avec_conjoint = !empty($avec_conjoint) ? 1 : 0;
$bgContact = '';
if (!empty($currentUser)) { // portail : coordonnées de l'utilisateur connecté, reprises sur le document imprimé
    $bgContact = trim(implode("\n", array_filter([trim(($currentUser['prenom'] ?? '') . ' ' . ($currentUser['nom'] ?? '')), $currentUser['email_pro'] ?? '', $currentUser['tel_pro'] ?? ''])));
}
$bval = function ($field) use ($budget) {
    if (!$budget || !isset($budget[$field])) return '';
    $v = (float)$budget[$field];
    return $v > 0 ? number_format($v, 2, '.', '') : '';
};
?>
<style>
    .b-card{background:#fff;border-radius:12px;padding:22px;margin-bottom:20px;box-shadow:0 2px 8px rgba(0,0,0,.06);border:1px solid #e8e8e8}
    .b-card h2{font-size:1.1rem;font-weight:700;margin-bottom:16px;padding-bottom:10px;border-bottom:2px solid #e8e8e8;display:flex;align-items:center;gap:10px}
    .b-card h2 i{color:#e4002b}.b-card h2 .tot{margin-left:auto;font-size:1rem;background:#f0f0f0;padding:3px 14px;border-radius:20px}
    .b-field{display:flex;align-items:center;gap:12px;margin-bottom:10px}.b-field label{flex:1;margin:0;color:#555;font-size:.9rem}
    .b-field input{width:160px;text-align:right}.conj{display:none}.conj.show{display:flex}
    .b-result{background:linear-gradient(135deg,#1a1a2e,#16213e);color:#fff;border-radius:12px;padding:26px;margin-bottom:16px}
    .b-row{display:flex;justify-content:space-between;padding:9px 0;border-bottom:1px solid rgba(255,255,255,.1)}
    .b-row.total{font-size:1.3rem;font-weight:700;border-top:2px solid rgba(255,255,255,.3);border-bottom:none;margin-top:6px;padding-top:14px}
    .pos{color:#4ade80}.neg{color:#f87171}
    @media(max-width:768px){.b-field{flex-wrap:wrap}.b-field label{flex:1 1 100%}.b-field input{width:100%}}
    @media print{body{background:#fff}.b-card,.b-result{box-shadow:none}}
</style>
<?php require __DIR__ . '/../_sim/actions.php'; ?>
<?php if ($budgetSave): ?><form method="POST" id="budgetForm"><input type="hidden" name="action" value="save"><?php endif; ?>
    <div class="b-card no-print">
        <h2><i class="fas fa-file-lines"></i> Document imprimé <span class="small fw-normal text-muted ms-2">(facultatif)</span></h2>
        <div class="row g-2">
            <div class="col-md-6"><label class="form-label small mb-0" for="bg_nom">Budget établi pour</label><input type="text" id="bg_nom" class="form-control" maxlength="100" autocomplete="off" placeholder="Nom du client ou du foyer"></div>
            <div class="col-md-6"><label class="form-label small mb-0" for="bg_contact">Vos coordonnées</label><textarea id="bg_contact" class="form-control" rows="2" maxlength="300" placeholder="Nom, e-mail, téléphone"><?= e($bgContact) ?></textarea></div>
        </div>
    </div>
    <div class="b-card d-flex align-items-center gap-3 no-print">
        <input type="checkbox" id="avec_conjoint" <?= $budgetSave ? 'name="avec_conjoint" value="1"' : '' ?> class="form-check-input m-0" style="width:20px;height:20px" <?= $avec_conjoint ? 'checked' : '' ?>>
        <label for="avec_conjoint" class="fw-semibold mb-0"><i class="fas fa-user-friends me-1"></i> Revenus du conjoint</label>
    </div>

    <div class="row">
        <div class="col-lg-8">
            <?php foreach ($sections as [$sec, $icon, $title, $fields]): ?>
            <div class="b-card">
                <h2><i class="fas <?= $icon ?>"></i> <?= e($title) ?> <span class="tot" id="tot-<?= $sec ?>">0,00 €</span></h2>
                <?php foreach ($fields as $id => $label): $isConj = in_array($id, $conjoint_fields, true); ?>
                <div class="b-field <?= $isConj ? 'conj' : '' ?>">
                    <label for="f-<?= $id ?>"><?= e($label) ?></label>
                    <input type="number" step="0.01" min="0" id="f-<?= $id ?>" <?= $budgetSave ? 'name="' . $id . '"' : '' ?> value="<?= e($bval($id)) ?>" class="form-control" data-section="<?= $sec ?>" placeholder="0.00" autocomplete="off">
                    <span class="text-muted">€</span>
                </div>
                <?php endforeach; ?>
            </div>
            <?php endforeach; ?>
        </div>

        <div class="col-lg-4">
            <div style="position:sticky;top:20px">
                <div class="b-result js-result">
                    <h2 class="h5 mb-3"><i class="fas fa-chart-pie me-2" style="color:#ffd700"></i>Résultat</h2>
                    <div class="b-row"><span>Total revenus</span><span class="pos" id="r-revenus">0,00 €</span></div>
                    <div class="b-row"><span>Total charges fixes</span><span style="color:#fbbf24" id="r-fixes">0,00 €</span></div>
                    <div class="b-row"><span>Total charges courantes</span><span style="color:#fbbf24" id="r-courantes">0,00 €</span></div>
                    <div class="b-row total"><span>Reste à vivre</span><span id="r-reste">0,00 €</span></div>
                </div>
                <div class="d-grid gap-2 no-print">
                    <?php if ($budgetSave): ?>
                    <button type="submit" class="btn btn-ce"><i class="fas fa-save me-1"></i>Enregistrer le budget</button>
                    <?php if ($budget && !empty($budget['updated_at'])): ?><div class="text-muted text-center small"><i class="fas fa-clock"></i> Dernière sauvegarde : <?= formatDateTime($budget['updated_at']) ?></div><?php endif; ?>
                    <?php endif; ?>
                    <button type="button" class="btn btn-outline-secondary" onclick="window.print()"><i class="fas fa-print me-1"></i>Imprimer / PDF</button>
                    <button type="button" class="btn btn-outline-danger" id="reset"><i class="fas fa-eraser me-1"></i>Tout effacer</button>
                </div>
            </div>
        </div>
    </div>
<?php if ($budgetSave): ?></form><?php endif; ?>

<div id="bgPrint" aria-hidden="true">
  <div class="bgp-head"><img src="https://www.img.caisse-epargne.fr/app/uploads/sites/16/2021/05/31152836/ce-logo-midi-pyrennees.png" alt="Caisse d'Épargne" class="bgp-logo"><div class="bgp-title">BUDGET MENSUEL</div><div class="bgp-date" id="bgp_date"></div></div>
  <div id="bgPrintBody"></div>
</div>
<style>
    #bgPrint{position:absolute;left:-9999px;top:0;width:180mm;font-family:Arial,Helvetica,sans-serif;font-size:10pt;color:#000;line-height:1.35}
    .bgp-head{display:flex;align-items:center;justify-content:space-between;gap:10px;border-bottom:2px solid #000;padding-bottom:6px;margin-bottom:10px}
    .bgp-logo{height:44px}.bgp-title{font-size:15pt;font-weight:700;text-align:center;flex:1}.bgp-date{font-size:9pt;text-align:right;min-width:70px}
    .bgp-ident{border:1px solid #000;padding:6px 10px;margin-bottom:10px}
    .bgp-sec{break-inside:avoid;margin-bottom:10px}
    .bgp-sec table{width:100%;border-collapse:collapse;font-size:inherit}
    .bgp-sec th{text-align:left;text-transform:uppercase;font-size:9pt;border-bottom:1.5px solid #000;padding:2px 0}
    .bgp-sec td{padding:2px 0;border-bottom:1px dotted #999}
    .bgp-sec .r{text-align:right;white-space:nowrap}
    .bgp-sec tr.tot td{font-weight:700;border-top:1px solid #000;border-bottom:none;padding-top:3px}
    .bgp-res{border:2px solid #000;padding:8px 12px;margin-top:6px;break-inside:avoid}
    .bgp-res div{display:flex;justify-content:space-between;padding:1px 0}
    .bgp-res .big{font-size:13pt;font-weight:700;border-top:1px solid #000;margin-top:4px;padding-top:4px}
    .bgp-contact{margin-top:14px;border:1px solid #000;padding:6px 10px;break-inside:avoid;white-space:pre-line}
    .bgp-foot{margin-top:10px;font-size:8pt;color:#333;border-top:1px solid #999;padding-top:4px}
    @media print{
        @page{size:A4 portrait;margin:14mm 15mm}
        body > *:not(#bgPrint){display:none!important}
        #bgPrint{position:static!important;left:auto!important;width:auto!important}
        html,body{background:#fff!important}
    }
</style>
<script>
(function() {
    // Document remis au client : généré à l'impression (bouton « Imprimer » ou Ctrl+P), indépendant de l'affichage de la page
    const esc = s => String(s ?? '').replace(/[&<>"']/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));
    const eur = v => v.toLocaleString('fr-FR', {minimumFractionDigits: 2, maximumFractionDigits: 2}) + ' €';
    function lines(sec) {
        return [...document.querySelectorAll(`input[data-section="${sec}"]`)].filter(i => {
            if (i.closest('.conj') && !i.closest('.conj').classList.contains('show')) return false;
            return (parseFloat(i.value) || 0) !== 0;
        }).map(i => ({label: (document.querySelector(`label[for="${i.id}"]`) || {}).textContent || '', v: parseFloat(i.value)}));
    }
    function renderPrint() {
        const S = [['revenus', 'Revenus mensuels'], ['fixes', 'Charges fixes mensuelles'], ['courantes', 'Charges courantes mensuelles']], tot = {};
        const body = S.map(([k, t]) => {
            const l = lines(k); tot[k] = l.reduce((a, x) => a + x.v, 0);
            return `<div class="bgp-sec"><table><thead><tr><th>${t}</th><th class="r"></th></tr></thead><tbody>
                ${l.map(x => `<tr><td>${esc(x.label)}</td><td class="r">${eur(x.v)}</td></tr>`).join('') || '<tr><td colspan="2"><i>Aucun élément renseigné</i></td></tr>'}
                <tr class="tot"><td>Total</td><td class="r">${eur(tot[k])}</td></tr></tbody></table></div>`;
        }).join('');
        const reste = tot.revenus - tot.fixes - tot.courantes;
        const pct = tot.revenus > 0 ? ((tot.fixes / tot.revenus) * 100).toFixed(1).replace('.', ',') + ' %' : '–';
        const nom = (document.getElementById('bg_nom').value || '').trim(), contact = (document.getElementById('bg_contact').value || '').trim();
        document.getElementById('bgp_date').textContent = new Date().toLocaleDateString('fr-FR');
        document.getElementById('bgPrintBody').innerHTML =
            (nom ? `<div class="bgp-ident"><b>Budget établi pour :</b> ${esc(nom)}</div>` : '') + body +
            `<div class="bgp-res"><div><span>Total des revenus</span><span>${eur(tot.revenus)}</span></div>
                <div><span>Total des charges fixes</span><span>${eur(tot.fixes)}</span></div>
                <div><span>Total des charges courantes</span><span>${eur(tot.courantes)}</span></div>
                <div><span>Part des charges fixes dans les revenus</span><span>${pct}</span></div>
                <div class="big"><span>Reste à vivre mensuel</span><span>${eur(reste)}</span></div></div>` +
            (contact ? `<div class="bgp-contact"><b>Votre contact</b>\n${esc(contact)}</div>` : '') +
            `<div class="bgp-foot">Simulation établie à partir des montants déclarés, à titre indicatif et sans valeur contractuelle.</div>`;
    }
    document.body.appendChild(document.getElementById('bgPrint'));
    window.addEventListener('beforeprint', renderPrint);
})();
</script>
<script>
(function() {
    const fmt = v => v.toLocaleString('fr-FR', {minimumFractionDigits: 2, maximumFractionDigits: 2}) + ' €';
    const cb = document.getElementById('avec_conjoint');
    const sum = sec => [...document.querySelectorAll(`input[data-section="${sec}"]`)].reduce((t, i) => {
        if (i.closest('.conj') && !i.closest('.conj').classList.contains('show')) return t;
        const v = parseFloat(i.value); return t + (isNaN(v) ? 0 : v);
    }, 0);
    function calc() {
        const r = sum('revenus'), f = sum('fixes'), c = sum('courantes'), reste = r - f - c;
        document.getElementById('tot-revenus').textContent = document.getElementById('r-revenus').textContent = fmt(r);
        document.getElementById('tot-fixes').textContent = document.getElementById('r-fixes').textContent = fmt(f);
        document.getElementById('tot-courantes').textContent = document.getElementById('r-courantes').textContent = fmt(c);
        const el = document.getElementById('r-reste');
        el.textContent = fmt(reste);
        el.className = reste >= 0 ? 'pos' : 'neg';
    }
    function toggleConj() {
        document.querySelectorAll('.conj').forEach(el => {
            el.classList.toggle('show', cb.checked);
            if (!cb.checked) el.querySelector('input').value = '';
        });
        calc();
    }
    cb.addEventListener('change', toggleConj);
    document.querySelectorAll('input[data-section]').forEach(i => i.addEventListener('input', calc));
    document.getElementById('reset').onclick = () => {
        if (!confirm('Effacer toutes les valeurs saisies ?')) return;
        document.querySelectorAll('input[data-section]').forEach(i => i.value = '');
        cb.checked = false; toggleConj();
    };
    <?php if (!$budgetSave): ?>cb.checked = false; // évite la restauration automatique du navigateur<?php endif; ?>

    toggleConj();
})();
</script>
<script>window.toolsOwnPrint=true; // document imprimé propre à cet outil</script>
<?php require __DIR__ . '/../_sim/common_js.php'; ?>
