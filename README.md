# Phase 10 Generator

Generate fresh [Phase 10](https://en.wikipedia.org/wiki/Phase_10) games to keep the
card game interesting. The app holds a large, difficulty-weighted library of phases
(the 10 classics plus hundreds of auto-generated variants) and builds a new 10-phase
game on demand — which you can tweak and print as a Phase-10-style card.

Built with **Laravel 13 · Livewire 4 · Flux UI Pro · Tailwind 4**. No database, no
login — phases live in a config file and the app is a public, stateless generator.

## How it works

- **Phase library** is defined in [`config/phases.php`](config/phases.php) — the 10
  classics plus enumeration settings. A `PhaseLibrary` service expands that into the
  full scored library in memory at runtime (currently ~96 phases across four bands).
- **Difficulty** is computed from each phase's components by `PhaseScoringService`,
  accounting for set/run/color/parity hardness, deck scarcity, and multi-component
  compounding. Scores map to bands: Easy / Medium / Hard / Brutal.
- **Generation** (`PhaseGeneratorService`) supports three modes, chosen at generate time:
  - **Classic ramp** — difficulty climbs easy→brutal across the slots.
  - **Flat band** — every phase from one band you pick.
  - **Manual** — choose the band for each slot yourself.
  - Plus a fixed seed for reproducible games, per-slot regenerate, and no duplicates.
- **Printing** is browser-native: the `/game/print` page renders the card and you
  Print → Save as PDF. No headless browser, no server-side PDF engine.

### Adding or removing phases

Edit [`config/phases.php`](config/phases.php) and redeploy. A phase is a list of
`[type, count]` components, e.g.:

```php
[['set', 3], ['run', 4]]            // "1 set of 3 + 1 run of 4"
[['color_run', 5]]                  // "1 run of 5 of one color"
['components' => [['run', 8]], 'notes' => 'Crowd favorite'],
```

Component types: `set`, `run`, `color`, `color_run`, `evens`, `odds`, `color_evens`,
`color_odds`. To add a brand-new requirement type, add a case to
`App\Enums\Phase\ComponentType`, a strategy class under `app/Support/Phase/Types/`,
and wire it in `ComponentType::strategy()`.

## Local development

Flux Pro is a licensed package — configure the credentials once:

```bash
composer config http-basic.composer.fluxui.dev "<flux-username>" "<flux-license-key>"
```

Then:

```bash
composer install
npm install
cp .env.example .env
php artisan key:generate
composer run dev          # serve + vite
```

Open http://localhost:8000.

### Tests & formatting

```bash
php artisan test          # Pest
vendor/bin/pint           # format
```

## Deployment

Push to `main` →

1. **CI** (`.github/workflows/ci.yml`) runs Pest + Pint.
2. **Build** (`.github/workflows/docker-publish.yml`) builds a multi-arch FrankenPHP
   image and pushes `ghcr.io/koolsb/phase10:main`.
3. In the **kools-k3s** GitOps repo, `argocd-image-updater` bumps the digest and
   ArgoCD deploys via the shared `charts/laravel` Helm chart.

### Required GitHub repo secrets

| Secret | Used by |
| --- | --- |
| `FLUX_USERNAME` | CI + image build (Flux Pro) |
| `FLUX_LICENSE_KEY` | CI + image build (Flux Pro) |

`GITHUB_TOKEN` (automatic) is used to push to GHCR.

### Cluster (kools-k3s repo)

Manifests live at `apps/phase10/` (values) and `apps/templates/phase10.yaml` (the
ArgoCD Application). Before enabling:

- Set the hostname in `apps/phase10/values.yaml`.
- Seal `APP_KEY` and a GHCR pull secret — see `apps/phase10/secrets/README.md`.
- Flip `phase10.enabled: true` in `apps/values.yaml`.

The container serves on **:8080** (non-root), exposes `/health.php` for probes, uses
file sessions/cache, and needs **no PVC and no database**.
