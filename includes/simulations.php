<?php
/**
 * Simulations enregistrées des modules de calcul du portail (capacité d'emprunt, frais de notaire, PTZ…).
 * Les pages publiques /tools réutilisent le même formulaire de calcul (ui.php) mais n'appellent jamais ces fonctions : rien n'y est enregistré.
 */
function simEnsureSchema() {
    getDB()->exec("CREATE TABLE IF NOT EXISTS simulations (
        id INT AUTO_INCREMENT PRIMARY KEY,
        type VARCHAR(30) NOT NULL,
        user_id INT NOT NULL,
        nom VARCHAR(150) NOT NULL,
        params TEXT,
        resultat TEXT,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        KEY idx_user_type (user_id, type)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
}

/** Traite les POST « save » / « delete » du module $type, puis redirige. À appeler avant tout affichage. */
function simHandlePost($type) {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') return;
    $db = getDB(); $userId = getCurrentUserId();
    $clean = function ($s) { $s = (string)$s; return (strlen($s) < 6000 && json_decode($s) !== null) ? $s : '{}'; };
    $action = $_POST['action'] ?? '';
    if ($action === 'save') {
        $nom = mb_substr(trim($_POST['nom'] ?? ''), 0, 150);
        $id = (int)($_POST['id'] ?? 0);
        if ($nom !== '') {
            if ($id) {
                $db->prepare("UPDATE simulations SET nom = ?, params = ?, resultat = ? WHERE id = ? AND user_id = ? AND type = ?")
                   ->execute([$nom, $clean($_POST['params'] ?? ''), $clean($_POST['resultat'] ?? ''), $id, $userId, $type]);
            } else {
                $db->prepare("INSERT INTO simulations (type, user_id, nom, params, resultat) VALUES (?, ?, ?, ?, ?)")
                   ->execute([$type, $userId, $nom, $clean($_POST['params'] ?? ''), $clean($_POST['resultat'] ?? '')]);
                $id = (int)$db->lastInsertId();
            }
        }
        header('Location: index.php?saved=1&load=' . $id);
        exit;
    }
    if ($action === 'delete') {
        $db->prepare("DELETE FROM simulations WHERE id = ? AND user_id = ? AND type = ?")->execute([(int)($_POST['id'] ?? 0), $userId, $type]);
        header('Location: index.php');
        exit;
    }
}

function simList($type) {
    $stmt = getDB()->prepare("SELECT * FROM simulations WHERE user_id = ? AND type = ? ORDER BY updated_at DESC LIMIT 100");
    $stmt->execute([getCurrentUserId(), $type]);
    return $stmt->fetchAll();
}

/** Formulaire « Enregistrer » commun ; les champs params/resultat sont remplis par la fonction JS $prepareFn(form). */
function simSaveForm($capLoad, $prepareFn) { ?>
  <form method="post" class="cap-card" onsubmit="return <?= $prepareFn ?>(this)">
    <h2><i class="fas fa-save me-2 text-danger"></i>Enregistrer cette simulation</h2>
    <input type="hidden" name="action" value="save"><input type="hidden" name="id" value="<?= (int)($capLoad['id'] ?? 0) ?>">
    <input type="hidden" name="params"><input type="hidden" name="resultat">
    <div class="input-group"><input type="text" name="nom" class="form-control" required maxlength="150" placeholder="Nom (ex. M. et Mme Dupont)" value="<?= e($capLoad['nom'] ?? '') ?>">
      <button class="btn btn-primary"><i class="fas fa-save me-1"></i><?= $capLoad ? 'Mettre à jour' : 'Enregistrer' ?></button></div>
  </form>
<?php }

/** Tableau des simulations enregistrées ; $cols = [ [titre, fn($resultat)=>texte], … ] */
function simTable(array $saved, array $cols) { ?>
<div class="card mt-3"><div class="card-header fw-semibold">Mes simulations enregistrées</div>
<div class="table-responsive"><table class="table table-sm table-hover mb-0 align-middle">
    <thead><tr><th>Nom</th><?php foreach ($cols as $c): ?><th class="text-end"><?= e($c[0]) ?></th><?php endforeach; ?><th>Mise à jour</th><th></th></tr></thead>
    <tbody>
    <?php foreach ($saved as $s): $r = json_decode($s['resultat'] ?? '{}', true) ?: []; ?>
        <tr><td><a href="?load=<?= (int)$s['id'] ?>"><?= e($s['nom']) ?></a></td>
            <?php foreach ($cols as $c): ?><td class="text-end"><?= e($c[1]($r)) ?></td><?php endforeach; ?>
            <td><?= e(date('d/m/Y', strtotime($s['updated_at']))) ?></td>
            <td class="text-end"><form method="post" onsubmit="return confirm('Supprimer cette simulation ?')" class="d-inline"><input type="hidden" name="action" value="delete"><input type="hidden" name="id" value="<?= (int)$s['id'] ?>"><button class="btn btn-sm btn-outline-danger"><i class="fas fa-trash"></i></button></form></td></tr>
    <?php endforeach; if (!$saved): ?><tr><td colspan="<?= count($cols) + 3 ?>" class="text-muted text-center py-3">Aucune simulation enregistrée.</td></tr><?php endif; ?>
    </tbody></table></div></div>
<?php }

function simLoad(array $saved) {
    foreach ($saved as $s) if ((int)($_GET['load'] ?? 0) === (int)$s['id']) return $s;
    return null;
}
function simEur($v, $d = 0) { return number_format((float)$v, $d, ',', ' ') . ' €'; }
