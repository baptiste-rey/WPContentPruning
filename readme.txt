=== Content Pruning ===
Contributors: baptisterey
Tags: seo, links, content pruning, google search console, internal links
Requires at least: 5.0
Tested up to: 6.7
Requires PHP: 7.4
Stable tag: 1.0.6
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Outil complet d'elagage et d'optimisation de contenu pour WordPress. Analysez vos liens, votre trafic et identifiez les contenus sous-performants.

== Description ==

Content Pruning est un plugin WordPress complet pour analyser et optimiser votre contenu. Il vous aide a identifier les pages sous-performantes, gerer vos liens sortants et internes, detecter les liens casses et prendre des decisions eclairees pour ameliorer votre SEO.

= Fonctionnalites principales =

**Liens sortants**

* Scan automatique de tous les liens sortants de votre site
* Verification des statuts HTTP (200, 301, 404, 500, etc.)
* Suppression en masse des liens casses
* Edition des liens (URL, ancre, target, nofollow)
* Purge et rescan complet
* Exclusion de domaines (YouTube, reseaux sociaux, etc.)

**Liens internes**

* Analyse du maillage interne de votre site
* Detection des pages sans liens internes
* Visualisation des liens internes par page

**Liens entrants**

* Identification des pages orphelines (aucun lien interne pointant vers elles)
* Statistiques de liens entrants par page
* Graphique de repartition des pages

**Trafic des pages**

* Integration avec Google Search Console
* Integration avec Google Analytics 4 (optionnel)
* Donnees d'impressions, clics, CTR et position moyenne
* Mots-cles Search Console par page
* Identification des pages sans impressions
* Suppression de pages avec redirection 301 automatique

**Outils supplementaires**

* Debug d'article (analyse detaillee des liens d'un article)
* Suppression en masse des commentaires
* Gestion des redirections 301

= Integration Google =

Le plugin se connecte a Google Search Console et Google Analytics 4 via OAuth 2.0 pour recuperer les vraies donnees de trafic de votre site. Vous aurez besoin d'un projet Google Cloud Console avec les API Search Console et Analytics activees.

== Installation ==

1. Telechargez le fichier ZIP du plugin
2. Dans votre tableau de bord WordPress, allez dans Extensions > Ajouter
3. Cliquez sur "Telecharger une extension" et selectionnez le fichier ZIP
4. Activez le plugin
5. Accedez au menu "Content Pruning" dans la barre laterale d'administration

= Configuration de l'API Google (optionnel) =

1. Creez un projet sur Google Cloud Console
2. Activez les API "Google Search Console API" et "Google Analytics Data API"
3. Creez des identifiants OAuth 2.0 (application web)
4. Ajoutez l'URL de redirection : `https://votre-site.com/wp-admin/admin.php?page=outbound-links-manager&tab=settings`
5. Copiez le Client ID et le Client Secret dans les parametres du plugin
6. Cliquez sur "Se connecter a Google" et autorisez l'acces

== Frequently Asked Questions ==

= Le plugin fonctionne-t-il sans connexion Google ? =

Oui. Les fonctionnalites de scan des liens sortants, internes et entrants fonctionnent sans aucune configuration Google. L'integration Google est uniquement necessaire pour l'onglet "Trafic Page".

= Quels types de contenus sont scannes ? =

Le plugin scanne les articles (posts) et les pages (pages) publies. Le contenu des champs meta est egalement analyse.

= Comment exclure certains domaines du scan ? =

Rendez-vous dans l'onglet "Parametres" et ajoutez les domaines a exclure (un par ligne). Par exemple : youtube.com, facebook.com, etc.

= La suppression d'une page cree-t-elle une redirection ? =

Oui. Lorsque vous supprimez une page depuis l'onglet "Trafic Page", le plugin met la page a la corbeille et cree automatiquement une redirection 301 vers l'URL de votre choix (par defaut la page d'accueil).

= Le plugin ralentit-il mon site ? =

Non. Le plugin fonctionne uniquement dans l'interface d'administration. Il n'ajoute aucun script ni style sur le front-end de votre site (a l'exception de la gestion des redirections 301).

== Screenshots ==

1. Onglet Liens sortants - Vue d'ensemble avec statistiques
2. Onglet Liens internes - Maillage interne
3. Onglet Liens entrants - Detection des pages orphelines
4. Onglet Trafic Page - Donnees Google Search Console
5. Parametres - Configuration des domaines exclus et API Google

== Changelog ==

= 1.0.6 =
* Amelioration de la gestion des redirections 301
* Correction de bugs mineurs

= 1.0.5 =
* Ajout de l'onglet Liens entrants
* Detection des pages orphelines
* Graphique de repartition des pages

= 1.0.4 =
* Integration Google Analytics 4
* Ajout des mots-cles Search Console par page

= 1.0.3 =
* Ajout de la verification des statuts HTTP
* Suppression en masse des liens casses

= 1.0.2 =
* Ajout du scan des liens internes
* Amelioration de l'interface

= 1.0.1 =
* Ajout de l'exclusion de domaines
* Debug d'article

= 1.0.0 =
* Version initiale
* Scan des liens sortants
* Integration Google Search Console

== Upgrade Notice ==

= 1.0.6 =
Mise a jour recommandee. Ameliorations de stabilite et corrections de bugs.
