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
    require_once __DIR__ . '/../../includes/baremes_forms.php';
    $data = baremeCollect($cle, (array)($_POST['d'] ?? []));
    $err = baremeCheck($cle, json_decode(json_encode($data), true));
    if ($err) { $_SESSION['bareme_err'] = [$cle, $err, $data]; header('Location: index.php?tab=baremes&msg=bareme_invalide'); exit; }
    $date = preg_match('/^\d{4}-\d{2}-\d{2}$/', $_POST['date_reference'] ?? '') ? $_POST['date_reference'] : null;
    baremeSave($cle, json_decode(json_encode($data), true), isset($_POST['valide']), $date, $adminUserId);
    header('Location: index.php?tab=baremes&msg=bareme_saved');
    exit;
}
if (in_array($action, ['save_pieces_modele', 'delete_pieces_modele', 'duplicate_pieces_modele', 'move_pieces_modele'], true)) {
    require_once __DIR__ . '/../../includes/pieces.php';
    piecesEnsureSchema();
    $id = (int)($_POST['id'] ?? 0);
    if ($action === 'delete_pieces_modele') {
        $db->prepare("DELETE FROM pieces_modeles WHERE id = ?")->execute([$id]);
        header('Location: index.php?tab=pieces&msg=pieces_deleted');
        exit;
    }
    if ($action === 'duplicate_pieces_modele') {
        $db->prepare("INSERT INTO pieces_modeles (nom, lignes, actif, ordre) SELECT CONCAT(LEFT(nom, 135), ' (copie)'), lignes, 0, ordre + 1 FROM pieces_modeles WHERE id = ?")->execute([$id]);
        header('Location: index.php?tab=pieces&msg=pieces_saved');
        exit;
    }
    if ($action === 'move_pieces_modele') { // échange l'ordre avec le modèle voisin
        $ids = $db->query("SELECT id FROM pieces_modeles ORDER BY ordre, nom, id")->fetchAll(PDO::FETCH_COLUMN);
        $pos = array_search($id, array_map('intval', $ids), true);
        $to = $pos === false ? false : $pos + (($_POST['dir'] ?? '') === 'up' ? -1 : 1);
        if ($pos !== false && isset($ids[$to])) { [$ids[$pos], $ids[$to]] = [$ids[$to], $ids[$pos]]; }
        $up = $db->prepare("UPDATE pieces_modeles SET ordre = ? WHERE id = ?");
        foreach ($ids as $i => $mid) $up->execute([$i + 1, (int)$mid]);
        header('Location: index.php?tab=pieces#m' . $id);
        exit;
    }
    $nom = mb_substr(trim($_POST['nom'] ?? ''), 0, 150);
    // Pièces saisies ligne à ligne (rubrique, pièce, précision, profil) -> format « Groupe ; Libellé ; Détail ; Profil »
    $clean = fn($v) => trim(str_replace([';', "\r", "\n"], [',', ' ', ' '], (string)$v));
    $profils = piecesProfils();
    $lignes = [];
    foreach ((array)($_POST['items'] ?? []) as $it) {
        $lib = $clean($it['libelle'] ?? '');
        if ($lib === '') continue;
        $prof = isset($profils[$it['profil'] ?? '']) ? $it['profil'] : 'tous';
        $lignes[] = implode(';', [$clean($it['groupe'] ?? '') ?: 'Divers', $lib, $clean($it['detail'] ?? ''), $prof]);
    }
    $lignes = implode("\n", $lignes);
    if ($nom === '' || !piecesParse($lignes)) { header('Location: index.php?tab=pieces&msg=pieces_invalide'); exit; }
    $actif = isset($_POST['actif']) ? 1 : 0;
    if ($id) {
        $db->prepare("UPDATE pieces_modeles SET nom = ?, lignes = ?, actif = ? WHERE id = ?")->execute([$nom, $lignes, $actif, $id]);
    } else {
        $ordre = (int)$db->query("SELECT COALESCE(MAX(ordre), 0) + 1 FROM pieces_modeles")->fetchColumn();
        $db->prepare("INSERT INTO pieces_modeles (nom, lignes, actif, ordre) VALUES (?, ?, ?, ?)")->execute([$nom, $lignes, $actif, $ordre]);
        $id = (int)$db->lastInsertId();
    }
    header('Location: index.php?tab=pieces&msg=pieces_saved#m' . $id);
    exit;
}

