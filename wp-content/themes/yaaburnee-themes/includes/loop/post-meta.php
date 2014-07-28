<?php 
	if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly
    //post date, author
    $metaShow = get_post_meta ( $post->ID, THEME_NAME."_show_meta", true );
?>
<?php if($metaShow!="hide") { ?>
    <div class="entry-meta">
        <span class="post-date date updated"><?php echo the_time("F d, Y"); ?></span>
        <?php if ( comments_open() ) { ?>
            <span class="post-comments">
                <a href="<?php the_permalink();?>#comments">
                    <?php comments_number(__('No Comments', THEME_NAME), __('1 Comment', THEME_NAME), __('% Comments', THEME_NAME)); ?>
                </a>
            </span>
        <?php } ?>
        <span class="post-category">
            <?php 
                $postCategories = wp_get_post_categories( $post->ID );
                $catCount = count($postCategories);
                $i=1;
                foreach($postCategories as $c){
                    $cat = get_category( $c );
                    $link = get_category_link($cat->term_id);
                ?>
                    <a href="<?php echo $link;?>"><?php echo $cat->name;?></a><?php if($catCount!=$i) { echo ", "; } ?> 
                <?php
                    $i++;
                }
            ?>
        </span>
        <span class="post-author"><?php echo the_author_posts_link();?></span>

    </div>
<?php } ?>