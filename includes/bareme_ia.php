<?php
/**
 * Vérification assistée par IA des barèmes : le portail récupère lui-même les pages des sources officielles
 * (service-public.fr en priorité, puis une liste de sites réputés), n'en garde que les passages qui contiennent les valeurs
 * du barème, et demande à l'IA de les comparer ligne à ligne. L'IA ne s'appuie que sur ces extraits et doit les citer ;
 * chaque citation est recontrôlée par le portail. Le résultat est un avis : seul l'administrateur déclare un barème « contrôlé ».
 */
require_once __DIR__ . '/baremes.php';
require_once __DIR__ . '/assist.php';

const BAREME_IA_TTL = 86400;          // durée de conservation d'une page récupérée (secondes)
const BAREME_IA_MAX_SOURCES = 6;
const BAREME_IA_BUDGET = 9000;        // caractères retenus par source

/** Sites autorisés (le domaine et ses sous-domaines) ; service-public.fr reste prioritaire. Modifiable en administration. */
function baremeIaDefaultDomains() {
    return ['service-public.fr', 'service-public.gouv.fr', 'legifrance.gouv.fr', 'impots.gouv.fr', 'bofip.impots.gouv.fr', 'economie.gouv.fr',
        'banque-france.fr', 'urssaf.fr', 'anil.org', 'data.gouv.fr', 'notaires.fr', 'info-retraite.fr', 'ameli.fr', 'orias.fr', 'acpr.banque-france.fr', 'vie-publique.fr', 'garantiedesdepots.fr', 'assemblee-nationale.fr', 'senat.fr', 'urssaf.fr', 'caf.fr', 'securite-sociale.fr'];
}
function baremeIaDomains() {
    $raw = trim((string)getToolsSetting('bareme_ia_domains', ''));
    if ($raw === '') return baremeIaDefaultDomains();
    $l = array_values(array_filter(array_map(fn($d) => strtolower(trim($d)), preg_split('/[\s,;]+/', $raw)), fn($d) => preg_match('/^[a-z0-9.-]+\.[a-z]{2,}$/', $d)));
    return $l ?: baremeIaDefaultDomains();
}
function baremeIaHostOk($url) {
    $p = parse_url($url);
    if (!$p || ($p['scheme'] ?? '') !== 'https' || empty($p['host'])) return false;
    $h = strtolower($p['host']);
    foreach (baremeIaDomains() as $d) if ($h === $d || substr($h, -strlen($d) - 1) === '.' . $d) return true;
    return false;
}
function baremeIaIsSP($url) { $h = strtolower((string)parse_url($url, PHP_URL_HOST)); return (bool)preg_match('/(^|\.)service-public\.(fr|gouv\.fr)$/', $h); }

