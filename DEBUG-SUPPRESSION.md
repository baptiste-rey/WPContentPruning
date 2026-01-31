# Instructions de Débogage - Bouton Supprimer

## Problème
Le bouton "Supprimer" n'a aucun effet.

## Étapes de Débogage

### 1. **Vider le cache du navigateur**
Le problème le plus probable est que le navigateur utilise l'ancienne version du fichier JavaScript.

**Actions à faire :**
- Appuyez sur `Ctrl + F5` (Windows) ou `Cmd + Shift + R` (Mac) pour forcer le rechargement
- OU videz complètement le cache du navigateur
- OU ouvrez la console développeur (F12) → Onglet "Network" → Cochez "Disable cache"

### 2. **Vérifier la console JavaScript**
Ouvrez la console du navigateur pour voir les messages de débogage :

**Comment ouvrir la console :**
- Appuyez sur `F12`
- OU clic droit → "Inspecter" → Onglet "Console"

**Messages attendus quand vous cliquez sur "Supprimer" :**
```
Bouton supprimer cliqué
ID du lien à supprimer: [un nombre]
URL AJAX: [l'URL de votre site]/wp-admin/admin-ajax.php
Nonce: [une chaîne de caractères]
Réponse AJAX reçue: {success: true, data: "..."}
```

### 3. **Problèmes possibles et solutions**

#### A. Aucun message dans la console
**Problème :** Le JavaScript ne s'exécute pas ou le bouton n'est pas cliqué
**Solution :**
1. Videz le cache du navigateur (Ctrl + F5)
2. Désactivez puis réactivez le plugin dans WordPress
3. Vérifiez que jQuery est bien chargé (tapez `jQuery` dans la console, vous devriez voir `function`)

#### B. Message "Erreur AJAX" dans la console
**Problème :** La requête AJAX n'aboutit pas
**Solution :**
1. Vérifiez l'URL AJAX dans la console (doit être `/wp-admin/admin-ajax.php`)
2. Vérifiez que le nonce est bien présent
3. Regardez la réponse complète dans l'onglet "Network" de la console

#### C. Message "Permission denied"
**Problème :** Vous n'avez pas les droits administrateur
**Solution :**
1. Connectez-vous avec un compte administrateur
2. Vérifiez les capacités de votre utilisateur

#### D. Message "Lien introuvable"
**Problème :** Le lien n'existe plus dans la base de données
**Solution :**
1. Relancez un scan complet
2. Rechargez la page des liens sortants

### 4. **Forcer le rechargement du plugin**

Si rien ne fonctionne, essayez ces étapes :

```
1. Désactivez le plugin dans WordPress
2. Supprimez le dossier du plugin (sauvegarde recommandée)
3. Téléversez à nouveau le dossier du plugin
4. Réactivez le plugin
5. Videz le cache du navigateur (Ctrl + F5)
```

### 5. **Test manuel via la console**

Vous pouvez tester manuellement la suppression en collant ce code dans la console (remplacez `1` par l'ID du lien) :

```javascript
jQuery.ajax({
    url: olm_ajax.ajax_url,
    type: 'POST',
    data: {
        action: 'olm_delete_link',
        security: olm_ajax.nonce,
        id: 1
    },
    success: function(response) {
        console.log('Succès:', response);
    },
    error: function(xhr, status, error) {
        console.log('Erreur:', xhr.responseText);
    }
});
```

## Version actuelle du plugin
Version 1.0.1 (avec logs de débogage activés)

## Fichiers modifiés
- `admin/js/admin.js` - Logs de débogage ajoutés
- `outbound-links-manager.php` - Version incrémentée à 1.0.1 pour forcer le rechargement

## Contact
Si le problème persiste après toutes ces étapes, envoyez-moi les messages de la console JavaScript.
