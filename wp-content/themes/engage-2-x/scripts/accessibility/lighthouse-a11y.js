#!/usr/bin/env node
/**
 * Run Lighthouse accessibility-only audits for every public URL from the site
 * sitemap (or a custom URL list). Outputs a single CSV of pages with score below 100.
 * Progress is saved after each URL so you can Ctrl+C and restart; already-done URLs are skipped.
 *
 * Usage:
 *   SITE=https://mediaengagement.org yarn lighthouse-a11y
 *   yarn lighthouse-a11y --urls=scripts/accessibility/urls.txt
 *
 * Requires Node 18+ (fetch). Run from theme root.
 */

const fs = require('fs');
const path = require('path');

const SITE = process.env.SITE || 'https://mediaengagement.org';
const DELAY_MS = 2000;
/** Output dir: inside scripts/accessibility so reports live with the script */
const REPORT_DIR = path.join(process.cwd(), 'scripts', 'accessibility', 'reports', 'lighthouse-a11y');
/** Single output file: pages with accessibility score < 100 */
const SUMMARY_FILENAME = 'accessibility-below-100.csv';
/** Checkpoint file for pause/resume (deleted when run completes) */
const PROGRESS_FILENAME = 'progress.json';

/**
 * Load progress from last run, if any. Returns { completedUrls: Set<string>, below100: Array<{url,score}> }.
 */
function loadProgress() {
  const progressPath = path.join(REPORT_DIR, PROGRESS_FILENAME);
  if (!fs.existsSync(progressPath)) {
    return { completedUrls: new Set(), below100: [] };
  }
  try {
    const data = JSON.parse(fs.readFileSync(progressPath, 'utf8'));
    return {
      completedUrls: new Set(data.completedUrls || []),
      below100: Array.isArray(data.below100) ? data.below100 : [],
    };
  } catch {
    return { completedUrls: new Set(), below100: [] };
  }
}

/**
 * Persist progress so the run can be resumed after Ctrl+C.
 * @param {Set<string>} completedUrls
 * @param {{ url: string, score: number }[]} below100
 */
function saveProgress(completedUrls, below100) {
  const progressPath = path.join(REPORT_DIR, PROGRESS_FILENAME);
  fs.writeFileSync(
    progressPath,
    JSON.stringify({
      completedUrls: [...completedUrls],
      below100,
    }),
    'utf8'
  );
}

/**
 * Archive progress file when a full run completes. Renames progress.json to
 * progress-YYYY-MM-DDTHH-mm-ss.json so prior runs can be reviewed. Next run will create a new progress.json.
 */
function archiveProgress() {
  const progressPath = path.join(REPORT_DIR, PROGRESS_FILENAME);
  if (!fs.existsSync(progressPath)) return;
  const timestamp = new Date().toISOString().slice(0, 19).replace(/:/g, '-');
  const archivePath = path.join(REPORT_DIR, `progress-${timestamp}.json`);
  fs.renameSync(progressPath, archivePath);
}

/** Extract <loc> URLs from sitemap XML string. */
function getUrlsFromSitemapXml(xml) {
  const locs = xml.match(/<loc>([^<]+)<\/loc>/g);
  return locs ? locs.map((el) => el.replace(/<\/?loc>/g, '').trim()) : [];
}

/** Fetch one sitemap URL and return response text. */
async function fetchSitemap(url) {
  const res = await fetch(url, { redirect: 'follow' });
  if (!res.ok) throw new Error(`Failed to fetch ${url}: ${res.status}`);
  return res.text();
}

/**
 * Collect all page URLs from WordPress sitemap (index + sub-sitemaps).
 * Filters to same-origin and skips sitemap index URLs for the final list.
 */
async function getUrlsFromSitemap(baseUrl) {
  const origin = new URL(baseUrl).origin;
  const indexUrl = baseUrl.replace(/\/?$/, '/wp-sitemap.xml');
  const seen = new Set();
  const pageUrls = [];

  async function processSitemap(url) {
    const xml = await fetchSitemap(url);
    const urls = getUrlsFromSitemapXml(xml);
    for (const u of urls) {
      if (!u.startsWith(origin) || seen.has(u)) continue;
      seen.add(u);
      // Sub-sitemap (WordPress uses .../wp-sitemap-posts-post-1.xml etc.)
      if (/sitemap.*\.xml$/i.test(u)) {
        await processSitemap(u);
      } else {
        pageUrls.push(u);
      }
    }
  }

  await processSitemap(indexUrl);
  return pageUrls;
}

/** Read URLs from a text file (one URL per line; skip empty and # comments). */
function getUrlsFromFile(filePath) {
  const abs = path.isAbsolute(filePath) ? filePath : path.join(process.cwd(), filePath);
  const text = fs.readFileSync(abs, 'utf8');
  return text
    .split('\n')
    .map((line) => line.trim())
    .filter((line) => line && !line.startsWith('#'));
}

