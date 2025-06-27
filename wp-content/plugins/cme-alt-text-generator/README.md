# CME Alt Text Generator for WordPress

A WordPress plugin that provides an admin interface for automatically generating alt text for images using AI services (OpenAI).

## Features

- **AI-Powered Alt Text Generation**: Uses OpenAI's API to generate descriptive alt text for images
- **Batch Processing**: Process all images at once or in configurable batches
- **Auto-Batch Mode**: Automatically process images in batches with configurable intervals
- **Progress Tracking**: Real-time progress indicators and statistics
- **Results Viewer**: Dedicated page to view recently generated alt text
- **Image Statistics**: Track progress of alt text coverage across your media library

## Requirements

- WordPress 6.5 or higher
- PHP 7.4 or higher
- OpenAI API key

## Installation

1. Download the plugin files
2. Upload the `cme-alt-text-generator` folder to your `/wp-content/plugins/` directory
3. Activate the plugin through the 'Plugins' menu in WordPress
4. Go to Tools > CME Alt Text Generator to configure your OpenAI API key

## Configuration

1. Navigate to **Tools > CME Alt Text Generator**
2. Enter your OpenAI API key in the configuration section
3. Save the API key

## Usage

### Basic Usage

1. After configuring your API key, you'll see statistics about images without alt text
2. Choose from several processing options:
   - **Generate Alt Text for All Images**: Process your entire media library
   - **Generate Sample (20 images)**: Process a small batch for testing
   - **Start Auto-Batch**: Automatically process 20 images every minute
   - **Generate Alt Text for Individual Images**: Click the button next to any image

### Viewing Results

Navigate to **Tools > CME Alt Text Results** to see:
- Progress summary with statistics
- Recently generated alt text
- Links to edit individual images

## API Configuration

The plugin requires an OpenAI API key to function. You can obtain one from [OpenAI's platform](https://platform.openai.com/api-keys).

## Security

- API keys are stored securely in WordPress options
- All user input is sanitized
- Nonces are used for AJAX requests
- Proper WordPress security practices are followed

## Development

### File Structure

```
cme-alt-text-generator/
├── cme-alt-text-generator.php    # Main plugin file
├── cme-alt-text-results.php      # Results viewer functionality
└── README.md                     # This file
```

### Hooks and Filters

The plugin uses standard WordPress hooks and can be extended through:
- `wp_ajax_generate_alt_text` - AJAX action for generating alt text
- `wp_ajax_get_images_without_alt` - AJAX action for getting image list

## Changelog

### 1.0.0
- Initial release
- OpenAI integration
- Batch processing capabilities
- Results viewer
- Auto-batch functionality

## License

GPL-2.0-or-later

## Support

For support, please contact the Center for Media Engagement.

## Contributing

This plugin is developed by the Center for Media Engagement. For contributions, please contact the development team. 