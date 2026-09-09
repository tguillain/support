---
paths:
  - 'resources/**'
---

# Resources

## Tokens de marque XEFI : relevés, pas inventés
La DA XEFI documentée (skills `design:*`) définit la grille 4px, l'échelle typo (H1 32 / H2 24 / H3 20 / H4 16, body 14), l'échelle d'espacement (4/8/16/24/32/48, **12px interdit**) et les hauteurs de contrôle (30/36/40/48) — mais **exclut volontairement les couleurs** (« per business unit »). Ne pas en inventer.

Valeurs relevées sur les sites XEFI, centralisées dans `resources/css/app.css` :
- `--color-brand: #e10600`, `--color-brand-strong: #b80510`, `--color-brand-tint: #fdecee` (xefi.sherlox.fr)
- `--color-ink: #111827` — le primaire est le near-black, **pas** le rouge : sur ce site le rouge ne marque que des accents (barres, coches), ce qui évite aussi qu'il entre en concurrence avec le rouge d'erreur
- `--radius-field: 0.625rem` (10px) et bordure `#d1d5db` sur les champs, `width:100%` sur inputs et boutons (app.xefi.com/login)
- police **Instrument Sans**, déjà celle du projet
- neutres = échelle `gray` de Tailwind, pas `slate`

Le shell d'authentification XEFI : colonne de 26rem, « eyebrow » majuscule en couleur de marque au-dessus du titre, titre centré, callout d'erreur icône + corps avec barre en inset, ligne « connexion sécurisée » en pied.

Attention : sur les écrans d'auth le produit dévie de la DA documentée (titre 18px, padding 10/18px). En cas de conflit, arbitrer et le dire.
