<?php
/**
 * WP-CLI Alt Text Generator Command
 * 
 * Usage: wp alt-text generate [--limit=<number>] [--dry-run] [--verbose]
 * 
 * @package Engage
 */

if (!defined('WP_CLI')) {
    return;
}

use WP_CLI;
use Exception;

/**
 * Alt Text Generator WP-CLI Commands
 */
class AltTextCLI {
    
    /**
     * Generate alt text for images without alt text
     *
     * ## OPTIONS
     *
     * [--limit=<number>]
     * : Limit the number of images to process
     *
     * [--dry-run]
     * : Show what would be processed without making changes
     *
     * [--verbose]
     * : Show detailed output
     *
     * [--api-key=<key>]
     * : OpenAI API key (optional, will use stored key if not provided)
     *
     * ## EXAMPLES
     *
     *     # Generate alt text for all images
     *     wp alt-text generate
     *
     *     # Generate alt text for first 10 images
     *     wp alt-text generate --limit=10
     *
     *     # Dry run to see what would be processed
     *     wp alt-text generate --dry-run --verbose
     *
     * @param array $args
     * @param array $assoc_args
     */
    public function generate($args, $assoc_args) {
        $limit = isset($assoc_args['limit']) ? intval($assoc_args['limit']) : null;
        $dry_run = isset($assoc_args['dry-run']);
        $verbose = isset($assoc_args['verbose']);
        $api_key = isset($assoc_args['api-key']) ? $assoc_args['api-key'] : '';
        
        if (empty($api_key)) {
            $api_key = get_option('engage_alt_text_openai_key', '');
        }
        
        if (empty($api_key)) {
            WP_CLI::error('OpenAI API key is required. Use --api-key option or configure it in WordPress admin.');
        }
        
        WP_CLI::log("Starting Alt Text Generator...");
        WP_CLI::log("API Key: " . substr($api_key, 0, 10) . "...");
        WP_CLI::log("Dry Run: " . ($dry_run ? 'Yes' : 'No'));
        WP_CLI::log("Limit: " . ($limit ? $limit : 'All images'));
        WP_CLI::log("");
        
        $images = $this->get_images_without_alt_text();
        $total_images = count($images);
        
        if ($total_images === 0) {
            WP_CLI::success("No images found without alt text!");
            return;
        }
        
        WP_CLI::log("Found {$total_images} images without alt text.");
        
        if ($limit) {
            $images = array_slice($images, 0, $limit);
            WP_CLI::log("Processing first {$limit} images...");
        }
        
        $progress = WP_CLI\Utils\make_progress_bar('Processing images', count($images));
        $processed = 0;
        $errors = 0;
        $start_time = microtime(true);
        
        foreach ($images as $image) {
            try {
                if (!$dry_run) {
                    $alt_text = $this->generate_and_save_alt_text($image->ID, $api_key);
                    if ($verbose) {
                        WP_CLI::log("Generated alt text for {$image->post_title}: \"{$alt_text}\"");
                    }
                } else {
                    if ($verbose) {
                        WP_CLI::log("Would generate alt text for: {$image->post_title}");
                    }
                }
                
                $processed++;
                
                // Add delay to avoid overwhelming the API
                if (!$dry_run) {
                    usleep(500000); // 0.5 seconds
                }
                
            } catch (Exception $e) {
                WP_CLI::warning("Error processing {$image->post_title}: " . $e->getMessage());
                $errors++;
            }
            
            $progress->tick();
        }
        
        $progress->finish();
        
        $end_time = microtime(true);
        $duration = round($end_time - $start_time, 2);
        
        WP_CLI::log("");
        WP_CLI::log("=== Summary ===");
        WP_CLI::log("Total images processed: {$processed}");
        WP_CLI::log("Errors: {$errors}");
        WP_CLI::log("Duration: {$duration} seconds");
        WP_CLI::log("Average time per image: " . round($duration / max(1, $processed), 2) . " seconds");
        
        if ($dry_run) {
            WP_CLI::log("This was a dry run - no changes were made.");
        } else {
            WP_CLI::success("Alt text generation completed!");
        }
    }
    
    /**
     * Get count of images without alt text
     *
     * ## EXAMPLES
     *
     *     wp alt-text count
     *
     * @param array $args
     * @param array $assoc_args
     */
    public function count($args, $assoc_args) {
        $images = $this->get_images_without_alt_text();
        $count = count($images);
        
        WP_CLI::log("Images without alt text: {$count}");
        
        if ($count > 0) {
            WP_CLI::log("");
            WP_CLI::log("Sample images:");
            $sample = array_slice($images, 0, 5);
            foreach ($sample as $image) {
                WP_CLI::log("  - {$image->post_title} (ID: {$image->ID})");
            }
        }
    }
    
    /**
     * Get images without alt text
     */
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
    
    /**
     * Generate and save alt text for an image
     */
    private function generate_and_save_alt_text($image_id, $api_key) {
        $image_path = get_attached_file($image_id);
        if (!$image_path || !file_exists($image_path)) {
            throw new Exception("Image file not found for ID: $image_id");
        }
        
        // Generate alt text using AI
        $alt_text = $this->generate_alt_text_with_ai($image_path, $api_key);
        
        if (empty($alt_text)) {
            throw new Exception("Failed to generate alt text for image: $image_id");
        }
        
        // Save the alt text
        update_post_meta($image_id, '_wp_attachment_image_alt', sanitize_text_field($alt_text));
        
        return $alt_text;
    }
    
    /**
     * Generate alt text using OpenAI API
     */
    private function generate_alt_text_with_ai($image_path, $api_key) {
        // Convert image to base64
        $image_data = file_get_contents($image_path);
        $base64_image = base64_encode($image_data);
        
        $headers = array(
            'Authorization: Bearer ' . $api_key,
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
}

WP_CLI::add_command('alt-text', 'AltTextCLI'); 