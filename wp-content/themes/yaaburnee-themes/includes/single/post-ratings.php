<?php 
    $rating = get_post_meta( $post->ID, THEME_NAME."_rating", true );
    $rating = floatval(str_replace(",", ".", $rating));
    $ratingSummary = get_post_meta( $post->ID, THEME_NAME."_rating_summary", true );

    if($rating>=4.75) {
        $rateText = __("Excellent",THEME_NAME);
    } else if($rating<4.75 && $rating>=3.75) {
        $rateText = __("Good",THEME_NAME);
    } else if($rating<3.75 && $rating>=2.75) {
        $rateText = __("Average",THEME_NAME);
    } else if($rating<2.75 && $rating>=1.75) {
        $rateText = __("Fair",THEME_NAME);
    } else if($rating<1.75 && $rating>=0.75) {
        $rateText = __("Poor",THEME_NAME);
    } else if($rating<0.75) {
        $rateText = __("Very Poor",THEME_NAME);
    }

?>
    <?php if($ratingSummary || $rating) { ?>
        <!-- Review block -->
        <div class="review-block"<?php if($rating) { ?> itemscope itemtype="http://data-vocabulary.org/Review"<?php } ?>>
            <div class="rev-box">
                <meta itemprop="itemreviewed" content="<?php the_title(); ?>"/>
                <meta itemprop="reviewer" content="<?php the_author();?>"/>
                <meta itemprop="dtreviewed" content="<?php echo the_time("F d, Y"); ?>"/>
                <div class="rev-score" itemprop="rating"><?php echo $rating;?></div>
                <div class="rev-title"><?php echo $rateText;?></div>
                <?php df_rating_html($rating);?>
            </div>
            <?php if($ratingSummary) { ?>
                <div class="rev-description" itemprop="summary">
                    <p><?php echo stripslashes($ratingSummary);?></p>
                </div>
            <?php } ?>
        </div>
    <?php } ?>
