<?php
$pageTitle = 'Modification Bureau Domiciliaire';
$extraCss = [];
require_once __DIR__ . '/../../templates/header.php';
?>

<style>
    /* Styles spécifiques au formulaire */
    .dom-container {
        max-width: 800px;
        margin: 0 auto;
        background: white;
        border: 2px solid #000;
        font-size: 10px;
    }
    .dom-title-section {
        background-color: #e0e0e0;
        padding: 10px;
        text-align: center;
        border-bottom: 1px solid #000;
    }
    .dom-title-section h2 {
        font-size: 18px;
        font-weight: bold;
        margin-bottom: 2px;
    }
    .dom-content {
        padding: 12px;
    }
    .dom-row {
        display: flex;
        margin-bottom: 8px;
        align-items: center;
    }
    .dom-label {
        width: 150px;
        font-size: 10px;
        flex-shrink: 0;
    }
    .dom-input {
        flex: 1;
        padding: 5px;
        border: 1px solid #000;
        font-size: 11px;
    }
    .dom-input-short {
        width: 150px;
        padding: 5px;
        border: 1px solid #000;
        font-size: 11px;
    }
    .dom-checkbox-group {
        display: flex;
        gap: 20px;
        flex: 1;
        font-size: 10px;
    }
    .dom-section-box {
        border: 2px solid #000;
        padding: 8px;
        margin-bottom: 10px;
    }
    .dom-pink {
        background-color: #ffe0f0;
    }
    .dom-section-title {
        font-weight: bold;
        margin-bottom: 10px;
    }
    .dom-signature-box {
        border: 1px dotted #000;
        height: 80px;
        margin-top: 8px;
        text-align: center;
        padding-top: 30px;
    }
    .dom-obs-title {
        font-weight: bold;
        font-size: 10px;
        margin-bottom: 5px;
    }
    .dom-obs-box {
        border: 1px solid #000;
        min-height: 60px;
        padding: 5px;
        width: 100%;
        font-family: Arial, sans-serif;
        font-size: 10px;
        resize: vertical;
    }
    .dom-signature-row {
        display: flex;
        border: 1px solid #000;
    }
    .dom-signature-cell {
        flex: 1;
        padding: 8px;
        min-height: 100px;
    }
    .dom-signature-cell:first-child {
        border-right: 1px solid #000;
    }
    .dom-signature-label {
        font-weight: bold;
        font-size: 10px;
    }
    .dom-footer-notes {
        font-size: 7px;
        padding: 8px;
        font-style: italic;
        line-height: 1.3;
    }
    .dom-select {
        padding: 5px;
        border: 1px solid #000;
        font-size: 11px;
        width: 100%;
    }

    @media print {
        /* Masquer tout le portail sauf le formulaire */
        .sidebar, .main-header, .content-wrapper > *:not(.dom-print-zone) {
            display: none !important;
        }
        body, .content-wrapper, .main-content {
            padding: 0 !important;
            margin: 0 !important;
            background: white !important;
        }
        .dom-print-zone {
            margin: 0 !important;
        }
        .dom-container {
            max-width: 100%;
            border-width: 1px;
        }
        .btn-print-dom {
            display: none !important;
        }
        @page {
            size: A4;
            margin: 10mm;
        }
    }
</style>

<!-- Bouton Imprimer -->
<div class="d-flex justify-content-between align-items-center mb-3">
    <h4 class="mb-0"><i class="fas fa-exchange-alt"></i> Modification de Bureau Domiciliaire</h4>
    <button class="btn btn-ce btn-print-dom" onclick="window.print()">
        <i class="fas fa-print"></i> Imprimer
    </button>
</div>