function baremeIaEnsureSchema() {
    $db = getDB();
    $db->exec("CREATE TABLE IF NOT EXISTS bareme_ia_cache (h CHAR(40) PRIMARY KEY, url VARCHAR(500) NOT NULL, texte MEDIUMTEXT, fetched_at DATETIME NOT NULL) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    $db->exec("CREATE TABLE IF NOT EXISTS bareme_ia (cle VARCHAR(30) PRIMARY KEY, rapport MEDIUMTEXT NOT NULL, verdict VARCHAR(20) DEFAULT NULL, nb_ecarts INT NOT NULL DEFAULT 0, created_at DATETIME NOT NULL, created_by INT DEFAULT NULL) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
}

/** Texte d'une page HTML : tableaux en lignes « a | b | c », un bloc par ligne. */
function baremeIaHtmlToText($html) {
    $html = preg_replace('~<(script|style|noscript|svg|nav|footer|header)\b[^>]*>.*?</\1>~is', ' ', $html);
    $html = preg_replace('~</t[dh]>\s*<t[dh]\b[^>]*>~i', ' | ', $html);
    $html = preg_replace('~<(br|/p|/div|/li|/tr|/h[1-6]|/table|/caption)\b[^>]*>~i', "\n", $html);
    $t = html_entity_decode(strip_tags($html), ENT_QUOTES | ENT_HTML5, 'UTF-8');
    $t = preg_replace('/[\x{00a0}\x{202f}\x{2009}]/u', ' ', $t);
    $t = preg_replace('/[ \t]+/', ' ', $t);
    $lines = array_values(array_filter(array_map('trim', explode("\n", $t)), fn($l) => $l !== ''));
    return implode("\n", $lines);
}

/** Récupère une page (HTML ou PDF) d'un site autorisé ; la redirection finale doit rester sur un site autorisé. [texte|null, message] */
function baremeIaFetch($url) {
    if (!baremeIaHostOk($url)) return [null, 'site non autorisé'];
    baremeIaEnsureSchema();
    $db = getDB(); $h = sha1($url);
    $st = $db->prepare("SELECT texte FROM bareme_ia_cache WHERE h = ? AND fetched_at > ?");
    $st->execute([$h, date('Y-m-d H:i:s', time() - BAREME_IA_TTL)]);
    $c = $st->fetchColumn();
    if ($c !== false && $c !== '') return [$c, ''];
    $ch = curl_init($url);
    curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER => true, CURLOPT_FOLLOWLOCATION => true, CURLOPT_MAXREDIRS => 5, CURLOPT_TIMEOUT => 40, CURLOPT_CONNECTTIMEOUT => 10,
        CURLOPT_PROTOCOLS => CURLPROTO_HTTPS, CURLOPT_REDIR_PROTOCOLS => CURLPROTO_HTTPS, CURLOPT_MAXFILESIZE => 15 * 1048576, CURLOPT_ENCODING => '',
        CURLOPT_USERAGENT => 'Mozilla/5.0 (compatible; PortailBaremes/1.0)', CURLOPT_HTTPHEADER => ['Accept-Language: fr-FR,fr;q=0.9']]);
    $body = curl_exec($ch); $code = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE); $final = (string)curl_getinfo($ch, CURLINFO_EFFECTIVE_URL); $ct = (string)curl_getinfo($ch, CURLINFO_CONTENT_TYPE);
    $err = curl_error($ch); curl_close($ch);
    if ($body === false) return [null, 'connexion impossible (' . $err . ')'];
    if ($code < 200 || $code >= 300) return [null, 'HTTP ' . $code];
    if (!baremeIaHostOk($final)) return [null, 'redirection vers un site non autorisé'];
    if (stripos($ct, 'pdf') !== false || substr($body, 0, 4) === '%PDF') {
        $tmp = tempnam(sys_get_temp_dir(), 'bia'); file_put_contents($tmp, $body);
        $txt = @shell_exec('pdftotext -layout -q ' . escapeshellarg($tmp) . ' - 2>/dev/null'); @unlink($tmp);
        if (!is_string($txt) || trim($txt) === '') return [null, 'PDF illisible'];
        $txt = preg_replace('/[\x{00a0}\x{202f}]/u', ' ', $txt);
        $txt = implode("\n", array_values(array_filter(array_map(fn($l) => trim(preg_replace('/\s{2,}/', ' | ', trim($l))), explode("\n", $txt)), fn($l) => $l !== '')));
    } else {
        $txt = baremeIaHtmlToText($body);
    }
    if (mb_strlen($txt) < 80) return [null, 'page vide'];
    $db->prepare("REPLACE INTO bareme_ia_cache (h, url, texte, fetched_at) VALUES (?, ?, ?, NOW())")->execute([$h, $url, $txt]);
    return [$txt, ''];
}

