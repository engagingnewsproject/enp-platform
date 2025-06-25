#!/bin/bash

# Alt Text Generator Shell Script
# Makes it easy to run the CLI alt text generator with common options

set -e

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Default values
LIMIT=""
DRY_RUN=""
VERBOSE=""
API_KEY=""

# Function to show usage
show_usage() {
    echo -e "${BLUE}Alt Text Generator for WordPress${NC}"
    echo ""
    echo "Usage: $0 [OPTIONS]"
    echo ""
    echo "OPTIONS:"
    echo "  -l, --limit N        Limit processing to N images"
    echo "  -d, --dry-run        Show what would be processed without making changes"
    echo "  -v, --verbose        Show detailed output"
    echo "  -k, --api-key KEY    OpenAI API key"
    echo "  -h, --help           Show this help message"
    echo ""
    echo "EXAMPLES:"
    echo "  $0 --limit 10 --dry-run --verbose"
    echo "  $0 --api-key sk-... --limit 100"
    echo "  $0 --verbose"
    echo ""
}

# Function to show current status
show_status() {
    echo -e "${BLUE}=== Alt Text Generator Status ===${NC}"
    echo "Limit: ${LIMIT:-All images}"
    echo "Dry Run: ${DRY_RUN:+Yes}"
    echo "Verbose: ${VERBOSE:+Yes}"
    echo "API Key: ${API_KEY:+Set}"
    echo ""
}

# Parse command line arguments
while [[ $# -gt 0 ]]; do
    case $1 in
        -l|--limit)
            LIMIT="$2"
            shift 2
            ;;
        -d|--dry-run)
            DRY_RUN="--dry-run"
            shift
            ;;
        -v|--verbose)
            VERBOSE="--verbose"
            shift
            ;;
        -k|--api-key)
            API_KEY="$2"
            shift 2
            ;;
        -h|--help)
            show_usage
            exit 0
            ;;
        *)
            echo -e "${RED}Unknown option: $1${NC}"
            show_usage
            exit 1
            ;;
    esac
done

# Check if we're in the right directory
if [ ! -f "cli-alt-text-generator.php" ]; then
    echo -e "${RED}Error: cli-alt-text-generator.php not found in current directory${NC}"
    echo "Please run this script from your WordPress theme directory."
    exit 1
fi

# Build command
CMD="php cli-alt-text-generator.php"

if [ ! -z "$LIMIT" ]; then
    CMD="$CMD --limit=$LIMIT"
fi

if [ ! -z "$DRY_RUN" ]; then
    CMD="$CMD $DRY_RUN"
fi

if [ ! -z "$VERBOSE" ]; then
    CMD="$CMD $VERBOSE"
fi

if [ ! -z "$API_KEY" ]; then
    CMD="$CMD --api-key=$API_KEY"
fi

# Show what we're about to do
show_status
echo -e "${YELLOW}Running command:${NC} $CMD"
echo ""

# Confirm before running (unless it's a dry run)
if [ -z "$DRY_RUN" ] && [ -z "$LIMIT" ]; then
    echo -e "${YELLOW}Warning: This will process ALL images without alt text!${NC}"
    read -p "Are you sure you want to continue? (y/N): " -n 1 -r
    echo
    if [[ ! $REPLY =~ ^[Yy]$ ]]; then
        echo -e "${RED}Cancelled.${NC}"
        exit 1
    fi
fi

# Run the command
echo -e "${GREEN}Starting alt text generation...${NC}"
echo ""

eval $CMD

echo ""
echo -e "${GREEN}Alt text generation completed!${NC}" 