---
paths:
  - 'functional/*/src/Jobs/**'
---

# Jobs

## Ne pas redéclarer $afterCommit sur un job
`Illuminate\Foundation\Queue\Queueable` (via `Illuminate\Bus\Queueable`) déclare déjà `public $afterCommit;` sans type. Ajouter `public bool $afterCommit = true;` sur le job est une composition de trait incompatible : **fatal à la compilation**, et PHPUnit le rapporte comme « Premature end of PHP process » sans jamais nommer la propriété.

Utiliser l'API du trait à la place : `$this->afterCommit();` dans le constructeur. Même piège pour `$connection`, `$queue`, `$delay`, `$middleware`, `$chained`.

Un listener `ShouldQueue` sans trait Queueable peut, lui, déclarer `public bool $afterCommit = true;` sans conflit.
