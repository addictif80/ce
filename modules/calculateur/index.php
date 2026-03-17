<?php
$pageTitle = 'Calculateur de budget';
require_once __DIR__ . '/../../templates/header.php';
$db = getDB();
$userId = getCurrentUserId();

// Tous les champs du budget
$revenus_fields = [
    'salaire'                    => 'Salaire net mensuel',
    'salaire_conjoint'           => 'Salaire net conjoint',
    'autres_revenus'             => 'Autres revenus',
    'autres_revenus_conjoint'    => 'Autres revenus conjoint',
    'allocations'                => 'Allocations (CAF, etc.)',
    'pensions'                   => 'Pensions',
    'pensions_conjoint'          => 'Pensions conjoint',
    'revenus_fonciers'           => 'Revenus fonciers',
    'revenus_fonciers_conjoint'  => 'Revenus fonciers conjoint',
];

$conjoint_fields = ['salaire_conjoint', 'autres_revenus_conjoint', 'pensions_conjoint', 'revenus_fonciers_conjoint'];

$charges_fixes_fields = [
    'loyer_charges'        => 'Loyer et charges',
    'credit_immo'          => 'Crédit immobilier',
    'credits_conso'        => 'Crédits consommation',
    'assurance_habitation' => 'Assurance habitation',
    'assurance_auto'       => 'Assurance auto',
    'assurance_sante'      => 'Assurance santé / mutuelle',
    'impots'               => 'Impôts sur le revenu',
    'taxe_fonciere'        => 'Taxe foncière',
    'taxe_habitation'      => 'Taxe d\'habitation',
];

$charges_courantes_fields = [
    'electricite_gaz'      => 'Électricité / Gaz',
    'eau'                  => 'Eau',
    'telephone_internet'   => 'Téléphone / Internet',
    'transport'            => 'Transport',
    'alimentation'         => 'Alimentation',
    'habillement'          => 'Habillement',
    'sante'                => 'Santé',
    'loisirs'              => 'Loisirs',
    'epargne_mensuelle'    => 'Épargne mensuelle',
    'divers'               => 'Divers',
];

$all_fields = array_merge(
    array_keys($revenus_fields),
    array_keys($charges_fixes_fields),
    array_keys($charges_courantes_fields)
);

// Sauvegarde (upsert)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'save') {
    $avec_conjoint = isset($_POST['avec_conjoint']) ? 1 : 0;

    // Vérifier si un budget existe déjà
    $stmt = $db->prepare("SELECT id FROM calculateur_budget WHERE user_id = ?");
    $stmt->execute([$userId]);
    $existing = $stmt->fetch();

    $columns = ['user_id', 'avec_conjoint'];
    $values = [$userId, $avec_conjoint];

    foreach ($all_fields as $field) {
        $columns[] = $field;
        $values[] = isset($_POST[$field]) && $_POST[$field] !== '' ? (float)$_POST[$field] : 0;
    }

    if ($existing) {
        $sets = [];
        foreach ($columns as $i => $col) {
            if ($col === 'user_id') continue;
            $sets[] = "$col = ?";
        }
        $sql = "UPDATE calculateur_budget SET " . implode(', ', $sets) . ", updated_at = NOW() WHERE user_id = ?";
        $updateValues = [];
        foreach ($columns as $i => $col) {
            if ($col === 'user_id') continue;
            $updateValues[] = $values[$i];
        }
        $updateValues[] = $userId;
        $stmt = $db->prepare($sql);
        $stmt->execute($updateValues);
    } else {
        $columns[] = 'created_at';
        $columns[] = 'updated_at';
        $values[] = date('Y-m-d H:i:s');
        $values[] = date('Y-m-d H:i:s');
        $placeholders = implode(', ', array_fill(0, count($columns), '?'));
        $sql = "INSERT INTO calculateur_budget (" . implode(', ', $columns) . ") VALUES ($placeholders)";
        $stmt = $db->prepare($sql);
        $stmt->execute($values);
    }

    header('Location: index.php?saved=1');
    exit;
}

// Charger le budget existant
$stmt = $db->prepare("SELECT * FROM calculateur_budget WHERE user_id = ?");
$stmt->execute([$userId]);
$budget = $stmt->fetch();

$avec_conjoint = $budget ? (int)$budget['avec_conjoint'] : 0;

// Valeur d'un champ (depuis le budget chargé ou 0)
function bval($budget, $field) {
    if (!$budget || !isset($budget[$field])) return '';
    $v = (float)$budget[$field];
    return $v > 0 ? number_format($v, 2, '.', '') : '';
}
?>

<?php if (isset($_GET['saved'])): ?>
<div class="alert alert-success alert-dismissible fade show" role="alert">
    <i class="fas fa-check-circle"></i> Budget enregistré avec succès.
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
<?php endif; ?>

