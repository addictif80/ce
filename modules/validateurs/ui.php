<?php
// Validateurs de numéros (IBAN, RIB, SIREN/SIRET, carte bancaire, numéro de sécurité sociale, BIC) : contrôle de format uniquement,
// entièrement dans le navigateur. Les numéros saisis ne sont jamais envoyés, ni enregistrés, ni repris dans un lien de partage ou un document imprimé.
require __DIR__ . '/../_sim/style.php';
?>
<style>.vd-ok{color:#198754;font-weight:700}.vd-ko{color:#dc3545;font-weight:700}.vd-out td{padding:3px 8px}.vd-mono{font-family:ui-monospace,Menlo,Consolas,monospace;letter-spacing:.5px}</style>
<div class="alert alert-secondary small no-print"><i class="fas fa-shield-halved me-1"></i>Ces contrôles vérifient seulement la <strong>forme</strong> d'un numéro (longueur, clé de contrôle) : ils ne prouvent pas que le compte, la société ou la carte existe. Les numéros saisis restent dans votre navigateur, ne sont pas enregistrés et ne sont pas repris dans les liens ou les impressions.</div>
<ul class="nav nav-pills mb-3 no-print" id="vdTabs">
  <?php foreach (['iban' => 'IBAN', 'rib' => 'RIB', 'siret' => 'SIREN / SIRET', 'carte' => 'Carte bancaire', 'nir' => 'N° de sécurité sociale', 'bic' => 'BIC'] as $k => $l): ?>
  <li class="nav-item"><button type="button" class="nav-link <?= $k === 'iban' ? 'active' : '' ?>" data-vd="<?= $k ?>"><?= e($l) ?></button></li>
  <?php endforeach; ?>
</ul>
<div class="cap-card" data-pane="iban"><h2>Contrôle d'un IBAN</h2>
  <input class="form-control vd-mono" data-noshare autocomplete="off" spellcheck="false" id="vd_iban" placeholder="FR76 3000 6000 0112 3456 7890 189">
  <table class="vd-out mt-3 w-100"><tbody id="vo_iban"></tbody></table></div>
<div class="cap-card" data-pane="rib" hidden><h2>Clé RIB et IBAN français</h2>
  <div class="row g-2">
    <div class="col-sm-3"><label class="form-label small mb-0">Code banque (5)</label><input class="form-control vd-mono" data-noshare autocomplete="off" id="vd_rb" maxlength="5"></div>
    <div class="col-sm-3"><label class="form-label small mb-0">Code guichet (5)</label><input class="form-control vd-mono" data-noshare autocomplete="off" id="vd_rg" maxlength="5"></div>
    <div class="col-sm-4"><label class="form-label small mb-0">N° de compte (11)</label><input class="form-control vd-mono" data-noshare autocomplete="off" id="vd_rc" maxlength="11"></div>
    <div class="col-sm-2"><label class="form-label small mb-0">Clé RIB (2, facultatif)</label><input class="form-control vd-mono" data-noshare autocomplete="off" id="vd_rk" maxlength="2"></div>
  </div>
  <table class="vd-out mt-3 w-100"><tbody id="vo_rib"></tbody></table></div>
<div class="cap-card" data-pane="siret" hidden><h2>SIREN (9 chiffres) ou SIRET (14 chiffres)</h2>
  <input class="form-control vd-mono" data-noshare autocomplete="off" id="vd_siret" placeholder="552 081 317 00014">
  <table class="vd-out mt-3 w-100"><tbody id="vo_siret"></tbody></table></div>
<div class="cap-card" data-pane="carte" hidden><h2>Numéro de carte bancaire</h2>
  <input class="form-control vd-mono" data-noshare autocomplete="off" id="vd_carte" placeholder="Numéro à 13-19 chiffres">
  <div class="form-text">Seule la clé de Luhn est contrôlée ; ne saisissez jamais le cryptogramme ni la date d'expiration.</div>
  <table class="vd-out mt-3 w-100"><tbody id="vo_carte"></tbody></table></div>
<div class="cap-card" data-pane="nir" hidden><h2>Numéro de sécurité sociale (NIR, 13 chiffres + clé)</h2>
  <input class="form-control vd-mono" data-noshare autocomplete="off" id="vd_nir" placeholder="1 85 05 78 006 084 36">
  <table class="vd-out mt-3 w-100"><tbody id="vo_nir"></tbody></table></div>
<div class="cap-card" data-pane="bic" hidden><h2>Code BIC / SWIFT</h2>
  <input class="form-control vd-mono" data-noshare autocomplete="off" id="vd_bic" placeholder="AGRIFRPP">
  <table class="vd-out mt-3 w-100"><tbody id="vo_bic"></tbody></table></div>
<script>
(function(){
  const $=id=>document.getElementById(id);
  const row=(l,v,cls)=>`<tr><td class="text-muted" style="width:42%">${l}</td><td class="${cls||''}">${v}</td></tr>`;
  const ok=t=>row('Résultat','<i class="fas fa-circle-check me-1"></i>'+t,'vd-ok'), ko=t=>row('Résultat','<i class="fas fa-circle-xmark me-1"></i>'+t,'vd-ko');
  const clean=s=>String(s||'').replace(/[\s.\-]/g,'').toUpperCase();
  const toDigits=s=>s.replace(/[A-Z]/g,c=>c.charCodeAt(0)-55);
  const mod97=s=>{let r=0;for(const ch of s) r=(r*10+(+ch))%97;return r;};
  const group=(s,n)=>s.replace(new RegExp('(.{'+n+'})','g'),'$1 ').trim();
  const LEN={AD:24,AT:20,BE:16,BG:22,CH:21,CY:28,CZ:24,DE:22,DK:18,EE:20,ES:24,FI:18,FR:27,GB:22,GR:27,HR:21,HU:28,IE:22,IS:26,IT:27,LI:21,LT:20,LU:20,LV:21,MC:27,MT:31,NL:18,NO:15,PL:28,PT:25,RO:24,SE:24,SI:19,SK:24,SM:27};
  const ribLetters=s=>s.replace(/[A-Z]/g,c=>{const v=c.charCodeAt(0)-64;return String(v<=9?v:v<=18?v-9:v-17);});
  const ribKey=(b,g,c)=>{const n=BigInt(ribLetters(b))*89n+BigInt(ribLetters(g))*15n+BigInt(ribLetters(c))*3n;return 97-Number(n%97n);};
  const luhn=s=>{let sum=0,alt=false;for(let i=s.length-1;i>=0;i--){let d=+s[i];if(alt){d*=2;if(d>9)d-=9;}sum+=d;alt=!alt;}return sum%10===0;};
  function iban(){
    const s=clean($('vd_iban').value), o=$('vo_iban'); if(!s){o.innerHTML=row('','Saisissez un IBAN.');return;}
    const cc=s.slice(0,2); let h='';
    if(!/^[A-Z]{2}\d{2}[A-Z0-9]+$/.test(s)) {o.innerHTML=ko('Format invalide (2 lettres, 2 chiffres, puis le numéro de compte)');return;}
    const exp=LEN[cc]; if(exp&&s.length!==exp) h+=row('Longueur',s.length+' caractères (attendu : '+exp+' pour '+cc+')','vd-ko');
    const valid=mod97(toDigits(s.slice(4)+s.slice(0,4)))===1&&(!exp||s.length===exp);
    h=(valid?ok('IBAN valide (clé de contrôle correcte)'):ko('IBAN invalide'))+row('Présentation',group(s,4),'vd-mono')+h;
    if(cc==='FR'&&s.length===27){const b=s.slice(4,9),g=s.slice(9,14),c=s.slice(14,25),k=s.slice(25);const kk=String(ribKey(b,g,c)).padStart(2,'0');
      h+=row('Code banque',b)+row('Code guichet',g)+row('N° de compte',c)+row('Clé RIB',k+(k===kk?' (correcte)':' (attendue : '+kk+')'),k===kk?'vd-ok':'vd-ko');}
    o.innerHTML=h;
  }
  function rib(){
    const b=clean($('vd_rb').value),g=clean($('vd_rg').value),c=clean($('vd_rc').value),k=clean($('vd_rk').value), o=$('vo_rib');
    if(!b&&!g&&!c){o.innerHTML=row('','Saisissez le code banque, le code guichet et le numéro de compte.');return;}
    if(!/^[A-Z0-9]{5}$/.test(b)||!/^[A-Z0-9]{5}$/.test(g)||!/^[A-Z0-9]{11}$/.test(c)){o.innerHTML=ko('Banque (5), guichet (5) et compte (11) : caractères attendus manquants ou invalides');return;}
    const kk=String(ribKey(b,g,c)).padStart(2,'0'); let h=row('Clé RIB calculée',kk,'vd-mono');
    if(k) h=(k===kk?ok('Clé RIB correcte'):ko('Clé RIB incorrecte (attendue : '+kk+')'))+h;
    const bban=b+g+c+kk, chk=String(98-mod97(toDigits(bban+'FR00'))).padStart(2,'0'), ib='FR'+chk+bban;
    h+=row('IBAN correspondant',group(ib,4),'vd-mono')+row('BIC','Non déductible du RIB : à rechercher auprès de la banque');
    o.innerHTML=h;
  }
  function siret(){
    const s=clean($('vd_siret').value),o=$('vo_siret'); if(!s){o.innerHTML=row('','Saisissez un SIREN ou un SIRET.');return;}
    if(!/^\d+$/.test(s)||(s.length!==9&&s.length!==14)){o.innerHTML=ko('Un SIREN compte 9 chiffres et un SIRET 14 chiffres');return;}
    const siren=s.slice(0,9); let valid;
    valid=luhn(s);
    if(!valid&&s.length===14&&siren==='356000000') valid=[...s].reduce((t,d)=>t+ +d,0)%5===0; // La Poste : règle particulière (somme des chiffres multiple de 5)
    let h=(valid?ok((s.length===9?'SIREN':'SIRET')+' valide (clé de Luhn correcte)'):ko('Numéro invalide'))+row('Présentation',s.length===9?group(s,3):siren.replace(/(\d{3})(\d{3})(\d{3})/,'$1 $2 $3')+' '+s.slice(9),'vd-mono');
    if(s.length===14) h+=row('SIREN',siren)+row('NIC (établissement)',s.slice(9));
    if(valid){const k=(12+3*(Number(BigInt(siren)%97n)))%97;h+=row('N° de TVA intracommunautaire (calculé)','FR'+String(k).padStart(2,'0')+siren,'vd-mono');}
    o.innerHTML=h;
  }
  function carte(){
    const s=clean($('vd_carte').value),o=$('vo_carte'); if(!s){o.innerHTML=row('','Saisissez un numéro de carte.');return;}
    if(!/^\d{13,19}$/.test(s)){o.innerHTML=ko('Le numéro compte entre 13 et 19 chiffres');return;}
    let net='Réseau non identifié';
    if(/^4/.test(s)) net='Visa'; else if(/^(5[1-5]|2(2[2-9][1-9]|2[3-9]|[3-6]|7[01]|720))/.test(s)) net='Mastercard'; else if(/^3[47]/.test(s)) net='American Express'; else if(/^(36|30[0-5]|38|39)/.test(s)) net='Diners Club'; else if(/^62/.test(s)) net='UnionPay'; else if(/^35/.test(s)) net='JCB';
    const masked=s.slice(0,6)+'•'.repeat(Math.max(0,s.length-10))+s.slice(-4);
    o.innerHTML=(luhn(s)?ok('Clé de Luhn correcte'):ko('Clé de Luhn incorrecte'))+row('Réseau (selon le début du numéro)',net)+row('Numéro masqué',group(masked,4),'vd-mono');
  }
  function nir(){
    const s0=clean($('vd_nir').value),o=$('vo_nir'); if(!s0){o.innerHTML=row('','Saisissez un numéro de sécurité sociale.');return;}
    const s=s0; if(!/^[12]\d{4}(\d{2}|2[AB])\d{6}(\d{2})?$/.test(s)||(s.length!==13&&s.length!==15)){o.innerHTML=ko('Format attendu : 13 chiffres (+ clé de 2 chiffres) ; sexe 1 ou 2');return;}
    let h=''; const base=s.slice(0,13), dep=base.slice(5,7);
    const num=BigInt(base.replace('2A','19').replace('2B','18')), key=97-Number(num%97n), kk=String(key).padStart(2,'0');
    if(s.length===15) h+=(s.slice(13)===kk?ok('Clé correcte'):ko('Clé incorrecte (attendue : '+kk+')')); else h+=row('Clé de contrôle calculée',kk,'vd-mono');
    h+=row('Sexe',base[0]==='1'?'Masculin':'Féminin')+row('Année de naissance (2 derniers chiffres)',base.slice(1,3))+row('Mois de naissance',base.slice(3,5))+row('Département / pays',dep);
    o.innerHTML=h;
  }
  function bic(){
    const s=clean($('vd_bic').value),o=$('vo_bic'); if(!s){o.innerHTML=row('','Saisissez un code BIC.');return;}
    if(!/^[A-Z]{4}[A-Z]{2}[A-Z0-9]{2}([A-Z0-9]{3})?$/.test(s)){o.innerHTML=ko('Un BIC compte 8 ou 11 caractères : 4 lettres (banque), 2 lettres (pays), 2 caractères (lieu), 3 caractères facultatifs (agence)');return;}
    o.innerHTML=ok('Format de BIC valide')+row('Banque',s.slice(0,4))+row('Pays',s.slice(4,6))+row('Localisation',s.slice(6,8))+row('Agence',s.length===11?s.slice(8):'—');
  }
  [['iban',iban],['rb',rib],['rg',rib],['rc',rib],['rk',rib],['siret',siret],['carte',carte],['nir',nir],['bic',bic]].forEach(([k,f])=>$('vd_'+k).addEventListener('input',f));
  [iban,rib,siret,carte,nir,bic].forEach(f=>f());
  document.querySelectorAll('[data-vd]').forEach(b=>b.onclick=()=>{
    document.querySelectorAll('[data-vd]').forEach(x=>x.classList.toggle('active',x===b));
    document.querySelectorAll('[data-pane]').forEach(p=>p.hidden=p.dataset.pane!==b.dataset.vd);
  });
  window.addEventListener('beforeunload',()=>{['iban','rb','rg','rc','rk','siret','carte','nir','bic'].forEach(k=>$('vd_'+k).value='');});
})();
</script>
<?php // Pas de boutons communs (lien, copie, impression, e-mail) : ils reprendraient des numéros sensibles. ?>
