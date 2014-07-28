    <?php if(df_page_id()==get_option('page_for_posts') || is_category() || is_tax() || is_archive() || is_search() || isset($_REQUEST['s'])) { ?>
        <!-- Category block news -->
        </div>
    <?php } ?>   
	</div>
	<?php get_template_part(THEME_INCLUDES."sidebar"); ?> 
</div>