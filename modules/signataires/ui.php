<?php
// « Qui peut signer quoi ? » : aide à l'orientation selon la situation du client et l'opération. Contenu modifiable dans l'administration (barèmes).
// Partagé par le portail et /tools ; rien n'est enregistré.
require_once __DIR__ . '/../../includes/baremes.php';
$sg = baremeGet('signataires'); $groupes = $sg['data']['groupes'] ?? [];
$ops = [];
foreach ($groupes as $g) foreach ($g['lignes'] as $l) if (!in_array($l['libelle'], $ops, true)) $ops[] = $l['libelle'];
require __DIR__ . '/../_sim/style.php';
?>
<?php require __DIR__ . '/../_sim/actions.php'; ?>
<?= baremeNotice(['signataires']) ?>
<div class="alert alert-warning small no-print"><i class="fas fa-scale-balanced me-1"></i>Aide à l'orientation : ces fiches résument des règles générales. Elles ne sont pas un avis juridique et ne remplacent pas les procédures de la banque : en cas de doute, consultez la conformité ou le service juridique.</div>
<div class="row g-3">
 <div class="col-lg-5">
  <div class="cap-card"><h2><i class="fas fa-user-tag me-2 text-danger"></i>La situation</h2>
    <label class="form-label small mb-0" for="sg_sit">Situation du client</label>
    <select class="form-select mb-2" id="sg_sit"><?php foreach ($groupes as $i => $g): ?><option value="<?= (int)$i ?>"><?= e($g['titre']) ?></option><?php endforeach; ?></select>
    <label class="form-label small mb-0" for="sg_op">Opération</label>
    <select class="form-select" id="sg_op"><option value="">Toutes les opérations</option><?php foreach ($ops as $i => $o): ?><option value="<?= (int)$i ?>"><?= e($o) ?></option><?php endforeach; ?></select>
  </div>
 </div>
 <div class="col-lg-7"><div class="js-result" id="sg_out"></div></div>
</div>
<script>
(function(){
  const G=<?= json_encode($groupes) ?>, OPS=<?= json_encode($ops) ?>;
  const $=id=>document.getElementById(id), esc=s=>String(s).replace(/[&<>"]/g,c=>({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;'}[c]));
  function run(){
    const g=G[parseInt($('sg_sit').value)]||{lignes:[],titre:''}, op=$('sg_op').value;
    const rows=g.lignes.filter(l=>op===''||l.libelle===OPS[parseInt(op)]);
    $('sg_out').innerHTML=`<div class="cap-card"><h2>${esc(g.titre)}</h2>`+(rows.length?rows.map(l=>`<div class="mb-3"><div class="fw-semibold text-danger">${esc(l.libelle)}</div><div><i class="fas fa-pen-nib me-1 text-muted"></i>${esc(l.valeur)}</div>${l.note?`<div class="small text-muted mt-1">${esc(l.note)}</div>`:''}</div>`).join(''):'<div class="text-muted">Aucune règle renseignée pour cette opération dans cette situation.</div>')+'</div>';
  }
  $('sg_sit').addEventListener('change',run);$('sg_op').addEventListener('change',run);run();
})();
</script>
<?php require __DIR__ . '/../_sim/common_js.php'; ?>