/** Lignes « chemin = valeur » d'un barème, pour l'IA. */
function baremeIaFlatten($d, $prefix = '', array &$out = []) {
    if (is_array($d)) {
        $isList = array_keys($d) === range(0, count($d) - 1) && $d && !is_array($d[0]);
        if ($isList) { $out[] = $prefix . ' = [' . implode(' ; ', array_map(fn($v) => is_bool($v) ? ($v ? 'oui' : 'non') : (string)$v, $d)) . ']'; return $out; }
        foreach ($d as $k => $v) baremeIaFlatten($v, $prefix === '' ? (string)$k : $prefix . '.' . $k, $out);
    } else $out[] = $prefix . ' = ' . (is_bool($d) ? ($d ? 'oui' : 'non') : (string)$d);
    return $out;
}
function baremeIaNumbers(array $lines) {
    $n = [];
    foreach ($lines as $l) if (preg_match_all('/-?\d+(?:[.,]\d+)?/', substr($l, (int)strpos($l, '=')), $m)) foreach ($m[0] as $x) { $x = str_replace(',', '.', $x); if (strlen(preg_replace('/\D/', '', $x)) >= 3 || strpos($x, '.') !== false) $n[$x] = true; }
    return array_keys($n);
}
function baremeIaNorm($s) { $s = mb_strtolower((string)$s); $s = preg_replace('/(?<=\d)[ ](?=\d{3}\b)/u', '', $s); $s = str_replace(',', '.', $s); return trim(preg_replace('/\s+/u', ' ', $s)); }

/** Passages d'une page qui contiennent des valeurs du barème (ou des mots du libellé), dans la limite du budget. */
function baremeIaExcerpt($texte, array $nums, array $words, $budget = BAREME_IA_BUDGET) {
    $lines = explode("\n", $texte);
    if (mb_strlen($texte) <= $budget) return $texte;
    $score = [];
    foreach ($lines as $i => $l) {
        $n = baremeIaNorm($l); $s = 0;
        foreach ($nums as $x) if (preg_match('/(?<![\d.])' . preg_quote($x, '/') . '(?![\d])/', $n)) $s += 2;
        foreach ($words as $w) if (mb_strpos($n, $w) !== false) $s += 0.5;
        if ($s > 0) $score[$i] = $s;
    }
    arsort($score); $keep = []; $len = 0;
    foreach ($score as $i => $s) {
        foreach ([$i - 1, $i, $i + 1] as $j) if ($j >= 0 && $j < count($lines) && !isset($keep[$j])) { $keep[$j] = true; $len += mb_strlen($lines[$j]) + 1; }
        if ($len >= $budget) break;
    }
    if (!$keep) return mb_substr($texte, 0, $budget);
    ksort($keep); $out = []; $prev = -2;
    foreach (array_keys($keep) as $j) { if ($j > $prev + 1) $out[] = '[…]'; $out[] = $lines[$j]; $prev = $j; }
    return mb_substr(implode("\n", $out), 0, $budget + 600);
}

/** Sources du barème : fiches service-public.fr de l'index local, puis pages du catalogue sur sites autorisés (service-public d'abord). */
function baremeIaSources($cle, array $nums, array $words) {
    $c = baremeCatalog()[$cle]; $src = []; $notes = [];
    $cand = [];
    foreach ($c['sources'] as [$lib, $url]) if ($url !== '') $cand[] = [$lib, $url];
    usort($cand, fn($a, $b) => (int)baremeIaIsSP($b[1]) <=> (int)baremeIaIsSP($a[1]));
    foreach (array_slice($cand, 0, BAREME_IA_MAX_SOURCES) as [$lib, $url]) {
        [$t, $m] = baremeIaFetch($url);
        if ($t === null) { $notes[] = $lib . ' : ' . $m; continue; }
        $src[] = ['lib' => $lib, 'url' => $url, 'full' => $t, 'extrait' => baremeIaExcerpt($t, $nums, $words), 'sp' => baremeIaIsSP($url)];
    }
    // fiches de l'index local service-public.fr (données ouvertes), si l'import a été fait
    try {
        require_once __DIR__ . '/servicepublic.php';
        $have = array_column($src, 'url');
        foreach (spSearch($c['label'] . ' ' . $c['help'], 3) as $f) {
            if (in_array($f['url'], $have, true) || count($src) >= BAREME_IA_MAX_SOURCES + 3) continue;
            $t = $f['titre'] . "\n" . $f['texte'];
            array_unshift($src, ['lib' => 'Service-public.fr (index local) – ' . $f['titre'], 'url' => $f['url'], 'full' => $t, 'extrait' => baremeIaExcerpt($t, $nums, $words), 'sp' => true]);
        }
    } catch (Throwable $e) {}
    usort($src, fn($a, $b) => (int)$b['sp'] <=> (int)$a['sp']);
    return [$src, $notes];
}

