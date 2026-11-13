# How To

Short recipes. Concepts live in [WordPress](wordpress.md); setup lives in [Getting Started](getting-started.md).

## Edit team member → alumni

<a href="https://streamable.com/9kd710" target="_blank">
  <img src="https://github.com/user-attachments/assets/45f792a3-b493-47ee-9826-e2af62d59522" alt="Watch Edit Team-member to Alumni video">
</a>

**[Watch video](https://streamable.com/9kd710)**

## Manually assign a post’s social image

If the social image is wrong when sharing a research paper (Rank Math):

1. Under **Rank Math SEO**, open **Social**.
2. Scroll the modal.
3. **Replace Image** and choose the correct image.
4. **Update** the post.

## Optional: proxy media from production (Local)

Not required. Useful if you want a smaller local uploads folder and are fine loading missing files from production.

1. In your Local site’s nginx config directory (`conf/nginx`), create `uploads-proxy.conf`:

```nginx
location ~ ^/wp-content/uploads/(.*) {
    if (!-e $request_filename) {
        rewrite ^/wp-content/uploads/(.*) https://www.mediaengagement.org/wp-content/uploads/$1 redirect;
    }
}
```

2. In `site.conf.hbs`, after the WordPress rules include, add:

```nginx
#
# Proxy missing uploads to production
#
include uploads-proxy.conf;
```

3. Restart the site in Local and confirm images load.

## WordPress

### Find ACF fields for a page

1. View the page on your local site → **Edit**.
2. In the field group header, use the gear icon to open the field group in a new tab.
3. Copy field names and search the theme (Twig/PHP) for them.

### Export / import ACF field groups

ACF field groups sync via JSON in `engage-2-x/acf-json`:

1. Edits in WP Admin save JSON into `acf-json` automatically.
2. Pull the repo so other environments pick up the JSON.
3. Commit and push field-group JSON with your PR.

The ACF admin UI is hidden on production / non-local via `WP_ENVIRONMENT_TYPE` (visible locally only).

Manual Tools export/import (if needed):

1. Local WP Admin → **Custom Fields → Tools** → export selected groups.
2. On Dev / Staging / Production → **Custom Fields → Tools** → import.

### Notes on post type archive queries

Archive behavior is largely driven by queries in `src/Managers/Permalinks.php` (vertical-aware URLs). To change an archive query:

1. Update the query logic in `Permalinks.php` (and related permalink helpers as needed).
2. In WP Admin → **Settings → Permalinks**, save permalinks again to flush rewrites.

### Adding a new post type or taxonomy

Checklist (theme paths under `engage-2-x`):

1. Add the post type / taxonomy under `src/Managers/Structures/PostTypes` (follow existing files).
2. Add rewrites in `src/Managers/Permalinks` (same patterns as existing types).
3. Add vertical rewrites via `addVerticalRewrites()` where required.
4. Add the taxonomy slug to `$taxRewriteMap` in the Permalinks model.
5. Register the post type with the Vertical taxonomy under `src/Managers/Taxonomies`.
6. Save **Settings → Permalinks**.
7. Register a filter menu in `Globals.php` following other post types.
8. Update `archive.php` (or the relevant archive template) for the filter menu.
9. In ACF Options → Archive Landing Pages, add the post type slug as a landing page type option.
10. Test archives and single views locally.

## Cookiebot (consent) via Google Tag Manager

Consent on **mediaengagement.org** uses **Cookiebot** through **Google Tag Manager**, not the WordPress plugin.

- [Cookiebot admin](https://admin.cookiebot.com/)
- [GTM deployment guide (Cookiebot)](https://support.cookiebot.com/hc/en-us/articles/360003793854-Google-Tag-Manager-deployment)

Quick reference:

1. Adding the Cookiebot CMP **template** is not enough — create a **Tag** that uses it.
2. Fire on **`Consent Initialization - All Pages`**, not a normal Page View.
3. Use the Cookiebot **Domain Group ID** in the tag.
4. GA4 / Ads tags should respect GTM Consent (`analytics_storage` / `ad_storage`).
5. If the banner is missing: try incognito, clear consent cookies, check `www` vs bare hostname, and Cookiebot geo/dialog settings.
