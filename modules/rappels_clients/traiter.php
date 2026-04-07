<?php
/**
 * Page publique (sans connexion) permettant au conseiller de marquer
 * une demande de rappel comme traitée via le lien reçu par mail.
 */
require_once __DIR__ . '/../../includes/config.php';

$token = trim($_GET['token'] ?? '');

if (!$token || strlen($token) !== 64 || !ctype_xdigit($token)) {
    http_response_code(400);
    $errMsg = "Lien invalide ou expiré.";
    $success = false;
} else {
    $db = getDB();
    $stmt = $db->prepare("SELECT id, identite_client, traitee FROM demandes_rappel_client WHERE token = ?");
    $stmt->execute([$token]);
    $demande = $stmt->fetch();

    if (!$demande) {
        http_response_code(404);
        $errMsg = "Cette demande est introuvable ou le lien a expiré.";
        $success = false;
    } elseif ($demande['traitee']) {
        $success = true;
        $alreadyDone = true;
        $clientIdent = $demande['identite_client'];
    } else {
        $db->prepare("UPDATE demandes_rappel_client SET traitee = 1 WHERE token = ?")->execute([$token]);
        $success = true;
        $alreadyDone = false;
        $clientIdent = $demande['identite_client'];
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Demande de rappel — Portail Conseiller</title>
  <style>
    * { box-sizing: border-box; margin: 0; padding: 0; }
    body { font-family: Arial, Helvetica, sans-serif; background: #f5f5f5; min-height: 100vh;
           display: flex; align-items: center; justify-content: center; padding: 20px; }
    .card { background: #fff; border-radius: 8px; box-shadow: 0 2px 16px rgba(0,0,0,.1);
            max-width: 480px; width: 100%; overflow: hidden; }
    .card-header { background: #CF0A2C; padding: 20px 28px; }
    .card-header img { height: 34px; display: block; }
    .card-body { padding: 36px 28px 32px; text-align: center; }
    .icon { font-size: 52px; margin-bottom: 16px; }
    .icon.ok  { color: #28A745; }
    .icon.ko  { color: #CF0A2C; }
    h2 { font-size: 20px; color: #1a1a1a; margin-bottom: 10px; }
    p  { font-size: 14px; color: #555; line-height: 1.6; }
    .client { display: inline-block; margin-top: 14px; padding: 8px 18px;
              background: #f0f0f0; border-radius: 20px; font-weight: bold;
              font-size: 15px; color: #222; }
    .footer-note { margin-top: 24px; font-size: 12px; color: #aaa; }
  </style>
</head>
<body>
  <div class="card">
    <div class="card-header">
      <img src="https://ce-prod.cloudimg.io/_images_/app/uploads/sites/16/2023/06/02105536/cemp-logo-paris-2024.png?func=bound&w=400&h=80&gravity=auto&optipress=2"
           alt="Caisse d'Épargne">
    </div>
    <div class="card-body">
      <?php if ($success): ?>
        <div class="icon ok">&#10003;</div>
        <?php if ($alreadyDone): ?>
          <h2>Déjà traitée</h2>
          <p>Cette demande a déjà été marquée comme traitée.</p>
        <?php else: ?>
          <h2>Demande traitée !</h2>
          <p>La demande de rappel a bien été marquée comme traitée.</p>
        <?php endif; ?>
        <span class="client"><?= htmlspecialchars($clientIdent) ?></span>
      <?php else: ?>
        <div class="icon ko">&#10007;</div>
        <h2>Erreur</h2>
        <p><?= htmlspecialchars($errMsg) ?></p>
      <?php endif; ?>
      <p class="footer-note">Portail Conseiller — Caisse d'Épargne</p>
    </div>
  </div>
</body>
</html>
