<?php
/**
 * Page publique (sans connexion) permettant au destinataire de consulter
 * la liste des clients d'une demande de gestion de portefeuille et de
 * marquer chaque client (ou tous les clients) comme traité, via le lien
 * reçu par mail.
 */
require_once __DIR__ . '/../../includes/config.php';

$token = trim($_GET['token'] ?? '');
$errMsg = null;
$demande = null;
$lignes = [];

if (!$token || strlen($token) !== 64 || !ctype_xdigit($token)) {
    http_response_code(400);
    $errMsg = "Lien invalide ou expiré.";
} else {
    $db = getDB();
    $stmt = $db->prepare("SELECT d.id, d.token, c.prenom AS cons_prenom, c.nom AS cons_nom
        FROM demandes_portefeuille d
        LEFT JOIN contacts_equipe c ON d.conseiller_id = c.id
        WHERE d.token = ?");
    $stmt->execute([$token]);
    $demande = $stmt->fetch();

    if (!$demande) {
        http_response_code(404);
        $errMsg = "Cette demande est introuvable ou le lien a expiré.";
    } else {
        // Traitement d'une action (POST)
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $action = $_POST['do'] ?? '';
            if ($action === 'traiter_ligne') {
                $ligneId = (int)($_POST['ligne_id'] ?? 0);
                $db->prepare("UPDATE demandes_portefeuille_lignes SET traitee = 1 WHERE id = ? AND demande_id = ?")
                   ->execute([$ligneId, $demande['id']]);
            } elseif ($action === 'tout_traiter') {
                $db->prepare("UPDATE demandes_portefeuille_lignes SET traitee = 1 WHERE demande_id = ?")
                   ->execute([$demande['id']]);
            }
            header('Location: traiter.php?token=' . urlencode($token));
            exit;
        }

        $stmtL = $db->prepare("SELECT * FROM demandes_portefeuille_lignes WHERE demande_id = ? ORDER BY type_demande, identite_client");
        $stmtL->execute([$demande['id']]);
        $lignes = $stmtL->fetchAll();
    }
}

