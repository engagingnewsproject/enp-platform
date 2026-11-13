# Coding

Day-to-day guidance for work in `wp-content/themes/engage-2-x`. For branches and PRs, see [Git](git.md). For deploy, see [Lead Only](lead-only.md).

## Run the local front-end build

From the theme directory:

```bash
cd wp-content/themes/engage-2-x
yarn
yarn watch   # BrowserSync + SCSS/JS rebuild
yarn prod    # minified assets before a lead deploys
```

Details: [Git](git.md#yarn-commands).

## Coding best practices

### Think about performance

- Will this add weight to the page?
- Will it slow first load or interaction?

### Don’t pull in a whole library for one feature

Examples: jQuery for one call when the site doesn’t need it otherwise; the full Font Awesome set for a handful of icons; Bootstrap for a single layout.

### Simple is better than clever

Clever code is fun until the next person (or you) has to maintain it. Prefer plain, clear solutions. If something clever is justified (big win, clear boundary), isolate it in a small, well-named, documented function.

### Functions should do one thing

Easier to test, debug, and read. Split a function if the name is a paragraph, the body is huge, or it has many unrelated steps.

### Ask for help after you’ve tried

Search and read a bit first — that’s where a lot of learning happens. When you ask, say what you’re trying to do, why, and what you already tried. Writing that down often solves the problem.

### Don’t let the editor reformat the whole file on save

Mismatched format-on-save rules turn one-line fixes into noisy PRs. Prefer project defaults; don’t enable aggressive auto-format unless the team agrees (e.g. Prettier).

### Fetch only the data you need

When querying posts or APIs, request what you need (or will clearly need), not everything “just in case.”

### Keep things organized

Follow existing theme patterns. If there’s no obvious home for new code, add a file that matches how neighboring code is structured.

## CSS

- Avoid `!important` unless you have a strong reason.
- Keep specificity low: prefer classes over IDs; aim for one level (`.block__element`) or at most two.
- Use [BEM](https://getbem.com/)-style names:
  - Block: `.article`
  - Element: `.article__title`
  - Modifier: `.article--research` or `.article__title--featured`
  - Don’t nest elements like `.article__title__link` — use `.article__title-link` instead.

## SCSS

Engage CSS is compiled with [Sass](https://sass-lang.com/).

- Edit files under `assets/scss/` (entry: `assets/scss/app.scss`).
- `yarn watch` compiles to `dist/css/` (and related assets).
- `yarn prod` builds minified production assets.

### Mobile-first workflow

Write base styles for small screens first, then layer larger breakpoints with mixins. Prefer not to use `@include media($mobile)` for “mobile only” if those styles can be the default.

Order inside a rule:

1. Mobile / fallback styles  
2. Larger breakpoints  
3. `@supports` enhancements (still mobile-first inside them)

Use shared variables and mixins (`$spacer`, `$spacer-sm`, etc., and mixins in `assets/scss/global/_mixins.scss`) so spacing and breakpoints stay consistent.

### Breakpoint mixins

See `assets/scss/global/_mixins.scss`. Typical mins:

| Mixin | Approx. min-width |
| --- | --- |
| `$mobile` | 400px |
| `$phablet` | 600px |
| `$tablet` | 800px |
| `$laptop` | 1000px |
| `$desktop` | 1200px |

Pass `'max'` as the first argument for max-width variants (see mixins file comments).

More on mixins: comment link in `_mixins.scss` → [wiki SCSS note redirected via repo](https://github.com/engagingnewsproject/mediaengagement/blob/master/wp-content/themes/engage-2-x/assets/scss/global/_mixins.scss).

## Theme structure

Useful starting points:

| Area | Where |
| --- | --- |
| Twig templates | `templates/` |
| SCSS / front-end JS | `assets/` |
| PHP managers / models | `src/` |
| ACF JSON | `acf-json/` |
| Built assets | `dist/` |

Video (Drive): [Finding a template file](https://drive.google.com/file/d/1grUKaQCQELhGl8spyl9MU63Y7TZmA0_z/view?usp=sharing)

## Timber and Twig

Engage is built on [Timber](https://timber.github.io/docs/) and [Twig](https://twig.symfony.com/).

- [Timber docs](https://timber.github.io/docs/)
- [Timber ACF cookbook](https://timber.github.io/docs/guides/acf-cookbook/)
- [Twig docs](https://twig.symfony.com/doc/3.x/)
- [ACF docs](https://www.advancedcustomfields.com/resources/)
- [ACF Repeater](https://www.advancedcustomfields.com/resources/repeater/)
- [ACF Flexible Content](https://www.advancedcustomfields.com/resources/flexible-content/)
- [Sass guide](https://sass-lang.com/guide)

## Related

- [How To](how-to.md) — ACF export/import, CPT checklist, and other recipes
- [WordPress](wordpress.md) — hooks, Rank Math, editor notes
