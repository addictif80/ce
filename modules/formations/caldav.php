<?php
/**
 * Export iCalendar (ICS) des formations pour synchronisation CalDAV
 * URL personnelle par utilisateur via token
 */
require_once __DIR__ . '/../../includes/config.php';

$token = $_GET['token'] ?? '';
if (!$token) {
    http_response_code(401);
    die('Token manquant');
}

$db = getDB();

// Trouver l'utilisateur correspondant au token
$stmt = $db->query("SELECT id, password FROM users");
$userId = null;
while ($row = $stmt->fetch()) {
    $expected = md5('caldav_' . $row['id'] . '_' . ($row['password'] ?? '') . '_formations');
    if (hash_equals($expected, $token)) {
        $userId = $row['id'];
        break;
    }
}

if (!$userId) {
    http_response_code(403);
    die('Token invalide');
}

// Récupérer les formations
$stmt = $db->prepare("SELECT * FROM formations WHERE user_id = ? ORDER BY date_debut");
$stmt->execute([$userId]);
$formations = $stmt->fetchAll();

// Générer le calendrier ICS
header('Content-Type: text/calendar; charset=utf-8');
header('Content-Disposition: inline; filename="formations.ics"');

$ics = "BEGIN:VCALENDAR\r\n";
$ics .= "VERSION:2.0\r\n";
$ics .= "PRODID:-//Portail CE//Formations//FR\r\n";
$ics .= "CALSCALE:GREGORIAN\r\n";
$ics .= "METHOD:PUBLISH\r\n";
$ics .= "X-WR-CALNAME:Formations CE\r\n";
$ics .= "X-WR-TIMEZONE:Europe/Paris\r\n";

foreach ($formations as $f) {
    $uid = 'formation-' . $f['id'] . '@portail-ce';
    $dtstart = date('Ymd', strtotime($f['date_debut']));
    // date_fin +1 jour car DTEND est exclusif en ICS pour les événements de type DATE
    $dtend = date('Ymd', strtotime($f['date_fin'] . ' +1 day'));
    $summary = escapeIcs($f['titre']);
    $location = $f['lieu'] === 'presentiel' ? escapeIcs($f['adresse_hotel'] ?? 'Présentiel') : 'Distanciel';
    $description = $f['lieu'] === 'presentiel' ? escapeIcs("Lieu: Présentiel\nHôtel: " . ($f['adresse_hotel'] ?? '-') . "\nRéservation: " . ($f['reservation_faite'] ? 'Oui' : 'Non') . "\nNote de frais: " . number_format($f['montant_total'], 2, ',', '') . " EUR") : 'Distanciel';
    $stamp = date('Ymd\THis\Z', strtotime($f['created_at']));

    $ics .= "BEGIN:VEVENT\r\n";
    $ics .= "UID:$uid\r\n";
    $ics .= "DTSTAMP:$stamp\r\n";
    $ics .= "DTSTART;VALUE=DATE:$dtstart\r\n";
    $ics .= "DTEND;VALUE=DATE:$dtend\r\n";
    $ics .= "SUMMARY:$summary\r\n";
    $ics .= "LOCATION:$location\r\n";
    $ics .= "DESCRIPTION:$description\r\n";
    $ics .= "END:VEVENT\r\n";
}

$ics .= "END:VCALENDAR\r\n";
echo $ics;

function escapeIcs($str) {
    $str = str_replace(['\\', ';', ',', "\n"], ['\\\\', '\\;', '\\,', '\\n'], $str ?? '');
    return $str;
}
