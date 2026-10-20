<?php
/**
 * Journal d'audit de /tools : chaque clic, saisie et action est ajouté à une table en « ajout seul ».
 * Immuabilité : (1) des déclencheurs SQL refusent tout UPDATE / DELETE sur la table ; (2) chaque ligne contient l'empreinte SHA-256
 * de la précédente (chaîne), de sorte que toute modification, suppression ou insertion a posteriori rompt la chaîne et est détectée
 * par la vérification d'intégrité de l'administration. Aucun écran de l'application ne permet de modifier ou de supprimer une ligne.
 */
require_once __DIR__ . '/functions.php';

const AUDIT_TABLE = 'tools_audit_log';
const AUDIT_VALUE_MAX = 500;

function auditEvents() {
    return ['page' => 'Ouverture de page', 'clic' => 'Clic', 'saisie' => 'Saisie / choix', 'impression' => 'Impression', 'copie' => 'Copie',
        'acces' => 'Accès par code', 'courrier' => 'Aide à la rédaction', 'autre' => 'Autre'];
}

function auditEnsureSchema() {
    static $done = false; if ($done) return; $done = true;
    $db = getDB();
    $db->exec("CREATE TABLE IF NOT EXISTS " . AUDIT_TABLE . " (
        id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        ts DATETIME(3) NOT NULL,
        actor_type VARCHAR(12) NOT NULL,
        actor VARCHAR(160) NOT NULL,
        ip VARCHAR(45) NOT NULL DEFAULT '',
        ua VARCHAR(255) NOT NULL DEFAULT '',
        sid VARCHAR(32) NOT NULL DEFAULT '',
        tool_key VARCHAR(50) NOT NULL DEFAULT '',
        page VARCHAR(200) NOT NULL DEFAULT '',
        event VARCHAR(20) NOT NULL,
        target VARCHAR(255) NOT NULL DEFAULT '',
        value TEXT,
        prev_hash CHAR(64) NOT NULL,
        hash CHAR(64) NOT NULL,
        INDEX idx_ts (ts), INDEX idx_tool (tool_key), INDEX idx_actor (actor), INDEX idx_event (event)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    // Déclencheurs d'immuabilité (créés une fois ; nécessite le privilège TRIGGER, sinon seule la chaîne d'empreintes protège)
    try {
        $have = $db->query("SELECT TRIGGER_NAME FROM information_schema.TRIGGERS WHERE TRIGGER_SCHEMA = DATABASE() AND EVENT_OBJECT_TABLE = '" . AUDIT_TABLE . "'")->fetchAll(PDO::FETCH_COLUMN);
        foreach (['update' => 'UPDATE', 'delete' => 'DELETE'] as $k => $op) {
            $name = AUDIT_TABLE . '_no_' . $k;
            if (!in_array($name, $have, true)) {
                $db->exec("CREATE TRIGGER $name BEFORE $op ON " . AUDIT_TABLE . " FOR EACH ROW SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Journal immuable : modification et suppression interdites'");
            }
        }
    } catch (Throwable $e) { error_log('audit triggers: ' . $e->getMessage()); }
}

function auditTriggersOk() {
    try {
        $n = (int)getDB()->query("SELECT COUNT(*) FROM information_schema.TRIGGERS WHERE TRIGGER_SCHEMA = DATABASE() AND EVENT_OBJECT_TABLE = '" . AUDIT_TABLE . "'")->fetchColumn();
        return $n >= 2;
    } catch (Throwable $e) { return false; }
}

/** Adresse IP réelle : X-Forwarded-For n'est lu que si la connexion vient d'un proxy privé (réseau local, Tailscale). */
function auditClientIp() {
    $ip = $_SERVER['REMOTE_ADDR'] ?? '';
    $priv = $ip !== '' && (!filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)
        || preg_match('/^100\.(6[4-9]|[7-9]\d|1[01]\d|12[0-7])\./', $ip));
    if ($priv && !empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        $f = trim(explode(',', $_SERVER['HTTP_X_FORWARDED_FOR'])[0]);
        if (filter_var($f, FILTER_VALIDATE_IP)) return $f;
    }
    return $ip;
}

/** Qui agit : utilisateur connecté, ou libellé du code d'accès /tools présenté. */
function auditActor() {
    if (toolsVisitorIsLoggedIn()) {
        $u = function_exists('getCurrentUser') ? getCurrentUser() : null;
        $name = trim(($u['prenom'] ?? '') . ' ' . ($u['nom'] ?? ''));
        return ['connecté', mb_substr(($name !== '' ? $name : 'utilisateur') . ' (#' . (int)($_SESSION['user_id'] ?? 0) . ')', 0, 160)];
    }
    $tok = $_COOKIE['tools_access'] ?? '';
    if ($tok !== '') {
        try {
            foreach (getActiveToolsCodes() as $row) {
                if (hash_equals(toolsAccessToken($row), $tok)) return ['code', mb_substr('code « ' . $row['label'] . ' »', 0, 160)];
            }
        } catch (Throwable $e) {}
    }
    return ['visiteur', 'visiteur sans code'];
}