<style>
.budget-section {
    background: #fff;
    border-radius: 12px;
    padding: 24px;
    margin-bottom: 24px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.06);
    border: 1px solid #e8e8e8;
}
.budget-section h3 {
    font-size: 1.1rem;
    font-weight: 700;
    margin-bottom: 20px;
    padding-bottom: 10px;
    border-bottom: 2px solid #e8e8e8;
    display: flex;
    align-items: center;
    gap: 10px;
}
.budget-section h3 i {
    color: #ce0e2d;
    font-size: 1.2rem;
}
.budget-section h3 .section-total {
    margin-left: auto;
    font-size: 1rem;
    font-weight: 600;
    color: #333;
    background: #f0f0f0;
    padding: 4px 14px;
    border-radius: 20px;
}
.budget-field {
    display: flex;
    align-items: center;
    margin-bottom: 10px;
    gap: 12px;
}
.budget-field label {
    flex: 1;
    font-size: 0.9rem;
    color: #555;
    margin: 0;
    white-space: nowrap;
}
.budget-field input {
    width: 160px;
    text-align: right;
    border: 1px solid #ddd;
    border-radius: 8px;
    padding: 8px 12px;
    font-size: 0.95rem;
    transition: border-color 0.2s;
}
.budget-field input:focus {
    border-color: #ce0e2d;
    outline: none;
    box-shadow: 0 0 0 3px rgba(206,14,45,0.1);
}
.budget-field .input-suffix {
    color: #999;
    font-size: 0.9rem;
    width: 20px;
}
.budget-result {
    background: linear-gradient(135deg, #1a1a2e 0%, #16213e 100%);
    color: #fff;
    border-radius: 12px;
    padding: 30px;
    margin-bottom: 24px;
}
.budget-result h3 {
    color: #fff;
    border-bottom-color: rgba(255,255,255,0.15);
    margin-bottom: 24px;
    padding-bottom: 12px;
    font-size: 1.2rem;
}
.budget-result h3 i {
    color: #ffd700;
}
.result-row {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 10px 0;
    font-size: 1rem;
}
.result-row:not(:last-child) {
    border-bottom: 1px solid rgba(255,255,255,0.1);
}
.result-row.total {
    font-size: 1.3rem;
    font-weight: 700;
    padding-top: 16px;
    margin-top: 6px;
    border-top: 2px solid rgba(255,255,255,0.3);
    border-bottom: none;
}
.result-value {
    font-weight: 600;
    font-variant-numeric: tabular-nums;
}
.positive {
    color: #4ade80;
}
.negative {
    color: #f87171;
}
.conjoint-field {
    display: none;
}
.conjoint-field.show {
    display: flex;
}
.conjoint-toggle {
    background: #fff;
    border-radius: 12px;
    padding: 18px 24px;
    margin-bottom: 24px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.06);
    border: 1px solid #e8e8e8;
    display: flex;
    align-items: center;
    gap: 12px;
}
.conjoint-toggle label {
    margin: 0;
    font-weight: 600;
    font-size: 0.95rem;
    cursor: pointer;
    user-select: none;
}
.conjoint-toggle input[type="checkbox"] {
    width: 20px;
    height: 20px;
    accent-color: #ce0e2d;
    cursor: pointer;
}
.btn-save-budget {
    display: flex;
    align-items: center;
    gap: 8px;
    padding: 12px 32px;
    font-size: 1rem;
    font-weight: 600;
}
@media (max-width: 768px) {
    .budget-field {
        flex-wrap: wrap;
    }
    .budget-field label {
        flex: 1 1 100%;
        margin-bottom: 4px;
    }
    .budget-field input {
        width: 100%;
    }
}
</style>

