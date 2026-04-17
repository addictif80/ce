<?php
$pageTitle = 'Agenda';
require_once __DIR__ . '/../../templates/header.php';
$db = getDB();
$userId = getCurrentUserId();

// Chargement des événements pour le mois affiché (±2 semaines tampon)
if (isset($_GET['action']) && $_GET['action'] === 'events') {
    header('Content-Type: application/json');
    $from = $_GET['from'] ?? date('Y-m-01');
    $to   = $_GET['to']   ?? date('Y-m-t');

    $events = [];

    // Formations
    try {
        $stmt = $db->prepare("SELECT id, titre, date_debut, date_fin, lieu FROM formations WHERE user_id = ? AND date_fin >= ? AND date_debut <= ?");
        $stmt->execute([$userId, $from, $to]);
        foreach ($stmt->fetchAll() as $f) {
            $events[] = [
                'id'    => 'f' . $f['id'],
                'title' => $f['titre'],
                'start' => $f['date_debut'],
                'end'   => $f['date_fin'],
                'type'  => 'formation',
                'color' => '#2980b9',
                'icon'  => 'fa-graduation-cap',
                'lien'  => 'modules/formations/index.php?open=' . $f['id'],
                'detail'=> $f['lieu'] ?? '',
            ];
        }
    } catch (Exception $e) {}

    // Instances (date_echeance)
    try {
        $stmt = $db->prepare("SELECT id, numero_personne, date_echeance, categories, statut FROM instances WHERE user_id = ? AND statut = 'a_faire' AND date_echeance IS NOT NULL AND date_echeance BETWEEN ? AND ?");
        $stmt->execute([$userId, $from, $to]);
        foreach ($stmt->fetchAll() as $i) {
            $overdue = $i['date_echeance'] < date('Y-m-d');
            $events[] = [
                'id'    => 'i' . $i['id'],
                'title' => $i['numero_personne'] ?: 'Instance #' . $i['id'],
                'start' => $i['date_echeance'],
                'end'   => $i['date_echeance'],
                'type'  => 'instance',
                'color' => $overdue ? '#c0392b' : '#e4002b',
                'icon'  => 'fa-tasks',
                'lien'  => 'modules/instances/index.php?open=' . $i['id'],
                'detail'=> $i['categories'] ?? '',
            ];
        }
    } catch (Exception $e) {}

    // Rappels (demandes_rappel)
    try {
        $stmt = $db->prepare("SELECT id, numero_personne, date_rappel, motif FROM demandes_rappel WHERE user_id = ? AND traitee = 0 AND date_rappel IS NOT NULL AND date_rappel BETWEEN ? AND ?");
        $stmt->execute([$userId, $from, $to]);
        foreach ($stmt->fetchAll() as $r) {
            $events[] = [
                'id'    => 'r' . $r['id'],
                'title' => $r['numero_personne'] ?: 'Rappel #' . $r['id'],
                'start' => $r['date_rappel'],
                'end'   => $r['date_rappel'],
                'type'  => 'rappel',
                'color' => '#d35400',
                'icon'  => 'fa-phone-alt',
                'lien'  => 'modules/rappels/index.php?open=' . $r['id'],
                'detail'=> mb_substr($r['motif'] ?? '', 0, 60),
            ];
        }
    } catch (Exception $e) {}

    echo json_encode($events);
    exit;
}
?>

