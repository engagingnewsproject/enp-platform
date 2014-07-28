<?php
	if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly
    $source = get_post_meta( $post->ID, THEME_NAME."_source", true ); 
	if($source) { 

        $links = explode('|**|', $source);
        $linksCount = count($links);
?>
    <!-- Source list -->
    <div class="source-list">
        <span><?php _e("Source:", THEME_NAME);?></span>
        <ul>
            <?php 
                $i=1;
                foreach ($links as $link) {
                    $link = explode('|*|', $link); 
                    if($link[0]){
            ?>
                <li>
                    <?php if($link[1]) { ?>
                        <a href="<?php echo $link[1];?>" target="_blank">
                    <?php } ?>
                        <?php echo $link[0];?>
                    <?php if($link[1]) { ?>
                        </a>
                    <?php } ?>
                    <?php if($linksCount!=$i) { echo ","; } ?>
                </li>
            <?php 
                    $i++;
                    } 
                }
            ?>
        </ul>
    </div>
<?php } ?>
