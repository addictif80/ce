<?php
$pageTitle = 'Messagerie';
require_once __DIR__ . '/../../templates/header.php';
$db = getDB();
$userId = getCurrentUserId();
$currentUser = getCurrentUser();

$db->exec("CREATE TABLE IF NOT EXISTS messages (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    contenu TEXT NOT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_created (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

$db->exec("CREATE TABLE IF NOT EXISTS message_vu (
    user_id INT PRIMARY KEY,
    dernier_message_id INT NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

// AJAX : envoyer un message
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    header('Content-Type: application/json');
    if ($_POST['action'] === 'send') {
        $contenu = trim($_POST['contenu'] ?? '');
        if (!$contenu) { echo json_encode(['error' => 'Message vide']); exit; }
        $contenu = mb_substr($contenu, 0, 2000);
        $stmt = $db->prepare("INSERT INTO messages (user_id, contenu) VALUES (?, ?)");
        $stmt->execute([$userId, $contenu]);
        $msgId = (int)$db->lastInsertId();

        // Notifier les utilisateurs absents depuis plus de 5 min
        $stmtU = $db->prepare("
            SELECT u.id FROM users u
            LEFT JOIN message_vu mv ON mv.user_id = u.id
            WHERE u.id != ? AND (mv.dernier_message_id IS NULL OR mv.dernier_message_id < ?)
        ");
        $stmtU->execute([$userId, $msgId]);
        $nom = ($currentUser['prenom'] ?? '') . ' ' . ($currentUser['nom'] ?? '');
        foreach ($stmtU->fetchAll() as $u) {
            createNotification($u['id'], 'message',
                $nom . ' a envoyé un message',
                mb_substr($contenu, 0, 80),
                APP_URL . '/modules/messagerie/index.php'
            );
        }

        // Marquer comme vu pour l'expéditeur
        $db->prepare("INSERT INTO message_vu (user_id, dernier_message_id) VALUES (?, ?) ON DUPLICATE KEY UPDATE dernier_message_id = VALUES(dernier_message_id)")
            ->execute([$userId, $msgId]);

        echo json_encode(['success' => true, 'id' => $msgId]);
        exit;
    }

    if ($_POST['action'] === 'mark_read') {
        $lastId = (int)($_POST['last_id'] ?? 0);
        if ($lastId) {
            $db->prepare("INSERT INTO message_vu (user_id, dernier_message_id) VALUES (?, ?) ON DUPLICATE KEY UPDATE dernier_message_id = GREATEST(dernier_message_id, VALUES(dernier_message_id))")
                ->execute([$userId, $lastId]);
            // Marquer les notifs messages comme lues
            $db->prepare("UPDATE notifications SET lu = 1 WHERE user_id = ? AND type = 'message'")->execute([$userId]);
        }
        echo json_encode(['success' => true]);
        exit;
    }
    exit;
}

// AJAX : récupérer les messages
if (isset($_GET['action']) && $_GET['action'] === 'messages') {
    header('Content-Type: application/json');
    $after = (int)($_GET['after'] ?? 0);
    $stmt = $db->prepare("
        SELECT m.id, m.user_id, m.contenu, m.created_at, u.nom, u.prenom
        FROM messages m JOIN users u ON m.user_id = u.id
        WHERE m.id > ?
        ORDER BY m.id ASC LIMIT 100
    ");
    $stmt->execute([$after]);
    echo json_encode(['messages' => $stmt->fetchAll(PDO::FETCH_ASSOC)]);
    exit;
}

// Chargement initial (50 derniers messages)
$stmt = $db->query("
    SELECT m.id, m.user_id, m.contenu, m.created_at, u.nom, u.prenom
    FROM messages m JOIN users u ON m.user_id = u.id
    ORDER BY m.id DESC LIMIT 50
");
$initialMessages = array_reverse($stmt->fetchAll(PDO::FETCH_ASSOC));

// Utilisateurs connectés récemment (vu dans les 5 dernières minutes est trop granulaire, on montre juste tous)
$users = $db->query("SELECT id, prenom, nom FROM users ORDER BY prenom, nom")->fetchAll(PDO::FETCH_ASSOC);
?>

<style>
.chat-container { display: flex; flex-direction: column; height: calc(100vh - 140px); background: #fff; border-radius: 12px; overflow: hidden; box-shadow: 0 2px 8px rgba(0,0,0,0.06); }
.chat-header { padding: 14px 20px; border-bottom: 1px solid #eee; display: flex; align-items: center; gap: 10px; flex-shrink: 0; }
.chat-header h4 { margin: 0; font-size: 16px; }
.chat-header .members { font-size: 12px; color: #999; }
.chat-body { flex: 1; overflow-y: auto; padding: 20px; display: flex; flex-direction: column; gap: 10px; }
.chat-input-area { padding: 14px 16px; border-top: 1px solid #eee; display: flex; gap: 10px; align-items: flex-end; flex-shrink: 0; background: #fafafa; }
.chat-input { flex: 1; border: 1px solid #ddd; border-radius: 20px; padding: 10px 18px; font-size: 14px; resize: none; max-height: 120px; outline: none; transition: border 0.2s; font-family: inherit; }
.chat-input:focus { border-color: var(--ce-red); }
.chat-send { background: var(--ce-red); color: #fff; border: none; border-radius: 50%; width: 40px; height: 40px; display: flex; align-items: center; justify-content: center; cursor: pointer; flex-shrink: 0; transition: background 0.2s; }
.chat-send:hover { background: var(--ce-red-dark, #c40025); }
.msg-row { display: flex; gap: 8px; align-items: flex-end; max-width: 80%; }
.msg-row.own { align-self: flex-end; flex-direction: row-reverse; }
.msg-avatar { width: 30px; height: 30px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 11px; font-weight: 700; color: #fff; flex-shrink: 0; }
.msg-bubble-wrap { display: flex; flex-direction: column; gap: 2px; }
.msg-row.own .msg-bubble-wrap { align-items: flex-end; }
.msg-name { font-size: 11px; color: #aaa; padding: 0 10px; }
.msg-bubble { padding: 9px 14px; border-radius: 18px; font-size: 14px; line-height: 1.5; word-break: break-word; white-space: pre-wrap; }
.msg-row:not(.own) .msg-bubble { background: #f0f0f0; color: #333; border-bottom-left-radius: 4px; }
.msg-row.own .msg-bubble { background: var(--ce-red); color: #fff; border-bottom-right-radius: 4px; }
.msg-time { font-size: 10px; color: #ccc; padding: 0 10px; }
.chat-day { text-align: center; color: #bbb; font-size: 11px; margin: 8px 0; }
.no-messages { color: #bbb; text-align: center; margin: auto; font-size: 14px; }
</style>

<div class="chat-container">
    <div class="chat-header">
        <i class="fas fa-comments" style="color:var(--ce-red);font-size:18px"></i>
        <div>
            <h4>Discussion générale</h4>
            <div class="members"><i class="fas fa-users"></i> <?= count($users) ?> membre<?= count($users) > 1 ? 's' : '' ?></div>
        </div>
    </div>
    <div class="chat-body" id="chatBody">
        <?php if (empty($initialMessages)): ?>
            <div class="no-messages"><i class="fas fa-comment-slash fa-2x mb-2 d-block"></i> Aucun message pour l'instant</div>
        <?php endif; ?>
    </div>
    <div class="chat-input-area">
        <textarea class="chat-input" id="chatInput" rows="1" placeholder="Écrire un message..." onkeydown="handleKey(event)" oninput="autoResize(this)"></textarea>
        <button class="chat-send" onclick="sendMessage()" title="Envoyer"><i class="fas fa-paper-plane"></i></button>
    </div>
</div>

<script>
const ME = <?= $userId ?>;
const B = '<?= $B ?>';
const initialMessages = <?= json_encode($initialMessages) ?>;
let lastId = 0;
let pollTimer = null;

const COLORS = ['#e4002b','#2980b9','#27ae60','#8e44ad','#d35400','#2c3e50','#16a085','#c0392b','#1abc9c','#e67e22'];
function userColor(uid) { return COLORS[uid % COLORS.length]; }
function initials(prenom, nom) { return ((prenom||'').charAt(0) + (nom||'').charAt(0)).toUpperCase(); }

function escHtml(s){ const d=document.createElement('div'); d.textContent=s||''; return d.innerHTML; }

function fmtTime(dt) {
    const d = new Date(dt.replace(' ','T'));
    return d.toLocaleTimeString('fr-FR',{hour:'2-digit',minute:'2-digit'});
}
function fmtDate(dt) {
    const d = new Date(dt.replace(' ','T'));
    const now = new Date();
    if (d.toDateString() === now.toDateString()) return 'Aujourd\'hui';
    const yest = new Date(now); yest.setDate(yest.getDate()-1);
    if (d.toDateString() === yest.toDateString()) return 'Hier';
    return d.toLocaleDateString('fr-FR',{weekday:'long',day:'numeric',month:'long'});
}

function renderMessage(m) {
    const own = m.user_id == ME;
    const col = userColor(m.user_id);
    return `<div class="msg-row ${own?'own':''}" data-id="${m.id}">
        <div class="msg-avatar" style="background:${col}">${initials(m.prenom,m.nom)}</div>
        <div class="msg-bubble-wrap">
            ${!own ? `<div class="msg-name">${escHtml(m.prenom+' '+m.nom)}</div>` : ''}
            <div class="msg-bubble">${escHtml(m.contenu)}</div>
            <div class="msg-time">${fmtTime(m.created_at)}</div>
        </div>
    </div>`;
}

function appendMessages(messages, scroll) {
    const body = document.getElementById('chatBody');
    let prevDay = body.querySelector('[data-day]')?.dataset?.day || '';
    messages.forEach(m => {
        const day = fmtDate(m.created_at);
        if (day !== prevDay) {
            const sep = document.createElement('div');
            sep.className = 'chat-day'; sep.dataset.day = day;
            sep.textContent = day;
            body.appendChild(sep);
            prevDay = day;
        }
        const div = document.createElement('div');
        div.innerHTML = renderMessage(m);
        body.appendChild(div.firstElementChild);
        lastId = Math.max(lastId, parseInt(m.id));
    });
    const noMsg = body.querySelector('.no-messages');
    if (noMsg && messages.length) noMsg.remove();
    if (scroll) body.scrollTop = body.scrollHeight;
}

// Init
appendMessages(initialMessages, true);
if (lastId) markRead(lastId);

function poll() {
    fetch(`${B}/modules/messagerie/index.php?action=messages&after=${lastId}`)
    .then(r=>r.json()).then(data=>{
        if (data.messages && data.messages.length) {
            const body = document.getElementById('chatBody');
            const atBottom = body.scrollHeight - body.scrollTop - body.clientHeight < 60;
            appendMessages(data.messages, atBottom);
            markRead(lastId);
        }
    }).catch(()=>{}).finally(()=>{ pollTimer = setTimeout(poll, 5000); });
}
pollTimer = setTimeout(poll, 5000);

function markRead(id) {
    fetch(B+'/modules/messagerie/index.php',{method:'POST',headers:{'Content-Type':'application/x-www-form-urlencoded'},body:'action=mark_read&last_id='+id});
}

function sendMessage() {
    const input = document.getElementById('chatInput');
    const contenu = input.value.trim();
    if (!contenu) return;
    input.value = ''; input.style.height = '';
    fetch(B+'/modules/messagerie/index.php',{method:'POST',headers:{'Content-Type':'application/x-www-form-urlencoded'},body:'action=send&contenu='+encodeURIComponent(contenu)})
    .then(r=>r.json()).then(data=>{
        if (data.success) {
            clearTimeout(pollTimer);
            fetch(`${B}/modules/messagerie/index.php?action=messages&after=${lastId}`)
            .then(r=>r.json()).then(d=>{
                if (d.messages?.length) { appendMessages(d.messages, true); markRead(lastId); }
                pollTimer = setTimeout(poll, 5000);
            });
        }
    });
}

function handleKey(e) {
    if (e.key === 'Enter' && !e.shiftKey) { e.preventDefault(); sendMessage(); }
}

function autoResize(el) {
    el.style.height = 'auto';
    el.style.height = Math.min(el.scrollHeight, 120) + 'px';
}

document.addEventListener('visibilitychange', function() {
    if (document.hidden) { clearTimeout(pollTimer); }
    else { clearTimeout(pollTimer); pollTimer = setTimeout(poll, 1000); }
});
</script>

<?php require_once __DIR__ . '/../../templates/footer.php'; ?>
