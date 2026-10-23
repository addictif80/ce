<?php
// Barre de document commune aux éditeurs (tableur, présentation) : nom, enregistrement, rattachement, versions (portail) ou fichier local (/tools).
// Variables : $docMode 'portal'|'tools', $docType 'sheet'|'slides', $docInit null|['id','nom','data','links','updated_at'], $docApi URL de api.php (portail).
$docInit = $docInit ?? null; $docApi = $docApi ?? 'api.php';
$linkables = $docMode === 'portal' ? array_map(fn($m) => $m['label'], docsLinkables()) : [];
?>
<style>
.db-bar{display:flex;flex-wrap:wrap;gap:8px;align-items:center;margin-bottom:10px}
.db-bar .db-name{max-width:340px;font-weight:600}.db-status{font-size:12.5px;color:#6c757d}.db-status.err{color:#c40025;font-weight:600}
.db-chip{display:inline-flex;align-items:center;gap:6px;background:#eef3fb;border:1px solid #cfdcf0;border-radius:14px;padding:2px 10px;font-size:12.5px}.db-chip a{text-decoration:none}.db-chip button{border:0;background:none;color:#c40025;padding:0}
.db-modal{position:fixed;inset:0;background:rgba(0,0,0,.45);display:none;align-items:center;justify-content:center;z-index:2000}.db-modal.on{display:flex}
.db-box{background:#fff;border-radius:12px;max-width:560px;width:calc(100% - 24px);max-height:84vh;overflow:auto;padding:18px}
.db-rec{display:block;width:100%;text-align:left;border:1px solid #e3e6ea;border-radius:8px;background:#fff;padding:7px 10px;margin-bottom:6px}.db-rec:hover{background:#f3f6fb}
</style>
<div class="db-bar no-print" id="db-bar">
 <input type="text" id="db-name" class="form-control form-control-sm db-name" maxlength="150" placeholder="Nom du document" aria-label="Nom du document" value="<?= e($docInit['nom'] ?? ($docType === 'sheet' ? 'Tableur sans titre' : 'Présentation sans titre')) ?>">
 <?php if ($docMode === 'portal'): ?>
  <button type="button" class="btn btn-sm btn-danger" id="db-save"><i class="fas fa-floppy-disk me-1"></i>Enregistrer</button>
  <button type="button" class="btn btn-sm btn-outline-secondary" id="db-attach"><i class="fas fa-paperclip me-1"></i>Rattacher à un enregistrement</button>
  <button type="button" class="btn btn-sm btn-outline-secondary" id="db-versions"><i class="fas fa-clock-rotate-left me-1"></i>Versions</button>
  <a class="btn btn-sm btn-outline-secondary" href="index.php"><i class="fas fa-folder-open me-1"></i>Mes documents</a>
  <span class="db-status" id="db-status" role="status"></span>
  <div class="w-100" id="db-links"></div>
 <?php else: ?>
  <button type="button" class="btn btn-sm btn-outline-secondary" id="db-dl"><i class="fas fa-download me-1"></i>Enregistrer sur mon ordinateur</button>
  <button type="button" class="btn btn-sm btn-outline-secondary" id="db-open"><i class="fas fa-folder-open me-1"></i>Ouvrir un fichier</button>
  <input type="file" id="db-file" accept=".json,application/json" class="d-none">
  <span class="db-status" id="db-status" role="status">Brouillon conservé dans ce navigateur</span>
 <?php endif; ?>
</div>
<?php if ($docMode === 'portal'): ?>
<div class="db-modal" id="db-m-attach"><div class="db-box"><h5><i class="fas fa-paperclip me-2"></i>Rattacher à un enregistrement</h5>
 <select id="db-mod" class="form-select form-select-sm mb-2"><?php foreach ($linkables as $k => $l): ?><option value="<?= e($k) ?>"><?= e($l) ?></option><?php endforeach; ?></select>
 <input type="search" id="db-q" class="form-control form-control-sm mb-2" placeholder="Rechercher (numéro de personne, nom, objet…)">
 <div id="db-recs" class="mb-2"></div><div class="text-end"><button type="button" class="btn btn-sm btn-outline-secondary" data-close>Fermer</button></div></div></div>
<div class="db-modal" id="db-m-versions"><div class="db-box"><h5><i class="fas fa-clock-rotate-left me-2"></i>Versions précédentes</h5>
 <div class="small text-muted mb-2">Une version est conservée automatiquement à chaque série de modifications (les 15 dernières). Restaurer remplace le contenu affiché : enregistrez ensuite pour le garder.</div>
 <div id="db-vers"></div><div class="text-end mt-2"><button type="button" class="btn btn-sm btn-outline-secondary" data-close>Fermer</button></div></div></div>
<?php endif; ?>
<script>
window.DocIO = (function () {
  'use strict';
  const MODE = <?= json_encode($docMode) ?>, TYPE = <?= json_encode($docType) ?>, API = <?= json_encode($docApi) ?>, INIT = <?= json_encode($docInit, JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE) ?>;
  const $ = id => document.getElementById(id), esc = s => String(s == null ? '' : s).replace(/[&<>"']/g, c => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));
  let ed = null, dirty = false, id = INIT ? INIT.id : 0, timer = null, saving = false, links = INIT ? INIT.links || [] : [];
  const status = (t, err) => { const s = $('db-status'); if (s) { s.textContent = t; s.className = 'db-status' + (err ? ' err' : ''); } };
  const nom = () => ($('db-name').value || '').trim() || 'Sans titre';
  const call = async (action, params) => { const fd = new FormData(); fd.append('action', action); Object.keys(params || {}).forEach(k => fd.append(k, params[k]));
    let r; try { r = await fetch(API, { method: 'POST', body: fd, headers: { 'X-Doc-Api': '1' }, credentials: 'same-origin' }); } catch (e) { return { ok: false, error: 'Connexion impossible.' }; }
    try { return await r.json(); } catch (e) { return { ok: false, error: 'Réponse inattendue du serveur (code ' + r.status + ').' }; } };
  function markDirty() { dirty = true; if (MODE === 'portal') { status('Modifications non enregistrées…'); clearTimeout(timer); timer = setTimeout(() => save(false), 25000); } else { clearTimeout(timer); timer = setTimeout(draft, 800); } }
  async function save(snapshot) {
    if (MODE !== 'portal' || saving || !ed) return; saving = true; clearTimeout(timer); status('Enregistrement…');
    const r = await call('save', { id, nom: nom(), data: JSON.stringify(ed.get()), snapshot: snapshot ? 1 : '' });
    saving = false;
    if (r.ok) { dirty = false; status('Enregistré à ' + new Date().toLocaleTimeString('fr-FR', { hour: '2-digit', minute: '2-digit' })); } else status('Échec de l\'enregistrement : ' + r.error, true);
  }
  // ----- /tools : brouillon local et fichier -----
  const KEY = 'ce_doc_draft_' + TYPE;
  function draft() { try { localStorage.setItem(KEY, JSON.stringify({ nom: nom(), data: ed.get(), t: Date.now() })); status('Brouillon enregistré dans ce navigateur'); } catch (e) { status('Brouillon non conservé (stockage du navigateur indisponible ou plein)', true); } }
  function download(name, blob) { const a = document.createElement('a'); a.href = URL.createObjectURL(blob); a.download = name; document.body.appendChild(a); a.click(); setTimeout(() => { URL.revokeObjectURL(a.href); a.remove(); }, 500); }
  const safeName = () => nom().replace(/[\\/:*?"<>|]+/g, '_');
  function renderLinks() { const b = $('db-links'); if (!b) return;
    b.innerHTML = links.length ? links.map(l => '<span class="db-chip"><i class="fas fa-paperclip"></i>' + (l.url ? '<a href="../../' + esc(l.url) + '" title="Ouvrir le module">' + esc(l.module_label) + '</a>' : esc(l.module_label)) + ' · ' + esc(l.label) + '<button type="button" data-unlink="' + esc(l.module) + ':' + l.record_id + '" title="Détacher" aria-label="Détacher"><i class="fas fa-xmark"></i></button></span>').join(' ') : '<span class="small text-muted">Ce document n\'est rattaché à aucun enregistrement.</span>'; }
  function modal(idm, on) { $(idm).classList.toggle('on', on); }
  async function recs() { const r = await call('records', { module: $('db-mod').value, q: $('db-q').value }); $('db-recs').innerHTML = r.ok ? (r.records.length ? r.records.map(x => '<button type="button" class="db-rec" data-rec="' + x.id + '">' + esc(x.label) + '</button>').join('') : '<div class="small text-muted">Aucun enregistrement trouvé parmi les vôtres.</div>') : '<div class="text-danger small">' + esc(r.error) + '</div>'; }
  function init(o) {
    ed = o;
    if (MODE === 'portal') {
      o.set(INIT.data); renderLinks();
      $('db-save').onclick = () => save(true); $('db-name').addEventListener('input', markDirty);
      document.addEventListener('keydown', e => { if ((e.ctrlKey || e.metaKey) && e.key.toLowerCase() === 's') { e.preventDefault(); save(true); } });
      window.addEventListener('beforeunload', e => { if (dirty) { e.preventDefault(); e.returnValue = ''; } });
      $('db-attach').onclick = async () => { if (dirty) await save(false); modal('db-m-attach', true); recs(); };
      $('db-mod').onchange = recs; let qt; $('db-q').oninput = () => { clearTimeout(qt); qt = setTimeout(recs, 250); };
      $('db-m-attach').addEventListener('click', async e => { if (e.target.matches('[data-close]') || e.target === $('db-m-attach')) return modal('db-m-attach', false);
        const b = e.target.closest('[data-rec]'); if (!b) return; const r = await call('link', { id, module: $('db-mod').value, record_id: b.dataset.rec });
        if (r.ok) { links = r.links; renderLinks(); modal('db-m-attach', false); } else alert(r.error); });
      $('db-bar').addEventListener('click', async e => { const u = e.target.closest('[data-unlink]'); if (!u) return; const [m, rid] = u.dataset.unlink.split(':'); const r = await call('unlink', { id, module: m, record_id: rid }); if (r.ok) { links = r.links; renderLinks(); } });
      $('db-versions').onclick = async () => { if (dirty) await save(false); modal('db-m-versions', true); const r = await call('versions', { id });
        $('db-vers').innerHTML = r.ok ? (r.versions.length ? r.versions.map(v => '<div class="d-flex justify-content-between align-items-center border-bottom py-1"><span>' + new Date(v.saved_at.replace(' ', 'T')).toLocaleString('fr-FR') + ' <span class="text-muted small">(' + Math.round(v.taille / 1024) + ' Ko)</span></span><button type="button" class="btn btn-sm btn-outline-primary" data-ver="' + v.id + '">Restaurer</button></div>').join('') : '<div class="text-muted small">Aucune version précédente pour l\'instant.</div>') : esc(r.error); };
      $('db-m-versions').addEventListener('click', async e => { if (e.target.matches('[data-close]') || e.target === $('db-m-versions')) return modal('db-m-versions', false);
        const b = e.target.closest('[data-ver]'); if (!b || !confirm('Remplacer le contenu affiché par cette version ?')) return; const r = await call('version_get', { id, version: b.dataset.ver });
        if (r.ok) { o.set(r.data); markDirty(); modal('db-m-versions', false); } else alert(r.error); });
      status('Document ouvert');
    } else {
      let restored = false; try { const d = JSON.parse(localStorage.getItem(KEY) || 'null'); if (d && d.data && Date.now() - d.t < 30 * 864e5 && confirm('Reprendre le brouillon enregistré le ' + new Date(d.t).toLocaleString('fr-FR') + ' ?')) { o.set(d.data); $('db-name').value = d.nom || $('db-name').value; restored = true; } } catch (e) {}
      if (!restored) o.set(o.blank());
      $('db-name').addEventListener('input', markDirty);
      $('db-dl').onclick = () => download(safeName() + '.' + TYPE + '.json', new Blob([JSON.stringify({ nom: nom(), type: TYPE, data: ed.get() })], { type: 'application/json' }));
      $('db-open').onclick = () => $('db-file').click();
      $('db-file').onchange = async e => { const f = e.target.files[0]; e.target.value = ''; if (!f) return; try { const j = JSON.parse(await f.text()); if (j.type !== TYPE || !j.data) throw 0; o.set(j.data); $('db-name').value = j.nom || f.name; markDirty(); } catch (er) { alert('Ce fichier n\'est pas un document de ce type enregistré depuis cet outil.'); } };
    }
  }
  return { init, markDirty, nom, download, safeName, call, getId: () => id };
})();
</script>
