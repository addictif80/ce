<?php
/**
 * Assemblage du fichier autonome (inclus par scripts/build_standalone.php).
 * Variables reçues : $tools, $report, $groups, $catalog, $exclus, $natifs, $out, $feedbackTo, $bsCss, $bsJs, $faCss, $pdfLib, $portalCss, $common, $cache
 */
require_once __DIR__ . '/rebrand.php';
require_once __DIR__ . '/config.php';
$keys = array_keys($tools);

// ---------- 1. Traitement de chaque page d'outil ----------
function sa_process($html, $key, array $toolKeys, $portalCss, $common) {
    // bibliothèques partagées : insérées une seule fois dans le fichier, injectées à l'ouverture de l'outil
    $html = preg_replace('~<link href="[^"]*bootstrap[^"]*\.css"[^>]*>~', '<style data-shared="bootstrap"></style>', $html);
    $html = preg_replace('~<link href="[^"]*font-awesome[^"]*\.css"[^>]*>~', '<style data-shared="fa"></style>', $html);
    $html = preg_replace('~<script src="[^"]*bootstrap\.bundle[^"]*"[^>]*></script>~', '<script data-shared="bootstrap-js"></script>', $html);
    $html = preg_replace('~<script src="[^"]*pdf-lib[^"]*"[^>]*></script>~', '<script data-shared="pdflib"></script>', $html);
    $html = preg_replace('~<link href="\.\./assets/css/style\.css"[^>]*>~', '<style data-shared="portalcss"></style>', $html);
    $html = preg_replace('~<link href="[^"]*leaflet[^"]*\.css"[^>]*>~', '<style data-shared="leaflet-css"></style>', $html);
    $html = preg_replace('~<script src="[^"]*leaflet[^"]*"[^>]*></script>~', '<script data-shared="leaflet"></script>', $html);
    $html = preg_replace('~<link href="\.\./\.\./assets/css/style\.css"[^>]*>~', '<style data-shared="portalcss"></style>', $html);
    $html = preg_replace('~href="(?:\.\./\.\./tools/|\./)"~', 'href="#" data-sa="home"', $html);
    // statistiques et journal : sans objet hors serveur
    $html = preg_replace('~<script>window\.toolsStatHit=.*?</script>~s', '<script>window.toolsStatHit=function(){};</script>', $html);
    // script commun des outils (identique d'un outil à l'autre) : mis en commun
    $html = preg_replace_callback('~<script>(// Boutons communs des outils.*?)</script>~s', fn($m) => trim($m[1]) === $common ? '<script data-shared="common"></script>' : $m[0], $html, 1);
    // liens vers d'autres outils -> ouverture dans le fichier
    $html = preg_replace_callback('~href="(?:\./|\.\./tools/)?([a-z_]+)\.php(?:\?[^"]*)?"~', fn($m) => in_array($m[1], $toolKeys, true) ? 'href="#" data-sa="open" data-tool="' . $m[1] . '"' : $m[0], $html);
    // habillage : retrait de la marque du portail, charte ABhD (feuille appliquée en dernier)
    $html = sa_rebrand($html);
    $html = preg_replace('~</head>~', '<style data-shared="theme"></style></head>', $html, 1);
    // bureau domiciliaire : liste des agences prise dans la zone de configuration du fichier (la liste de la génération sert de repli)
    $html = preg_replace('~const agencies = (\[.*?\]);(?= // « NOM)~s', 'const agencies = (window.TOOLS_SA && window.TOOLS_SA.agencies && window.TOOLS_SA.agencies.length) ? window.TOOLS_SA.agencies : $1;', $html);
    // point d'accroche de l'amorce (injectée à l'ouverture : outil courant, partage, routes de remplacement du serveur)
    $html = preg_replace('~<head>~', "<head>\n<!--SA-BOOT-->", $html, 1);
    return $html;
}
foreach ($tools as $k => &$t) if (isset($t['html'])) $t['html'] = sa_process($t['html'], $k, $keys, $portalCss, $common);
unset($t);