/**
 * Run Lighthouse for one URL (accessibility only). Returns accessibility score 0–100 or null on failure.
 * @param {string} url
 * @param {number} chromePort
 * @returns {Promise<{ score: number } | { score: null, error: string }>}
 */
async function runLighthouse(url, chromePort) {
  const lighthouse = require('lighthouse').default;
  const config = {
    extends: 'lighthouse:default',
    onlyCategories: ['accessibility'],
  };
  const options = {
    port: chromePort,
    logLevel: 'silent',
    output: 'json',
  };
  const runnerResult = await lighthouse(url, options, config);
  const score = runnerResult?.lhr?.categories?.accessibility?.score;
  // LHR uses 0–1; convert to 0–100 for output
  const score100 = score != null ? Math.round(Number(score) * 100) : null;
  return { score: score100 };
}

function sleep(ms) {
  return new Promise((resolve) => setTimeout(resolve, ms));
}

async function main() {
  const args = process.argv.slice(2);
  const urlsArg = args.find((a) => a.startsWith('--urls='));
  const urlsPath = urlsArg ? urlsArg.slice('--urls='.length) : null;

  let urls;
  if (urlsPath) {
    urls = getUrlsFromFile(urlsPath);
    if (urls.length === 0) {
      console.error('No URLs found in', urlsPath);
      process.exit(1);
    }
    console.log('Using', urls.length, 'URL(s) from', urlsPath);
  } else {
    console.log('Fetching URLs from sitemap at', SITE);
    try {
      urls = await getUrlsFromSitemap(SITE);
    } catch (err) {
      console.error('Sitemap fetch failed:', err.message);
      process.exit(1);
    }
    if (urls.length === 0) {
      console.error('No page URLs found in sitemap.');
      process.exit(1);
    }
    console.log('Found', urls.length, 'URL(s) from sitemap');
  }

  fs.mkdirSync(REPORT_DIR, { recursive: true });

  const progress = loadProgress();
  const urlsToRun = urls.filter((u) => !progress.completedUrls.has(u));

  if (urlsToRun.length === 0) {
    const summaryPath = path.join(REPORT_DIR, SUMMARY_FILENAME);
    const csvRows = ['url,score', ...progress.below100.map(({ url, score }) => `"${url.replace(/"/g, '""')}",${score}`)];
    fs.writeFileSync(summaryPath, csvRows.join('\n'), 'utf8');
    archiveProgress();
    console.log('No URLs left to run (already complete). Summary written from progress.');
    console.log('Pages with accessibility score below 100:', progress.below100.length);
    console.log('Summary:', path.relative(process.cwd(), summaryPath));
    return;
  }

  if (progress.completedUrls.size > 0) {
    console.log('Resuming: skipping', progress.completedUrls.size, 'already-done URL(s). Remaining:', urlsToRun.length);
  }

  const chromeLauncher = require('chrome-launcher');
  const chrome = await chromeLauncher.launch({
    chromeFlags: ['--headless', '--no-sandbox', '--disable-gpu'],
  });

  const completedUrls = new Set(progress.completedUrls);
  const below100 = [...progress.below100];
  const total = urls.length;

  /** On Ctrl+C, save progress and exit so the next run can resume */
  const onSigint = () => {
    console.log('\nStopping — saving progress...');
    saveProgress(completedUrls, below100);
    console.log('Progress saved. Run again to resume.');
    process.exit(130);
  };
  process.on('SIGINT', onSigint);

  try {
    for (let i = 0; i < urlsToRun.length; i++) {
      const url = urlsToRun[i];
      const doneCount = completedUrls.size + 1;
      process.stdout.write(`[${doneCount}/${total}] ${url} ... `);
      try {
        const { score } = await runLighthouse(url, chrome.port);
        completedUrls.add(url);
        if (score != null) {
          if (score < 100) {
            below100.push({ url, score });
            console.log(`${score} (below 100)`);
          } else {
            console.log(`${score}`);
          }
        } else {
          console.log('FAIL (no score)');
        }
        saveProgress(completedUrls, below100);
      } catch (err) {
        console.log('FAIL:', err.message);
        saveProgress(completedUrls, below100);
      }
      if (i < urlsToRun.length - 1) await sleep(DELAY_MS);
    }
  } finally {
    process.removeListener('SIGINT', onSigint);
    await chrome.kill();
  }

  const summaryPath = path.join(REPORT_DIR, SUMMARY_FILENAME);
  const csvRows = ['url,score', ...below100.map(({ url, score }) => `"${url.replace(/"/g, '""')}",${score}`)];
  fs.writeFileSync(summaryPath, csvRows.join('\n'), 'utf8');
  archiveProgress();

  console.log('');
  console.log('Done. Pages with accessibility score below 100:', below100.length);
  console.log('Summary:', path.relative(process.cwd(), summaryPath));
}

main().catch((err) => {
  console.error(err);
  process.exit(1);
});
