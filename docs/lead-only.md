# Lead Only

For the CME web developer / project lead. Undergrads should use [Git](git.md) and should **not** deploy.

## Deploy overview

GitHub Actions deploys to WP Engine when these branches are pushed:

| GitHub branch | WP Engine env | Site |
| --- | --- | --- |
| `dev` | cmedev | https://cmedev.wpengine.com |
| `stage` | cmestaging | https://cmestaging.wpengine.com |
| `stable` | cmengage | https://mediaengagement.org |

Workflows live under [`.github/workflows/`](../.github/workflows/). Pushing `master` alone does **not** deploy to dev.

See also: [WP Engine branched deploys with GitHub Actions](https://wpengine.com/builders/branched-deploys-wp-engine-github-actions/) and [Actions for this repo](https://github.com/engagingnewsproject/mediaengagement/actions).

## Deploy checklist

1. **Notify Kat** before pushing updates that will reach the live site.
2. Compile production assets in the theme:

```bash
cd wp-content/themes/engage-2-x
yarn prod
```

3. Merge and push **development**:

```bash
git checkout dev && git merge master && git push
```

4. Merge and push **staging**:

```bash
git checkout stage && git merge master && git push
```

5. **Notify Kat** again before production.
6. Merge to **production** branch and push:

```bash
git checkout stable && git merge master
# save merge message in vim if needed: :wq
git push
```

7. In WP Engine, clear caches (portal and/or WP Engine plugin) after production goes out.

## Sync production database → dev / staging

Theme scripts (from `engage-2-x`):

```bash
yarn sync-db-dev
yarn sync-db-staging
```

These export production DB, import into the target env, search-replace URLs, and flush cache. Confirm SSH keys / script paths before running. **Dev/staging only** — not a substitute for careful production ops.

## SSH (WP Engine gateway)

Connect (replace `environment` with the install name, e.g. `cmengage`, `cmedev`, `cmestaging`):

```bash
ssh -i ~/.ssh/wpengine_rsa -o IdentitiesOnly=yes environment@environment.ssh.wpengine.net
```

Useful once connected:

```bash
wp theme activate twentytwentyone --skip-themes   # emergency default theme
du -h --max-depth 1                               # directory sizes
```

Prefer Local’s **Site Shell** for local WP-CLI.

## Legacy / emergency: WP Engine git remotes

Primary deploys should go through **GitHub Actions** (above). Some machines still have direct WPE remotes:

```text
dev      git@git.wpengine.com:cmedev.git
staging  git@git.wpengine.com:cmestaging.git
prod     git@git.wpengine.com:cmengage.git
```

(Exact remote names vary: `prod` vs `production`.)

### Force push — at your own risk

If WPE’s tree diverged and a normal push is rejected, a force push may appear in old docs:

```bash
git push -f production stable
```

**Do not force-push casually.** You can overwrite remote history and break the install. Prefer pulling/reconciling, or redeploying via Actions from a known-good GitHub branch. Only force-push when you understand the impact and have a recovery plan.

## Related

- Short deploy notes also live in the [README](../Readme.md#deployment-for-project-leads-only)
- [Git](git.md) — what undergrads should follow instead
