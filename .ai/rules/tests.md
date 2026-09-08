---
paths:
  - 'functional/*/tests/**'
---

# Tests

## Deux appels Rest dans un même test rejouent le premier payload
`RestServiceProvider::register()` enregistre `RestRequest`, `SearchRequest`, `MutateRequest`, `DestroyRequest`… en **singleton**. Une requête HTTP réelle a son propre conteneur, donc c'est inoffensif en production ; un test de feature réutilise la même application entre deux appels, et le second appel au même endpoint reçoit l'objet requête du premier — il rejoue son payload sans erreur (un `update` devient un `create` en doublon).

Appeler `$this->app->forgetInstance(...)` sur ces classes entre deux appels, ou n'en faire qu'un par méthode de test. Voir `forgetRestRequests()` dans TicketsApiTest.

Corollaire : un test qui enchaîne create → update → delete et qui passe sans ce reset ne prouve rien.