// ---------- 2. Ressources partagées ----------
$common = sa_rebrand($common);
$portalCss = sa_rebrand($portalCss);
$shared = ['bootstrap' => $bsCss, 'bootstrap-js' => $bsJs, 'common' => $common, 'pdflib' => $pdfLib, 'portalcss' => $portalCss, 'theme' => file_get_contents(__DIR__ . '/theme.css'), 'leaflet' => $leafletJs, 'leaflet-css' => $leafletCss, 'zonage' => $zonage ?: '', 'shim' => file_get_contents(__DIR__ . '/shim.js')];
foreach ($shared as $n => $c) if (stripos($c, '</script') !== false && $n !== 'shim' && $n !== 'zonage') { say("Ressource partagée « $n » contient </script : à traiter"); exit(1); }

// ---------- 3. Gabarit de base (page fournie : accueil, budget, courrier) ----------
$base = file_get_contents(__DIR__ . '/base_outils.html');
$date = date('d/m/Y');
$esc = fn($s) => htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8');
$groupIcons = ['Financer un projet' => '#1f3a5f', 'Épargne et patrimoine' => '#2e7d5b', 'Conseil et conformité' => '#6F2C91', 'Boîte à outils' => '#a34a3d', 'Références' => '#2f6aa3'];

// logos et polices de la charte ABhD (fichiers du dossier standalone/assets, embarqués en base64)
$asset = fn($f) => file_get_contents(__DIR__ . '/assets/' . $f);
$logo = 'data:image/png;base64,' . base64_encode($asset('abhd-logo.png'));
$icon = 'data:image/png;base64,' . base64_encode($asset('abhd-A.png'));
$fontCss = '@font-face{font-family:Raleway;font-style:normal;font-weight:400 800;font-display:swap;src:url(data:font/woff2;base64,' . base64_encode($asset('raleway-latin.woff2')) . ') format("woff2")}'
         . '@font-face{font-family:Raleway;font-style:italic;font-weight:400;font-display:swap;src:url(data:font/woff2;base64,' . base64_encode($asset('raleway-italic.woff2')) . ') format("woff2")}';
// accueil
$home = '<div id="accueil">
  <nav class="ab-nav"><img src="' . $logo . '" alt="ABhD"><span>Outils conseiller</span></nav>
  <header class="ab-hero">
    <div>
      <h1>Tous vos outils, <span class="underline">au même endroit</span>.</h1>
      <p>Simulateurs, calculateurs, mémos et documents : de quoi préparer un entretien sans rien installer.</p>
      <p class="note">Ces outils sont accessibles uniquement depuis le partage OneDrive aux personnes concernées. Aucune donnée ne peut être enregistrée, et aucune donnée n\'est stockée informatiquement.</p>
      <div id="accueil-recherche"><input type="search" id="rech" placeholder="Rechercher un outil…" aria-label="Rechercher un outil" autocomplete="off"></div>
    </div>
    <div class="art" aria-hidden="true"><div><i class="fas fa-toolbox"></i></div></div>
  </header>
  <div id="accueil-groupes">';
