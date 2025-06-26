<?php
/**
 * Alt Text Generator for WordPress Images
 * 
 * Provides both CLI and admin interface for automatically generating
 * alt text for images using AI services.
 * 
 * @package Engage
 */

namespace Engage\Admin;

use Exception;

class AltTextGenerator {
    
    /**
     * OpenAI API key
     */
    private $api_key;
    
    /**
     * Constructor
     */
    public function __construct() {
        $this->api_key = get_option('engage_alt_text_openai_key', '');
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('wp_ajax_generate_alt_text', array($this, 'ajax_generate_alt_text'));
        add_action('wp_ajax_get_images_without_alt', array($this, 'ajax_get_images_without_alt'));
    }
    
    /**
     * Add admin menu item
     */
    public function add_admin_menu() {
        add_submenu_page(
            'tools.php',
            'Alt Text Generator',
            'Alt Text Generator',
            'manage_options',
            'alt-text-generator',
            array($this, 'admin_page')
        );
    }
    
    /**
     * Admin page HTML
     */
    public function admin_page() {
        if (isset($_POST['save_api_key'])) {
            update_option('engage_alt_text_openai_key', sanitize_text_field($_POST['openai_api_key']));
            $this->api_key = get_option('engage_alt_text_openai_key', '');
            echo '<div class="notice notice-success"><p>API key saved successfully!</p></div>';
        }
        
        $images_without_alt = $this->get_images_without_alt_text();
        ?>
        <div class="wrap">
            <h1>Alt Text Generator</h1>
            
            <div class="card">
                <h2>OpenAI API Configuration</h2>
                <form method="post">
                    <table class="form-table">
                        <tr>
                            <th scope="row">OpenAI API Key</th>
                            <td>
                                <input type="password" name="openai_api_key" value="<?php echo esc_attr($this->api_key); ?>" class="regular-text" />
                                <p class="description">Enter your OpenAI API key to enable AI-powered alt text generation.</p>
                            </td>
                        </tr>
                    </table>
                    <p class="submit">
                        <input type="submit" name="save_api_key" class="button-primary" value="Save API Key" />
                    </p>
                </form>
            </div>
            
            <div class="card">
                <h2>Image Statistics</h2>
                <p><strong>Images without alt text:</strong> <span id="images-count"><?php echo count($images_without_alt); ?></span></p>
                
                <?php if (!empty($this->api_key)): ?>
                    <div class="alt-text-controls">
                        <button id="generate-all-alt-text" class="button button-primary">Generate Alt Text for All Images</button>
                        <button id="generate-sample-alt-text" class="button button-secondary">Generate Sample (35 images)</button>
                        <button id="start-auto-batch" class="button button-primary">Start Auto-Batch (20 every 5 min)</button>
                        <button id="stop-auto-batch" class="button button-secondary">Stop Auto-Batch</button>
                        <div id="progress-container" style="display: none;">
                            <div class="progress-bar">
                                <div id="progress-bar-fill" style="width: 0%; height: 20px; background: #0073aa;"></div>
                            </div>
                            <p id="progress-text">Processing...</p>
                        </div>
                    </div>
                <?php else: ?>
                    <p class="notice notice-warning">Please configure your OpenAI API key to enable alt text generation.</p>
                <?php endif; ?>
            </div>
            
            <div class="card">
                <h2>Recent Images Without Alt Text</h2>
                <div id="images-list">
                    <?php 
                    $sample_images = array_slice($images_without_alt, 0, 10);
                    foreach ($sample_images as $image): 
                        $image_url = wp_get_attachment_image_url($image->ID, 'thumbnail');
                    ?>
                        <div class="image-item" data-id="<?php echo $image->ID; ?>">
                            <img src="<?php echo esc_url($image_url); ?>" alt="" style="width: 100px; height: 100px; object-fit: cover;" />
                            <div class="image-info">
                                <strong><?php echo esc_html($image->post_title); ?></strong>
                                <p>ID: <?php echo $image->ID; ?></p>
                                <button class="button generate-single-alt" data-id="<?php echo $image->ID; ?>">Generate Alt Text</button>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
        
        <style>
        .image-item {
            display: flex;
            align-items: center;
            margin-bottom: 15px;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
        }
        .image-info {
            margin-left: 15px;
        }
        .progress-bar {
            width: 100%;
            height: 20px;
            background: #f0f0f0;
            border-radius: 10px;
            overflow: hidden;
            margin: 10px 0;
        }
        .alt-text-controls {
            margin: 20px 0;
        }
        </style>
        
        <script>
        jQuery(document).ready(function($) {
            $('#generate-all-alt-text').click(function() {
                if (confirm('This will generate alt text for all <?php echo count($images_without_alt); ?> images. Continue?')) {
                    generateAltText('all');
                }
            });
            
            $('#generate-sample-alt-text').click(function() {
                generateAltText('sample');
            });
            
            $('.generate-single-alt').click(function() {
                var imageId = $(this).data('id');
                generateAltText('single', imageId);
            });
            
            function generateAltText(type, imageId = null) {
                $('#progress-container').show();
                $('#progress-bar-fill').css('width', '0%');
                $('#progress-text').text('Starting...');
                
                var data = {
                    action: 'generate_alt_text',
                    type: type,
                    image_id: imageId,
                    nonce: '<?php echo wp_create_nonce('generate_alt_text_nonce'); ?>'
                };
                
                $.post(ajaxurl, data, function(response) {
                    if (response.success) {
                        $('#progress-text').text('Completed! ' + response.data.processed + ' images processed.');
                        $('#progress-bar-fill').css('width', '100%');
                        setTimeout(function() {
                            location.reload();
                        }, 2000);
                    } else {
                        $('#progress-text').text('Error: ' + response.data);
                        $('#progress-container').hide();
                    }
                });
            }
			let autoBatchInterval = null;
            let isAutoBatching = false;

            function startAutoBatching() {
                if (isAutoBatching) return;
                isAutoBatching = true;
                $('#progress-text').text('Auto-batching started...');
                processBatch();
                autoBatchInterval = setInterval(processBatch, 5 * 60 * 1000); // 5 minutes
            }

            function stopAutoBatching() {
                isAutoBatching = false;
                clearInterval(autoBatchInterval);
                $('#progress-text').text('Auto-batching stopped.');
            }

            function processBatch() {
                $('#progress-container').show();
                $('#progress-bar-fill').css('width', '0%');
                $('#progress-text').text('Processing batch...');
                var data = {
                    action: 'generate_alt_text',
                    type: 'sample',
                    image_id: null,
                    nonce: '<?php echo wp_create_nonce('generate_alt_text_nonce'); ?>'
                };
                $.post(ajaxurl, data, function(response) {
                    if (response.success) {
                        $('#progress-text').text('Batch completed! ' + response.data.processed + ' images processed.');
                        $('#progress-bar-fill').css('width', '100%');
                        if (response.data.processed < 20) {
                            stopAutoBatching();
                            $('#progress-text').text('All images processed!');
                        }
                    } else {
                        $('#progress-text').text('Error: ' + response.data);
                        stopAutoBatching();
                    }
                });
            }

            $('#start-auto-batch').click(startAutoBatching);
            $('#stop-auto-batch').click(stopAutoBatching);
        });
        </script>
        <?php
    }
    
