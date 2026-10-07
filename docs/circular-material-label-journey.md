# Retours de matières et préparation des labels

## Parcours complet

1. La marque choisit un label autorisé et crée une feuille de route pour ses propres produits. Pour les plans liés au contenu recyclé, les étapes de collecte, de transformation et d’incorporation sont identifiées explicitement.
2. Depuis l’étape de collecte, elle publie une demande de matière : nature, pureté minimale, états acceptés, quantité et bonus de points. Le label, les produits et la version du périmètre sont déterminés par l’étape choisie. Une demande générale reste possible.
3. Le consommateur enregistre son Smart Scan. Pour le parcours multimodal existant, le passeport doit être contrôlé et prêt pour le retour ; il choisit un point partenaire autorisé, déclare le retour puis le dépôt avec photo.
4. Le collecteur confirme le QR de dépôt, ajoute une photo et renseigne la pesée. L’admin vérifie la matière avant de la proposer aux marques.
5. Le service de compatibilité compare le textile aux demandes ouvertes. La marque réserve une proposition compatible. Les textiles déjà réservés et les quantités dépassant le besoin disponible ne peuvent pas être réservés une seconde fois.
6. Lorsque le textile est au centre (`hub_transfer`), le collecteur confirme sa remise à la marque avec le code de transfert, une photo et une note. Le consommateur suit cette étape sans déclarer un second dépôt. Dans le mode distinct `consumer_transfer`, il peut confirmer lui-même le transfert direct. La marque confirme la réception puis effectue son contrôle final. La validation termine l’échange et libère les points une seule fois. Elle crée un bilan matière en stock ; elle ne prouve pas un recyclage.
7. Si la demande ciblait une feuille de route toujours active et de même version, la matière lui est associée. Sinon la marque choisit une étape de collecte compatible de ses feuilles de route. Chaque matière est affectée intégralement à un seul périmètre : le fractionnement entre plusieurs plans n’est pas proposé.
8. La marque documente successivement le transfert au partenaire, la transformation et l’incorporation. Chaque étape exige une description et des justificatifs privés. La sortie recyclée ne dépasse pas l’entrée pesée ; la somme des quantités incorporées aux produits ne dépasse pas cette sortie.
9. Les boutons « Préparer les preuves » ouvrent l’étape correspondante avec la matière présélectionnée. La marque complète les actions, les résultats pour chaque produit et les pièces nécessaires. Les quantités et références de traçabilité proviennent du bilan unique.
10. L’admin consulte les preuves, le bilan et les justificatifs de seconde vie. Il approuve ou demande des corrections. Seules les étapes approuvées font progresser la feuille de route.
11. Le consommateur suit séparément la récompense et la seconde vie déclarée par la marque. Les justificatifs internes du partenaire et les documents de production restent réservés à la marque propriétaire et à l’admin.

## Progression et vocabulaire

- Feuille de route : pourcentage d’étapes approuvées, par périmètre de produits. Le tableau de bord et le catalogue des labels utilisent la moyenne des plans actifs pour présenter la préparation d’un label.
- Dossier documentaire général : ancien circuit de preuves pondérées, identifié comme tel. Son score n’est pas remplacé artificiellement par le pourcentage d’une feuille de route dont les critères et le périmètre diffèrent.
- Bilan matière : quantités physiques uniques. Plusieurs versions ou étapes peuvent référencer ce bilan sans additionner plusieurs fois la collecte.
- Le statut de réception validée et les résultats documentés ne constituent pas une certification. La décision officielle reste externe.

## Corrections, exceptions et historique

- Un refus de la marque avant validation remet le textile à disposition et retire le bonus non validé. Le refus après validation et la double validation sont bloqués.
- Une pesée estimée ne suffit pas pour une preuve de collecte. Le bilan conserve la pesée confirmée ; une modification ultérieure incohérente doit être vérifiée avant de continuer.
- Une marque peut corriger les quantités ou compléter les justificatifs de l’étape de seconde vie atteinte. Les événements et preuves précédents restent conservés. Les étapes qui référencent la matière repassent à corriger ; leurs nouvelles preuves doivent être revues.
- Une étape approuvée ne peut pas être modifiée silencieusement. Les anciennes décisions et versions restent consultables.
- Après affectation d’une matière, les produits, la situation de départ, l’objectif et le périmètre du plan sont conservés. Pour un nouveau périmètre, créer une autre feuille de route avec d’autres matières. Les échéances peuvent encore être ajustées.
- Une feuille de route archivée conserve l’historique et bloque les nouvelles actions. Elle peut être restaurée.
- Les anciennes soumissions sont conservées. Une matière déjà référencée dans les preuves en attente ou approuvées d’un autre plan ne peut pas être réaffectée. Les anciens retours validés peuvent être associés explicitement depuis les correspondances, sans conversion automatique d’une ancienne déclaration en traitement réalisé.

## Exemple démontrable

Pour une demande de 50 kg, un consommateur dépose 10 kg mesurés. Le contrôle final de la marque valide ses 100 points de collecte et 50 points bonus. Le bilan indique 10 kg en stock, puis 8 kg de sortie recyclée, puis 7 kg incorporés au produit du plan. Ces valeurs restent 10/8/7 kg même si les trois étapes référencent la même matière. Avec une feuille de route à sept étapes, l’approbation de ces trois étapes donne 43 % de préparation interne, sans délivrer de certification.

## Déploiement et validation

La migration additive `2026_10_08_000900_link_materials_to_label_roadmaps.php` ajoute les relations, les étapes circulaires et les bilans sans supprimer les preuves existantes. Le démarrage Render existant exécute déjà `php artisan migrate --force` puis compile les vues. Les preuves de seconde vie utilisent le stockage privé existant des feuilles de route : Cloudinary en production, stockage local simulé en test. Aucune nouvelle clé n’est nécessaire.

Les tests couvrent les quatre rôles, les étapes et quantités, les droits sur les preuves, la double affectation, les corrections avec nouvelle revue et la double validation des points.
