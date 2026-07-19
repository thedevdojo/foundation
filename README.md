# DevDojo Foundation

**One install, the whole platform.** `devdojo/foundation` is the metapackage that bundles the
DevDojo feature packages — auth, accounts, billing, blog, changelog, notifications, teams, and
the component library — and adds the piece none of them have on their own: a **runtime feature
flag system** that decides which features are active. Flip a feature on or off from config, from
code, or from the `/foundation/setup` screen — never with a Composer operation.

```bash
composer require devdojo/foundation
php artisan foundation:install
```

---

## Table of contents

- [What's in the box](#whats-in-the-box)
- [How it works](#how-it-works)
- [Requirements](#requirements)
- [Installation](#installation)
- [Feature flags](#feature-flags)
  - [Config defaults](#config-defaults)
  - [Runtime overrides](#runtime-overrides)
  - [The setup screen](#the-setup-screen)
- [Dependency resolution](#dependency-resolution)
- [Gating your own code](#gating-your-own-code)
- [The onboarding wizard component](#the-onboarding-wizard-component)
- [Publish tags](#publish-tags)
- [License](#license)

---

## What's in the box

Requiring `devdojo/foundation` pulls in every feature package:

| Package | What it gives you |
| --- | --- |
| [`devdojo/auth`](https://github.com/thedevdojo/auth) | Authentication — login, registration, social providers, 2FA. |
| [`devdojo/accounts`](https://github.com/thedevdojo/accounts) | A complete Clerk-style account area: profile, avatar, email changes, connected accounts, password, 2FA, sessions. |
| [`devdojo/billing`](https://github.com/thedevdojo/billing) | Subscriptions, plans, and checkout via Stripe or Paddle, plus plan-based feature limits. |
| [`devdojo/blog`](https://github.com/thedevdojo/blog) | A drop-in blog — posts, categories, admin resources. |
| [`devdojo/changelog`](https://github.com/thedevdojo/changelog) | A product changelog with per-user read tracking. |
| [`devdojo/components`](https://github.com/thedevdojo/components) | A crafted set of Blade UI components you copy into your app and own. |
| [`devdojo/notifications`](https://github.com/thedevdojo/notifications) | In-app database notifications and notification preferences. |
| [`devdojo/teams`](https://github.com/thedevdojo/teams) | Teams, memberships, roles & permissions, and email invitations. |

Each package works standalone and documents its own API in its README. The foundation's job is
bundling them and coordinating **which ones are active**.

---

## How it works

Every feature package self-gates in its service provider on its flag:

```php
config('foundation.features.billing', true)   // default-on when foundation is absent
```

The foundation makes sure that flag holds the **effective** state before any feature package
boots:

1. `config/foundation.php` supplies the **defaults** (everything on).
2. Per-app **overrides** stored in the `foundation_settings` table are merged over them.
3. **Dependencies** are expanded — any enabled feature force-enables its prerequisites.

The resolved map is written back to `config('foundation.features')` in an application
`booting` callback — after the database is available, but before any provider's `boot()` —
so every feature package's gate reads the correct, override-aware state. If the database (or
the `foundation_settings` table) isn't available yet, the config defaults apply, which keeps
install and package-discovery safe.

Because activation is data, not dependencies, toggling a feature off and back on is instant and
lossless — nothing is uninstalled, and migrations stay in place.

---

## Requirements

| Requirement | Notes |
| --- | --- |
| PHP `^8.2 / ^8.3 / ^8.4` | |
| Laravel `^11 / ^12 / ^13` | |
| Livewire | Used by the bundled onboarding wizard component; installed via the feature packages. |

Each bundled feature package brings its own requirements — see its README.

---

## Installation

```bash
composer require devdojo/foundation
php artisan foundation:install
```

The installer:

1. Publishes `config/foundation.php` (`--force` to overwrite an existing copy).
2. Runs migrations for the full stack — the foundation plus every feature package.
3. Seeds the `foundation_settings` table with the default feature flags (existing rows are
   kept, so re-running is safe).
4. Links storage (best-effort).

Then visit **`/foundation/setup`** to choose which features are active.

> The `foundation_settings` migration is **auto-loaded** (not publish-only) — the flag store
> exists wherever the foundation is installed. Feature packages keep their own conventions for
> their migrations.

---

## Feature flags

### Config defaults

`config/foundation.php`:

```php
'features' => [
    'auth' => true,  // foundational — effectively always on
    'billing' => true,
    'blog' => true,
    'changelog' => true,
    'notifications' => true,
    'accounts' => true,
    'teams' => true,
],
```

All features default to **on** so the foundation is fully functional out of the box.
`devdojo/components` is bundled but has no flag — it's a copy-into-your-app component library
with no runtime surface to toggle.

### Runtime overrides

Per-app overrides live in the `foundation_settings` table as `features.<name>` keys and are
merged over the config defaults at boot. Persist one from code:

```php
use Devdojo\Foundation\Foundation;

Foundation::setFeature('blog', false);   // takes effect on the next request
```

Overrides are read defensively: before the table exists, or if the database is unreachable,
the foundation silently falls back to the config defaults.

### The setup screen

**`/foundation/setup`** is a visual toggle board for the same flags — one switch per feature,
with each feature's prerequisites labeled. `auth` is foundational and cannot be disabled there.
Saving writes the overrides to `foundation_settings`; changes take effect on the next request.
(`/foundation` redirects here for convenience.)

The screen is protected by the `view-foundation-setup` middleware: it is visible **in your
local environment**, or to any user passing the `viewFoundationSetup` Gate:

```php
// app/Providers/AppServiceProvider.php
Gate::define('viewFoundationSetup', fn ($user) => $user->isAdmin());
```

Everyone else gets a 403.

---

## Dependency resolution

Some features assume users can sign in. `config/foundation.php` declares the prerequisites:

```php
'depends' => [
    'blog' => ['auth'],
    'accounts' => ['auth'],
    'billing' => ['auth'],
    'teams' => ['auth'],
],
```

Whenever the feature map is resolved, every **enabled** feature force-enables its
prerequisites — so switching `billing` on always switches `auth` on with it, whatever the
stored overrides say. The setup screen shows these requirements next to each toggle.

---

## Gating your own code

Read the effective state through the `Foundation` class — it always returns the fully resolved
map (defaults + overrides + dependencies):

```php
use Devdojo\Foundation\Foundation;

Foundation::enabled('billing');   // bool
Foundation::features();           // ['auth' => true, 'billing' => true, ...]
```

Use it to make your app degrade cleanly when a feature is off:

```blade
@if (\Devdojo\Foundation\Foundation::enabled('blog'))
    <a href="{{ route('blog.index') }}">Blog</a>
@endif
```

If you build your own toggleable feature (or a new feature package), follow the same
convention the bundled packages use — gate on the config key with a default of `true`, so the
feature still works standalone without the foundation:

```php
if (config('foundation.features.my-feature', true)) {
    // register routes, components, resources...
}
```

---

## The onboarding wizard component

The foundation registers a `foundation.setup` Livewire component — a full-screen "new project"
wizard intended to be rendered by the host app's welcome view:

```blade
<livewire:foundation.setup />
```

It presents the wizard steps (Starter, Packages, Project, Database, Review, Launch) and a
starter-template picker — Blank plus the Rally, Deskly, Formly, Hunted, and Catalog demo
templates. The template preview images ship with the package; publish them so the wizard can
serve them:

```bash
php artisan vendor:publish --tag=foundation-assets   # → public/vendor/foundation/images
```

This is distinct from `/foundation/setup` (the feature-flag screen above).

---

## Publish tags

| Tag | Publishes to |
| --- | --- |
| `foundation-config` | `config/foundation.php` |
| `foundation-assets` | `public/vendor/foundation/images` |

---

## License

MIT © [DevDojo](https://devdojo.com)
