---
paths:
  - 'functional/*/src/Livewire/**'
---

# Livewire

## Composants Livewire 4 dans un layer OSDD
Livewire n'auto-découvre que les composants sous `App\Livewire` et les `component_locations` par défaut. Un composant de layer doit être nommé à la main dans le `LayerServiceProvider` : `Livewire::component('tickets.ticket-list', TicketList::class)`, avec `loadViewsFrom(__DIR__.'/../../resources/views', 'tickets')` pour que `view('tickets::livewire.ticket-list')` résolve.

Piège de version : en Livewire 4, `component_layout` vaut `layouts::app` (namespace de vue), plus `components.layouts.app` comme en v3. Le layout d'une page plein écran va donc dans `resources/views/layouts/app.blade.php` — ailleurs, on obtient « No hint path defined for [layouts] ». Le namespace n'est enregistré que si le dossier existe au boot.

En test, `WithPagination` ne publie pas de propriété `$page` : la page vit dans `$paginators`. Utiliser `->call('setPage', 2)` et `->assertSet('paginators.page', 2)`.

Tailwind 4 balaie déjà `storage/framework/views/*.php` via `@source` dans `resources/css/app.css`, donc les classes d'une vue de layer sont reprises sans ajouter de `@source`.