<div class="dom-print-zone">
<div class="dom-container">

    <div class="dom-title-section">
        <h2>Modification de Bureau Domiciliaire</h2>
        <div style="font-size: 10px; font-style: italic;">(Intra CEMP)</div>
    </div>

    <div class="dom-content">
        <div class="dom-row">
            <label class="dom-label">Nom, pr&eacute;nom du client</label>
            <input type="text" class="dom-input">
        </div>

        <div class="dom-row">
            <label class="dom-label">Adresse</label>
            <input type="text" class="dom-input">
        </div>

        <div class="dom-row">
            <label class="dom-label">Date de naissance</label>
            <input type="date" class="dom-input-short">
            <label style="margin-left: 20px; margin-right: 5px;">N&deg; Personne du client</label>
            <input type="text" class="dom-input">
        </div>

        <div class="dom-row">
            <label class="dom-label">Qualit&eacute; demandeur</label>
            <div class="dom-checkbox-group">
                <div><input type="radio" name="qualite" checked> Titulaire</div>
                <div><input type="radio" name="qualite"> Mandataire</div>
                <div><input type="radio" name="qualite"> Repr&eacute;sentant l&eacute;gal</div>
            </div>
            <label style="margin-left: 20px; margin-right: 5px;">Radical</label>
            <input type="text" class="dom-input-short">
        </div>

        <!-- Cadre réservé au client -->
        <div class="dom-section-box dom-pink">
            <div class="dom-section-title">Cadre r&eacute;serv&eacute; au client</div>
            <div style="font-size: 10px; margin-bottom: 10px;">
                Je demande &agrave; la Caisse d'&Eacute;pargne de Midi-Pyr&eacute;n&eacute;es que tous mes comptes et cr&eacute;dits soient domicili&eacute;s &agrave; compter de ce jour
            </div>

            <div class="dom-row">
                <label class="dom-label">&agrave; l'agence :</label>
                <select class="dom-select" id="agence-select">
                    <option value="">S&eacute;lectionner une agence...</option>
                </select>
            </div>

            <div class="dom-row">
                <label class="dom-label">Ancien Bureau Dom.</label>
                <select class="dom-select" id="ancien-bureau-select">
                    <option value="">S&eacute;lectionner une agence...</option>
                </select>
            </div>

            <div class="dom-row">
                <label class="dom-label">Motif</label>
                <input type="text" class="dom-input">
            </div>

            <div class="dom-row">
                <label class="dom-label">Fait le</label>
                <input type="date" class="dom-input-short" id="date-fait">
                <label style="margin-left: 20px; margin-right: 5px;">&agrave;</label>
                <input type="text" class="dom-input-short" id="lieu-fait" readonly style="background-color: #f0f0f0;">
            </div>

            <div style="text-align: center; font-weight: bold; margin-top: 15px;">
                Signature du client
            </div>
            <div class="dom-signature-box">.</div>
        </div>

        <!-- Observations -->
        <div style="margin-bottom: 8px;">
            <div class="dom-obs-title">Observations agence cessionnaire <span style="font-style: italic; font-weight: normal;">(en majuscules)</span></div>
            <textarea class="dom-obs-box"></textarea>
        </div>

        <div style="margin-bottom: 8px;">
            <div class="dom-obs-title">Observations agence c&eacute;dante <span style="font-style: italic; font-weight: normal;">(optionnel / documents transmis ...)</span></div>
            <textarea class="dom-obs-box"></textarea>
        </div>

        <!-- Signatures -->
        <div class="dom-signature-row">
            <div class="dom-signature-cell">
                <div class="dom-signature-label">NOM signataire et visa agence c&eacute;dante</div>
            </div>
            <div class="dom-signature-cell">
                <div class="dom-signature-label">NOM signataire et visa agence cessionnaire</div>
            </div>
        </div>

        <!-- Mode opératoire -->
        <div class="dom-footer-notes">
            <strong>Mode op&eacute;ratoire</strong> :<br>
            <em>1. L'<strong>agence cessionnaire</strong> : renseigne le bordereau ; le scanne apr&egrave;s visas du client et du DS/DSA/DA ; adresse la demande scann&eacute;e &agrave; l'agence c&eacute;dante par mail (BAL AGENCE) <strong>avec copie au Directeur de Secteur de l'agence c&eacute;dante</strong> (si l'agence c&eacute;dante n'est pas agence de secteur).</em><br>
            <em>2. &Agrave; r&eacute;ception du bordereau, l'<strong>agence c&eacute;dante</strong> proc&egrave;de au traitement de la demande (gestion IP, ...) et informe l'agence cessionnaire par email ;</em><br>
            <em>3. &Agrave; r&eacute;ception des &eacute;l&eacute;ments du dossier client de l'agence c&eacute;dante, l'<strong>agence cessionnaire</strong> proc&egrave;de au changement de domiciliation et archive la demande dans l'ENVELOPPE JOURNEE.</em><br>
            <br>
            <strong style="color: #c00;">Arbitrages</strong> : <em style="color: #c00;">Les contestations sont g&eacute;r&eacute;es par les Directeurs Commerciaux.</em><br>
            <br>
            <strong style="color: #800080;">BONNE PRATIQUE :</strong> <em style="color: #800080;">Cr&eacute;er une bo&icirc;te CHANGEMENT DE DOM dans la BAL AGENCE pour suivre les demandes</em>
        </div>
    </div>
</div>
</div>

