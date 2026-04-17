<?php
/**
 * Fonctions utilitaires
 */

require_once __DIR__ . '/config.php';

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
 * Récupérer les liens externes avec leurs catégories
 */
function getLiensExternes() {
    $db = getDB();
    // Auto-add categorie_id column
    try { $db->exec("ALTER TABLE liens_externes ADD COLUMN categorie_id INT DEFAULT NULL"); } catch (Exception $e) {}
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
function getDefaultMenuItems() {
    return [
        // Sections
        ['item_key' => 'activite', 'parent_key' => null, 'label' => 'Mon activité', 'icon' => 'fa-briefcase', 'url' => null, 'uri_patterns' => 'instances,rappels,demandes_clients,offres,rappels_clients,signatures', 'ordre' => 1],
        ['item_key' => 'formation', 'parent_key' => null, 'label' => 'Formation', 'icon' => 'fa-graduation-cap', 'url' => null, 'uri_patterns' => 'formations', 'ordre' => 2],
        ['item_key' => 'commercial', 'parent_key' => null, 'label' => 'Commercial', 'icon' => 'fa-handshake', 'url' => null, 'uri_patterns' => 'production,phoning,eai,mobilites', 'ordre' => 3],
        ['item_key' => 'outils', 'parent_key' => null, 'label' => 'Outils', 'icon' => 'fa-tools', 'url' => null, 'uri_patterns' => 'credit_immo,calculateur,courriers,courriers_internes,blocnotes,procedures,bureau_dom,retraits', 'ordre' => 4],
        ['item_key' => 'references', 'parent_key' => null, 'label' => 'Références', 'icon' => 'fa-bookmark', 'url' => null, 'uri_patterns' => '/codes/,/contacts/', 'ordre' => 5],
        // Items - Mon activité
        ['item_key' => 'instances', 'parent_key' => 'activite', 'label' => 'Mes instances', 'icon' => 'fa-tasks', 'url' => '/modules/instances/index.php', 'uri_patterns' => 'instances', 'ordre' => 1],
        ['item_key' => 'rappels', 'parent_key' => 'activite', 'label' => 'Demandes de rappel', 'icon' => 'fa-phone-alt', 'url' => '/modules/rappels/index.php', 'uri_patterns' => 'rappels', 'ordre' => 2],
        ['item_key' => 'demandes_clients', 'parent_key' => 'activite', 'label' => 'Suivi demandes clients', 'icon' => 'fa-headset', 'url' => '/modules/demandes_clients/index.php', 'uri_patterns' => 'demandes_clients', 'ordre' => 3],
        ['item_key' => 'offres', 'parent_key' => 'activite', 'label' => 'Offres en cours', 'icon' => 'fa-tags', 'url' => '/modules/offres/index.php', 'uri_patterns' => 'offres', 'ordre' => 4],
        ['item_key' => 'rappels_clients', 'parent_key' => 'activite', 'label' => 'Rappels clients', 'icon' => 'fa-phone-square-alt', 'url' => '/modules/rappels_clients/index.php', 'uri_patterns' => 'rappels_clients', 'ordre' => 5],
        ['item_key' => 'calendrier_instances', 'parent_key' => 'activite', 'label' => 'Calendrier instances', 'icon' => 'fa-calendar', 'url' => '/modules/instances/calendrier.php', 'uri_patterns' => 'instances/calendrier', 'ordre' => 6],
        ['item_key' => 'signatures', 'parent_key' => 'activite', 'label' => 'Suivi signatures', 'icon' => 'fa-file-signature', 'url' => '/modules/signatures/index.php', 'uri_patterns' => 'signatures', 'ordre' => 7],
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
        // Items - Références
        ['item_key' => 'codes', 'parent_key' => 'references', 'label' => 'Codes utiles', 'icon' => 'fa-key', 'url' => '/modules/codes/index.php', 'uri_patterns' => '/codes/', 'ordre' => 1],
        ['item_key' => 'contacts', 'parent_key' => 'references', 'label' => 'Contacts utiles', 'icon' => 'fa-address-book', 'url' => '/modules/contacts/index.php', 'uri_patterns' => '/contacts/', 'ordre' => 2],
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

    // Production
    try {
        $stmt = $db->prepare("SELECT id, produit_vendu AS titre, details AS detail, 'production' AS type FROM suivi_production WHERE user_id = ? AND (produit_vendu LIKE ? OR details LIKE ?)");
        $stmt->execute([$userId, $like, $like]);
        $results = array_merge($results, $stmt->fetchAll());
    } catch (Exception $e) {}

    // Crédit immobilier
    try {
        $stmt = $db->prepare("SELECT id, CONCAT(COALESCE(numero_personne,''), ' - ', COALESCE(adresse_bien,'')) AS titre, adresse_bien AS detail, 'credit_immo' AS type FROM credit_immobilier WHERE user_id = ? AND (numero_personne LIKE ? OR adresse_bien LIKE ?)");
        $stmt->execute([$userId, $like, $like]);
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
