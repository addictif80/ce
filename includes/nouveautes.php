<?php
/**
 * « Quoi de neuf » de /tools : nouveautés saisies par l'administrateur (nouveaux outils, évolutions, corrections)
 * + barèmes récemment mis à jour (lus depuis le suivi des barèmes). Aucune donnée de visiteur n'est enregistrée :
 * le « déjà vu » est conservé dans le navigateur du visiteur.
 */
function newsEnsureSchema() {
    static $done = false; if ($done) return; $done = true;
    $db = getDB();
    $db->exec("CREATE TABLE IF NOT EXISTS tools_news (
        id INT AUTO_INCREMENT PRIMARY KEY,
        date_news DATE NOT NULL,
        titre VARCHAR(150) NOT NULL,
        texte TEXT,
        type VARCHAR(20) NOT NULL DEFAULT 'evolution',
        tool_key VARCHAR(40) DEFAULT NULL,
        visible TINYINT(1) NOT NULL DEFAULT 1,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    // Premier lancement : une entrée par outil récent du catalogue
    if ((int)$db->query("SELECT COUNT(*) FROM tools_news")->fetchColumn() === 0 && function_exists('getPublicToolsCatalog')) {
        $ins = $db->prepare("INSERT INTO tools_news (date_news, titre, texte, type, tool_key) VALUES (?, ?, ?, 'outil', ?)");
        foreach (getPublicToolsCatalog() as $k => $t) {
            if (!empty($t['added'])) $ins->execute([$t['added'], 'Nouvel outil : ' . $t['label'], $t['description'] ?? '', $k]);
        }
    }
}

function newsTypes() {
    return ['outil' => ['Nouvel outil', 'primary', 'fa-wand-magic-sparkles'], 'evolution' => ['Évolution', 'info', 'fa-arrow-up-right-dots'],
            'bareme' => ['Barème mis à jour', 'warning', 'fa-scale-balanced'], 'correction' => ['Correction', 'success', 'fa-screwdriver-wrench']];
}

function newsList($onlyVisible = true, $limit = 100) {
    newsEnsureSchema();
    $sql = "SELECT * FROM tools_news" . ($onlyVisible ? " WHERE visible = 1" : "") . " ORDER BY date_news DESC, id DESC LIMIT " . (int)$limit;
    return getDB()->query($sql)->fetchAll();
}

function newsSave(array $p) {
    newsEnsureSchema();
    $date = preg_match('/^\d{4}-\d{2}-\d{2}$/', (string)($p['date_news'] ?? '')) ? $p['date_news'] : date('Y-m-d');
    $type = isset(newsTypes()[$p['type'] ?? '']) ? $p['type'] : 'evolution';
    $titre = mb_substr(trim((string)($p['titre'] ?? '')), 0, 150);
    if ($titre === '') return false;
    $texte = mb_substr(trim((string)($p['texte'] ?? '')), 0, 2000);
    $tool = mb_substr(trim((string)($p['tool_key'] ?? '')), 0, 40) ?: null;
    $visible = isset($p['visible']) ? 1 : 0;
    $id = (int)($p['id'] ?? 0);
    if ($id > 0) getDB()->prepare("UPDATE tools_news SET date_news=?, titre=?, texte=?, type=?, tool_key=?, visible=? WHERE id=?")->execute([$date, $titre, $texte, $type, $tool, $visible, $id]);
    else getDB()->prepare("INSERT INTO tools_news (date_news, titre, texte, type, tool_key, visible) VALUES (?,?,?,?,?,?)")->execute([$date, $titre, $texte, $type, $tool, $visible]);
    return true;
}

function newsDelete($id) { newsEnsureSchema(); getDB()->prepare("DELETE FROM tools_news WHERE id = ?")->execute([(int)$id]); }

/** Barèmes avec leur date de contrôle, du plus récent au plus ancien (affichés dans « Quoi de neuf »). */
function newsBaremes() {
    require_once __DIR__ . '/baremes.php';
    $out = [];
    foreach (baremeCatalog() as $k => $c) {
        $b = baremeGet($k);
        $out[] = ['cle' => $k, 'label' => $c['label'], 'icon' => $c['icon'] ?? 'fa-scale-balanced', 'date' => $b['date'], 'statut' => baremeStatut($b)];
    }
    usort($out, fn($a, $b) => strcmp((string)$b['date'], (string)$a['date']));
    return $out;
}

/** Date de la nouveauté la plus récente (pour le badge « nouveau » du visiteur). */
function newsLatestDate() {
    try { newsEnsureSchema(); return (string)getDB()->query("SELECT MAX(date_news) FROM tools_news WHERE visible = 1")->fetchColumn(); }
    catch (Throwable $e) { return ''; }
}