<form method="POST" id="budgetForm">
    <input type="hidden" name="action" value="save">

    <!-- Conjoint toggle -->
    <div class="conjoint-toggle">
        <input type="checkbox" name="avec_conjoint" id="avec_conjoint" value="1" <?= $avec_conjoint ? 'checked' : '' ?>>
        <label for="avec_conjoint"><i class="fas fa-user-friends"></i> Revenus du conjoint</label>
    </div>

    <div class="row">
        <div class="col-lg-8">
            <!-- REVENUS -->
            <div class="budget-section">
                <h3>
                    <i class="fas fa-arrow-circle-down"></i> Revenus mensuels
                    <span class="section-total" id="totalRevenusDisplay">0,00 &euro;</span>
                </h3>
                <?php foreach ($revenus_fields as $field => $label): ?>
                    <?php $isConjoint = in_array($field, $conjoint_fields); ?>
                    <div class="budget-field <?= $isConjoint ? 'conjoint-field' : '' ?>" <?= $isConjoint ? 'data-conjoint' : '' ?>>
                        <label for="<?= $field ?>"><?= e($label) ?></label>
                        <input type="number" step="0.01" min="0" name="<?= $field ?>" id="<?= $field ?>"
                               value="<?= bval($budget, $field) ?>"
                               placeholder="0.00" data-section="revenus" oninput="calculateAll()">
                        <span class="input-suffix">&euro;</span>
                    </div>
                <?php endforeach; ?>
            </div>

            <!-- CHARGES FIXES -->
            <div class="budget-section">
                <h3>
                    <i class="fas fa-file-invoice-dollar"></i> Charges fixes mensuelles
                    <span class="section-total" id="totalChargesFixesDisplay">0,00 &euro;</span>
                </h3>
                <?php foreach ($charges_fixes_fields as $field => $label): ?>
                    <div class="budget-field">
                        <label for="<?= $field ?>"><?= e($label) ?></label>
                        <input type="number" step="0.01" min="0" name="<?= $field ?>" id="<?= $field ?>"
                               value="<?= bval($budget, $field) ?>"
                               placeholder="0.00" data-section="charges_fixes" oninput="calculateAll()">
                        <span class="input-suffix">&euro;</span>
                    </div>
                <?php endforeach; ?>
            </div>

            <!-- CHARGES COURANTES -->
            <div class="budget-section">
                <h3>
                    <i class="fas fa-shopping-cart"></i> Charges courantes mensuelles
                    <span class="section-total" id="totalChargesCourantesDisplay">0,00 &euro;</span>
                </h3>
                <?php foreach ($charges_courantes_fields as $field => $label): ?>
                    <div class="budget-field">
                        <label for="<?= $field ?>"><?= e($label) ?></label>
                        <input type="number" step="0.01" min="0" name="<?= $field ?>" id="<?= $field ?>"
                               value="<?= bval($budget, $field) ?>"
                               placeholder="0.00" data-section="charges_courantes" oninput="calculateAll()">
                        <span class="input-suffix">&euro;</span>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- SIDEBAR RESULTS -->
        <div class="col-lg-4">
            <div style="position: sticky; top: 20px;">
                <div class="budget-result">
                    <h3><i class="fas fa-chart-pie"></i> Résultat</h3>
                    <div class="result-row">
                        <span>Total revenus</span>
                        <span class="result-value positive" id="resultRevenus">0,00 &euro;</span>
                    </div>
                    <div class="result-row">
                        <span>Total charges fixes</span>
                        <span class="result-value" id="resultChargesFixes" style="color: #fbbf24;">0,00 &euro;</span>
                    </div>
                    <div class="result-row">
                        <span>Total charges courantes</span>
                        <span class="result-value" id="resultChargesCourantes" style="color: #fbbf24;">0,00 &euro;</span>
                    </div>
                    <div class="result-row total">
                        <span>Reste &agrave; vivre</span>
                        <span class="result-value" id="resultReste">0,00 &euro;</span>
                    </div>
                </div>

                <button type="submit" class="btn btn-ce btn-save-budget w-100">
                    <i class="fas fa-save"></i> Enregistrer le budget
                </button>

                <?php if ($budget && !empty($budget['updated_at'])): ?>
                <div class="text-muted text-center mt-3" style="font-size: 0.85rem;">
                    <i class="fas fa-clock"></i> Dernière sauvegarde : <?= formatDateTime($budget['updated_at']) ?>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</form>

<script>
// Conjoint toggle
const conjointCheckbox = document.getElementById('avec_conjoint');
const conjointFields = document.querySelectorAll('.conjoint-field');

function toggleConjoint() {
    const show = conjointCheckbox.checked;
    conjointFields.forEach(el => {
        if (show) {
            el.classList.add('show');
        } else {
            el.classList.remove('show');
            // Reset conjoint field values when hidden
            const input = el.querySelector('input[type="number"]');
            if (input) input.value = '';
        }
    });
    calculateAll();
}

conjointCheckbox.addEventListener('change', toggleConjoint);
// Init on load
toggleConjoint();

// Formatting
function formatMoney(value) {
    return value.toLocaleString('fr-FR', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) + ' \u20AC';
}

// Calculation
function sumSection(section) {
    let total = 0;
    const inputs = document.querySelectorAll('input[data-section="' + section + '"]');
    inputs.forEach(input => {
        // Skip hidden conjoint fields
        const parent = input.closest('.conjoint-field');
        if (parent && !parent.classList.contains('show')) return;
        const val = parseFloat(input.value);
        if (!isNaN(val)) total += val;
    });
    return total;
}

function calculateAll() {
    const totalRevenus = sumSection('revenus');
    const totalChargesFixes = sumSection('charges_fixes');
    const totalChargesCourantes = sumSection('charges_courantes');
    const reste = totalRevenus - totalChargesFixes - totalChargesCourantes;

    // Section totals
    document.getElementById('totalRevenusDisplay').textContent = formatMoney(totalRevenus);
    document.getElementById('totalChargesFixesDisplay').textContent = formatMoney(totalChargesFixes);
    document.getElementById('totalChargesCourantesDisplay').textContent = formatMoney(totalChargesCourantes);

    // Result panel
    document.getElementById('resultRevenus').textContent = formatMoney(totalRevenus);
    document.getElementById('resultChargesFixes').textContent = formatMoney(totalChargesFixes);
    document.getElementById('resultChargesCourantes').textContent = formatMoney(totalChargesCourantes);

    const resteEl = document.getElementById('resultReste');
    resteEl.textContent = formatMoney(reste);
    resteEl.className = 'result-value ' + (reste >= 0 ? 'positive' : 'negative');
}

// Initial calculation on page load
calculateAll();
</script>

<?php require_once __DIR__ . '/../../templates/footer.php'; ?>
