---
paths:
  - 'functional/*/database/**'
---

# Database

## Factories et seeders dans un layer OSDD
Les factories d'un layer vivent hors du namespace `Database\Factories\`, donc le resolver par défaut de Laravel ne les trouve jamais. Chaque modèle de layer doit porter `#[UseFactory(XFactory::class)]`, sinon `X::factory()` échoue.

Le nom de classe d'un seeder doit correspondre exactement au nom du fichier : avec `optimize-autoloader`, un décalage fait charger le fichier deux fois et lève `Cannot redeclare class`.

Un seeder de layer s'enregistre une seule fois via `loadSeeders([...], priority: N)` dans le `LayerServiceProvider` (priorité basse = exécuté en premier ; utile pour ordonner tickets → comments). `Database\Seeders\DatabaseSeeder` appelle `SeederRegistry::seeders()`, donc `migrate --seed` et `osdd:seed` passent tous deux par ce registre — ne pas appeler un seeder de layer en dur ailleurs.
