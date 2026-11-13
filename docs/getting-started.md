# Getting Started

Long-form onboarding and local install for [mediaengagement.org](https://mediaengagement.org/). For a short summary, see the [repo README](../Readme.md).

## First day on the job

1. Get logins for WordPress ([mediaengagement.org](https://mediaengagement.org/)), WP Engine, and access to the [mediaengagement](https://github.com/engagingnewsproject/mediaengagement) GitHub repo. Ask the lead developer or your manager if anything is missing.
2. Follow **Installation** below (or the short steps in the [README](../Readme.md)). Use Cursor, VS Code, or another editor you’re comfortable with.
3. When the site runs locally, explore WP Admin. Some tasks are content-only; when you write code, follow [Git](git.md) (branching and PRs). **Do not deploy** — that is lead-only ([Lead Only](lead-only.md)).
4. Ask the lead developer and your manager for a good first project.

If you haven’t used WordPress before, poke around on your local site. Break things, fix them, edit posts. If local code is a mess, `git reset --hard origin/master` gets you back to a clean tree (you lose uncommitted work). Create branches and commit often.

Theme work lives under:

```text
…/app/public/wp-content/themes/engage-2-x
```

- Front end: `templates/` (Twig) and `assets/scss/`
- PHP / queries: `src/` and theme PHP files

See [Coding](coding.md) for structure, Timber/Twig, and SCSS.

Welcome to the CME team.

## Installation

### Summary

1. Install [Local](http://localwp.com/) (WP Engine’s local app).
2. Connect Local to the CME WP Engine account.
3. Pull `mediaengagement.org` (cmengage) to Local.
4. Point the site’s git `origin` at this GitHub repo and sync to `master`.
5. In `engage-2-x`, run `yarn` (and Composer for Timber if needed).
6. Activate `engage-2-x` and run `yarn watch`.

### 1. Install Local

1. Download and install [Local](http://localwp.com/) for your OS.
2. Launch Local and finish any first-run prompts.

### 2. Enable WP Engine API credentials

1. Open [API Access](https://my.wpengine.com/api_access) in the WP Engine User Portal.
2. Find the CME account → **Manage** → turn **Account API Access** on.
3. **Generate Credentials** and keep that page open for the next step.

### 3. Connect Local to WP Engine

1. In Local, open **Connect**.
2. **Log in to your host** → **WP Engine**.
3. Paste the API credentials and log in.

### 4. Pull the site to Local

1. In **Connect**, find the production site (cmengage / mediaengagement.org).
2. **Pull to Local**.
3. Include the database on the first pull so you can skip the optional DB steps below.
4. If you are not a user on the remote install yet, [add an admin via phpMyAdmin](https://wpengine.com/support/add-admin-user-phpmyadmin/).

Optional front-end debug quieting in `wp-config.php` (local only):

```php
define( 'WPE_CLUSTER_ID', '0' );
define( 'WP_DEBUG', false );
define( 'WP_DEBUG_DISPLAY', false );
ini_set( 'display_errors', 'Off' );
```

### 5. Optional: import the database manually

Skip if you included the database in the pull.

1. In WP Engine → [Sites](https://my.wpengine.com/sites) → cmengage → **phpMyAdmin**.
2. Export the database (Custom method if the dump is large; split tables if it times out).
3. Import into Local’s MySQL (Local’s site shell / socket — the site ID under `~/Library/Application Support/Local/run/` varies per machine).
4. In `wp-config.php`, local DB user/password are typically `root` / `root`, host `localhost`.

### 6. Connect the Local site folder to GitHub

From your Local site’s `app/public` directory (path varies; often under `~/Local Sites/<site-name>/app/public`):

```bash
git remote -v
```

If `origin` is missing or wrong:

```bash
git remote add origin https://github.com/engagingnewsproject/mediaengagement.git
# or, if origin already exists:
git remote set-url origin https://github.com/engagingnewsproject/mediaengagement.git

git fetch --all
git checkout master
git reset --hard origin/master
```

You should be on `master` matching GitHub. To refresh content later, pull from WP Engine again (include DB if you need fresh data).

### 7. Theme dependencies and watch mode

```bash
cd wp-content/themes/engage-2-x
node -v
yarn -v
yarn
```

If Timber isn’t installed yet:

```bash
composer require timber/timber
```

1. In WP Admin → **Appearance**, activate **engage-2-x**.
2. Point BrowserSync / Mix proxy at your Local site URL if needed (`webpack.mix.js`).
3. Start development:

```bash
yarn watch
```

4. Before handing work off for deploy (leads), compile production assets:

```bash
yarn prod
```

Use a current Node LTS that works with this theme’s Laravel Mix setup. There is no `.nvmrc` in `engage-2-x` right now — if `yarn` fails, try a recent Node 20+ LTS via [nvm](https://github.com/nvm-sh/nvm).

### 8. Quiz tool (only if you work on quizzes)

Quiz config lives in `wp-content/enp-quiz-config.php` and (on servers) `enp-quiz-database-config.php` in the web root. Local hosts matching `localhost` are already handled in that config. If quiz create/take paths fail locally, ask the lead before changing production path logic.

### 9. Open the site

In Local, use **Open site** and **WP Admin**. Prefer Local’s **Site Shell** for WP-CLI so commands can reach the database.

## Important links

- [Center for Media Engagement on GitHub](https://github.com/engagingnewsproject)
- [This repository](https://github.com/engagingnewsproject/mediaengagement)
- [WP Engine Portal](https://identity.wpengine.com/signin)
- [Creating an issue on GitHub (video)](https://streamja.com/noaGB)
- [Mastering Markdown](https://guides.github.com/features/mastering-markdown/)
- [Google Docs to Markdown](https://github.com/evbacher/gd2md-html/wiki)
- [WP Engine WP-CLI](https://wpengine.com/support/troubleshoot-ssh-gateway/)

## Next reading

- [Git](git.md) — branches and pull requests
- [Coding](coding.md) — theme structure and SCSS
- [How To](how-to.md) — short recipes (including optional media proxy)
