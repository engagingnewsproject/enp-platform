'use strict';

/**
 * After Mix compiles flickity-inline.scss, paste the result into html-header.twig
 * between CSS comments engage-flickity-custom:start / :end (inside the <style> block).
 */

const fs = require('fs');
const path = require('path');

const root = path.resolve(__dirname, '..');
const cssPath = path.join(root, 'dist/css/flickity-inline.css');
const twigPath = path.join(root, 'templates/html-header.twig');

const MARKER_START = '/* engage-flickity-custom:start */';
const MARKER_END = '/* engage-flickity-custom:end */';

function escapeRegExp(s) {
  return s.replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
}

function main() {
  if (!fs.existsSync(cssPath)) {
    console.warn(
      '[inject-flickity-inline-css] Skip: dist/css/flickity-inline.css not found.'
    );
    return;
  }

  let css = fs
    .readFileSync(cssPath, 'utf8')
    .replace(/\/\*# sourceMappingURL=.*?\*\//gs, '')
    .trim();

  const twig = fs.readFileSync(twigPath, 'utf8');

  if (!twig.includes(MARKER_START) || !twig.includes(MARKER_END)) {
    console.error(
      '[inject-flickity-inline-css] Markers missing in templates/html-header.twig'
    );
    process.exit(1);
  }

  const pattern = new RegExp(
    `${escapeRegExp(MARKER_START)}[\\s\\S]*?${escapeRegExp(MARKER_END)}`,
    'm'
  );

  if (!pattern.test(twig)) {
    console.error('[inject-flickity-inline-css] Could not match marker region.');
    process.exit(1);
  }

  const indentedCss = css
    .split('\n')
    .map((line) => `\t${line.replace(/\s+$/, '')}`)
    .join('\n');

  const replacement = `${MARKER_START}\n${indentedCss}\n\t${MARKER_END}`;
  const updated = twig.replace(pattern, replacement);

  if (updated === twig) {
    return;
  }

  fs.writeFileSync(twigPath, updated, 'utf8');
  console.log('[inject-flickity-inline-css] Updated templates/html-header.twig');
}

module.exports = main;

if (require.main === module) {
  main();
}