function baremeIaPrompt($cle, array $lines, array $src) {
    $c = baremeCatalog()[$cle];
    $sys = "Tu es un vérificateur de données réglementaires françaises. On te donne les valeurs d'un barème saisies dans un logiciel et des EXTRAITS de sources officielles numérotés S1, S2… "
        . "Compare chaque valeur aux extraits, en t'appuyant UNIQUEMENT sur ces extraits (jamais sur ta mémoire). Les sources de type service-public.fr font foi en cas de divergence entre sources. "
        . "Pour chaque rubrique contrôlée : statut « concordant » (la source donne la même valeur), « ecart » (la source donne une valeur différente) ou « non_verifiable » (les extraits ne permettent pas de conclure). "
        . "Pour « concordant » et « ecart », indique la source (S1…) et recopie MOT POUR MOT, entre guillemets, un court passage de l'extrait (moins de 200 caractères) qui prouve ta conclusion. "
        . "Regroupe les valeurs voisines d'une même rubrique (ex. les tranches d'un barème) en une seule ligne. Ne dépasse pas 40 lignes ; traite d'abord les écarts. "
        . "Réponds UNIQUEMENT en JSON : {\"resume\":\"2 phrases\",\"controles\":[{\"rubrique\":\"\",\"valeur_saisie\":\"\",\"statut\":\"concordant|ecart|non_verifiable\",\"valeur_source\":\"\",\"source\":\"S1\",\"extrait\":\"\",\"commentaire\":\"\"}]}";
    $u = "BARÈME : " . $c['label'] . "\nDescription : " . $c['help'] . "\n\nVALEURS SAISIES (chemin = valeur ; les montants sont en euros, les taux en %) :\n" . implode("\n", $lines) . "\n\nSOURCES :\n";
    foreach ($src as $i => $s) $u .= "\n=== S" . ($i + 1) . " – " . $s['lib'] . " – " . $s['url'] . " ===\n" . $s['extrait'] . "\n";
    return [$sys, $u];
}

