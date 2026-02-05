# Accessibility reports (Lighthouse)

Run Lighthouse **accessibility-only** audits for the live site. Outputs **one file** listing every page with an accessibility score **below 100**. Same checks as Chrome DevTools → Lighthouse → Accessibility.

## Prerequisites

- **Node 18+** (script uses native `fetch`)
- **Chrome or Chromium** (Lighthouse launches headless Chrome)
- From the **theme root**: `wp-content/themes/engage-2-x/`

## Quick start

From the theme directory:

```bash
cd wp-content/themes/engage-2-x
yarn install   # if you haven’t already (installs lighthouse)
SITE=https://mediaengagement.org yarn lighthouse-a11y
```

The script writes a single CSV to **`scripts/accessibility/reports/lighthouse-a11y/accessibility-below-100.csv`** with columns `url` and `score` (0–100). Only pages with score &lt; 100 are included.

**Pause and resume:** Progress is saved after each URL and when you press Ctrl+C. You can stop the run and start it again with the same command; already-audited URLs are skipped. Progress is stored in `scripts/accessibility/reports/lighthouse-a11y/progress.json` (same folder as the CSV). When every URL is done, that file is removed so the next run is fresh.

## How to run

### 1. Audit all pages from the sitemap (default)

Uses the site’s WordPress sitemap (`/wp-sitemap.xml`) to discover every public URL, then runs Lighthouse on each.

```bash
# Use default site (https://mediaengagement.org)
yarn lighthouse-a11y

# Or set the live site explicitly
SITE=https://mediaengagement.org yarn lighthouse-a11y
```

- **Delay:** 2 seconds between each URL so the server isn’t hammered.
- **Output:** One CSV, `scripts/accessibility/reports/lighthouse-a11y/accessibility-below-100.csv`, with only pages scoring below 100.

### 2. Audit a custom list of URLs

Put one URL per line in `urls.txt` (or another file), then pass it with `--urls=`.

```bash
yarn lighthouse-a11y --urls=scripts/accessibility/urls.txt
```

- Lines starting with `#` and blank lines are ignored.
- Example content in `urls.txt`:
  ```
  # One URL per line
  https://mediaengagement.org/
  https://mediaengagement.org/about/
  ```

## Files in this directory

| File | Purpose |
|------|--------|
| `lighthouse-a11y.js` | Node script: fetches URLs (sitemap or file), runs Lighthouse a11y-only, writes one CSV of pages below 100. |
| `urls.txt` | Optional. One URL per line; use with `--urls=scripts/accessibility/urls.txt`. |
| `README.md` | This file. |

## Output file

- **Path:** `scripts/accessibility/reports/lighthouse-a11y/accessibility-below-100.csv` (inside this directory).
- **Format:** CSV with headers `url,score`. One row per page with accessibility score below 100 (score is 0–100).
- **Ignored by git:** `scripts/accessibility/reports/` is in the theme’s `.gitignore`, so the file is not committed.

## Troubleshooting

- **“Sitemap fetch failed”** – Check `SITE` is correct and the live site serves `/wp-sitemap.xml`. Try opening `https://your-site.com/wp-sitemap.xml` in a browser.
- **Chrome not found** – Lighthouse uses `chrome-launcher` to find Chrome/Chromium. Install Chrome or set `CHROME_PATH` if needed.
- **No URLs found** – With `--urls=...`, ensure the file has at least one non-comment, non-empty line.
