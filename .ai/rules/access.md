---
paths:
  - 'functional/*/src/Access/**'
---

# Access

## Controls access-control dans un layer : enregistrement manuel obligatoire
`Access::discoverControls()` déduit le nom de classe du chemin relatif à `base_path()` et ne sait remplacer que `app/` par `App\`. Un chemin de layer produit `Functional\tickets\src\Access\Controls\...`, invalide, et le Control est ignoré **silencieusement** (le `ReflectionException` est catché). Il faut donc `(new Access)->addControl(new XControl())` dans le `register()` du LayerServiceProvider.

Corollaire piégeux : `Access::$controls` est un static typé sans valeur par défaut. Si `app/Access/Controls` n'existe pas et que personne n'appelle `addControl`, toute lecture lève « must not be accessed before initialization ». L'enregistrement manuel initialise le registre au passage.

Articulation avec laravel-rest-api : le point d'accroche est `Resource::searchQuery()` (plus `destroyQuery`/`restoreQuery`/`forceDeleteQuery`), que le package appelle déjà dans un `where(...)` isolé — voir le commentaire « The perimeter runs in a subquery » dans `Query/Traits/PerformSearch.php`. Y appeler `$query->controlled()`. Ne rien mettre dans le contrôleur REST.

Les périmètres ne lisent que des permissions, jamais `hasRole()` : la permission autorise l'action, la query du périmètre décide des lignes.
