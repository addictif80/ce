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
 * Récupérer les liens externes
 */
function getLiensExternes() {
    $db = getDB();
    return $db->query("SELECT * FROM liens_externes ORDER BY ordre ASC, nom ASC")->fetchAll();
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

    // Contacts utiles
    $stmt = $db->prepare("SELECT id, service AS titre, a_contacter_pour AS detail, 'contacts' AS type FROM contacts_utiles WHERE user_id = ? AND (telephone LIKE ? OR mail LIKE ? OR service LIKE ? OR a_contacter_pour LIKE ?)");
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
        $stmt = $db->prepare("SELECT id, CONCAT(prenom, ' ', nom) AS titre, CONCAT(COALESCE(email,''), ' ', COALESCE(telephone,''), ' ', COALESCE(ligne_interne,'')) AS detail, 'equipe' AS type FROM contacts_equipe WHERE (nom LIKE ? OR prenom LIKE ? OR email LIKE ? OR telephone LIKE ? OR ligne_interne LIKE ?)");
        $stmt->execute([$like, $like, $like, $like, $like]);
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
