<?php
// Onglet « Conditions » de l'administration : texte d'avertissement « outils d'aide », affiché sur /tools et à accepter par les utilisateurs du portail.
require_once __DIR__ . '/../../includes/terms.php';
$t = getTermsSettings();
[$nbOk, $nbTotal] = termsStats();
?>
<div class="alert alert-info">
    <strong><i class="fas fa-circle-info"></i> À quoi ça sert ?</strong>
    Ce texte est affiché de deux façons : sur <strong>/tools</strong>, dans une carte placée en première position, et dans le <strong>portail</strong>, où chaque utilisateur doit l'accepter à sa première connexion
    (une fenêtre s'affiche à chaque page tant qu'il ne l'a pas fait ; il peut aussi se déconnecter). Une fois accepté, il n'est plus demandé, sauf si vous exigez une nouvelle acceptation.
</div>
<div class="row g-3 mb-4">
    <div class="col-md-4"><div class="stat-card"><div class="stat-number"><?= $nbOk ?> / <?= $nbTotal ?></div><div class="stat-label">Utilisateurs ayant accepté la version en vigueur</div></div></div>
</div>
<div class="data-table-container mb-4">
    <div class="data-table-header"><h3><i class="fas fa-scale-balanced"></i> Texte des conditions et avertissement</h3></div>
    <div class="p-3">
        <form method="post">
            <input type="hidden" name="action" value="save_terms">
            <div class="mb-3"><label class="form-label">Titre</label><input type="text" name="terms_title" class="form-control" maxlength="150" value="<?= e($t['title']) ?>"></div>
            <div class="mb-3"><label class="form-label">Texte <span class="text-muted small">(un paragraphe par ligne)</span></label>
                <textarea name="terms_text" class="form-control" rows="8" maxlength="4000"><?= e($t['text']) ?></textarea></div>
            <div class="form-check mb-3"><input class="form-check-input" type="checkbox" name="ask_again" id="ask_again"><label class="form-check-label" for="ask_again">Demander à nouveau l'acceptation à tous les utilisateurs (à cocher quand le texte change sur le fond)</label></div>
            <button class="btn btn-ce btn-sm"><i class="fas fa-save"></i> Enregistrer</button>
        </form>
    </div>
</div>
