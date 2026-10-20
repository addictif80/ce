<?php
/**
 * Agences (nom, adresse, numéro), gérées dans l'administration (onglet Agences). Utilisées par le formulaire de bureau domiciliaire
 * (portail et /tools) et par la demande d'accès au portail depuis /tools. La liste ci-dessous n'est que le contenu initial de la table.
 */
function agencesDefaultNames() {
    return [
        'ALBI FRANCOIS VERDIER', 'ALBI JEAN JAURES', 'ALBI MADELEINE', 'ARBEAU',
        'ARGELES GAZOST', 'AUBIN', 'AUCH LIBERATION', 'AUCH PATTE D\'OIE',
        'AUCAMVILLE', 'AUREILHAN', 'AUSSONNE', 'AUTERIVE',
        'BAGNERES DE BIGORRE', 'BAGNERES DE LUCHON', 'BALMA', 'BARAQUEVILLE',
        'BEAUMONT DE LOMAGNE', 'BEAUZELLE', 'BLAGNAC GRAND NOBLE', 'BLAGNAC PUIG',
        'BOULOGNE SUR GESSE', 'BRASSAC', 'BRUGUIERES', 'CAHORS CLEMENCEAU',
        'CAHORS MARYSE BASTIE', 'CAHORS TERRE ROUGE', 'CAPDENAC GARE', 'CARBONNE',
        'CARMAUX', 'CASTANET TOLOSAN', 'CASTELGINEST', 'CASTELSARRASIN',
        'CASTRES CARNOT', 'CASTRES LAMEILHE', 'CASTRES LES LICES', 'CAUSSADE',
        'CAZERES', 'COLOMIERS DUROCH', 'COLOMIERS HOTEL DE VILLE', 'CONDOM',
        'CORDES SUR CIEL', 'CORNEBARRIEU', 'CUGNAUX', 'DECAZEVILLE',
        'EAUZE', 'ECONOMIE SOCIALE', 'ESPALION', 'FENOUILLET',
        'FIGEAC', 'FLEURANCE', 'FOIX VILLOTE', 'FONSEGRIVES',
        'FONSORBES', 'FRONTON', 'GAILLAC', 'GIMONT',
        'GOURDAN-POLIGNAN', 'GOURDON', 'GRAMAT', 'GRAULHET',
        'GRENADE', 'GRISOLLES', 'HTES PYRENEES GERS COMMINGES', 'L\'ISLE JOURDAIN',
        'L\'UNION', 'LA PRIMAUBE', 'LA SALVETAT SAINT GILLES', 'LABASTIDE ROUAIROUX',
        'LABEGE', 'LABRUGUIERE', 'LACAUNE', 'LAGUIOLE',
        'LANNEMEZAN', 'LAVAUR', 'LAVELANET', 'LECTOURE',
        'LEGUEVIN', 'LEZAT SUR LEZE', 'LOT   TARN ET GARONNE', 'LOURDES',
        'MARCIAC', 'MARCILLAC VALLON', 'MARSSAC SUR TARN', 'MASSEUBE',
        'MAUVEZIN', 'MAZAMET GAMBETTA', 'MAZERES', 'MILLAU CAPELLE',
        'MILLAU LA TINE', 'MIRANDE', 'MIREPOIX', 'MOISSAC',
        'MONBANQUIERENLIGNE', 'MONTASTRUC LA CONSEILLERE', 'MONTAUBAN FUTUROPOLE', 'MONTAUBAN GAMBETTA',
        'MONTAUBAN VILLEBOURBON', 'MONTBAZENS', 'MONTECH', 'MONTESQUIEU VOLVESTRE',
        'MURET NIEL', 'NOGARO', 'ONET-SEBAZAC', 'PAMIERS',
        'PAMIERS LA BOURIETTE', 'PIBRAC', 'PINS JUSTARET', 'PLAISANCE DU TOUCH',
        'PORTET SUR GARONNE', 'PRAYSSAC', 'PROFESSIONNELS DE L\'IMMOBILIER', 'PUYLAURENS',
        'RABASTENS', 'RAMONVILLE SAINT AGNE', 'REALMONT', 'REQUISTA',
        'REVEL', 'RIEUMES', 'RIEUPEYROUX', 'RODEZ BOURRAN',
        'RODEZ EUROPE', 'RODEZ FAUBOURG', 'RODEZ RAYNALDY', 'SAINT AFFRIQUE',
        'SAINT CERE', 'SAINT GAUDENS', 'SAINT GIRONS', 'SAINT JEAN',
        'SAINT JORY', 'SAINT JUERY', 'SAINT LYS', 'SAINT ORENS DE GAMEVILLE',
        'SAINT SULPICE', 'SAMATAN', 'SECTEUR PUBLIC / LOGT SOCIAL', 'SEVERAC LE CHATEAU',
        'SOUAL', 'SOUILLAC', 'TARASCON SUR ARIEGE', 'TARBES ARSENAL',
        'TARBES FOIRAIL', 'TARBES LA GESPE', 'TARBES LARREY', 'TARN AVEYRON',
        'TOULOUSE ALSACE LORRAINE', 'TOULOUSE ARCOLE', 'TOULOUSE ARIEGE', 'TOULOUSE AUCAMVILLE',
        'TOULOUSE BELLEFONTAINE', 'TOULOUSE BONNEFOY', 'TOULOUSE CARNOT', 'TOULOUSE COTE PAVEE',
        'TOULOUSE CROIX DAURADE', 'TOULOUSE CROIX DE PIERRE', 'TOULOUSE GRANDE BRETAGNE', 'TOULOUSE GUILHEMERY',
        'TOULOUSE JEAN JAURES', 'TOULOUSE JOLIMONT LA ROSERAIE', 'TOULOUSE LANGUEDOC', 'TOULOUSE LARDENNE',
        'TOULOUSE MINIMES', 'TOULOUSE SAINT AGNE', 'TOULOUSE SAINT CYPRIEN', 'TOULOUSE SAINT SIMON',
        'TOULOUSE SAINT-EXUPERY', 'TOULOUSE SEPT DENIERS', 'TOULOUSE ST MICHEL', 'TOURNEFEUILLE',
        'VALENCE D\'AGEN', 'VARILHES', 'VERDUN SUR GARONNE', 'VERFEIL',
        'VIC EN BIGORRE', 'VIC FEZENSAC', 'VILLEFRANCHE DE LAURAGAIS', 'VILLEFRANCHE LA ROCADE',
        'VILLEFRANCHE SARZANA', 'VILLEMUR SUR TARN', 'VILLENEUVE TOLOSANE',
    ];
}


