# Fichier autonome « Outils conseiller »

`outils-conseiller.html` réunit **tous les outils de /tools dans un seul fichier** (CSS, scripts et polices embarqués) :
il s'ouvre par double-clic, sans serveur, et peut être déposé sur un partage (OneDrive…).

## Zone de configuration (tout ce qui se modifie à la main)
Au tout début du fichier (balises `<script type="text/plain" id="cfg-...">`), en texte simple, une ligne par élément, champs séparés par `;`,
`#` pour un commentaire, rien à protéger (apostrophes comprises). Une ligne mal écrite est ignorée : la page ne casse pas.
- `cfg-general` : `adresse_retours:` (destinataire du .eml), `seuil_endettement:` et `presentation_demarrage:true/false`.
- `cfg-popup1`, `cfg-popup2` : fenêtres d'information (`actif:true`, titre, bouton, fréquence, dates, puis le message après la ligne `---`). Elles s'affichent à l'accueil, après la présentation de démarrage ; à la génération, elles reprennent celles de l'administration. Ne jamais écrire de balise script dans un message.
- `cfg-outils` : `nom_de_l_outil:true` ou `nom_de_l_outil:false` pour afficher ou masquer un outil (accueil, navigation et formulaire de retour).
- `cfg-rapide` : menu déroulant « Accès rapide » de la barre du haut. `nom_de_l_outil:true` l'ajoute au menu, `false` (ou absence de ligne) l'en retire ; l'ordre des lignes est l'ordre du menu.
- `cfg-agences` : Nom ; Numéro ; Adresse ; Code postal et ville ; Téléphone. Alimente le courrier et le bureau domiciliaire.
- `cfg-conseillers` : signataires du courrier (Prénom ; NOM ; Poste ; Téléphone ; E-mail).
- `cfg-politesses` : formules de politesse du courrier.
- `cfg-ponderation` et `cfg-lignes` : pondérations du budget (avec `credit` en dernière colonne pour un crédit) et postes affichés d'office.
Les valeurs de départ viennent de `standalone/config/*.txt` (et de la liste des agences du portail à la génération).
Barèmes, mémos et pièces justificatives ne sont pas dans cette zone : ils se gèrent dans l'administration, puis le fichier est régénéré.

## Charte graphique
Le fichier suit la charte du site ABhD.fr : indigo `#24285b`, jaune `#ffd200`, police Raleway, logo ABhD (fichiers dans `standalone/assets/`).
Aucune référence à la marque du portail : le générateur retire logos, noms, mentions légales et couleurs (`standalone/rebrand.php`)
et signale s'il en reste (« Références restantes… »). `standalone/theme.css` habille les outils, `standalone/shell.css` l'accueil et la navigation.
Le **générateur de courrier est repris à l'identique** de la page d'origine (en-tête avec logo, pied de page légal, couleurs, liste des collaborateurs) : le générateur ne le modifie pas.

## Contenu
- Présentation de démarrage plein écran (une fois par session ; « Revoir la présentation » en bas de l'accueil), fenêtres d'information et présentations « Comment ça marche ? » des outils, à la charte ABhD (`standalone/slideshow.css`).
- Accueil avec recherche et regroupement par thème, barre de navigation entre outils, bouton « Un retour ? ».
- Les deux modules de la page d'origine (calculateur de budget, générateur de courrier) sont conservés tels quels.
- Les autres outils sont les pages de /tools, générées à partir du même code que le portail.
- **Retour d'expérience** : le formulaire ne transmet rien ; il fabrique un fichier `.eml` (brouillon `X-Unsent`) que l'utilisateur
  télécharge puis ouvre dans sa messagerie pour l'envoyer.

## Limites
- Données figées à la date de génération (affichée en bas de l'accueil) : barèmes, plafonds, agences, zonage, procédures…
- Non inclus, car ils exigent un serveur : assistant IA, recherche service-public.fr, aides à la rédaction par IA du courrier.
- Les recherches **RGE** et **DPE** interrogent directement l'ADEME : accès à internet nécessaire (les cartes aussi).
- « Copier le lien » produit une adresse qui pointe vers le fichier de l'utilisateur : elle ne fonctionne que pour qui possède le même fichier au même emplacement.
- Ctrl+P imprime l'outil affiché ; les autres boutons « Imprimer » fonctionnent comme sur /tools.

## Régénérer le fichier
Depuis l'administration (Outils publics > « Fichier HTML autonome ») : bouton « Générer et télécharger », ou en ligne de commande :

    php scripts/build_standalone.php [--out=standalone/outils-conseiller.html] [--feedback-to=adresse@exemple.fr]

Lancé sur le serveur du portail, le script embarque les barèmes **tels qu'ils sont enregistrés en administration**, les agences,
le zonage A/B/C importé, et les procédures, codes et contacts validés. Sans base de données, il utilise les valeurs par défaut
(barèmes « à confirmer ») et laisse de côté procédures, codes et contacts. Les bibliothèques (Bootstrap, icônes, Leaflet, pdf-lib)
sont téléchargées au premier lancement puis gardées dans `standalone/.cache`.

L'adresse qui reçoit les retours est préremplie avec `adrien.bochard@cemp.caisse-epargne.fr` ; elle se change avec `--feedback-to` (ou dans le champ de l'administration) ; elle peut aussi être
modifiée dans le fichier généré : constante `FEEDBACK_TO` au début du dernier `<script>`.

## Fichiers
- `scripts/build_standalone.php` : orchestration (un processus PHP par outil, rendu des pages de `tools/`).
- `standalone/assemble.php` : traitement des pages et montage du fichier.
- `standalone/base_outils.html` : gabarit de départ (page d'origine : accueil, budget, courrier).
- `standalone/rebrand.php`, `theme.css`, `assets/` : charte ABhD ; `standalone/shell.css`, `shell.js` : accueil, navigation, formulaire de retour ; `shim.js` : amorce de chaque outil (liens, absence de serveur, ADEME, zonage).
- `standalone/pages/` : procédures, codes et contacts (instantané de la base).
