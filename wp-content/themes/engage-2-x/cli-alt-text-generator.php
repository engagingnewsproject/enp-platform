<?php
/**
 * CLI Alt Text Generator for WordPress Images
 * 
 * Command line script to automatically generate alt text for images
 * using OpenAI's GPT-4 Vision API.
 * 
 * Usage: php cli-alt-text-generator.php [options]
 * 
 * Options:
 *   --api-key=KEY     OpenAI API key
 *   --limit=N         Limit processing to N images (default: all)
 *   --dry-run         Show what would be processed without making changes
 *   --verbose         Show detailed output
 *   --help            Show this help message
 * 
 * @package Engage
 */

// Prevent direct web access
if (php_sapi_name() !== 'cli') {
    die('This script can only be run from the command line.');
}

// Load WordPress
require_once __DIR__ . '/public/wp-config.php';
require_once __DIR__ . '/vendor/autoload.php';

use Exception;

class CLIAltTextGenerator {
    
    private $api_key;
    private $verbose;
    private $dry_run;
    private $limit;
    private $processed = 0;
    private $errors = 0;
    
    public function __construct($options = array()) {
        $this->api_key = $options['api_key'] ?? '';
        $this->verbose = $options['verbose'] ?? false;
        $this->dry_run = $options['dry_run'] ?? false;
        $this->limit = $options['limit'] ?? null;
        
        if (empty($this->api_key)) {
            $this->api_key = get_option('engage_alt_text_openai_key', '');
        }
        
        if (empty($this->api_key)) {
            throw new Exception('OpenAI API key is required. Use --api-key option or configure it in WordPress admin.');
        }
    }
    
    public function run() {
        $this->log("Starting Alt Text Generator...");
        $this->log("API Key: " . substr($this->api_key, 0, 10) . "...");
        $this->log("Dry Run: " . ($this->dry_run ? 'Yes' : 'No'));
        $this->log("Limit: " . ($this->limit ? $this->limit : 'All images'));
        $this->log("");
        
        $images = $this->get_images_without_alt_text();
        $total_images = count($images);
        
        if ($total_images === 0) {
            $this->log("No images found without alt text!");
            return;
        }
        
        $this->log("Found {$total_images} images without alt text.");
        
        if ($this->limit) {
            $images = array_slice($images, 0, $this->limit);
            $this->log("Processing first {$this->limit} images...");
        }
        
        $start_time = microtime(true);
        
        foreach ($images as $index => $image) {
            $this->log("Processing image " . ($index + 1) . "/" . count($images) . ": {$image->post_title} (ID: {$image->ID})");
            
            try {
                if (!$this->dry_run) {
                    $alt_text = $this->generate_and_save_alt_text($image->ID);
                    $this->log("  ✓ Generated: \"{$alt_text}\"");
                } else {
                    $this->log("  [DRY RUN] Would generate alt text for this image");
                }
                
                $this->processed++;
                
                // Add delay to avoid overwhelming the API
                if (!$this->dry_run) {
                    usleep(500000); // 0.5 seconds
                }
                
            } catch (Exception $e) {
                $this->log("  ✗ Error: " . $e->getMessage());
                $this->errors++;
            }
            
            // Show progress every 10 images
            if (($index + 1) % 10 === 0) {
                $this->log("Progress: " . ($index + 1) . "/" . count($images) . " images processed");
            }
        }
        
        $end_time = microtime(true);
        $duration = round($end_time - $start_time, 2);
        
        $this->log("");
        $this->log("=== Summary ===");
        $this->log("Total images processed: {$this->processed}");
        $this->log("Errors: {$this->errors}");
        $this->log("Duration: {$duration} seconds");
        $this->log("Average time per image: " . round($duration / max(1, $this->processed), 2) . " seconds");
        
        if ($this->dry_run) {
            $this->log("This was a dry run - no changes were made.");
        }
    }
    