function ensureAgencesSchema() {
    static $done = false; if ($done) return; $done = true;
    $db = getDB();
    $db->exec("CREATE TABLE IF NOT EXISTS agences (
        id INT AUTO_INCREMENT PRIMARY KEY,
        nom VARCHAR(120) NOT NULL,
        adresse VARCHAR(255) NOT NULL DEFAULT '',
        numero VARCHAR(10) NOT NULL DEFAULT '',
        actif TINYINT(1) NOT NULL DEFAULT 1,
        UNIQUE KEY uk_nom (nom)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    if ((int)$db->query("SELECT COUNT(*) FROM agences")->fetchColumn() === 0) {
        $ins = $db->prepare("INSERT IGNORE INTO agences (nom, adresse, numero) VALUES (?, ?, ?)");
        foreach (agencesDefaultNames() as $nom) {
            $cap = $nom === 'CAPDENAC GARE';
            $ins->execute([$nom, $cap ? '5 Avenue Charles de Gaulle, 12700 Capdenac-Gare' : '', $cap ? '346' : '']);
        }
    }
}

/** Agences (lignes complètes), triées par nom ; $onlyActive = false pour l'administration. */
function getAgencesFull($onlyActive = true) {
    try {
        ensureAgencesSchema();
        $rows = getDB()->query("SELECT id, nom, adresse, numero, actif FROM agences " . ($onlyActive ? "WHERE actif = 1 " : "") . "ORDER BY nom")->fetchAll(PDO::FETCH_ASSOC);
        if ($rows) return $rows;
    } catch (Throwable $e) { error_log('agences: ' . $e->getMessage()); }
    return array_map(fn($n) => ['id' => 0, 'nom' => $n, 'adresse' => '', 'numero' => '', 'actif' => 1], agencesDefaultNames()); // base indisponible : liste de secours
}

/** Noms des agences actives (compatibilité). */
function getAgences() { return array_column(getAgencesFull(true), 'nom'); }

/** Libellé « NOM (numéro) » sans l'adresse (bureau domiciliaire). */
function agenceLabelNum(array $a) { return $a['nom'] . ($a['numero'] !== '' ? ' (' . $a['numero'] . ')' : ''); }

/** Libellé « NOM — adresse » (sélections qui affichent l'adresse). */
function agenceLabelAdresse(array $a) { return $a['nom'] . ($a['adresse'] !== '' ? ' — ' . $a['adresse'] : ''); }
