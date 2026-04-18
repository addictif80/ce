<?php
/**
 * Génération automatique des bilans EAI hebdomadaires
 * Crontab : 30 8 * * 2 php /chemin/vers/ce/cron/eai_hebdo.php
 * (chaque mardi à 8h30)
 */

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/functions.php';

$db = getDB();

// Trouver le mardi de la semaine courante
$now = new DateTime();
$dow = (int)$now->format('N'); // 1=lundi … 7=dimanche
$offset = $dow === 2 ? 0 : (2 - $dow + 7) % 7 - ($dow > 2 ? 7 : 0);
$tuesdayDt = (clone $now)->modify($offset . ' days');
$tuesdayDate = $tuesdayDt->format('Y-m-d');

echo date('Y-m-d H:i:s') . " - Génération EAI pour la semaine du $tuesdayDate\n";

// Vérifier que les tables EAI existent
try {
    $db->query("SELECT 1 FROM eai_attendus LIMIT 1");
    $db->query("SELECT 1 FROM eai_rapports LIMIT 1");
    $db->query("SELECT 1 FROM eai_valeurs LIMIT 1");
} catch (Exception $e) {
    echo "Tables EAI non initialisées — ouvrez le module EAI une première fois.\n";
    exit(1);
}

$allCles = $db->query("SELECT cle FROM eai_attendus ORDER BY ordre")->fetchAll(PDO::FETCH_COLUMN);
if (empty($allCles)) {
    echo "Aucun KPI configuré dans eai_attendus.\n";
    exit(0);
}

$users = $db->query("SELECT id, prenom, nom FROM users ORDER BY id")->fetchAll();
$count = 0;

foreach ($users as $user) {
    $userId = $user['id'];

    // Créer ou récupérer le rapport de la semaine
    $stmt = $db->prepare("SELECT id FROM eai_rapports WHERE user_id = ? AND semaine_date = ?");
    $stmt->execute([$userId, $tuesdayDate]);
    $rapportId = $stmt->fetchColumn();

    if (!$rapportId) {
        $db->prepare("INSERT INTO eai_rapports (user_id, semaine_date) VALUES (?, ?)")
            ->execute([$userId, $tuesdayDate]);
        $rapportId = (int)$db->lastInsertId();
    }

    // Calculer les données auto
    $autoData = getEaiAutoFillData($db, $userId, $tuesdayDate);

    // Seed/mettre à jour les valeurs auto (sans écraser les overrides manuels)
    $stmtGet = $db->prepare("SELECT id, auto_filled FROM eai_valeurs WHERE rapport_id = ? AND cle = ?");
    foreach ($allCles as $cle) {
        if (!isset($autoData[$cle])) continue;
        $val = $autoData[$cle];

        $stmtGet->execute([$rapportId, $cle]);
        $row = $stmtGet->fetch();

        if (!$row) {
            $db->prepare("INSERT INTO eai_valeurs (rapport_id, cle, valeur, auto_filled) VALUES (?, ?, ?, 1)")
                ->execute([$rapportId, $cle, $val]);
        } elseif ($row['auto_filled']) {
            $db->prepare("UPDATE eai_valeurs SET valeur = ? WHERE id = ?")
                ->execute([$val, $row['id']]);
        }
    }

    echo date('H:i:s') . " - [{$user['prenom']} {$user['nom']}] EAI mis à jour (rapport #$rapportId)\n";
    $count++;
}

echo date('Y-m-d H:i:s') . " - Terminé. $count utilisateur(s) traité(s).\n";
