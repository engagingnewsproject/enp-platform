 <?php
    if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly
    $posttags = get_the_tags();
?>
<?php if ($posttags) { ?>
    <!-- Tag list -->
    <div class="tag-list">
        <span><?php _e("Tagged", THEME_NAME);?></span>
        <ul>
            <?php   
                foreach($posttags as $tag) {
                    echo '<li><a href="'.get_tag_link($tag->term_id).'">'.$tag->name . '</a></li>'; 
                }
            ?>
            <div class="clear"></div>
        </ul>
    </div>
    <div class="clear"></div>
<?php } ?>