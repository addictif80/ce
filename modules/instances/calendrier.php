<?php
$pageTitle = 'Calendrier des instances';
require_once __DIR__ . '/../../templates/header.php';
$db = getDB();
$userId = getCurrentUserId();

// Mois/année
$month = isset($_GET['month']) ? (int)$_GET['month'] : (int)date('m');
$year = isset($_GET['year']) ? (int)$_GET['year'] : (int)date('Y');

$firstDay = mktime(0, 0, 0, $month, 1, $year);
$daysInMonth = (int)date('t', $firstDay);
$startWeekday = (int)date('N', $firstDay);

$prevMonth = $month - 1; $prevYear = $year;
if ($prevMonth < 1) { $prevMonth = 12; $prevYear--; }
$nextMonth = $month + 1; $nextYear = $year;
if ($nextMonth > 12) { $nextMonth = 1; $nextYear++; }

$monthNames = ['', 'Janvier', 'Février', 'Mars', 'Avril', 'Mai', 'Juin', 'Juillet', 'Août', 'Septembre', 'Octobre', 'Novembre', 'Décembre'];

// Instances avec échéance ce mois
$startDate = sprintf('%04d-%02d-01', $year, $month);
$endDate = sprintf('%04d-%02d-%02d', $year, $month, $daysInMonth);
$stmt = $db->prepare("SELECT * FROM instances WHERE user_id = ? AND date_echeance BETWEEN ? AND ? ORDER BY date_echeance");
$stmt->execute([$userId, $startDate, $endDate]);
$instances = $stmt->fetchAll();

$eventsByDay = [];
foreach ($instances as $inst) {
    $d = (int)date('j', strtotime($inst['date_echeance']));
    $eventsByDay[$d][] = $inst;
}
?>

<style>
    .cal-nav { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; }
    .cal-table { width: 100%; border-collapse: collapse; table-layout: fixed; }
    .cal-table th { background: var(--ce-red); color: white; padding: 10px; text-align: center; font-size: 13px; }
    .cal-table td { border: 1px solid #ddd; vertical-align: top; height: 110px; padding: 5px; font-size: 13px; }
    .cal-table td.today { background: #fff3cd; }
    .cal-table td.other-month { background: #f9f9f9; color: #ccc; }
    .cal-day-num { font-weight: 700; font-size: 14px; margin-bottom: 4px; }
    .cal-event { padding: 2px 6px; border-radius: 4px; font-size: 11px; margin-bottom: 2px; display: block; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; color: white; }
    .cal-event.afaire { background: var(--ce-orange); }
    .cal-event.fait { background: var(--ce-green); }
    .cal-event.retard { background: var(--ce-red); }

    @media print {
        .no-print, .sidebar, .topbar { display: none !important; }
        .main-content { margin-left: 0 !important; }
        .page-content { padding: 10px !important; }
        .cal-table td { height: 80px; }
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
                            foreach ($eventsByDay[$day] as $ev):
                                $cls = 'afaire';
                                if ($ev['statut'] === 'fait') $cls = 'fait';
                                elseif ($ev['date_echeance'] < $todayStr) $cls = 'retard';
                            ?>
                                <span class="cal-event <?= $cls ?>" title="<?= e($ev['numero_personne']) ?> - <?= e($ev['categories']) ?>">
                                    <?= e($ev['numero_personne']) ?>
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
    <div class="col-auto"><span class="cal-event afaire" style="display:inline-block;">À faire</span></div>
    <div class="col-auto"><span class="cal-event fait" style="display:inline-block;">Fait</span></div>
    <div class="col-auto"><span class="cal-event retard" style="display:inline-block;">En retard</span></div>
</div>

<?php require_once __DIR__ . '/../../templates/footer.php'; ?>
