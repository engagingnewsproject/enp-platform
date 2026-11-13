# WordPress

Concepts and editor-facing notes. Step-by-step recipes (ACF, CPT, social image) are in [How To](how-to.md).

## Hooks and filters

- **Hooks (actions):** run code at a moment in WordPress’s lifecycle (e.g. when a post is saved).
- **Filters:** change a value before WordPress uses it.

You’ll use [`WP_Query`](https://developer.wordpress.org/reference/classes/wp_query/) often. Prefer it over custom SQL for most post queries.

## Images

Don’t upscale tiny uploads — they look soft. If unsure, upload larger and let WordPress/CSS scale down. [More background](https://rawinfopages.co.uk/are-your-photos-blurry-on-wordpress-solve-image-problems/).

## Editing publications

On the publications UI in WP Admin you can typically:

1. Add a publication  
2. Drag to reorder  
3. Delete  
4. Insert between existing items  

(If the UI has changed, follow what’s on screen and update this section in a PR.)

## Plugins

- Work in the **engage-2-x** theme (and approved plugins’ settings) — never the WP **Theme File Editor**.
- Don’t edit WordPress core or plugin PHP; updates will wipe those changes. Configure plugins from their admin screens.

### ENP Registration

Custom registration email behavior lives in the [enp-custom-registration](https://github.com/engagingnewsproject/enp-custom-registration) plugin/repo.

### SEO / Rank Math

Helpful Rank Math docs:

- [Choosing focus keywords](https://rankmath.com/kb/score-100-in-tests/#first-step-choosing-focus-keywords)
- [Color codes](https://rankmath.com/kb/score-100-in-tests/#making-sense-of-rank-math-s-recommendations-the-color-codes)
- [Keyword in meta description](https://rankmath.com/kb/score-100-in-tests/#focus-keyword-in-the-meta-description-primary-focus-keyword-only)
- [Keyword in URL](https://rankmath.com/kb/score-100-in-tests/#focus-keyword-in-the-url-primary-focus-keyword-only)
- [Keyword at start of content](https://rankmath.com/kb/score-100-in-tests/#focus-keyword-at-the-beginning-of-the-content)
- [Google Trends](https://trends.google.com/trends/) for keyword research

**Research pages (typical):**

1. Set the focus keyword in Rank Math.  
2. Write a meta description that includes it.  
3. Put the focus keyword in the featured image alt text where appropriate.  
4. Use Rank Math **Analytics** / **SEO Analysis** to find weak pages and errors.

Social share image fixes: [How To — social image](how-to.md#manually-assign-a-posts-social-image).

## Related

- [How To](how-to.md) — CPT checklist, ACF, archive queries  
- [Coding](coding.md) — theme / Twig / SCSS  
