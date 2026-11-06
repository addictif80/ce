<?php
// Guide de la fiscalité bancaire et patrimoniale, partagé par le portail et /tools. Consultation seule : rien n'est enregistré.
$gf = require __DIR__ . '/data.php';
?>
<style>
.gf-nav{display:flex;flex-wrap:wrap;gap:6px;margin-bottom:14px}.gf-nav a{font-size:.85rem;border:1px solid #d9d9d9;border-radius:20px;padding:3px 12px;text-decoration:none;color:inherit;background:#fff}.gf-nav a:hover{background:#f3f3f3}
.gf-sec{margin-bottom:22px;scroll-margin-top:70px}.gf-sec>h2{font-size:1.15rem;font-weight:700;margin:0 0 4px}.gf-intro{color:#6c757d;font-size:.9rem;margin-bottom:10px}
.gf-card{background:#fff;border:1px solid #e8e8e8;border-radius:12px;margin-bottom:14px;box-shadow:0 2px 8px rgba(0,0,0,.05);break-inside:avoid}
.gf-card h3{font-size:1rem;font-weight:700;margin:0;padding:11px 16px;border-bottom:2px solid #6c757d;display:flex;gap:8px;justify-content:space-between;align-items:center}
.gf-badge{font-size:.7rem;font-weight:600;border-radius:10px;padding:2px 8px;white-space:nowrap}.gf-ok{background:#d1e7dd;color:#0f5132}.gf-warn{background:#fff3cd;color:#664d03}
.gf-row{display:flex;gap:14px;justify-content:space-between;padding:8px 16px;border-bottom:1px solid #f0f0f0}.gf-row:last-of-type{border-bottom:0}
.gf-val{font-weight:700;text-align:right;max-width:46%;min-width:96px}.gf-note{font-size:.8rem;color:#6c757d;margin-top:2px}.gf-w{background:#fffbe6}
.gf-src{padding:8px 16px;border-top:1px solid #f0f0f0;font-size:.78rem;color:#6c757d}.gf-src a{margin-right:10px}
.gf-hit{outline:2px solid #ffd200}.gf-div{font-size:.88rem}.gf-div li{margin-bottom:6px}
@media (max-width:640px){.gf-row{flex-direction:column;gap:2px}.gf-val{text-align:left;max-width:none}}
@media print{.gf-card{box-shadow:none}.no-print{display:none!important}}
</style>
<div class="alert alert-warning small"><i class="fas fa-triangle-exclamation me-1"></i><strong>À contrôler avant diffusion.</strong> Les chiffres ont été recoupés le <?= e($gf['verifie']) ?> sur service-public.gouv.fr, impots.gouv.fr, le BOFiP et Légifrance. Les lignes sur fond jaune n'ont pas pu être confirmées par une source officielle. Ce guide est général et ne remplace ni la documentation interne ni l'avis d'un spécialiste ; il ne tient pas compte de la situation personnelle du client.</div>
<div class="d-flex gap-2 flex-wrap mb-3 no-print">
  <input type="search" class="form-control" id="gf_q" data-noshare placeholder="Rechercher (PFU, PEA, abattement, IFI…)" style="max-width:360px" aria-label="Rechercher dans le guide">
  <button type="button" class="btn btn-sm btn-outline-secondary" onclick="window.print()"><i class="fas fa-print me-1"></i>Imprimer</button>
</div>
<nav class="gf-nav no-print" aria-label="Sections">
<?php foreach ($gf['sections'] as $s): ?><a href="#gf-<?= e($s['id']) ?>"><i class="fas <?= e($s['icon']) ?> me-1"></i><?= e($s['titre']) ?></a><?php endforeach; ?>
<a href="#gf-div"><i class="fas fa-code-compare me-1"></i>Sources croisées</a>
</nav>
<div id="gf_list" class="js-result">
<?php foreach ($gf['sections'] as $s): ?>
<section class="gf-sec" id="gf-<?= e($s['id']) ?>"><h2><i class="fas <?= e($s['icon']) ?> me-2"></i><?= e($s['titre']) ?></h2>
  <div class="gf-intro"><?= e($s['intro']) ?></div>
  <?php foreach ($s['cards'] as $c): ?>
  <div class="gf-card"><h3><span><?= e($c['titre']) ?></span>
    <span class="gf-badge <?= $c['statut'] === 'ok' ? 'gf-ok' : 'gf-warn' ?>"><?= $c['statut'] === 'ok' ? 'Recoupé' : 'Partiellement à confirmer' ?></span></h3>
    <?php foreach ($c['rows'] as $r): ?>
    <div class="gf-row<?= !empty($r[3]) ? ' gf-w' : '' ?>"><div><?= e($r[0]) ?><?php if (!empty($r[2])): ?><div class="gf-note"><?= e($r[2]) ?></div><?php endif; ?></div>
      <div class="gf-val"><?= e($r[1]) ?><?php if (!empty($r[3])): ?> <span class="gf-badge gf-warn">à confirmer</span><?php endif; ?></div></div>
    <?php endforeach; ?>
    <?php if (!empty($c['sources'])): ?><div class="gf-src">Sources : <?php foreach ($c['sources'] as $src): ?><a href="<?= e($src[1]) ?>" target="_blank" rel="noopener noreferrer"><?= e($src[0]) ?></a><?php endforeach; ?></div><?php endif; ?>
  </div>
  <?php endforeach; ?>
</section>
<?php endforeach; ?>
<section class="gf-sec" id="gf-div"><h2><i class="fas fa-code-compare me-2"></i>Sources croisées : divergences relevées</h2>
  <div class="gf-intro">Lors du recoupement, des sources se contredisaient. Voici ce qui a été retenu et pourquoi (une source officielle prime sur un site tiers, et le texte le plus récent sur le plus ancien).</div>
  <div class="gf-card"><div class="p-3"><ul class="gf-div mb-0">
  <?php foreach ($gf['divergences'] as $d): ?><li><strong><?= e($d[0]) ?></strong> : <?= e($d[1]) ?></li><?php endforeach; ?>
  </ul></div></div>
</section>
</div>
<p class="small text-muted">Dernier recoupement : <?= e($gf['verifie']) ?>. Les barèmes évoluent chaque année par la loi de finances : revérifiez sur les textes officiels avant de communiquer un chiffre à un client.</p>
<script>
(function(){
  const q=document.getElementById('gf_q'); if(!q) return;
  const norm=s=>s.toLowerCase().normalize('NFD').replace(/[̀-ͯ]/g,'');
  q.addEventListener('input',()=>{
    const t=norm(q.value.trim());
    document.querySelectorAll('#gf_list .gf-sec').forEach(sec=>{
      let any=false;
      sec.querySelectorAll('.gf-card').forEach(c=>{
        const h3=c.querySelector('h3'),rows=c.querySelectorAll('.gf-row'); let hitCard=!t||(h3&&norm(h3.textContent).includes(t));
        if(rows.length){ rows.forEach(r=>{const h=hitCard||norm(r.textContent).includes(t);r.style.display=h?'':'none';if(h&&t)hitCard=true;});
          hitCard=!t||[...rows].some(r=>r.style.display!=='none'); }
        else hitCard=!t||norm(c.textContent).includes(t);
        c.style.display=hitCard?'':'none'; any=any||hitCard;
      });
      sec.style.display=any?'':'none';
    });
  });
})();
</script>
<?php require __DIR__ . '/../_sim/common_js.php'; ?>
