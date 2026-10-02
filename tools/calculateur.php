<?php
require_once __DIR__ . '/_layout.php';

$revenus = [
    'salaire' => 'Salaire net mensuel', 'salaire_conjoint' => 'Salaire net conjoint', 'autres_revenus' => 'Autres revenus',
    'autres_revenus_conjoint' => 'Autres revenus conjoint', 'allocations' => 'Allocations (CAF, etc.)', 'pensions' => 'Pensions',
    'pensions_conjoint' => 'Pensions conjoint', 'revenus_fonciers' => 'Revenus fonciers', 'revenus_fonciers_conjoint' => 'Revenus fonciers conjoint',
];
$conjointFields = ['salaire_conjoint', 'autres_revenus_conjoint', 'pensions_conjoint', 'revenus_fonciers_conjoint'];
$chargesFixes = [
    'loyer_charges' => 'Loyer et charges', 'credit_immo' => 'Crédit immobilier', 'credits_conso' => 'Crédits consommation',
    'assurance_habitation' => 'Assurance habitation', 'assurance_auto' => 'Assurance auto', 'assurance_sante' => 'Assurance santé / mutuelle',
    'impots' => 'Impôts sur le revenu', 'taxe_fonciere' => 'Taxe foncière', 'taxe_habitation' => 'Taxe d\'habitation',
];
$chargesCourantes = [
    'electricite_gaz' => 'Électricité / Gaz', 'eau' => 'Eau', 'telephone_internet' => 'Téléphone / Internet', 'transport' => 'Transport',
    'alimentation' => 'Alimentation', 'habillement' => 'Habillement', 'sante' => 'Santé', 'loisirs' => 'Loisirs',
    'epargne_mensuelle' => 'Épargne mensuelle', 'divers' => 'Divers',
];
$sections = [
    ['revenus', 'fa-arrow-circle-down', 'Revenus mensuels', $revenus],
    ['fixes', 'fa-file-invoice-dollar', 'Charges fixes mensuelles', $chargesFixes],
    ['courantes', 'fa-shopping-cart', 'Charges courantes mensuelles', $chargesCourantes],
];

toolsHeader('Calculateur de budget', 'calculateur', '<style>
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
</style>');
?>
<div class="container my-3">
    <?php privacyBanner('Les montants saisis ne quittent pas votre navigateur : ils ne sont ni envoyés, ni sauvegardés, et disparaissent si vous actualisez la page.') ?>

    <div class="b-card d-flex align-items-center gap-3 no-print">
        <input type="checkbox" id="avec_conjoint" class="form-check-input m-0" style="width:20px;height:20px">
        <label for="avec_conjoint" class="fw-semibold mb-0"><i class="fas fa-user-friends me-1"></i> Revenus du conjoint</label>
    </div>

    <div class="row">
        <div class="col-lg-8">
            <?php foreach ($sections as [$sec, $icon, $title, $fields]): ?>
            <div class="b-card">
                <h2><i class="fas <?= $icon ?>"></i> <?= e($title) ?> <span class="tot" id="tot-<?= $sec ?>">0,00 €</span></h2>
                <?php foreach ($fields as $id => $label): $isConj = in_array($id, $conjointFields, true); ?>
                <div class="b-field <?= $isConj ? 'conj' : '' ?>">
                    <label for="f-<?= $id ?>"><?= e($label) ?></label>
                    <input type="number" step="0.01" min="0" id="f-<?= $id ?>" class="form-control" data-section="<?= $sec ?>" placeholder="0.00" autocomplete="off">
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
                    <button type="button" class="btn btn-outline-secondary" onclick="window.print()"><i class="fas fa-print me-1"></i>Imprimer / PDF</button>
                    <button type="button" class="btn btn-outline-danger" id="reset"><i class="fas fa-eraser me-1"></i>Tout effacer</button>
                </div>
            </div>
        </div>
    </div>
</div>

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
    cb.addEventListener('change', () => {
        document.querySelectorAll('.conj').forEach(el => {
            el.classList.toggle('show', cb.checked);
            if (!cb.checked) el.querySelector('input').value = '';
        });
        calc();
    });
    document.querySelectorAll('input[data-section]').forEach(i => i.addEventListener('input', calc));
    document.getElementById('reset').onclick = () => {
        if (!confirm('Effacer toutes les valeurs saisies ?')) return;
        document.querySelectorAll('input[data-section]').forEach(i => i.value = '');
        cb.checked = false; cb.dispatchEvent(new Event('change'));
    };
    cb.checked = false; // évite la restauration automatique du navigateur
    calc();
})();
</script>
<?php toolsFooter();
