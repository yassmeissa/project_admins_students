# Test de la Recherche en Temps Réel

## Changements effectués :

### 1. **StudentController.php**
   - Ajout de `JsonResponse` dans les imports
   - Création d'une nouvelle endpoint API : `/student/{idS}/api/search_courses`
   - Cette endpoint retourne les résultats de recherche en JSON pour le JavaScript

### 2. **index.html.twig (student_course)**
   - Modification du formulaire de recherche en simple input sans form submit
   - Ajout d'un bouton "Effacer" qui s'affiche uniquement pendant la recherche
   - Ajout d'un script JavaScript complet qui :
     * Écoute les changements du champ de recherche
     * Effectue une requête AJAX à l'API
     * Affiche les résultats en temps réel sans rechargement de page
     * Gère le débouncing (attente 300ms après la dernière saisie)
     * Affiche un message vide si aucun résultat n'est trouvé

## Comment ça fonctionne :

1. L'utilisateur tape dans la barre de recherche
2. Après 300ms sans saisie, une requête AJAX est envoyée
3. L'API retourne les cours correspondants en JSON
4. Les cours s'affichent en temps réel dans la grille
5. L'utilisateur peut effacer la recherche avec le bouton "X" rouge

## Avantages :

- ✅ Pas de rechargement de page
- ✅ Recherche instantanée et fluide
- ✅ Débouncing pour éviter les appels inutiles
- ✅ Bouton "Effacer" facile d'accès
- ✅ Design moderne et responsive
- ✅ Thème dark/light supporté

## Pour tester :

1. Se connecter avec un compte étudiant
2. Aller sur la page "Mes Cours"
3. Taper dans la barre de recherche
4. Les résultats s'affichent instantanément sans rechargement
