<?php
$pageTitle = 'Mon profil';
require_once __DIR__ . '/../../templates/header.php';
$db = getDB();
$userId = getCurrentUserId();
$user = getCurrentUser();
$message = '';
$error = '';

// Mise à jour du profil (seuls tel_pro et ligne_interne sont modifiables)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_profile') {
    $stmt = $db->prepare("UPDATE users SET tel_pro = ?, ligne_interne = ?, updated_at = NOW() WHERE id = ?");
    $stmt->execute([
        trim($_POST['tel_pro'] ?? ''),
        trim($_POST['ligne_interne'] ?? ''),
        $userId
    ]);
    $message = 'Profil mis à jour avec succès.';
    $user = getCurrentUser();
}

// Changement de mot de passe
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'change_password') {
    if (empty($_POST['current_password']) || empty($_POST['new_password']) || empty($_POST['confirm_password'])) {
        $error = 'Veuillez remplir tous les champs.';
    } elseif ($_POST['new_password'] !== $_POST['confirm_password']) {
        $error = 'Les mots de passe ne correspondent pas.';
    } elseif (strlen($_POST['new_password']) < 6) {
        $error = 'Le mot de passe doit contenir au moins 6 caractères.';
    } else {
        $stmt = $db->prepare("SELECT password FROM users WHERE id = ?");
        $stmt->execute([$userId]);
        $current = $stmt->fetchColumn();
        if (!password_verify($_POST['current_password'], $current)) {
            $error = 'Mot de passe actuel incorrect.';
        } else {
            $hash = password_hash($_POST['new_password'], PASSWORD_DEFAULT);
            $stmt = $db->prepare("UPDATE users SET password = ?, updated_at = NOW() WHERE id = ?");
            $stmt->execute([$hash, $userId]);
            $message = 'Mot de passe modifié avec succès.';
        }
    }
}
?>

<?php if ($message): ?>
<div class="alert alert-success alert-dismissible fade show" role="alert">
    <?= e($message) ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
<?php endif; ?>

<?php if ($error): ?>
<div class="alert alert-danger alert-dismissible fade show" role="alert">
    <?= e($error) ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
<?php endif; ?>

<div class="row g-4">
    <!-- Informations personnelles -->
    <div class="col-md-7">
        <div class="dashboard-card">
            <h4><i class="fas fa-user-edit"></i> Informations personnelles</h4>
            <form method="POST">
                <input type="hidden" name="action" value="update_profile">
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label">Identifiant</label>
                        <input type="text" class="form-control" value="<?= e($user['username']) ?>" disabled>
                        <small class="text-muted">L'identifiant ne peut pas être modifié.</small>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Rôle</label>
                        <input type="text" class="form-control" value="<?= $user['is_admin'] ? 'Administrateur' : 'Utilisateur' ?>" disabled>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Nom</label>
                        <input type="text" class="form-control" value="<?= e($user['nom']) ?>" disabled>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Prénom</label>
                        <input type="text" class="form-control" value="<?= e($user['prenom']) ?>" disabled>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Email professionnel</label>
                        <input type="email" class="form-control" value="<?= e($user['email_pro']) ?>" disabled>
                        <small class="text-muted">Contactez un administrateur pour modifier ces informations.</small>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Tél. professionnel</label>
                        <input type="text" name="tel_pro" class="form-control" value="<?= e($user['tel_pro']) ?>">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Ligne interne</label>
                        <input type="text" name="ligne_interne" class="form-control" value="<?= e($user['ligne_interne']) ?>">
                    </div>
                    <div class="col-12">
                        <button type="submit" class="btn btn-ce"><i class="fas fa-save"></i> Enregistrer</button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Changement de mot de passe -->
    <div class="col-md-5">
        <div class="dashboard-card">
            <h4><i class="fas fa-lock"></i> Changer le mot de passe</h4>
            <form method="POST">
                <input type="hidden" name="action" value="change_password">
                <div class="row g-3">
                    <div class="col-12">
                        <label class="form-label">Mot de passe actuel *</label>
                        <input type="password" name="current_password" class="form-control" required>
                    </div>
                    <div class="col-12">
                        <label class="form-label">Nouveau mot de passe *</label>
                        <input type="password" name="new_password" class="form-control" required minlength="6">
                        <small class="text-muted">6 caractères minimum</small>
                    </div>
                    <div class="col-12">
                        <label class="form-label">Confirmer le nouveau mot de passe *</label>
                        <input type="password" name="confirm_password" class="form-control" required>
                    </div>
                    <div class="col-12">
                        <button type="submit" class="btn btn-ce"><i class="fas fa-key"></i> Modifier le mot de passe</button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Informations du compte -->
    <div class="col-12">
        <div class="dashboard-card">
            <h4><i class="fas fa-info-circle"></i> Informations du compte</h4>
            <div class="row g-3">
                <div class="col-md-4">
                    <p class="text-muted mb-1">Compte créé le</p>
                    <p><strong><?= formatDateTime($user['created_at']) ?></strong></p>
                </div>
                <div class="col-md-4">
                    <p class="text-muted mb-1">Dernière modification</p>
                    <p><strong><?= formatDateTime($user['updated_at']) ?></strong></p>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../../templates/footer.php'; ?>
