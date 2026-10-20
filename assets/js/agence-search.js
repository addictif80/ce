/**
 * Recherche dans une liste déroulante d'agences, par nom ou par numéro (sans accent ni casse).
 * agenceSearch(select) ajoute un champ de saisie au-dessus de la liste ; la valeur du <select> et son envoi ne changent pas.
 * Une seule agence trouvée : elle est sélectionnée. Une recherche vide rétablit toute la liste.
 */
function agenceSearch(sel, placeholder) {
  if (!sel || sel.dataset.agSearch) return; sel.dataset.agSearch = '1';
  const norm = s => String(s).normalize('NFD').replace(/[̀-ͯ]/g, '').toLowerCase();
  const first = sel.options[0] ? { v: sel.options[0].value, t: sel.options[0].text } : { v: '', t: 'Sélectionner une agence…' };
  const all = [...sel.options].slice(1).map(o => ({ v: o.value, t: o.text, n: norm(o.text) }));
  const inp = document.createElement('input');
  inp.type = 'search'; inp.autocomplete = 'off'; inp.className = 'agence-search no-print ' + (sel.classList.contains('dom-select') ? 'dom-select' : 'form-control form-control-sm');
  inp.placeholder = placeholder || 'Rechercher une agence par nom ou numéro…'; inp.setAttribute('aria-label', 'Rechercher une agence par nom ou numéro');
  inp.style.marginBottom = '4px';
  sel.parentNode.insertBefore(inp, sel);
  const fill = list => {
    const cur = sel.value; sel.innerHTML = '';
    sel.add(new Option(first.t, first.v));
    list.forEach(a => sel.add(new Option(a.t, a.v)));
    if ([...sel.options].some(o => o.value === cur)) sel.value = cur;
  };
  inp.addEventListener('input', () => {
    const q = norm(inp.value).trim();
    if (!q) { fill(all); return; }
    const words = q.split(/\s+/);
    let list = all.filter(a => words.every(w => a.n.includes(w)));
    if (/^\d+$/.test(q)) list.sort((a, b) => (b.n.includes('(' + q + ')') - a.n.includes('(' + q + ')')) || a.t.localeCompare(b.t));
    fill(list);
    if (list.length === 1) { sel.value = list[0].v; sel.dispatchEvent(new Event('change', { bubbles: true })); }
  });
}
