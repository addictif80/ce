<?php
// ═══════════════════════════════════════════════════════
// AJAX POST – CRUD événements personnels
// ═══════════════════════════════════════════════════════
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['ajax_action'])) {
    require_once __DIR__ . '/../../includes/auth.php';
    require_once __DIR__ . '/../../includes/functions.php';
    requireLogin();
    header('Content-Type: application/json');

    $db     = getDB();
    $userId = getCurrentUserId();
    $action = $_POST['ajax_action'];

    try {
        $db->exec("CREATE TABLE IF NOT EXISTS evenements_perso (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL,
            created_by INT NOT NULL,
            titre VARCHAR(255) NOT NULL,
            description TEXT,
            date_debut DATE NOT NULL,
            date_fin DATE,
            heure_debut TIME,
            heure_fin TIME,
            couleur VARCHAR(7) DEFAULT '#27ae60',
            is_admin_event TINYINT(1) DEFAULT 0,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
            FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    } catch (Exception $e) {}

    if ($action === 'get_event') {
        $id   = (int)($_POST['id'] ?? 0);
        $stmt = $db->prepare("SELECT * FROM evenements_perso WHERE id = ? AND user_id = ?");
        $stmt->execute([$id, $userId]);
        echo json_encode($stmt->fetch(PDO::FETCH_ASSOC) ?: ['error' => 'not_found']);
        exit;
    }

    if ($action === 'add_event') {
        $titre   = trim($_POST['titre'] ?? '');
        $desc    = trim($_POST['description'] ?? '');
        $debut   = $_POST['date_debut'] ?? '';
        $fin     = !empty($_POST['date_fin'])    ? $_POST['date_fin']    : null;
        $h_debut = !empty($_POST['heure_debut']) ? $_POST['heure_debut'] : null;
        $h_fin   = !empty($_POST['heure_fin'])   ? $_POST['heure_fin']   : null;
        $couleur = preg_match('/^#[0-9a-fA-F]{6}$/', $_POST['couleur'] ?? '') ? $_POST['couleur'] : '#27ae60';

        if (empty($titre) || empty($debut)) { echo json_encode(['error' => 'missing_fields']); exit; }

        $stmt = $db->prepare("INSERT INTO evenements_perso
            (user_id, created_by, titre, description, date_debut, date_fin, heure_debut, heure_fin, couleur, is_admin_event)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 0)");
        $stmt->execute([$userId, $userId, $titre, $desc, $debut, $fin, $h_debut, $h_fin, $couleur]);
        echo json_encode(['success' => true, 'id' => $db->lastInsertId()]);
        exit;
    }

    if ($action === 'edit_event') {
        $id   = (int)($_POST['id'] ?? 0);
        $stmt = $db->prepare("SELECT * FROM evenements_perso WHERE id = ? AND user_id = ?");
        $stmt->execute([$id, $userId]);
        $ev = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$ev)                  { echo json_encode(['error' => 'not_found']);  exit; }
        if ($ev['is_admin_event']) { echo json_encode(['error' => 'forbidden']); exit; }

        $titre   = trim($_POST['titre'] ?? '');
        $desc    = trim($_POST['description'] ?? '');
        $debut   = $_POST['date_debut'] ?? '';
        $fin     = !empty($_POST['date_fin'])    ? $_POST['date_fin']    : null;
        $h_debut = !empty($_POST['heure_debut']) ? $_POST['heure_debut'] : null;
        $h_fin   = !empty($_POST['heure_fin'])   ? $_POST['heure_fin']   : null;
        $couleur = preg_match('/^#[0-9a-fA-F]{6}$/', $_POST['couleur'] ?? '') ? $_POST['couleur'] : '#27ae60';

        if (empty($titre) || empty($debut)) { echo json_encode(['error' => 'missing_fields']); exit; }

        $db->prepare("UPDATE evenements_perso SET titre=?, description=?, date_debut=?, date_fin=?,
            heure_debut=?, heure_fin=?, couleur=?, updated_at=NOW() WHERE id=?")
           ->execute([$titre, $desc, $debut, $fin, $h_debut, $h_fin, $couleur, $id]);
        echo json_encode(['success' => true]);
        exit;
    }

    if ($action === 'delete_event') {
        $id   = (int)($_POST['id'] ?? 0);
        $stmt = $db->prepare("SELECT is_admin_event FROM evenements_perso WHERE id = ? AND user_id = ?");
        $stmt->execute([$id, $userId]);
        $ev = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$ev)                  { echo json_encode(['error' => 'not_found']);  exit; }
        if ($ev['is_admin_event']) { echo json_encode(['error' => 'forbidden']); exit; }

        $db->prepare("DELETE FROM evenements_perso WHERE id = ? AND user_id = ?")->execute([$id, $userId]);
        echo json_encode(['success' => true]);
        exit;
    }

    echo json_encode(['error' => 'unknown_action']);
    exit;
}

