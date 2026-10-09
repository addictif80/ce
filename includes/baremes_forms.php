<?php
/**
 * Formulaires d'administration des barèmes (champs libellés, sans JSON) : affichage et lecture des données saisies.
 * Convention : tous les champs sont postés sous d[...] ; baremeCollect() reconstruit la structure attendue par baremeCheck().
 */
require_once __DIR__ . '/../modules/ptz/bareme.php';
const BF_ZONES = ['A' => 'Zone A / A bis', 'B1' => 'Zone B1', 'B2' => 'Zone B2', 'C' => 'Zone C'];

function bfNum($name, $val, $step = 'any', $w = 92) {
    return '<input type="number" step="' . $step . '" name="d' . $name . '" value="' . e((string)$val) . '" class="form-control form-control-sm text-end" style="width:' . $w . 'px;display:inline-block" required>';
}
function bfHelp($txt) { return '<div class="form-text mb-2">' . $txt . '</div>'; }
function bfTitle($n, $t) { return '<h6 class="mt-4 mb-1 border-bottom pb-1"><span class="badge bg-secondary me-2">' . $n . '</span>' . $t . '</h6>'; }
function bfNumList($v) { return is_array($v) ? array_values($v) : []; }

function baremeRenderForm($cle, array $d) {
    ob_start();
    if ($cle === 'doublissimo') { $c = $d['campagne']; ?>
        <?= bfHelp('Montant du Doublissimo = pourcentage du financement total (coût du projet − apport), dans la limite du plafond.') ?>
        <table class="table table-sm align-middle w-auto"><tbody>
            <tr><td>Pourcentage du financement total (%)</td><td><?= bfNum('[pourcentage]', $d['pourcentage'], 'any') ?></td></tr>
            <tr><td>Plafond hors campagne (€)</td><td><?= bfNum('[plafond]', $d['plafond'], '1') ?></td></tr>
            <tr><td>Durée minimale (mois)</td><td><?= bfNum('[duree_min_mois]', $d['duree_min_mois'], '1') ?></td></tr>
            <tr><td>Durée maximale (mois, dans la limite de la durée du prêt principal)</td><td><?= bfNum('[duree_max_mois]', $d['duree_max_mois'], '1') ?></td></tr>
        </tbody></table>
        <?= bfTitle(2, 'Offre exceptionnelle (campagne)') ?>
        <?= bfHelp('Pendant la campagne, le plafond est relevé (selon le canal d\'origine du client) et un taux fixe préférentiel s\'applique. Sans date de début, il n\'y a pas de campagne ; sans date de fin, la campagne reste ouverte jusqu\'à ce que vous en saisissiez une.') ?>
        <table class="table table-sm align-middle w-auto"><tbody>
            <tr><td>Du</td><td><input type="date" name="d[campagne][debut]" value="<?= e($c['debut']) ?>" class="form-control form-control-sm" style="width:160px"></td></tr>
            <tr><td>Au (inclus, facultatif)</td><td><input type="date" name="d[campagne][fin]" value="<?= e($c['fin']) ?>" class="form-control form-control-sm" style="width:160px"></td></tr>
            <tr><td>Plafond canal agence (€)</td><td><?= bfNum('[campagne][plafond_agence]', $c['plafond_agence'], '1') ?></td></tr>
            <tr><td>Plafond prescription immobilière (€)</td><td><?= bfNum('[campagne][plafond_prescription]', $c['plafond_prescription'], '1') ?></td></tr>
            <tr><td>Taux fixe pendant la campagne (%)</td><td><?= bfNum('[campagne][taux]', $c['taux'], '0.01') ?></td></tr>
        </tbody></table>
    <?php } elseif ($cle === 'primo_jeune') { ?>
        <?= bfHelp('Prêt à 0 % sans frais de dossier, complémentaire au PTZ (obligatoire) : montant limité à un pourcentage du financement total (PTZ compris) et à un plafond.') ?>
        <table class="table table-sm align-middle w-auto"><tbody>
            <tr><td>Pourcentage du financement total (%)</td><td><?= bfNum('[pourcentage]', $d['pourcentage'], 'any') ?></td></tr>
            <tr><td>Montant maximum (€)</td><td><?= bfNum('[plafond]', $d['plafond'], '1') ?></td></tr>
            <tr><td>Durée maximale (mois, multiple de 12)</td><td><?= bfNum('[duree_max_mois]', $d['duree_max_mois'], '12') ?></td></tr>
            <tr><td>Âge maximum de l'un des emprunteurs (ans, inclus)</td><td><?= bfNum('[age_max]', $d['age_max'], '1') ?></td></tr>
        </tbody></table>
    <?php } elseif ($cle === 'primoz') { ?>
        <?= bfHelp('Le Primoz est amorti après un long différé (intérêts seuls) ; il se combine avec un prêt principal classique.') ?>
        <table class="table table-sm align-middle w-auto"><tbody>
            <tr><td>Part minimale du financement total (%)</td><td><?= bfNum('[pct_min]', $d['pct_min'], 'any') ?></td></tr>
            <tr><td>Part maximale du financement total (%)</td><td><?= bfNum('[pct_max]', $d['pct_max'], 'any') ?></td></tr>
            <tr><td>Montant minimum (€)</td><td><?= bfNum('[montant_min]', $d['montant_min'], '1') ?></td></tr>
            <tr><td>Montant maximum (€)</td><td><?= bfNum('[montant_max]', $d['montant_max'], '1') ?></td></tr>
            <tr><td>Durée minimale (mois)</td><td><?= bfNum('[duree_min_mois]', $d['duree_min_mois'], '12') ?></td></tr>
            <tr><td>Durée maximale (mois)</td><td><?= bfNum('[duree_max_mois]', $d['duree_max_mois'], '12') ?></td></tr>
            <tr><td>Différé minimal (mois)</td><td><?= bfNum('[differe_min_mois]', $d['differe_min_mois'], '1') ?></td></tr>
            <tr><td>Différé maximal (mois)</td><td><?= bfNum('[differe_max_mois]', $d['differe_max_mois'], '1') ?></td></tr>
            <tr><td>Âge maximum des emprunteurs (ans, inclus)</td><td><?= bfNum('[age_max]', $d['age_max'], '1') ?></td></tr>
        </tbody></table>
    <?php } elseif ($cle === 'grandioz') { ?>
        <?= bfHelp('Prêt à échéances progressives : l\'échéance augmente chaque année du pourcentage indiqué.') ?>
        <table class="table table-sm align-middle w-auto"><tbody>
            <tr><td>Progression annuelle des échéances (%)</td><td><?= bfNum('[progression]', $d['progression'], '0.1') ?></td></tr>
            <tr><td>Montant minimum du financement (€)</td><td><?= bfNum('[montant_min]', $d['montant_min'], '1') ?></td></tr>
            <tr><td>Durée minimale (mois)</td><td><?= bfNum('[duree_min_mois]', $d['duree_min_mois'], '12') ?></td></tr>
            <tr><td>Durée maximale (mois)</td><td><?= bfNum('[duree_max_mois]', $d['duree_max_mois'], '12') ?></td></tr>
            <tr><td>Âge maximum de l'un des emprunteurs (ans, inclus)</td><td><?= bfNum('[age_max]', $d['age_max'], '1') ?></td></tr>
        </tbody></table>
    <?php } elseif ($cle === 'usure') { $cats = array_merge($d['categories'], array_fill(0, 4, ['groupe' => '', 'libelle' => '', 'taux' => ''])); ?>
        <?= bfHelp('Saisissez les seuils publiés (TAEG maximal autorisé, en %) et leur période d\'application. Une ligne dont le libellé est vide est ignorée ; le groupe regroupe les catégories à l\'affichage (ex. « Prêts immobiliers », « Crédits à la consommation »).') ?>
        <table class="table table-sm align-middle w-auto"><tbody>
            <tr><td>Période d\'application (libellé)</td><td><input name="d[periode]" value="<?= e($d['periode'] ?? '') ?>" class="form-control form-control-sm" style="width:240px" placeholder="4e trimestre 2026"></td></tr>
            <tr><td>Du</td><td><input type="date" name="d[du]" value="<?= e($d['du'] ?? '') ?>" class="form-control form-control-sm" style="width:160px"></td></tr>
            <tr><td>Au</td><td><input type="date" name="d[au]" value="<?= e($d['au'] ?? '') ?>" class="form-control form-control-sm" style="width:160px"></td></tr>
        </tbody></table>
        <table class="table table-sm align-middle"><thead><tr><th>Groupe</th><th>Catégorie</th><th>Seuil (%)</th></tr></thead><tbody>
        <?php foreach ($cats as $i => $c): ?><tr>
            <td style="width:24%"><input name="d[categories][<?= $i ?>][groupe]" value="<?= e($c['groupe'] ?? '') ?>" class="form-control form-control-sm"></td>
            <td><input name="d[categories][<?= $i ?>][libelle]" value="<?= e($c['libelle'] ?? '') ?>" class="form-control form-control-sm"></td>
            <td style="width:110px"><input type="number" step="0.01" name="d[categories][<?= $i ?>][taux]" value="<?= e((string)($c['taux'] ?? '')) ?>" class="form-control form-control-sm text-end"></td></tr><?php endforeach; ?>
        </tbody></table>
    <?php } elseif ($cle === 'saisie') { $q = bfNumList($d['quotites']); ?>
        <?= bfHelp('Tranches de rémunération nette <strong>annuelle</strong> : la quotité de chaque tranche s\'applique à la part de rémunération comprise dans cette tranche. Au-delà du dernier seuil, tout est saisissable.') ?>
        <table class="table table-sm align-middle w-auto"><thead><tr><th>Tranche</th><th>Jusqu\'à (€ par an)</th><th>Part saisissable (%)</th></tr></thead><tbody>
        <?php foreach (bfNumList($d['seuils']) as $i => $v): ?><tr><td>Tranche <?= $i + 1 ?></td><td><?= bfNum("[seuils][$i]", $v, '1') ?></td><td><?= bfNum("[quotites][$i]", $q[$i] ?? 0, 'any') ?></td></tr><?php endforeach; ?>
        <tr><td>Au-delà</td><td class="text-muted">—</td><td><?= bfNum('[quotites][6]', $q[6] ?? 100, 'any') ?></td></tr>
        </tbody></table>
        <table class="table table-sm align-middle w-auto"><tbody>
            <tr><td>Majoration par personne à charge (€ par an)</td><td><?= bfNum('[charge_annuelle]', $d['charge_annuelle'], '1') ?></td></tr>
            <tr><td>RSA pour une personne seule (€ par mois) = solde bancaire insaisissable</td><td><?= bfNum('[rsa_mensuel]', $d['rsa_mensuel'], '0.01') ?></td></tr>
        </tbody></table>
    <?php } elseif (baremeIsMemo($cle)) { $gi = 0; $ch = baremeCatalog()[$cle]['champs'] ?? ['Libellé', 'Valeur', 'Précision (facultatif)']; ?>
        <?= bfHelp('Chaque groupe est un encadré du mémo. Une ligne vide est ignorée ; pour supprimer une ligne, videz son libellé. Le champ « Valeur » est libre (montant, durée, texte).') ?>
        <div id="memoGroups-<?= e($cle) ?>">
        <?php foreach ($d['groupes'] as $g): $li = 0; ?>
            <div class="border rounded p-2 mb-3 memo-group"><input name="d[groupes][<?= $gi ?>][titre]" value="<?= e($g['titre']) ?>" class="form-control fw-bold mb-2" placeholder="Titre du groupe">
            <table class="table table-sm align-middle mb-1"><tbody>
            <?php foreach (array_merge($g['lignes'], [['libelle' => '', 'valeur' => '', 'note' => '']]) as $l): ?>
                <tr><td style="width:34%"><input name="d[groupes][<?= $gi ?>][lignes][<?= $li ?>][libelle]" value="<?= e($l['libelle']) ?>" class="form-control form-control-sm" placeholder="<?= e($ch[0]) ?>"></td>
                    <td style="width:22%"><input name="d[groupes][<?= $gi ?>][lignes][<?= $li ?>][valeur]" value="<?= e($l['valeur']) ?>" class="form-control form-control-sm" placeholder="<?= e($ch[1]) ?>"></td>
                    <td><input name="d[groupes][<?= $gi ?>][lignes][<?= $li ?>][note]" value="<?= e($l['note'] ?? '') ?>" class="form-control form-control-sm" placeholder="<?= e($ch[2]) ?>"></td></tr>
            <?php $li++; endforeach; ?></tbody></table></div>
        <?php $gi++; endforeach; ?>
        <div class="border rounded p-2 mb-3 memo-group bg-light"><input name="d[groupes][<?= $gi ?>][titre]" class="form-control fw-bold mb-2" placeholder="Nouveau groupe (titre) — laissez vide pour ne rien ajouter">
            <table class="table table-sm align-middle mb-1"><tbody>
            <?php for ($li = 0; $li < 4; $li++): ?>
                <tr><td style="width:34%"><input name="d[groupes][<?= $gi ?>][lignes][<?= $li ?>][libelle]" class="form-control form-control-sm" placeholder="<?= e($ch[0]) ?>"></td>
                    <td style="width:22%"><input name="d[groupes][<?= $gi ?>][lignes][<?= $li ?>][valeur]" class="form-control form-control-sm" placeholder="<?= e($ch[1]) ?>"></td>
                    <td><input name="d[groupes][<?= $gi ?>][lignes][<?= $li ?>][note]" class="form-control form-control-sm" placeholder="<?= e($ch[2]) ?>"></td></tr>
            <?php endfor; ?></tbody></table></div>
        </div>
    <?php } elseif ($cle === 'fiscalite') { $F = $d; ?>
        <?= bfHelp('Paramètres d\'estimation : ils ne remplacent pas un calcul fiscal personnalisé.') ?>
        <?= bfTitle(1, 'Prélèvements sur les revenus de l\'épargne') ?>
        <table class="table table-sm align-middle w-auto"><tbody>
            <tr><td>Impôt forfaitaire (PFU, part impôt sur le revenu) (%)</td><td><?= bfNum('[pfu_ir]', $F['pfu_ir'], '0.1') ?></td></tr>
            <tr><td>Prélèvements sociaux : produits financiers (PEA, compte-titres, comptes à terme…) (%)</td><td><?= bfNum('[ps_standard]', $F['ps_standard'], '0.1') ?></td></tr>
            <tr><td>Prélèvements sociaux : assurance-vie et épargne logement (%)</td><td><?= bfNum('[ps_assurance_vie]', $F['ps_assurance_vie'], '0.1') ?></td></tr>
        </tbody></table>
        <?= bfTitle(2, 'Assurance-vie') ?>
        <table class="table table-sm align-middle w-auto"><tbody>
            <tr><td>Rachat après 8 ans : taux d\'impôt sur les gains, après abattement (%)</td><td><?= bfNum('[av_taux_8ans]', $F['av_taux_8ans'], '0.1') ?></td></tr>
            <tr><td>Rachat après 8 ans : abattement annuel, personne seule (€)</td><td><?= bfNum('[av_abattement_seul]', $F['av_abattement_seul'], '1') ?></td></tr>
            <tr><td>Rachat après 8 ans : abattement annuel, couple (€)</td><td><?= bfNum('[av_abattement_couple]', $F['av_abattement_couple'], '1') ?></td></tr>
            <tr><td>Rachat après 8 ans : seuil de primes versées au taux réduit (€)</td><td><?= bfNum('[av_seuil_primes]', $F['av_seuil_primes'], '1') ?></td></tr>
            <tr><td>Décès : abattement par bénéficiaire, primes versées avant 70 ans (€)</td><td><?= bfNum('[av_990i_abattement]', $F['av_990i_abattement'], '1') ?></td></tr>
            <tr><td>Décès : taux jusqu\'au seuil (%)</td><td><?= bfNum('[av_990i_taux1]', $F['av_990i_taux1'], '0.01') ?></td></tr>
            <tr><td>Décès : seuil de la première tranche, après abattement (€)</td><td><?= bfNum('[av_990i_seuil]', $F['av_990i_seuil'], '1') ?></td></tr>
            <tr><td>Décès : taux au-delà (%)</td><td><?= bfNum('[av_990i_taux2]', $F['av_990i_taux2'], '0.01') ?></td></tr>
        </tbody></table>
        <?= bfTitle(3, 'Impôt sur le revenu (revenus de l\'année précédente)') ?>
        <table class="table table-sm align-middle w-auto"><thead><tr><th>Tranche</th><th>Jusqu\'à (€ par part)</th><th>Taux (%)</th></tr></thead><tbody>
        <?php $tt = bfNumList($F['ir_taux']); foreach (bfNumList($F['ir_seuils']) as $i => $v): ?><tr><td>Tranche <?= $i + 1 ?></td><td><?= bfNum("[ir_seuils][$i]", $v, '1') ?></td><td><?= bfNum("[ir_taux][$i]", $tt[$i] ?? 0, '0.1') ?></td></tr><?php endforeach; ?>
        <tr><td>Au-delà</td><td class="text-muted">—</td><td><?= bfNum('[ir_taux][4]', $tt[4] ?? 45, '0.1') ?></td></tr></tbody></table>
        <?= bfTitle(4, 'PER') ?>
        <table class="table table-sm align-middle w-auto"><tbody>
            <tr><td>Plafond annuel de la Sécurité sociale de l\'année précédente (€)</td><td><?= bfNum('[pass_n1]', $F['pass_n1'], '1') ?></td></tr>
            <tr><td>Plafond de déduction : part des revenus professionnels (%)</td><td><?= bfNum('[per_pct]', $F['per_pct'], '0.1') ?></td></tr>
            <tr><td>Plafond maximal : nombre de PASS pris en compte</td><td><?= bfNum('[per_plafond_pass]', $F['per_plafond_pass'], '1') ?></td></tr>
        </tbody></table>
    <?php } elseif ($cle === 'hcsf') { ?>
        <?= bfHelp('Les grandes règles du Haut Conseil de stabilité financière (HCSF) appliquées aux crédits immobiliers.') ?>
        <table class="table table-sm align-middle w-auto"><tbody>
            <tr><td>Taux d'endettement maximal (%, assurance comprise)</td><td><?= bfNum('[taux_endettement_max]', $d['taux_endettement_max'], '0.1') ?></td></tr>
            <tr><td>Durée maximale du crédit (années)</td><td><?= bfNum('[duree_max_annees]', $d['duree_max_annees'], '1') ?></td></tr>
            <tr><td>Durée maximale pour un bien neuf ou avec travaux (années)</td><td><?= bfNum('[duree_max_annees_neuf]', $d['duree_max_annees_neuf'], '1') ?></td></tr>
        </tbody></table>
    <?php } elseif ($cle === 'notaire') { ?>
        <?= bfTitle(1, 'Émoluments du notaire (rémunération, avant TVA)') ?>
        <?= bfHelp('Barème dégressif : un taux par tranche du prix. Laissez « jusqu\'à » vide sur la dernière ligne (= au-delà de la tranche précédente). Ajoutez ou supprimez des tranches si le barème change.') ?>
        <table class="table table-sm align-middle w-auto" id="bfEmol"><thead><tr><th>Jusqu'à (€)</th><th>Taux (%)</th><th></th></tr></thead><tbody>
        <?php foreach (bfNumList($d['emoluments']) as $i => $t): ?>
            <tr><td><input type="number" step="any" name="d[emol][<?= $i ?>][borne]" value="<?= $t[0] === null ? '' : e((string)$t[0]) ?>" placeholder="au-delà" class="form-control form-control-sm text-end" style="width:130px"></td>
                <td><input type="number" step="any" name="d[emol][<?= $i ?>][taux]" value="<?= e((string)$t[1]) ?>" class="form-control form-control-sm text-end" style="width:100px"></td>
                <td><button type="button" class="btn btn-sm btn-outline-danger" onclick="this.closest('tr').remove()"><i class="fas fa-xmark"></i></button></td></tr>
        <?php endforeach; ?></tbody></table>
        <button type="button" class="btn btn-sm btn-outline-secondary" onclick="bfAddRow('bfEmol','emol',[['borne','130','au-delà'],['taux','100','']])"><i class="fas fa-plus"></i> Ajouter une tranche</button>

        <?= bfTitle(2, 'Droits et taxes') ?>
        <?= bfHelp('Ces taux s\'appliquent au prix du bien (hors mobilier). Le taux départemental est celui des droits de mutation du département (voir tableau 3).') ?>
        <table class="table table-sm align-middle w-auto"><tbody>
            <tr><td>Droits et taxes pour un bien <strong>neuf</strong> (%)</td><td><?= bfNum('[droits_neuf]', $d['droits_neuf'], 'any') ?></td></tr>
            <tr><td>Taxe communale, ancien (%)</td><td><?= bfNum('[taxe_communale]', $d['taxe_communale'], 'any') ?></td></tr>
            <tr><td>Frais d'assiette, ancien (% des droits départementaux)</td><td><?= bfNum('[frais_assiette]', $d['frais_assiette'], 'any') ?></td></tr>
            <tr><td>TVA sur les émoluments (%)</td><td><?= bfNum('[tva]', $d['tva'], 'any') ?></td></tr>
            <tr><td>Contribution de sécurité immobilière (% du prix, minimum 15 €)</td><td><?= bfNum('[csi]', $d['csi'], 'any') ?></td></tr>
        </tbody></table>

        <?= bfTitle(3, 'Droits de mutation par département (ancien)') ?>
        <?= bfHelp('Le simulateur propose la liste des départements. « Taux » = taux départemental appliqué (5 % dans les départements qui ont voté la hausse de 2025) ; « Taux primo-accédant » = taux de droit commun, appliqué aux primo-accédants qui achètent leur résidence principale (le tableau d\'impots.gouv.fr indique que le taux majoré est « hors primo-accédant »). Si vous videz la liste, le simulateur propose les taux usuels.') ?>
        <div class="mb-2">Taux des autres départements (%) : <?= bfNum('[taux_departemental_defaut]', $d['taux_departemental_defaut'], 'any', 80) ?></div>
        <table class="table table-sm align-middle w-auto" id="bfDep"><thead><tr><th>Code</th><th>Département</th><th>Taux (%)</th><th>Taux primo-accédant (%)</th><th></th></tr></thead><tbody>
        <?php $i = 0; foreach ((array)$d['departements'] as $code => $dep): ?>
            <tr><td><input name="d[dep][<?= $i ?>][code]" value="<?= e((string)$code) ?>" maxlength="4" class="form-control form-control-sm" style="width:70px"></td>
                <td><input name="d[dep][<?= $i ?>][nom]" value="<?= e($dep['nom'] ?? '') ?>" maxlength="60" class="form-control form-control-sm" style="width:200px"></td>
                <td><input type="number" step="any" name="d[dep][<?= $i ?>][taux]" value="<?= e((string)$dep['taux']) ?>" class="form-control form-control-sm text-end" style="width:90px"></td>
                <td><input type="number" step="any" name="d[dep][<?= $i ?>][taux_primo]" value="<?= e((string)($dep['taux_primo'] ?? $dep['taux'])) ?>" class="form-control form-control-sm text-end" style="width:90px"></td>
                <td><button type="button" class="btn btn-sm btn-outline-danger" onclick="this.closest('tr').remove()"><i class="fas fa-xmark"></i></button></td></tr>
        <?php $i++; endforeach; ?></tbody></table>
        <button type="button" class="btn btn-sm btn-outline-secondary" onclick="bfAddRow('bfDep','dep',[['code','70',''],['nom','200',''],['taux','90',''],['taux_primo','90','']])"><i class="fas fa-plus"></i> Ajouter un département</button>
    <?php } else { /* ptz */ $zl = BF_ZONES; ?>
        <?= bfTitle(1, 'Coefficient familial') ?>
        <?= bfHelp('Sert à déterminer la tranche de revenus : revenu retenu ÷ coefficient. Il dépend du nombre de personnes qui occuperont le logement.') ?>
        <table class="table table-sm align-middle w-auto"><thead><tr><?php foreach (['1 personne', '2', '3', '4', '5 et plus'] as $h): ?><th class="text-center"><?= $h ?></th><?php endforeach; ?></tr></thead>
            <tbody><tr><?php foreach (bfNumList($d['coeff_familial']) as $i => $v): ?><td><?= bfNum("[coeff_familial][$i]", $v, '0.01', 78) ?></td><?php endforeach; ?></tr></tbody></table>

        <?= bfTitle(2, 'Revenu maximal pour avoir droit au PTZ') ?>
        <?= bfHelp('Selon la zone du logement et le nombre de personnes (revenu fiscal de référence). Au-dessus, pas de PTZ.') ?>
        <div class="table-responsive"><table class="table table-sm align-middle w-auto"><thead><tr><th></th><?php foreach (['1', '2', '3', '4', '5', '6', '7', '8 et +'] as $h): ?><th class="text-center"><?= $h ?> pers.</th><?php endforeach; ?></tr></thead><tbody>
        <?php foreach ($zl as $z => $zn): ?><tr><th class="text-nowrap"><?= $zn ?></th><?php foreach (bfNumList($d['plafonds_ressources'][$z]) as $i => $v): ?><td><?= bfNum("[plafonds_ressources][$z][$i]", $v, '1') ?></td><?php endforeach; ?></tr><?php endforeach; ?>
        </tbody></table></div>

        <?= bfTitle(3, 'Tranches de revenus') ?>
        <?= bfHelp('Limite haute de chaque tranche, comparée au revenu retenu divisé par le coefficient familial. Au-delà de la tranche 4, pas de PTZ.') ?>
        <table class="table table-sm align-middle w-auto"><thead><tr><th></th><?php foreach (['Tranche 1', 'Tranche 2', 'Tranche 3', 'Tranche 4'] as $h): ?><th class="text-center"><?= $h ?> jusqu'à</th><?php endforeach; ?></tr></thead><tbody>
        <?php foreach ($zl as $z => $zn): ?><tr><th class="text-nowrap"><?= $zn ?></th><?php foreach (bfNumList($d['tranches'][$z]) as $i => $v): ?><td><?= bfNum("[tranches][$z][$i]", $v, '1') ?></td><?php endforeach; ?></tr><?php endforeach; ?>
        </tbody></table>

        <?= bfTitle(4, 'Coût maximal de l\'opération pris en compte') ?>
        <?= bfHelp('Si le coût de l\'opération dépasse ce plafond, le PTZ est calculé sur le plafond.') ?>
        <table class="table table-sm align-middle w-auto"><thead><tr><th></th><?php foreach (['1', '2', '3', '4', '5 et +'] as $h): ?><th class="text-center"><?= $h ?> pers.</th><?php endforeach; ?></tr></thead><tbody>
        <?php foreach ($zl as $z => $zn): ?><tr><th class="text-nowrap"><?= $zn ?></th><?php foreach (bfNumList($d['plafonds_operation'][$z]) as $i => $v): ?><td><?= bfNum("[plafonds_operation][$z][$i]", $v, '1') ?></td><?php endforeach; ?></tr><?php endforeach; ?>
        </tbody></table>

        <?= bfTitle(5, 'Part du coût financée par le PTZ (%)') ?>
        <?= bfHelp('Pourcentage du coût (plafonné) selon la tranche, pour chaque type de logement, et zones où ce type de logement est accepté.') ?>
        <table class="table table-sm align-middle w-auto"><thead><tr><th>Type de logement</th><?php foreach (['T1', 'T2', 'T3', 'T4'] as $h): ?><th class="text-center"><?= $h ?></th><?php endforeach; ?><th>Zones acceptées</th></tr></thead><tbody>
        <?php foreach ($d['types'] as $k => $t): ?><tr><td><?= e($t['label']) ?></td>
            <?php foreach (bfNumList($t['quotites']) as $i => $v): ?><td><?= bfNum("[types][$k][quotites][$i]", $v, 'any', 70) ?></td><?php endforeach; ?>
            <td class="text-nowrap"><?php foreach ($zl as $z => $zn): ?><label class="me-2"><input type="checkbox" name="d[types][<?= e($k) ?>][zones][]" value="<?= $z ?>" <?= in_array($z, $t['zones'], true) ? 'checked' : '' ?>> <?= $z ?></label><?php endforeach; ?></td></tr><?php endforeach; ?>
        </tbody></table>

        <?= bfTitle(6, 'Durée de remboursement') ?>
        <?= bfHelp('Durée totale et période de différé (pendant laquelle on ne rembourse pas), en années, selon la tranche.') ?>
        <table class="table table-sm align-middle w-auto"><thead><tr><th></th><th>Durée totale (ans)</th><th>dont différé (ans)</th></tr></thead><tbody>
        <?php foreach (bfNumList($d['durees']) as $i => $du): ?><tr><th>Tranche <?= $i + 1 ?></th><td><?= bfNum("[durees][$i][total]", $du['total'], '1', 80) ?></td><td><?= bfNum("[durees][$i][differe]", $du['differe'], '1', 80) ?></td></tr><?php endforeach; ?>
        </tbody></table>

        <?= bfTitle(7, 'Revenu retenu') ?>
        <?= bfHelp('Le revenu pris en compte est le plus élevé entre le revenu fiscal de référence et le coût de l\'opération divisé par ce nombre.') ?>
        <div>Coût de l'opération ÷ <?= bfNum('[diviseur_cout]', $d['diviseur_cout'], 'any', 70) ?></div>
    <?php }
    return ob_get_clean();
}

