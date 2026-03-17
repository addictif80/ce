<?php
/**
 * Script CRON de notification par email
 * Envoie un rappel aux utilisateurs pour :
 * - Instances non traitées datant de plus d'une semaine
 * - Demandes clients non traitées datant de plus d'une semaine
 *
 * À planifier via crontab (ex: tous les jours à 8h) :
 * 0 8 * * * php /chemin/vers/ce/cron/notifications.php
 */

require_once __DIR__ . '/../includes/config.php';

// Configuration email
$fromEmail = defined('MAIL_FROM') ? MAIL_FROM : 'noreply@portail-ce.local';
$fromName = defined('MAIL_FROM_NAME') ? MAIL_FROM_NAME : 'Portail Gestion d\'Activités';
$appUrl = defined('APP_URL') ? APP_URL : 'http://localhost/ce';

$db = getDB();

// Récupérer tous les utilisateurs avec un email
$users = $db->query("SELECT id, nom, prenom, email_pro FROM users WHERE email_pro IS NOT NULL AND email_pro != ''")->fetchAll();

$totalSent = 0;

foreach ($users as $user) {
    $userId = $user['id'];
    $email = $user['email_pro'];

    // Instances non traitées datant de plus d'une semaine
    $stmt = $db->prepare("
        SELECT id, numero_personne, date_ajout, date_echeance, categories, details
        FROM instances
        WHERE user_id = ? AND statut = 'a_faire' AND date_ajout <= DATE_SUB(CURDATE(), INTERVAL 7 DAY)
        ORDER BY date_echeance ASC
    ");
    $stmt->execute([$userId]);
    $instancesEnRetard = $stmt->fetchAll();

    // Demandes clients non traitées datant de plus d'une semaine
    $stmt = $db->prepare("
        SELECT id, numero_personne, date_ajout, details_demande, service
        FROM demandes_clients
        WHERE user_id = ? AND traitee = 0 AND date_ajout <= DATE_SUB(CURDATE(), INTERVAL 7 DAY)
        ORDER BY date_ajout ASC
    ");
    $stmt->execute([$userId]);
    $demandesEnRetard = $stmt->fetchAll();

    // Rien à signaler, on passe
    if (empty($instancesEnRetard) && empty($demandesEnRetard)) {
        continue;
    }

    // Construire l'email
    $subject = 'Rappel : ' . count($instancesEnRetard) . ' instance(s) et ' . count($demandesEnRetard) . ' demande(s) client en attente depuis plus d\'une semaine';

    $body = '<!DOCTYPE html><html lang="fr"><head><meta charset="UTF-8"></head><body style="font-family:Segoe UI,Arial,sans-serif;color:#333;max-width:700px;margin:0 auto;">';
    $body .= '<div style="background:#e4002b;color:white;padding:20px;text-align:center;border-radius:8px 8px 0 0;">';
    $body .= '<h1 style="margin:0;font-size:20px;">Portail Gestion d\'Activités</h1>';
    $body .= '<p style="margin:5px 0 0;opacity:0.8;font-size:13px;">Rappel automatique</p></div>';
    $body .= '<div style="padding:25px;background:#fff;border:1px solid #ddd;border-top:none;">';
    $body .= '<p>Bonjour <strong>' . htmlspecialchars($user['prenom']) . '</strong>,</p>';
    $body .= '<p>Voici un rappel des éléments en attente depuis plus d\'une semaine :</p>';

    // Instances
    if (!empty($instancesEnRetard)) {
        $body .= '<h2 style="color:#e4002b;font-size:16px;border-bottom:2px solid #e4002b;padding-bottom:8px;">Instances non traitées (' . count($instancesEnRetard) . ')</h2>';
        $body .= '<table style="width:100%;border-collapse:collapse;font-size:13px;margin-bottom:20px;">';
        $body .= '<tr style="background:#f5f5f5;"><th style="padding:8px;text-align:left;border:1px solid #ddd;">N° / Nom</th><th style="padding:8px;text-align:left;border:1px solid #ddd;">Date ajout</th><th style="padding:8px;text-align:left;border:1px solid #ddd;">Échéance</th><th style="padding:8px;text-align:left;border:1px solid #ddd;">Catégorie</th></tr>';
        foreach ($instancesEnRetard as $inst) {
            $echeanceStyle = '';
            if ($inst['date_echeance'] && $inst['date_echeance'] < date('Y-m-d')) {
                $echeanceStyle = 'color:#e4002b;font-weight:bold;';
            }
            $body .= '<tr>';
            $body .= '<td style="padding:8px;border:1px solid #ddd;">' . htmlspecialchars($inst['numero_personne']) . '</td>';
            $body .= '<td style="padding:8px;border:1px solid #ddd;">' . date('d/m/Y', strtotime($inst['date_ajout'])) . '</td>';
            $body .= '<td style="padding:8px;border:1px solid #ddd;' . $echeanceStyle . '">' . ($inst['date_echeance'] ? date('d/m/Y', strtotime($inst['date_echeance'])) : '-') . '</td>';
            $body .= '<td style="padding:8px;border:1px solid #ddd;">' . htmlspecialchars($inst['categories'] ?? '') . '</td>';
            $body .= '</tr>';
        }
        $body .= '</table>';
    }

    // Demandes clients
    if (!empty($demandesEnRetard)) {
        $body .= '<h2 style="color:#e4002b;font-size:16px;border-bottom:2px solid #e4002b;padding-bottom:8px;">Demandes clients non traitées (' . count($demandesEnRetard) . ')</h2>';
        $body .= '<table style="width:100%;border-collapse:collapse;font-size:13px;margin-bottom:20px;">';
        $body .= '<tr style="background:#f5f5f5;"><th style="padding:8px;text-align:left;border:1px solid #ddd;">N° / Nom</th><th style="padding:8px;text-align:left;border:1px solid #ddd;">Date ajout</th><th style="padding:8px;text-align:left;border:1px solid #ddd;">Service</th><th style="padding:8px;text-align:left;border:1px solid #ddd;">Détails</th></tr>';
        foreach ($demandesEnRetard as $dem) {
            $body .= '<tr>';
            $body .= '<td style="padding:8px;border:1px solid #ddd;">' . htmlspecialchars($dem['numero_personne']) . '</td>';
            $body .= '<td style="padding:8px;border:1px solid #ddd;">' . date('d/m/Y', strtotime($dem['date_ajout'])) . '</td>';
            $body .= '<td style="padding:8px;border:1px solid #ddd;">' . htmlspecialchars($dem['service'] ?? '') . '</td>';
            $body .= '<td style="padding:8px;border:1px solid #ddd;">' . htmlspecialchars(mb_substr($dem['details_demande'] ?? '', 0, 80)) . '</td>';
            $body .= '</tr>';
        }
        $body .= '</table>';
    }

    $body .= '<p style="margin-top:20px;"><a href="' . $appUrl . '" style="background:#e4002b;color:white;padding:10px 25px;text-decoration:none;border-radius:6px;font-weight:600;">Accéder au portail</a></p>';
    $body .= '<p style="color:#999;font-size:12px;margin-top:30px;">Ce message est envoyé automatiquement. Vous recevez ce rappel car des éléments sont en attente depuis plus de 7 jours.</p>';
    $body .= '</div></body></html>';

    // Envoyer l'email
    $headers = "MIME-Version: 1.0\r\n";
    $headers .= "Content-type: text/html; charset=UTF-8\r\n";
    $headers .= "From: $fromName <$fromEmail>\r\n";
    $headers .= "Reply-To: $fromEmail\r\n";
    $headers .= "X-Mailer: Portail-CE/1.0\r\n";

    if (mail($email, $subject, $body, $headers)) {
        $totalSent++;
        echo date('Y-m-d H:i:s') . " - Email envoyé à {$user['prenom']} {$user['nom']} ({$email}) : " . count($instancesEnRetard) . " instance(s), " . count($demandesEnRetard) . " demande(s)\n";
    } else {
        echo date('Y-m-d H:i:s') . " - ERREUR envoi à {$email}\n";
    }
}

echo date('Y-m-d H:i:s') . " - Terminé. $totalSent email(s) envoyé(s).\n";
