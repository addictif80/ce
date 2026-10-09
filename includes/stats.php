<?php
/**
 * Compteurs d'usage anonymes de /tools : nombre de vues par outil et par jour, et quelques actions (présentation, impression…).
 * Aucun identifiant, aucune adresse IP, aucun cookie : uniquement des totaux (jour, outil, type d'événement) pour savoir quels outils servent.
 */
function statsEvents() { return ['view' => 'Vues', 'tour' => 'Présentation « Comment ça marche ? »', 'print' => 'Impressions', 'link' => 'Liens copiés', 'copy' => 'Résultats copiés', 'mail' => 'E-mails', 'f2f' => 'Mode client']; }

function statsEnsureSchema() {
    static $done = false; if ($done) return; $done = true;
    getDB()->exec("CREATE TABLE IF NOT EXISTS tools_stats (
        day DATE NOT NULL,
        tool_key VARCHAR(50) NOT NULL,
        event VARCHAR(12) NOT NULL,
        n INT NOT NULL DEFAULT 0,
        PRIMARY KEY (day, tool_key, event)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
}

/** Incrémente un compteur ; ne lève jamais d'erreur (une statistique ne doit pas casser une page). */
function statsHit($toolKey, $event = 'view') {
    try {
        if (!isset(statsEvents()[$event]) || !preg_match('/^[a-z0-9_]{1,50}$/', (string)$toolKey)) return;
        statsEnsureSchema();
        getDB()->prepare("INSERT INTO tools_stats (day, tool_key, event, n) VALUES (CURDATE(), ?, ?, 1) ON DUPLICATE KEY UPDATE n = n + 1")->execute([$toolKey, $event]);
    } catch (Throwable $e) {}
}

/** Totaux des $days derniers jours : [tool_key => [event => n]] et série de vues par jour [tool_key => [date => n]]. */
function statsSummary($days = 30) {
    statsEnsureSchema();
    $st = getDB()->prepare("SELECT day, tool_key, event, n FROM tools_stats WHERE day >= DATE_SUB(CURDATE(), INTERVAL ? DAY)");
    $st->execute([(int)$days - 1]);
    $tot = []; $serie = [];
    foreach ($st->fetchAll() as $r) {
        $tot[$r['tool_key']][$r['event']] = ($tot[$r['tool_key']][$r['event']] ?? 0) + (int)$r['n'];
        if ($r['event'] === 'view') $serie[$r['tool_key']][$r['day']] = (int)$r['n'];
    }
    return [$tot, $serie];
}