/** Lit les champs postés (d[...]) et reconstruit la structure du barème ; la validation est faite ensuite par baremeCheck(). */
function baremeCollect($cle, array $p) {
    $n = fn($v) => is_numeric(str_replace(',', '.', (string)$v)) ? (float)str_replace(',', '.', (string)$v) : 0.0;
    $row = fn($a) => array_map($n, array_values((array)$a));
    if ($cle === 'hcsf') {
        return ['taux_endettement_max' => $n($p['taux_endettement_max'] ?? 0), 'duree_max_annees' => $n($p['duree_max_annees'] ?? 0), 'duree_max_annees_neuf' => $n($p['duree_max_annees_neuf'] ?? 0)];
    }
    if ($cle === 'usure') {
        $cats = [];
        foreach ((array)($p['categories'] ?? []) as $c) {
            $lib = mb_substr(trim((string)($c['libelle'] ?? '')), 0, 150); if ($lib === '' || trim((string)($c['taux'] ?? '')) === '') continue;
            $cats[] = ['groupe' => mb_substr(trim((string)($c['groupe'] ?? '')), 0, 100), 'libelle' => $lib, 'taux' => $n($c['taux'])];
        }
        $date = fn($v) => preg_match('/^\d{4}-\d{2}-\d{2}$/', (string)$v) ? $v : '';
        return ['periode' => mb_substr(trim((string)($p['periode'] ?? '')), 0, 80), 'du' => $date($p['du'] ?? ''), 'au' => $date($p['au'] ?? ''), 'categories' => $cats];
    }
    if ($cle === 'saisie') {
        return ['seuils' => $row($p['seuils'] ?? []), 'quotites' => $row($p['quotites'] ?? []), 'charge_annuelle' => $n($p['charge_annuelle'] ?? 0), 'rsa_mensuel' => $n($p['rsa_mensuel'] ?? 0)];
    }
    if (baremeIsMemo($cle)) {
        $groupes = [];
        foreach ((array)($p['groupes'] ?? []) as $g) {
            $titre = mb_substr(trim((string)($g['titre'] ?? '')), 0, 120); $lignes = [];
            foreach ((array)($g['lignes'] ?? []) as $l) {
                $lib = mb_substr(trim((string)($l['libelle'] ?? '')), 0, 200); if ($lib === '') continue;
                $lignes[] = ['libelle' => $lib, 'valeur' => mb_substr(trim((string)($l['valeur'] ?? '')), 0, 400), 'note' => mb_substr(trim((string)($l['note'] ?? '')), 0, 600)];
            }
            if ($titre !== '' && $lignes) $groupes[] = ['titre' => $titre, 'lignes' => $lignes];
        }
        return ['groupes' => $groupes];
    }
    if ($cle === 'fiscalite') {
        $out = [];
        foreach (['pfu_ir', 'ps_standard', 'ps_assurance_vie', 'av_taux_8ans', 'av_abattement_seul', 'av_abattement_couple', 'av_seuil_primes', 'av_990i_abattement', 'av_990i_taux1', 'av_990i_seuil', 'av_990i_taux2', 'pass_n1', 'per_pct', 'per_plafond_pass'] as $k) $out[$k] = $n($p[$k] ?? 0);
        $out['ir_seuils'] = $row($p['ir_seuils'] ?? []); $out['ir_taux'] = $row($p['ir_taux'] ?? []);
        return $out;
    }
    if ($cle === 'primoz' || $cle === 'grandioz') {
        $out = [];
        foreach (array_keys(baremeCatalog()[$cle]['default']) as $k) $out[$k] = $n($p[$k] ?? 0);
        return $out;
    }
    if ($cle === 'primo_jeune') {
        return ['pourcentage' => $n($p['pourcentage'] ?? 0), 'plafond' => $n($p['plafond'] ?? 0), 'duree_max_mois' => $n($p['duree_max_mois'] ?? 0), 'age_max' => $n($p['age_max'] ?? 0)];
    }
    if ($cle === 'doublissimo') {
        $c = (array)($p['campagne'] ?? []);
        $date = fn($v) => preg_match('/^\d{4}-\d{2}-\d{2}$/', (string)$v) ? $v : '';
        return ['pourcentage' => $n($p['pourcentage'] ?? 0), 'plafond' => $n($p['plafond'] ?? 0), 'duree_min_mois' => $n($p['duree_min_mois'] ?? 0), 'duree_max_mois' => $n($p['duree_max_mois'] ?? 0),
            'campagne' => ['debut' => $date($c['debut'] ?? ''), 'fin' => $date($c['fin'] ?? ''), 'plafond_agence' => $n($c['plafond_agence'] ?? 0), 'plafond_prescription' => $n($c['plafond_prescription'] ?? 0), 'taux' => $n($c['taux'] ?? 0)]];
    }
    if ($cle === 'notaire') {
        $emol = [];
        foreach ((array)($p['emol'] ?? []) as $t) {
            if (!isset($t['taux']) || trim((string)$t['taux']) === '') continue;
            $emol[] = [trim((string)($t['borne'] ?? '')) === '' ? null : $n($t['borne']), $n($t['taux'])];
        }
        usort($emol, fn($a, $b) => ($a[0] ?? INF) <=> ($b[0] ?? INF));
        $deps = [];
        foreach ((array)($p['dep'] ?? []) as $dep) {
            $code = mb_substr(trim((string)($dep['code'] ?? '')), 0, 4);
            if ($code === '' || trim((string)($dep['taux'] ?? '')) === '') continue;
            $deps[$code] = ['nom' => mb_substr(trim((string)($dep['nom'] ?? '')), 0, 60), 'taux' => $n($dep['taux']), 'taux_primo' => trim((string)($dep['taux_primo'] ?? '')) === '' ? $n($dep['taux']) : $n($dep['taux_primo'])];
        }
        ksort($deps, SORT_NATURAL);
        return ['emoluments' => $emol, 'droits_neuf' => $n($p['droits_neuf'] ?? 0), 'taxe_communale' => $n($p['taxe_communale'] ?? 0), 'frais_assiette' => $n($p['frais_assiette'] ?? 0),
            'tva' => $n($p['tva'] ?? 0), 'csi' => $n($p['csi'] ?? 0), 'taux_departemental_defaut' => $n($p['taux_departemental_defaut'] ?? 0), 'departements' => $deps ?: new stdClass()];
    }
    // PTZ : les libellés des types de logement sont fixes (repris du barème par défaut)
    $def = ptzDefaultBareme();
    $out = ['millesime' => $def['millesime'], 'valide' => false, 'coeff_familial' => $row($p['coeff_familial'] ?? []), 'diviseur_cout' => $n($p['diviseur_cout'] ?? 0),
        'plafonds_ressources' => [], 'tranches' => [], 'plafonds_operation' => [], 'types' => [], 'durees' => []];
    foreach (array_keys(BF_ZONES) as $z) {
        $out['plafonds_ressources'][$z] = $row($p['plafonds_ressources'][$z] ?? []);
        $out['tranches'][$z] = $row($p['tranches'][$z] ?? []);
        $out['plafonds_operation'][$z] = $row($p['plafonds_operation'][$z] ?? []);
    }
    foreach ($def['types'] as $k => $t) {
        $out['types'][$k] = ['label' => $t['label'], 'quotites' => $row($p['types'][$k]['quotites'] ?? []),
            'zones' => array_values(array_intersect(array_keys(BF_ZONES), (array)($p['types'][$k]['zones'] ?? [])))];
    }
    foreach ((array)($p['durees'] ?? []) as $d) $out['durees'][] = ['total' => $n($d['total'] ?? 0), 'differe' => $n($d['differe'] ?? 0)];
    return $out;
}
