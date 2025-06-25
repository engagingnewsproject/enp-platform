# Alt Text Generator for WordPress

This solution provides automated alt text generation for WordPress images using OpenAI's GPT-4 Vision API. It includes both a web-based admin interface and command-line tools for batch processing.

## Features

- **AI-Powered Alt Text Generation**: Uses OpenAI's GPT-4 Vision API to generate descriptive alt text
- **Web Admin Interface**: User-friendly interface in WordPress admin
- **CLI Script**: Command-line script for batch processing
- **WP-CLI Integration**: WP-CLI commands for easy automation
- **Batch Processing**: Handle large numbers of images efficiently
- **Error Handling**: Robust error handling and logging
- **Progress Tracking**: Real-time progress updates
- **Dry Run Mode**: Test without making changes

## Setup

### 1. Prerequisites

- WordPress site with admin access
- OpenAI API key (get one at https://platform.openai.com/api-keys)
- PHP with cURL extension enabled
- WP-CLI (optional, for command-line usage)

### 2. Installation

The files are already included in your theme:

- `includes/admin/alt-text-generator.php` - Admin interface
- `cli-alt-text-generator.php` - CLI script
- `wp-cli-alt-text.php` - WP-CLI commands

### 3. Configure API Key

#### Option A: Web Admin Interface
1. Go to **Tools > Alt Text Generator** in your WordPress admin
2. Enter your OpenAI API key
3. Click "Save API Key"

#### Option B: WordPress Options
```php
update_option('engage_alt_text_openai_key', 'your-openai-api-key-here');
```

## Usage

### Web Admin Interface

1. Navigate to **Tools > Alt Text Generator** in WordPress admin
2. Configure your OpenAI API key if not already done
3. Choose your processing option:
   - **Generate Alt Text for All Images**: Process all images without alt text
   - **Generate Sample (5 images)**: Test with a small batch
   - **Individual Images**: Click "Generate Alt Text" on specific images

### Command Line Script

```bash
# Basic usage (uses stored API key)
php cli-alt-text-generator.php

# With API key
php cli-alt-text-generator.php --api-key=sk-your-key-here

# Process only first 10 images
php cli-alt-text-generator.php --limit=10

# Dry run (see what would be processed)
php cli-alt-text-generator.php --dry-run --verbose

# Show help
php cli-alt-text-generator.php --help
```

### WP-CLI Commands

If you have WP-CLI installed:

```bash
# Count images without alt text
wp alt-text count

# Generate alt text for all images
wp alt-text generate

# Generate alt text for first 10 images
wp alt-text generate --limit=10

# Dry run with verbose output
wp alt-text generate --dry-run --verbose

# Use specific API key
wp alt-text generate --api-key=sk-your-key-here
```

## Processing Large Numbers of Images

For your 3,772 images, here are recommended approaches:

### 1. Start Small (Recommended)
```bash
# Test with 10 images first
wp alt-text generate --limit=10 --verbose
```

### 2. Process in Batches
```bash
# Process 100 images at a time
wp alt-text generate --limit=100
```

### 3. Full Processing
```bash
# Process all images (will take several hours)
wp alt-text generate
```

### 4. Background Processing
```bash
# Run in background (Linux/Mac)
nohup wp alt-text generate > alt-text-log.txt 2>&1 &
```

## Cost Estimation

Using OpenAI's GPT-4 Vision API:
- **Input cost**: ~$0.01-0.03 per image
- **For 3,772 images**: Approximately $37-113 USD
- **Processing time**: ~30-60 minutes (with 0.5-second delays)

## Monitoring Progress

### Web Interface
- Progress bar shows real-time updates
- Success/error messages for each image
- Summary statistics after completion

### Command Line
- Progress bar (WP-CLI) or countdown (CLI script)
- Verbose mode shows each image being processed
- Summary at the end with timing information

### Logs
- Errors are logged to WordPress error log
- Check `wp-content/debug.log` for detailed error information

## Error Handling

The system handles various error scenarios:
- **API errors**: Retries and continues with next image
- **Missing files**: Skips images that can't be found
- **Network issues**: Graceful handling of timeouts
- **Rate limiting**: Built-in delays to avoid API limits

## Customization

### Modify Alt Text Prompt
Edit the prompt in the `generate_alt_text_with_ai()` method:

```php
'text' => 'Generate a concise, descriptive alt text for this image. Focus on what is visually important and meaningful. Keep it under 125 characters. Do not include phrases like "image of" or "photo of" - just describe what you see.'
```

### Adjust Processing Speed
Modify the delay between API calls:

```php
usleep(500000); // 0.5 seconds - increase for slower processing
```

### Change API Model
Switch to a different OpenAI model:

```php
'model' => 'gpt-4-vision-preview', // or 'gpt-4o' for newer model
```

## Troubleshooting

### Common Issues

1. **"OpenAI API key not configured"**
   - Set your API key in the admin interface or via command line

2. **"Image file not found"**
   - Check that image files exist in the uploads directory
   - Verify file permissions

3. **"OpenAI API error: HTTP 429"**
   - Rate limit exceeded - increase delays between requests
   - Check your OpenAI account usage

4. **"Invalid response from OpenAI API"**
   - Check API key validity
   - Verify internet connection

### Debug Mode

Enable WordPress debug mode to see detailed error logs:

```php
// In wp-config.php
define('WP_DEBUG', true);
define('WP_DEBUG_LOG', true);
```

## Security Considerations

- API keys are stored in WordPress options (encrypted in database)
- All user input is sanitized
- Nonces are used for AJAX requests
- Only administrators can access the interface

## Performance Tips

1. **Start with a small batch** to test the system
2. **Run during off-peak hours** to avoid impacting site performance
3. **Monitor server resources** during large batch processing
4. **Use WP-CLI** for better progress tracking
5. **Consider running in background** for large batches

## Support

For issues or questions:
1. Check the error logs
2. Test with a small batch first
3. Verify your OpenAI API key and credits
4. Ensure your server has sufficient memory and time limits

## Files Created

- `includes/admin/alt-text-generator.php` - Admin interface and AJAX handlers
- `cli-alt-text-generator.php` - Standalone CLI script
- `wp-cli-alt-text.php` - WP-CLI integration
- Updated `functions.php` - Includes the alt text generator

The solution is now ready to use! Start with a small test batch to ensure everything works correctly before processing all 3,772 images. 