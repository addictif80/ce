<?php
$pageTitle = 'Rapport d\'activité';
require_once __DIR__ . '/../../templates/header.php';
$db     = getDB();
$userId = getCurrentUserId();
$user   = getCurrentUser();

// Instances en cours (à faire uniquement)
$stmt = $db->prepare("SELECT * FROM instances WHERE user_id = ? AND statut = 'a_faire' ORDER BY date_echeance ASC");
$stmt->execute([$userId]);
$instances = $stmt->fetchAll();

// Demandes clients en cours (non traitées)
$stmt = $db->prepare("SELECT * FROM demandes_clients WHERE user_id = ? AND traitee = 0 ORDER BY date_ajout DESC");
$stmt->execute([$userId]);
$demandes = $stmt->fetchAll();

// Contacts équipe pour la sélection mail (uniquement ceux ayant un e-mail)
$stmtContacts = $db->query(
    "SELECT id, nom, prenom, email_pro AS email, 'auto' AS source FROM users
        WHERE email_pro IS NOT NULL AND email_pro != ''
     UNION ALL
     SELECT id, nom, prenom, email, 'manual' AS source FROM contacts_equipe
        WHERE email IS NOT NULL AND email != ''
     ORDER BY nom, prenom"
);
$teamContacts = $stmtContacts->fetchAll();

$today     = date('d/m/Y');
$expediteur = trim($user['prenom'] . ' ' . $user['nom']);
?>

