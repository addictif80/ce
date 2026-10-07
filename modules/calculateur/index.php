<?php
$pageTitle = 'Calculateur de budget';
require_once __DIR__ . '/../../templates/header.php';
$db = getDB();
$userId = getCurrentUserId();

// Tous les champs du budget (partagés avec la page publique /tools)
require_once __DIR__ . '/fields.php';

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
$budgetSave = true;
?>

<?php if (isset($_GET['saved'])): ?>
<div class="alert alert-success alert-dismissible fade show" role="alert">
    <i class="fas fa-check-circle"></i> Budget enregistré avec succès.
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
<?php endif; ?>

<div class="mb-3">
    <h4 class="mb-1"><i class="fas fa-calculator"></i> Calculateur de budget</h4>
    <p class="text-muted mb-0">Estimez votre reste à vivre ; le budget saisi est enregistré pour vous.</p>
</div>
<?php require __DIR__ . '/ui.php'; ?>
<?php require_once __DIR__ . '/../../templates/footer.php'; ?>
