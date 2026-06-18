# Quiz spam analysis (theme admin)

**Analyse Quizzes** on the Quizzes list writes post meta for sort/filter; it does not delete content.

## Data files (`data/`)

| File | Purpose |
|------|---------|
| `thresholds.json` | Rule weights, tier thresholds, `allowlist_domains` (trusted embed hosts / registrable domains), burst threshold |
| `trusted_domains.txt` | Extra trusted hosts merged with `allowlist_domains`—embed URLs under these are never flagged disposable (`#` comments allowed) |
| `disposable_email_domains.txt` | Domains matched against **embed site URL hosts** (same list format as disposable email registrable domains) |
| `extra_risk_domains.txt` | Site-specific risky embed hosts (`#` comments allowed) |

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

## Spam user quizzes (bulk draft / delete)

**Quizzes → Spam user quizzes** lists every row in `enp_quiz` whose `quiz_owner` has the `spam_user` role (site administrators are excluded even if mis-tagged). The default **All** view hides quizzes already marked deleted in ENP (`quiz_is_deleted = 1`). Use **Marked deleted in ENP** to find leftovers (synced WordPress posts may still show “Quiz deleted in ENP” on edit)—bulk **Permanently delete** there to hard-remove ENP rows and drop orphan CPTs.

| Bulk action | Effect |
|-------------|--------|
| Export CSV | Download selected rows for review |
| Set to draft | Sets `enp_quiz.quiz_status` to `draft` (unpublish in the app). Run **Sync Quizzes** on the main list afterward so CPT meta matches. |
| Permanently delete | Hard-deletes the **`enp_quiz`** row (and embed links) first, then force-deletes synced **quiz** posts. **Trash on the main Quizzes list does not remove ENP data** and may leave `quiz_is_deleted` rows visible under **Marked deleted in ENP**. |

Workflow: mark users `spam_user` → open this screen → filter **Published** → export → bulk draft → verify → bulk delete (chunked).

## Troubleshooting

If wp-admin fatals on `Engage\QuizSpamAnalysis\Analysis` (or `Logger` / `get_instance`), you had an old bootstrap expecting the short namespace. The theme ships **compat** classes under `compat-quiz-spam/` and autoloads them via Composer—run `composer dump-autoload` in this theme directory. Use **`quiz-spam-admin.php`** as the only functional entrypoint in `functions.php` (or `class-quiz-spam-analysis-admin.php`, which simply requires `quiz-spam-admin.php` if needed); do not require both.
