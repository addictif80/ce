<?php
/**
 * Fonctions utilitaires
 */

require_once __DIR__ . '/config.php';

function createNotification($userId, $type, $titre, $message = '', $lien = '') {
    try {
        $db = getDB();
        $db->exec("CREATE TABLE IF NOT EXISTS notifications (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL,
            type VARCHAR(50) NOT NULL,
            titre VARCHAR(255) NOT NULL,
            message TEXT,
            lien VARCHAR(500),
            lu TINYINT(1) DEFAULT 0,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
        $stmt = $db->prepare("INSERT INTO notifications (user_id, type, titre, message, lien) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([(int)$userId, $type, $titre, $message, $lien]);
    } catch (Exception $e) {}
}

/**
 * Échapper le HTML
 */
function e($str) {
    return htmlspecialchars($str ?? '', ENT_QUOTES, 'UTF-8');
}

/**
 * Extrait d'un texte
 */
function excerpt($text, $length = 80) {
    $text = strip_tags($text ?? '');
    if (strlen($text) <= $length) return $text;
    return substr($text, 0, $length) . '...';
}

/**
 * Formater une date
 */
function formatDate($date) {
    if (!$date) return '';
    return date('d/m/Y', strtotime($date));
}

function formatDateTime($datetime) {
    if (!$datetime) return '';
    return date('d/m/Y H:i', strtotime($datetime));
}

/**
 * Classe CSS selon la date d'échéance
 */
function getEcheanceClass($date) {
    if (!$date) return '';
    $now = new DateTime();
    $echeance = new DateTime($date);
    $diff = $now->diff($echeance);
    $days = (int)$diff->format('%r%a');

    if ($days < 0) return 'bg-danger text-white';
    if ($days <= 2) return 'bg-warning';
    return '';
}

/**
 * Couleur pour les offres
 */
function getOffreClass($date_debut, $date_fin) {
    $now = date('Y-m-d');
    if ($date_fin && $date_fin < $now) return 'bg-danger text-white';
    if ($date_debut && $date_debut > $now) return 'bg-warning';
    return '';
}

/**
 * Ajouter une note
 */
function addNote($table_name, $record_id, $message, $user_id) {
    $db = getDB();
    $stmt = $db->prepare("INSERT INTO notes (user_id, table_name, record_id, message) VALUES (?, ?, ?, ?)");
    return $stmt->execute([$user_id, $table_name, $record_id, $message]);
}

/**
 * Récupérer les notes
 */
function getNotes($table_name, $record_id) {
    $db = getDB();
    $stmt = $db->prepare("SELECT n.*, u.nom, u.prenom FROM notes n JOIN users u ON n.user_id = u.id WHERE n.table_name = ? AND n.record_id = ? ORDER BY n.created_at DESC");
    $stmt->execute([$table_name, $record_id]);
    return $stmt->fetchAll();
}

/**
 * Générer un lien de partage aléatoire
 */
function generateShareLink() {
    return bin2hex(random_bytes(32));
}

/**
 * Prépare le schéma nécessaire aux contributions publiques sur les procédures
 * (ajout/modification proposées par des visiteurs non connectés).
 */
function ensureProcedureProposalsSchema() {
    $db = getDB();
    try { $db->exec("ALTER TABLE procedures MODIFY COLUMN user_id INT NULL"); } catch (Exception $e) {}
    try { $db->exec("ALTER TABLE procedures ADD COLUMN contributor_prenom VARCHAR(100) DEFAULT NULL"); } catch (Exception $e) {}
    try { $db->exec("ALTER TABLE procedures ADD COLUMN contributor_nom VARCHAR(100) DEFAULT NULL"); } catch (Exception $e) {}
    $db->exec("CREATE TABLE IF NOT EXISTS procedure_proposals (
        id INT AUTO_INCREMENT PRIMARY KEY,
        type ENUM('create','edit') NOT NULL,
        procedure_id INT DEFAULT NULL,
        nom VARCHAR(255) NOT NULL,
        texte LONGTEXT DEFAULT NULL,
        contributor_prenom VARCHAR(100) NOT NULL,
        contributor_nom VARCHAR(100) NOT NULL,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (procedure_id) REFERENCES procedures(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
}

/**
 * Catégories communes utilisées pour classer le suivi de production,
 * les offres en cours et les intérêts clients.
 */
function getCategoriesProduction() {
    return ['Banca', 'Epargne', 'Placement', 'Credit', 'Assurance'];
}

/**
 * Prépare le schéma nécessaire au suivi des intérêts clients
 * (un client intéressé par une offre à venir).
 */
function ensureInteretsClientsSchema() {
    $db = getDB();
    $db->exec("CREATE TABLE IF NOT EXISTS interets_clients (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        client_nom VARCHAR(255) NOT NULL,
        categorie VARCHAR(100) DEFAULT '',
        interet VARCHAR(500) NOT NULL,
        details TEXT DEFAULT NULL,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
}

/**
 * Prépare le schéma de la liste dynamique de mots-clés utilisés pour les
 * intérêts clients (évite que plusieurs mots-clés désignent le même
 * produit, ex : emprunt / obligation / obligataire).
 */
function ensureMotsClesInteretsSchema() {
    $db = getDB();
    $db->exec("CREATE TABLE IF NOT EXISTS mots_cles_interets (
        id INT AUTO_INCREMENT PRIMARY KEY,
        mot VARCHAR(150) NOT NULL,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        UNIQUE KEY uniq_mot (mot)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
}

/**
 * Récupérer la liste dynamique des mots-clés déjà utilisés.
 */
function getMotsClesInterets() {
    $db = getDB();
    ensureMotsClesInteretsSchema();
    return $db->query("SELECT mot FROM mots_cles_interets ORDER BY mot ASC")->fetchAll(PDO::FETCH_COLUMN);
}

/**
 * Enregistre dans la liste dynamique les mots-clés (séparés par des
 * virgules) saisis pour un intérêt client, s'ils n'y figurent pas déjà.
 */
function registerMotsClesInterets($interetTexte) {
    $db = getDB();
    ensureMotsClesInteretsSchema();
    $mots = array_filter(array_map('trim', explode(',', (string)$interetTexte)));
    if (empty($mots)) return;
    $stmt = $db->prepare("INSERT IGNORE INTO mots_cles_interets (mot) VALUES (?)");
    foreach ($mots as $mot) {
        if ($mot !== '') $stmt->execute([$mot]);
    }
}

/**
 * Parmi une liste d'intérêts clients, retourne ceux dont le mot-clé
 * est repris dans le titre ou le contenu d'une offre.
 */
function matchInteretsForOffre(array $interets, $nomOffre, $detailsOffre) {
    $texte = mb_strtolower(trim($nomOffre . ' ' . strip_tags((string)$detailsOffre)));
    $matches = [];
    foreach ($interets as $interet) {
        // Un intérêt peut contenir plusieurs mots-clés séparés par des virgules :
        // une seule correspondance parmi eux suffit à retenir le client.
        $motsCles = array_filter(array_map('trim', explode(',', (string)$interet['interet'])));
        foreach ($motsCles as $mot) {
            $mot = mb_strtolower($mot);
            if ($mot !== '' && mb_stripos($texte, $mot) !== false) {
                $matches[] = $interet;
                break;
            }
        }
    }
    return $matches;
}

/**
 * Prépare le schéma nécessaire aux catégories de procédures
 */
function ensureProcedureCategoriesSchema() {
    $db = getDB();
    $db->exec("CREATE TABLE IF NOT EXISTS categories_procedures (id INT AUTO_INCREMENT PRIMARY KEY, nom VARCHAR(100) NOT NULL, ordre INT DEFAULT 0) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    try { $db->exec("ALTER TABLE procedures ADD COLUMN categorie_id INT DEFAULT NULL"); } catch (Exception $e) {}
}

/**
 * Récupérer les catégories de procédures
 */
function getCategoriesProcedures() {
    $db = getDB();
    ensureProcedureCategoriesSchema();
    return $db->query("SELECT * FROM categories_procedures ORDER BY ordre ASC, nom ASC")->fetchAll();
}

/**
 * Récupérer les liens externes avec leurs catégories
 */
function getLiensExternes() {
    $db = getDB();
    try { $db->exec("ALTER TABLE liens_externes ADD COLUMN categorie_id INT DEFAULT NULL"); } catch (Exception $e) {}
    try { $db->exec("ALTER TABLE liens_externes ADD COLUMN mode_ouverture VARCHAR(10) NOT NULL DEFAULT 'onglet'"); } catch (Exception $e) {}
    return $db->query("SELECT l.*, c.nom AS categorie_nom FROM liens_externes l LEFT JOIN categories_liens c ON l.categorie_id = c.id ORDER BY c.ordre ASC, l.ordre ASC, l.nom ASC")->fetchAll();
}

/**
 * Récupérer les catégories de liens
 */
function getCategoriesLiens() {
    $db = getDB();
    $db->exec("CREATE TABLE IF NOT EXISTS categories_liens (id INT AUTO_INCREMENT PRIMARY KEY, nom VARCHAR(100) NOT NULL, ordre INT DEFAULT 0) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    return $db->query("SELECT * FROM categories_liens ORDER BY ordre ASC, nom ASC")->fetchAll();
}

/**
 * Configuration du menu - Valeurs par défaut
 */

function getEaiAutoFillData($db, $userId, $tuesdayDate) {
    $dt = new DateTime($tuesdayDate);
    $dow = (int)$dt->format('N');
    $mondayDt = clone $dt;
    $mondayDt->modify('-' . ($dow - 1) . ' days');

    $prevMon = (clone $mondayDt)->modify('-7 days')->format('Y-m-d');
    $prevSun = (clone $mondayDt)->modify('-1 day')->format('Y-m-d');
    $curMon  = $mondayDt->format('Y-m-d');
    $curSun  = (clone $mondayDt)->modify('+6 days')->format('Y-m-d');
    $nextMon = (clone $mondayDt)->modify('+7 days')->format('Y-m-d');
    $nextSun = (clone $mondayDt)->modify('+13 days')->format('Y-m-d');

    $data = [];

    $stmt = $db->prepare("SELECT COALESCE(SUM(nombre_appels), 0) FROM seances_phoning WHERE user_id = ? AND date_ajout BETWEEN ? AND ?");
    $stmt->execute([$userId, $prevMon, $prevSun]);
    $data['volume_appels_sortants'] = (float)$stmt->fetchColumn();

    $stmt = $db->prepare("SELECT COUNT(*) as total, SUM(resultat IN ('repondu','rdv')) as decroche FROM appels_phoning WHERE user_id = ? AND DATE(created_at) BETWEEN ? AND ?");
    $stmt->execute([$userId, $prevMon, $prevSun]);
    $r = $stmt->fetch();
    $total = (int)$r['total'];
    $data['taux_decroche'] = $total > 0 ? round((float)$r['decroche'] / $total * 100, 1) : 0;

    $rdvStmt = $db->prepare("SELECT COUNT(*) as total, COALESCE(SUM(is_anv = 1), 0) as anv FROM appels_phoning WHERE user_id = ? AND resultat = 'rdv' AND date_rdv BETWEEN ? AND ?");
    $rdvStmt->execute([$userId, $prevMon, $prevSun]); $r = $rdvStmt->fetch();
    $data['rdv_s'] = (int)$r['total']; $data['rdv_proactifs_s'] = (int)$r['total']; $data['rdv_anv_s'] = (int)$r['anv'];
    $rdvStmt->execute([$userId, $curMon, $curSun]); $r = $rdvStmt->fetch();
    $data['rdv_s1'] = (int)$r['total']; $data['rdv_proactifs_s1'] = (int)$r['total']; $data['rdv_anv_s1'] = (int)$r['anv'];
    $rdvStmt->execute([$userId, $nextMon, $nextSun]); $r = $rdvStmt->fetch();
    $data['rdv_s2'] = (int)$r['total']; $data['rdv_proactifs_s2'] = (int)$r['total']; $data['rdv_anv_s2'] = (int)$r['anv'];

    $ventesFrom = $prevMon;
    $ventesTo   = (new DateTime())->format('Y-m-d');

    $clesCount = ['ventes_brut_anv','cartes_hdg_dd','izicartes','forfaits','livrets','pel_quadreto','assvie_peri','nouveau_societaire','equip_jequip','bp_jbp'];
    $stmtCount = $db->prepare("SELECT COALESCE(COUNT(*), 0) FROM suivi_production WHERE user_id = ? AND eai_cle = ? AND DATE(date_rdv) BETWEEN ? AND ?");
    foreach ($clesCount as $cle) { $stmtCount->execute([$userId, $cle, $ventesFrom, $ventesTo]); $data[$cle] = (int)$stmtCount->fetchColumn(); }

    $clesSum = ['volume_pret_perso','volume_collecte','volume_parts_sociales'];
    $stmtSum = $db->prepare("SELECT COALESCE(SUM(CAST(montant_nombre AS DECIMAL(15,2))), 0) FROM suivi_production WHERE user_id = ? AND eai_cle = ? AND DATE(date_rdv) BETWEEN ? AND ?");
    foreach ($clesSum as $cle) { $stmtSum->execute([$userId, $cle, $ventesFrom, $ventesTo]); $data[$cle] = (float)$stmtSum->fetchColumn(); }

    if ($data['ventes_brut_anv'] === 0) $data['ventes_brut_anv'] = $data['rdv_anv_s'];

    return $data;
}

function getDefaultMenuItems() {
    return [
        // Sections
        ['item_key' => 'activite', 'parent_key' => null, 'label' => 'Mon activité', 'icon' => 'fa-briefcase', 'url' => null, 'uri_patterns' => 'instances,rappels,demandes_clients,offres,interets_clients,rappels_clients,signatures,envoi_documents,kanban,gestion_portefeuille', 'ordre' => 1],
        ['item_key' => 'formation', 'parent_key' => null, 'label' => 'Formation', 'icon' => 'fa-graduation-cap', 'url' => null, 'uri_patterns' => 'formations', 'ordre' => 2],
        ['item_key' => 'commercial', 'parent_key' => null, 'label' => 'Commercial', 'icon' => 'fa-handshake', 'url' => null, 'uri_patterns' => 'production,phoning,eai,mobilites', 'ordre' => 3],
        ['item_key' => 'outils', 'parent_key' => null, 'label' => 'Outils', 'icon' => 'fa-tools', 'url' => null, 'uri_patterns' => 'credit_immo,calculateur,courriers,courriers_internes,blocnotes,procedures,bureau_dom,retraits,dpe,/rge/,/capacite/,/notaire/,/ptz/,/rachat/,/pieces/,/modules/pdf/,/modules/stock/', 'ordre' => 4],
        ['item_key' => 'references', 'parent_key' => null, 'label' => 'Références', 'icon' => 'fa-bookmark', 'url' => null, 'uri_patterns' => '/codes/,/contacts/', 'ordre' => 5],
        // Items - Mon activité
        ['item_key' => 'kanban', 'parent_key' => 'activite', 'label' => 'Vue Kanban', 'icon' => 'fa-columns', 'url' => '/modules/kanban/index.php', 'uri_patterns' => 'kanban', 'ordre' => 0],
        ['item_key' => 'instances', 'parent_key' => 'activite', 'label' => 'Mes instances', 'icon' => 'fa-tasks', 'url' => '/modules/instances/index.php', 'uri_patterns' => 'instances', 'ordre' => 1],
        ['item_key' => 'rappels', 'parent_key' => 'activite', 'label' => 'Demandes de rappel', 'icon' => 'fa-phone-alt', 'url' => '/modules/rappels/index.php', 'uri_patterns' => 'rappels', 'ordre' => 2],
        ['item_key' => 'demandes_clients', 'parent_key' => 'activite', 'label' => 'Suivi demandes clients', 'icon' => 'fa-headset', 'url' => '/modules/demandes_clients/index.php', 'uri_patterns' => 'demandes_clients', 'ordre' => 3],
        ['item_key' => 'offres', 'parent_key' => 'activite', 'label' => 'Offres en cours', 'icon' => 'fa-tags', 'url' => '/modules/offres/index.php', 'uri_patterns' => 'offres', 'ordre' => 4],
        ['item_key' => 'interets_clients', 'parent_key' => 'activite', 'label' => 'Intérêts clients', 'icon' => 'fa-star', 'url' => '/modules/interets_clients/index.php', 'uri_patterns' => 'interets_clients', 'ordre' => 9],
        ['item_key' => 'rappels_clients', 'parent_key' => 'activite', 'label' => 'Rappels clients', 'icon' => 'fa-phone-square-alt', 'url' => '/modules/rappels_clients/index.php', 'uri_patterns' => 'rappels_clients', 'ordre' => 5],
        ['item_key' => 'gestion_portefeuille', 'parent_key' => 'activite', 'label' => 'Gestion portefeuille', 'icon' => 'fa-briefcase', 'url' => '/modules/gestion_portefeuille/index.php', 'uri_patterns' => 'gestion_portefeuille', 'ordre' => 10],
        ['item_key' => 'calendrier_instances', 'parent_key' => 'activite', 'label' => 'Calendrier instances', 'icon' => 'fa-calendar', 'url' => '/modules/instances/calendrier.php', 'uri_patterns' => 'instances/calendrier', 'ordre' => 6],
        ['item_key' => 'signatures', 'parent_key' => 'activite', 'label' => 'Suivi signatures', 'icon' => 'fa-file-signature', 'url' => '/modules/signatures/index.php', 'uri_patterns' => 'signatures', 'ordre' => 7],
        ['item_key' => 'envoi_documents', 'parent_key' => 'activite', 'label' => 'Envoi de documents', 'icon' => 'fa-file-export', 'url' => '/modules/envoi_documents/index.php', 'uri_patterns' => 'envoi_documents', 'ordre' => 8],
        // Items - Outils (suite)
        ['item_key' => 'stock', 'parent_key' => 'outils', 'label' => 'Fournitures', 'icon' => 'fa-boxes', 'url' => '/modules/stock/index.php', 'uri_patterns' => '/modules/stock/', 'ordre' => 9],
        // Items - Formation
        ['item_key' => 'formations', 'parent_key' => 'formation', 'label' => 'Formations', 'icon' => 'fa-graduation-cap', 'url' => '/modules/formations/index.php', 'uri_patterns' => 'formations', 'ordre' => 1],
        ['item_key' => 'calendrier_formations', 'parent_key' => 'formation', 'label' => 'Calendrier formations', 'icon' => 'fa-calendar-alt', 'url' => '/modules/formations/calendrier.php', 'uri_patterns' => 'formations/calendrier', 'ordre' => 2],
        // Items - Commercial
        ['item_key' => 'production', 'parent_key' => 'commercial', 'label' => 'Suivi production', 'icon' => 'fa-chart-line', 'url' => '/modules/production/index.php', 'uri_patterns' => 'production', 'ordre' => 1],
        ['item_key' => 'phoning', 'parent_key' => 'commercial', 'label' => 'Séances phoning', 'icon' => 'fa-phone-volume', 'url' => '/modules/phoning/index.php', 'uri_patterns' => 'phoning', 'ordre' => 2],
        ['item_key' => 'eai', 'parent_key' => 'commercial', 'label' => 'EAI', 'icon' => 'fa-bullseye', 'url' => '/modules/eai/index.php', 'uri_patterns' => 'eai', 'ordre' => 3],
        ['item_key' => 'mobilites', 'parent_key' => 'commercial', 'label' => 'Mobilités', 'icon' => 'fa-exchange-alt', 'url' => '/modules/mobilites/index.php', 'uri_patterns' => 'mobilites', 'ordre' => 4],
        // Items - Outils
        ['item_key' => 'credit_immo', 'parent_key' => 'outils', 'label' => 'Crédit immobilier', 'icon' => 'fa-house-chimney', 'url' => '/modules/credit_immo/index.php', 'uri_patterns' => 'credit_immo', 'ordre' => 1],
        ['item_key' => 'calculateur', 'parent_key' => 'outils', 'label' => 'Calculateur budget', 'icon' => 'fa-calculator', 'url' => '/modules/calculateur/index.php', 'uri_patterns' => 'calculateur', 'ordre' => 2],
        ['item_key' => 'courriers', 'parent_key' => 'outils', 'label' => 'Générateur courriers', 'icon' => 'fa-envelope', 'url' => '/modules/courriers/index.php', 'uri_patterns' => 'courriers', 'ordre' => 3],
        ['item_key' => 'courriers_internes', 'parent_key' => 'outils', 'label' => 'Courriers internes', 'icon' => 'fa-file-alt', 'url' => '/modules/courriers_internes/index.php', 'uri_patterns' => 'courriers_internes', 'ordre' => 4],
        ['item_key' => 'blocnotes', 'parent_key' => 'outils', 'label' => 'Bloc-notes', 'icon' => 'fa-sticky-note', 'url' => '/modules/blocnotes/index.php', 'uri_patterns' => 'blocnotes', 'ordre' => 5],
        ['item_key' => 'procedures', 'parent_key' => 'outils', 'label' => 'Procédures', 'icon' => 'fa-book', 'url' => '/modules/procedures/index.php', 'uri_patterns' => 'procedures', 'ordre' => 6],
        ['item_key' => 'bureau_dom', 'parent_key' => 'outils', 'label' => 'Bureau domiciliaire', 'icon' => 'fa-building', 'url' => '/modules/bureau_dom/index.php', 'uri_patterns' => 'bureau_dom', 'ordre' => 7],
        ['item_key' => 'retraits', 'parent_key' => 'outils', 'label' => 'Calculateur retraits', 'icon' => 'fa-money-bill-wave', 'url' => '/modules/retraits/index.php', 'uri_patterns' => 'retraits', 'ordre' => 8],
        ['item_key' => 'rge', 'parent_key' => 'outils', 'label' => 'Vérification RGE', 'icon' => 'fa-certificate', 'url' => '/modules/rge/index.php', 'uri_patterns' => '/rge/', 'ordre' => 11],
        ['item_key' => 'capacite', 'parent_key' => 'outils', 'label' => 'Capacité d\'emprunt', 'icon' => 'fa-hand-holding-dollar', 'url' => '/modules/capacite/index.php', 'uri_patterns' => '/capacite/', 'ordre' => 12],
        ['item_key' => 'notaire', 'parent_key' => 'outils', 'label' => 'Frais de notaire', 'icon' => 'fa-scale-balanced', 'url' => '/modules/notaire/index.php', 'uri_patterns' => '/notaire/', 'ordre' => 13],
        ['item_key' => 'ptz', 'parent_key' => 'outils', 'label' => 'Simulateur PTZ', 'icon' => 'fa-percent', 'url' => '/modules/ptz/index.php', 'uri_patterns' => '/ptz/', 'ordre' => 14],
        ['item_key' => 'rachat', 'parent_key' => 'outils', 'label' => 'Prêt relais / rachat', 'icon' => 'fa-arrows-rotate', 'url' => '/modules/rachat/index.php', 'uri_patterns' => '/rachat/', 'ordre' => 15],
        ['item_key' => 'pieces', 'parent_key' => 'outils', 'label' => 'Pièces justificatives', 'icon' => 'fa-list-check', 'url' => '/modules/pieces/index.php', 'uri_patterns' => '/pieces/', 'ordre' => 16],
        ['item_key' => 'pdf', 'parent_key' => 'outils', 'label' => 'Boîte à outils PDF', 'icon' => 'fa-file-pdf', 'url' => '/modules/pdf/index.php', 'uri_patterns' => '/modules/pdf/', 'ordre' => 17],
        ['item_key' => 'dpe', 'parent_key' => 'outils', 'label' => 'Recherche DPE', 'icon' => 'fa-leaf', 'url' => '/modules/dpe/index.php', 'uri_patterns' => '/dpe/', 'ordre' => 10],
        // Items - Références
        ['item_key' => 'codes', 'parent_key' => 'references', 'label' => 'Codes utiles', 'icon' => 'fa-key', 'url' => '/modules/codes/index.php', 'uri_patterns' => '/codes/', 'ordre' => 1],
        ['item_key' => 'contacts', 'parent_key' => 'references', 'label' => 'Contacts utiles', 'icon' => 'fa-address-book', 'url' => '/modules/contacts/index.php', 'uri_patterns' => '/contacts/', 'ordre' => 2],
        // Section Communication
        ['item_key' => 'communication', 'parent_key' => null, 'label' => 'Communication', 'icon' => 'fa-comments', 'url' => null, 'uri_patterns' => 'messagerie,agenda', 'ordre' => 6],
        ['item_key' => 'messagerie', 'parent_key' => 'communication', 'label' => 'Messagerie', 'icon' => 'fa-comment-dots', 'url' => '/modules/messagerie/index.php', 'uri_patterns' => 'messagerie', 'ordre' => 1],
        ['item_key' => 'agenda', 'parent_key' => 'communication', 'label' => 'Agenda', 'icon' => 'fa-calendar-week', 'url' => '/modules/agenda/index.php', 'uri_patterns' => 'agenda', 'ordre' => 2],
    ];
}

/**
 * Récupérer la configuration du menu (avec auto-seed)
 */
function getMenuConfig() {
    $db = getDB();
    // Auto-create table
    $db->exec("CREATE TABLE IF NOT EXISTS menu_config (
        id INT AUTO_INCREMENT PRIMARY KEY,
        item_key VARCHAR(50) NOT NULL UNIQUE,
        parent_key VARCHAR(50) DEFAULT NULL,
        label VARCHAR(100) NOT NULL,
        icon VARCHAR(50) NOT NULL,
        url VARCHAR(255) DEFAULT NULL,
        uri_patterns VARCHAR(500) DEFAULT NULL,
        ordre INT DEFAULT 0,
        visible TINYINT(1) DEFAULT 1
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    $count = $db->query("SELECT COUNT(*) FROM menu_config")->fetchColumn();
    if ($count == 0) {
        $stmt = $db->prepare("INSERT INTO menu_config (item_key, parent_key, label, icon, url, uri_patterns, ordre) VALUES (?, ?, ?, ?, ?, ?, ?)");
        foreach (getDefaultMenuItems() as $item) {
            $stmt->execute([$item['item_key'], $item['parent_key'], $item['label'], $item['icon'], $item['url'], $item['uri_patterns'], $item['ordre']]);
        }
    } else {
        // Insert any new default items that don't exist yet (preserves custom ordering)
        $existing = $db->query("SELECT item_key FROM menu_config")->fetchAll(PDO::FETCH_COLUMN);
        $existing = array_flip($existing);
        $stmt = $db->prepare("INSERT INTO menu_config (item_key, parent_key, label, icon, url, uri_patterns, ordre) VALUES (?, ?, ?, ?, ?, ?, ?)");
        foreach (getDefaultMenuItems() as $item) {
            if (!isset($existing[$item['item_key']])) {
                $stmt->execute([$item['item_key'], $item['parent_key'], $item['label'], $item['icon'], $item['url'], $item['uri_patterns'], $item['ordre']]);
            }
        }
    }

    $items = $db->query("SELECT * FROM menu_config WHERE visible = 1 ORDER BY ordre ASC")->fetchAll();

    // Build structured menu: sections with their children
    $sections = [];
    $children = [];
    foreach ($items as $item) {
        if ($item['parent_key'] === null) {
            $sections[$item['item_key']] = $item;
        } else {
            $children[$item['parent_key']][] = $item;
        }
    }

    $menu = [];
    foreach ($sections as $key => $section) {
        $section['items'] = $children[$key] ?? [];
        $menu[] = $section;
    }

    return $menu;
}

/**
 * Recherche globale
 */
function searchGlobal($query, $userId) {
    $db = getDB();
    $results = [];
    $like = '%' . $query . '%';

    // Instances
    $stmt = $db->prepare("SELECT id, numero_personne AS titre, details AS detail, 'instances' AS type FROM instances WHERE user_id = ? AND (numero_personne LIKE ? OR details LIKE ? OR categories LIKE ?)");
    $stmt->execute([$userId, $like, $like, $like]);
    $results = array_merge($results, $stmt->fetchAll());

    // Demandes rappel
    $stmt = $db->prepare("SELECT id, numero_personne AS titre, motif AS detail, 'rappels' AS type FROM demandes_rappel WHERE user_id = ? AND (numero_personne LIKE ? OR motif LIKE ?)");
    $stmt->execute([$userId, $like, $like]);
    $results = array_merge($results, $stmt->fetchAll());

    // Offres
    $stmt = $db->prepare("SELECT id, nom AS titre, details AS detail, 'offres' AS type FROM offres WHERE user_id = ? AND (nom LIKE ? OR details LIKE ?)");
    $stmt->execute([$userId, $like, $like]);
    $results = array_merge($results, $stmt->fetchAll());

    // Codes utiles
    $stmt = $db->prepare("SELECT id, code AS titre, fonction AS detail, 'codes' AS type FROM codes_utiles WHERE user_id = ? AND (code LIKE ? OR fonction LIKE ?)");
    $stmt->execute([$userId, $like, $like]);
    $results = array_merge($results, $stmt->fetchAll());

    // Contacts utiles (propres + approuvés par tous)
    $stmt = $db->prepare("SELECT id, service AS titre, a_contacter_pour AS detail, 'contacts' AS type FROM contacts_utiles WHERE (user_id = ? OR approved = 1) AND (telephone LIKE ? OR mail LIKE ? OR service LIKE ? OR a_contacter_pour LIKE ?)");
    $stmt->execute([$userId, $like, $like, $like, $like]);
    $results = array_merge($results, $stmt->fetchAll());

    // Demandes clients
    $stmt = $db->prepare("SELECT id, numero_personne AS titre, details_demande AS detail, 'demandes_clients' AS type FROM demandes_clients WHERE user_id = ? AND (numero_personne LIKE ? OR details_demande LIKE ? OR service LIKE ?)");
    $stmt->execute([$userId, $like, $like, $like]);
    $results = array_merge($results, $stmt->fetchAll());

    // Bloc-notes
    $stmt = $db->prepare("SELECT id, nom_note AS titre, contenu AS detail, 'blocnotes' AS type FROM blocnotes WHERE user_id = ? AND (nom_note LIKE ? OR contenu LIKE ?)");
    $stmt->execute([$userId, $like, $like]);
    $results = array_merge($results, $stmt->fetchAll());

    // Procédures
    $stmt = $db->prepare("SELECT id, nom AS titre, texte AS detail, 'procedures' AS type FROM procedures WHERE (nom LIKE ? OR texte LIKE ?)");
    $stmt->execute([$like, $like]);
    $results = array_merge($results, $stmt->fetchAll());

    // Courriers
    $stmt = $db->prepare("SELECT id, CONCAT(nom_prenom_dest, ' - ', objet) AS titre, corps AS detail, 'courriers' AS type FROM courriers WHERE user_id = ? AND (nom_prenom_dest LIKE ? OR objet LIKE ? OR corps LIKE ?)");
    $stmt->execute([$userId, $like, $like, $like]);
    $results = array_merge($results, $stmt->fetchAll());

    // Formations
    $stmt = $db->prepare("SELECT id, titre AS titre, CONCAT(lieu, ' - ', COALESCE(adresse_hotel,'')) AS detail, 'formations' AS type FROM formations WHERE user_id = ? AND (titre LIKE ? OR adresse_hotel LIKE ?)");
    $stmt->execute([$userId, $like, $like]);
    $results = array_merge($results, $stmt->fetchAll());

    // Équipe (utilisateurs + contacts équipe manuels)
    $stmt = $db->prepare("SELECT id, CONCAT(prenom, ' ', nom) AS titre, CONCAT(COALESCE(email_pro,''), ' ', COALESCE(tel_pro,''), ' ', COALESCE(ligne_interne,'')) AS detail, 'equipe' AS type FROM users WHERE (nom LIKE ? OR prenom LIKE ? OR email_pro LIKE ? OR tel_pro LIKE ? OR ligne_interne LIKE ?)");
    $stmt->execute([$like, $like, $like, $like, $like]);
    $results = array_merge($results, $stmt->fetchAll());

    try {
        $stmt = $db->prepare("SELECT id, CONCAT(prenom, ' ', nom) AS titre, CONCAT(COALESCE(email,''), ' ', COALESCE(telephone,''), ' ', COALESCE(ligne_interne,'')) AS detail, 'equipe' AS type FROM contacts_equipe WHERE user_id = ? AND (nom LIKE ? OR prenom LIKE ? OR email LIKE ? OR telephone LIKE ? OR ligne_interne LIKE ?)");
        $stmt->execute([$userId, $like, $like, $like, $like, $like]);
        $results = array_merge($results, $stmt->fetchAll());
    } catch (Exception $e) {}

    // Séances phoning (dossiers)
    $stmt = $db->prepare("SELECT id, COALESCE(titre, CONCAT('Séance du ', DATE_FORMAT(date_ajout, '%d/%m/%Y'))) AS titre, CONCAT(nombre_appels, ' appels, ', nombre_rdv, ' RDV') AS detail, 'phoning' AS type FROM seances_phoning WHERE user_id = ? AND (titre LIKE ? OR notes LIKE ?)");
    $stmt->execute([$userId, $like, $like]);
    $results = array_merge($results, $stmt->fetchAll());

    // Appels phoning (recherche par numéro/nom ou commentaire)
    try {
        $stmt = $db->prepare("SELECT a.seance_id AS id, CONCAT(COALESCE(a.numero_personne, ''), ' (', a.resultat, ')') AS titre, COALESCE(a.commentaire, '') AS detail, 'phoning' AS type FROM appels_phoning a WHERE a.user_id = ? AND (a.numero_personne LIKE ? OR a.commentaire LIKE ?)");
        $stmt->execute([$userId, $like, $like]);
        $results = array_merge($results, $stmt->fetchAll());
    } catch (Exception $e) {}

    // Courriers internes
    try {
        $stmt = $db->prepare("SELECT id, CONCAT(nom_prenom_dest, ' - ', objet) AS titre, corps AS detail, 'courriers_internes' AS type FROM courriers_internes WHERE user_id = ? AND (nom_prenom_dest LIKE ? OR objet LIKE ? OR corps LIKE ?)");
        $stmt->execute([$userId, $like, $like, $like]);
        $results = array_merge($results, $stmt->fetchAll());
    } catch (Exception $e) {}

    // Signatures (dossiers)
    try {
        $stmt = $db->prepare("SELECT id, CONCAT(COALESCE(numero_personne,''), ' - ', nom_client) AS titre, email_client AS detail, 'signatures' AS type FROM dossiers_signature WHERE user_id = ? AND (numero_personne LIKE ? OR nom_client LIKE ? OR email_client LIKE ?)");
        $stmt->execute([$userId, $like, $like, $like]);
        $results = array_merge($results, $stmt->fetchAll());
    } catch (Exception $e) {}

    // Mobilités
    try {
        $stmt = $db->prepare("SELECT id, CONCAT(COALESCE(numero_personne,''), ' - ', nom_client) AS titre, CONCAT(COALESCE(banque_depart,''), ' ', COALESCE(notes,'')) AS detail, 'mobilites' AS type FROM mobilites WHERE user_id = ? AND (numero_personne LIKE ? OR nom_client LIKE ? OR banque_depart LIKE ? OR notes LIKE ?)");
        $stmt->execute([$userId, $like, $like, $like, $like]);
        $results = array_merge($results, $stmt->fetchAll());
    } catch (Exception $e) {}

    // Rappels clients
    try {
        $stmt = $db->prepare("SELECT id, identite_client AS titre, motif AS detail, 'rappels_clients' AS type FROM demandes_rappel_client WHERE user_id = ? AND (identite_client LIKE ? OR motif LIKE ?)");
        $stmt->execute([$userId, $like, $like]);
        $results = array_merge($results, $stmt->fetchAll());
    } catch (Exception $e) {}

    // Gestion portefeuille
    try {
        $stmt = $db->prepare("SELECT l.demande_id AS id, l.identite_client AS titre, l.motif AS detail, 'gestion_portefeuille' AS type
            FROM demandes_portefeuille_lignes l
            JOIN demandes_portefeuille d ON d.id = l.demande_id
            WHERE d.user_id = ? AND (l.identite_client LIKE ? OR l.motif LIKE ? OR l.numero_personne LIKE ?)");
        $stmt->execute([$userId, $like, $like, $like]);
        $results = array_merge($results, $stmt->fetchAll());
    } catch (Exception $e) {}

    // Production
    try {
        $stmt = $db->prepare("SELECT id, produit_vendu AS titre, details AS detail, 'production' AS type FROM suivi_production WHERE user_id = ? AND (produit_vendu LIKE ? OR details LIKE ?)");
        $stmt->execute([$userId, $like, $like]);
        $results = array_merge($results, $stmt->fetchAll());
    } catch (Exception $e) {}

    // Crédit immobilier
    try {
        $stmt = $db->prepare("SELECT id, CONCAT(COALESCE(numero_personne,''), ' - ', COALESCE(adresse_bien,'')) AS titre, adresse_bien AS detail, 'credit_immo' AS type FROM credit_immobilier WHERE user_id = ? AND (numero_personne LIKE ? OR adresse_bien LIKE ? OR emprunteurs_json LIKE ?)");
        $stmt->execute([$userId, $like, $like, $like]);
        $results = array_merge($results, $stmt->fetchAll());
    } catch (Exception $e) {}

    return $results;
}

/**
 * Statistiques pour le tableau de bord
 */
function getDashboardStats($userId) {
    $db = getDB();
    $stats = [];

    $stmt = $db->prepare("SELECT COUNT(*) FROM instances WHERE user_id = ? AND statut = 'a_faire'");
    $stmt->execute([$userId]);
    $stats['instances_a_faire'] = $stmt->fetchColumn();

    $stmt = $db->prepare("SELECT COUNT(*) FROM instances WHERE user_id = ? AND statut = 'a_faire' AND date_echeance < CURDATE()");
    $stmt->execute([$userId]);
    $stats['instances_retard'] = $stmt->fetchColumn();

    $stmt = $db->prepare("SELECT COUNT(*) FROM demandes_rappel WHERE user_id = ? AND traitee = 0");
    $stmt->execute([$userId]);
    $stats['rappels_en_cours'] = $stmt->fetchColumn();

    $stmt = $db->prepare("SELECT COUNT(*) FROM offres WHERE user_id = ? AND (date_fin IS NULL OR date_fin >= CURDATE()) AND (date_debut IS NULL OR date_debut <= CURDATE())");
    $stmt->execute([$userId]);
    $stats['offres_en_cours'] = $stmt->fetchColumn();

    $stmt = $db->prepare("SELECT COUNT(*) FROM offres WHERE user_id = ? AND date_fin < CURDATE()");
    $stmt->execute([$userId]);
    $stats['offres_terminees'] = $stmt->fetchColumn();

    $stmt = $db->prepare("SELECT COUNT(*) FROM demandes_clients WHERE user_id = ? AND traitee = 0");
    $stmt->execute([$userId]);
    $stats['demandes_non_traitees'] = $stmt->fetchColumn();

    // Phoning - semaine en cours
    $stmt = $db->prepare("SELECT COALESCE(SUM(nombre_appels),0) as appels, COALESCE(SUM(nombre_rdv),0) as rdv FROM seances_phoning WHERE user_id = ? AND YEARWEEK(date_ajout, 1) = YEARWEEK(CURDATE(), 1)");
    $stmt->execute([$userId]);
    $phoning = $stmt->fetch();
    $stats['phoning_appels_semaine'] = $phoning['appels'];
    $stats['phoning_rdv_semaine'] = $phoning['rdv'];
    $stats['phoning_reste_appels'] = max(0, 60 - $phoning['appels']);
    $stats['phoning_reste_rdv'] = max(0, 12 - $phoning['rdv']);

    // Formations à venir
    $stmt = $db->prepare("SELECT COUNT(*) FROM formations WHERE user_id = ? AND date_fin >= CURDATE()");
    $stmt->execute([$userId]);
    $stats['formations_a_venir'] = $stmt->fetchColumn();

    // Notes de frais non envoyées Expansya
    $stmt = $db->prepare("SELECT COUNT(*) FROM formations WHERE user_id = ? AND lieu = 'presentiel' AND envoyee_expansya = 0");
    $stmt->execute([$userId]);
    $stats['formations_non_expansya'] = $stmt->fetchColumn();

    return $stats;
}

/**
 * Récupérer les éléments en retard (pour badges menu + modale alerte)
 * Retard = non traité et datant de plus de 7 jours
 */
function getRetardsUrgents($userId) {
    $db = getDB();
    $retards = ['instances' => [], 'demandes_clients' => [], 'rappels' => []];

    // Instances : statut a_faire ET échéance dépassée de 7 jours+
    $stmt = $db->prepare("SELECT id, numero_personne, date_echeance, categories, details
        FROM instances WHERE user_id = ? AND statut = 'a_faire'
        AND date_echeance IS NOT NULL AND date_echeance <= DATE_SUB(CURDATE(), INTERVAL 7 DAY)
        ORDER BY date_echeance ASC LIMIT 20");
    $stmt->execute([$userId]);
    $retards['instances'] = $stmt->fetchAll();

    // Demandes clients : non traitée ET créée il y a plus de 7 jours
    $stmt = $db->prepare("SELECT id, numero_personne, date_ajout, details_demande, service
        FROM demandes_clients WHERE user_id = ? AND traitee = 0
        AND date_ajout <= DATE_SUB(CURDATE(), INTERVAL 7 DAY)
        ORDER BY date_ajout ASC LIMIT 20");
    $stmt->execute([$userId]);
    $retards['demandes_clients'] = $stmt->fetchAll();

    // Rappels : non traité ET créé il y a plus de 7 jours
    $stmt = $db->prepare("SELECT id, numero_personne, date_ajout, motif
        FROM demandes_rappel WHERE user_id = ? AND traitee = 0
        AND date_ajout <= DATE_SUB(CURDATE(), INTERVAL 7 DAY)
        ORDER BY date_ajout ASC LIMIT 20");
    $stmt->execute([$userId]);
    $retards['rappels'] = $stmt->fetchAll();

    return $retards;
}

/**
 * Nombre de produits en alerte stock pour un utilisateur portail donné.
 * Retourne 0 si l'utilisateur n'est pas abonné aux alertes ou si les tables n'existent pas.
 */
function getStockAlertes($userId) {
    try {
        $db = getDB();
        $stmt = $db->prepare("SELECT 1 FROM stock_alert_users WHERE user_id = ?");
        $stmt->execute([$userId]);
        if (!$stmt->fetchColumn()) return 0;
        return (int)$db->query(
            "SELECT COUNT(*) FROM stock_produits
             WHERE actif = 1 AND alerte_active = 1
               AND seuil_alerte IS NOT NULL
               AND quantite_stock <= seuil_alerte"
        )->fetchColumn();
    } catch (Exception $e) {
        return 0;
    }
}

/**
 * Récupérer la config SMTP depuis la base
 */
function getSmtpConfig() {
    $db = getDB();
    try {
        $db->exec("CREATE TABLE IF NOT EXISTS smtp_config (
            id INT AUTO_INCREMENT PRIMARY KEY,
            smtp_host VARCHAR(255) NOT NULL DEFAULT '',
            smtp_port INT DEFAULT 587,
            smtp_user VARCHAR(255) DEFAULT '',
            smtp_pass VARCHAR(255) DEFAULT '',
            smtp_secure ENUM('tls','ssl','none') DEFAULT 'tls',
            mail_from VARCHAR(255) DEFAULT '',
            mail_from_name VARCHAR(255) DEFAULT 'Portail CE',
            rappel_enabled TINYINT(1) DEFAULT 1,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
        $row = $db->query("SELECT * FROM smtp_config LIMIT 1")->fetch();
        if (!$row) {
            $db->exec("INSERT INTO smtp_config (smtp_host) VALUES ('')");
            $row = $db->query("SELECT * FROM smtp_config LIMIT 1")->fetch();
        }
        return $row;
    } catch (Exception $e) {
        return null;
    }
}

/**
 * Envoyer un email via SMTP (socket direct, sans dépendance externe)
 */
function sendSmtpMail($to, $subject, $htmlBody) {
    $cfg = getSmtpConfig();
    if (!$cfg || empty($cfg['smtp_host']) || empty($to)) return false;

    $host = $cfg['smtp_host'];
    $port = (int)$cfg['smtp_port'] ?: 587;
    $user = $cfg['smtp_user'];
    $pass = $cfg['smtp_pass'];
    $secure = $cfg['smtp_secure'];
    $from = $cfg['mail_from'] ?: $user;
    $fromName = $cfg['mail_from_name'] ?: 'Portail CE';

    // Construire le message MIME
    $boundary = md5(uniqid(time()));
    $headers = "From: {$fromName} <{$from}>\r\n";
    $headers .= "To: {$to}\r\n";
    $headers .= "Subject: {$subject}\r\n";
    $headers .= "MIME-Version: 1.0\r\n";
    $headers .= "Content-Type: text/html; charset=UTF-8\r\n";
    $headers .= "Date: " . date('r') . "\r\n";

    // Connexion SMTP
    $prefix = ($secure === 'ssl') ? 'ssl://' : '';
    $fp = @fsockopen($prefix . $host, $port, $errno, $errstr, 10);
    if (!$fp) return false;

    $resp = function() use ($fp) {
        $r = '';
        while ($line = fgets($fp, 512)) {
            $r .= $line;
            if (substr($line, 3, 1) === ' ') break;
        }
        return $r;
    };

    $cmd = function($c) use ($fp, $resp) {
        fwrite($fp, $c . "\r\n");
        return $resp();
    };

    $resp(); // banner

    $cmd("EHLO localhost");

    if ($secure === 'tls') {
        $cmd("STARTTLS");
        stream_socket_enable_crypto($fp, true, STREAM_CRYPTO_METHOD_TLS_CLIENT);
        $cmd("EHLO localhost");
    }

    if (!empty($user) && !empty($pass)) {
        $cmd("AUTH LOGIN");
        $cmd(base64_encode($user));
        $cmd(base64_encode($pass));
    }

    $cmd("MAIL FROM:<{$from}>");
    $cmd("RCPT TO:<{$to}>");
    $cmd("DATA");

    $message = $headers . "\r\n" . $htmlBody . "\r\n";
    fwrite($fp, $message . "\r\n.\r\n");
    $result = $resp();

    $cmd("QUIT");
    fclose($fp);

    return strpos($result, '250') !== false;
}

/**
 * Envoyer un email de rappel quotidien pour les retards (1x/jour max)
 */
function sendDailyReminderIfNeeded($userId) {
    $cfg = getSmtpConfig();
    if (!$cfg || !$cfg['rappel_enabled'] || empty($cfg['smtp_host'])) return;

    $db = getDB();

    // Récupérer l'email de l'utilisateur
    $stmt = $db->prepare("SELECT email_pro, nom, prenom FROM users WHERE id = ?");
    $stmt->execute([$userId]);
    $user = $stmt->fetch();
    if (!$user || empty($user['email_pro'])) return;

    // Vérifier si un rappel a déjà été envoyé aujourd'hui
    $stmt = $db->prepare("SELECT id FROM notifications_log WHERE user_id = ? AND DATE(date_envoi) = CURDATE() AND type = 'rappel_retards'");
    $stmt->execute([$userId]);
    if ($stmt->fetch()) return; // Déjà envoyé aujourd'hui

    // Récupérer les retards
    $retards = getRetardsUrgents($userId);
    $nbInstances = count($retards['instances']);
    $nbDemandes = count($retards['demandes_clients']);
    $nbRappels = count($retards['rappels']);
    $total = $nbInstances + $nbDemandes + $nbRappels;

    if ($total === 0) return; // Rien en retard

    // Construire l'email HTML
    $prenom = e($user['prenom']);
    $html = "
    <div style='font-family:Arial,sans-serif;max-width:600px;margin:0 auto;'>
        <div style='background:#dc0032;color:white;padding:20px;text-align:center;'>
            <h1 style='margin:0;font-size:22px;'>Rappel - {$total} traitement(s) en retard</h1>
        </div>
        <div style='padding:20px;background:#f9f9f9;'>
            <p>Bonjour <strong>{$prenom}</strong>,</p>
            <p>Vous avez des traitements en retard de plus de 7 jours :</p>";

    if ($nbInstances > 0) {
        $html .= "<h3 style='color:#dc0032;'>Instances ({$nbInstances})</h3><ul>";
        foreach ($retards['instances'] as $r) {
            $html .= "<li><strong>" . e($r['numero_personne']) . "</strong> - Échéance : " . formatDate($r['date_echeance']) . " - " . excerpt(e($r['details'] ?? ''), 60) . "</li>";
        }
        $html .= "</ul>";
    }
    if ($nbDemandes > 0) {
        $html .= "<h3 style='color:#dc0032;'>Demandes clients ({$nbDemandes})</h3><ul>";
        foreach ($retards['demandes_clients'] as $r) {
            $html .= "<li><strong>" . e($r['numero_personne']) . "</strong> - Depuis le " . formatDate($r['date_ajout']) . " - " . excerpt(e($r['details_demande'] ?? ''), 60) . "</li>";
        }
        $html .= "</ul>";
    }
    if ($nbRappels > 0) {
        $html .= "<h3 style='color:#dc0032;'>Rappels ({$nbRappels})</h3><ul>";
        foreach ($retards['rappels'] as $r) {
            $html .= "<li><strong>" . e($r['numero_personne']) . "</strong> - Depuis le " . formatDate($r['date_ajout']) . " - " . excerpt(e($r['motif'] ?? ''), 60) . "</li>";
        }
        $html .= "</ul>";
    }

    $appUrl = defined('APP_URL') ? APP_URL : '';
    $html .= "
            <p style='margin-top:20px;'>
                <a href='{$appUrl}' style='display:inline-block;background:#dc0032;color:white;padding:12px 24px;text-decoration:none;font-weight:bold;'>
                    Accéder au portail
                </a>
            </p>
            <p style='color:#999;font-size:12px;margin-top:20px;'>Ce rappel est envoyé automatiquement une fois par jour.</p>
        </div>
    </div>";

    $subject = "[Portail CE] {$total} traitement(s) en retard";
    $sent = sendSmtpMail($user['email_pro'], $subject, $html);

    // Logger l'envoi (même en échec pour éviter le spam)
    $db->prepare("INSERT INTO notifications_log (user_id, type, nb_instances, nb_demandes) VALUES (?, 'rappel_retards', ?, ?)")
        ->execute([$userId, $nbInstances, $nbDemandes]);
}

/**
 * Outils publics (accessibles sans connexion, sans enregistrement de données)
 * L'administrateur choisit ceux qui sont affichés sur /tools et accessibles publiquement.
 * Chemins relatifs au dossier tools/.
 */
function getPublicToolsCatalog() {
    // 'default' : état tant que l'admin n'a rien décidé (false pour les données internes : rien n'est exposé sans choix explicite)
    return [
        'calculateur' => [
            'label' => 'Calculateur de budget', 'icon' => 'fa-calculator', 'url' => 'calculateur.php', 'default' => true,
            'description' => 'Estimez votre reste à vivre à partir de vos revenus et de vos charges mensuelles.',
            'note' => 'Les calculs se font dans votre navigateur : rien n\'est envoyé ni conservé.',
        ],
        'capacite' => [
            'added' => '2026-10-06',
            'label' => 'Capacité d\'emprunt', 'icon' => 'fa-hand-holding-dollar', 'url' => 'capacite.php', 'default' => true,
            'description' => 'Estimez le capital empruntable et le budget d\'achat à partir des revenus, des charges et des conditions du prêt.',
            'note' => 'Les calculs se font dans votre navigateur : rien n\'est envoyé ni conservé.',
        ],
        'notaire' => [
            'added' => '2026-10-06',
            'label' => 'Frais de notaire', 'icon' => 'fa-scale-balanced', 'url' => 'notaire.php', 'default' => true,
            'description' => 'Estimez les frais de notaire d\'une acquisition (ancien ou neuf) : droits, émoluments, taxes et débours.',
            'note' => 'Les calculs se font dans votre navigateur : rien n\'est envoyé ni conservé.',
        ],
        'ptz' => [
            'added' => '2026-10-06',
            'label' => 'Simulateur PTZ', 'icon' => 'fa-percent', 'url' => 'ptz.php', 'default' => true,
            'description' => 'Vérifiez l\'éligibilité au prêt à taux zéro et estimez son montant, sa durée et son différé.',
            'note' => 'Les calculs se font dans votre navigateur : rien n\'est envoyé ni conservé.',
        ],
        'relais' => [
            'added' => '2026-10-07',
            'label' => 'Prêt relais', 'icon' => 'fa-house-circle-check', 'url' => 'relais.php', 'default' => true,
            'description' => 'Estimez le montant d\'un prêt relais, son coût et ce qu\'il reste après la vente du bien.',
            'note' => 'Les calculs se font dans votre navigateur : rien n\'est envoyé ni conservé.',
        ],
        'rachat' => [
            'added' => '2026-10-07',
            'label' => 'Rachat de crédits', 'icon' => 'fa-layer-group', 'url' => 'rachat.php', 'default' => true,
            'description' => 'Simulez le regroupement de vos crédits : indemnités de remboursement anticipé, nouvelle mensualité et coût global.',
            'note' => 'Les calculs se font dans votre navigateur : rien n\'est envoyé ni conservé.',
        ],
        'pieces' => [
            'added' => '2026-10-08',
            'label' => 'Pièces justificatives', 'icon' => 'fa-list-check', 'url' => 'pieces.php', 'default' => true,
            'description' => 'Obtenez la liste des pièces à demander selon le type de demande et la situation du client, à imprimer ou à envoyer.',
            'note' => 'La liste se construit dans votre navigateur : rien n\'est envoyé ni conservé.',
        ],
        'pdf' => [
            'added' => '2026-10-08',
            'label' => 'Boîte à outils PDF', 'icon' => 'fa-file-pdf', 'url' => 'pdf.php', 'default' => true,
            'description' => 'Fusionnez, découpez ou faites pivoter des PDF, et convertissez des images en PDF.',
            'note' => 'Le traitement se fait dans votre navigateur : vos fichiers ne sont ni envoyés ni conservés.',
        ],
        'courrier' => [
            'label' => 'Générateur de courrier', 'icon' => 'fa-envelope-open-text', 'url' => 'courrier.php', 'default' => true,
            'description' => 'Rédigez un courrier mis en forme, avec variables, puis imprimez-le ou enregistrez-le en PDF.',
            'note' => 'Le courrier reste dans votre navigateur : rien n\'est envoyé ni conservé.',
        ],
        'dpe' => [
            'label' => 'Recherche DPE par adresse', 'icon' => 'fa-leaf', 'url' => '../modules/dpe/index.php', 'default' => true,
            'description' => 'Retrouvez les diagnostics de performance énergétique d\'une adresse, sur une liste ou une carte.',
            'note' => 'L\'adresse saisie est transmise aux API publiques de l\'ADEME et de la Base Adresse Nationale pour la recherche, sans être enregistrée par ce portail.',
        ],
        'rge' => [
            'label' => 'Vérification RGE', 'icon' => 'fa-certificate', 'url' => 'rge.php', 'default' => true,
            'description' => 'Vérifiez la certification RGE d\'une entreprise par nom, SIREN ou SIRET.',
            'note' => 'Le nom ou le numéro saisi est transmis à l\'API publique de l\'ADEME pour la recherche, sans être enregistré par ce portail.',
        ],
        'bureau_dom' => [
            'label' => 'Bureau domiciliaire', 'icon' => 'fa-building', 'url' => 'bureau_dom.php', 'default' => true,
            'description' => 'Remplissez le formulaire de modification de bureau domiciliaire, puis imprimez-le.',
            'note' => 'Le formulaire reste dans votre navigateur : rien n\'est envoyé ni conservé.',
        ],
        'procedures' => [
            'label' => 'Procédures', 'icon' => 'fa-book', 'url' => '../modules/procedures/public.php', 'default' => true,
            'description' => 'Consultez les procédures publiées et leur contenu.',
            'note' => 'Consultation sans enregistrement. Seule exception : une proposition d\'ajout ou de modification que vous choisissez d\'envoyer pour validation.',
        ],
        'codes' => [
            'label' => 'Codes utiles', 'icon' => 'fa-key', 'url' => 'codes.php', 'default' => false,
            'description' => 'Liste des codes validés et de leur fonction.',
            'note' => 'Consultation sans enregistrement. Seule exception : une proposition d\'ajout ou de modification que vous choisissez d\'envoyer pour validation.',
        ],
        'contacts' => [
            'label' => 'Contacts utiles', 'icon' => 'fa-address-book', 'url' => 'contacts.php', 'default' => false,
            'description' => 'Services à contacter, avec téléphone, e-mail et motif de contact.',
            'note' => 'Consultation sans enregistrement. Seule exception : une proposition d\'ajout ou de modification que vous choisissez d\'envoyer pour validation.',
        ],
    ];
}

function ensurePublicToolsSchema() {
    $db = getDB();
    $db->exec("CREATE TABLE IF NOT EXISTS public_tools (
        tool_key VARCHAR(50) PRIMARY KEY,
        enabled TINYINT(1) NOT NULL DEFAULT 1
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    // indisponible = 1 (avec enabled = 0) : la carte reste visible sur /tools, grisée, avec le motif
    try { $db->exec("ALTER TABLE public_tools ADD COLUMN indisponible TINYINT(1) NOT NULL DEFAULT 0"); } catch (Exception $e) {}
    try { $db->exec("ALTER TABLE public_tools ADD COLUMN motif VARCHAR(500) DEFAULT NULL"); } catch (Exception $e) {}
}

/**
 * État de chaque outil public : 'actif' (listé et utilisable), 'indisponible' (carte grisée + motif)
 * ou 'masque' (ni listé ni utilisable). Valeur par défaut du catalogue tant que l'admin n'a rien décidé.
 */
function getPublicToolsStatus() {
    ensurePublicToolsSchema();
    $rows = [];
    foreach (getDB()->query("SELECT tool_key, enabled, indisponible, motif FROM public_tools")->fetchAll() as $r) $rows[$r['tool_key']] = $r;
    $status = [];
    foreach (getPublicToolsCatalog() as $key => $tool) {
        if (!isset($rows[$key])) { $status[$key] = ['state' => !empty($tool['default']) ? 'actif' : 'masque', 'motif' => '']; continue; }
        $r = $rows[$key];
        $state = (int)$r['enabled'] === 1 ? 'actif' : ((int)$r['indisponible'] === 1 ? 'indisponible' : 'masque');
        $status[$key] = ['state' => $state, 'motif' => (string)($r['motif'] ?? '')];
    }
    return $status;
}

/** Clés des outils publics actifs */
function getEnabledPublicTools() {
    return array_keys(array_filter(getPublicToolsStatus(), fn($s) => $s['state'] === 'actif'));
}

/** Vrai si un utilisateur du portail est connecté (il passe outre états et codes d'accès) */
function toolsVisitorIsLoggedIn() {
    if (isset($_COOKIE[session_name()])) {
        if (session_status() !== PHP_SESSION_ACTIVE) @session_start();
        return !empty($_SESSION['user_id']);
    }
    return false;
}

/** Bloque l'accès public à un outil non actif (page « indisponible » avec le motif, ou 404 si masqué) ; les utilisateurs connectés passent toujours. */
function requirePublicTool($key, $json = false) {
    if (toolsVisitorIsLoggedIn()) return;
    requireToolsAccess($json);
    $st = getPublicToolsStatus()[$key] ?? ['state' => 'masque', 'motif' => ''];
    if ($st['state'] === 'actif') return;

    $unavailable = $st['state'] === 'indisponible';
    http_response_code($unavailable ? 503 : 404);
    $msg = $unavailable ? ($st['motif'] !== '' ? $st['motif'] : 'Cet outil est temporairement indisponible.') : 'Cet outil n\'est pas disponible.';
    if ($json) { header('Content-Type: application/json; charset=utf-8'); echo json_encode(['error' => $msg]); exit; }
    $label = htmlspecialchars(getPublicToolsCatalog()[$key]['label'] ?? 'Outil', ENT_QUOTES, 'UTF-8');
    echo '<!DOCTYPE html><html lang="fr"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">'
        . '<title>' . $label . ' indisponible</title>'
        . '<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet"></head>'
        . '<body class="bg-light"><div class="container py-5" style="max-width:640px"><div class="card shadow-sm"><div class="card-body text-center p-4">'
        . '<h1 class="h4">' . $label . '</h1><p class="mt-3 mb-4">' . nl2br(htmlspecialchars($msg, ENT_QUOTES, 'UTF-8')) . '</p>'
        . ($unavailable ? '<a class="btn btn-outline-secondary" href="' . (strpos($_SERVER['SCRIPT_NAME'] ?? '', '/modules/') !== false ? '../../tools/' : './') . '">Retour aux outils</a>' : '')
        . '</div></div></div></body></html>';
    exit;
}

function ensureToolsFeedbackSchema() {
    getDB()->exec("CREATE TABLE IF NOT EXISTS tools_feedback (
        id INT AUTO_INCREMENT PRIMARY KEY,
        type VARCHAR(20) NOT NULL,
        tool_key VARCHAR(50) DEFAULT NULL,
        message TEXT NOT NULL,
        contact VARCHAR(255) DEFAULT NULL,
        lu TINYINT(1) NOT NULL DEFAULT 0,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
}

/**
 * Protection par code d'accès de /tools : l'admin peut activer la protection et créer plusieurs codes
 * (libellé, activation, date d'expiration facultative). Les codes sont stockés hachés.
 * Une fois un code valide saisi, le visiteur reçoit un cookie signé ; supprimer/désactiver le code
 * (ou le laisser expirer) coupe l'accès au prochain chargement.
 */
function ensureToolsAccessSchema() {
    $db = getDB();
    $db->exec("CREATE TABLE IF NOT EXISTS tools_settings (
        k VARCHAR(50) PRIMARY KEY,
        v TEXT
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    $db->exec("CREATE TABLE IF NOT EXISTS tools_codes (
        id INT AUTO_INCREMENT PRIMARY KEY,
        label VARCHAR(100) NOT NULL,
        code_hash VARCHAR(255) NOT NULL,
        actif TINYINT(1) NOT NULL DEFAULT 1,
        expires_at DATE DEFAULT NULL,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    // Journal des connexions : une ligne par saisie réussie d'un code (aucune IP conservée)
    $db->exec("CREATE TABLE IF NOT EXISTS tools_code_logs (
        id INT AUTO_INCREMENT PRIMARY KEY,
        code_id INT NOT NULL,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_code (code_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
}

function getToolsSetting($key, $default = '') {
    ensureToolsAccessSchema();
    $stmt = getDB()->prepare("SELECT v FROM tools_settings WHERE k = ?");
    $stmt->execute([$key]);
    $v = $stmt->fetchColumn();
    return $v === false ? $default : $v;
}

function setToolsSetting($key, $value) {
    ensureToolsAccessSchema();
    getDB()->prepare("REPLACE INTO tools_settings (k, v) VALUES (?, ?)")->execute([$key, $value]);
}

function toolsProtectionEnabled() {
    return getToolsSetting('protection_enabled', '0') === '1';
}

/** Codes utilisables aujourd'hui (actifs et non expirés) */
function getActiveToolsCodes() {
    ensureToolsAccessSchema();
    $stmt = getDB()->prepare("SELECT * FROM tools_codes WHERE actif = 1 AND (expires_at IS NULL OR expires_at >= ?)");
    $stmt->execute([date('Y-m-d')]);
    return $stmt->fetchAll();
}

function toolsAccessSecret() {
    $secret = getToolsSetting('secret', '');
    if ($secret === '') { $secret = bin2hex(random_bytes(32)); setToolsSetting('secret', $secret); }
    return $secret;
}

/** Chemin de base de l'application (ex. "" ou "/ce"), déduit de l'URL du script courant */
function toolsBasePath() {
    return preg_match('#^(.*?)/(tools|modules)/#', $_SERVER['SCRIPT_NAME'] ?? '', $m) ? $m[1] : '';
}

function toolsAccessToken(array $codeRow) {
    $payload = $codeRow['id'] . '.' . substr(md5($codeRow['code_hash']), 0, 10);
    return $payload . '.' . hash_hmac('sha256', $payload, toolsAccessSecret());
}

function toolsGrantAccess(array $codeRow) {
    setcookie('tools_access', toolsAccessToken($codeRow), [
        'expires' => time() + 30 * 86400, 'path' => toolsBasePath() . '/',
        'secure' => !empty($_SERVER['HTTPS']), 'httponly' => true, 'samesite' => 'Lax',
    ]);
}

/** Vrai si la protection est désactivée, ou si le visiteur présente un cookie lié à un code encore valide */
function toolsAccessGranted() {
    if (!toolsProtectionEnabled()) return true;
    $parts = explode('.', $_COOKIE['tools_access'] ?? '');
    if (count($parts) !== 3) return false;
    foreach (getActiveToolsCodes() as $row) {
        if (hash_equals(toolsAccessToken($row), implode('.', $parts))) return true;
    }
    return false;
}

/** Vérifie un code saisi ; retourne la ligne du code correspondant ou null */
function findToolsCode($input) {
    foreach (getActiveToolsCodes() as $row) {
        if (password_verify($input, $row['code_hash'])) return $row;
    }
    return null;
}

/** Redirige vers la saisie du code (ou répond 401 pour une API) si /tools est protégé et le visiteur n'a pas de code valide */
function requireToolsAccess($json = false) {
    if (toolsVisitorIsLoggedIn() || toolsAccessGranted()) return;
    if ($json) {
        http_response_code(401);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['error' => "Code d'accès requis."]);
        exit;
    }
    header('Location: ' . toolsBasePath() . '/tools/access.php?next=' . urlencode($_SERVER['REQUEST_URI'] ?? ''));
    exit;
}

/**
 * Message d'information affiché sur /tools (éditeur WYSIWYG dans l'admin).
 * Le HTML est assaini à l'enregistrement : liste blanche de balises et d'attributs.
 */
function sanitizeToolsMessageHtml($html) {
    $html = trim((string)$html);
    if ($html === '') return '';
    $allowed = ['p', 'br', 'b', 'strong', 'i', 'em', 'u', 'ul', 'ol', 'li', 'h2', 'h3', 'a', 'span', 'div', 'font', 'blockquote'];
    $dropWhole = ['script', 'style', 'iframe', 'object', 'embed', 'form', 'svg', 'math', 'template'];
    $doc = new DOMDocument();
    libxml_use_internal_errors(true);
    $doc->loadHTML('<?xml encoding="utf-8"?><div id="root">' . $html . '</div>', LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
    libxml_clear_errors();
    $root = $doc->getElementById('root');
    if (!$root) return '';

    $clean = function (DOMNode $node) use (&$clean, $allowed, $dropWhole) {
        foreach (iterator_to_array($node->childNodes) as $child) {
            if ($child->nodeType !== XML_ELEMENT_NODE) {
                if ($child->nodeType !== XML_TEXT_NODE) $node->removeChild($child); // commentaires, etc.
                continue;
            }
            $tag = strtolower($child->nodeName);
            if (in_array($tag, $dropWhole, true)) { $node->removeChild($child); continue; }
            $clean($child);
            if (!in_array($tag, $allowed, true)) { // balise inconnue : on garde le contenu
                while ($child->firstChild) $node->insertBefore($child->firstChild, $child);
                $node->removeChild($child);
                continue;
            }
            foreach (iterator_to_array($child->attributes) as $attr) {
                $name = strtolower($attr->nodeName);
                $val = trim($attr->nodeValue);
                $keep = false;
                if ($tag === 'a' && $name === 'href' && preg_match('#^(https?://|mailto:)#i', $val)) $keep = true;
                elseif ($tag === 'font' && $name === 'color' && preg_match('/^(#[0-9a-f]{3,8}|[a-z]+)$/i', $val)) $keep = true;
                elseif ($name === 'style') {
                    // uniquement color et text-align
                    $parts = [];
                    foreach (explode(';', $val) as $decl) {
                        if (preg_match('/^\s*(color|text-align)\s*:\s*(#[0-9a-f]{3,8}|rgb\(\s*\d{1,3}\s*,\s*\d{1,3}\s*,\s*\d{1,3}\s*\)|[a-z]+)\s*$/i', $decl, $m)) $parts[] = strtolower($m[1]) . ':' . $m[2];
                    }
                    if ($parts) { $child->setAttribute('style', implode(';', $parts)); continue; }
                }
                if (!$keep) $child->removeAttribute($attr->nodeName);
            }
            if ($tag === 'a') {
                if ($child->hasAttribute('href')) { $child->setAttribute('target', '_blank'); $child->setAttribute('rel', 'noopener noreferrer'); }
                else { while ($child->firstChild) $node->insertBefore($child->firstChild, $child); $node->removeChild($child); }
            }
        }
    };
    $clean($root);
    $out = '';
    foreach ($root->childNodes as $c) $out .= $doc->saveHTML($c);
    return trim($out);
}

/** Message à afficher sur /tools pour le visiteur courant, ou '' */
function getToolsMessageForVisitor() {
    if (getToolsSetting('message_enabled', '0') !== '1') return '';
    $html = getToolsSetting('message_html', '');
    if (trim(strip_tags($html)) === '') return '';
    if (getToolsSetting('message_audience', 'all') === 'members' && !toolsVisitorIsLoggedIn()) return '';
    return $html;
}

/** Enregistre une connexion réussie avec ce code (aucune donnée sur le visiteur) */
function logToolsCodeUse($codeId) {
    ensureToolsAccessSchema();
    getDB()->prepare("INSERT INTO tools_code_logs (code_id) VALUES (?)")->execute([(int)$codeId]);
}

/** Statistiques de connexion par code : [code_id => ['total', 'last', 'days30']] */
function getToolsCodeStats() {
    ensureToolsAccessSchema();
    $stmt = getDB()->prepare("SELECT code_id, COUNT(*) AS total, MAX(created_at) AS last_use,
        SUM(CASE WHEN created_at >= ? THEN 1 ELSE 0 END) AS days30 FROM tools_code_logs GROUP BY code_id");
    $stmt->execute([date('Y-m-d H:i:s', strtotime('-30 days'))]);
    $out = [];
    foreach ($stmt->fetchAll() as $r) $out[(int)$r['code_id']] = ['total' => (int)$r['total'], 'last' => $r['last_use'], 'days30' => (int)$r['days30']];
    return $out;
}

/** Nombre de procédures publiées par catégorie (pour la carte « Procédures ») : [['nom','nb'], …] */
function getPublicProcedureCategoryCounts() {
    ensureProcedureCategoriesSchema();
    try { getDB()->exec("ALTER TABLE procedures ADD COLUMN approved TINYINT(1) DEFAULT 0"); } catch (Exception $e) {}
    $rows = getDB()->query("SELECT c.nom AS nom, COUNT(p.id) AS nb
        FROM procedures p LEFT JOIN categories_procedures c ON p.categorie_id = c.id
        WHERE p.approved = 1 GROUP BY c.id, c.nom ORDER BY (c.id IS NULL), c.ordre, c.nom")->fetchAll();
    foreach ($rows as &$r) { $r['nom'] = $r['nom'] ?? 'Sans catégorie'; $r['nb'] = (int)$r['nb']; }
    return $rows;
}

/** Limite par visiteur (fichier temporaire, aucune IP en base) : retourne false si le quota est atteint, sinon enregistre l'essai */
function toolsRateLimitHit($bucket, $max, $windowSeconds) {
    $file = sys_get_temp_dir() . '/tools_rl_' . $bucket . '_' . md5($_SERVER['REMOTE_ADDR'] ?? '');
    $now = time();
    $hits = is_file($file) ? array_filter(array_map('intval', file($file, FILE_IGNORE_NEW_LINES)), fn($t) => $t > $now - $windowSeconds) : [];
    if (count($hits) >= $max) return false;
    $hits[] = $now;
    @file_put_contents($file, implode("\n", $hits));
    return true;
}

/**
 * Propositions des visiteurs de /tools (ajouts et modifications de codes utiles et de contacts utiles),
 * à valider par l'administrateur. Les procédures ont leur propre table (procedure_proposals).
 */
function ensureToolsProposalsSchema() {
    getDB()->exec("CREATE TABLE IF NOT EXISTS tools_proposals (
        id INT AUTO_INCREMENT PRIMARY KEY,
        kind VARCHAR(10) NOT NULL,
        type VARCHAR(10) NOT NULL,
        target_id INT DEFAULT NULL,
        data TEXT NOT NULL,
        contributor_prenom VARCHAR(100) NOT NULL,
        contributor_nom VARCHAR(100) NOT NULL,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
}

/** Champs proposables par type d'élément : champ => [libellé, longueur max] */
function getToolsProposalFields($kind) {
    return $kind === 'code'
        ? ['code' => ['Code', 100], 'fonction' => ['Fonction', 255]]
        : ['service' => ['Service', 255], 'telephone' => ['Téléphone', 20], 'mail' => ['E-mail', 150], 'a_contacter_pour' => ['À contacter pour', 1000]];
}

/**
 * Traite un POST de proposition (ajout ou modification) depuis une page publique.
 * Retourne null en cas de succès, sinon un message d'erreur.
 */
function handleToolsProposalPost($kind) {
    if (!empty($_POST['website'])) return null; // champ piège : on fait comme si c'était envoyé
    $type = ($_POST['type'] ?? '') === 'edit' ? 'edit' : 'create';
    $prenom = mb_substr(trim($_POST['contributor_prenom'] ?? ''), 0, 100);
    $nom = mb_substr(trim($_POST['contributor_nom'] ?? ''), 0, 100);
    if ($prenom === '' || $nom === '') return 'Merci de renseigner votre nom et votre prénom.';

    $data = [];
    foreach (getToolsProposalFields($kind) as $field => [$label, $max]) {
        $v = trim((string)($_POST[$field] ?? ''));
        if ($v === '' && in_array($field, ['code', 'service'], true)) return 'Le champ « ' . $label . ' » est obligatoire.';
        if (mb_strlen($v) > $max) return 'Le champ « ' . $label . ' » est trop long (' . $max . ' caractères maximum).';
        $data[$field] = $v;
    }
    if ($kind === 'contact' && $data['mail'] !== '' && !filter_var($data['mail'], FILTER_VALIDATE_EMAIL)) return "L'adresse e-mail n'est pas valide.";

    $db = getDB();
    $targetId = null;
    if ($type === 'edit') {
        $targetId = (int)($_POST['target_id'] ?? 0);
        $table = $kind === 'code' ? 'codes_utiles' : 'contacts_utiles';
        $stmt = $db->prepare("SELECT id FROM $table WHERE id = ? AND approved = 1");
        $stmt->execute([$targetId]);
        if (!$stmt->fetch()) return "L'élément à modifier est introuvable.";
    }
    if (!toolsRateLimitHit('proposal', 10, 3600)) return 'Trop de propositions envoyées récemment. Réessayez plus tard.';

    ensureToolsProposalsSchema();
    $db->prepare("INSERT INTO tools_proposals (kind, type, target_id, data, contributor_prenom, contributor_nom) VALUES (?, ?, ?, ?, ?, ?)")
       ->execute([$kind, $type, $targetId, json_encode($data, JSON_UNESCAPED_UNICODE), $prenom, $nom]);
    return null;
}

/** Modèles de courrier que l'admin rend utilisables sur /tools (colonne ajoutée à la demande) */
function ensurePublicTemplatesColumn() {
    try { getDB()->exec("ALTER TABLE modeles_courriers ADD COLUMN public_tools TINYINT(1) NOT NULL DEFAULT 0"); } catch (Exception $e) {}
}

function getPublicCourrierTemplates() {
    try {
        ensurePublicTemplatesColumn();
        $rows = getDB()->query("SELECT id, nom_modele, objet, corps, variables FROM modeles_courriers WHERE public_tools = 1 AND approved = 1 ORDER BY nom_modele")->fetchAll();
    } catch (Exception $e) { return []; }
    foreach ($rows as &$r) $r['corps'] = sanitizeToolsMessageHtml($r['corps'] ?? ''); // affiché à des visiteurs anonymes
    return $rows;
}

/**
 * Recherche globale de /tools : uniquement dans les outils actifs (états de l'admin) et les données
 * publiables (éléments validés, modèles de courrier choisis). Retourne [['type','titre','detail','url'], …]
 * avec des URL relatives au dossier tools/.
 */
function searchToolsGlobal($query) {
    $query = trim($query);
    if (mb_strlen($query) < 2) return [];
    $db = getDB();
    $active = getEnabledPublicTools();
    $catalog = getPublicToolsCatalog();
    $like = '%' . str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $query) . '%';
    $needle = mb_strtolower($query);
    $enc = urlencode($query);
    $results = [];

    // Les outils eux-mêmes
    foreach ($active as $key) {
        $t = $catalog[$key];
        if (preg_match('/(?<![\p{L}\p{N}])' . preg_quote($query, '/') . '/iu', $t['label'] . ' ' . $t['description'])) { // début de mot
            $results[] = ['type' => 'Outil', 'titre' => $t['label'], 'detail' => $t['description'], 'url' => $t['url']];
        }
    }

    if (in_array('procedures', $active, true)) {
        try {
            ensureProcedureCategoriesSchema();
            $stmt = $db->prepare("SELECT p.nom, p.texte, c.nom AS cat FROM procedures p LEFT JOIN categories_procedures c ON p.categorie_id = c.id
                WHERE p.approved = 1 AND (p.nom LIKE ? OR p.texte LIKE ?) ORDER BY p.mise_en_avant DESC, p.nom LIMIT 10");
            $stmt->execute([$like, $like]);
            foreach ($stmt->fetchAll() as $r) {
                $results[] = ['type' => 'Procédure', 'titre' => $r['nom'], 'detail' => ($r['cat'] ? $r['cat'] . ' – ' : '') . mb_substr(trim(strip_tags($r['texte'] ?? '')), 0, 120),
                    'url' => '../modules/procedures/public.php?q=' . urlencode($r['nom'])];
            }
        } catch (Exception $e) {}
    }
    if (in_array('codes', $active, true)) {
        try {
            $stmt = $db->prepare("SELECT code, fonction FROM codes_utiles WHERE approved = 1 AND (code LIKE ? OR fonction LIKE ?) ORDER BY code LIMIT 10");
            $stmt->execute([$like, $like]);
            foreach ($stmt->fetchAll() as $r) $results[] = ['type' => 'Code utile', 'titre' => $r['code'], 'detail' => mb_substr((string)$r['fonction'], 0, 120), 'url' => 'codes.php?q=' . urlencode($r['code'])];
        } catch (Exception $e) {}
    }
    if (in_array('contacts', $active, true)) {
        try {
            $stmt = $db->prepare("SELECT service, a_contacter_pour FROM contacts_utiles WHERE approved = 1 AND (service LIKE ? OR a_contacter_pour LIKE ? OR mail LIKE ? OR telephone LIKE ?) ORDER BY service LIMIT 10");
            $stmt->execute([$like, $like, $like, $like]);
            foreach ($stmt->fetchAll() as $r) $results[] = ['type' => 'Contact utile', 'titre' => $r['service'], 'detail' => mb_substr((string)$r['a_contacter_pour'], 0, 120), 'url' => 'contacts.php?q=' . urlencode($r['service'])];
        } catch (Exception $e) {}
    }
    if (in_array('courrier', $active, true)) {
        foreach (getPublicCourrierTemplates() as $t) {
            if (mb_strpos(mb_strtolower($t['nom_modele'] . ' ' . $t['objet']), $needle) !== false) {
                $results[] = ['type' => 'Modèle de courrier', 'titre' => $t['nom_modele'], 'detail' => $t['objet'], 'url' => 'courrier.php?modele=' . (int)$t['id']];
            }
        }
    }
    // Raccourci : vérifier la recherche dans l'outil RGE
    if (in_array('rge', $active, true)) {
        $results[] = ['type' => 'Vérification RGE', 'titre' => 'Vérifier « ' . $query . ' » (RGE)', 'detail' => 'Rechercher une entreprise par nom, SIREN ou SIRET', 'url' => 'rge.php?q=' . $enc];
    }
    return $results;
}
