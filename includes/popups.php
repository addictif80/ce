<?php
/**
 * Fenêtres popup d'information (messages de l'administrateur) affichées sur le tableau de bord du portail et/ou sur /tools.
 * Deux popups indépendantes (contenu, dates, fréquence) ; chacune choisit où elle s'affiche : tableau de bord, /tools, ou les deux.
 * Sur /tools, la popup s'affiche une fois le code d'accès validé (ou directement si /tools n'est pas protégé).
 * Réglages stockés dans tools_settings (clés popup1_*, popup2_*).
 */
function popupSlots() { return ['popup1' => 'Popup 1', 'popup2' => 'Popup 2']; }

function popupFrequencies() {
    return [
        'session' => 'Une fois par session de navigation',
        'daily' => 'Une fois par jour',
        'once' => 'Une seule fois (réaffichée si le message est modifié)',
        'always' => 'À chaque visite de la page',
    ];
}

function popupDefaults($slot) {
    return ['enabled' => 0, 'title' => '', 'html' => '', 'button' => 'Fermer', 'dashboard' => $slot === 'popup1' ? 1 : 0, 'tools' => $slot === 'popup2' ? 1 : 0,
        'frequency' => 'session', 'start' => '', 'end' => '', 'rev' => 0];
}

function getPopupSettings($slot) {
    ensureToolsAccessSchema();
    $out = popupDefaults($slot);
    $st = getDB()->prepare("SELECT k, v FROM tools_settings WHERE k LIKE ?");
    $st->execute([$slot . '\_%']);
    foreach ($st->fetchAll(PDO::FETCH_KEY_PAIR) as $k => $v) {
        $f = substr($k, strlen($slot) + 1);
        if (array_key_exists($f, $out)) $out[$f] = $v;
    }
    foreach (['enabled', 'dashboard', 'tools', 'rev'] as $f) $out[$f] = (int)$out[$f];
    if (!isset(popupFrequencies()[$out['frequency']])) $out['frequency'] = 'session';
    return $out;
}

function savePopupSettings($slot, array $p) {
    $date = fn($v) => preg_match('/^\d{4}-\d{2}-\d{2}$/', (string)$v) ? $v : '';
    $cur = getPopupSettings($slot);
    $html = sanitizeToolsMessageHtml($p['html'] ?? '');
    $vals = [
        'enabled' => isset($p['enabled']) ? '1' : '0',
        'title' => mb_substr(trim((string)($p['title'] ?? '')), 0, 150),
        'html' => $html,
        'button' => mb_substr(trim((string)($p['button'] ?? '')), 0, 40) ?: 'Fermer',
        'dashboard' => isset($p['dashboard']) ? '1' : '0',
        'tools' => isset($p['tools']) ? '1' : '0',
        'frequency' => isset(popupFrequencies()[$p['frequency'] ?? '']) ? $p['frequency'] : 'session',
        'start' => $date($p['start'] ?? ''), 'end' => $date($p['end'] ?? ''),
        // numéro de version : change à chaque enregistrement, ce qui réaffiche une popup « une seule fois »
        'rev' => (string)(((int)$cur['rev']) + 1),
    ];
    foreach ($vals as $f => $v) setToolsSetting($slot . '_' . $f, $v);
}

/** Popups à afficher sur $where ('dashboard' ou 'tools') : actives, avec contenu, dans leur période de validité. */
function getActivePopups($where) {
    $today = date('Y-m-d');
    $out = [];
    foreach (array_keys(popupSlots()) as $slot) {
        $p = getPopupSettings($slot);
        if (!$p['enabled'] || !$p[$where] || trim(strip_tags($p['html'])) === '') continue;
        if ($p['start'] !== '' && $today < $p['start']) continue;
        if ($p['end'] !== '' && $today > $p['end']) continue;
        $p['slot'] = $slot;
        $out[] = $p;
    }
    return $out;
}

/** Affiche les popups de $where (fenêtres Bootstrap affichées l'une après l'autre) ; à appeler en bas de page, Bootstrap JS étant chargé. */
function renderPopups($where) {
    $list = getActivePopups($where);
    if (!$list) return;
    $meta = [];
    foreach ($list as $p) {
        $meta[] = ['id' => 'pp-' . $p['slot'], 'key' => 'popup_' . $p['slot'] . '_' . $where . '_' . $p['rev'], 'freq' => $p['frequency']];
        ?>
<div class="modal fade" id="pp-<?= e($p['slot']) ?>" tabindex="-1" aria-hidden="true"><div class="modal-dialog modal-dialog-centered modal-lg"><div class="modal-content">
    <?php if ($p['title'] !== ''): ?><div class="modal-header"><h5 class="modal-title"><?= e($p['title']) ?></h5><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fermer"></button></div><?php endif; ?>
    <div class="modal-body popup-body"><?= $p['html'] /* HTML assaini à l'enregistrement par l'administrateur */ ?></div>
    <div class="modal-footer"><button type="button" class="btn btn-danger" data-bs-dismiss="modal"><?= e($p['button']) ?></button></div>
</div></div></div>
<?php } ?>
<style>.popup-body > :last-child{margin-bottom:0}.popup-body img{max-width:100%;height:auto}</style>
<script>
(function () {
    const popups = <?= json_encode($meta) ?>, today = new Date().toISOString().slice(0, 10);
    const get = (s, k) => { try { return s.getItem(k); } catch (e) { return null; } };
    const set = (s, k, v) => { try { s.setItem(k, v); } catch (e) {} };
    // Une popup est affichée si sa fréquence l'autorise (stockage local au navigateur, rien n'est envoyé au serveur)
    function due(p) {
        if (p.freq === 'always') return true;
        if (p.freq === 'session') return !get(sessionStorage, p.key);
        if (p.freq === 'daily') return get(localStorage, p.key) !== today;
        return !get(localStorage, p.key);
    }
    function seen(p) {
        if (p.freq === 'session') set(sessionStorage, p.key, '1');
        else if (p.freq === 'daily') set(localStorage, p.key, today);
        else if (p.freq === 'once') set(localStorage, p.key, '1');
    }
    const queue = popups.filter(due);
    function next() {
        const p = queue.shift(); if (!p) return;
        const el = document.getElementById(p.id), m = new bootstrap.Modal(el);
        el.addEventListener('hidden.bs.modal', () => setTimeout(next, 250), {once: true});
        seen(p); m.show();
    }
    window.addEventListener('load', next);
})();
</script>
<?php }
