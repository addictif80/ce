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
    </ul>
</div></div>
