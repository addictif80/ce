<?php
/**
 * Habillage du fichier autonome : retire toute référence à la marque du portail (logos, noms, mentions légales, couleurs)
 * pour adopter la charte ABhD (indigo #24285b, jaune #ffd200, police Raleway).
 */
const SA_INDIGO = '#24285b';

/** Remplacements appliqués aux pages d'outils et au script commun (impression comprise). */
function sa_rebrand($s) {
    // les mentions d'indépendance (<!--MENTION-->…<!--/MENTION-->) citent volontairement la marque : elles ne sont jamais réécrites
    $keep = [];
    $s = preg_replace_callback('~<!--MENTION-->.*?<!--/MENTION-->~s', function ($m) use (&$keep) { $k = '@@MENTION' . count($keep) . '@@'; $keep[$k] = $m[0]; return $k; }, $s);
    $keep['@@MENTIONPRINT@@'] = 'Document généré localement par un outil indépendant, <!--MENTION-->non édité par la Caisse d’Épargne ni par BPCE<!--/MENTION-->. Rien n’est enregistré ni transmis.';
    return strtr(sa_rebrand_core($s), $keep);
}
function sa_rebrand_core($s) {
    // logos
    $s = preg_replace('~<img\b[^>]*(?:caisse-epargne|cloudimg|ce-logo)[^>]*>~i', '', $s);
    $s = str_replace('<img src="\'+LOGO+\'" alt="Caisse d\\\'Épargne" onerror="this.style.display=\\\'none\\\'">', '<span></span>', $s);
    // adresses de logos restantes (gabarits d'impression des scripts) : image transparente de 1 pixel
    $s = preg_replace('~https?://[^\s\'"\\)]*(?:caisse-epargne|cloudimg)[^\s\'"\\)]*~i', 'data:image/gif;base64,R0lGODlhAQABAAAAACH5BAEKAAEALAAAAAABAAEAAAICTAEAOw==', $s);
    // textes
    $s = str_replace("nom: 'Caisse d\\'Épargne'", "nom: 'Classique'", $s);
    $apo = "(?:'|’|&#0?39;|\\\\')";
    $s = preg_replace('~la Caisse d' . $apo . '(?:É|E|&Eacute;)pargne de Midi-Pyr(?:é|&eacute;)n(?:é|&eacute;)es~u', 'l\'établissement', $s);
    $s = preg_replace('~par la Caisse d' . $apo . '(?:É|E|&Eacute;)pargne~u', 'par l\'établissement', $s);
    $s = preg_replace('~Caisse d' . $apo . '(?:É|E|&Eacute;)pargne et de Prévoyance de Midi-Pyrénées — document généré par un outil d.{1,2}aide, qui ne se substitue pas aux outils internes du groupe BPCE\.~u', '@@MENTIONPRINT@@', $s);
    $s = preg_replace('~Caisse d' . $apo . '(?:É|E|&Eacute;)pargne~u', 'établissement', $s);
    $s = preg_replace('~du groupe BPCE~u', 'de votre établissement', $s);
    $s = preg_replace('~<div[^>]*>\(Intra CEMP\)</div>~', '', $s);
    $s = preg_replace('~\s+CEMP\b~', '', $s);
    $s = str_replace(' - Portail Gestion d&#039;Activités', ' - Outils conseiller', $s);
    // couleurs de la marque -> indigo ABhD
    $s = preg_replace('~#(?:e4002b|c40025|c4002b|d4002b)\b~i', SA_INDIGO, $s);
    $s = preg_replace('~rgba\(\s*(?:196\s*,\s*0\s*,\s*37|228\s*,\s*0\s*,\s*43)\s*,~', 'rgba(36,40,91,', $s);
    $s = str_replace('#ff6b57', '#ffd200', $s);
    return $s;
}

/**
 * Contrôle : références à la marque du portail encore présentes. On regarde le texte visible des pages (hors scripts et styles)
 * et les adresses de ressources de la marque ; les adresses e-mail du gabarit d'origine (liste des collaborateurs du courrier) sont tolérées.
 */
function sa_scan($html, $strict = false) {
    $out = [];
    $html = preg_replace('~<!--MENTION-->.*?<!--/MENTION-->~s', ' ', $html);
    $visible = preg_replace('~<(script|style)\b.*?</\1>~is', ' ', $html);
    $visible = preg_replace('~[\w.+-]+@[\w.-]*caisse-epargne\.fr~i', '', $visible);
    foreach (['caisse d.{0,8}pargne' => 'Caisse d\'Épargne (texte)', 'bpce' => 'BPCE', '\bcemp\b' => 'CEMP (texte)', 'midi.pyr' => 'Midi-Pyrénées', 'ce-logo' => 'logo CE'] as $re => $lab)
        if ($n = preg_match_all('~' . $re . '~iu', $visible)) $out[$lab] = $n;
    $all = preg_replace('~[\w.+-]+@[\w.-]*caisse-epargne\.fr~i', '', $html);
    foreach (['cloudimg|img\.caisse-epargne|caisse-epargne\.fr' => 'adresse de ressource de la marque', '#e4002b|#c40025' => 'rouge de la marque'] as $re => $lab)
        if ($n = preg_match_all('~' . $re . '~iu', $all)) $out[$lab] = $n;
    if ($strict) { $plain = preg_replace('~[\w.+-]+@[\w.-]*caisse-epargne\.fr~i', '', $html); foreach (['caisse d.{0,8}pargne' => 'Caisse d\'Épargne (scripts)', 'midi.pyr' => 'Midi-Pyrénées (scripts)', 'bpce' => 'BPCE (scripts)'] as $re => $lab) if ($n = preg_match_all('~' . $re . '~iu', $plain)) $out[$lab] = $n; }
    return $out;
}
