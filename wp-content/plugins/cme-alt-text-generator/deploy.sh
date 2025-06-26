#!/bin/bash

# CME Alt Text Generator Deployment Script
# This script pushes the plugin to its own GitHub repository

# Configuration
PLUGIN_REPO="git@github.com:engagingnewsproject/cme-alt-text-generator.git"
PLUGIN_BRANCH="main"
PLUGIN_PATH="wp-content/plugins/cme-alt-text-generator"

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

echo -e "${GREEN}🚀 CME Alt Text Generator Deployment${NC}"
echo "=================================="

# Check if we're in the right directory
if [ ! -d "$PLUGIN_PATH" ]; then
    echo -e "${RED}❌ Error: Plugin directory not found at $PLUGIN_PATH${NC}"
    echo "Please run this script from the root of your main repository."
    exit 1
fi

# Check if there are any uncommitted changes
if [ -n "$(git status --porcelain)" ]; then
    echo -e "${YELLOW}⚠️  Warning: You have uncommitted changes in your main repository.${NC}"
    echo "Please commit or stash them before deploying."
    read -p "Continue anyway? (y/N): " -n 1 -r
    echo
    if [[ ! $REPLY =~ ^[Yy]$ ]]; then
        exit 1
    fi
fi

echo -e "${GREEN}📦 Pushing plugin to GitHub repository...${NC}"

# Push the plugin subtree to the external repository
if git subtree push --prefix="$PLUGIN_PATH" "$PLUGIN_REPO" "$PLUGIN_BRANCH"; then
    echo -e "${GREEN}✅ Successfully deployed plugin to GitHub!${NC}"
    echo -e "${GREEN}🔗 Repository: $PLUGIN_REPO${NC}"
else
    echo -e "${RED}❌ Failed to deploy plugin.${NC}"
    echo "Make sure:"
    echo "1. The GitHub repository exists"
    echo "2. You have push access to the repository"
    echo "3. Your SSH keys are configured correctly"
    exit 1
fi

echo -e "${GREEN}🎉 Deployment complete!${NC}" 