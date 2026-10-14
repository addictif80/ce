<?php
/**
 * Fiche client 360° : agrégation de toutes les informations connues d'un client
 * à partir des différents modules du portail.
 *
 * Le portail n'utilise pas une clé client unique : selon les modules, le client
 * est identifié par un numéro de personne, un nom/prénom, ou une « identité »
 * texte libre. L'agrégateur gère ces différentes familles de colonnes :
 *   - numero_personne
 *   - nom_client / prenom_client
 *   - identite_client
 *   - nom_prenom_dest (+ nom_dest / prenom_dest)
 *   - client_nom
 *
 * Cas particulier — crédit immobilier : dans ce module, `numero_personne` est en
 * réalité le **numéro de dossier** (« N° dossier »). L'identité du client se
 * trouve dans `emprunteurs_json` (num_personne + nom de chaque emprunteur). Le
 * rattachement à la fiche passe donc par ce JSON, pas par `numero_personne`.
 *
 * Toutes les lectures sont limitées à ce que le conseiller connecté a saisi
 * (user_id), pour respecter la confidentialité.
 */
require_once __DIR__ . '/functions.php';

/**
 * Normalise un nom pour comparaison : minuscules, sans accents, sans civilité
 * (« MME », « M. »…), mots triés. Permet de rapprocher « MME DUPONT Marie » et
 * « DUPONT Marie ». Retourne '' si rien d'exploitable.
 */
function ficheNormNom($nom) {
    $n = mb_strtolower(trim((string)$nom), 'UTF-8');
    $n = strtr($n, [
        'à'=>'a','á'=>'a','â'=>'a','ä'=>'a','ã'=>'a','å'=>'a','ç'=>'c',
        'è'=>'e','é'=>'e','ê'=>'e','ë'=>'e','ì'=>'i','í'=>'i','î'=>'i','ï'=>'i',
        'ò'=>'o','ó'=>'o','ô'=>'o','ö'=>'o','õ'=>'o','ù'=>'u','ú'=>'u','û'=>'u','ü'=>'u',
        'ý'=>'y','ÿ'=>'y','ñ'=>'n','œ'=>'oe','æ'=>'ae',
    ]);
    $toks = preg_split('/[^a-z0-9]+/', $n, -1, PREG_SPLIT_NO_EMPTY) ?: [];
    $civ = ['m','mr','mme','mlle','monsieur','madame','mademoiselle','me','mmes','mrs','ms'];
    $toks = array_values(array_filter($toks, fn($t) => !in_array($t, $civ, true)));
    $toks = array_values(array_unique($toks));
    sort($toks);
    return implode(' ', $toks);
}

/**
 * Tokens significatifs (longueur >= 3) d'un nom, hors civilité — utilisés pour
 * rechercher le client dans `credit_immobilier.emprunteurs_json` même quand
 * l'ordre des mots diffère.
 */
function ficheNomTokens($nom) {
    $sig = ficheNormNom($nom);
    if ($sig === '') return [];
    return array_values(array_filter(explode(' ', $sig), fn($t) => mb_strlen($t) >= 3));
}