if ($action === 'save_news' || $action === 'delete_news') {
    require_once __DIR__ . '/../../includes/nouveautes.php';
    if ($action === 'save_news') newsSave($_POST); else newsDelete($_POST['id'] ?? 0);
    header('Location: index.php?tab=nouveautes&msg=' . ($action === 'save_news' ? 'news_saved' : 'news_deleted'));
    exit;
}

if ($action === 'save_assist') {
    require_once __DIR__ . '/../../includes/assist.php';
    setToolsSetting('assist_on', isset($_POST['enabled']) ? '1' : '0');
    $k = trim($_POST['assist_key'] ?? '');
    if (isset($_POST['clear_key'])) setToolsSetting('assist_key', '');
    elseif ($k !== '') setToolsSetting('assist_key', mb_substr($k, 0, 500));
    $m = trim($_POST['assist_model'] ?? '');
    setToolsSetting('assist_model', preg_match('/^[A-Za-z0-9._\/:-]{1,60}$/', $m) ? $m : '');
    header('Location: index.php?tab=redaction&msg=assist_saved');
    exit;
}

if ($action === 'test_assist') {
    require_once __DIR__ . '/../../includes/assist.php';
    if (assistKey() === '') { $r = ['ko', 'Aucune clé API enregistrée.']; }
    else {
        [$out, $detail] = assistCall("Tu es un correcteur. Réponds uniquement par le texte corrigé.", "Je vous remerci pour votre message, nous resterons a votre dispositon.", 200);
        $r = $out === null ? ['ko', $detail] : ['ok', $out];
    }
    // résultat conservé (et non à usage unique) : il reste lisible après rechargement
    setToolsSetting('assist_last_test', json_encode([$r[0], $r[1], date('d/m/Y H:i:s')], JSON_UNESCAPED_UNICODE));
    header('Location: index.php?tab=redaction#assist-test');
    exit;
}

if ($action === 'save_intro') {
    setToolsSetting('tools_intro_enabled', isset($_POST['enabled']) ? '1' : '0');
    header('Location: index.php?tab=popups&msg=popup_saved#intro-card');
    exit;
}

if ($action === 'save_popup') {
    require_once __DIR__ . '/../../includes/popups.php';
    $slot = $_POST['slot'] ?? '';
    if (isset(popupSlots()[$slot])) savePopupSettings($slot, $_POST);
    header('Location: index.php?tab=popups&msg=popup_saved#' . urlencode($slot) . '-card');
    exit;
}

if ($action === 'save_terms') {
    require_once __DIR__ . '/../../includes/terms.php';
    saveTermsSettings($_POST['terms_title'] ?? '', $_POST['terms_text'] ?? '', isset($_POST['ask_again']));
    header('Location: index.php?tab=conditions&msg=terms_saved');
    exit;
}

if ($action === 'import_zonage' || $action === 'clear_zonage') {
    require_once __DIR__ . '/../../includes/zonage.php';
    if ($action === 'clear_zonage') {
        zonageEnsureSchema();
        $db->exec("DELETE FROM zonage_communes");
        setToolsSetting('zonage_date', ''); setToolsSetting('zonage_source', ''); setToolsSetting('zonage_libelle', '');
        header('Location: index.php?tab=baremes&msg=zonage_vide#zonage-card');
        exit;
    }
    $f = $_FILES['zonage'] ?? null;
    if (!$f || $f['error'] !== UPLOAD_ERR_OK || $f['size'] > 8 * 1048576) {
        $_SESSION['zonage_res'] = ['ok' => false, 'message' => 'Aucun fichier reçu, ou fichier trop volumineux (8 Mo maximum).'];
    } else {
        $_SESSION['zonage_res'] = zonageImport($f['tmp_name'], $f['name'], isset($_POST['remplacer']));
    }
    header('Location: index.php?tab=baremes#zonage-card');
    exit;
}
