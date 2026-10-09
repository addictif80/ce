<?php
// Mémo de référence (plafonds et seuils, délais légaux), partagé par le portail et /tools. Variable attendue : $memoKey ('memo_plafonds' | 'memo_delais').
require_once __DIR__ . '/../../includes/baremes.php';
$mb = baremeGet($memoKey); $groupes = $mb['data']['groupes'] ?? [];
?>
<style>.mm-card{background:#fff;border:1px solid #e8e8e8;border-radius:12px;margin-bottom:16px;box-shadow:0 2px 8px rgba(0,0,0,.05);break-inside:avoid}.mm-card h2{font-size:1rem;font-weight:700;margin:0;padding:12px 16px;border-bottom:2px solid #e4002b}.mm-row{display:flex;gap:12px;justify-content:space-between;padding:8px 16px;border-bottom:1px solid #f0f0f0}.mm-row:last-child{border-bottom:0}.mm-val{font-weight:700;text-align:right;white-space:nowrap}.mm-note{font-size:.8rem;color:#6c757d}.mm-hit{background:#fff3cd}@media (max-width:600px){.mm-row{flex-direction:column;gap:2px}.mm-val{text-align:left;white-space:normal}}@media print{.mm-card{box-shadow:none}}</style>
<?= baremeNotice([$memoKey]) ?>
<div class="d-flex gap-2 flex-wrap mb-3 no-print">
  <input type="search" class="form-control" id="mm_q" data-noshare value="<?= e($_GET['q'] ?? '') ?>" placeholder="Filtrer le mémo…" style="max-width:340px" aria-label="Filtrer le mémo">
  <button type="button" class="btn btn-sm btn-outline-secondary" onclick="window.print()"><i class="fas fa-print me-1"></i>Imprimer</button>
</div>
<div id="mm_list" class="js-result">
<?php foreach ($groupes as $g): ?>
  <div class="mm-card mm-group"><h2><?= e($g['titre']) ?></h2>
  <?php foreach ($g['lignes'] as $l): ?>
    <div class="mm-row"><div><?= e($l['libelle']) ?><?php if (!empty($l['note'])): ?><div class="mm-note"><?= e($l['note']) ?></div><?php endif; ?></div><div class="mm-val"><?= e($l['valeur']) ?></div></div>
  <?php endforeach; ?></div>
<?php endforeach; if (!$groupes): ?><div class="alert alert-light border">Ce mémo est vide pour le moment.</div><?php endif; ?>
</div>
<p class="small text-muted">Mémo d'information : les valeurs évoluent par décret ou en loi de finances. Vérifiez-les sur les textes officiels avant de les communiquer.</p>
<script>
(function(){
  const q=document.getElementById('mm_q'); if(!q) return;
  const norm=s=>s.toLowerCase().normalize('NFD').replace(/[̀-ͯ]/g,'');
  q.addEventListener('input',()=>{
    const t=norm(q.value.trim());
    document.querySelectorAll('.mm-group').forEach(g=>{
      let any=false;
      g.querySelectorAll('.mm-row').forEach(r=>{const hit=!t||norm(r.textContent).includes(t);r.style.display=hit?'':'none';r.classList.toggle('mm-hit',!!t&&hit);any=any||hit;});
      g.style.display=any?'':'none';
    });
  });
  if(q.value) q.dispatchEvent(new Event('input'));
})();
</script>
<?php require __DIR__ . '/../_sim/common_js.php'; ?>
