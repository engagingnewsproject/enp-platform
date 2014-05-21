<?php

	wp_reset_query();

	global $query_string;
	$query_vars = explode('&',$query_string);
									
	foreach($query_vars as $key) {
		if(strpos($key,'page=') !== false) {
			$i = substr($key,8,12);
			break;
		}
	}
	
	if(!isset($i)) {
		$i = 1;
	}

	$galleryImages = get_post_meta ( $post->ID, THEME_NAME."_gallery_images", true ); 
	$imageIDs = explode(",",$galleryImages);
	$count = count($imageIDs);

	//main image
	$file = wp_get_attachment_url($imageIDs[$i-1]);
	$image = get_post_thumb(false, 1200, 0, false, $file);
?>
<?php get_template_part(THEME_LOOP."loop","start"); ?> 
    <?php if (have_posts()) : ?>
    	<!-- Blank page container -->
        <div class="blank-page-container df-slide-item" id="<?php echo $post->ID;?>">
        	<span class="next-image" data-next="<?php echo $i+1;?>"></span>
        	<?php get_template_part(THEME_SINGLE."page-title"); ?> 
            <!-- Stack photo -->
            <span class="gal-current-image">
				<div class="photo-stack loading waiter">
	                <img class="image-big-gallery df-gallery-image" data-id="<?php echo $i;?>" style="display:none;" src="<?php echo $image['src'];?>" alt="<?php the_title();?>"/>
	                <a href="javascript:void(0);" class="prev" rel="<?php if($i>1) { echo $i-1; } else { echo $i-1; } ?>">&#xf104;</a>
	                <a href="javascript:void(0);" class="next" rel="<?php if($i<$count) { echo $i+1; } else { echo $i; } ?>">&#xf105;</a>
	            </div>
	        </span>
            <!-- Thumbnails -->
            <div class="photo-stack-thumbnails">
            	<?php 
            		$c=1;
            		foreach($imageIDs as $id) { 
            			if($id) {
	            			$file = wp_get_attachment_url($id);
	            			$image = get_post_thumb(false, 60, 60, false, $file);
	            	?>
	                		<a href="javascript:;" rel="<?php echo $c;?>" class="gal-thumbs<?php if($c==$i) { ?> active<?php } ?>" data-nr="<?php echo $c;?>">
	                			<img src="<?php echo $image['src'];?>" alt="<?php the_title();?>"/>
	                		</a>
	                <?php $c++; ?>
               	 	<?php } ?>
                <?php } ?>
            </div>
            <div class="post-title">
	            <h1><?php the_title();?></h1>
        	</div>
            <div class="post-content">
				<?php 
					if (get_the_content() != "") { 				
						add_filter('the_content','remove_images');
						add_filter('the_content','remove_objects');
						the_content();
					} 
				?>

			</div>
		</div>
	<?php else: ?>
		<p><?php  _e('Sorry, no posts matched your criteria.' , THEME_NAME ); ?></p>
	<?php endif; ?>
<?php get_template_part(THEME_LOOP."loop","end"); ?> 

  