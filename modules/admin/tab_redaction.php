<?php
// Onglet « Rédaction » : réglages de l'aide à la rédaction du générateur de courrier (correction, reformulation, réponse à un mail).
require_once __DIR__ . '/../../includes/assist.php';
$hasKey = assistKey() !== '';
$test = json_decode(getToolsSetting('assist_last_test', ''), true) ?: null;
?>
<div class="alert alert-info">
    <strong><i class="fas fa-circle-info"></i> Aide à la rédaction du générateur de courrier</strong>
    Quand elle est activée, trois boutons discrets apparaissent dans l'éditeur du courrier (<code>/tools</code> et module connecté) :
    <em>Corriger</em> (orthographe et grammaire), <em>Reformuler</em> (style au choix) et <em>Répondre à un mail</em>.
    Pour l'utilisateur, c'est une simple fonction de rédaction : aucune mention du service utilisé. Chaque proposition est comparée au texte d'origine (passages modifiés surlignés) et n'est appliquée qu'après validation.
</div>
<div class="card mb-4"><div class="card-header"><strong><i class="fas fa-key"></i> Connexion au service (1min.ai)</strong>
    <span class="badge bg-<?= assistEnabled() ? 'success' : 'secondary' ?> ms-2"><?= assistEnabled() ? 'Active' : 'Désactivée' ?></span></div>
<div class="card-body">
    <form method="post" class="row g-3">
        <input type="hidden" name="action" value="save_assist">
        <div class="col-12"><div class="form-check form-switch"><input class="form-check-input" type="checkbox" role="switch" name="enabled" id="assistOn" <?= getToolsSetting('assist_on', '0') === '1' ? 'checked' : '' ?>><label class="form-check-label" for="assistOn">Activer l'aide à la rédaction</label></div></div>
        <div class="col-md-6"><label class="form-label">Clé API 1min.ai</label>
            <input type="password" name="assist_key" class="form-control" autocomplete="new-password" placeholder="<?= $hasKey ? '•••••••• enregistrée (laisser vide pour la conserver)' : 'Collez la clé API' ?>">
            <?php if ($hasKey): ?><div class="form-check mt-1"><input class="form-check-input" type="checkbox" name="clear_key" id="clearKey"><label class="form-check-label small" for="clearKey">Supprimer la clé enregistrée</label></div><?php endif; ?>
            <div class="form-text">La clé n'est jamais affichée ni transmise aux navigateurs : seul le serveur appelle le service.</div></div>
        <div class="col-md-3"><label class="form-label">Modèle</label><input type="text" name="assist_model" class="form-control" value="<?= e(getToolsSetting('assist_model', '')) ?>" placeholder="gpt-4o-mini">
            <div class="form-text">Identifiant du modèle proposé par 1min.ai (vide = gpt-4o-mini).</div></div>
        <div class="col-12"><label class="form-label">Consignes complémentaires de rédaction <span class="text-muted">(facultatif)</span></label>
            <textarea name="assist_rules" class="form-control" rows="4" maxlength="2000" placeholder="Ex. : Toujours écrire « la Caisse d'Épargne » (jamais « la banque »). Ne jamais citer de montant de frais sans renvoyer aux conditions tarifaires. Terminer par « Bien cordialement » plutôt que par une formule longue."><?= e(getToolsSetting('assist_rules', '')) ?></textarea>
            <div class="form-text">S'ajoute au cadrage bancaire intégré (vouvoiement, vocabulaire de banque, ton orienté solution, fond exact) pour les trois fonctions. Enregistrez puis refaites un essai de reformulation pour affiner.</div></div>
        <div class="col-12"><hr class="my-1"><strong class="d-block mb-1">Usages complémentaires (assistant de dossier)</strong>
            <div class="form-text mb-2">Ces usages s'appuient sur la même connexion ci-dessus. Les données du dossier sont <strong>anonymisées</strong> (numéro de personne, nom, e-mail, téléphone, IBAN) avant tout envoi au service.</div>
            <div class="form-check form-switch mb-1"><input class="form-check-input" type="checkbox" role="switch" name="assist_dossier_on" id="assistDossierOn" <?= getToolsSetting('assist_dossier_on', '1') === '1' ? 'checked' : '' ?>><label class="form-check-label" for="assistDossierOn">Résumé de dossier et suggestions de prochaine action (fiche client 360°)</label></div>
            <div class="form-check form-switch"><input class="form-check-input" type="checkbox" role="switch" name="assist_search_on" id="assistSearchOn" <?= getToolsSetting('assist_search_on', '1') === '1' ? 'checked' : '' ?>><label class="form-check-label" for="assistSearchOn">Recherche en langage naturel dans les procédures</label></div>
            <div class="form-text">Même sans IA, la fiche client affiche un résumé local et les règles métier ; seule l'enrichissement est désactivé.</div>
        </div>
        <div class="col-12"><label class="form-label">Consignes de l'« Assistant IA » <span class="text-muted">(facultatif)</span></label>
            <textarea name="chat_rules" class="form-control" rows="3" maxlength="2000" placeholder="Ex. : Pour toute question réglementaire, rappelle de vérifier dans la documentation interne. Toujours vouvoyer."><?= e(getToolsSetting('chat_rules', '')) ?></textarea>
            <div class="form-text">S'ajoute aux consignes générales de l'assistant de conversation (module « Assistant IA »). Ce module est actif dans le portail ; il est masqué dans /tools tant que vous ne l'activez pas dans l'onglet « Outils publics ».</div></div>
        <div class="col-12"><button class="btn btn-ce">Enregistrer</button></div>
    </form>
