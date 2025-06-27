# Git Subtree Workflow for CME Alt Text Generator

This document explains how to manage the CME Alt Text Generator plugin using Git Subtree, which allows the plugin to exist in both the main theme repository and its own GitHub repository.

## Overview

The plugin is maintained in the main theme repository at:
```
public/wp-content/plugins/cme-alt-text-generator/
```

And is also available as a standalone repository at:
```
git@github.com:yourusername/cme-alt-text-generator.git
```

## Initial Setup

### 1. Create the GitHub Repository

1. Go to GitHub and create a new repository called `cme-alt-text-generator`
2. Make it public or private as needed
3. Don't initialize with README, .gitignore, or license (we'll push our existing files)

### 2. Update the Deployment Script

Edit `deploy.sh` and update the `PLUGIN_REPO` variable with your actual GitHub username:

```bash
PLUGIN_REPO="git@github.com:YOUR_USERNAME/cme-alt-text-generator.git"
```

### 3. Initial Push

From the root of your main repository, run:

```bash
./public/wp-content/plugins/cme-alt-text-generator/deploy.sh
```

Or manually:

```bash
git subtree push --prefix=public/wp-content/plugins/cme-alt-text-generator git@github.com:yourusername/cme-alt-text-generator.git main
```

## Daily Workflow

### Making Changes

1. **Work normally** in your main theme repository
2. **Edit the plugin files** in `public/wp-content/plugins/cme-alt-text-generator/`
3. **Commit changes** to your main repository as usual

### Deploying to Plugin Repository

When you want to update the standalone plugin repository:

```bash
# From the root of your main repository
./public/wp-content/plugins/cme-alt-text-generator/deploy.sh
```

Or manually:

```bash
git subtree push --prefix=public/wp-content/plugins/cme-alt-text-generator git@github.com:yourusername/cme-alt-text-generator.git main
```

## Pulling Changes from Plugin Repository

If you make changes directly in the plugin repository and want to pull them back to the main repository:

```bash
git subtree pull --prefix=public/wp-content/plugins/cme-alt-text-generator git@github.com:yourusername/cme-alt-text-generator.git main
```

## Best Practices

### 1. Always Work in Main Repository
- Make all changes in the main theme repository
- Use the plugin repository primarily for distribution/sharing

### 2. Commit Before Deploying
- Always commit your changes to the main repository before deploying
- The deployment script will warn you about uncommitted changes

### 3. Use the Deployment Script
- The `deploy.sh` script includes error checking and helpful messages
- It's easier than remembering the full git subtree command

### 4. Version Management
- Update the version number in the main plugin file when making significant changes
- Consider using semantic versioning (e.g., 1.0.0, 1.0.1, 1.1.0)

## Troubleshooting

### "Repository not found" Error
- Make sure the GitHub repository exists
- Check that you have the correct repository URL
- Verify your SSH keys are set up correctly

### "Permission denied" Error
- Ensure you have push access to the plugin repository
- Check your GitHub authentication

### Merge Conflicts
- If you get merge conflicts when pulling from the plugin repository, resolve them as you would with any Git merge
- Consider using `git subtree pull --squash` to avoid complex merge histories

## File Structure

The plugin repository will contain:
```
cme-alt-text-generator/
├── cme-alt-text-generator.php    # Main plugin file
├── cme-alt-text-results.php      # Results viewer
├── README.md                     # Plugin documentation
├── .gitignore                    # Git ignore rules
├── deploy.sh                     # Deployment script
└── GIT_SUBTREE_WORKFLOW.md       # This file
```

## Benefits of This Approach

1. **Single source of truth**: All development happens in the main repository
2. **Easy deployment**: Simple script to push updates
3. **Full history**: Plugin repository gets complete commit history
4. **No symlinks**: Standard WordPress plugin structure
5. **Flexible**: Can pull changes from either repository if needed 