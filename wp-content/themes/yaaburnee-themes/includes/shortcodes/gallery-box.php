<?php
add_shortcode('df-gallery', 'gallery_handler');
function gallery_handler($atts, $content=null, $code="") {
	if(isset($atts['url'])) {
		if(substr($atts['url'],-1) == '/') {
			$atts['url'] = substr($atts['url'],0,-1);
		}
		$vars = explode('/',$atts['url']);
		$slug = $vars[count($vars)-1];
		$page = get_page_by_path($slug,'OBJECT','gallery-item');
		if(is_object($page)) {
			$id = $page->ID;
			if(is_numeric($id)) {
				$gallery_style = get_post_meta ( $id, THEME_NAME."_gallery_style", true );
				$galleryImages = get_post_meta ( $id, THEME_NAME."_gallery_images", true ); 
				$imageIDs = explode(",",$galleryImages);
				$count = count($imageIDs);
				if($gallery_style=="lightbox") { $classL = 'light-show '; } else { $classL = false; }

				$content.=	'<div class="gallery-preview">';
					$content.=	'<div class="photos">';
	            		$counter=1;
	            		foreach($imageIDs as $imgID) { 
	            			if ($counter==5) break;
	            			if($imgID) {
		            			$file = wp_get_attachment_url($imgID);
		            			$image = get_post_thumb(false, 83, 83, false, $file);
								if($counter==1) { $class=' class="active"'; } else { $class=false; }				
								$content.=	'<div class="img-block">
												<a href="'.$atts['url'].'?page='.$counter.'" class="hover-effect '.$classL.'" data-id="gallery-'.$id.'">
													<img src="'.$image['src'].'" alt="'.$page->post_title.'" title="'.$page->post_title.'" data-id="'.$counter.'"/>
												</a>
											</div>';
									
								$counter++;
							}
						} 

						$content.=	'<a href="'.$atts['url'].'" class="img-block '.$classL.'" data-id="gallery-'.$id.'">
										<span class="icon-text"><i class="fa fa-camera"></i></span>
										<strong>'.__("View all", THEME_NAME).'</strong>
										<strong>'.DF_image_count($id).' '.__("photos", THEME_NAME).'</strong>
									</a>';
					$content.=	'</div>';
					$content.=	'<h2><a href="'.$atts['url'].'" class="'.$classL.'" data-id="gallery-'.$id.'">'.$page->post_title.'</a></h2>';
					if($page->post_excerpt) { 
						$content.=	'<p>'.$page->post_excerpt.'</p>'; 
					} else {
						$content.=	'<p>'.WordLimiter($page->post_content, 30).'</p>'; 
					}

				$content.=	'</div>';

			} else {
				$content.= "Incorrect URL attribute defined";
			}
		} else {
			$content.= "Incorrect URL attribute defined";
		}
		
	} else {
		$content.= "No url attribute defined!";
	
	}
	return $content;
}


?>
