<?php

	define('WP_USE_THEMES', false);
	require( '../../../wp-blog-header.php' );

	if ( $webdados_fb = webdados_fb_run() ) {

		if ( isset($_GET['img']) && trim($_GET['img'])!='' ) {
			if ( $url=parse_url(urldecode(trim($_GET['img']))) ) {
				if ( $url['host']==$_SERVER['HTTP_HOST'] ) {
		
					if( $image=imagecreatefromfile($_SERVER['DOCUMENT_ROOT'].$url['path']) ) {
	
						$thumb_width = intval(WEBDADOS_FB_W);
						$thumb_height = intval(WEBDADOS_FB_H);
						
						$width = imagesx($image);
						$height = imagesy($image);
						
						$original_aspect = $width / $height;
						$thumb_aspect = $thumb_width / $thumb_height;
						
						if ( $original_aspect >= $thumb_aspect )
						{
						   // If image is wider than thumbnail (in aspect ratio sense)
						   $new_height = $thumb_height;
						   $new_width = $width / ($height / $thumb_height);
						}
						else
						{
						   // If the thumbnail is wider than the image
						   $new_width = $thumb_width;
						   $new_height = $height / ($width / $thumb_width);
						}
						
						$thumb = imagecreatetruecolor( $thumb_width, $thumb_height );
						//Fill with white because the source image can be a transparent PNG
						$thumb_fill_color = apply_filters('fb_og_thumb_fill_color', array(255, 255, 255) );
						imagefill($thumb, 0, 0, imagecolorallocate ( $thumb , $thumb_fill_color[0] , $thumb_fill_color[1] , $thumb_fill_color[2] ) );
						
						// Resize and crop
						imagecopyresampled($thumb,
						                   $image,
						                   0 - ($new_width - $thumb_width) / 2, // Center the image horizontally
						                   0 - ($new_height - $thumb_height) / 2, // Center the image vertically
						                   0, 0,
						                   $new_width, $new_height,
						                   $width, $height);
						//Barra
						if ( trim($webdados_fb->options['fb_image_overlay_image'])!='' ) {
							$barra_url = parse_url( apply_filters( 'fb_og_thumb_image', trim($webdados_fb->options['fb_image_overlay_image']), intval($_GET['post_id']) ) );
							$barra = imagecreatefromfile($_SERVER['DOCUMENT_ROOT'].$barra_url['path']);
							imagecopy($thumb, $barra, 0, 0, 0, 0, intval(WEBDADOS_FB_W), intval(WEBDADOS_FB_H) );
						}
	
						@header('HTTP/1.0 200 OK');
						switch( apply_filters( 'fb_og_overlayed_image_format', 'jpg' ) ) {
							case 'png':
								header('Content-Type: image/png');
								imagepng($thumb);
								break;
							case 'jpg':
							default:
								header('Content-Type: image/jpeg');
								imagejpeg($thumb, NULL, 95);
								break;
						}
						imagedestroy($image);
						imagedestroy($thumb);
						imagedestroy($barra);
					} else {

					}
		
				}
			}
		}
	}



	function imagecreatefromfile( $filename ) {
		try {
	    	if (!file_exists($filename)) {
	    	    throw new InvalidArgumentException('File "'.htmlentities($filename).'" not found.');
	    	}
	    	switch ( strtolower( pathinfo( $filename, PATHINFO_EXTENSION ))) {
	    	    case 'jpeg':
	    	    case 'jpg':
	    	        return imagecreatefromjpeg($filename);
	    	    break;
		
	    	    case 'png':
	    	        return imagecreatefrompng($filename);
	    	    break;
		
	    	    case 'gif':
	    	        return imagecreatefromgif($filename);
	    	    break;
		
	    	    default:
	    	        throw new InvalidArgumentException('File "'.htmlentities($filename).'" is not valid jpg, png or gif image.');
	    	    break;
	    	}
	    } catch (Exception $e) {
    		die( 'Caught exception: '.  $e->getMessage() );
    		return false;
		}
	}