    private function get_images_without_alt_text() {
        global $wpdb;
        
        $query = "
            SELECT p.ID, p.post_title, p.post_name
            FROM {$wpdb->posts} p
            LEFT JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id AND pm.meta_key = '_wp_attachment_image_alt'
            WHERE p.post_type = 'attachment' 
            AND p.post_mime_type LIKE 'image/%'
            AND (pm.meta_value IS NULL OR pm.meta_value = '')
            ORDER BY p.post_date DESC
        ";
        
        return $wpdb->get_results($query);
    }
    
    private function generate_and_save_alt_text($image_id) {
        $image_path = get_attached_file($image_id);
        if (!$image_path || !file_exists($image_path)) {
            throw new Exception("Image file not found for ID: $image_id");
        }
        
        // Generate alt text using AI
        $alt_text = $this->generate_alt_text_with_ai($image_path);
        
        if (empty($alt_text)) {
            throw new Exception("Failed to generate alt text for image: $image_id");
        }
        
        // Save the alt text
        update_post_meta($image_id, '_wp_attachment_image_alt', sanitize_text_field($alt_text));
        
        return $alt_text;
    }
    
    private function generate_alt_text_with_ai($image_path) {
        // Convert image to base64
        $image_data = file_get_contents($image_path);
        $base64_image = base64_encode($image_data);
        
        $headers = array(
            'Authorization: Bearer ' . $this->api_key,
            'Content-Type: application/json'
        );
        
        $data = array(
            'model' => 'gpt-4-vision-preview',
            'messages' => array(
                array(
                    'role' => 'user',
                    'content' => array(
                        array(
                            'type' => 'text',
                            'text' => 'Generate a concise, descriptive alt text for this image. Focus on what is visually important and meaningful. Keep it under 125 characters. Do not include phrases like "image of" or "photo of" - just describe what you see.'
                        ),
                        array(
                            'type' => 'image_url',
                            'image_url' => array(
                                'url' => 'data:image/jpeg;base64,' . $base64_image
                            )
                        )
                    )
                )
            ),
            'max_tokens' => 150
        );
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, 'https://api.openai.com/v1/chat/completions');
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        
        $response = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($http_code !== 200) {
            throw new Exception("OpenAI API error: HTTP $http_code - $response");
        }
        
        $result = json_decode($response, true);
        
        if (!isset($result['choices'][0]['message']['content'])) {
            throw new Exception("Invalid response from OpenAI API");
        }
        
        return trim($result['choices'][0]['message']['content']);
    }
    
    private function log($message) {
        if ($this->verbose || strpos($message, 'Error') !== false || strpos($message, 'Summary') !== false) {
            echo $message . PHP_EOL;
        }
    }
}

// Parse command line arguments
function parse_args($argv) {
    $options = array();
    
    foreach ($argv as $arg) {
        if (strpos($arg, '--') === 0) {
            $parts = explode('=', $arg, 2);
            $key = substr($parts[0], 2);
            $value = isset($parts[1]) ? $parts[1] : true;
            
            switch ($key) {
                case 'api-key':
                    $options['api_key'] = $value;
                    break;
                case 'limit':
                    $options['limit'] = intval($value);
                    break;
                case 'dry-run':
                    $options['dry_run'] = true;
                    break;
                case 'verbose':
                    $options['verbose'] = true;
                    break;
                case 'help':
                    show_help();
                    exit(0);
                    break;
            }
        }
    }
    
    return $options;
}

function show_help() {
    echo "CLI Alt Text Generator for WordPress Images\n\n";
    echo "Usage: php cli-alt-text-generator.php [options]\n\n";
    echo "Options:\n";
    echo "  --api-key=KEY     OpenAI API key\n";
    echo "  --limit=N         Limit processing to N images (default: all)\n";
    echo "  --dry-run         Show what would be processed without making changes\n";
    echo "  --verbose         Show detailed output\n";
    echo "  --help            Show this help message\n\n";
    echo "Examples:\n";
    echo "  php cli-alt-text-generator.php --api-key=sk-... --limit=10 --dry-run\n";
    echo "  php cli-alt-text-generator.php --verbose\n";
}

// Main execution
try {
    $options = parse_args($argv);
    $generator = new CLIAltTextGenerator($options);
    $generator->run();
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . PHP_EOL;
    exit(1);
} 