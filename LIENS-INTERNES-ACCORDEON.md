# Affichage des Liens Internes en Accordéon

## Description

Cette fonctionnalité permet d'afficher la liste détaillée des liens internes de chaque page dans un menu en accordéon. Pour chaque lien, l'URL de destination et le texte d'ancre sont affichés.

## Comment utiliser

1. **Accéder à l'onglet des liens internes**
   - Dans le menu WordPress, cliquez sur "Liens sortants"
   - Sélectionnez l'onglet "Liens internes"

2. **Lancer un scan des liens internes**
   - Si ce n'est pas déjà fait, cliquez sur "Lancer un nouveau scan des liens internes"
   - Attendez que le scan se termine (une barre de progression s'affiche)

3. **Voir les détails des liens**
   - Dans le tableau, chaque page affiche son titre et le nombre de liens internes
   - Cliquez sur le bouton "Voir les X lien(s) interne(s)" pour ouvrir l'accordéon
   - L'accordéon affiche tous les liens internes de cette page avec :
     - **URL** : L'URL de destination du lien (cliquable)
     - **Ancre** : Le texte d'ancre utilisé pour le lien

4. **Fermer l'accordéon**
   - Cliquez à nouveau sur le bouton pour fermer l'accordéon
   - L'icône de flèche indique l'état ouvert/fermé

## Informations affichées

Pour chaque lien interne, vous verrez :
- **URL de destination** : L'URL complète vers laquelle le lien pointe (sur votre site)
- **Texte d'ancre** : Le texte visible du lien dans le contenu

## Caractéristiques techniques

### Stockage des données

Les détails des liens internes sont stockés dans une nouvelle table `wp_internal_links_details` avec les colonnes suivantes :
- `id` : Identifiant unique
- `post_id` : ID du contenu contenant le lien
- `target_url` : URL de destination du lien
- `anchor_text` : Texte d'ancre du lien
- `created_at` : Date de création de l'enregistrement

### Scan des liens

Lors du scan des liens internes :
1. Le scanner analyse le contenu HTML de chaque page
2. Il extrait tous les liens `<a>` pointant vers votre site
3. Pour chaque lien, il sauvegarde l'URL et le texte d'ancre
4. Les URLs relatives sont converties en URLs absolues
5. Les anciens liens sont supprimés et remplacés par les nouveaux à chaque scan

### Affichage

- **Accordéon interactif** : Animation fluide à l'ouverture/fermeture
- **Style moderne** : Design cohérent avec l'interface WordPress
- **Icônes** : Flèche qui pivote pour indiquer l'état
- **Liens cliquables** : Les URLs peuvent être ouvertes directement dans un nouvel onglet

## Modifications apportées

### Fichiers modifiés

1. **includes/class-database.php**
   - Ajout de la table `wp_internal_links_details`
   - Structure pour stocker les détails de chaque lien interne

2. **includes/class-internal-scanner.php**
   - Remplacement de `count_internal_links()` par `extract_internal_links()`
   - Ajout de `save_internal_links_details()` pour sauvegarder les détails
   - Utilisation de DOMDocument pour un parsing HTML plus robuste

3. **admin/class-internal-list-table.php**
   - Ajout d'un accordéon dans la colonne "Titre de la page"
   - Ajout de `get_internal_links_details()` pour récupérer les liens
   - Amélioration du rendu HTML avec boutons et accordéon

4. **admin/js/admin.js**
   - Ajout du gestionnaire d'événements pour l'accordéon
   - Animation slide pour l'ouverture/fermeture
   - Rotation de l'icône de flèche

5. **admin/css/admin.css**
   - Styles pour l'accordéon et son contenu
   - Mise en forme des liens et ancres
   - Effets de transition et hover

## Mise à jour de la base de données

Pour activer cette fonctionnalité sur une installation existante :

1. **Désactivez le plugin** depuis l'interface WordPress
2. **Réactivez le plugin** pour créer la nouvelle table
3. **Lancez un nouveau scan** des liens internes pour peupler la table

Ou exécutez manuellement cette requête SQL :

```sql
CREATE TABLE IF NOT EXISTS wp_internal_links_details (
    id bigint(20) NOT NULL AUTO_INCREMENT,
    post_id bigint(20) NOT NULL,
    target_url varchar(2083) NOT NULL,
    anchor_text text,
    created_at datetime DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY post_id (post_id),
    KEY target_url (target_url(191))
) DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

## Utilisation avancée

### Filtrage des résultats
Utilisez la barre de recherche pour filtrer les pages par titre et voir uniquement celles qui vous intéressent.

### Tri des colonnes
Cliquez sur les en-têtes de colonnes pour trier :
- Par nombre de liens internes (ordre décroissant par défaut)
- Par titre de page
- Par date de publication

### Export des données
Les liens affichés dans l'accordéon peuvent être copiés manuellement. Pour un export automatique, une fonctionnalité future pourrait être ajoutée.

## Avantages

- **Visibilité complète** : Voyez exactement quels liens internes existent sur chaque page
- **Optimisation SEO** : Identifiez les opportunités de maillage interne
- **Audit rapide** : Vérifiez la qualité des ancres utilisées
- **Interface intuitive** : Accordéon pour ne pas surcharger l'affichage

## Support

Pour toute question ou problème avec cette fonctionnalité, vérifiez :
1. Que le scan des liens internes a été exécuté
2. Que la table `wp_internal_links_details` existe dans la base de données
3. Que JavaScript est activé dans votre navigateur