<script>
const agencies = [
    "ALBI FRANCOIS VERDIER","ALBI JEAN JAURES","ALBI MADELEINE","ARBEAU","ARGELES GAZOST",
    "AUBIN","AUCH LIBERATION","AUCH PATTE D'OIE","AUCAMVILLE","AUREILHAN","AUSSONNE",
    "AUTERIVE","BAGNERES DE BIGORRE","BAGNERES DE LUCHON","BALMA","BARAQUEVILLE",
    "BEAUMONT DE LOMAGNE","BEAUZELLE","BLAGNAC GRAND NOBLE","BLAGNAC PUIG",
    "BOULOGNE SUR GESSE","BRASSAC","BRUGUIERES","CAHORS CLEMENCEAU","CAHORS MARYSE BASTIE",
    "CAHORS TERRE ROUGE","CAPDENAC GARE","CARBONNE","CARMAUX","CASTANET TOLOSAN",
    "CASTELGINEST","CASTELSARRASIN","CASTRES CARNOT","CASTRES LAMEILHE","CASTRES LES LICES",
    "CAUSSADE","CAZERES","COLOMIERS DUROCH","COLOMIERS HOTEL DE VILLE","CONDOM",
    "CORDES SUR CIEL","CORNEBARRIEU","CUGNAUX","DECAZEVILLE","EAUZE","ECONOMIE SOCIALE",
    "ESPALION","FENOUILLET","FIGEAC","FLEURANCE","FOIX VILLOTE","FONSEGRIVES","FONSORBES",
    "FRONTON","GAILLAC","GIMONT","GOURDAN-POLIGNAN","GOURDON","GRAMAT","GRAULHET",
    "GRENADE","GRISOLLES","HTES PYRENEES GERS COMMINGES","L'ISLE JOURDAIN","L'UNION",
    "LA PRIMAUBE","LA SALVETAT SAINT GILLES","LABASTIDE ROUAIROUX","LABEGE","LABRUGUIERE",
    "LACAUNE","LAGUIOLE","LANNEMEZAN","LAVAUR","LAVELANET","LECTOURE","LEGUEVIN",
    "LEZAT SUR LEZE","LOT   TARN ET GARONNE","LOURDES","MARCIAC","MARCILLAC VALLON",
    "MARSSAC SUR TARN","MASSEUBE","MAUVEZIN","MAZAMET GAMBETTA","MAZERES","MILLAU CAPELLE",
    "MILLAU LA TINE","MIRANDE","MIREPOIX","MOISSAC","MONBANQUIERENLIGNE",
    "MONTASTRUC LA CONSEILLERE","MONTAUBAN FUTUROPOLE","MONTAUBAN GAMBETTA",
    "MONTAUBAN VILLEBOURBON","MONTBAZENS","MONTECH","MONTESQUIEU VOLVESTRE","MURET NIEL",
    "NOGARO","ONET-SEBAZAC","PAMIERS","PAMIERS LA BOURIETTE","PIBRAC","PINS JUSTARET",
    "PLAISANCE DU TOUCH","PORTET SUR GARONNE","PRAYSSAC","PROFESSIONNELS DE L'IMMOBILIER",
    "PUYLAURENS","RABASTENS","RAMONVILLE SAINT AGNE","REALMONT","REQUISTA","REVEL","RIEUMES",
    "RIEUPEYROUX","RODEZ BOURRAN","RODEZ EUROPE","RODEZ FAUBOURG","RODEZ RAYNALDY",
    "SAINT AFFRIQUE","SAINT CERE","SAINT GAUDENS","SAINT GIRONS","SAINT JEAN","SAINT JORY",
    "SAINT JUERY","SAINT LYS","SAINT ORENS DE GAMEVILLE","SAINT SULPICE","SAMATAN",
    "SECTEUR PUBLIC / LOGT SOCIAL","SEVERAC LE CHATEAU","SOUAL","SOUILLAC",
    "TARASCON SUR ARIEGE","TARBES ARSENAL","TARBES FOIRAIL","TARBES LA GESPE","TARBES LARREY",
    "TARN AVEYRON","TOULOUSE ALSACE LORRAINE","TOULOUSE ARCOLE","TOULOUSE ARIEGE",
    "TOULOUSE AUCAMVILLE","TOULOUSE BELLEFONTAINE","TOULOUSE BONNEFOY","TOULOUSE CARNOT",
    "TOULOUSE COTE PAVEE","TOULOUSE CROIX DAURADE","TOULOUSE CROIX DE PIERRE",
    "TOULOUSE GRANDE BRETAGNE","TOULOUSE GUILHEMERY","TOULOUSE JEAN JAURES",
    "TOULOUSE JOLIMONT LA ROSERAIE","TOULOUSE LANGUEDOC","TOULOUSE LARDENNE",
    "TOULOUSE MINIMES","TOULOUSE SAINT AGNE","TOULOUSE SAINT CYPRIEN","TOULOUSE SAINT SIMON",
    "TOULOUSE SAINT-EXUPERY","TOULOUSE SEPT DENIERS","TOULOUSE ST MICHEL","TOURNEFEUILLE",
    "VALENCE D'AGEN","VARILHES","VERDUN SUR GARONNE","VERFEIL","VIC EN BIGORRE",
    "VIC FEZENSAC","VILLEFRANCHE DE LAURAGAIS","VILLEFRANCHE LA ROCADE","VILLEFRANCHE SARZANA",
    "VILLEMUR SUR TARN","VILLENEUVE TOLOSANE"
];

(function() {
    const select1 = document.getElementById('agence-select');
    const select2 = document.getElementById('ancien-bureau-select');

    agencies.forEach(agency => {
        select1.add(new Option(agency, agency));
        select2.add(new Option(agency, agency));
    });

    select1.addEventListener('change', function() {
        document.getElementById('lieu-fait').value = this.value;
    });

    // Date du jour par défaut
    const today = new Date();
    document.getElementById('date-fait').value = today.toISOString().split('T')[0];
})();
</script>

<?php require_once __DIR__ . '/../../templates/footer.php'; ?>