foreach ($groups as $g => $gk) {
    $cards = '';
    foreach ($gk as $k) {
        if (!isset($tools[$k])) continue;
        $t = $tools[$k];
        $cards .= '<button type="button" class="carte-outil" data-open="' . $esc($k) . '" data-txt="' . $esc(mb_strtolower($t['title'] . ' ' . $t['desc'])) . '"><span class="ic"><i class="fas ' . $esc($t['icon']) . '"></i></span><span class="tx"><strong>' . $esc($t['title']) . '</strong><small>' . $esc($t['desc']) . '</small></span><span class="go">Ouvrir <i class="fas fa-arrow-right"></i></span></button>';
    }
    if ($cards) $home .= '<section class="groupe-accueil"><h2>' . $esc($g) . '</h2><div class="grille-outils">' . $cards . '</div></section>';
}
$home .= '</div>
  <p id="accueil-vide" hidden>Aucun outil ne correspond à cette recherche.</p>
  <footer id="accueil-pied"><strong>ABhD</strong> · Reprenez le contrôle<br>
    Fichier généré le ' . $date . '. Les barèmes et plafonds réglementaires sont figés à cette date : vérifiez-les avant toute décision. Outil d\'aide : il ne se substitue pas aux outils internes de votre établissement.<br>
    <button type="button" class="lien-discret" data-sa-act="feedback"><i class="fas fa-comment-dots"></i> Un problème, une idée ? Écrire un retour</button></footer>
</div>';
// barre supérieure
$top = '<div id="topbar" hidden>
  <div class="marque"><img src="' . $logo . '" alt="ABhD"></div>
  <button type="button" class="btn-top" id="top-home"><i class="fas fa-th-large"></i> Tous les outils</button>
  <span id="top-titre"></span>
  <select id="top-select" aria-label="Changer d\'outil"><option value="">Changer d\'outil…</option>';
foreach ($groups as $g => $gk) { $top .= '<optgroup label="' . $esc($g) . '">'; foreach ($gk as $k) if (isset($tools[$k])) $top .= '<option value="' . $esc($k) . '">' . $esc($tools[$k]['title']) . '</option>'; $top .= '</optgroup>'; }
$top .= '</select>
  <button type="button" class="btn-top" id="top-feedback" title="Écrire un retour (fichier e-mail à envoyer)"><i class="fas fa-comment-dots"></i> Un retour ?</button>
</div>';
$frameModule = '<div id="mod-frame" class="module" hidden></div>';
// formulaire de retour : génère un fichier .eml à télécharger puis à envoyer depuis la messagerie
$fbOptions = '<option value="">Général (tous les outils)</option>';
foreach ($groups as $g => $gk) { $fbOptions .= '<optgroup label="' . $esc($g) . '">'; foreach ($gk as $k) if (isset($tools[$k])) $fbOptions .= '<option value="' . $esc($k) . '">' . $esc($tools[$k]['title']) . '</option>'; $fbOptions .= '</optgroup>'; }
$feedback = '<div id="fb-fond" hidden role="dialog" aria-modal="true" aria-labelledby="fb-titre"><div id="fb-boite">
  <h2 id="fb-titre"><i class="fas fa-comment-dots"></i> Écrire un retour</h2>
  <p class="fb-note">Ce formulaire ne transmet rien : il prépare un <strong>fichier e-mail (.eml)</strong> que vous téléchargez, puis ouvrez dans votre messagerie pour l\'envoyer.</p>
  <label for="fb-type">Nature du retour</label>
  <select id="fb-type"><option value="Un problème">Un problème</option><option value="Une suggestion">Une suggestion</option><option value="Une question">Une question</option><option value="Autre">Autre</option></select>
  <label for="fb-outil">Outil concerné</label>
  <select id="fb-outil">' . $fbOptions . '</select>
  <label for="fb-msg">Votre message <span class="fb-compteur" id="fb-compteur"></span></label>
  <textarea id="fb-msg" rows="6" maxlength="2000" placeholder="Décrivez le problème, l\'idée ou la question (5 à 2000 caractères)"></textarea>
  <label for="fb-contact">Votre nom ou votre adresse e-mail (facultatif)</label>
  <input type="text" id="fb-contact" maxlength="255" autocomplete="off" placeholder="Pour qu\'on puisse vous répondre">
  <div id="fb-erreur" role="alert" hidden></div>
  <div id="fb-actions"><button type="button" id="fb-annuler">Annuler</button><button type="button" id="fb-generer"><i class="fas fa-download"></i> Télécharger le fichier e-mail (.eml)</button></div>
  <p class="fb-note" id="fb-apres" hidden></p>
