<?php
/**
 * Import des fiches « particuliers » de service-public.fr en ligne de commande.
 *   php cron/import_service_public.php                    télécharge l'archive officielle puis l'indexe
 *   php cron/import_service_public.php /chemin/archive.zip indexe une archive déjà copiée sur le serveur (sans accès Internet)
 */
if (PHP_SAPI !== 'cli') { http_response_code(403); exit; }
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/servicepublic.php';
$arg = $argv[1] ?? null;
if ($arg !== null && !is_file($arg)) { fwrite(STDERR, "Fichier introuvable : $arg\n"); exit(1); }
$r = $arg ? spImportFile($arg) : spImportNow();
echo $r['ok'] ? "OK : {$r['n']} fiches indexées, {$r['supprimees']} retirées\n" : "ÉCHEC : {$r['err']}\n";
exit($r['ok'] ? 0 : 1);
