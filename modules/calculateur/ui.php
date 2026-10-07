<?php
// Calculateur de budget (reste à vivre), partagé par le module du portail (avec enregistrement) et la page publique /tools (sans enregistrement).
// Variables attendues : $budgetSave (bool), $budget (array|null : budget enregistré), $avec_conjoint (0|1).
require_once __DIR__ . '/fields.php';
$budget = $budget ?? null;
$avec_conjoint = !empty($avec_conjoint) ? 1 : 0;
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
<?php if ($budgetSave): ?><form method="POST" id="budgetForm"><input type="hidden" name="action" value="save"><?php endif; ?>
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
                <div class="b-result">
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