/** Vérifie un barème. Retourne le rapport (tableau) ; en cas d'échec : ['erreur' => message]. */
function baremeIaCheck($cle, $userId = null) {
    $cat = baremeCatalog();
    if (!isset($cat[$cle]) || baremeIsMemo($cle)) return ['erreur' => 'Barème inconnu ou sans valeurs chiffrées.'];
    if (!assistEnabled()) return ['erreur' => 'L\'assistant IA n\'est pas activé (clé API et activation dans l\'administration).'];
    if (!array_filter($cat[$cle]['sources'], fn($x) => $x[1] !== '')) return ['erreur' => 'Barème interne : aucune source publique à consulter. Contrôlez-le avec la documentation interne de l\'offre (la comparaison avec des sources publiques n\'aurait pas de sens).', 'interne' => true];
    $data = baremeGet($cle)['data'];
    $lines = baremeIaFlatten($data);
    if (count($lines) > 400) { $lines = array_slice($lines, 0, 400); }
    $nums = baremeIaNumbers($lines);
    $words = array_values(array_filter(array_map('mb_strtolower', preg_split('/[^\p{L}]+/u', $cat[$cle]['label'] . ' ' . $cat[$cle]['help'])), fn($w) => mb_strlen($w) >= 5));
    [$src, $notes] = baremeIaSources($cle, $nums, $words);
    if (!$src) return ['erreur' => 'Aucune source n\'a pu être consultée : ' . implode(' ; ', $notes)];
    [$sys, $usr] = baremeIaPrompt($cle, $lines, $src);
    [$rep, $err] = assistCall($sys, $usr, 4000);
    if ($rep === null) return ['erreur' => 'Appel à l\'IA impossible : ' . $err];
    $a = strpos($rep, '{'); $b = strrpos($rep, '}');
    $j = ($a !== false && $b !== false) ? json_decode(substr($rep, $a, $b - $a + 1), true) : null;
    if (!is_array($j) || !isset($j['controles']) || !is_array($j['controles'])) return ['erreur' => 'Réponse de l\'IA illisible, relancez la vérification.'];
    $ctl = []; $ecarts = 0; $ok = 0; $nv = 0;
    foreach ($j['controles'] as $x) {
        if (!is_array($x)) continue;
        $statut = in_array($x['statut'] ?? '', ['concordant', 'ecart', 'non_verifiable'], true) ? $x['statut'] : 'non_verifiable';
        $si = preg_match('/^S(\d+)$/', (string)($x['source'] ?? ''), $m) ? (int)$m[1] - 1 : -1; $ext = trim((string)($x['extrait'] ?? '')); $cite = null;
        if ($statut !== 'non_verifiable') {
            $cite = $si >= 0 && isset($src[$si]) && $ext !== '' && mb_strpos(baremeIaNorm($src[$si]['full']), baremeIaNorm(trim($ext, " \"«»“”"))) !== false;
            if (!$cite) $statut = $statut === 'ecart' ? 'ecart_non_prouve' : 'non_verifiable';   // citation introuvable : l'avis n'est pas retenu
        }
        if ($statut === 'ecart') $ecarts++; elseif ($statut === 'concordant') $ok++; else $nv++;
        $ctl[] = ['rubrique' => mb_substr((string)($x['rubrique'] ?? ''), 0, 150), 'valeur_saisie' => mb_substr((string)($x['valeur_saisie'] ?? ''), 0, 150), 'statut' => $statut,
            'valeur_source' => mb_substr((string)($x['valeur_source'] ?? ''), 0, 150), 'source' => $si >= 0 && isset($src[$si]) ? $si + 1 : null, 'extrait' => mb_substr($ext, 0, 250), 'commentaire' => mb_substr((string)($x['commentaire'] ?? ''), 0, 300), 'cite' => $cite];
    }
    $verdict = $ecarts ? 'ecarts' : ($ok && $ok >= $nv ? 'concordant' : 'insuffisant');
    $rap = ['cle' => $cle, 'date' => date('Y-m-d H:i:s'), 'verdict' => $verdict, 'ecarts' => $ecarts, 'concordants' => $ok, 'non_verifiables' => $nv, 'resume' => mb_substr((string)($j['resume'] ?? ''), 0, 600),
        'controles' => $ctl, 'sources' => array_map(fn($s) => ['lib' => $s['lib'], 'url' => $s['url'], 'sp' => $s['sp']], $src), 'notes' => $notes, 'modele' => assistModel()];
    baremeIaEnsureSchema();
    getDB()->prepare("REPLACE INTO bareme_ia (cle, rapport, verdict, nb_ecarts, created_at, created_by) VALUES (?, ?, ?, ?, NOW(), ?)")->execute([$cle, json_encode($rap, JSON_UNESCAPED_UNICODE), $verdict, $ecarts, $userId]);
    return $rap;
}

function baremeIaLast($cle) {
    try { baremeIaEnsureSchema(); $st = getDB()->prepare("SELECT rapport FROM bareme_ia WHERE cle = ?"); $st->execute([$cle]); $r = $st->fetchColumn(); return $r ? json_decode($r, true) : null; } catch (Throwable $e) { return null; }
}

