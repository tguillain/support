---
paths:
  - phpstan.neon
---

# General

## Configuration PHPStan : niveau 7, zéro baseline
Niveau 7 (défaut Xefi quand rien n'est configuré). Aucun baseline, aucun `ignoreErrors`, aucun `@phpstan-ignore` : ces trois portes restent fermées.

Quatre réglages non évidents, sans lesquels on obtient des dizaines de faux positifs :
- `databaseMigrationsPath` doit lister **les migrations de chaque layer** en plus de `database/migrations`, sinon Larastan ne type aucune colonne des modèles de layer.
- `stubFiles` pointe sur `phpstan/faker-container.stub.php`, une copie corrigée de `faker_mixin.php`. Ne pas utiliser le fichier généré directement : il est réécrit à chaque `package:discover`, et PHPStan **valide un stub même si son chemin est dans `excludePaths`**.
- Les macros de builder posées par un global scope (`controlled()` de lomkit/laravel-access-control) sont invisibles à l'analyse. Les déclarer en `@method static` sur le modèle et appeler `Model::controlled()`, ou passer par l'API typée du Control (`Control::queried()`).
- Les hooks `*Query()` de laravel-rest-api sont typés avec le *contrat* `Illuminate\Contracts\Database\Eloquent\Builder` alors que le framework passe toujours le builder concret. Narrower le paramètre est un fatal PHP : narrower **dans** la méthode avec un `instanceof`, et lever une exception nommée sur la branche impossible plutôt que renvoyer la requête intacte.

Les tests sont dans `paths`. Conséquence : `MaxLinePerClassRule` (200) et `MaxLinePerMethodRule` (40) s'appliquent aussi aux classes de test — les découper par préoccupation, avec une classe de base abstraite `*TestCase` pour le setUp et les helpers (en `protected`). Le namespace de tests d'un layer doit être mappé dans l'`autoload-dev` **de la racine** : `dump-autoload` ne relit pas le composer.json d'un package de type path.
