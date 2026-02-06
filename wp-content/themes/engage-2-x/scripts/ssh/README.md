# SSH Database Sync Scripts

Shell scripts that sync the production WordPress database to development or staging via SSH, using WP-CLI on WP Engine.

## Scripts

| Script | Purpose |
|--------|---------|
| `sync-db-dev.sh` | Exports production DB, imports into **Development** (cmedev), runs search-replace for URLs, flushes cache |
| `sync-db-staging.sh` | Same flow, but targets **Staging** (cmestaging) |

## Prerequisites

- SSH key at `~/.ssh/wpengine_rsa` with access to the WP Engine installs
- WP-CLI available on the target WP Engine environments

## Usage

From the theme root:

```bash
yarn sync-db-dev      # Sync to Development
yarn sync-db-staging  # Sync to Staging
```

Or run directly:

```bash
bash scripts/ssh/sync-db-dev.sh
bash scripts/ssh/sync-db-staging.sh
```

## Configuration

Each script has config at the top:

- **SSH commands** – Hosts and key paths (default: `cmengage`, `cmedev`, `cmestaging` on WP Engine)
- **URLs** – Source (`mediaengagement.org`) and target (`cmedev.wpengine.com` / `cmestaging.wpengine.com`)

Change these if the environments or domains differ.