</div></div>
<div class="card mb-4" id="assist-test"><div class="card-header"><strong><i class="fas fa-vial"></i> Tester la connexion</strong></div>
<div class="card-body">
    <form method="post"><input type="hidden" name="action" value="test_assist"><button class="btn btn-outline-secondary" <?= $hasKey ? '' : 'disabled' ?>>Lancer un test de correction</button></form>
    <?php if ($test): ?><div class="alert alert-<?= $test[0] === 'ok' ? 'success' : 'danger' ?> mt-3 mb-0"><div class="small text-muted mb-1">Dernier test : <?= e($test[2] ?? '') ?></div><?= $test[0] === 'ok' ? '<strong>Connexion réussie.</strong> Réponse reçue : ' : '<strong>Échec.</strong> ' ?><code style="white-space:pre-wrap;word-break:break-word"><?= e($test[1]) ?></code></div><?php endif; ?>
</div></div>
<div class="card mb-4"><div class="card-body small text-muted">
    <strong>À savoir</strong>
    <ul class="mb-0">
        <li>Le texte que l'utilisateur soumet (corps du courrier, ou message auquel il répond) est transmis au service externe le temps du traitement ; ce portail n'en conserve aucune copie. La bannière de confidentialité de <code>/tools/courrier</code> le précise automatiquement quand la fonction est active.</li>
        <li>Limites : 8 000 caractères par demande et 40 demandes par heure et par session de navigation.</li>
        <li>Les variables <code>{{...}}</code> du courrier sont contrôlées : une proposition qui les altérerait est écartée.</li>
        <li>Les propositions restent à relire : elles ne remplacent pas la vérification du conseiller avant envoi.</li>
        <li><strong>Fiche client 360° et recherche dans les procédures :</strong> les données de dossier sont anonymisées (numéro de personne, nom, e-mail, téléphone, IBAN masqués) avant tout envoi. Le résumé local et les règles métier restent disponibles même si l'IA est désactivée.</li>
    </ul>
</div></div>
<?php require_once __DIR__ . '/../../includes/servicepublic.php'; $sp = spStatus(); ?>
<div class="card mb-4" id="sp-card"><div class="card-header"><strong><i class="fas fa-magnifying-glass"></i> Recherche service-public.fr (fiches « particuliers »)</strong>
    <span class="badge bg-<?= $sp['n'] ? 'success' : 'secondary' ?> ms-2"><?= $sp['n'] ? number_format($sp['n'], 0, ',', ' ') . ' fiches' : 'Base vide' ?></span></div>
<div class="card-body">
    <p class="small text-muted mb-2">Outil « Recherche service-public.fr » (/tools et portail). Les fiches proviennent des données ouvertes de la Direction de l'information légale et administrative (Licence Ouverte 2.0), téléchargées depuis
        <code>lecomarquage.service-public.fr</code> puis indexées dans la base du portail : la recherche est locale. Le résumé rédigé s'ajoute si l'aide à la rédaction est active (clé API ci-dessus).</p>
    <?php if ($sp['import']): ?>
        <div class="alert alert-<?= !empty($sp['import']['ok']) ? 'success' : 'danger' ?> py-2 small">
            <?php if (!empty($sp['import']['ok'])): ?>Dernier import le <?= e($sp['import']['at']) ?> : <?= (int)$sp['import']['n'] ?> fiches indexées<?= !empty($sp['import']['sup']) ? ', ' . (int)$sp['import']['sup'] . ' retirée(s)' : '' ?>.
            <?php else: ?>Échec le <?= e($sp['import']['at']) ?> : <?= e($sp['import']['err'] ?? '') ?><?php endif; ?>
            <?php if ($sp['derniere_maj']): ?> Fiche la plus récente : <?= e(date('d/m/Y', strtotime($sp['derniere_maj']))) ?>.<?php endif; ?></div>
    <?php endif; ?>
    <form method="post" onsubmit="this.querySelector('button').disabled=true;this.querySelector('button').textContent='Import en cours (environ une minute)…';">
        <input type="hidden" name="action" value="import_servicepublic">
        <button class="btn btn-ce"><i class="fas fa-cloud-arrow-down me-1"></i><?= $sp['n'] ? 'Mettre à jour les fiches' : 'Importer les fiches' ?></button>
        <span class="small text-muted ms-2">Archive d'environ 23 Mo ; à relancer de temps en temps (les fiches sont mises à jour chaque jour à la source).</span></form>
    <hr>
    <div class="small"><strong>Sans accès Internet sur le serveur ?</strong> Téléchargez l'archive depuis un poste connecté :
        <a href="https://lecomarquage.service-public.fr/vdd/3.4/part/zip/vosdroits-latest.zip" target="_blank" rel="noopener noreferrer">vosdroits-latest.zip</a> (fiches « particuliers »), puis déposez-la ici.
        <form method="post" enctype="multipart/form-data" class="d-flex gap-2 align-items-center mt-2 flex-wrap" onsubmit="this.querySelector('button').disabled=true;this.querySelector('button').textContent='Import en cours…';">
            <input type="hidden" name="action" value="import_servicepublic_file"><input type="file" name="archive" accept=".zip" class="form-control form-control-sm" style="max-width:340px" required>
            <button class="btn btn-sm btn-outline-secondary"><i class="fas fa-upload me-1"></i>Importer ce fichier</button></form>
        <div class="text-muted mt-2">Si l'envoi échoue (fichier trop gros pour le serveur), copiez le zip sur la VM puis lancez : <code>php cron/import_service_public.php /chemin/vosdroits-latest.zip</code> depuis le dossier du portail.</div></div>
</div></div>
