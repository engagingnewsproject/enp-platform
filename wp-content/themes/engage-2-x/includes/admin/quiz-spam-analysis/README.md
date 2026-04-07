# Quiz spam analysis (theme admin)

**Analyse Quizzes** on the Quizzes list writes post meta for sort/filter; it does not delete content.

## Data files (`data/`)

| File | Purpose |
|------|---------|
| `thresholds.json` | Rule weights, tier thresholds, `allowlist_domains` (core trusted hosts), burst threshold |
| `trusted_domains.txt` | Extra trusted owner-email domains, merged with `allowlist_domains` (`#` comments allowed) |
| `disposable_email_domains.txt` | Upstream disposable domains (one per line) |
| `extra_risk_domains.txt` | Site-specific risky domains (`#` comments allowed) |

Refresh disposable list to match the plugin inventory:

```bash
curl -fsSL \
  "https://raw.githubusercontent.com/disposable-email-domains/disposable-email-domains/main/disposable_email_blocklist.conf" \
  -o disposable_email_domains.txt
```

Or copy from `wp-content/plugins/enp-quiz/LOCAL/quiz-spam-inventory/disposable_email_domains.txt`.

## Capability

Running analysis requires `manage_options` (same pattern as **Sync Quizzes**).

## Chunked analysis

Large sites process **80** quiz posts per HTTP request, then redirect to continue until finished, to avoid timeouts.

## Theme caveat

This logic lives in the theme; switching themes removes these admin features until you restore Engage or move the code to a plugin.

## Troubleshooting

If wp-admin fatals on `Engage\QuizSpamAnalysis\Analysis` (or `Logger` / `get_instance`), you had an old bootstrap expecting the short namespace. The theme ships **compat** classes under `compat-quiz-spam/` and autoloads them via Composer—run `composer dump-autoload` in this theme directory. Use **`quiz-spam-admin.php`** as the only functional entrypoint in `functions.php` (or `class-quiz-spam-analysis-admin.php`, which simply requires `quiz-spam-admin.php` if needed); do not require both.
