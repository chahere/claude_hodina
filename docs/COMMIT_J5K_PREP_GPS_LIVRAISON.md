# COMMIT PREP — J5K GPS livraison

## Objectif métier

À Mayotte, les adresses textuelles ne suffisent pas toujours. J5K doit permettre d'ajouter une position GPS optionnelle à l'adresse de livraison.

## Principe majeur

Le GPS n'est pas la source tarifaire. La source logistique reste la commune livrée.

```text
commune livrée = tarifs / zone / barge / graphe logistique
GPS = aide terrain pour trouver le client
```

## Périmètre MVP recommandé

- Ajouter latitude / longitude optionnelles sur `Address`.
- Ajouter un bouton client `Utiliser ma position actuelle`.
- Remplir des champs cachés GPS si le client accepte.
- Ne pas bloquer si le client refuse ou si le navigateur ne supporte pas la géolocalisation.
- Ajouter le GPS au snapshot de commande.
- Afficher un lien Google Maps / Waze dans l'admin commande.

## Hors périmètre MVP

- Carte interactive complexe.
- Optimisation automatique de tournée.
- Géocodage d'adresse.
- Paiement en ligne.

## Points de vigilance

- Consentement navigateur obligatoire.
- HTTPS obligatoire pour `navigator.geolocation`.
- Ne jamais remplacer la commune livrée par une commune devinée GPS.
- Garder la saisie d'adresse compréhensible pour les clients non techniques.