</div></div>';

// ---------- 4. Feuille de style ajoutée au gabarit ----------
$css = file_get_contents(__DIR__ . '/shell.css');
// ---------- 5. Script de navigation ----------
$data = ['tools' => array_values(array_map(fn($t) => ['key' => $t['key'], 'title' => $t['title'], 'native' => $t['native'] ?? null, 'html' => $t['html'] ?? null], $tools)), 'shared' => $shared, 'date' => $date, 'feedbackTo' => $feedbackTo];
$json = json_encode($data, JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE);
if ($json === false) { say('Échec de l\'encodage JSON : ' . json_last_error_msg()); exit(1); }
$nav = '<!-- ===== NAVIGATION ET OUTILS INTÉGRÉS ===== -->
<script type="application/json" id="sa-data">' . $json . '</script>
<script>' . file_get_contents(__DIR__ . '/shell.js') . '</script>';

// ---------- 6. Montage ----------
$sub = function ($pattern, $replacement, $subject, $what) { $n = 0; $r = preg_replace_callback($pattern, fn() => $replacement, $subject, 1, $n); if ($n !== 1 || $r === null) { say("Gabarit : $what introuvable"); exit(1); } return $r; };
$base = $sub('~<div id="accueil">.*?(?=\s*<!-- =+\s*BARRE SUPERIEURE)~s', $home . "\n", $base, 'bloc accueil');
$base = $sub('~<div id="topbar" hidden>.*?</div>\s*(?=<!-- =+\s*MODULE 1)~s', $top . "\n\n", $base, 'barre supérieure');
$base = $sub('~<!-- ===== NAVIGATION ===== -->\s*<script>.*?</script>~s', $frameModule . "\n" . $feedback . "\n" . $nav, $base, 'script de navigation');
$base = preg_replace_callback('~(<meta name="viewport"[^>]*>)~', fn($m) => $m[1] . "\n" . sa_config_block($tools, $groups, $agences, $feedbackTo), $base, 1); // zone de configuration, tout en haut du fichier
$base = $sub('~</style>~', "\n" . $css . "\n</style>", $base, 'feuille de style');
$base = $sub('~</head>~', '<link rel="icon" type="image/png" href="' . $icon . '">' . "\n" . '<style id="sa-font">' . $fontCss . '</style>' . "\n" . '<style id="sa-fa">' . $faCss . "</style>\n</head>", $base, 'en-tête'); // icônes : une seule copie, réutilisée par les outils intégrés

$left = [];
foreach ($tools as $k => $t) if (isset($t['html']) && ($r = sa_scan($t['html']))) $left[$k] = $r;
// le générateur de courrier est repris tel quel du gabarit d'origine (en-tête, pied de page, liste des collaborateurs) : exclu du contrôle
$page = preg_replace('~<script type="application/json" id="sa-data">.*?</script>~s', '', $base);
$page = preg_replace('~<div id="mod-courrier".*?(?=<!-- =+\s*SCRIPTS)~s', '', $page);
$page = str_replace($feedbackTo, '', $page);
if ($r = sa_scan($page, true)) $left['(page)'] = $r;
if ($left) say('Références restantes à la marque du portail : ' . json_encode($left, JSON_UNESCAPED_UNICODE));
if (!is_dir(dirname($out))) mkdir(dirname($out), 0775, true);
file_put_contents($out, $base);
say('');
foreach ($tools as $k => $t) say(str_pad($k, 16) . (isset($t['html']) ? str_pad((string)round(strlen($t['html']) / 1024), 6, ' ', STR_PAD_LEFT) . ' Ko' : '  natif'));
foreach ($report as $k => $r) say("!! $k : $r");
say(sprintf("\n%s : %.1f Mo, %d outils intégrés", $out, filesize($out) / 1048576, count($tools)));
