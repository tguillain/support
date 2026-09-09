---
paths:
  - 'functional/*/lang/**'
---

# Lang

## Messages de validation : imbriqués en langue, aplatis pour le validateur
Ne jamais écrire une clé de traduction contenant un point (`'title.required' => …`) : `__()` découpe sur les points, donc `__('ns::form.validation.title.required')` ne résout pas et renvoie la clé brute. Le message devient inadressable depuis un test ou une vue.

Imbriquer dans le fichier de langue (`'validation' => ['title' => ['required' => …]]`) et aplatir au point d'usage : `Arr::dot(__('ns::form.validation'))` dans le `messages()` du composant — le validateur veut des clés `champ.règle`, la langue veut une arborescence.

Livewire : passer un sous-ensemble de règles à `validate($rules)` conserve `messages()` et `validationAttributes()` (voir `providedOrGlobalRulesMessagesAndAttributes`), donc les règles peuvent rester déclarées une seule fois dans `rules()` et chaque action en sélectionner une partie avec `Arr::only`.

`Rule::enum(X::class)` honore bien une clé de message custom `champ.enum`.
