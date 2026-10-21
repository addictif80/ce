<?php
$pageTitle = 'Calculateur de budget';
require_once __DIR__ . '/../../templates/header.php';
$db = getDB();
$userId = getCurrentUserId();

// Rubriques du budget (partagées avec la page publique /tools)
require_once __DIR__ . '/fields.php';

// Migration : l'état complet du budget (lignes personnalisées, plusieurs montants par ligne) est conservé en JSON ;
// les colonnes historiques restent renseignées pour les rubriques par défaut.
try { $db->exec("ALTER TABLE calculateur_budget ADD COLUMN lignes_json MEDIUMTEXT DEFAULT NULL"); } catch (Exception $e) {}
$legacyCols = [];
try { foreach ($db->query("SHOW COLUMNS FROM calculateur_budget")->fetchAll(PDO::FETCH_COLUMN) as $c) $legacyCols[$c] = true; } catch (Exception $e) {}

// Sauvegarde (upsert)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'save') {
    $state = budgetSanitizeState($_POST['lignes_json'] ?? '');
    $avec_conjoint = isset($_POST['avec_conjoint']) ? 1 : 0;
    $state['conjoint'] = $avec_conjoint;
    $data = ['avec_conjoint' => $avec_conjoint, 'lignes_json' => json_encode($state, JSON_UNESCAPED_UNICODE)];
    foreach (budgetLegacyValues($state) as $id => $v) if (isset($legacyCols[$id])) $data[$id] = $v;

    $stmt = $db->prepare("SELECT id FROM calculateur_budget WHERE user_id = ?");
    $stmt->execute([$userId]);
    if ($stmt->fetch()) {
        $sets = []; foreach ($data as $col => $v) $sets[] = "`$col` = ?";
        $db->prepare("UPDATE calculateur_budget SET " . implode(', ', $sets) . ", updated_at = NOW() WHERE user_id = ?")->execute(array_merge(array_values($data), [$userId]));
    } else {
        $cols = array_merge(['user_id'], array_keys($data), ['created_at', 'updated_at']);
        $vals = array_merge([$userId], array_values($data), [date('Y-m-d H:i:s'), date('Y-m-d H:i:s')]);
        $db->prepare("INSERT INTO calculateur_budget (" . implode(', ', array_map(fn($c) => "`$c`", $cols)) . ") VALUES (" . implode(', ', array_fill(0, count($cols), '?')) . ")")->execute($vals);
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