// ═══════════════════════════════════════════════════════
// AJAX GET – chargement des événements (toutes sources)
// ═══════════════════════════════════════════════════════
if (isset($_GET['action']) && $_GET['action'] === 'events') {
    require_once __DIR__ . '/../../includes/auth.php';
    require_once __DIR__ . '/../../includes/functions.php';
    requireLogin();
    header('Content-Type: application/json');

    $db     = getDB();
    $userId = getCurrentUserId();
    $from   = $_GET['from'] ?? date('Y-m-01');
    $to     = $_GET['to']   ?? date('Y-m-t');
    $events = [];

    // Formations
    try {
        $stmt = $db->prepare("SELECT id, titre, date_debut, date_fin, lieu FROM formations WHERE user_id = ? AND date_fin >= ? AND date_debut <= ?");
        $stmt->execute([$userId, $from, $to]);
        foreach ($stmt->fetchAll() as $f) {
            $events[] = [
                'id' => 'f'.$f['id'], 'title' => $f['titre'],
                'start' => $f['date_debut'], 'end' => $f['date_fin'],
                'type' => 'formation', 'color' => '#2980b9', 'icon' => 'fa-graduation-cap',
                'lien' => 'modules/formations/index.php?open='.$f['id'],
                'detail' => $f['lieu'] ?? '', 'editable' => false, 'is_admin_event' => false,
            ];
        }
    } catch (Exception $e) {}

    // Instances
    try {
        $stmt = $db->prepare("SELECT id, numero_personne, date_echeance, categories FROM instances WHERE user_id = ? AND statut = 'a_faire' AND date_echeance IS NOT NULL AND date_echeance BETWEEN ? AND ?");
        $stmt->execute([$userId, $from, $to]);
        foreach ($stmt->fetchAll() as $i) {
            $overdue  = $i['date_echeance'] < date('Y-m-d');
            $events[] = [
                'id' => 'i'.$i['id'], 'title' => $i['numero_personne'] ?: 'Instance #'.$i['id'],
                'start' => $i['date_echeance'], 'end' => $i['date_echeance'],
                'type' => 'instance', 'color' => $overdue ? '#c0392b' : '#e4002b', 'icon' => 'fa-tasks',
                'lien' => 'modules/instances/index.php?open='.$i['id'],
                'detail' => $i['categories'] ?? '', 'editable' => false, 'is_admin_event' => false,
            ];
        }
    } catch (Exception $e) {}

    // Rappels
    try {
        $stmt = $db->prepare("SELECT id, numero_personne, date_rappel, motif FROM demandes_rappel WHERE user_id = ? AND traitee = 0 AND date_rappel IS NOT NULL AND date_rappel BETWEEN ? AND ?");
        $stmt->execute([$userId, $from, $to]);
        foreach ($stmt->fetchAll() as $r) {
            $events[] = [
                'id' => 'r'.$r['id'], 'title' => $r['numero_personne'] ?: 'Rappel #'.$r['id'],
                'start' => $r['date_rappel'], 'end' => $r['date_rappel'],
                'type' => 'rappel', 'color' => '#d35400', 'icon' => 'fa-phone-alt',
                'lien' => 'modules/rappels/index.php?open='.$r['id'],
                'detail' => mb_substr($r['motif'] ?? '', 0, 60), 'editable' => false, 'is_admin_event' => false,
            ];
        }
    } catch (Exception $e) {}

    // Événements personnels + admin
    try {
        $db->exec("CREATE TABLE IF NOT EXISTS evenements_perso (
            id INT AUTO_INCREMENT PRIMARY KEY, user_id INT NOT NULL, created_by INT NOT NULL,
            titre VARCHAR(255) NOT NULL, description TEXT, date_debut DATE NOT NULL,
            date_fin DATE, heure_debut TIME, heure_fin TIME, couleur VARCHAR(7) DEFAULT '#27ae60',
            is_admin_event TINYINT(1) DEFAULT 0,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
            FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

        $stmt = $db->prepare(
            "SELECT e.*, u.nom AS creator_nom, u.prenom AS creator_prenom
             FROM evenements_perso e LEFT JOIN users u ON e.created_by = u.id
             WHERE e.user_id = ? AND e.date_debut <= ? AND COALESCE(e.date_fin, e.date_debut) >= ?"
        );
        $stmt->execute([$userId, $to, $from]);
        foreach ($stmt->fetchAll() as $ev) {
            $heures = '';
            if ($ev['heure_debut']) {
                $heures = substr($ev['heure_debut'], 0, 5);
                if ($ev['heure_fin']) $heures .= ' – '.substr($ev['heure_fin'], 0, 5);
            }
            $detail = $ev['description'] ?? '';
            if ($heures) $detail = $heures.($detail ? ' · '.$detail : '');
            if ($ev['is_admin_event'] && $ev['creator_nom'])
                $detail .= ($detail ? ' · ' : '').'Par '.$ev['creator_prenom'].' '.$ev['creator_nom'];

            $events[] = [
                'id'             => 'p'.$ev['id'],
                'db_id'          => (int)$ev['id'],
                'title'          => $ev['titre'],
                'start'          => $ev['date_debut'],
                'end'            => $ev['date_fin'] ?: $ev['date_debut'],
                'type'           => 'perso',
                'color'          => $ev['couleur'] ?: '#27ae60',
                'icon'           => $ev['is_admin_event'] ? 'fa-lock' : 'fa-calendar-day',
                'lien'           => null,
                'detail'         => $detail,
                'is_admin_event' => (bool)$ev['is_admin_event'],
                'editable'       => !(bool)$ev['is_admin_event'],
                'heure_debut'    => $ev['heure_debut'] ? substr($ev['heure_debut'], 0, 5) : '',
                'heure_fin'      => $ev['heure_fin']   ? substr($ev['heure_fin'],   0, 5) : '',
                'description'    => $ev['description'] ?? '',
                'couleur'        => $ev['couleur'] ?: '#27ae60',
            ];
        }
    } catch (Exception $e) {}

    echo json_encode($events);
    exit;
}

// ═══════════════════════════════════════════════════════
// Page HTML
// ═══════════════════════════════════════════════════════
$pageTitle = 'Agenda';
require_once __DIR__ . '/../../templates/header.php';
$db     = getDB();
$userId = getCurrentUserId();
?>
<style>
:root { --ce-red: #e4002b; }
.agenda-wrap { background:#fff; border-radius:12px; box-shadow:0 2px 8px rgba(0,0,0,.06); overflow:hidden; }
.agenda-toolbar { display:flex; align-items:center; justify-content:space-between; padding:14px 20px; border-bottom:1px solid #eee; flex-wrap:wrap; gap:10px; }
.agenda-title { font-size:18px; font-weight:600; color:#333; }
.agenda-nav { display:flex; align-items:center; gap:8px; }
.agenda-nav button { background:none; border:1px solid #ddd; border-radius:8px; padding:6px 12px; cursor:pointer; font-size:13px; transition:all .2s; }
.agenda-nav button:hover { background:#f5f5f5; }
.agenda-nav button.today-btn { background:var(--ce-red); color:#fff; border-color:var(--ce-red); }
.btn-add-event { background:var(--ce-red); color:#fff; border:none; border-radius:8px; padding:7px 14px; font-size:13px; cursor:pointer; display:flex; align-items:center; gap:6px; transition:opacity .2s; }
.btn-add-event:hover { opacity:.88; }
.view-btns { display:flex; border:1px solid #ddd; border-radius:8px; overflow:hidden; }
.view-btns button { background:none; border:none; padding:6px 14px; cursor:pointer; font-size:13px; transition:background .2s; }
.view-btns button.active { background:var(--ce-red); color:#fff; }
/* Grille mensuelle */
.cal-grid { display:grid; grid-template-columns:repeat(7,1fr); }
.cal-day-header { padding:8px; text-align:center; font-size:12px; font-weight:600; color:#888; border-bottom:1px solid #eee; background:#fafafa; }
.cal-cell { min-height:90px; border-right:1px solid #f0f0f0; border-bottom:1px solid #f0f0f0; padding:6px; vertical-align:top; transition:background .1s; cursor:pointer; }
.cal-cell:nth-child(7n) { border-right:none; }
.cal-cell:hover { background:#fafafa; }
.cal-cell.other-month { background:#fafafa; }
.cal-cell.today { background:#fff8f8; }
.cal-cell.today .cal-date { background:var(--ce-red); color:#fff; }
.cal-date { display:inline-flex; align-items:center; justify-content:center; width:24px; height:24px; border-radius:50%; font-size:12px; font-weight:500; margin-bottom:4px; }
.cal-event { font-size:11px; padding:2px 6px; border-radius:4px; color:#fff; margin-bottom:2px; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; cursor:pointer; }
.cal-more { font-size:10px; color:#999; cursor:pointer; margin-top:2px; }
/* Vues semaine / jour */
.week-grid { display:grid; }
.week-col-header { padding:10px 8px; text-align:center; border-bottom:2px solid #eee; border-right:1px solid #f0f0f0; font-size:12px; }
.week-col-header.today-col { background:#fff8f8; color:var(--ce-red); font-weight:700; }
.week-slot { height:40px; border-bottom:1px solid #f5f5f5; border-right:1px solid #f0f0f0; position:relative; }
.week-time-label { font-size:10px; color:#ccc; text-align:right; padding-right:6px; line-height:40px; }
.week-event { position:absolute; left:2px; right:2px; border-radius:4px; color:#fff; font-size:11px; padding:2px 5px; cursor:pointer; overflow:hidden; z-index:1; }
.all-day-row { display:flex; border-bottom:1px solid #eee; background:#fafafa; }
.all-day-label { width:60px; font-size:10px; color:#bbb; padding:4px 6px; flex-shrink:0; }
.all-day-cell { flex:1; padding:4px 6px; border-right:1px solid #f0f0f0; min-height:28px; }
/* Légende */
.agenda-legend { display:flex; gap:14px; flex-wrap:wrap; padding:10px 20px; border-top:1px solid #f0f0f0; font-size:12px; color:#666; }
.legend-dot { width:10px; height:10px; border-radius:50%; display:inline-block; margin-right:4px; }
/* Tooltip */
.evt-tooltip { position:fixed; background:#fff; border:1px solid #e0e0e0; border-radius:10px; padding:14px 16px; box-shadow:0 8px 24px rgba(0,0,0,.15); z-index:99999; max-width:280px; display:none; }
.evt-tooltip-title { font-weight:600; font-size:14px; margin-bottom:4px; }
.evt-tooltip-detail { font-size:12px; color:#666; margin-bottom:8px; }
.evt-tooltip-actions { display:flex; gap:6px; }
/* Sélecteur de couleur */
.color-swatches { display:flex; gap:8px; flex-wrap:wrap; margin-top:6px; }
.color-swatch { width:26px; height:26px; border-radius:50%; border:2px solid transparent; cursor:pointer; transition:transform .15s; }
.color-swatch:hover { transform:scale(1.15); }
.color-swatch.selected { border-color:#333; }
@media (max-width:768px) {
    .week-grid { grid-template-columns:40px repeat(7,1fr) !important; }
    .cal-event { display:none; }
    .cal-cell { min-height:50px; }
}
</style>
<div class="agenda-wrap">
    <div class="agenda-toolbar">
        <div class="d-flex align-items-center gap-2">
            <div class="agenda-nav">
                <button onclick="navigate(-1)"><i class="fas fa-chevron-left"></i></button>
                <button class="today-btn" onclick="goToday()">Aujourd'hui</button>
                <button onclick="navigate(1)"><i class="fas fa-chevron-right"></i></button>
            </div>
            <div class="agenda-title" id="agendaTitle"></div>
        </div>
        <div class="d-flex align-items-center gap-2">
            <button class="btn-add-event" onclick="openAddModal(null)">
                <i class="fas fa-plus"></i> Ajouter un événement
            </button>
            <div class="view-btns">
                <button id="btnMois"    onclick="setView('month')" class="active">Mois</button>
                <button id="btnSemaine" onclick="setView('week')">Semaine</button>
                <button id="btnJour"    onclick="setView('day')">Jour</button>
            </div>
        </div>
    </div>

    <div id="agendaBody"></div>

    <div class="agenda-legend">
        <span><span class="legend-dot" style="background:#2980b9"></span>Formation</span>
        <span><span class="legend-dot" style="background:#e4002b"></span>Instance</span>
        <span><span class="legend-dot" style="background:#d35400"></span>Rappel</span>
        <span><span class="legend-dot" style="background:#27ae60"></span>Mon événement</span>
        <span><i class="fas fa-lock" style="font-size:10px;color:#8e44ad;margin-right:4px"></i>Événement admin</span>
    </div>
</div>

<!-- Tooltip événement -->
<div class="evt-tooltip" id="evtTooltip">
    <div class="evt-tooltip-title" id="ttTitle"></div>
    <div class="evt-tooltip-detail" id="ttDetail"></div>
    <div class="evt-tooltip-actions" id="ttActions"></div>
</div>
<!-- Modale ajout / modification événement personnel -->
<div class="modal fade" id="modalEvent" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="modalEventTitle">Nouvel événement</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <input type="hidden" id="evtId">
        <div class="mb-3">
          <label class="form-label fw-semibold">Titre <span class="text-danger">*</span></label>
          <input type="text" class="form-control" id="evtTitre" placeholder="Ex : Réunion d'équipe" maxlength="255">
        </div>
        <div class="row g-2 mb-3">
          <div class="col-6">
            <label class="form-label fw-semibold">Date début <span class="text-danger">*</span></label>
            <input type="date" class="form-control" id="evtDateDebut">
          </div>
          <div class="col-6">
            <label class="form-label fw-semibold">Date fin</label>
            <input type="date" class="form-control" id="evtDateFin">
          </div>
        </div>
        <div class="row g-2 mb-3">
          <div class="col-6">
            <label class="form-label">Heure début</label>
            <input type="time" class="form-control" id="evtHeureDebut">
          </div>
          <div class="col-6">
            <label class="form-label">Heure fin</label>
            <input type="time" class="form-control" id="evtHeureFin">
          </div>
        </div>
        <div class="mb-3">
          <label class="form-label">Description</label>
          <textarea class="form-control" id="evtDescription" rows="2" placeholder="Détails optionnels..."></textarea>
        </div>
        <div class="mb-2">
          <label class="form-label">Couleur</label>
          <div class="color-swatches" id="colorSwatches"></div>
          <input type="hidden" id="evtCouleur" value="#27ae60">
        </div>
        <div id="evtError" class="alert alert-danger d-none mt-2"></div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
        <button type="button" class="btn btn-ce" onclick="saveEvent()">
          <i class="fas fa-save"></i> Enregistrer
        </button>
      </div>
    </div>
  </div>
</div>
<script>
const B = '<?= $B ?>';
const DAYS_FR   = ['Lun','Mar','Mer','Jeu','Ven','Sam','Dim'];
const MONTHS_FR = ['Janvier','Février','Mars','Avril','Mai','Juin','Juillet','Août','Septembre','Octobre','Novembre','Décembre'];
const COLORS    = ['#27ae60','#2980b9','#8e44ad','#e67e22','#e74c3c','#16a085','#2c3e50','#f39c12','#c0392b','#1abc9c'];

let view      = 'month';
let current   = new Date(); current.setDate(1);
let allEvents = [];

const fmt        = d => d.toISOString().slice(0,10);
const addDays    = (d,n) => { const r=new Date(d); r.setDate(r.getDate()+n); return r; };
const startOfWeek= d => { const r=new Date(d); const day=r.getDay()||7; r.setDate(r.getDate()-(day-1)); return r; };
const isSameDay  = (a,b) => a.toDateString()===b.toDateString();

// ── Chargement événements ──────────────────────────────
async function loadEvents(from, to) {
    const res = await fetch(`${B}/modules/agenda/index.php?action=events&from=${from}&to=${to}`);
    allEvents = await res.json();
}

function getEventsForDay(ds) {
    return allEvents.filter(e => e.start && e.start <= ds && (e.end||e.start) >= ds);
}

// ── Tooltip ───────────────────────────────────────────
function showTooltip(evt, ev) {
    const tt = document.getElementById('evtTooltip');
    document.getElementById('ttTitle').textContent  = ev.title;
    document.getElementById('ttDetail').textContent = ev.detail || '';

    let actionsHtml = '';
    if (ev.lien) {
        actionsHtml += `<a href="${B}/${ev.lien}" class="btn btn-sm btn-ce" style="font-size:12px"><i class="fas fa-eye"></i> Voir</a>`;
    }
    if (ev.editable) {
        actionsHtml += `<button class="btn btn-sm btn-outline-secondary" style="font-size:12px" onclick="openEditModal(${JSON.stringify(ev).replace(/"/g,'&quot;')})"><i class="fas fa-pencil-alt"></i></button>`;
        actionsHtml += `<button class="btn btn-sm btn-outline-danger"    style="font-size:12px" onclick="confirmDelete(${ev.db_id})"><i class="fas fa-trash"></i></button>`;
    } else if (ev.is_admin_event) {
        actionsHtml += `<span class="badge" style="background:#8e44ad;font-size:11px"><i class="fas fa-lock"></i> Événement admin</span>`;
    }
    document.getElementById('ttActions').innerHTML = actionsHtml;

    const r = evt.currentTarget ? evt.currentTarget.getBoundingClientRect() : evt.target.getBoundingClientRect();
    tt.style.display = 'block';
    tt.style.left    = Math.min(r.left, window.innerWidth - 300) + 'px';
    tt.style.top     = (r.bottom + 8) + 'px';
    evt.stopPropagation();
}
document.addEventListener('click', () => { document.getElementById('evtTooltip').style.display='none'; });

// ── Vue mois ──────────────────────────────────────────
function renderMonth() {
    const y=current.getFullYear(), m=current.getMonth();
    document.getElementById('agendaTitle').textContent = MONTHS_FR[m]+' '+y;

    const first=new Date(y,m,1), last=new Date(y,m+1,0);
    const startDow=(first.getDay()||7)-1;
    let html='<div class="cal-grid">';
    DAYS_FR.forEach(d => { html+=`<div class="cal-day-header">${d}</div>`; });

    let day=addDays(first,-startDow);
    const today=new Date(); today.setHours(0,0,0,0);
    for (let w=0;w<6;w++) {
        for (let d=0;d<7;d++) {
            const ds=fmt(day), isToday=isSameDay(day,today), otherM=day.getMonth()!==m;
            const evs=getEventsForDay(ds);
            html+=`<div class="cal-cell${otherM?' other-month':''}${isToday?' today':''}" data-date="${ds}" onclick="cellClick(event,'${ds}')">`;
            html+=`<div class="cal-date">${day.getDate()}</div>`;
            evs.slice(0,3).forEach(ev => {
                const evJson=JSON.stringify(ev).replace(/"/g,'&quot;');
                html+=`<div class="cal-event" style="background:${ev.color}" onclick="event.stopPropagation();showTooltip(event,${evJson})" title="${ev.title}">${ev.title}</div>`;
            });
            if (evs.length>3) html+=`<div class="cal-more">+${evs.length-3} autre(s)</div>`;
            html+='</div>';
            day=addDays(day,1);
        }
        if (day>last && w>=4) break;
    }
    html+='</div>';
    document.getElementById('agendaBody').innerHTML=html;
}

// ── Vue semaine / jour ────────────────────────────────
function renderWeek() {
    const weekStart = view==='day' ? new Date(current) : startOfWeek(current);
    const days      = view==='day' ? 1 : 7;
    if (view==='day') {
        document.getElementById('agendaTitle').textContent = current.toLocaleDateString('fr-FR',{weekday:'long',day:'numeric',month:'long',year:'numeric'});
    } else {
        const wEnd=addDays(weekStart,6);
        document.getElementById('agendaTitle').textContent='Semaine du '+weekStart.getDate()+' au '+wEnd.getDate()+' '+MONTHS_FR[wEnd.getMonth()]+' '+wEnd.getFullYear();
    }
    const today=new Date(); today.setHours(0,0,0,0);
    const cols=view==='day' ? 'grid-template-columns:60px 1fr' : 'grid-template-columns:60px repeat(7,1fr)';

    let allDayHtml=`<div class="all-day-row"><div class="all-day-label" style="line-height:28px">Journée</div>`;
    for (let d=0;d<days;d++) {
        const day2=addDays(weekStart,d), ds=fmt(day2);
        const evs=getEventsForDay(ds);
        allDayHtml+=`<div class="all-day-cell">`;
        evs.forEach(ev => {
            const evJson=JSON.stringify(ev).replace(/"/g,'&quot;');
            allDayHtml+=`<div class="cal-event" style="background:${ev.color};font-size:11px;padding:2px 6px;margin-bottom:2px" onclick="showTooltip(event,${evJson})">${ev.title}</div>`;
        });
        allDayHtml+='</div>';
    }
    allDayHtml+='</div>';

    let html=`<div style="${cols}" class="week-grid">`;
    html+='<div class="week-col-header" style="border-right:1px solid #f0f0f0"></div>';
    for (let d=0;d<days;d++) {
        const day2=addDays(weekStart,d), isToday=isSameDay(day2,today);
        html+=`<div class="week-col-header${isToday?' today-col':''}"><div>${DAYS_FR[day2.getDay()||7-1]}</div><div style="font-size:18px;font-weight:700">${day2.getDate()}</div></div>`;
    }
    for (let h=8;h<=20;h++) {
        html+=`<div class="week-slot"><div class="week-time-label">${h}h</div></div>`;
        for (let d=0;d<days;d++) html+='<div class="week-slot"></div>';
    }
    html+='</div>';
    document.getElementById('agendaBody').innerHTML=allDayHtml+html;
}

// ── Navigation ────────────────────────────────────────
function navigate(dir) {
    if (view==='month')      current.setMonth(current.getMonth()+dir);
    else if (view==='week')  current=addDays(current,dir*7);
    else                     current=addDays(current,dir);
    refresh();
}
function goToday() { current=new Date(); if(view==='month') current.setDate(1); refresh(); }
function setView(v) {
    view=v;
    document.querySelectorAll('.view-btns button').forEach(b=>b.classList.remove('active'));
    document.getElementById('btn'+v.charAt(0).toUpperCase()+v.slice(1)).classList.add('active');
    if(v==='month') current.setDate(1);
    refresh();
}
async function refresh() {
    let from,to;
    if (view==='month') {
        const y=current.getFullYear(),m=current.getMonth();
        from=fmt(addDays(new Date(y,m,1),-6));
        to  =fmt(addDays(new Date(y,m+1,0),6));
    } else if (view==='week') {
        const ws=startOfWeek(current);
        from=fmt(ws); to=fmt(addDays(ws,6));
    } else {
        from=to=fmt(current);
    }
    try { await loadEvents(from,to); } catch(e) { allEvents=[]; }
    view==='month' ? renderMonth() : renderWeek();
}

// ── Clic sur une case (vue mois) ──────────────────────
function cellClick(evt, date) {
    if (evt.target.classList.contains('cal-event') || evt.target.classList.contains('cal-more')) return;
    openAddModal(date);
}

// ── Sélecteur de couleurs ─────────────────────────────
function buildColorSwatches(selected) {
    const container=document.getElementById('colorSwatches');
    container.innerHTML='';
    COLORS.forEach(c => {
        const div=document.createElement('div');
        div.className='color-swatch'+(c===selected?' selected':'');
        div.style.background=c;
        div.title=c;
        div.onclick=()=>{ selectColor(c); };
        container.appendChild(div);
    });
    document.getElementById('evtCouleur').value=selected;
}
function selectColor(c) {
    document.getElementById('evtCouleur').value=c;
    document.querySelectorAll('.color-swatch').forEach(s=>{ s.classList.toggle('selected',s.style.background===c||rgbToHex(s.style.background)===c); });
}
function rgbToHex(rgb) {
    const m=rgb.match(/\d+/g); if(!m) return rgb;
    return '#'+m.slice(0,3).map(x=>parseInt(x).toString(16).padStart(2,'0')).join('');
}

// ── Modale Ajouter ────────────────────────────────────
function openAddModal(date) {
    document.getElementById('evtId').value='';
    document.getElementById('evtTitre').value='';
    document.getElementById('evtDescription').value='';
    document.getElementById('evtDateDebut').value=date||fmt(new Date());
    document.getElementById('evtDateFin').value='';
    document.getElementById('evtHeureDebut').value='';
    document.getElementById('evtHeureFin').value='';
    document.getElementById('evtError').classList.add('d-none');
    document.getElementById('modalEventTitle').textContent='Nouvel événement';
    buildColorSwatches('#27ae60');
    new bootstrap.Modal(document.getElementById('modalEvent')).show();
}

// ── Modale Modifier ───────────────────────────────────
function openEditModal(ev) {
    document.getElementById('evtId').value=ev.db_id;
    document.getElementById('evtTitre').value=ev.title;
    document.getElementById('evtDescription').value=ev.description||'';
    document.getElementById('evtDateDebut').value=ev.start;
    document.getElementById('evtDateFin').value=ev.end!==ev.start?ev.end:'';
    document.getElementById('evtHeureDebut').value=ev.heure_debut||'';
    document.getElementById('evtHeureFin').value=ev.heure_fin||'';
    document.getElementById('evtError').classList.add('d-none');
    document.getElementById('modalEventTitle').textContent='Modifier l\'événement';
    buildColorSwatches(ev.couleur||'#27ae60');
    document.getElementById('evtTooltip').style.display='none';
    new bootstrap.Modal(document.getElementById('modalEvent')).show();
}

// ── Enregistrement (add ou edit) ──────────────────────
async function saveEvent() {
    const id      = document.getElementById('evtId').value;
    const titre   = document.getElementById('evtTitre').value.trim();
    const debut   = document.getElementById('evtDateDebut').value;
    const errDiv  = document.getElementById('evtError');
    errDiv.classList.add('d-none');
    if (!titre || !debut) { errDiv.textContent='Le titre et la date de début sont obligatoires.'; errDiv.classList.remove('d-none'); return; }

    const body=new FormData();
    body.append('ajax_action', id ? 'edit_event' : 'add_event');
    if (id) body.append('id', id);
    body.append('titre',       titre);
    body.append('description', document.getElementById('evtDescription').value);
    body.append('date_debut',  debut);
    body.append('date_fin',    document.getElementById('evtDateFin').value);
    body.append('heure_debut', document.getElementById('evtHeureDebut').value);
    body.append('heure_fin',   document.getElementById('evtHeureFin').value);
    body.append('couleur',     document.getElementById('evtCouleur').value);

    const res  = await fetch(`${B}/modules/agenda/index.php`, {method:'POST', body});
    const data = await res.json();
    if (data.error) { errDiv.textContent='Erreur : '+data.error; errDiv.classList.remove('d-none'); return; }

    bootstrap.Modal.getInstance(document.getElementById('modalEvent')).hide();
    refresh();
}

// ── Suppression ───────────────────────────────────────
async function confirmDelete(id) {
    if (!confirm('Supprimer cet événement ?')) return;
    document.getElementById('evtTooltip').style.display='none';
    const body=new FormData();
    body.append('ajax_action','delete_event');
    body.append('id', id);
    await fetch(`${B}/modules/agenda/index.php`, {method:'POST', body});
    refresh();
}

// ── Init ──────────────────────────────────────────────
document.getElementById('btnMois').classList.add('active');
setView('month');
</script>
<?php require_once __DIR__ . '/../../templates/footer.php'; ?>
