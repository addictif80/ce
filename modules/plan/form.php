<?php
// Champs communs du plan de financement et du comparateur de scénarios : projet, financement de départ, foyer.
// Le calcul est fait par le moteur du module crédit immobilier (voir assets/js/plan-adapter.js).
?>
<div class="cap-card"><h2><i class="fas fa-house me-2 text-danger"></i>Le projet</h2>
  <div class="row g-2">
    <div class="col-6"><label class="form-label small mb-0">Type d'opération</label><select class="form-select" id="pl_type">
      <option value="ANCIEN_SANS_TRAVAUX">Ancien sans travaux</option><option value="ANCIEN_AVEC_TRAVAUX">Ancien avec travaux</option><option value="NEUF_VEFA">Neuf (VEFA)</option><option value="CONSTRUCTION_CCMI">Construction avec CCMI</option><option value="CONSTRUCTION_SANS_CCMI">Construction sans CCMI</option></select></div>
    <div class="col-6"><label class="form-label small mb-0">Usage du bien</label><select class="form-select" id="pl_usage"><option value="RP">Résidence principale</option><option value="RS">Résidence secondaire</option><option value="RL">Investissement locatif</option></select></div>
    <div class="col-6"><label class="form-label small mb-0">Primo-accédant</label><select class="form-select" id="pl_primo"><option value="OUI">Oui</option><option value="NON">Non</option></select></div>
    <div class="col-6"><label class="form-label small mb-0">Origine du client</label><select class="form-select" id="pl_canal"><option value="AGENCE">Client issu de l'agence</option><option value="PRESCRIPTION">Prescription immobilière</option></select></div>
    <div class="col-12" id="pl_geo" style="display:none"><div class="row g-2">
      <div class="col-6"><label class="form-label small mb-0">Département du bien</label><select class="form-select" id="pl_dep" data-noshare><option value="">Choisir…</option></select></div>
      <div class="col-6"><label class="form-label small mb-0">Commune</label><input type="text" class="form-control" id="pl_commune" data-noshare list="pl_communes" autocomplete="off" placeholder="Nom de la commune" disabled><datalist id="pl_communes"></datalist></div></div></div>
    <div class="col-6"><label class="form-label small mb-0">Zone du bien <span class="text-muted" id="pl_zinfo"></span></label><select class="form-select" id="pl_zone"><option value="">À renseigner</option><option value="A">A bis / A</option><option value="B1">B1</option><option value="B2">B2</option><option value="C">C</option></select></div>
  </div></div>
<div class="cap-card"><h2><i class="fas fa-coins me-2 text-danger"></i>Coût et apport</h2>
  <div class="row g-2">
    <div class="col-6"><label class="form-label small mb-0">Acquisition (€)</label><input type="number" min="0" step="any" class="form-control" id="pl_acq" value="150000"></div>
    <div class="col-6"><label class="form-label small mb-0">Travaux (€)</label><input type="number" min="0" step="any" class="form-control" id="pl_trav" value="0"></div>
    <div class="col-6"><label class="form-label small mb-0">dont éligibles à l'EcoPTZ (€)</label><input type="number" min="0" step="any" class="form-control" id="pl_traveco" value="0"></div>
    <div class="col-6"><label class="form-label small mb-0">Frais d'agence / négociation (€)</label><input type="number" min="0" step="any" class="form-control" id="pl_nego" value="0"></div>
    <div class="col-6"><label class="form-label small mb-0">Frais de notaire (€) <a href="#" id="pl_est_not" class="small" data-noshare>estimer</a></label><input type="number" min="0" step="any" class="form-control" id="pl_notaire" value="11250"></div>
    <div class="col-6"><label class="form-label small mb-0">Frais divers (€)</label><input type="number" min="0" step="any" class="form-control" id="pl_divers" value="0"></div>
    <div class="col-6"><label class="form-label small mb-0">Garantie (€) <a href="#" id="pl_est_gar" class="small" data-noshare>estimer</a></label><input type="number" min="0" step="any" class="form-control" id="pl_garantie" value="1800"></div>
    <div class="col-6"><label class="form-label small mb-0">Frais de dossier (€)</label><input type="number" min="0" step="any" class="form-control" id="pl_fd" value="500"></div>
    <div class="col-6"><label class="form-label small mb-0">Apport personnel (€)</label><input type="number" min="0" step="any" class="form-control" id="pl_apport" value="20000"></div>
    <div class="col-6"><label class="form-label small mb-0">Prêt 1 % patronal (€)</label><input type="number" min="0" step="any" class="form-control" id="pl_patr" value="0"></div>
  </div>
  <div class="form-text">Les estimations sont des ordres de grandeur : notaire 7,5 % de l'acquisition (ancien) ou 2,5 % (neuf) ; garantie 1,2 % du montant à financer. Pour un calcul précis, utilisez l'outil « Frais de notaire ».</div></div>
<div class="cap-card"><h2><i class="fas fa-users me-2 text-danger"></i>Les emprunteurs</h2>
  <div class="row g-2">
    <div class="col-12"><label class="form-label small mb-0">Enfants à charge</label><input type="number" min="0" max="10" step="1" class="form-control" id="pl_enf" value="0" style="max-width:120px"></div>
  </div>
  <?php foreach ([1 => 'Emprunteur 1', 2 => 'Emprunteur 2 (facultatif)'] as $i => $lab): ?>
  <div class="fw-semibold mt-3 mb-1"><?= e($lab) ?></div>
  <div class="row g-2">
    <div class="col-6"><label class="form-label small mb-0">Revenus nets mensuels (€)</label><input type="number" min="0" step="any" class="form-control" id="pl_rev<?= $i ?>" value="<?= $i === 1 ? 2800 : 0 ?>"></div>
    <div class="col-6"><label class="form-label small mb-0">Charges conservées (€ par mois)</label><input type="number" min="0" step="any" class="form-control" id="pl_chg<?= $i ?>" value="0"></div>
    <div class="col-4"><label class="form-label small mb-0">RFR N-2 (€)</label><input type="number" min="0" step="any" class="form-control" id="pl_rfr<?= $i ?>" value="<?= $i === 1 ? 30000 : 0 ?>"></div>
    <div class="col-4"><label class="form-label small mb-0">Date de naissance</label><input type="date" class="form-control" id="pl_nais<?= $i ?>"></div>
    <div class="col-4"><label class="form-label small mb-0">Assurance (% par an)</label><input type="number" min="0" step="any" class="form-control" id="pl_ass<?= $i ?>" value="0.3"></div>
  </div>
  <?php endforeach; ?>
  <div class="form-text">Avec un PTZ, le revenu fiscal de référence à retenir est celui de l'année N-2. Les dates de naissance servent aux critères d'âge des prêts jeunes.</div></div>