    /**
     * Get images without alt text
     */
    public function get_images_without_alt_text() {
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
     * AJAX handler for generating alt text
     */
    public function ajax_generate_alt_text() {
        check_ajax_referer('generate_alt_text_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die('Unauthorized');
        }
        
        $type = sanitize_text_field($_POST['type']);
        $image_id = isset($_POST['image_id']) ? intval($_POST['image_id']) : null;
        
        try {
            switch ($type) {
                case 'all':
                    $processed = $this->process_all_images();
                    break;
                case 'sample':
                    $processed = $this->process_sample_images(20);
                    break;
                case 'single':
                    $processed = $this->process_single_image($image_id);
                    break;
                default:
                    throw new Exception('Invalid type specified');
            }
            
            wp_send_json_success(array(
                'processed' => $processed,
                'message' => "Successfully processed $processed images"
            ));
            
        } catch (Exception $e) {
            wp_send_json_error($e->getMessage());
        }
    }
    
    /**
     * Process all images without alt text
     */
    public function process_all_images() {
        $images = $this->get_images_without_alt_text();
        $processed = 0;
        
        foreach ($images as $image) {
            try {
                $this->generate_and_save_alt_text($image->ID);
                $processed++;
                
                // Add a small delay to avoid overwhelming the API
                usleep(500000); // 0.5 seconds
                
            } catch (Exception $e) {
                error_log("Alt text generation failed for image {$image->ID}: " . $e->getMessage());
                continue;
            }
        }
        
        return $processed;
    }
    
    /**
     * Process a sample of images
     */
    public function process_sample_images($count = 5) {
        $images = $this->get_images_without_alt_text();
        $sample = array_slice($images, 0, $count);
        $processed = 0;
        
        foreach ($sample as $image) {
            try {
                $this->generate_and_save_alt_text($image->ID);
                $processed++;
                usleep(500000);
            } catch (Exception $e) {
                error_log("Alt text generation failed for image {$image->ID}: " . $e->getMessage());
                continue;
            }
        }
        
        return $processed;
    }
    
    /**
     * Process a single image
     */
    public function process_single_image($image_id) {
        $this->generate_and_save_alt_text($image_id);
        return 1;
    }
    
    /**
     * Generate and save alt text for an image
     */
    public function generate_and_save_alt_text($image_id) {
        if (empty($this->api_key)) {
            throw new Exception('OpenAI API key not configured');
        }
        
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
    
    /**
     * Generate alt text using OpenAI API
     */
    public function generate_alt_text_with_ai($image_path) {
        // Convert image to base64
        $image_data = file_get_contents($image_path);
        $base64_image = base64_encode($image_data);
        
        $headers = array(
            'Authorization: Bearer ' . $this->api_key,
            'Content-Type: application/json'
        );
        
        $data = array(
            'model' => 'gpt-4o',
            'messages' => array(
                array(
                    'role' => 'user',
                    'content' => array(
                        array(
                            'type' => 'text',
                            'text' => 'Generate a concise, descriptive alt text for this image. Focus on what is visually important and meaningful. Keep it under 100 characters. Do not include phrases like "image of" or "photo of" - just describe what you see.'
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

// Initialize the class
new AltTextGenerator(); 