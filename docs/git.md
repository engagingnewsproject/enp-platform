# Git

Branching, pull requests, and local yarn commands for undergrad / day-to-day developers.

**Do not deploy.** Merging to `dev` / `stage` / `stable` and WP Engine deploys are [Lead Only](lead-only.md).

## Remember

- Start from an up-to-date `master` (`git pull origin master`).
- Test your changes locally (and ask for review) before asking a lead to deploy.
- Run `yarn prod` in `engage-2-x` when your front-end work is ready so production assets are in the commit/PR if the lead expects them.
- Only leads push deploy branches / production.

## Quick guide

1. Pick a [GitHub issue](https://github.com/engagingnewsproject/mediaengagement/issues) and assign it to yourself.
2. Update `master` and create a branch (see naming below).
3. Commit and push as you hit stable stopping points.
4. Open a **pull request into `master`** (or into `stable` only for a true hotfix led by the project lead).
5. Link the PR to the issue; add short implementation notes on the issue if helpful.
6. Address review comments; start the next task while you wait.

## Branch naming

Match recent repo practice:

| Kind | Branch from | Name pattern | Example |
| --- | --- | --- | --- |
| Feature / improvement | `master` | `feat/short-slug` | `feat/box-calc-cap` |
| Bug fix | `master` | `fix/short-slug` | `fix/cart-overage` |
| Docs | `master` | `docs/short-slug` | `docs/repo-docs-wiki-index` |
| Hotfix (lead) | `stable` | `hotfix/short-slug` | `hotfix/login-500` |

```bash
git checkout master
git pull origin master
git checkout -b feat/your-slug
git push -u origin HEAD
```

## Pull requests

- Target **`master`** for normal work.
- Keep PRs focused; describe what and why briefly.
- After merge, delete the remote feature branch when you’re done with it.

## Yarn commands

From `wp-content/themes/engage-2-x`:

```bash
yarn            # install dependencies
yarn watch      # local rebuild + BrowserSync
yarn prod       # production build (minified assets)
```

If CSS/JS merge conflicts appear in built files, rebuild with `yarn prod` and commit the result rather than hand-merging minified output.

## Handy git commands

```bash
git status
git checkout master
git pull origin master
git merge feat/your-slug    # usually done via GitHub PR instead
git remote -v
```

## Related

- [Getting Started](getting-started.md) — first-time setup
- [Lead Only](lead-only.md) — deploy and WP Engine
