<?php
$pageTitle = 'Calendrier des formations';
require_once __DIR__ . '/../../templates/header.php';
$db = getDB();
$userId = getCurrentUserId();
$user = getCurrentUser();

// Mois/année courant ou sélectionné
$month = isset($_GET['month']) ? (int)$_GET['month'] : (int)date('m');
$year = isset($_GET['year']) ? (int)$_GET['year'] : (int)date('Y');

// Bornes du mois
$firstDay = mktime(0, 0, 0, $month, 1, $year);
$daysInMonth = (int)date('t', $firstDay);
$startWeekday = (int)date('N', $firstDay); // 1=lundi, 7=dimanche

// Navigation mois
$prevMonth = $month - 1;
$prevYear = $year;
if ($prevMonth < 1) { $prevMonth = 12; $prevYear--; }
$nextMonth = $month + 1;
$nextYear = $year;
if ($nextMonth > 12) { $nextMonth = 1; $nextYear++; }

$monthNames = ['', 'Janvier', 'Février', 'Mars', 'Avril', 'Mai', 'Juin', 'Juillet', 'Août', 'Septembre', 'Octobre', 'Novembre', 'Décembre'];

// Formations du mois (celles qui chevauchent le mois)
$startDate = sprintf('%04d-%02d-01', $year, $month);
$endDate = sprintf('%04d-%02d-%02d', $year, $month, $daysInMonth);
$stmt = $db->prepare("SELECT * FROM formations WHERE user_id = ? AND date_debut <= ? AND date_fin >= ? ORDER BY date_debut");
$stmt->execute([$userId, $endDate, $startDate]);
$formations = $stmt->fetchAll();

// Indexer par jour
$eventsByDay = [];
foreach ($formations as $f) {
    $dStart = max(1, $f['date_debut'] >= $startDate ? (int)date('j', strtotime($f['date_debut'])) : 1);
    $dEnd = min($daysInMonth, $f['date_fin'] <= $endDate ? (int)date('j', strtotime($f['date_fin'])) : $daysInMonth);
    for ($d = $dStart; $d <= $dEnd; $d++) {
        $eventsByDay[$d][] = $f;
    }
}

// Token CalDAV unique par utilisateur
$caldavToken = md5('caldav_' . $userId . '_' . ($user['password'] ?? '') . '_formations');
$caldavUrl = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http') . '://' . ($_SERVER['HTTP_HOST'] ?? 'localhost') . dirname(dirname($_SERVER['SCRIPT_NAME'])) . '/formations/caldav.php?token=' . $caldavToken;
?>

<style>
    .cal-nav { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; }
    .cal-table { width: 100%; border-collapse: collapse; table-layout: fixed; }
    .cal-table th { background: var(--ce-red); color: white; padding: 10px; text-align: center; font-size: 13px; }
    .cal-table td { border: 1px solid #ddd; vertical-align: top; height: 110px; padding: 5px; font-size: 13px; }
    .cal-table td.today { background: #fff3cd; }
    .cal-table td.other-month { background: #f9f9f9; color: #ccc; }
    .cal-day-num { font-weight: 700; font-size: 14px; margin-bottom: 4px; }
    .cal-event { background: var(--ce-red); color: white; padding: 2px 6px; border-radius: 4px; font-size: 11px; margin-bottom: 2px; display: block; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; cursor: default; }
    .cal-event.distanciel { background: var(--ce-blue); }
    .caldav-section { background: white; border-radius: 10px; padding: 20px; box-shadow: 0 2px 8px rgba(0,0,0,0.06); margin-top: 20px; }

    @media print {
        .no-print, .sidebar, .topbar { display: none !important; }
        .main-content { margin-left: 0 !important; }
        .page-content { padding: 10px !important; }
        .cal-table td { height: 80px; }
        .caldav-section { display: none !important; }
    }
</style>

<div class="cal-nav no-print">
    <a href="?month=<?= $prevMonth ?>&year=<?= $prevYear ?>" class="btn btn-ce-outline"><i class="fas fa-chevron-left"></i> Précédent</a>
    <h3><?= $monthNames[$month] ?> <?= $year ?></h3>
    <a href="?month=<?= $nextMonth ?>&year=<?= $nextYear ?>" class="btn btn-ce-outline">Suivant <i class="fas fa-chevron-right"></i></a>
</div>

<div class="mb-3 no-print">
    <a href="index.php" class="btn btn-ce-outline"><i class="fas fa-list"></i> Liste</a>
    <button onclick="window.print()" class="btn btn-ce"><i class="fas fa-print"></i> Imprimer</button>
</div>

<div class="data-table-container" style="overflow:visible;">
    <table class="cal-table">
        <thead>
            <tr>
                <th>Lun</th><th>Mar</th><th>Mer</th><th>Jeu</th><th>Ven</th><th>Sam</th><th>Dim</th>
            </tr>
        </thead>
        <tbody>
        <?php
        $day = 1;
        $todayStr = date('Y-m-d');
        $started = false;
        for ($row = 0; $row < 6; $row++):
            if ($day > $daysInMonth) break;
        ?>
            <tr>
            <?php for ($col = 1; $col <= 7; $col++):
                if (!$started && $col < $startWeekday): ?>
                    <td class="other-month"></td>
                <?php elseif ($day > $daysInMonth): ?>
                    <td class="other-month"></td>
                <?php else:
                    $started = true;
                    $dateStr = sprintf('%04d-%02d-%02d', $year, $month, $day);
                    $isToday = ($dateStr === $todayStr);
                ?>
                    <td class="<?= $isToday ? 'today' : '' ?>">
                        <div class="cal-day-num"><?= $day ?></div>
                        <?php if (isset($eventsByDay[$day])):
                            foreach ($eventsByDay[$day] as $ev): ?>
                                <span class="cal-event <?= $ev['lieu'] === 'distanciel' ? 'distanciel' : '' ?>" title="<?= e($ev['titre']) ?> (<?= $ev['lieu'] ?>)">
                                    <?= e($ev['titre']) ?>
                                </span>
                            <?php endforeach;
                        endif; ?>
                    </td>
                <?php
                    $day++;
                    endif;
                endfor; ?>
            </tr>
        <?php endfor; ?>
        </tbody>
    </table>
</div>

<div class="row mt-3 no-print">
    <div class="col-auto"><span class="cal-event" style="display:inline-block;">Présentiel</span></div>
    <div class="col-auto"><span class="cal-event distanciel" style="display:inline-block;">Distanciel</span></div>
</div>

<!-- CalDAV sync -->
<div class="caldav-section no-print">
    <h4><i class="fas fa-sync"></i> Synchronisation CalDAV</h4>
    <p class="text-muted">Utilisez cette URL pour synchroniser vos formations dans votre application de calendrier (Outlook, Thunderbird, Google Calendar, Apple Calendar, etc.) :</p>
    <div class="input-group">
        <input type="text" class="form-control" id="caldavUrl" value="<?= e($caldavUrl) ?>" readonly>
        <button class="btn btn-ce" onclick="navigator.clipboard.writeText(document.getElementById('caldavUrl').value).then(()=>alert('URL copiée !'))"><i class="fas fa-copy"></i> Copier</button>
    </div>
    <small class="text-muted mt-2 d-block">Cette URL est personnelle et liée à votre compte. Ne la partagez pas.</small>
</div>

<?php require_once __DIR__ . '/../../templates/footer.php'; ?>
