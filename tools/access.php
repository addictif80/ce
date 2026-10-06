<?php
// Saisie du code d'accès à /tools (affichée seulement quand l'admin a activé la protection)
require_once __DIR__ . '/_layout.php';

$base = toolsBasePath();
// Destination après validation : chemin local uniquement (pas de redirection externe)
$next = (string)($_GET['next'] ?? $_POST['next'] ?? '');
if ($next === '' || $next[0] !== '/' || strpos($next, '//') === 0 || strpos($next, '\\') !== false || strpos($next, "\n") !== false) {
    $next = $base . '/tools/';
}

if (!toolsProtectionEnabled() || toolsAccessGranted()) { header('Location: ' . $next); exit; }

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // 10 essais par 10 minutes et par visiteur
    $rlFile = sys_get_temp_dir() . '/tools_code_' . md5($_SERVER['REMOTE_ADDR'] ?? '');
    $now = time();
    $hits = is_file($rlFile) ? array_filter(array_map('intval', file($rlFile, FILE_IGNORE_NEW_LINES)), fn($t) => $t > $now - 600) : [];
    if (count($hits) >= 10) {
        $error = 'Trop de tentatives. Réessayez dans quelques minutes.';
    } else {
        $row = findToolsCode(trim($_POST['code'] ?? ''));
        if ($row) {
            toolsGrantAccess($row);
            logToolsCodeUse($row['id']);
            header('Location: ' . $next);
            exit;
        }
        $hits[] = $now;
        @file_put_contents($rlFile, implode("\n", $hits));
        $error = 'Code incorrect.';
    }
}

$GLOBALS['toolsNoFeedbackLink'] = true;
$GLOBALS['toolsNoGate'] = true;
toolsHeader('Accès aux outils');
?>
<div class="container my-4" style="max-width:480px">
    <div class="card shadow-sm"><div class="card-body p-4">
        <p class="mb-3"><i class="fas fa-lock me-1"></i>Ces outils sont protégés. Saisissez le code d'accès qui vous a été communiqué.</p>
        <?php if ($error): ?><div class="alert alert-danger py-2"><?= e($error) ?></div><?php endif; ?>
        <form method="post" autocomplete="off">
            <input type="hidden" name="next" value="<?= e($next) ?>">
            <input type="password" name="code" class="form-control form-control-lg mb-3" placeholder="Code d'accès" required autofocus>
            <button type="submit" class="btn btn-danger w-100">Accéder aux outils</button>
        </form>
    </div></div>
</div>
<?php toolsFooter();
