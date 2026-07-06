# COMMIT documentation — J5T / J5U / J5V / cadrage J5W

Date : 27/06/2026

## Résumé

Mise à jour documentaire après les validations et décisions récentes :

- checkout invité simplifié validé recette ;
- expéditeur e-mails paramétrable EasyAdmin validé recette avec `commande@hodina.fr` ;
- documentation du code J5V-A présent dans les sources ;
- cadrage des prochains lots J5W : communes autorisées par produit, sous-zones `DeliveryArea`, planning par cutoff, livraison express, proposition d’heure livreur.

## Décisions ajoutées

- Formulation préparatoire pré-J5W-A : `DeliveryZone` protégeait les garde-fous PT/GT/barge/BFS. Depuis J5W-A, le forfait local est porté par `DeliveryPricingZone` / `DeliveryCommune.localPricingZone`.
- `DeliveryArea` sera une future couche planning/exploitation, extensible et administrable.
- Le découpage initial reprend Hodidagoni : Petite-Terre, Mamoudzou agglo, Grande-Terre Sud, Grande-Terre Nord et Centre.
- Labattoir reste dans les `DeliveryCommune` seedées et appartient à Petite-Terre.
- La livraison express sera une demande à confirmer humainement pendant le pilote.
- L’heure proposée plus tard par un livreur ne doit pas écraser l’heure demandée par le client.

## Validation documentaire

Cette mise à jour distingue :

- validé recette : J5T-A/J5T-A-bis, J5U-A ;
- présent dans le code mais validation recette non actée : J5V-A ;
- prévu / non codé : J5W-A à J5W-E.


## Mise à jour 29/06/2026

Le cadrage J5W du 27/06/2026 est partiellement supersédé : J5W-A désigne désormais les zones tarifaires locales par secteur. Le sujet « produits limités à certaines communes » est repoussé en J5Y-A pour éviter la collision documentaire.
