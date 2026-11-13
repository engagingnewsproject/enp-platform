# mediaengagement.org

WordPress site for the [Center for Media Engagement](https://mediaengagement.org/) at the University of Texas at Austin. The public theme is **engage-2-x** (Timber / Twig).

This repo is the full WordPress install used with Local + WP Engine. **Day-to-day code changes belong in `wp-content/themes/engage-2-x`** (and approved plugins) — avoid editing core or unrelated plugin files.

## Get started

### Installation

1. Install [Local](http://localwp.com/).
2. Connect Local to the CME WP Engine account.
3. Pull `mediaengagement.org` (cmengage) to Local.
4. Point the site at this GitHub repo and sync `master`.
5. In `wp-content/themes/engage-2-x`, run `yarn`.
6. Start development with `yarn watch`.

Full install guide: [docs/getting-started.md](docs/getting-started.md).

### Local development

```bash
cd wp-content/themes/engage-2-x
yarn
yarn watch   # rebuild assets while you work
yarn prod    # production build before a lead deploys
```

Activate the **engage-2-x** theme in WP Admin → Appearance. Use Local’s **Site Shell** for WP-CLI.

More detail: [docs/getting-started.md](docs/getting-started.md) · [docs/coding.md](docs/coding.md) · [docs/git.md](docs/git.md).

## Get help

- Team docs: [docs/README.md](docs/README.md)
- Slack: Website channel or **@luke** (see wiki footer)
- GitHub issues: [engagingnewsproject/mediaengagement](https://github.com/engagingnewsproject/mediaengagement/issues)

## Maintainers

Maintained by the CME web team. Project lead: Luke. Undergrads contribute via issues and PRs into `master`; deploy is lead-only ([docs/lead-only.md](docs/lead-only.md)).
