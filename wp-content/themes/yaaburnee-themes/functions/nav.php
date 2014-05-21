<?php
function custom_nav_btn_links($search=0, $page_num) {
	$pageURL = 'http://';
	$pageURL .= $_SERVER["SERVER_NAME"].$_SERVER["REQUEST_URI"];
	if ($search == "") {
	$pos = strpos($pageURL,"/page/");
	$len = strlen($pageURL);
		if($pos > 0) {
			$pos = strpos($pageURL,"/page/");
			$pageURL = substr($pageURL, 0, $pos);
			return htmlentities($pageURL."/page/".$page_num);
		}
		if (substr($pageURL,$len-1) == "/") return htmlentities($pageURL."page/".$page_num);
		else return htmlentities($pageURL."/page/".$page_num);
	}
	else {
		$pos = strpos($pageURL,"&paged=");
		$len = strlen($pageURL);
		if($pos > 0) {
			$pos = strpos($pageURL,"&paged=");
			$pageURL = substr($pageURL, 0, $pos);
			return htmlentities($pageURL."&paged=".$page_num);
		}
		if (substr($pageURL,$len-1) == "/") return htmlentities($pageURL."&paged=".$page_num);
		else return htmlentities($pageURL."&paged=".$page_num);
	}
}

/* -------------------------------------------------------------------------*
 * 								BLOG PAGE BUTTONS							*
 * -------------------------------------------------------------------------*/
 
function customized_nav_btns($page_num,$max_num_pages,$search=0) {
		global $wp_query;
		$big = 999999999; // need an unlikely integer
		$args = array(
			'base' 			=> str_replace( $big, '%#%', esc_url( get_pagenum_link( $big ) ) ),
			'format'       	=> '?page=%#%',
			'total'       	=> $max_num_pages,
			'current'      	=> max( 1, $page_num ),
			'show_all'     	=> false,
			'end_size'    	=> 1,
			'mid_size'     	=> 2,
			'prev_next'    	=> true,
			'prev_text'    	=> __('PREV', THEME_NAME),
			'next_text'    	=> __('NEXT', THEME_NAME),
			'type'         	=> 'list',
			'add_args'     	=> false,
			'add_fragment' 	=> ''
		);
		
?>

        <?php echo paginate_links($args);?>

<?php
}

/* -------------------------------------------------------------------------*
 * 								GALLERY PAGE BUTTONS						*
 * -------------------------------------------------------------------------*/
 
function gallery_nav_btns($page_num,$max_num_pages,$search=0) {

	if($page_num == '' && $page_num == 0){ $page_num = '1'; }
	
	
		?>
		<div class="gallery-navi">
					
				<?php if($page_num>1) { ?>
						<?php
							if($page_num < 4 OR $max_num_pages < 8) {
								$start = 1;
								if($max_num_pages >= 7 ) {$end = 7;}
								else $end = $max_num_pages;
							}
							else {
								if($page_num + 3 > $max_num_pages) {
									$end = $max_num_pages;
									$start = $end - 7;
								}
								else {
									$start = $page_num - 3;
									$end = $page_num + 3;
								}
							}
							
							for($i = $start; $i <= $end; $i++) {
								?><!--<a <?php if($i == $page_num) {?> class="active" <?php } else { ?> class="default" <?php } ?> href="<?php echo custom_nav_btn_links($search, $i); ?>"><span><?php echo $i;?></span></a>--><?php
							}	
						?>
						
						<a href="<?php if ($page_num < $max_num_pages) {$new_page = $page_num + 1;} else {$new_page = $page_num;} echo custom_nav_btn_links($search, $new_page);?>" class="next"> </a>
						<!--<a href="<?php if ($page_num > 1) { $new_page = $page_num - 1;} else {$new_page = 1;} echo custom_nav_btn_links($search, $new_page); ?>" class="prev"><?php printf ( __( 'Previous' , THEME_NAME ));?></a>-->
				<?php } else { ?>
						<a href="<?php echo custom_nav_btn_links($search, 2);?>" class="next"> </a>
				<?php } ?>
		</div>
		<?php
	
}
?>