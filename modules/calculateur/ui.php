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
$bState = budgetBuildState($budget, $avec_conjoint);
$bStateDefault = budgetBuildState(null, 0);
$bMeta = []; foreach ($budget_sections as $k => $s) $bMeta[$k] = ['title' => $s['title'], 'sub' => $s['sub'] ?? '', 'cible' => $s['cible'], 'icon' => $s['icon']];
?>
<style>
    .b-card{background:#fff;border-radius:12px;padding:22px;margin-bottom:20px;box-shadow:0 2px 8px rgba(0,0,0,.06);border:1px solid #e8e8e8}
    .b-card h2{font-size:1.1rem;font-weight:700;margin-bottom:16px;padding-bottom:10px;border-bottom:2px solid #e8e8e8;display:flex;align-items:center;gap:10px}
    .b-card h2 i{color:#e4002b}.b-card h2 .tot{margin-left:auto;font-size:1rem;background:#f0f0f0;padding:3px 14px;border-radius:20px}
    .b-result{background:linear-gradient(135deg,#1a1a2e,#16213e);color:#fff;border-radius:12px;padding:26px;margin-bottom:16px}
    .b-row{display:flex;justify-content:space-between;padding:9px 0;border-bottom:1px solid rgba(255,255,255,.1)}
    .b-row.total{font-size:1.3rem;font-weight:700;border-top:2px solid rgba(255,255,255,.3);border-bottom:none;margin-top:6px;padding-top:14px}
    .pos{color:#4ade80}.neg{color:#f87171}
    .r5-row{margin-bottom:10px}.r5-top{display:flex;justify-content:space-between;font-size:14px}.r5-bar{position:relative;height:10px;background:#e9ecef;border-radius:5px;margin:4px 0 2px}.r5-fill{height:100%;border-radius:5px}.r5-tgt{position:absolute;top:-3px;width:2px;height:16px;background:#212529}
    .r5-ok{background:#2e9b57}.r5-warn{background:#f0ad00}.r5-bad{background:#d9534f}.r5-msg{font-size:12.5px;color:#555}
    @media print{body{background:#fff}.b-card,.b-result{box-shadow:none}}
    .b-sub{font-size:.8rem;font-weight:400;color:#6c757d}.b-line{display:flex;align-items:flex-start;gap:10px;margin-bottom:10px;flex-wrap:wrap}.b-lab{flex:1 1 200px;min-width:0;color:#555;font-size:.9rem;padding-top:7px}
    .b-lab input{width:100%;font-size:.9rem}.b-amts{display:flex;flex-direction:column;gap:5px;align-items:flex-end}.b-am{display:flex;gap:4px;align-items:center}.b-am input{width:130px;text-align:right}
    .b-am .b-x{border:0;background:none;color:#adb5bd;padding:0 2px}.b-am .b-x:hover{color:#d9534f}.b-plus{border:1px dashed #adb5bd;background:#fff;color:#6c757d;border-radius:6px;width:28px;height:28px;line-height:1;padding:0}.b-plus:hover{border-color:#e4002b;color:#e4002b}
    .b-sum{font-size:.82rem;color:#6c757d;text-align:right;min-width:90px;padding-top:8px}.b-delline{border:0;background:none;color:#adb5bd;padding-top:6px}.b-delline:hover{color:#d9534f}
    .b-addline{border:1px dashed #adb5bd;background:#fafafa;color:#555;border-radius:8px;padding:6px 12px;font-size:.88rem;width:100%}.b-addline:hover{border-color:#e4002b;color:#e4002b}.b-line.conj{display:none}.b-line.conj.show{display:flex}
    @media(max-width:768px){.b-amts{align-items:stretch;width:100%}.b-am input{flex:1;width:auto}}
</style>
<?php require __DIR__ . '/../_sim/actions.php'; ?>
<?php if ($budgetSave): ?><form method="POST" id="budgetForm"><input type="hidden" name="action" value="save"><input type="hidden" name="lignes_json" id="lignes_json" value=""><?php endif; ?>
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
        <div class="col-lg-8" id="bSections"></div>
        <div class="col-lg-4">
            <div style="position:sticky;top:20px">
                <div class="b-result js-result">
                    <h2 class="h5 mb-3"><i class="fas fa-chart-pie me-2" style="color:#ffd700"></i>Résultat</h2>
                    <div class="b-row"><span>Total revenus</span><span class="pos" id="r-revenus">0,00 €</span></div>
                    <div class="b-row"><span>Dépenses fixes</span><span style="color:#fbbf24" id="r-fixes">0,00 €</span></div>
                    <div class="b-row"><span>Dépenses variables</span><span style="color:#fbbf24" id="r-variables">0,00 €</span></div>
                    <div class="b-row"><span>Épargne</span><span style="color:#93c5fd" id="r-epargne">0,00 €</span></div>
                    <div class="b-row total"><span>Reste à vivre</span><span id="r-reste">0,00 €</span></div>
                </div>
                <div class="b-card js-result" id="rule5030" style="margin-top:12px">
                    <h2 class="h6 mb-1"><i class="fas fa-scale-balanced"></i> Règle des 50 / 30 / 20</h2>
                    <div class="small text-muted mb-2">Repère courant : 50 % des revenus pour les dépenses fixes, 30 % pour les dépenses variables, 20 % pour l'épargne.</div>
                    <div id="rule-body"><div class="small text-muted">Renseignez vos revenus pour comparer votre budget.</div></div>
                    <div class="small text-muted mt-2">Dépenses fixes : loyer, crédits, assurances et mutuelle, électricité, gaz, eau, taxes et impôts. Dépenses variables : non essentielles et du quotidien (téléphone, box, abonnements, alimentation, transport, loisirs…).</div>
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
    #bgPrint{position:absolute;left:-9999px;top:0;width:180mm;font-family:Arial,Helvetica,sans-serif;font-size:9.5pt;color:#000;line-height:1.28}
    .bgp-head{display:flex;align-items:center;justify-content:space-between;gap:10px;border-bottom:2px solid #000;padding-bottom:6px;margin-bottom:10px}
    .bgp-logo{height:44px}.bgp-title{font-size:15pt;font-weight:700;text-align:center;flex:1}.bgp-date{font-size:9pt;text-align:right;min-width:70px}
    .bgp-ident{border:1px solid #000;padding:6px 10px;margin-bottom:10px}
    .bgp-sec{break-inside:avoid;margin-bottom:8px}.bgp-res+.bgp-sec{margin-top:10px}
    .bgp-sec table{width:100%;border-collapse:collapse;font-size:inherit}
    .bgp-sec th{text-align:left;text-transform:uppercase;font-size:9pt;border-bottom:1.5px solid #000;padding:2px 0}
    .bgp-sec td{padding:1.5px 0;border-bottom:1px dotted #999}
    .bgp-sec .r{text-align:right;white-space:nowrap}
    .bgp-sec tr.tot td{font-weight:700;border-top:1px solid #000;border-bottom:none;padding-top:3px}
    .bgp-res{border:2px solid #000;padding:8px 12px;margin-top:6px;break-inside:avoid}
    .bgp-res div{display:flex;justify-content:space-between;padding:1px 0}
    .bgp-res .big{font-size:13pt;font-weight:700;border-top:1px solid #000;margin-top:4px;padding-top:4px}
    .bgp-contact{margin-top:10px;border:1px solid #000;padding:6px 10px;break-inside:avoid;white-space:pre-line}
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
    function ruleHtml() {
        if (!window.budgetRule) return '';
        const {r, parts, RULE} = window.budgetRule(); if (r <= 0) return '';
        return `<div class="bgp-sec"><table><thead><tr><th>Règle des 50 / 30 / 20 (repère indicatif)</th><th class="r">Montant</th><th class="r">Part des revenus</th><th class="r">Cible</th></tr></thead><tbody>`
            + Object.keys(RULE).map(k => `<tr><td>${esc(RULE[k].label)}</td><td class="r">${eur(parts[k])}</td><td class="r">${(parts[k] / r * 100).toFixed(1).replace('.', ',')} %</td><td class="r">${RULE[k].cible} %</td></tr>`).join('') + `</tbody></table></div>`;
    }
    function renderPrint() {
        if (!window.budgetData) return;
        const D = window.budgetData(), tot = D.tot;
        const body = D.keys.map(k => {
            const l = D.lines[k];
            return `<div class="bgp-sec"><table><thead><tr><th>${esc(D.meta[k].title)}</th><th class="r"></th></tr></thead><tbody>
                ${l.map(x => `<tr><td>${esc(x.label)}${x.a.length > 1 ? ' <span style="color:#666;font-size:8.5pt">(' + x.a.filter(v => v).map(eur).join(' + ') + ')</span>' : ''}</td><td class="r">${eur(x.total)}</td></tr>`).join('') || '<tr><td colspan="2"><i>Aucun élément renseigné</i></td></tr>'}
                <tr class="tot"><td>Total</td><td class="r">${eur(tot[k])}</td></tr></tbody></table></div>`;
        }).join('');
        const reste = tot.revenus - tot.fixes - tot.variables - tot.epargne;
        const pct = tot.revenus > 0 ? ((tot.fixes / tot.revenus) * 100).toFixed(1).replace('.', ',') + ' %' : '–';
        const nom = (document.getElementById('bg_nom').value || '').trim(), contact = (document.getElementById('bg_contact').value || '').trim();
        document.getElementById('bgp_date').textContent = new Date().toLocaleDateString('fr-FR');
        document.getElementById('bgPrintBody').innerHTML =
            (nom ? `<div class="bgp-ident"><b>Budget établi pour :</b> ${esc(nom)}</div>` : '') + body +
            `<div class="bgp-res"><div><span>Total des revenus</span><span>${eur(tot.revenus)}</span></div>
                <div><span>Dépenses fixes</span><span>${eur(tot.fixes)}</span></div>
                <div><span>Dépenses variables</span><span>${eur(tot.variables)}</span></div>
                <div><span>Épargne</span><span>${eur(tot.epargne)}</span></div>
                <div><span>Part des dépenses fixes dans les revenus</span><span>${pct}</span></div>
                <div class="big"><span>Reste à vivre mensuel</span><span>${eur(reste)}</span></div></div>` + ruleHtml() +
            (contact ? `<div class="bgp-contact"><b>Votre contact</b>\n${esc(contact)}</div>` : '') +
            `<div class="bgp-foot">Simulation établie à partir des montants déclarés, à titre indicatif et sans valeur contractuelle.</div>`;
    }
    document.body.appendChild(document.getElementById('bgPrint'));
    window.addEventListener('beforeprint', renderPrint);
})();
</script>
<script>
(function() {
    const META = <?= json_encode($bMeta, JSON_UNESCAPED_UNICODE) ?>, KEYS = Object.keys(META);
    const DEFAULT = <?= json_encode($bStateDefault, JSON_UNESCAPED_UNICODE) ?>;
    let ST = <?= json_encode($bState, JSON_UNESCAPED_UNICODE) ?>;
    const $ = id => document.getElementById(id), root = $('bSections'), cb = $('avec_conjoint');
    const fmt = v => v.toLocaleString('fr-FR', {minimumFractionDigits: 2, maximumFractionDigits: 2}) + ' €';
    const esc = s => String(s ?? '').replace(/[&<>"']/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));
    const num = v => { const n = parseFloat(String(v).replace(',', '.')); return isNaN(n) || n < 0 ? 0 : n; };
    const lsum = l => l.a.reduce((t, v) => t + v, 0);
    const visible = l => !l.conj || ST.conjoint;
    const secTotal = k => ST.sections[k].filter(visible).reduce((t, l) => t + lsum(l), 0);
    const hidden = $('lignes_json');
    const sync = () => { if (hidden) hidden.value = JSON.stringify(ST); };

    function lineHtml(k, i, l) {
        const rows = l.a.map((v, j) => `<div class="b-am"><input type="number" step="0.01" min="0" inputmode="decimal" class="form-control form-control-sm" data-amt="${j}" value="${v ? v : ''}" placeholder="0,00" aria-label="Montant ${j + 1} : ${esc(l.label)}"><span class="text-muted small">€</span>${l.a.length > 1 ? `<button type="button" class="b-x" data-delamt="${j}" title="Retirer ce montant" aria-label="Retirer ce montant"><i class="fas fa-xmark"></i></button>` : ''}${j === l.a.length - 1 ? `<button type="button" class="b-plus" data-addamt title="Ajouter un montant à cette ligne" aria-label="Ajouter un montant à cette ligne">+</button>` : ''}</div>`).join('');
        return `<div class="b-line ${l.conj ? 'conj' + (ST.conjoint ? ' show' : '') : ''}" data-k="${k}" data-i="${i}">
            <div class="b-lab">${l.custom ? `<input class="form-control form-control-sm" data-label maxlength="60" placeholder="Nom de la ligne" value="${esc(l.label)}" aria-label="Nom de la ligne">` : esc(l.label)}</div>
            <div class="b-amts">${rows}</div>
            <div class="b-sum" data-sum>${l.a.length > 1 ? '= ' + fmt(lsum(l)) : ''}</div>${l.custom ? `<button type="button" class="b-delline" data-delline title="Supprimer la ligne" aria-label="Supprimer la ligne"><i class="fas fa-trash"></i></button>` : ''}</div>`;
    }
    function render(focus) {
        root.innerHTML = KEYS.map(k => `<div class="b-card" data-sec="${k}"><h2><i class="fas ${META[k].icon}"></i><span>${esc(META[k].title)}${META[k].sub ? ` <span class="b-sub d-block">${esc(META[k].sub)}${META[k].cible ? ' · repère ' + META[k].cible + ' %' : ''}</span>` : ''}</span><span class="tot" id="tot-${k}">0,00 €</span></h2>
            ${ST.sections[k].map((l, i) => lineHtml(k, i, l)).join('')}
            <button type="button" class="b-addline no-print" data-addline="${k}"><i class="fas fa-plus me-1"></i>Ajouter une ligne</button></div>`).join('');
        if (focus) { const e = root.querySelector(focus); if (e) e.focus(); }
        calc();
    }
    function renderRule(r, reste) {
        const box = $('rule-body'); if (!box) return;
        if (r <= 0) { box.innerHTML = '<div class="small text-muted">Renseignez vos revenus pour comparer votre budget.</div>'; return; }
        const p = ruleParts(); const tol = 3;
        box.innerHTML = Object.keys(RULE).map(k => {
            const m = p[k], pct = m / r * 100, cible = RULE[k].cible, ecart = pct - cible, ecartE = r * ecart / 100, epargne = k === 'epargne';
            const ok = epargne ? ecart >= -tol : ecart <= tol;
            const cls = ok ? 'r5-ok' : (Math.abs(ecart) > 10 ? 'r5-bad' : 'r5-warn'), pts = Math.abs(ecart).toFixed(1).replace('.', ',') + ' points';
            const msg = Math.abs(ecart) <= tol ? 'Dans la cible'
                : epargne ? (ok ? 'Au-dessus de la cible : très bien' : 'Il manque ' + pts + ' (' + fmt(Math.abs(ecartE)) + ' par mois)')
                : (ok ? 'Sous la cible de ' + pts + ' : de la marge' : 'Dépasse la cible de ' + pts + ' (' + fmt(Math.abs(ecartE)) + ' par mois)');
            return `<div class="r5-row"><div class="r5-top"><span><b>${RULE[k].label}</b> <span class="text-muted">(cible ${cible} %)</span></span><span><b>${pct.toFixed(1).replace('.', ',')} %</b> · ${fmt(m)}</span></div>
              <div class="r5-bar"><div class="r5-fill ${cls}" style="width:${Math.max(0, Math.min(100, pct))}%"></div><div class="r5-tgt" style="left:calc(${cible}% - 1px)"></div></div><div class="r5-msg">${msg}</div></div>`;
        }).join('') + (reste > 0 ? `<div class="r5-msg"><i class="fas fa-piggy-bank me-1"></i>Reste non affecté : <b>${fmt(reste)}</b> par mois, qui peut compléter l'épargne.</div>` : (reste < 0 ? '<div class="r5-msg text-danger"><i class="fas fa-triangle-exclamation me-1"></i>Les dépenses et l\'épargne dépassent les revenus.</div>' : ''));
    }
    const RULE = {besoins: {label: META.fixes.title, cible: META.fixes.cible}, envies: {label: META.variables.title, cible: META.variables.cible}, epargne: {label: META.epargne.title, cible: META.epargne.cible}};
    const ruleParts = () => ({besoins: secTotal('fixes'), envies: secTotal('variables'), epargne: secTotal('epargne')});
    function calc() {
        const t = {}; KEYS.forEach(k => { t[k] = secTotal(k); const e = $('tot-' + k); if (e) e.textContent = fmt(t[k]); });
        $('r-revenus').textContent = fmt(t.revenus); $('r-fixes').textContent = fmt(t.fixes); $('r-variables').textContent = fmt(t.variables); $('r-epargne').textContent = fmt(t.epargne);
        const reste = t.revenus - t.fixes - t.variables - t.epargne, el = $('r-reste');
        el.textContent = fmt(reste); el.className = reste >= 0 ? 'pos' : 'neg';
        renderRule(t.revenus, reste); sync();
    }
    window.budgetRule = () => ({r: secTotal('revenus'), parts: ruleParts(), RULE});
    window.budgetData = () => { const lines = {}, tot = {}; KEYS.forEach(k => { lines[k] = ST.sections[k].filter(visible).filter(l => lsum(l) !== 0).map(l => ({label: l.label || 'Ligne', a: l.a, total: lsum(l)})); tot[k] = secTotal(k); }); return {keys: KEYS, meta: META, lines, tot}; };
    // lien de partage (tools-common.js) : l'état complet tient dans la partie « # » de l'adresse
    window.toolsShare = { get: () => ({st: ST}), set: x => { if (x && x.st && x.st.sections) { ST = x.st; cb.checked = !!ST.conjoint; render(); } } };

    root.addEventListener('input', e => {
        const line = e.target.closest('.b-line'); if (!line) return;
        const l = ST.sections[line.dataset.k][+line.dataset.i];
        if (e.target.matches('[data-amt]')) { l.a[+e.target.dataset.amt] = num(e.target.value); const s = line.querySelector('[data-sum]'); s.textContent = l.a.length > 1 ? '= ' + fmt(lsum(l)) : ''; calc(); }
        else if (e.target.matches('[data-label]')) { l.label = e.target.value; sync(); }
    });
    root.addEventListener('click', e => {
        const add = e.target.closest('[data-addline]');
        if (add) { const k = add.dataset.addline; ST.sections[k].push({id: '', label: '', custom: true, conj: false, a: [0]}); render('[data-sec="' + k + '"] .b-line:last-of-type [data-label]'); return; }
        const line = e.target.closest('.b-line'); if (!line) return;
        const k = line.dataset.k, i = +line.dataset.i, l = ST.sections[k][i];
        if (e.target.closest('[data-addamt]')) { l.a.push(0); render('[data-sec="' + k + '"] .b-line[data-i="' + i + '"] .b-am:last-of-type input'); }
        else if (e.target.closest('[data-delamt]')) { l.a.splice(+e.target.closest('[data-delamt]').dataset.delamt, 1); if (!l.a.length) l.a = [0]; render(); }
        else if (e.target.closest('[data-delline]')) { if (!l.label.trim() && !lsum(l) || confirm('Supprimer la ligne « ' + (l.label || 'sans nom') + ' » ?')) { ST.sections[k].splice(i, 1); render(); } }
    });
    cb.addEventListener('change', () => { ST.conjoint = cb.checked ? 1 : 0; if (!cb.checked) KEYS.forEach(k => ST.sections[k].forEach(l => { if (l.conj) l.a = [0]; })); render(); });
    $('reset').onclick = () => { if (!confirm('Effacer toutes les valeurs saisies et les lignes ajoutées ?')) return; ST = JSON.parse(JSON.stringify(DEFAULT)); cb.checked = false; render(); };
    <?php if (!$budgetSave): ?>cb.checked = false; ST.conjoint = 0; // évite la restauration automatique du navigateur<?php endif; ?>
    const form = $('budgetForm'); if (form) form.addEventListener('submit', sync);
    render();
})();
</script>
<script>window.toolsOwnPrint=true; // document imprimé propre à cet outil</script>
<?php require __DIR__ . '/../_sim/common_js.php'; ?>
