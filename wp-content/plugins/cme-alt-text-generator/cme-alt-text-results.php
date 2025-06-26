<?php
/**
 * CME Alt Text Results Viewer
 * 
 * Admin page to view recently generated alt text
 * 
 * @package CME\AltTextGenerator
 */

namespace CME\AltTextGenerator;

class AltTextResults {
    
    public function __construct() {
        add_action('admin_menu', array($this, 'add_admin_menu'));
    }
    
    public function add_admin_menu() {
        add_submenu_page(
            'tools.php',
            'CME Alt Text Results',
            'CME Alt Text Results',
            'manage_options',
            'alt-text-results',
            array($this, 'admin_page')
        );
    }
    
    public function admin_page() {
        global $wpdb;
        
        // Get recently processed images
        $query = "
            SELECT 
                p.ID,
                p.post_title,
                p.post_date,
                p.post_modified,
                pm.meta_value as alt_text
            FROM {$wpdb->posts} p
            INNER JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id 
            WHERE p.post_type = 'attachment' 
            AND p.post_mime_type LIKE 'image/%'
            AND pm.meta_key = '_wp_attachment_image_alt'
            AND pm.meta_value != ''
            ORDER BY p.post_modified DESC
            LIMIT 50
        ";
        
        $results = $wpdb->get_results($query);
        
        // Get statistics
        $query_no_alt = "
            SELECT COUNT(*) as count
            FROM {$wpdb->posts} p
            LEFT JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id AND pm.meta_key = '_wp_attachment_image_alt'
            WHERE p.post_type = 'attachment' 
            AND p.post_mime_type LIKE 'image/%'
            AND (pm.meta_value IS NULL OR pm.meta_value = '')
        ";
        
        $count_result = $wpdb->get_row($query_no_alt);
        
        $query_total = "
            SELECT COUNT(*) as count
            FROM {$wpdb->posts} p
            WHERE p.post_type = 'attachment' 
            AND p.post_mime_type LIKE 'image/%'
        ";
        
        $total_result = $wpdb->get_row($query_total);
        $with_alt = $total_result->count - $count_result->count;
        $progress = round(($with_alt / $total_result->count) * 100, 1);
        
        ?>
        <div class="wrap">
            <h1>CME Alt Text Generation Results</h1>
            
            <div class="card">
                <h2>Progress Summary</h2>
                <div class="progress-summary">
                    <div class="progress-item">
                        <strong>Total Images:</strong> <?php echo number_format($total_result->count); ?>
                    </div>
                    <div class="progress-item">
                        <strong>With Alt Text:</strong> <?php echo number_format($with_alt); ?>
                    </div>
                    <div class="progress-item">
                        <strong>Without Alt Text:</strong> <?php echo number_format($count_result->count); ?>
                    </div>
                    <div class="progress-item">
                        <strong>Progress:</strong> <?php echo $progress; ?>%
                    </div>
                </div>
                
                <div class="progress-bar-container">
                    <div class="progress-bar">
                        <div class="progress-fill" style="width: <?php echo $progress; ?>%;"></div>
                    </div>
                </div>
            </div>
            
            <div class="card">
                <h2>Recently Generated Alt Text</h2>
                
                <?php if (empty($results)): ?>
                    <p>No images with alt text found.</p>
                <?php else: ?>
                    <div class="alt-text-results">
                        <?php foreach ($results as $image): 
                            $image_url = wp_get_attachment_image_url($image->ID, 'thumbnail');
                            $edit_url = get_edit_post_link($image->ID);
                        ?>
                            <div class="result-item">
                                <div class="image-preview">
                                    <img src="<?php echo esc_url($image_url); ?>" alt="<?php echo esc_attr($image->alt_text); ?>" />
                                </div>
                                <div class="image-details">
                                    <h3><?php echo esc_html($image->post_title); ?></h3>
                                    <p><strong>ID:</strong> <?php echo $image->ID; ?></p>
                                    <p><strong>Generated:</strong> <?php echo date('M j, Y g:i A', strtotime($image->post_modified)); ?></p>
                                    <p><strong>Alt Text:</strong> "<?php echo esc_html($image->alt_text); ?>"</p>
                                    <p><a href="<?php echo esc_url($edit_url); ?>" class="button button-small">Edit Image</a></p>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
            
            <div class="card">
                <h2>Quick Actions</h2>
                <p>
                    <a href="<?php echo admin_url('tools.php?page=alt-text-generator'); ?>" class="button button-primary">Back to Alt Text Generator</a>
                    <a href="<?php echo admin_url('upload.php'); ?>" class="button">View All Media</a>
                </p>
            </div>
        </div>
        
        <style>
        .progress-summary {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin: 20px 0;
        }
        .progress-item {
            padding: 10px;
            background: #f9f9f9;
            border-radius: 4px;
        }
        .progress-bar-container {
            margin: 20px 0;
        }
        .progress-bar {
            width: 100%;
            height: 20px;
            background: #f0f0f0;
            border-radius: 10px;
            overflow: hidden;
        }
        .progress-fill {
            height: 100%;
            background: linear-gradient(90deg, #0073aa, #00a0d2);
            transition: width 0.3s ease;
        }
        .alt-text-results {
            max-height: 600px;
            overflow-y: auto;
        }
        .result-item {
            display: flex;
            align-items: flex-start;
            margin-bottom: 20px;
            padding: 15px;
            border: 1px solid #ddd;
            border-radius: 4px;
            background: #fff;
        }
        .image-preview {
            margin-right: 15px;
        }
        .image-preview img {
            width: 100px;
            height: 100px;
            object-fit: cover;
            border-radius: 4px;
        }
        .image-details {
            flex: 1;
        }
        .image-details h3 {
            margin-top: 0;
            margin-bottom: 10px;
        }
        .image-details p {
            margin: 5px 0;
        }
        </style>
        <?php
    }
} 