function auditHash($prev, array $r) {
    return hash('sha256', $prev . '|' . json_encode([$r['ts'], $r['actor_type'], $r['actor'], $r['ip'], $r['ua'], $r['sid'], $r['tool_key'], $r['page'], $r['event'], $r['target'], $r['value']], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
}

/** Ajoute des lignes au journal, dans l'ordre, en chaînant les empreintes (verrou pour éviter deux écritures simultanées). */
function auditAppend(array $events) {
    if (!$events) return 0;
    try {
        auditEnsureSchema();
        $db = getDB();
        [$atype, $actor] = auditActor();
        $ip = mb_substr(auditClientIp(), 0, 45);
        $ua = mb_substr((string)($_SERVER['HTTP_USER_AGENT'] ?? ''), 0, 255);
        $got = $db->query("SELECT GET_LOCK('tools_audit_chain', 10)")->fetchColumn();
        if (!$got) return 0;
        $n = 0;
        try {
            $prev = (string)($db->query("SELECT hash FROM " . AUDIT_TABLE . " ORDER BY id DESC LIMIT 1")->fetchColumn() ?: str_repeat('0', 64));
            $ins = $db->prepare("INSERT INTO " . AUDIT_TABLE . " (ts, actor_type, actor, ip, ua, sid, tool_key, page, event, target, value, prev_hash, hash) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?)");
            foreach ($events as $e) {
                $r = [
                    'ts' => $e['ts'] ?? date('Y-m-d H:i:s') . '.' . sprintf('%03d', (int)(microtime(true) * 1000) % 1000),
                    'actor_type' => $e['actor_type'] ?? $atype, 'actor' => $e['actor'] ?? $actor, 'ip' => $ip, 'ua' => $ua,
                    'sid' => preg_replace('/[^a-z0-9]/i', '', (string)($e['sid'] ?? '')) ?: '',
                    'tool_key' => preg_match('/^[a-z0-9_]{1,50}$/', (string)($e['tool_key'] ?? '')) ? $e['tool_key'] : '',
                    'page' => mb_substr((string)($e['page'] ?? ''), 0, 200),
                    'event' => isset(auditEvents()[$e['event'] ?? '']) ? $e['event'] : 'autre',
                    'target' => mb_substr((string)($e['target'] ?? ''), 0, 255),
                    'value' => isset($e['value']) && $e['value'] !== '' ? mb_substr((string)$e['value'], 0, AUDIT_VALUE_MAX) : null,
                ];
                $r['sid'] = mb_substr($r['sid'], 0, 32);
                $h = auditHash($prev, $r);
                $ins->execute([$r['ts'], $r['actor_type'], $r['actor'], $r['ip'], $r['ua'], $r['sid'], $r['tool_key'], $r['page'], $r['event'], $r['target'], $r['value'], $prev, $h]);
                $prev = $h; $n++;
            }
        } finally { $db->query("SELECT RELEASE_LOCK('tools_audit_chain')"); }
        return $n;
    } catch (Throwable $e) { error_log('audit append: ' . $e->getMessage()); return 0; }
}

/** Événement côté serveur (ouverture de page, accès par code…). Ne lève jamais d'erreur. */
function auditServer($toolKey, $event, $target = '', $value = null) {
    auditAppend([['tool_key' => (string)$toolKey, 'page' => mb_substr((string)($_SERVER['REQUEST_URI'] ?? ''), 0, 200), 'event' => $event, 'target' => $target, 'value' => $value, 'sid' => '']]);
}

/** Vérifie toute la chaîne. Retourne ['ok'=>bool, 'rows'=>int, 'head'=>hash, 'bad_id'=>?int, 'reason'=>string]. */
function auditVerify() {
    auditEnsureSchema();
    $db = getDB();
    $prev = str_repeat('0', 64); $rows = 0; $last = $prev; $after = 0;
    while (true) {
        $st = $db->prepare("SELECT * FROM " . AUDIT_TABLE . " WHERE id > ? ORDER BY id ASC LIMIT 2000");
        $st->execute([$after]);
        $batch = $st->fetchAll(PDO::FETCH_ASSOC);
        if (!$batch) break;
        foreach ($batch as $r) {
            $after = (int)$r['id']; $rows++;
            if ($r['prev_hash'] !== $prev) return ['ok' => false, 'rows' => $rows, 'head' => $last, 'bad_id' => $after, 'reason' => 'chaîne rompue : une ligne précédente a été supprimée, insérée ou déplacée'];
            if (!hash_equals(auditHash($prev, $r), $r['hash'])) return ['ok' => false, 'rows' => $rows, 'head' => $last, 'bad_id' => $after, 'reason' => 'contenu modifié : l\'empreinte de la ligne ne correspond plus'];
            $prev = $r['hash']; $last = $r['hash'];
        }
    }
    return ['ok' => true, 'rows' => $rows, 'head' => $last, 'bad_id' => null, 'reason' => ''];
}

function auditFilters(array $q) {
    $w = []; $p = [];
    if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $q['du'] ?? '')) { $w[] = 'ts >= ?'; $p[] = $q['du'] . ' 00:00:00'; }
    if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $q['au'] ?? '')) { $w[] = 'ts <= ?'; $p[] = $q['au'] . ' 23:59:59.999'; }
    if (preg_match('/^[a-z0-9_]{1,50}$/', $q['outil'] ?? '')) { $w[] = 'tool_key = ?'; $p[] = $q['outil']; }
    if (isset(auditEvents()[$q['ev'] ?? ''])) { $w[] = 'event = ?'; $p[] = $q['ev']; }
    $a = trim((string)($q['acteur'] ?? '')); if ($a !== '') { $w[] = '(actor LIKE ? OR ip LIKE ?)'; $p[] = "%$a%"; $p[] = "%$a%"; }
    $t = trim((string)($q['texte'] ?? '')); if ($t !== '') { $w[] = '(target LIKE ? OR value LIKE ?)'; $p[] = "%$t%"; $p[] = "%$t%"; }
    return [$w ? 'WHERE ' . implode(' AND ', $w) : '', $p];
}