<style>
:root { --ce-red: #e4002b; }
.agenda-wrap { background: #fff; border-radius: 12px; box-shadow: 0 2px 8px rgba(0,0,0,0.06); overflow: hidden; }
.agenda-toolbar { display: flex; align-items: center; justify-content: space-between; padding: 14px 20px; border-bottom: 1px solid #eee; flex-wrap: wrap; gap: 10px; }
.agenda-title { font-size: 18px; font-weight: 600; color: #333; }
.agenda-nav { display: flex; align-items: center; gap: 8px; }
.agenda-nav button { background: none; border: 1px solid #ddd; border-radius: 8px; padding: 6px 12px; cursor: pointer; font-size: 13px; transition: all 0.2s; }
.agenda-nav button:hover { background: #f5f5f5; }
.agenda-nav button.today-btn { background: var(--ce-red); color: #fff; border-color: var(--ce-red); }
.view-btns { display: flex; border: 1px solid #ddd; border-radius: 8px; overflow: hidden; }
.view-btns button { background: none; border: none; padding: 6px 14px; cursor: pointer; font-size: 13px; transition: background 0.2s; }
.view-btns button.active { background: var(--ce-red); color: #fff; }

/* Monthly grid */
.cal-grid { display: grid; grid-template-columns: repeat(7, 1fr); }
.cal-day-header { padding: 8px; text-align: center; font-size: 12px; font-weight: 600; color: #888; border-bottom: 1px solid #eee; background: #fafafa; }
.cal-cell { min-height: 90px; border-right: 1px solid #f0f0f0; border-bottom: 1px solid #f0f0f0; padding: 6px; vertical-align: top; transition: background 0.1s; cursor: pointer; }
.cal-cell:nth-child(7n) { border-right: none; }
.cal-cell:hover { background: #fafafa; }
.cal-cell.other-month { background: #fafafa; }
.cal-cell.today { background: #fff8f8; }
.cal-cell.today .cal-date { background: var(--ce-red); color: #fff; }
.cal-date { display: inline-flex; align-items: center; justify-content: center; width: 24px; height: 24px; border-radius: 50%; font-size: 12px; font-weight: 500; margin-bottom: 4px; }
.cal-event { font-size: 11px; padding: 2px 6px; border-radius: 4px; color: #fff; margin-bottom: 2px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; cursor: pointer; }
.cal-more { font-size: 10px; color: #999; cursor: pointer; margin-top: 2px; }

/* Week / Day grid */
.week-grid { display: grid; }
.week-grid.week-view { grid-template-columns: 60px repeat(7, 1fr); }
.week-grid.day-view { grid-template-columns: 60px 1fr; }
.week-col-header { padding: 10px 8px; text-align: center; border-bottom: 2px solid #eee; border-right: 1px solid #f0f0f0; font-size: 12px; }
.week-col-header.today-col { background: #fff8f8; color: var(--ce-red); font-weight: 700; }
.week-time-col { padding: 0; }
.week-slot { height: 40px; border-bottom: 1px solid #f5f5f5; border-right: 1px solid #f0f0f0; position: relative; }
.week-time-label { font-size: 10px; color: #ccc; text-align: right; padding-right: 6px; line-height: 40px; }
.week-event { position: absolute; left: 2px; right: 2px; border-radius: 4px; color: #fff; font-size: 11px; padding: 2px 5px; cursor: pointer; overflow: hidden; z-index: 1; }
.all-day-row { display: flex; border-bottom: 1px solid #eee; background: #fafafa; }
.all-day-label { width: 60px; font-size: 10px; color: #bbb; padding: 4px 6px; flex-shrink: 0; }
.all-day-cell { flex: 1; padding: 4px 6px; border-right: 1px solid #f0f0f0; min-height: 28px; }

/* List view (no-date events) */
.agenda-list { padding: 16px 20px; }
.agenda-list-item { display: flex; gap: 14px; padding: 12px 0; border-bottom: 1px solid #f5f5f5; cursor: pointer; }
.agenda-list-item:hover { background: #fafafa; margin: 0 -20px; padding: 12px 20px; }
.agenda-list-dot { width: 10px; height: 10px; border-radius: 50%; flex-shrink: 0; margin-top: 5px; }
.agenda-list-date { width: 100px; font-size: 12px; color: #888; flex-shrink: 0; }
.agenda-list-title { font-weight: 500; font-size: 14px; }
.agenda-list-detail { font-size: 12px; color: #aaa; }
.agenda-empty { text-align: center; padding: 40px; color: #bbb; font-size: 14px; }

/* Legend */
.agenda-legend { display: flex; gap: 14px; flex-wrap: wrap; padding: 10px 20px; border-top: 1px solid #f0f0f0; font-size: 12px; color: #666; }
.legend-dot { width: 10px; height: 10px; border-radius: 50%; display: inline-block; margin-right: 4px; }

/* Event detail tooltip */
.evt-tooltip { position: fixed; background: #fff; border: 1px solid #e0e0e0; border-radius: 10px; padding: 14px 16px; box-shadow: 0 8px 24px rgba(0,0,0,0.15); z-index: 99999; max-width: 280px; display: none; }
.evt-tooltip-title { font-weight: 600; font-size: 14px; margin-bottom: 6px; }
.evt-tooltip-detail { font-size: 12px; color: #666; margin-bottom: 8px; }

@media (max-width: 768px) {
    .week-grid.week-view { grid-template-columns: 40px repeat(7,1fr); }
    .cal-event { display: none; }
    .cal-cell { min-height: 50px; }
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
        <div class="view-btns">
            <button id="btnMois" onclick="setView('month')" class="active">Mois</button>
            <button id="btnSemaine" onclick="setView('week')">Semaine</button>
            <button id="btnJour" onclick="setView('day')">Jour</button>
        </div>
    </div>

    <div id="agendaBody"></div>

    <div class="agenda-legend">
        <span><span class="legend-dot" style="background:#2980b9"></span>Formation</span>
        <span><span class="legend-dot" style="background:#e4002b"></span>Instance</span>
        <span><span class="legend-dot" style="background:#d35400"></span>Rappel</span>
    </div>
</div>

<!-- Tooltip événement -->
<div class="evt-tooltip" id="evtTooltip">
    <div class="evt-tooltip-title" id="ttTitle"></div>
    <div class="evt-tooltip-detail" id="ttDetail"></div>
    <a id="ttLink" href="#" class="btn btn-sm" style="background:var(--ce-red);color:#fff;font-size:12px">Voir</a>
</div>

<script>
const B = '<?= $B ?>';
const DAYS_FR = ['Lun','Mar','Mer','Jeu','Ven','Sam','Dim'];
const MONTHS_FR = ['Janvier','Février','Mars','Avril','Mai','Juin','Juillet','Août','Septembre','Octobre','Novembre','Décembre'];

let view = 'month';
let current = new Date(); current.setDate(1); // 1er du mois courant
let allEvents = [];

function fmt(d) { return d.toISOString().slice(0,10); }
function addDays(d, n) { const r = new Date(d); r.setDate(r.getDate()+n); return r; }
function startOfWeek(d) { const r = new Date(d); const day = r.getDay()||7; r.setDate(r.getDate()-(day-1)); return r; }
function isSameDay(a, b) { return a.toDateString() === b.toDateString(); }

async function loadEvents(from, to) {
    const url = `${B}/modules/agenda/index.php?action=events&from=${from}&to=${to}`;
    const res = await fetch(url);
    allEvents = await res.json();
}

function getEventsForDay(dateStr) {
    return allEvents.filter(e => {
        if (!e.start) return false;
        if (e.start <= dateStr && (e.end || e.start) >= dateStr) return true;
        return false;
    });
}

function showTooltip(evt, ev, linkBase) {
    const tt = document.getElementById('evtTooltip');
    document.getElementById('ttTitle').textContent = ev.title;
    document.getElementById('ttDetail').textContent = ev.detail || '';
    document.getElementById('ttLink').href = ev.lien ? `${B}/${ev.lien}` : '#';
    const r = evt.target.getBoundingClientRect();
    tt.style.display = 'block';
    tt.style.left = Math.min(r.left, window.innerWidth - 300) + 'px';
    tt.style.top = (r.bottom + 8) + 'px';
    evt.stopPropagation();
}

document.addEventListener('click', () => { document.getElementById('evtTooltip').style.display = 'none'; });

// ===== MONTH VIEW =====
function renderMonth() {
    const y = current.getFullYear(), m = current.getMonth();
    document.getElementById('agendaTitle').textContent = MONTHS_FR[m] + ' ' + y;

    const first = new Date(y, m, 1);
    const last  = new Date(y, m+1, 0);
    const startDow = (first.getDay()||7) - 1;

    let html = '<div class="cal-grid">';
    DAYS_FR.forEach(d => { html += `<div class="cal-day-header">${d}</div>`; });

    let day = addDays(first, -startDow);
    const today = new Date(); today.setHours(0,0,0,0);

    for (let w = 0; w < 6; w++) {
        for (let d = 0; d < 7; d++) {
            const ds = fmt(day);
            const isToday = isSameDay(day, today);
            const otherM = day.getMonth() !== m;
            const evs = getEventsForDay(ds);
            html += `<div class="cal-cell${otherM?' other-month':''}${isToday?' today':''}">`;
            html += `<div class="cal-date">${day.getDate()}</div>`;
            const shown = evs.slice(0, 3);
            shown.forEach(ev => {
                html += `<div class="cal-event" style="background:${ev.color}" onclick="showTooltip(event,${JSON.stringify(ev)})" title="${ev.title}">${ev.title}</div>`;
            });
            if (evs.length > 3) html += `<div class="cal-more">+${evs.length-3} autre(s)</div>`;
            html += '</div>';
            day = addDays(day, 1);
        }
        if (day > last && w >= 4) break;
    }
    html += '</div>';
    document.getElementById('agendaBody').innerHTML = html;
}

// ===== WEEK VIEW =====
function renderWeek() {
    let dow = view === 'day' ? 0 : null;
    const weekStart = view === 'day' ? new Date(current) : startOfWeek(current);
    const days = view === 'day' ? 1 : 7;

    if (view === 'day') {
        document.getElementById('agendaTitle').textContent = current.toLocaleDateString('fr-FR',{weekday:'long',day:'numeric',month:'long',year:'numeric'});
    } else {
        const wEnd = addDays(weekStart, 6);
        document.getElementById('agendaTitle').textContent = 'Semaine du ' + weekStart.getDate() + ' au ' + wEnd.getDate() + ' ' + MONTHS_FR[wEnd.getMonth()] + ' ' + wEnd.getFullYear();
    }

    const today = new Date(); today.setHours(0,0,0,0);
    const cols = view === 'day' ? 'grid-template-columns:60px 1fr' : 'grid-template-columns:60px repeat(7,1fr)';

    // All-day events row
    let allDayHtml = `<div class="all-day-row"><div class="all-day-label" style="line-height:28px;font-size:10px;color:#bbb">Journée</div>`;
    for (let d = 0; d < days; d++) {
        const day = addDays(weekStart, d);
        const ds = fmt(day);
        const evs = getEventsForDay(ds);
        allDayHtml += `<div class="all-day-cell">`;
        evs.forEach(ev => {
            allDayHtml += `<div class="cal-event" style="background:${ev.color};font-size:11px;padding:2px 6px;margin-bottom:2px" onclick="showTooltip(event,${JSON.stringify(ev)})">${ev.title}</div>`;
        });
        allDayHtml += '</div>';
    }
    allDayHtml += '</div>';

    // Headers
    let html = `<div style="${cols}" class="week-grid">`;
    html += '<div class="week-col-header" style="border-right:1px solid #f0f0f0"></div>';
    for (let d = 0; d < days; d++) {
        const day = addDays(weekStart, d);
        const isToday = isSameDay(day, today);
        html += `<div class="week-col-header${isToday?' today-col':''}">`;
        html += `<div>${DAYS_FR[day.getDay()||7-1]}</div><div style="font-size:18px;font-weight:700">${day.getDate()}</div>`;
        html += '</div>';
    }

    // Time slots 8h-20h
    for (let h = 8; h <= 20; h++) {
        html += `<div class="week-slot"><div class="week-time-label">${h}h</div></div>`;
        for (let d = 0; d < days; d++) html += '<div class="week-slot"></div>';
    }
    html += '</div>';

    document.getElementById('agendaBody').innerHTML = allDayHtml + html;
}

// ===== NAVIGATION =====
function navigate(dir) {
    if (view === 'month') {
        current.setMonth(current.getMonth() + dir);
    } else if (view === 'week') {
        current = addDays(current, dir * 7);
    } else {
        current = addDays(current, dir);
    }
    refresh();
}

function goToday() {
    current = new Date();
    if (view === 'month') current.setDate(1);
    refresh();
}

function setView(v) {
    view = v;
    document.querySelectorAll('.view-btns button').forEach(b => b.classList.remove('active'));
    document.getElementById('btn' + v.charAt(0).toUpperCase() + v.slice(1)).classList.add('active');
    if (v === 'month') current.setDate(1);
    refresh();
}

async function refresh() {
    let from, to;
    if (view === 'month') {
        const y = current.getFullYear(), m = current.getMonth();
        const first = new Date(y, m, 1);
        const last  = new Date(y, m+1, 0);
        from = fmt(addDays(first, -6));
        to   = fmt(addDays(last, 6));
    } else if (view === 'week') {
        const ws = startOfWeek(current);
        from = fmt(ws);
        to   = fmt(addDays(ws, 6));
    } else {
        from = to = fmt(current);
    }
    await loadEvents(from, to);
    view === 'month' ? renderMonth() : renderWeek();
}

// Init
document.getElementById('btnMois').classList.add('active');
setView('month');
</script>

<?php require_once __DIR__ . '/../../templates/footer.php'; ?>
