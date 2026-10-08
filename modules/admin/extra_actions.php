<?php
// Actions d'administration : barèmes et modèles de pièces justificatives (inclus dans le bloc POST de index.php).
if ($action === 'save_bareme' || $action === 'reset_bareme') {
    require_once __DIR__ . '/../../includes/baremes.php';
    $cle = $_POST['cle'] ?? '';
    $cat = baremeCatalog();
    if (!isset($cat[$cle])) { header('Location: index.php?tab=baremes&msg=bareme_inconnu'); exit; }
    if ($action === 'reset_bareme') {
        baremeEnsureSchema();
        $db->prepare("DELETE FROM baremes WHERE cle = ?")->execute([$cle]);
        header('Location: index.php?tab=baremes&msg=bareme_reset');
        exit;
    }
    $data = json_decode($_POST['data'] ?? '', true);
    $err = baremeCheck($cle, $data);
    if ($err) { $_SESSION['bareme_err'] = [$cle, $err, $_POST['data'] ?? '']; header('Location: index.php?tab=baremes&msg=bareme_invalide'); exit; }
    $date = preg_match('/^\d{4}-\d{2}-\d{2}$/', $_POST['date_reference'] ?? '') ? $_POST['date_reference'] : null;
    baremeSave($cle, $data, isset($_POST['valide']), $date, $adminUserId);
    header('Location: index.php?tab=baremes&msg=bareme_saved');
    exit;
}
if ($action === 'save_pieces_modele' || $action === 'delete_pieces_modele') {
    require_once __DIR__ . '/../../includes/pieces.php';
    piecesEnsureSchema();
    $id = (int)($_POST['id'] ?? 0);
    if ($action === 'delete_pieces_modele') {
        $db->prepare("DELETE FROM pieces_modeles WHERE id = ?")->execute([$id]);
        header('Location: index.php?tab=pieces&msg=pieces_deleted');
        exit;
    }
    $nom = mb_substr(trim($_POST['nom'] ?? ''), 0, 150);
    $lignes = mb_substr((string)($_POST['lignes'] ?? ''), 0, 20000);
    if ($nom === '' || !piecesParse($lignes)) { header('Location: index.php?tab=pieces&msg=pieces_invalide'); exit; }
    $actif = isset($_POST['actif']) ? 1 : 0; $ordre = (int)($_POST['ordre'] ?? 0);
    if ($id) $db->prepare("UPDATE pieces_modeles SET nom = ?, lignes = ?, actif = ?, ordre = ? WHERE id = ?")->execute([$nom, $lignes, $actif, $ordre, $id]);
    else $db->prepare("INSERT INTO pieces_modeles (nom, lignes, actif, ordre) VALUES (?, ?, ?, ?)")->execute([$nom, $lignes, $actif, $ordre]);
    header('Location: index.php?tab=pieces&msg=pieces_saved');
    exit;
}
