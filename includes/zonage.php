<?php
/**
 * Zonage A/B/C des communes (PTZ, etc.) : liste officielle importée par l'administrateur depuis un fichier CSV
 * (data.gouv.fr « Liste des communes selon le zonage ABC »). Permet au simulateur PTZ de déduire la zone de la commune.
 * Zones stockées : A, Abis, B1, B2, C (« A bis » est traitée comme A pour le PTZ).
 */
function zonageEnsureSchema() {
    getDB()->exec("CREATE TABLE IF NOT EXISTS zonage_communes (
        code VARCHAR(5) NOT NULL PRIMARY KEY,
        dep VARCHAR(3) NOT NULL,
        commune VARCHAR(120) NOT NULL,
        zone VARCHAR(4) NOT NULL,
        KEY idx_dep (dep)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
}

function zonageStats() {
    try {
        zonageEnsureSchema();
        $n = (int)getDB()->query("SELECT COUNT(*) FROM zonage_communes")->fetchColumn();
    } catch (Exception $e) { $n = 0; }
    return ['count' => $n, 'date' => getToolsSetting('zonage_date', ''), 'source' => getToolsSetting('zonage_source', ''), 'libelle' => getToolsSetting('zonage_libelle', '')];
}

function zonageDepartementNames() {
    require_once __DIR__ . '/baremes.php';
    $names = [];
    foreach (baremeCatalog()['notaire']['default']['departements'] as $code => $d) $names[(string)$code] = $d['nom'];
    $names['2A'] = 'Corse-du-Sud'; $names['2B'] = 'Haute-Corse';
    $names['69'] = 'Rhône';
    return $names;
}

/** Normalise un en-tête de colonne : minuscules, sans accents ni ponctuation */
function zonageNorm($s) {
    $s = mb_strtolower(trim((string)$s));
    $s = strtr($s, ['é' => 'e', 'è' => 'e', 'ê' => 'e', 'ë' => 'e', 'à' => 'a', 'â' => 'a', 'î' => 'i', 'ï' => 'i', 'ô' => 'o', 'ù' => 'u', 'û' => 'u', 'ç' => 'c']);
    return preg_replace('/[^a-z0-9]/', '', $s);
}

/**
 * Importe un fichier CSV de zonage. Colonnes reconnues automatiquement (code commune, libellé, zone, département facultatif),
 * séparateur et encodage détectés. Renvoie ['ok' => bool, 'message' => string, 'importees' => int, 'ignorees' => int].
 */
function zonageImport($path, $nomFichier, $remplacer) {
    $raw = @file_get_contents($path);
    if ($raw === false || $raw === '') return ['ok' => false, 'message' => 'Fichier vide ou illisible.'];
    $raw = preg_replace('/^\xEF\xBB\xBF/', '', $raw);
    if (!mb_check_encoding($raw, 'UTF-8')) $raw = mb_convert_encoding($raw, 'UTF-8', 'Windows-1252');
    $lines = preg_split('/\R/', $raw);
    $head = array_shift($lines);
    $delim = ';';
    foreach ([';' => substr_count($head, ';'), ',' => substr_count($head, ','), "\t" => substr_count($head, "\t")] as $d => $c) if ($c > substr_count($head, $delim)) $delim = $d;
    $cols = array_map('zonageNorm', str_getcsv($head, $delim));
    $find = function ($cands) use ($cols) { foreach ($cols as $i => $c) if (in_array($c, $cands, true)) return $i; return false; };
    $iCode = $find(['codgeo', 'codecommune', 'codeinsee', 'insee', 'id', 'code']);
    $iNom = $find(['libgeo', 'commune', 'libelle', 'nom', 'nomcommune']);
    $iDep = $find(['dep', 'departement', 'numdep', 'codedepartement']);
    $iZone = false;
    foreach ($cols as $i => $c) if (strpos($c, 'zon') === 0) { $iZone = $i; break; }
    if ($iCode === false || $iNom === false || $iZone === false) {
        return ['ok' => false, 'message' => 'Colonnes non reconnues : le fichier doit contenir le code commune (ex. CODGEO), le nom de la commune et le zonage. En-têtes lus : ' . implode(' | ', array_map('trim', str_getcsv($head, $delim)))];
    }
    $zones = ['A' => 'A', 'ABIS' => 'Abis', 'B1' => 'B1', 'B2' => 'B2', 'C' => 'C'];
    $rows = []; $ignorees = 0;
    foreach ($lines as $l) {
        if (trim($l) === '') continue;
        $f = str_getcsv($l, $delim);
        $code = strtoupper(trim($f[$iCode] ?? ''));
        if (preg_match('/^\d{4}$/', $code)) $code = '0' . $code;
        $zone = $zones[strtoupper(preg_replace('/[\s_\-]/', '', $f[$iZone] ?? ''))] ?? null;
        $nom = trim($f[$iNom] ?? '');
        if (!preg_match('/^(\d{5}|2[AB]\d{3})$/', $code) || $zone === null || $nom === '') { $ignorees++; continue; }
        $dep = ($iDep !== false && trim($f[$iDep] ?? '') !== '') ? strtoupper(trim($f[$iDep])) : (substr($code, 0, 2) === '97' ? substr($code, 0, 3) : substr($code, 0, 2));
        $rows[] = [$code, mb_substr($dep, 0, 3), mb_substr($nom, 0, 120), $zone];
    }
    if (count($rows) < 100 && $remplacer) return ['ok' => false, 'message' => 'Moins de 100 communes valides dans le fichier : import refusé pour ne pas vider la liste existante (décochez « Remplacer » pour un fichier partiel).'];
    if (!$rows) return ['ok' => false, 'message' => 'Aucune commune valide trouvée dans le fichier.'];

    zonageEnsureSchema();
    $db = getDB();
    $db->beginTransaction();
    try {
        if ($remplacer) $db->exec("DELETE FROM zonage_communes");
        foreach (array_chunk($rows, 400) as $chunk) {
            $ph = implode(',', array_fill(0, count($chunk), '(?,?,?,?)'));
            $db->prepare("INSERT INTO zonage_communes (code, dep, commune, zone) VALUES $ph ON DUPLICATE KEY UPDATE dep = VALUES(dep), commune = VALUES(commune), zone = VALUES(zone)")
               ->execute(array_merge(...$chunk));
        }
        $db->commit();
    } catch (Exception $e) { $db->rollBack(); return ['ok' => false, 'message' => 'Erreur lors de l\'enregistrement : ' . $e->getMessage()]; }
    setToolsSetting('zonage_date', date('Y-m-d'));
    setToolsSetting('zonage_source', mb_substr($nomFichier, 0, 150));
    setToolsSetting('zonage_libelle', mb_substr(trim((string)($head !== '' ? ($cols[$iZone] !== '' ? str_getcsv($head, $delim)[$iZone] : '') : '')), 0, 150));
    return ['ok' => true, 'message' => count($rows) . ' communes importées' . ($ignorees ? ' (' . $ignorees . ' lignes ignorées)' : '') . '.', 'importees' => count($rows), 'ignorees' => $ignorees];
}

/** Départements présents dans la liste : [[code, nom], ...] */
function zonageDepartements() {
    zonageEnsureSchema();
    $names = zonageDepartementNames();
    $out = [];
    foreach (getDB()->query("SELECT DISTINCT dep FROM zonage_communes ORDER BY dep")->fetchAll(PDO::FETCH_COLUMN) as $dep) $out[] = [$dep, $names[$dep] ?? ''];
    usort($out, fn($a, $b) => strnatcasecmp($a[0], $b[0]));
    return $out;
}

/** Communes d'un département : [[code, nom, zone], ...] */
function zonageCommunes($dep) {
    zonageEnsureSchema();
    $st = getDB()->prepare("SELECT code, commune, zone FROM zonage_communes WHERE dep = ? ORDER BY commune");
    $st->execute([$dep]);
    return array_map('array_values', $st->fetchAll(PDO::FETCH_ASSOC));
}
