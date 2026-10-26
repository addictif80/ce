<?php
/**
 * Documents bureautiques (tableurs et présentations) créés dans le portail et rattachables à n'importe quel enregistrement d'un module.
 * Un document appartient à son créateur ; un rattachement associe (module, numéro d'enregistrement) à un document.
 */
const DOCS_MAX_BYTES = 6 * 1048576;
const DOCS_MAX_PER_USER = 300;
const DOCS_MAX_VERSIONS = 15;

function docsEnsureSchema() {
    static $done = false; if ($done) return; $done = true;
    $db = getDB();
    $db->exec("CREATE TABLE IF NOT EXISTS ce_docs (
        id INT AUTO_INCREMENT PRIMARY KEY, user_id INT NOT NULL, type VARCHAR(10) NOT NULL, nom VARCHAR(150) NOT NULL,
        contenu LONGTEXT NOT NULL, taille INT NOT NULL DEFAULT 0,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP, updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        KEY idx_user (user_id)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    $db->exec("CREATE TABLE IF NOT EXISTS ce_doc_links (
        id INT AUTO_INCREMENT PRIMARY KEY, doc_id INT NOT NULL, module VARCHAR(40) NOT NULL, record_id INT NOT NULL, record_label VARCHAR(200) DEFAULT NULL,
        linked_by INT DEFAULT NULL, linked_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        UNIQUE KEY uq_link (doc_id, module, record_id), KEY idx_rec (module, record_id)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    $db->exec("CREATE TABLE IF NOT EXISTS ce_doc_versions (
        id INT AUTO_INCREMENT PRIMARY KEY, doc_id INT NOT NULL, contenu LONGTEXT NOT NULL, taille INT NOT NULL DEFAULT 0,
        saved_at DATETIME DEFAULT CURRENT_TIMESTAMP, saved_by INT DEFAULT NULL, KEY idx_doc (doc_id)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
}

/** Modules dont les enregistrements peuvent recevoir un document (table, expression du libellé, colonnes de recherche, page du module). */
function docsLinkables() {
    return [
        'credit_immo' => ['label' => 'Crédit immobilier (dossier)', 'table' => 'credit_immobilier', 'text' => "CONCAT('Dossier ', COALESCE(NULLIF(numero_personne,''),'sans numéro'), ' · ', DATE_FORMAT(date_ajout,'%d/%m/%Y'))", 'search' => ['numero_personne', 'adresse_bien'], 'url' => 'modules/credit_immo/index.php'],
        'demandes_clients' => ['label' => 'Demandes clients', 'table' => 'demandes_clients', 'text' => "CONCAT('Demande ', numero_personne, IF(service IS NULL OR service='', '', CONCAT(' · ', service)))", 'search' => ['numero_personne', 'service', 'details_demande'], 'url' => 'modules/demandes_clients/index.php'],
        'mobilites' => ['label' => 'Mobilités bancaires', 'table' => 'mobilites', 'text' => "CONCAT('Mobilité ', COALESCE(NULLIF(nom_client,''), NULLIF(numero_personne,''), '?'))", 'search' => ['nom_client', 'numero_personne'], 'url' => 'modules/mobilites/index.php'],
        'courriers' => ['label' => 'Courriers', 'table' => 'courriers', 'text' => "CONCAT('Courrier : ', COALESCE(NULLIF(objet,''),'(sans objet)'), IF(nom_prenom_dest IS NULL OR nom_prenom_dest='', '', CONCAT(' · ', nom_prenom_dest)))", 'search' => ['objet', 'nom_prenom_dest'], 'url' => 'modules/courriers/index.php'],
        'courriers_internes' => ['label' => 'Courriers internes', 'table' => 'courriers_internes', 'text' => "CONCAT('Courrier interne : ', objet, ' · ', nom_prenom_dest)", 'search' => ['objet', 'nom_prenom_dest'], 'url' => 'modules/courriers_internes/index.php'],
        'blocnotes' => ['label' => 'Bloc-notes', 'table' => 'blocnotes', 'text' => 'nom_note', 'search' => ['nom_note'], 'url' => 'modules/blocnotes/index.php'],
        'procedures' => ['label' => 'Procédures', 'table' => 'procedures', 'text' => 'nom', 'search' => ['nom'], 'url' => 'modules/procedures/index.php'],
        'formations' => ['label' => 'Formations', 'table' => 'formations', 'text' => 'titre', 'search' => ['titre', 'lieu'], 'url' => 'modules/formations/index.php'],
        'agenda' => ['label' => 'Agenda (événements)', 'table' => 'evenements_perso', 'text' => "CONCAT(titre, ' · ', DATE_FORMAT(date_debut,'%d/%m/%Y'))", 'search' => ['titre', 'description'], 'url' => 'modules/agenda/index.php'],
    ];
}

function docsOwn($id) { // document de l'utilisateur connecté (ou de n'importe qui pour un administrateur) ; null sinon
    docsEnsureSchema();
    $st = getDB()->prepare("SELECT * FROM ce_docs WHERE id = ?"); $st->execute([(int)$id]); $d = $st->fetch();
    if (!$d) return null;
    return ((int)$d['user_id'] === (int)getCurrentUserId() || isAdmin()) ? $d : null;
}

/** Contrôle de structure d'un document avant enregistrement ; renvoie un message d'erreur ou null. */
function docsCheck($type, $data) {
    if (!is_array($data) || ($data['v'] ?? 0) !== 1) return 'Format de document invalide.';
    if ($type === 'sheet') {
        if (empty($data['sheets']) || !is_array($data['sheets']) || count($data['sheets']) > 20) return 'Un tableur contient de 1 à 20 feuilles.';
        foreach ($data['sheets'] as $s) if (!is_array($s) || !isset($s['name']) || !is_string($s['name']) || !isset($s['cells']) ) return 'Feuille invalide.';
    } elseif ($type === 'slides') {
        if (empty($data['slides']) || !is_array($data['slides']) || count($data['slides']) > 200) return 'Une présentation contient de 1 à 200 diapositives.';
    } else return 'Type de document inconnu.';
    return null;
}
function docsCleanName($n) { $n = trim(preg_replace('/[\x00-\x1f]+/u', ' ', (string)$n)); $n = mb_substr($n, 0, 150); return $n === '' ? 'Sans titre' : $n; }

function docsLinksOf($docId) {
    $st = getDB()->prepare("SELECT module, record_id, record_label FROM ce_doc_links WHERE doc_id = ? ORDER BY linked_at"); $st->execute([(int)$docId]);
    $reg = docsLinkables(); $out = [];
    foreach ($st->fetchAll() as $l) $out[] = ['module' => $l['module'], 'record_id' => (int)$l['record_id'], 'label' => $l['record_label'] ?: '#' . $l['record_id'], 'module_label' => $reg[$l['module']]['label'] ?? $l['module'], 'url' => isset($reg[$l['module']]) ? $reg[$l['module']]['url'] : null];
    return $out;
}

/** Documents rattachés à un enregistrement (pour la fiche d'un module) : [{id, type, nom, updated_at}] */
function docsForRecord($module, $recordId) {
    docsEnsureSchema();
    $st = getDB()->prepare("SELECT d.id, d.type, d.nom, d.updated_at FROM ce_doc_links l JOIN ce_docs d ON d.id = l.doc_id WHERE l.module = ? AND l.record_id = ? AND (d.user_id = ? OR ?) ORDER BY d.updated_at DESC");
    $st->execute([$module, (int)$recordId, getCurrentUserId(), isAdmin() ? 1 : 0]);
    return $st->fetchAll();
}

/** Libellé d'un enregistrement de module du registre, s'il appartient à l'utilisateur ; null sinon. */
function docsRecordLabel($module, $recordId) {
    $reg = docsLinkables();
    if (!isset($reg[$module])) return (preg_match('/^[a-z_]{2,40}$/', (string)$module) && (int)$recordId > 0) ? 'Enregistrement n° ' . (int)$recordId : null; // module hors registre : rattachement libre par numéro
    $m = $reg[$module];
    try {
        $st = getDB()->prepare("SELECT {$m['text']} AS l FROM `{$m['table']}` WHERE id = ? AND user_id = ?"); $st->execute([(int)$recordId, getCurrentUserId()]);
        $l = $st->fetchColumn(); return $l === false ? null : mb_substr((string)$l, 0, 200);
    } catch (Throwable $e) { return null; }
}

function docsApi() {
    docsEnsureSchema();
    $db = getDB(); $uid = (int)getCurrentUserId(); $a = $_POST['action'] ?? '';
    $ok = fn($x = []) => print(json_encode(['ok' => true] + $x, JSON_UNESCAPED_UNICODE));
    $ko = function ($m, $code = 400) { http_response_code($code); echo json_encode(['ok' => false, 'error' => $m], JSON_UNESCAPED_UNICODE); exit; };
    $payload = function ($type) use ($ko) {
        $raw = (string)($_POST['data'] ?? ''); if (strlen($raw) > DOCS_MAX_BYTES) $ko('Document trop volumineux (' . (DOCS_MAX_BYTES / 1048576) . ' Mo maximum) : réduisez les images.');
        $d = json_decode($raw, true); if ($e = docsCheck($type, $d)) $ko($e);
        return [json_encode($d, JSON_UNESCAPED_UNICODE), $d];
    };
    if ($a === 'create') {
        $type = $_POST['type'] ?? ''; [$json] = $payload($type);
        $n = $db->prepare("SELECT COUNT(*) FROM ce_docs WHERE user_id = ?"); $n->execute([$uid]);
        if ((int)$n->fetchColumn() >= DOCS_MAX_PER_USER) $ko('Limite de ' . DOCS_MAX_PER_USER . ' documents atteinte.');
        $db->prepare("INSERT INTO ce_docs (user_id, type, nom, contenu, taille) VALUES (?, ?, ?, ?, ?)")->execute([$uid, $type, docsCleanName($_POST['nom'] ?? ''), $json, strlen($json)]);
        $id = (int)$db->lastInsertId();
        if (!empty($_POST['module']) && ($lab = docsRecordLabel($_POST['module'], (int)($_POST['record_id'] ?? 0))) !== null)
            $db->prepare("INSERT IGNORE INTO ce_doc_links (doc_id, module, record_id, record_label, linked_by) VALUES (?, ?, ?, ?, ?)")->execute([$id, $_POST['module'], (int)$_POST['record_id'], $lab, $uid]);
        $ok(['id' => $id]); return;
    }
    if ($a === 'list') {
        $st = $db->prepare("SELECT id, type, nom, taille, updated_at FROM ce_docs WHERE user_id = ? ORDER BY updated_at DESC"); $st->execute([$uid]);
        $docs = $st->fetchAll(); foreach ($docs as &$d) $d['links'] = docsLinksOf($d['id']); $ok(['docs' => $docs]); return;
    }
    if ($a === 'links_for') {
        $m = $_POST['module'] ?? ''; if (!preg_match('/^[a-z_]{2,40}$/', $m)) $ko('Module invalide.');
        $ok(['docs' => docsForRecord($m, (int)($_POST['record_id'] ?? 0))]); return;
    }
    if ($a === 'records') {
        $reg = docsLinkables(); $m = $reg[$_POST['module'] ?? ''] ?? null; if (!$m) $ko('Module inconnu.');
        $q = trim((string)($_POST['q'] ?? '')); $params = [$uid]; $w = '';
        if ($q !== '') { $w = ' AND (' . implode(' OR ', array_map(fn($c) => "`$c` LIKE ?", $m['search'])) . ')'; foreach ($m['search'] as $_) $params[] = '%' . $q . '%'; }
        try { $st = $db->prepare("SELECT id, {$m['text']} AS label FROM `{$m['table']}` WHERE user_id = ?$w ORDER BY id DESC LIMIT 25"); $st->execute($params); $ok(['records' => $st->fetchAll()]); }
        catch (Throwable $e) { $ok(['records' => []]); }
        return;
    }
    $id = (int)($_POST['id'] ?? 0); $d = docsOwn($id);
    if (!$d) $ko('Document introuvable.', 404);
    switch ($a) {
        case 'open': $ok(['nom' => $d['nom'], 'type' => $d['type'], 'data' => json_decode($d['contenu'], true), 'updated_at' => $d['updated_at'], 'links' => docsLinksOf($id)]); return;
        case 'save':
            [$json] = $payload($d['type']);
            $last = $db->prepare("SELECT saved_at, contenu FROM ce_doc_versions WHERE doc_id = ? ORDER BY id DESC LIMIT 1"); $last->execute([$id]); $lv = $last->fetch();
            $snap = !empty($_POST['snapshot']) || !$lv || strtotime($lv['saved_at']) < time() - 600;
            if ($snap && $d['contenu'] !== $json && (!$lv || $lv['contenu'] !== $d['contenu'])) { // on garde l'état précédent
                $db->prepare("INSERT INTO ce_doc_versions (doc_id, contenu, taille, saved_by) VALUES (?, ?, ?, ?)")->execute([$id, $d['contenu'], strlen($d['contenu']), $uid]);
                $db->prepare("DELETE FROM ce_doc_versions WHERE doc_id = ? AND id NOT IN (SELECT id FROM (SELECT id FROM ce_doc_versions WHERE doc_id = ? ORDER BY id DESC LIMIT " . DOCS_MAX_VERSIONS . ") x)")->execute([$id, $id]);
            }
            $nom = isset($_POST['nom']) ? docsCleanName($_POST['nom']) : $d['nom'];
            $db->prepare("UPDATE ce_docs SET contenu = ?, taille = ?, nom = ? WHERE id = ?")->execute([$json, strlen($json), $nom, $id]);
            $u = $db->prepare("SELECT updated_at FROM ce_docs WHERE id = ?"); $u->execute([$id]); $ok(['updated_at' => $u->fetchColumn()]); return;
        case 'rename': $db->prepare("UPDATE ce_docs SET nom = ? WHERE id = ?")->execute([docsCleanName($_POST['nom'] ?? ''), $id]); $ok(); return;
        case 'delete':
            $db->prepare("DELETE FROM ce_doc_links WHERE doc_id = ?")->execute([$id]); $db->prepare("DELETE FROM ce_doc_versions WHERE doc_id = ?")->execute([$id]); $db->prepare("DELETE FROM ce_docs WHERE id = ?")->execute([$id]); $ok(); return;
        case 'link':
            $m = $_POST['module'] ?? ''; $rid = (int)($_POST['record_id'] ?? 0);
            $lab = docsRecordLabel($m, $rid); if ($lab === null) $ko('Enregistrement introuvable ou qui ne vous appartient pas.');
            $db->prepare("INSERT IGNORE INTO ce_doc_links (doc_id, module, record_id, record_label, linked_by) VALUES (?, ?, ?, ?, ?)")->execute([$id, $m, $rid, $lab, $uid]); $ok(['links' => docsLinksOf($id)]); return;
        case 'unlink':
            $db->prepare("DELETE FROM ce_doc_links WHERE doc_id = ? AND module = ? AND record_id = ?")->execute([$id, $_POST['module'] ?? '', (int)($_POST['record_id'] ?? 0)]); $ok(['links' => docsLinksOf($id)]); return;
        case 'versions':
            $st = $db->prepare("SELECT id, saved_at, taille FROM ce_doc_versions WHERE doc_id = ? ORDER BY id DESC"); $st->execute([$id]); $ok(['versions' => $st->fetchAll()]); return;
        case 'version_get':
            $st = $db->prepare("SELECT contenu FROM ce_doc_versions WHERE doc_id = ? AND id = ?"); $st->execute([$id, (int)($_POST['version'] ?? 0)]); $c = $st->fetchColumn();
            if ($c === false) $ko('Version introuvable.', 404); $ok(['data' => json_decode($c, true)]); return;
    }
    $ko('Action inconnue.');
}
