---
paths:
  - 'functional/*/src/Rest/**'
---

# Rest

## Resources lomkit/laravel-rest-api dans un layer
`rest:resource` et `rest:controller` génèrent en dur dans `app/Rest/` et préfixent le modèle par `App\Models\` ; `osdd:resource` n'est pas un équivalent (il étend le générateur de JsonResource de Laravel). Il faut donc générer puis relocaliser dans `<layer>/src/Rest/{Resources,Controllers,Instructions}` et corriger le namespace + `$model`.

En v2.23, `fields()` est le whitelist unique : `SearchSort` et `SearchFilter` valident tous les deux contre `getFields()`, il n'existe pas de `filterableFields()`/`sortableFields()`. Pour exposer un champ en lecture sans le rendre requêtable, imposer la restriction dans `beforeSearch()` du contrôleur (voir TicketsController) et faire passer une recherche ciblée par une Instruction déclarée.

Les modèles de layer ont un nom de policy au pluriel qui casse la découverte par convention : attacher explicitement avec `#[UsePolicy(XPolicy::class)]`. Les méthodes `attach{Model}`/`detach{Model}` ne sont enforced que si elles existent sur la policy — leur absence vaut autorisation.