function ensureFicheClientSchema() {
    $db = getDB();
    $db->exec("CREATE TABLE IF NOT EXISTS fiche_client_notes (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        numero_personne VARCHAR(100) NOT NULL,
        message TEXT NOT NULL,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_fcn (user_id, numero_personne)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    $db->exec("CREATE TABLE IF NOT EXISTS fiche_client_actions (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        numero_personne VARCHAR(100) NOT NULL,
        action VARCHAR(255) NOT NULL,
        produit VARCHAR(255) DEFAULT '',
        source VARCHAR(10) DEFAULT 'regle',
        priorite VARCHAR(10) DEFAULT 'normale',
        statut VARCHAR(10) DEFAULT 'a_faire',
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_fca (user_id, numero_personne)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
}

/**
 * Recherche des clients dans tous les modules.
 * Retourne une liste de ['numero' => ..., 'nom' => ..., 'sources' => [..]].
 */
function searchClients($db, $q, $userId) {
    $q = trim((string)$q);
    if ($q === '') return [];
    $like = '%' . $q . '%';
    $clients = [];

    $add = function (&$clients, $numero, $nom, $source) {
        $numero = trim((string)$numero);
        $nom = trim((string)$nom);
        if ($numero === '' && $nom === '') return;
        // Clé de regroupement : le n° de personne prime ; sinon le nom normalisé
        // (sans civilité type « MME », sans accents, mots triés) pour qu'un même
        // client n'apparaisse pas deux fois selon l'orthographe saisie.
        if ($numero !== '') {
            $key = 'N:' . $numero;
        } else {
            $sig = ficheNormNom($nom);
            $key = 'M:' . ($sig !== '' ? $sig : mb_strtolower($nom));
        }
        if (!isset($clients[$key])) $clients[$key] = ['numero' => $numero, 'nom' => $nom, 'sources' => []];
        if ($nom !== '' && $clients[$key]['nom'] === '') $clients[$key]['nom'] = $nom;
        if (!in_array($source, $clients[$key]['sources'], true)) $clients[$key]['sources'][] = $source;
    };

    $run = function ($sql, $params, $source, $numCol, $nomCol = null) use ($db, &$add, &$clients) {
        try {
            $stmt = $db->prepare($sql);
            $stmt->execute($params);
            foreach ($stmt->fetchAll() as $r) {
                $numero = ($numCol !== null && isset($r[$numCol])) ? $r[$numCol] : '';
                $nom = ($nomCol !== null && isset($r[$nomCol])) ? $r[$nomCol] : '';
                $add($clients, $numero, $nom, $source);
            }
        } catch (Exception $e) {}
    };

    $run("SELECT numero_personne FROM instances WHERE user_id = ? AND (numero_personne LIKE ? OR details LIKE ?) LIMIT 30",
         [$userId, $like, $like], 'instances', 'numero_personne');
    $run("SELECT numero_personne FROM demandes_rappel WHERE user_id = ? AND (numero_personne LIKE ? OR motif LIKE ?) LIMIT 30",
         [$userId, $like, $like], 'rappels', 'numero_personne');
    $run("SELECT numero_personne FROM demandes_clients WHERE user_id = ? AND (numero_personne LIKE ? OR details_demande LIKE ?) LIMIT 30",
         [$userId, $like, $like], 'demandes_clients', 'numero_personne');
    $run("SELECT numero_personne, nom_client FROM dossiers_signature WHERE user_id = ? AND (numero_personne LIKE ? OR nom_client LIKE ?) LIMIT 30",
         [$userId, $like, $like], 'signatures', 'numero_personne', 'nom_client');
    $run("SELECT numero_personne, nom_client FROM mobilites WHERE user_id = ? AND (numero_personne LIKE ? OR nom_client LIKE ?) LIMIT 30",
         [$userId, $like, $like], 'mobilites', 'numero_personne', 'nom_client');
    // Crédit immobilier : « numero_personne » y est un n° de dossier ; l'identité du client
    // se trouve dans emprunteurs_json (num_personne + nom de chaque emprunteur). On ajoute
    // donc un « client » par emprunteur, ce qui rapproche aussi ce dossier des autres modules.
    try {
        $stmt = $db->prepare("SELECT numero_personne, emprunteurs_json FROM credit_immobilier WHERE user_id = ? AND (numero_personne LIKE ? OR emprunteurs_json LIKE ? OR adresse_bien LIKE ?) LIMIT 30");
        $stmt->execute([$userId, $like, $like, $like]);
        foreach ($stmt->fetchAll() as $r) {
            $dnum = trim((string)($r['numero_personne'] ?? ''));
            $empsJson = (string)($r['emprunteurs_json'] ?? '');
            $dossierHit = (stripos($dnum, $q) !== false) || (stripos($empsJson, $q) !== false);
            $emps = json_decode($empsJson, true);
            $ok = false;
            if (is_array($emps)) {
                foreach ($emps as $em) {
                    $num = trim((string)($em['num_personne'] ?? ''));
                    $nm  = trim((string)($em['nom'] ?? ''));
                    if ($num === '' && $nm === '') continue;
                    // On ne remonte un emprunteur que s'il correspond à la recherche, ou si le
                    // dossier lui-même a été trouvé (n° de dossier / emprunteurs). Évite de
                    // remonter un client sans rapport quand la recherche portait sur l'adresse.
                    $hit = $dossierHit || ($nm !== '' && stripos($nm, $q) !== false) || ($num !== '' && stripos($num, $q) !== false);
                    if ($hit) { $add($clients, $num, $nm, 'credit_immo'); $ok = true; }
                }
            }
            if (!$ok && $dnum !== '' && stripos($dnum, $q) !== false) $add($clients, $dnum, '', 'credit_immo');
        }
    } catch (Exception $e) {}
    $run("SELECT numero_personne, CONCAT(prenom_client, ' ', nom_client) AS nom_complet FROM envois_documents WHERE user_id = ? AND (numero_personne LIKE ? OR nom_client LIKE ? OR prenom_client LIKE ?) LIMIT 30",
         [$userId, $like, $like, $like], 'envois', 'numero_personne', 'nom_complet');
    $run("SELECT numero_personne FROM appels_phoning WHERE user_id = ? AND numero_personne LIKE ? LIMIT 30",
         [$userId, $like], 'phoning', 'numero_personne');
    $run("SELECT identite_client FROM demandes_rappel_client WHERE user_id = ? AND identite_client LIKE ? LIMIT 30",
         [$userId, $like], 'rappels_clients', null, 'identite_client');
    $run("SELECT l.numero_personne, l.identite_client FROM demandes_portefeuille_lignes l JOIN demandes_portefeuille d ON d.id = l.demande_id WHERE (d.user_id = ? OR d.conseiller_id = ?) AND (l.numero_personne LIKE ? OR l.identite_client LIKE ?) LIMIT 30",
         [$userId, $userId, $like, $like], 'portefeuille', 'numero_personne', 'identite_client');
    $run("SELECT client_nom FROM interets_clients WHERE user_id = ? AND client_nom LIKE ? LIMIT 30",
         [$userId, $like], 'interets', null, 'client_nom');
    $run("SELECT nom_prenom_dest FROM courriers WHERE user_id = ? AND nom_prenom_dest LIKE ? LIMIT 30",
         [$userId, $like], 'courriers', null, 'nom_prenom_dest');
    $run("SELECT nom_prenom_dest FROM courriers_internes WHERE user_id = ? AND nom_prenom_dest LIKE ? LIMIT 30",
         [$userId, $like], 'courriers_internes', null, 'nom_prenom_dest');

    // Fusion finale : une entrée « nom seul » rejoint l'entrée portant le MÊME nom et un
    // numéro de personne (le crédit immobilier fournit ce lien via ses emprunteurs). Cela
    // corrige les doublons du type « MME DUPONT » (courrier) vs « DUPONT » (phoning).
    $sigToKey = [];
    foreach ($clients as $k => $c) {
        if ($c['numero'] !== '' && $c['nom'] !== '') {
            $s = ficheNormNom($c['nom']);
            if ($s !== '' && !isset($sigToKey[$s])) $sigToKey[$s] = $k;
        }
    }
    foreach ($clients as $k => $c) {
        if ($c['numero'] !== '' || $c['nom'] === '') continue;
        $s = ficheNormNom($c['nom']);
        if ($s === '' || !isset($sigToKey[$s])) continue;
        $target = $sigToKey[$s];
        if ($target === $k || !isset($clients[$target])) continue;
        foreach ($c['sources'] as $src) {
            if (!in_array($src, $clients[$target]['sources'], true)) $clients[$target]['sources'][] = $src;
        }
        unset($clients[$k]);
    }

    // Tri : d'abord les entrées portant un numéro, puis par nom
    $out = array_values($clients);
    usort($out, function ($a, $b) {
        if (($a['numero'] !== '') !== ($b['numero'] !== '')) return $a['numero'] !== '' ? -1 : 1;
        return strcmp($a['nom'], $b['nom']);
    });
    return array_slice($out, 0, 60);
}

/**
 * Construit la fiche complète d'un client.
 * $numero : numéro de personne (peut être ''), $nom : nom/identité (peut être '').
 */
function getFicheClient($db, $numero, $nom, $userId) {
    ensureFicheClientSchema();
    $numero = trim((string)$numero);
    $nom = trim((string)$nom);
    $numeroOk = ($numero !== '');
    $nomOk = ($nom !== '');
    $likeNom = $nomOk ? '%' . $nom . '%' : null;

    $fiche = [
        'numero' => $numero, 'nom' => $nom,
        'instances' => [], 'rappels' => [], 'demandes' => [], 'signatures' => [],
        'mobilites' => [], 'credit_immo' => [], 'envois' => [], 'phoning' => [],
        'rappels_clients' => [], 'portefeuille' => [], 'interets' => [],
        'courriers' => [], 'notes' => [], 'actions' => [],
        'stats' => [],
    ];

    $fetch = function ($sql, $params, $key) use ($db, &$fiche) {
        try {
            $stmt = $db->prepare($sql);
            $stmt->execute($params);
            $fiche[$key] = $stmt->fetchAll();
        } catch (Exception $e) { $fiche[$key] = []; }
    };

    // Fabrique une clause « numéro et/ou nom » selon les identifiants disponibles.
    // Retourne [clause, params] (params commence par le user_id) ou null si rien de fiable.
    $buildWhere = function (array $numeroCols, array $nomCols) use ($numero, $numeroOk, $nomOk, $likeNom, $userId) {
        $conds = []; $params = [$userId];
        if ($numeroOk) {
            foreach ($numeroCols as $c) { $conds[] = $c . ' = ?'; $params[] = $numero; }
        }
        if ($nomOk) {
            foreach ($nomCols as $c) { $conds[] = $c . ' LIKE ?'; $params[] = $likeNom; }
        }
        if (!$conds) return null;
        return ['(' . implode(' OR ', $conds) . ')', $params];
    };

    // Instances, rappels et demandes clients : identifiés par le numéro de personne
    if ($w = $buildWhere(['numero_personne'], [])) $fetch("SELECT * FROM instances WHERE user_id = ? AND {$w[0]} ORDER BY date_ajout DESC LIMIT 50", $w[1], 'instances');
    if ($w = $buildWhere(['numero_personne'], [])) $fetch("SELECT * FROM demandes_rappel WHERE user_id = ? AND {$w[0]} ORDER BY date_ajout DESC LIMIT 50", $w[1], 'rappels');
    if ($w = $buildWhere(['numero_personne'], [])) $fetch("SELECT * FROM demandes_clients WHERE user_id = ? AND {$w[0]} ORDER BY date_ajout DESC LIMIT 50", $w[1], 'demandes');
    // Signatures et mobilités : numéro ou nom
    if ($w = $buildWhere(['numero_personne'], ['nom_client'])) $fetch("SELECT * FROM dossiers_signature WHERE user_id = ? AND {$w[0]} ORDER BY date_envoi DESC LIMIT 50", $w[1], 'signatures');
    if ($w = $buildWhere(['numero_personne'], ['nom_client'])) $fetch("SELECT * FROM mobilites WHERE user_id = ? AND {$w[0]} ORDER BY date_ajout DESC LIMIT 50", $w[1], 'mobilites');
    // Crédit immobilier : dans ce module, `numero_personne` est un n° de dossier ; le client
    // est identifié par les emprunteurs (num_personne / nom dans emprunteurs_json). On
    // rattache donc par n° de dossier OU par identité d'emprunteur (n° de personne ou nom).
    $ciConds = []; $ciParams = [$userId];
    if ($numeroOk) {
        $ciConds[] = 'numero_personne = ?';        $ciParams[] = $numero;
        $ciConds[] = 'emprunteurs_json LIKE ?';    $ciParams[] = '%' . $numero . '%';
    }
    if ($nomOk) {
        $toks = ficheNomTokens($nom);
        if (!$toks) { $s = ficheNormNom($nom); if ($s !== '') $toks = [$s]; }
        if ($toks) {
            $sub = [];
            foreach ($toks as $t) { $sub[] = 'emprunteurs_json LIKE ?'; $ciParams[] = '%' . $t . '%'; }
            $ciConds[] = '(' . implode(' AND ', $sub) . ')'; // tous les mots du nom doivent être présents
        }
    }
    if ($ciConds) $fetch("SELECT * FROM credit_immobilier WHERE user_id = ? AND (" . implode(' OR ', $ciConds) . ") ORDER BY date_ajout DESC LIMIT 50", $ciParams, 'credit_immo');
    // Envois de documents : numéro ou nom
    if ($w = $buildWhere(['numero_personne'], ['nom_client'])) $fetch("SELECT * FROM envois_documents WHERE user_id = ? AND {$w[0]} ORDER BY date_envoi DESC LIMIT 50", $w[1], 'envois');
    // Phoning : numéro de personne
    if ($w = $buildWhere(['numero_personne'], [])) $fetch("SELECT * FROM appels_phoning WHERE user_id = ? AND {$w[0]} ORDER BY created_at DESC LIMIT 50", $w[1], 'phoning');
    // Rappels clients, intérêts, courriers : identité libre (nom)
    if ($w = $buildWhere([], ['identite_client'])) $fetch("SELECT * FROM demandes_rappel_client WHERE user_id = ? AND {$w[0]} ORDER BY created_at DESC LIMIT 50", $w[1], 'rappels_clients');
    if ($w = $buildWhere([], ['client_nom'])) $fetch("SELECT * FROM interets_clients WHERE user_id = ? AND {$w[0]} ORDER BY created_at DESC LIMIT 50", $w[1], 'interets');
    if ($w = $buildWhere([], ['nom_prenom_dest'])) $fetch("SELECT * FROM courriers WHERE user_id = ? AND {$w[0]} ORDER BY date_courrier DESC LIMIT 50", $w[1], 'courriers');
    if ($w = $buildWhere([], ['nom_prenom_dest'])) $fetch("SELECT * FROM courriers_internes WHERE user_id = ? AND {$w[0]} ORDER BY date_courrier DESC LIMIT 50", $w[1], 'courriers_internes');
    // Les courriers internes sont ajoutés à la liste des courriers (même affichage)
    $fiche['courriers'] = array_merge($fiche['courriers'], $fiche['courriers_internes'] ?? []);

    // Portefeuille : lignes rattachées aux demandes du conseiller
    $pConds = []; $pParams = [$userId, $userId];
    if ($numeroOk) { $pConds[] = 'l.numero_personne = ?'; $pParams[] = $numero; }
    if ($nomOk) { $pConds[] = 'l.identite_client LIKE ?'; $pParams[] = $likeNom; }
    if ($pConds) $fetch("SELECT l.* FROM demandes_portefeuille_lignes l JOIN demandes_portefeuille d ON d.id = l.demande_id WHERE (d.user_id = ? OR d.conseiller_id = ?) AND (" . implode(' OR ', $pConds) . ") ORDER BY l.created_at DESC LIMIT 50", $pParams, 'portefeuille');

    // Notes et actions internes à la fiche
    try {
        $cle = $numero !== '' ? $numero : $nom;
        $stmt = $db->prepare("SELECT * FROM fiche_client_notes WHERE user_id = ? AND numero_personne = ? ORDER BY created_at DESC");
        $stmt->execute([$userId, $cle]);
        $fiche['notes'] = $stmt->fetchAll();
        $stmt = $db->prepare("SELECT * FROM fiche_client_actions WHERE user_id = ? AND numero_personne = ? ORDER BY created_at DESC");
        $stmt->execute([$userId, $cle]);
        $fiche['actions'] = $stmt->fetchAll();
    } catch (Exception $e) {}

    // Indicateurs de synthèse
    $now = time();
    $instanceRetard = 0;
    foreach ($fiche['instances'] as $i) {
        if (($i['statut'] ?? '') === 'a_faire' && !empty($i['date_echeance']) && strtotime($i['date_echeance']) < $now) $instanceRetard++;
    }
    $demandesOuvertes = 0;
    foreach ($fiche['demandes'] as $d) if ((int)($d['traitee'] ?? 0) === 0) $demandesOuvertes++;
    $rappelsOuverts = 0;
    foreach ($fiche['rappels'] as $r) if ((int)($r['traitee'] ?? 0) === 0) $rappelsOuverts++;
    $signaturesEnCours = 0;
    foreach ($fiche['signatures'] as $s) if (($s['statut'] ?? '') === 'en_cours') $signaturesEnCours++;
    $mobilitesEnCours = 0;
    foreach ($fiche['mobilites'] as $m) if (($m['etape'] ?? '') !== 'termine') $mobilitesEnCours++;
    $creditsASuivre = 0;
    foreach ($fiche['credit_immo'] as $c) if ((int)($c['suivi_offre_signee'] ?? 0) === 0) $creditsASuivre++;

    $lastContact = null;
    foreach ([['phoning', 'created_at'], ['demandes', 'date_ajout'], ['rappels', 'date_ajout'], ['courriers', 'date_courrier'], ['envois', 'date_envoi']] as $src) {
        foreach ($fiche[$src[0]] as $row) {
            $d = $row[$src[1]] ?? null;
            if ($d && ($lastContact === null || strtotime($d) > strtotime($lastContact))) $lastContact = $d;
        }
    }

    $fiche['stats'] = [
        'instance_retard' => $instanceRetard,
        'demandes_ouvertes' => $demandesOuvertes,
        'rappels_ouverts' => $rappelsOuverts,
        'signatures_en_cours' => $signaturesEnCours,
        'mobilites_en_cours' => $mobilitesEnCours,
        'credits_a_suivre' => $creditsASuivre,
        'nb_interets' => count($fiche['interets']),
        'dernier_contact' => $lastContact,
    ];

    return $fiche;
}

/** Libellés courts des sources, pour l'affichage. */
function ficheSourceLabels() {
    return [
        'instances' => 'Instance', 'rappels' => 'Demande de rappel', 'demandes' => 'Demande client',
        'signatures' => 'Signature', 'mobilites' => 'Mobilité', 'credit_immo' => 'Crédit immobilier',
        'envois' => 'Envoi de documents', 'phoning' => 'Appel sortant', 'rappels_clients' => 'Rappel client',
        'portefeuille' => 'Portefeuille', 'interets' => 'Intérêt client', 'courriers' => 'Courrier',
        'demandes_clients' => 'Demande client', 'courriers_internes' => 'Courrier interne',
    ];
}

/** Ajoute une note interne à la fiche. */
function addFicheClientNote($db, $userId, $cle, $message) {
    ensureFicheClientSchema();
    $message = trim((string)$message);
    if ($message === '') return false;
    $stmt = $db->prepare("INSERT INTO fiche_client_notes (user_id, numero_personne, message) VALUES (?, ?, ?)");
    return $stmt->execute([$userId, $cle, $message]);
}

/** Enregistre une action retenue (issue des règles ou de l'IA). */
function addFicheClientAction($db, $userId, $cle, $action, $produit = '', $source = 'regle', $priorite = 'normale') {
    ensureFicheClientSchema();
    $action = trim((string)$action);
    if ($action === '') return false;
    $stmt = $db->prepare("INSERT INTO fiche_client_actions (user_id, numero_personne, action, produit, source, priorite, statut) VALUES (?, ?, ?, ?, ?, ?, 'a_faire')");
    return $stmt->execute([$userId, $cle, $action, $produit, $source, $priorite]);
}

/**
 * Transforme la fiche en contexte texte, pour l'IA et le résumé local.
 * (L'anonymisation éventuelle est faite séparément par assist_dossier.php.)
 */
function ficheToText($fiche) {
    $lines = [];
    $lines[] = 'Identifiant client : ' . ($fiche['numero'] !== '' ? $fiche['numero'] : '(non renseigné)');
    if ($fiche['nom'] !== '') $lines[] = 'Nom : ' . $fiche['nom'];
    $s = $fiche['stats'];
    $lines[] = 'Synthèse : ' . $s['instance_retard'] . ' instance(s) en retard, ' .
               $s['demandes_ouvertes'] . ' demande(s) client ouverte(s), ' .
               $s['rappels_ouverts'] . ' rappel(s) en attente, ' .
               $s['signatures_en_cours'] . ' signature(s) en cours, ' .
               $s['mobilites_en_cours'] . ' mobilité(s) en cours, ' .
               $s['credits_a_suivre'] . ' crédit(s) immobilier à suivre.';
    if (!empty($s['dernier_contact'])) $lines[] = 'Dernier contact connu : ' . $s['dernier_contact'];

    if ($fiche['instances']) {
        $lines[] = '';
        $lines[] = 'Instances (' . count($fiche['instances']) . ') :';
        foreach (array_slice($fiche['instances'], 0, 10) as $i) {
            $lines[] = '- [' . ($i['statut'] ?? '') . '] ' . excerpt((string)($i['details'] ?? ''), 160) .
                       (!empty($i['date_echeance']) ? ' (échéance ' . $i['date_echeance'] . ')' : '');
        }
    }
    if ($fiche['demandes']) {
        $lines[] = '';
        $lines[] = 'Demandes clients (' . count($fiche['demandes']) . ') :';
        foreach (array_slice($fiche['demandes'], 0, 10) as $d) {
            $lines[] = '- ' . (($d['traitee'] ?? 0) ? 'traitée' : 'OUVERTE') . ' : ' . excerpt((string)($d['details_demande'] ?? ''), 160) .
                       ' — service : ' . ($d['service'] ?? '—');
        }
    }
    if ($fiche['rappels']) {
        $lines[] = '';
        $lines[] = 'Demandes de rappel (' . count($fiche['rappels']) . ') :';
        foreach (array_slice($fiche['rappels'], 0, 10) as $r) {
            $lines[] = '- ' . (($r['traitee'] ?? 0) ? 'traitée' : 'EN ATTENTE') . ' : ' . excerpt((string)($r['motif'] ?? ''), 160);
        }
    }
    if ($fiche['signatures']) {
        $lines[] = '';
        $lines[] = 'Dossiers de signature (' . count($fiche['signatures']) . ') :';
        foreach (array_slice($fiche['signatures'], 0, 10) as $sg) {
            $lines[] = '- ' . ($sg['statut'] ?? '') . ' depuis le ' . ($sg['date_envoi'] ?? '?');
        }
    }
    if ($fiche['mobilites']) {
        $lines[] = '';
        $lines[] = 'Mobilités (' . count($fiche['mobilites']) . ') :';
        foreach (array_slice($fiche['mobilites'], 0, 10) as $m) {
            $lines[] = '- étape ' . ($m['etape'] ?? '?') . ', banque de départ : ' . ($m['banque_depart'] ?? '—');
        }
    }
    if ($fiche['credit_immo']) {
        $lines[] = '';
        $lines[] = 'Crédits immobiliers (' . count($fiche['credit_immo']) . ') :';
        foreach (array_slice($fiche['credit_immo'], 0, 10) as $c) {
            $lines[] = '- ' . ($c['type_credit'] ?? 'type ?') . ', montant acquisition ' . ($c['montant_acquisition'] ?? 0) .
                       ', offre signée : ' . (($c['suivi_offre_signee'] ?? 0) ? 'oui' : 'non');
        }
    }
    if ($fiche['phoning']) {
        $lines[] = '';
        $lines[] = 'Derniers appels (' . count($fiche['phoning']) . ') :';
        foreach (array_slice($fiche['phoning'], 0, 10) as $p) {
            $lines[] = '- ' . ($p['resultat'] ?? '') . ' le ' . substr((string)($p['created_at'] ?? ''), 0, 10) .
                       ($p['resultat'] === 'rdv' && !empty($p['date_rdv']) ? ' (RDV le ' . $p['date_rdv'] . ')' : '') .
                       ' ' . excerpt((string)($p['commentaire'] ?? ''), 120);
        }
    }
    if ($fiche['interets']) {
        $lines[] = '';
        $lines[] = 'Intérêts clients :';
        foreach (array_slice($fiche['interets'], 0, 10) as $it) {
            $lines[] = '- ' . ($it['categorie'] ?? '') . ' : ' . excerpt((string)($it['interet'] ?? ''), 120);
        }
    }
    if ($fiche['notes']) {
        $lines[] = '';
        $lines[] = 'Notes internes :';
        foreach (array_slice($fiche['notes'], 0, 10) as $n) {
            $lines[] = '- ' . excerpt((string)$n['message'], 160);
        }
    }
    if ($fiche['courriers']) {
        $lines[] = '';
        $lines[] = 'Courriers (' . count($fiche['courriers']) . ') :';
        foreach (array_slice($fiche['courriers'], 0, 8) as $c) {
            $lines[] = '- ' . substr((string)($c['date_courrier'] ?? ''), 0, 10) . ' : ' . excerpt((string)($c['objet'] ?? ''), 120);
        }
    }
    return implode("\n", $lines);
}