<!-- Styles spécifiques impression -->
<style>
@media print {
    .sidebar, .topbar, .no-print { display: none !important; }
    .main-content { margin-left: 0 !important; }
    .page-content { padding: 0 !important; }
    .rapport-header { margin-bottom: 20px; }
    .rapport-section { page-break-inside: avoid; }
    .data-table th { background-color: #CF0A2C !important; color: #fff !important; -webkit-print-color-adjust: exact; print-color-adjust: exact; }
    .badge-afaire { background-color: #ffc107 !important; color: #000 !important; border-radius: 4px; padding: 2px 8px; -webkit-print-color-adjust: exact; print-color-adjust: exact; }
    .badge-retard { background-color: #dc3545 !important; color: #fff !important; border-radius: 4px; padding: 2px 8px; -webkit-print-color-adjust: exact; print-color-adjust: exact; }
    .rapport-footer { margin-top: 30px; font-size: 11px; color: #888; border-top: 1px solid #ddd; padding-top: 8px; }
}
.rapport-header { display: flex; align-items: center; justify-content: space-between; margin-bottom: 24px; }
.rapport-header h2 { margin: 0; font-size: 1.3rem; color: #CF0A2C; }
.rapport-header .rapport-meta { font-size: 13px; color: #666; }
.rapport-section { margin-bottom: 36px; }
.rapport-section h4 { color: #CF0A2C; border-bottom: 2px solid #CF0A2C; padding-bottom: 6px; margin-bottom: 12px; font-size: 1rem; }
.rapport-section .empty-msg { color: #888; font-style: italic; font-size: 13px; padding: 10px 0; }
.badge-retard { background: #dc3545; color: #fff; border-radius: 4px; padding: 2px 8px; font-size: 12px; }
.badge-afaire { background: #ffc107; color: #000; border-radius: 4px; padding: 2px 8px; font-size: 12px; }
.rapport-footer { margin-top: 30px; font-size: 11px; color: #888; border-top: 1px solid #ddd; padding-top: 8px; }
.contact-checkbox-list { max-height: 320px; overflow-y: auto; border: 1px solid #dee2e6; border-radius: 6px; padding: 10px 14px; }
.contact-checkbox-list label { display: flex; align-items: center; gap: 10px; padding: 5px 0; cursor: pointer; font-size: 14px; }
.contact-checkbox-list label:not(:last-child) { border-bottom: 1px solid #f0f0f0; }
</style>

<!-- Barre d'actions (masquée à l'impression) -->
<div class="row g-3 mb-4 no-print">
    <div class="col-md-3">
        <div class="stat-card">
            <div class="stat-number"><?= count($instances) ?></div>
            <div class="stat-label">Instances en cours</div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="stat-card">
            <div class="stat-number"><?= count($demandes) ?></div>
            <div class="stat-label">Demandes en cours</div>
        </div>
    </div>
    <div class="col-auto d-flex align-items-center gap-2">
        <button class="btn btn-ce-outline" onclick="window.print()"><i class="fas fa-print"></i> Imprimer</button>
        <button class="btn btn-ce" data-bs-toggle="modal" data-bs-target="#shareMailModal"><i class="fas fa-envelope"></i> Envoyer par mail</button>
        <a href="index.php" class="btn btn-outline-secondary"><i class="fas fa-arrow-left"></i> Retour</a>
    </div>
</div>

<!-- ========== RAPPORT ========== -->
<div class="rapport-header">
    <div>
        <h2><i class="fas fa-file-alt"></i> Rapport d'activité</h2>
        <div class="rapport-meta"><?= e($expediteur) ?> &mdash; généré le <?= $today ?></div>
    </div>
</div>

<!-- Instances en cours -->
<div class="rapport-section">
    <h4><i class="fas fa-tasks"></i> Instances en cours (<?= count($instances) ?>)</h4>
    <?php if (empty($instances)): ?>
        <p class="empty-msg">Aucune instance en cours.</p>
    <?php else: ?>
    <div class="data-table-container">
        <table class="data-table" id="tableInstances">
            <thead>
                <tr>
                    <th>Date ajout</th>
                    <th>N° Personne / Nom</th>
                    <th>Échéance</th>
                    <th>Catégorie</th>
                    <th>Détails</th>
                    <th class="no-print">Statut</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($instances as $inst):
                $echeanceClass = getEcheanceClass($inst['date_echeance']);
                $isRetard = ($echeanceClass === 'bg-danger text-white');
            ?>
                <tr>
                    <td><?= formatDate($inst['date_ajout']) ?></td>
                    <td><strong><?= e($inst['numero_personne']) ?></strong></td>
                    <td>
                        <?php if ($inst['date_echeance']): ?>
                            <span class="<?= $isRetard ? 'badge-retard' : '' ?>"><?= formatDate($inst['date_echeance']) ?></span>
                        <?php else: ?>
                            <span class="text-muted">—</span>
                        <?php endif; ?>
                    </td>
                    <td><?= e($inst['categories']) ?></td>
                    <td><?= e(excerpt($inst['details'], 100)) ?></td>
                    <td class="no-print">
                        <span class="badge-afaire"><i class="fas fa-clock"></i> À faire</span>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php endif; ?>
</div>

<!-- Demandes clients en cours -->
<div class="rapport-section">
    <h4><i class="fas fa-headset"></i> Demandes clients en cours (<?= count($demandes) ?>)</h4>
    <?php if (empty($demandes)): ?>
        <p class="empty-msg">Aucune demande client en cours.</p>
    <?php else: ?>
    <div class="data-table-container">
        <table class="data-table" id="tableDemandes">
            <thead>
                <tr>
                    <th>Date ajout</th>
                    <th>N° Personne</th>
                    <th>Détails de la demande</th>
                    <th>Date envoi</th>
                    <th>Service</th>
                    <th class="no-print">Statut</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($demandes as $dem): ?>
                <tr>
                    <td><?= formatDate($dem['date_ajout']) ?></td>
                    <td><strong><?= e($dem['numero_personne']) ?></strong></td>
                    <td><?= e(excerpt($dem['details_demande'], 100)) ?></td>
                    <td><?= formatDate($dem['date_envoi']) ?: '<span class="text-muted">—</span>' ?></td>
                    <td><?= e($dem['service']) ?></td>
                    <td class="no-print">
                        <span class="badge-afaire"><i class="fas fa-clock"></i> En cours</span>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php endif; ?>
</div>

<div class="rapport-footer">
    Portail Conseiller &mdash; Caisse d'Épargne &mdash; rapport généré le <?= $today ?> par <?= e($expediteur) ?>
</div>

<!-- ========== MODAL ENVOI MAIL ========== -->
<div class="modal fade modal-fullscreen-custom" id="shareMailModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fas fa-envelope"></i> Envoyer le rapport par mail</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" action="share_rapport_mail.php" target="_blank">
                <div class="modal-body">
                    <p class="text-muted mb-3">
                        Sélectionnez les membres de l'équipe à qui envoyer ce rapport.<br>
                        Un fichier <strong>.eml</strong> sera téléchargé, prêt à être ouvert et envoyé depuis Outlook.
                    </p>

                    <?php if (empty($teamContacts)): ?>
                        <div class="alert alert-warning">
                            <i class="fas fa-exclamation-triangle"></i>
                            Aucun contact avec adresse mail disponible.
                            Ajoutez des contacts dans <a href="../contacts/index.php">Contacts utiles</a>.
                        </div>
                    <?php else: ?>
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <label class="form-label mb-0 fw-bold">Destinataires</label>
                            <div class="d-flex gap-2">
                                <button type="button" class="btn btn-sm btn-outline-secondary" onclick="toggleAllContacts(true)">Tout sélectionner</button>
                                <button type="button" class="btn btn-sm btn-outline-secondary" onclick="toggleAllContacts(false)">Tout désélectionner</button>
                            </div>
                        </div>
                        <div class="contact-checkbox-list">
                            <?php foreach ($teamContacts as $contact): ?>
                            <label>
                                <input type="checkbox" name="contact_ids[]"
                                       value="<?= (int)$contact['id'] ?>_<?= e($contact['source']) ?>"
                                       class="form-check-input contact-check">
                                <span>
                                    <strong><?= e($contact['nom'] . ' ' . $contact['prenom']) ?></strong>
                                    <span class="text-muted ms-2"><?= e($contact['email']) ?></span>
                                </span>
                            </label>
                            <?php endforeach; ?>
                        </div>
                        <div class="mt-2 text-muted" id="selectedCount" style="font-size:13px;">0 destinataire(s) sélectionné(s)</div>
                    <?php endif; ?>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Annuler</button>
                    <?php if (!empty($teamContacts)): ?>
                    <button type="submit" class="btn btn-ce" id="btnGenererEml" disabled>
                        <i class="fas fa-download"></i> Générer le .eml
                    </button>
                    <?php endif; ?>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
// Compteur de sélection et état du bouton
document.querySelectorAll('.contact-check').forEach(function(cb) {
    cb.addEventListener('change', updateCount);
});
function updateCount() {
    const n = document.querySelectorAll('.contact-check:checked').length;
    document.getElementById('selectedCount').textContent = n + ' destinataire(s) sélectionné(s)';
    document.getElementById('btnGenererEml').disabled = (n === 0);
}
function toggleAllContacts(checked) {
    document.querySelectorAll('.contact-check').forEach(cb => { cb.checked = checked; });
    updateCount();
}
</script>

<?php require_once __DIR__ . '/../../templates/footer.php'; ?>
