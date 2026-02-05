# Accessibility reports (Lighthouse)

Run Lighthouse **accessibility-only** audits for the live site. Outputs **one file** listing every page with an accessibility score **below 100**. Same checks as Chrome DevTools → Lighthouse → Accessibility.

[Digital Accessibility Google Doc](https://docs.google.com/document/d/1oGWvoJpOrZs0ynZ4yfwPx4ZRtJ951-tITuuR4epBykY/edit?tab=t.0)

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

**Pause and resume:** Progress is saved after each URL and when you press Ctrl+C. You can stop the run and start it again with the same command; already-audited URLs are skipped. Progress is stored in `scripts/accessibility/reports/lighthouse-a11y/progress.json`. When every URL is done, that file is **renamed** to `progress-YYYY-MM-DDTHH-mm-ss.json` (e.g. `progress-2026-02-04T14-30-00.json`) so you can keep a history of full runs. To start a completely new run from scratch, delete `progress.json` before running (optionally delete or keep old `progress-*.json` archives).

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

## Output files

- **CSV report:** `scripts/accessibility/reports/lighthouse-a11y/accessibility-below-100.csv` — headers `url,score`, one row per page with score below 100 (0–100).
- **Progress:** While running or after a partial run, `progress.json` holds completed URLs and below-100 list (used for resume). When a **full** run completes, it is renamed to `progress-YYYY-MM-DDTHH-mm-ss.json` so you can compare prior runs. Delete `progress.json` to start a fresh run.
- **Ignored by git:** `scripts/accessibility/reports/` is in the project `.gitignore`, so these files are not committed.

## Troubleshooting

- **“Sitemap fetch failed”** – Check `SITE` is correct and the live site serves `/wp-sitemap.xml`. Try opening `https://your-site.com/wp-sitemap.xml` in a browser.
- **Chrome not found** – Lighthouse uses `chrome-launcher` to find Chrome/Chromium. Install Chrome or set `CHROME_PATH` if needed.
- **No URLs found** – With `--urls=...`, ensure the file has at least one non-comment, non-empty line.