$conseillerNom = $demande ? trim(($demande['cons_prenom'] ?? '') . ' ' . ($demande['cons_nom'] ?? '')) : '';
$nbTotal = count($lignes);
$nbTraitees = count(array_filter($lignes, fn($l) => (int)$l['traitee'] === 1));
$toutTraite = $nbTotal > 0 && $nbTraitees === $nbTotal;
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Demande de gestion de portefeuille — Portail Conseiller</title>
  <style>
    * { box-sizing: border-box; margin: 0; padding: 0; }
    body { font-family: Arial, Helvetica, sans-serif; background: #f5f5f5; min-height: 100vh;
           padding: 30px 16px; }
    .card { background: #fff; border-radius: 8px; box-shadow: 0 2px 16px rgba(0,0,0,.1);
            max-width: 780px; width: 100%; margin: 0 auto; overflow: hidden; }
    .card-header { background: #CF0A2C; padding: 20px 28px; }
    .card-header img { height: 34px; display: block; }
    .card-subtitle { background: #a50823; padding: 8px 28px; font-size: 11px; color: #f9c9c9;
                      text-transform: uppercase; letter-spacing: .08em; }
    .card-body { padding: 30px 28px 32px; }
    .icon { font-size: 52px; margin-bottom: 16px; text-align: center; }
    .icon.ok  { color: #28A745; }
    .icon.ko  { color: #CF0A2C; }
    h2 { font-size: 20px; color: #1a1a1a; margin-bottom: 10px; text-align: center; }
    p  { font-size: 14px; color: #555; line-height: 1.6; }
    .center { text-align: center; }
    .summary { margin: 0 0 20px 0; font-size: 14px; color: #444; }
    .toolbar { display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px; flex-wrap: wrap; gap: 10px; }
    .btn-green { display: inline-block; padding: 10px 20px; background: #28A745; color: #fff;
                 font-size: 14px; font-weight: bold; text-decoration: none; border: none;
                 border-radius: 5px; cursor: pointer; }
    .btn-green:disabled { background: #a8d8b5; cursor: default; }
    .btn-small { padding: 6px 14px; font-size: 12px; }
    table { width: 100%; border-collapse: collapse; margin-top: 6px; }
    th, td { padding: 10px 12px; border-bottom: 1px solid #eee; font-size: 13px; text-align: left; vertical-align: middle; }
    th { background: #f8f8f8; font-size: 11px; color: #888; text-transform: uppercase; letter-spacing: .05em; }
    .badge { display: inline-block; padding: 2px 10px; border-radius: 12px; color: #fff; font-size: 11px; font-weight: bold; }
    .badge-attribution { background: #28A745; }
    .badge-suppression { background: #CF0A2C; }
    .badge-traite { background: #6c757d; }
    .footer-note { margin-top: 24px; font-size: 12px; color: #aaa; text-align: center; }
    .numero { color: #999; font-size: 11px; }
  </style>
</head>
<body>
  <div class="card">
    <div class="card-header">
      <img src="https://ce-prod.cloudimg.io/_images_/app/uploads/sites/16/2023/06/02105536/cemp-logo-paris-2024.png?func=bound&w=400&h=80&gravity=auto&optipress=2"
           alt="Caisse d'Épargne">
    </div>
    <div class="card-subtitle">Demande de gestion de portefeuille</div>
    <div class="card-body">
      <?php if ($errMsg): ?>
        <div class="icon ko">&#10007;</div>
        <h2>Erreur</h2>
        <p class="center"><?= htmlspecialchars($errMsg) ?></p>
      <?php else: ?>
        <?php if ($toutTraite): ?>
          <div class="icon ok">&#10003;</div>
          <h2>Demande entièrement traitée</h2>
        <?php else: ?>
          <h2>Liste des clients à traiter</h2>
        <?php endif; ?>

        <p class="summary center">
          Destinataire : <strong><?= htmlspecialchars($conseillerNom) ?></strong><br>
          <?= $nbTraitees ?> / <?= $nbTotal ?> client(s) traité(s)
        </p>

        <div class="toolbar">
          <span></span>
          <form method="POST" onsubmit="return confirm('Marquer tous les clients de cette liste comme traités ?')">
            <input type="hidden" name="do" value="tout_traiter">
            <button type="submit" class="btn-green" <?= $toutTraite ? 'disabled' : '' ?>>
              <i>&#10003;</i> Tout traiter
            </button>
          </form>
        </div>

        <table>
          <thead>
            <tr>
              <th>Client</th>
              <th>Type</th>
              <th>Motif</th>
              <th>Statut</th>
              <th></th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($lignes as $l): ?>
              <tr>
                <td>
                  <?= htmlspecialchars($l['identite_client']) ?><br>
                  <span class="numero">N° <?= htmlspecialchars($l['numero_personne']) ?></span>
                </td>
                <td>
                  <span class="badge <?= $l['type_demande'] === 'suppression' ? 'badge-suppression' : 'badge-attribution' ?>">
                    <?= $l['type_demande'] === 'suppression' ? 'Suppression' : 'Attribution' ?>
                  </span>
                </td>
                <td><?= nl2br(htmlspecialchars($l['motif'] ?? '')) ?></td>
                <td>
                  <?php if ($l['traitee']): ?>
                    <span class="badge badge-traite">Traité</span>
                  <?php else: ?>
                    <span class="badge" style="background:#ffc107;color:#212529;">En attente</span>
                  <?php endif; ?>
                </td>
                <td>
                  <?php if (!$l['traitee']): ?>
                    <form method="POST">
                      <input type="hidden" name="do" value="traiter_ligne">
                      <input type="hidden" name="ligne_id" value="<?= (int)$l['id'] ?>">
                      <button type="submit" class="btn-green btn-small">Traiter</button>
                    </form>
                  <?php endif; ?>
                </td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      <?php endif; ?>
      <p class="footer-note">Portail Conseiller — Caisse d'Épargne</p>
    </div>
  </div>
</body>
</html>
