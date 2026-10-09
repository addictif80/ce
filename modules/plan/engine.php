<?php
// Moteur de calcul du module crédit immobilier, chargé dans les pages de simulation (plan de financement, scénarios, crédits spéciaux) :
// barèmes (PTZ, Doublissimo, Primo Jeune, Primoz, Grandioz) puis ptz-calc.js, ci.js, ci_extra.js et la passerelle plan-adapter.js.
if (!empty($GLOBALS['planEngineDone'])) return;
$GLOBALS['planEngineDone'] = true;
require_once __DIR__ . '/../../includes/baremes.php';
require_once __DIR__ . '/../ptz/bareme.php';
$planEngineJs = fn($f) => '<script>' . file_get_contents(__DIR__ . '/../../' . $f) . '</script>';
?>
<script>
// Valeurs neutres attendues par le code du module crédit immobilier (liste des dossiers, filtre du tableau)
const dossiersData = [], workflowLabels = {}, conseillerData = {};
function filterTable() {}
const ciPtzBareme = <?= json_encode(ptzGetBareme()) ?>;
const ciDoublissimo = <?= json_encode(baremeGet('doublissimo')['data']) ?>;
const ciPrimoJeune = <?= json_encode(baremeGet('primo_jeune')['data']) ?>;
const ciPrimoz = <?= json_encode(baremeGet('primoz')['data']) ?>;
const ciGrandioz = <?= json_encode(baremeGet('grandioz')['data']) ?>;
</script>
<?= $planEngineJs('assets/js/ptz-calc.js') ?>
<?= $planEngineJs('modules/credit_immo/ci.js') ?>
<?= $planEngineJs('modules/credit_immo/ci_extra.js') ?>
<?= $planEngineJs('assets/js/plan-adapter.js') ?>
<?= $planEngineJs('assets/js/plan-form.js') ?>
