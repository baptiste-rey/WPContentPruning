# Fonctionnalité de Suppression en Masse

## Description

Cette fonctionnalité permet de supprimer plusieurs liens sortants en une seule action depuis l'interface d'administration du plugin Outbound Links Manager.

## Comment utiliser

1. **Accéder à la page des liens sortants**
   - Dans le menu WordPress, cliquez sur "Liens sortants"
   - Assurez-vous d'être sur l'onglet "Liens sortants"

2. **Sélectionner les liens à supprimer**
   - Cochez les cases à côté des liens que vous souhaitez supprimer
   - Vous pouvez cocher la case dans l'en-tête du tableau pour sélectionner tous les liens de la page

3. **Choisir l'action "Supprimer"**
   - Dans le menu déroulant "Actions groupées" en haut ou en bas du tableau
   - Sélectionnez "Supprimer"
   - Cliquez sur le bouton "Appliquer"

4. **Confirmation**
   - Les liens sélectionnés seront supprimés
   - Le texte d'ancre sera conservé dans les articles
   - Un message de confirmation s'affichera indiquant le nombre de liens supprimés

## Comportement

- **Conservation du texte** : Lorsqu'un lien est supprimé, seule la balise `<a>` est retirée. Le texte de l'ancre reste visible dans l'article.
- **Historique** : Chaque suppression est enregistrée dans l'historique du plugin avec les informations suivantes :
  - ID du lien supprimé
  - Type d'action : "delete"
  - Valeur avant suppression
  - Utilisateur ayant effectué l'action
  - Date et heure de l'action

## Sécurité

- **Permissions** : Seuls les utilisateurs ayant la capacité `manage_options` (administrateurs) peuvent effectuer des suppressions en masse
- **Nonce** : Chaque action est protégée par un nonce WordPress pour éviter les attaques CSRF
- **Vérification** : Le plugin vérifie l'existence du lien et du contenu avant toute suppression

## Modifications techniques

### Fichiers modifiés

1. **admin/class-list-table.php**
   - Ajout d'un constructeur pour initialiser le tableau avec les paramètres singulier/pluriel
   - Ajout de la méthode `get_bulk_actions()` pour définir l'action "Supprimer"

2. **admin/class-admin.php**
   - Ajout de la méthode `process_bulk_action()` pour traiter les actions en masse
   - Ajout de la méthode `handle_bulk_delete()` pour gérer la suppression en masse
   - Modification de `display_plugin_admin_page()` pour appeler le traitement des actions en masse

3. **admin/partials/admin-display.php**
   - Ajout de l'affichage des messages de succès/erreur après une suppression en masse

## Messages affichés

- **Succès** : "X lien(s) supprimé(s) avec succès."
- **Erreur** : "X erreur(s) rencontrée(s) lors de la suppression."

## Limitations

- La suppression est définitive et ne peut pas être annulée depuis l'interface
- Pour restaurer un lien supprimé, il faudra le recréer manuellement dans l'article
- L'historique permet de retrouver les informations d'un lien supprimé