function baremeIaRender(array $r) {
    if (!empty($r['interne'])) return '<div class="alert alert-secondary py-2 mb-0"><i class="fas fa-building"></i> ' . e($r['erreur']) . '</div>';
    if (isset($r['erreur'])) return '<div class="alert alert-danger py-2 mb-0"><i class="fas fa-circle-xmark"></i> ' . e($r['erreur']) . '</div>';
    $vb = ['concordant' => ['success', 'Aucun écart relevé'], 'ecarts' => ['danger', $r['ecarts'] . ' écart(s) relevé(s)'], 'insuffisant' => ['warning text-dark', 'Sources insuffisantes pour conclure']][$r['verdict']] ?? ['secondary', '?'];
    $st = ['concordant' => ['success', 'Concordant'], 'ecart' => ['danger', 'Écart'], 'ecart_non_prouve' => ['warning text-dark', 'Écart non prouvé'], 'non_verifiable' => ['secondary', 'Non vérifiable']];
    $h = '<div class="border rounded p-3 bg-white"><div class="d-flex flex-wrap justify-content-between align-items-center mb-2"><span class="badge bg-' . $vb[0] . ' fs-6">' . e($vb[1]) . '</span>'
        . '<span class="small text-muted">Avis de l\'IA du ' . e(date('d/m/Y H:i', strtotime($r['date']))) . ' · ' . (int)$r['concordants'] . ' concordant(s), ' . (int)$r['non_verifiables'] . ' non vérifiable(s)</span></div>';
    if ($r['resume'] !== '') $h .= '<p class="mb-2">' . e($r['resume']) . '</p>';
    $h .= '<div class="table-responsive"><table class="table table-sm align-middle mb-2" style="font-size:13px"><thead><tr><th>Rubrique</th><th>Saisi</th><th>Dans la source</th><th>Avis</th><th>Preuve</th></tr></thead><tbody>';
    $ord = ['ecart' => 0, 'ecart_non_prouve' => 1, 'non_verifiable' => 3, 'concordant' => 2];
    usort($r['controles'], fn($a, $b) => $ord[$a['statut']] <=> $ord[$b['statut']]);
    foreach ($r['controles'] as $x) {
        $b = $st[$x['statut']];
        $h .= '<tr><td>' . e($x['rubrique']) . '</td><td>' . e($x['valeur_saisie']) . '</td><td>' . e($x['valeur_source']) . '</td><td><span class="badge bg-' . $b[0] . '">' . $b[1] . '</span></td><td class="small text-muted">'
            . ($x['commentaire'] !== '' ? e($x['commentaire']) . '<br>' : '') . ($x['extrait'] !== '' ? '« ' . e($x['extrait']) . ' » ' . ($x['source'] ? '(source ' . (int)$x['source'] . ($x['cite'] ? ', citation retrouvée' : ', <span class="text-danger">citation introuvable</span>') . ')' : '') : '') . '</td></tr>';
    }
    $h .= '</tbody></table></div><div class="small"><strong>Sources consultées :</strong><ol class="mb-1">';
    foreach ($r['sources'] as $s) $h .= '<li><a href="' . e($s['url']) . '" target="_blank" rel="noopener noreferrer">' . e($s['lib']) . '</a>' . ($s['sp'] ? ' <span class="badge bg-primary">prioritaire</span>' : '') . '</li>';
    $h .= '</ol>' . ($r['notes'] ? '<div class="text-muted">Non consultées : ' . e(implode(' ; ', $r['notes'])) . '</div>' : '') . '</div>'
        . '<div class="small text-muted mt-2"><i class="fas fa-circle-info"></i> Avis automatique fondé uniquement sur les extraits ci-dessus : il ne remplace pas votre contrôle. Seul le bouton « J\'ai contrôlé ces valeurs » valide le barème.</div></div>';
    return $h;
}
