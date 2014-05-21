<?php
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly
/* -------------------------------------------------------------------------*
 * 							CATEGORY CUSTOM FIELD							*
 * -------------------------------------------------------------------------*/
  	$prefix = THEME_NAME.'_';
  	/* 
   	* configure your meta box
   	*/
  	$config = array(
    	'id'             => THEME_NAME.'_blog_style',          // meta box id, unique per meta box
    	'title'          => 'Category Style',          // meta box title
    	'pages'          => array('category'),      // taxonomy name, accept categories, post_tag and custom taxonomies
    	'context'        => 'normal',            // where the meta box appear: normal (default), advanced, side; optional
    	'priority'       => 'high',            // order of meta box: high (default), low; optional
    	'fields'         => array(),            // list of meta fields (can be added by field arrays)
    	'local_images'   => false,          // Use local or hosted images (meta box images for add/remove)
    	'use_with_theme' => THEME_FUNCTIONS_URL."tax-meta-class/"         //change path if used with theme set to true, false for a plugin or anything else for a custom path(default false).
  	);


	$sidebar_names = get_option( THEME_NAME."_sidebar_names" );
	$sidebar_names = explode( "|*|", $sidebar_names );
	$sidebars = array();
	$sidebars['default'] = 'Default';
	$sidebars['DFoff'] = 'No Sidebar';

	if(!empty($sidebar_names)) {
		foreach ($sidebar_names as $sidebar) {
			if($sidebar!="") {
				$sidebars[strtolower($sidebar)] = $sidebar;
			}
		}
	}	



  	$my_meta =  new Tax_Meta_Class($config);
  	$my_meta->addColor(THEME_NAME.'_title_color',array('name'=> 'Category Color'));
	$my_meta->addSelect($prefix.'blog_style',array('1'=> __('Large Images', THEME_NAME),'2'=>__('Small Images', THEME_NAME),'3'=>__('Grid Layout', THEME_NAME)),array('name'=> __('Category Style ','category'), 'std'=> array('1')));
	$my_meta->addSelect($prefix.'breaking_by_cat',array('yes'=> __('Yes', THEME_NAME),'no'=>__('No', THEME_NAME)),array('name'=> __('Show Breaking News Only From This Category ','category'), 'std'=> array('yes')));
	$my_meta->addSelect(THEME_NAME.'_sidebar_position',array('right'=>'Right','left'=>'Left'),array('name'=> __('Main Sidebar Position ','tax-meta'), 'std'=> array('right')));
	$my_meta->addSelect(THEME_NAME.'_sidebar_select', $sidebars ,array('name'=> __('Main Sidebar','tax-meta'), 'std'=> array('default')));


	$my_meta->Finish();


/* -------------------------------------------------------------------------*
 * 								GET OPTION									*
 * -------------------------------------------------------------------------*/
 
function df_get_option($id, $type, $echo=false) {
	$my_meta = new Tax_Meta_Class('');
	$value = $my_meta->get_tax_meta($id, THEME_NAME.'_'.$type);
	$my_meta->Finish();

	if($echo!=false) {
		echo $value;
	} else {
		return $value;
	}
}


/* -------------------------------------------------------------------------*
 * 							MAIN NAV MENU WALKER							*
 * -------------------------------------------------------------------------*/

class DF_Walker extends Walker_Nav_Menu {
    function start_el(&$output, $item, $depth = 0, $args = array(), $current_object_id = 0) {
        global $wp_query;
		$my_meta = new Tax_Meta_Class('');
        $indent = ( $depth ) ? str_repeat( "\t", $depth ) : '';
        


        $class_names = $value = '';

        $classes = empty( $item->classes ) ? array() : (array) $item->classes;

        $class_names = join( ' ', apply_filters( 'nav_menu_css_class', array_filter( $classes ), $item ) );
        $class_names = ' class="' . esc_attr( $class_names ).'"';

		
        $output .= $indent . '<li id="menu-item-'. $item->ID . '"'.$value . $class_names .'>';
        if($depth==0) {
	        if($item->object=="category") {
				$titleColor = $my_meta->get_tax_meta($item->object_id, THEME_NAME.'_title_color');
			}
			if($item->object=="page") {
				$titleColor = "#".df_meta($item->object_id, THEME_NAME."_title_color"); 	
			}

			if(!isset($titleColor) || $titleColor=="#" || $titleColor=="") $titleColor = "#".get_option(THEME_NAME."_default_cat_color"); 

		    $output .= '<span style="background-color: '.$titleColor.'"></span>';	
        }
        $attributes  = ! empty( $item->attr_title ) ? ' title="'  . esc_attr( $item->attr_title ) .'"' : '';
        $attributes .= ! empty( $item->target )     ? ' target="' . esc_attr( $item->target     ) .'"' : '';
        $attributes .= ! empty( $item->xfn )        ? ' rel="'    . esc_attr( $item->xfn        ) .'"' : '';
        $attributes .= ! empty( $item->url )        ? ' href="'   . esc_attr( $item->url        ) .'"' : '';

        //$attributes .= ' data-id="'. esc_attr( $item->object_id        ) .'"';
        //$attributes .= ' data-slug="'. esc_attr(  basename(get_permalink($item->object_id )) ) .'"';

        $item_output = $args->before;
        $item_output .= '<a'. $attributes .'>';

        $item_output .= $args->link_before . apply_filters( 'the_title', $item->title, $item->ID ) . $args->link_after;



        $item_output .= '</a>';

        $item_output .= $args->after;

        $output .= apply_filters( 'walker_nav_menu_start_el', $item_output, $item, $depth, $args );
		$my_meta->Finish();



    }
}

/* -------------------------------------------------------------------------*
 * 							WEATHER FORECAST								*
 * -------------------------------------------------------------------------*/
 
function DF_weather_forecast($ip) {
	// get the location type ip or city
	$locationType = get_option(THEME_NAME."_weather_location_type");
	if($locationType == "custom") {
		$whitelist = array();
	} else {
		$whitelist = array('localhost', '127.0.0.1');
	}

	//api key
	$weather_api = get_option(THEME_NAME."_weather_api");
	// api key type, premium or free
	$weather_api_key_type = get_option(THEME_NAME."_weather_api_key_type");

	//if api key set
	if($weather_api) {
		// check the ip, it's not localhost
		if(!in_array($_SERVER['HTTP_HOST'], $whitelist)){
			if($locationType == "custom") {
				$result = true;
			} else {
				//get nearest city by ip
				$url = "http://www.geoplugin.net/json.gp?ip=".$ip;
				$result = json_response($url);
			}

			if($result!=false) {
				// if we use a custom city name
				if($locationType == "custom") {
					$city = false;
					$country = false;
					//get wp cache
					$weatherResult = get_transient('weather_result_'.urlencode($ip));
				// if we use nearest city by ip
				} else {
					//get wp cache
					$city = $result->geoplugin_city;
					$country = $result->geoplugin_countryName;
					$weatherResult = get_transient('weather_result_'.urlencode($city).'_'.urlencode($country));
				}

				//if there is no cache set
				if($weatherResult==false) {
					// get temperature type C or F
					$temperature = get_option(THEME_NAME."_temperature");
					
					//if we have a city
					if($city) {
						//premium api request
						if($weather_api_key_type=="premium") {
							$url = "http://api.worldweatheronline.com/premium/v1/weather.ashx?key=".$weather_api."&q=".urlencode($city).",".urlencode($country)."&num_of_days=1&includeLocation=yes&date=today&format=json";
						//free api request
						} else {
							$url = "http://api.worldweatheronline.com/free/v1/weather.ashx?key=".$weather_api."&q=".urlencode($city).",".urlencode($country)."&num_of_days=1&includeLocation=yes&date=today&format=json";
						}	

						$result = json_response($url);
					//if we have just a IP and haven't find a nearest city
					} else {
						//premium api request
						if($weather_api_key_type=="premium") {
							$url = "http://api.worldweatheronline.com/premium/v1/weather.ashx?key=".$weather_api."&q=".$ip."&num_of_days=1&includeLocation=yes&date=today&format=json";
						//free api request
						} else {
							$url = "http://api.worldweatheronline.com/free/v1/weather.ashx?key=".$weather_api."&q=".$ip."&num_of_days=1&includeLocation=yes&date=today&format=json";
						}
						//get the results
						$result = json_response($url);
					}
					
					//if we have the results, set up them for output in array
					if($result!=false) {
						$weather = array();

			
						$weather['temp_F'] = $result->data->current_condition[0]->temp_F;	
						$weather['temp_C'] = $result->data->current_condition[0]->temp_C;
						
						// add + before 
						$weather['temp_F'] = intval($weather['temp_F']);
						if($weather['temp_F']>0) {
							$weather['temp_F'] = "+".$weather['temp_F'];
						} else {
							$weather['temp_F'];
						}				

						// add + before 
						$weather['temp_C'] = intval($weather['temp_C']);
						if($weather['temp_C']>0) {
							$weather['temp_C'] = "+".$weather['temp_C'];
						} else {
							$weather['temp_C'];
						}

						$weather['temp_F'] = $weather['temp_F'].'&deg;F';
						$weather['temp_C'] = $weather['temp_C'].'&deg;C';

						$weatherCode = $result->data->current_condition[0]->weatherCode;
						$weather['city'] = $result->data->nearest_area[0]->areaName[0]->value;
						$weather['country'] = $result->data->nearest_area[0]->country[0]->value;
						//$weather['weatherDesc'] = $result->data->weather[0]->weatherDesc[0]->value;

						//add the weather images for the output codes
						switch ($weatherCode) {
							case '395':
								$weather['image'] = "weather-snow";
								$weather['weatherDesc'] = __('Moderate or heavy snow in area with thunder', THEME_NAME);
								break;
							case '392':
								$weather['image'] = "weather-snow";
								$weather['weatherDesc'] = __('Patchy light snow in area with thunder', THEME_NAME);
								break;
							case '371':
								$weather['image'] = "weather-snow";
								$weather['weatherDesc'] = __('Moderate or heavy snow showers', THEME_NAME);
								break;
							case '368':
								$weather['image'] = "weather-snow";
								$weather['weatherDesc'] = __('Light snow showers', THEME_NAME);
								break;
							case '350':
								$weather['image'] = "weather-snow";
								$weather['weatherDesc'] = __('Ice pellets', THEME_NAME);
								break;
							case '338':
								$weather['image'] = "weather-snow";
								$weather['weatherDesc'] = __('Heavy snow', THEME_NAME);
								break;
							case '335':
								$weather['image'] = "weather-snow";
								$weather['weatherDesc'] = __('Patchy heavy snow', THEME_NAME);
								break;
							case '332':
								$weather['image'] = "weather-snow";
								$weather['weatherDesc'] = __('Moderate snow', THEME_NAME);
								break;
							case '329':
								$weather['image'] = "weather-snow";
								$weather['weatherDesc'] = __('Patchy moderate snow', THEME_NAME);
								break;
							case '326':
								$weather['image'] = "weather-snow";
								$weather['weatherDesc'] = __('Light snow', THEME_NAME);
								break;
							case '323':
								$weather['image'] = "weather-snow";
								$weather['weatherDesc'] = __('Patchy light snow', THEME_NAME);
								break;
							case '320':
								$weather['image'] = "weather-snow";
								$weather['weatherDesc'] = __('Moderate or heavy sleet', THEME_NAME);
								break;
							case '317':
								$weather['image'] = "weather-snow";
								$weather['weatherDesc'] = __('Light sleet', THEME_NAME);
								break;
							case '284':
								$weather['image'] = "weather-snow";
								$weather['weatherDesc'] = __('Heavy freezing drizzle', THEME_NAME);
								break;
							case '281':
								$weather['image'] = "weather-snow";
								$weather['weatherDesc'] = __('Freezing drizzle', THEME_NAME);
								break;
							case '266':
								$weather['image'] = "weather-snow";
								$weather['weatherDesc'] = __('Light drizzle', THEME_NAME);
								break;
							case '263':
								$weather['image'] = "weather-snow";
								$weather['weatherDesc'] = __('Patchy light drizzle', THEME_NAME);
								break;
							case '230':
								$weather['image'] = "weather-snow";
								$weather['weatherDesc'] = __('Blizzard', THEME_NAME);
								break;
							case '227':
								$weather['image'] = "weather-snow";
								$weather['weatherDesc'] = __('Blowing snow', THEME_NAME);
								break;
							case '389':
								$weather['image'] = "weather-thunder";
								$weather['weatherDesc'] = __('Moderate or heavy rain in area with thunder', THEME_NAME);
								break;
							case '386':
								$weather['image'] = "weather-thunder";
								$weather['weatherDesc'] = __('Patchy light rain in area with thunder', THEME_NAME);
								break;
							case '200':
								$weather['image'] = "weather-thunder";
								$weather['weatherDesc'] = __('Thundery outbreaks in nearby', THEME_NAME);
								break;
							case '377':
								$weather['image'] = "weather-rain";
								$weather['weatherDesc'] = __('Moderate or heavy showers of ice pellets', THEME_NAME);
								break;
							case '374':
								$weather['image'] = "weather-rain";
								$weather['weatherDesc'] = __('Light showers of ice pellets', THEME_NAME);
								break;
							case '365':
								$weather['image'] = "weather-rain";
								$weather['weatherDesc'] = __('Moderate or heavy sleet showers', THEME_NAME);
								break;
							case '362':
								$weather['image'] = "weather-rain";
								$weather['weatherDesc'] = __('Light sleet showers', THEME_NAME);
								break;
							case '359':
								$weather['image'] = "weather-rain";
								$weather['weatherDesc'] = __('Torrential rain shower', THEME_NAME);
								break;
							case '356':
								$weather['image'] = "weather-rain";
								$weather['weatherDesc'] = __('Moderate or heavy rain shower', THEME_NAME);
								break;
							case '353':
								$weather['image'] = "weather-rain";
								$weather['weatherDesc'] = __('Light rain shower', THEME_NAME);
								break;
							case '314':
								$weather['image'] = "weather-rain";
								$weather['weatherDesc'] = __('Moderate or Heavy freezing rain', THEME_NAME);
								break;
							case '311':
								$weather['image'] = "weather-rain";
								$weather['weatherDesc'] = __('Light freezing rain', THEME_NAME);
								break;
							case '308':
								$weather['image'] = "weather-rain";
								$weather['weatherDesc'] = __('Heavy rain', THEME_NAME);
								break;
							case '305':
								$weather['image'] = "weather-rain";
								$weather['weatherDesc'] = __('Heavy rain at times', THEME_NAME);
								break;
							case '302':
								$weather['image'] = "weather-rain";
								$weather['weatherDesc'] = __('Moderate rain', THEME_NAME);
								break;
							case '299':
								$weather['image'] = "weather-rain";
								$weather['weatherDesc'] = __('Moderate rain at times', THEME_NAME);
								break;
							case '296':
								$weather['image'] = "weather-rain";
								$weather['weatherDesc'] = __('Light rain', THEME_NAME);
								break;
							case '293':
								$weather['image'] = "weather-rain";
								$weather['weatherDesc'] = __('Patchy light rain', THEME_NAME);
								break;
							case '185':
								$weather['image'] = "weather-rain";
								$weather['weatherDesc'] = __('Patchy freezing drizzle nearby', THEME_NAME);
								break;
							case '179':
								$weather['image'] = "weather-rain";
								$weather['weatherDesc'] = __('Patchy snow nearby', THEME_NAME);
								break;
							case '176':
								$weather['image'] = "weather-rain";
								$weather['weatherDesc'] = __('Patchy rain nearby', THEME_NAME);
								break;
							case '260':
								$weather['image'] = "weather-cloudy";
								$weather['weatherDesc'] = __('Freezing fog', THEME_NAME);
								break;
							case '248':
								$weather['image'] = "weather-cloudy";
								$weather['weatherDesc'] = __('Fog', THEME_NAME);
								break;
							case '143':
								$weather['image'] = "weather-cloudy";
								$weather['weatherDesc'] = __('Mist', THEME_NAME);
								break;
							case '122':
								$weather['image'] = "weather-cloudy";
								$weather['weatherDesc'] = __('Overcast', THEME_NAME);
								break;
							case '119':
								$weather['image'] = "weather-cloudy";
								$weather['weatherDesc'] = __('Cloudy', THEME_NAME);
								break;
							case '116':
								$weather['image'] = "weather-clouds";
								$weather['weatherDesc'] = __('Partly Cloudy', THEME_NAME);
								break;
							case '113':
								$weather['image'] = "weather-sun";
								$weather['weatherDesc'] = __('Sunny', THEME_NAME);
								break;
							case '182':
								$weather['image'] = "weather-sleet";
								$weather['weatherDesc'] = __('Patchy sleet nearby', THEME_NAME);
								break;
							default:
								$weather['image'] = "weather-default";
								$weather['weatherDesc'] = __('Can\'t get any data.', THEME_NAME);
								break;
						}
						//set wp cache
						set_transient( 'weather_result_'.urlencode($city).'_'.urlencode($country), $weather, 3600 );
					//can't get any results
					} else {
						$weather['error'] = __("Something went wrong with the connection!",THEME_NAME);
					}
				//get the previous cache
				} else {
					$weather = get_transient('weather_result_'.urlencode($city).'_'.urlencode($country));
				}
			//can't locate the city by ip
			} else {
				$weather['error'] = __("Something went wrong with the connection!",THEME_NAME);
			}
		// the ip is on a black list
		} else {
			$weather['error'] = __("This option doesn't work on localhost!",THEME_NAME);
		}
	// there is no valid api key
	} else {

		$weather['error'] = __("Please set up your API key!",THEME_NAME);

	}
	return $weather;

	
}

/* -------------------------------------------------------------------------*
 * 								HEX -> RGB								*
 * -------------------------------------------------------------------------*/
 
function df_HexToRGB($hex) {
		$hex = ereg_replace("#", "", $hex);
		$color = array();
 
		if(strlen($hex) == 3) {
			$color['r'] = hexdec(substr($hex, 0, 1) . $r);
			$color['g'] = hexdec(substr($hex, 1, 1) . $g);
			$color['b'] = hexdec(substr($hex, 2, 1) . $b);
		}
		else if(strlen($hex) == 6) {
			$color['r'] = hexdec(substr($hex, 0, 2));
			$color['g'] = hexdec(substr($hex, 2, 2));
			$color['b'] = hexdec(substr($hex, 4, 2));
		}
 
		return $color['r'].",".$color['g'].",".$color['b'];
}

/* -------------------------------------------------------------------------*
 * 								GET TITLE COLOR								*
 * -------------------------------------------------------------------------*/
 
function df_title_color($id, $type="category", $echo=true) {
 	if($type == "category" && $id!="popular" && $id!="latest") {
		$my_meta = new Tax_Meta_Class('');
		$titleColor = $my_meta->get_tax_meta($id, THEME_NAME.'_title_color');
		$my_meta->Finish();
	} else if ($type=="page") {
		$titleColor = "#".get_post_meta($id, THEME_NAME."_title_color",true); 
	}

	
	if(!isset($titleColor) || $titleColor=="" || $titleColor=="#") $titleColor = "#".get_option(THEME_NAME."_default_cat_color");
	
	if($echo!=false) {
		echo $titleColor;
	} else {
		return $titleColor;
	}
}

/* -------------------------------------------------------------------------*
 * 								CONTENT WIDTH								*
 * -------------------------------------------------------------------------*/
 
 if ( ! isset( $content_width ) ) 
    $content_width = 900;

/* -------------------------------------------------------------------------*
 * 								WRAP THE TITLE								*
 * -------------------------------------------------------------------------*/
 function df_title_wrap($title, $tag="span") {
	$theTitle = $title;
	$words = explode(" ",$theTitle);
	$wCount = count($words);
	$theTitle = false;
	foreach($words as $key => $title) {
		if(($key+1)==$wCount) {
			$theTitle.= "<".$tag.">".$title." </".$tag.">";
		} else {
			$theTitle.= $title." ";	
		}
		
	}

	echo $theTitle;
}

/* -------------------------------------------------------------------------*
 * 					GET META VALUE OUTSIDE THE LOOP							*
 * -------------------------------------------------------------------------*/
 
 function df_meta($id, $value) {
	$meta = get_post_meta($id, $value, true);
	return $meta;
}

/* -------------------------------------------------------------------------*
 * 							AVARAGE POST RATING								*
 * -------------------------------------------------------------------------*/
 
function df_avarage_rating($id) {
 	$ratings = get_post_meta( $id, THEME_NAME."_ratings", true );
 	if(isset($ratings) && $ratings!="") {
		$totalRate = array();
		$rating = explode(";", $ratings);

		foreach($rating as $rate) { 
			$ratingValues = explode(":", $rate);
			if(isset($ratingValues[1])){
				$ratings = (str_replace(",",".",$ratingValues[1]));
			}
			$totalRate[] = $ratings;
		} 

		if(!empty($totalRate)) { 
			$rateCount = count($totalRate);	
			$total = 0;
			foreach ($totalRate as $val) {
				$total = $total + $val;
			}

			$avarageRate = round($total/$rateCount, 1);
			$avarageRateRound = round($total/$rateCount * 2, 0, PHP_ROUND_HALF_UP);

		}
		return array($avarageRate,$avarageRateRound);
	} else {
		return false;
	}

}

/* -------------------------------------------------------------------------*
 * 									RATING HTML								*
 * -------------------------------------------------------------------------*/
 
function df_rating_html($count) {
	$count = floatval(str_replace(",", ".", $count));
?>
    <div class="review-stars" title="<?php _e("Rating: ", THEME_NAME); echo $count;?>">
        <?php
			$stars   = floor($count); // full stars
			$halves  = round($count-$stars); // half stars
			$empties = 5 - $stars - $halves;  // empty stars

            for($i=1; $i<=$stars; $i++) {
        ?>      <span class="star-full"></span>
        <?php
            }

        ?>
        <?php 
            //half star if needed
             for($i=1; $i<=$halves; $i++) {
        ?>
                <span class="star-half"></span>
        <?php 
            }                             
           
            for ($i=1; $i<=$empties;$i++) {
        ?>
                <span class="star-empty"></span>
        <?php } ?>
    </div>
<?php
}
/* -------------------------------------------------------------------------*
 * 									MAIN MENU								*
 * -------------------------------------------------------------------------*/
 
function add_menu_arrows($menu) {
	$c=0;
	$pos = strpos($menu,"</i>");
	while($pos !== false) {
		$pos_next_a = strpos($menu,"</i>",$pos + 1);
		$pos_next_ul = strpos($menu,"<ul",$pos + 1);

			
		if($pos_next_a > $pos_next_ul && $pos_next_ul > 0) {
			$insert_end = $pos + strlen("<span>");
			$insert_start = strrpos($menu,"<i",$insert_end-strlen($menu));
			$insert_start = strpos($menu,">",$insert_start) + strlen(">");
			
			$start = substr($menu,0,$insert_start);
			$end = substr($menu,$insert_start);
			$menu = $start."<span>".$end;
			
			$start = substr($menu,0,$insert_end);
			$end = substr($menu,$insert_end);
			$menu = $start."</span>".$end;
			
			$pos = $pos + strlen("<span></span>");
		}
		
		$pos = strpos($menu,"</i>",$pos + 1);
	}
	return $menu;
}

/* -------------------------------------------------------------------------*
 * 						MAIN MENU DESCRIPTION								*
 * -------------------------------------------------------------------------*/
 
function description_in_nav_el($item_output, $item, $depth, $args)
{
	if(isset($item->attr_title) && $item->attr_title!="") {
		$menu = preg_replace('/(<a.*?>)([^<]*?)<\/a>/', '$1$2<font>'.$item->attr_title.'</font></a>', $item_output);
	} else {
		$menu = $item_output;
	}
	
	return $menu;
}

/* -------------------------------------------------------------------------*
 * 							DF GET SIDEBAR SIDE								*
 * -------------------------------------------------------------------------*/
 
function df_get_sidebar($id, $side="right") {
	//sidebars defauult option
	$sidebarPosition = get_option ( THEME_NAME."_sidebar_position" ); 
	//sidebars singlepost/page option
	$sidebarPositionCustom = get_post_meta ( $id, THEME_NAME."_sidebar_position", true ); 

	//left side sidebar
	if( ($sidebarPosition == "left" || ( $sidebarPosition == "custom" &&  $sidebarPositionCustom == "left")) && $side == 'left' ) { 
		get_template_part(THEME_INCLUDES."sidebar");
	}

	//right side sidebar
	if( ($sidebarPosition == "right" || ( $sidebarPosition == "custom" &&  $sidebarPositionCustom == "right") || ( $sidebarPosition == "custom" && !$sidebarPositionCustom )) && $side == 'right') { 
		get_template_part(THEME_INCLUDES."sidebar");
	}

}

/* -------------------------------------------------------------------------*
 * 								GOOGLE + BUTTON								*
 * -------------------------------------------------------------------------*/
 
function df_plusones($url) {
	if($url) {
	  	$curl = curl_init();
	  	curl_setopt($curl, CURLOPT_URL, "https://clients6.google.com/rpc");
	  	curl_setopt($curl, CURLOPT_POST, 1);
	  	curl_setopt($curl, CURLOPT_POSTFIELDS, '[{"method":"pos.plusones.get","id":"p","params":{"nolog":true,"id":"' . $url . '","source":"widget","userId":"@viewer","groupId":"@self"},"jsonrpc":"2.0","key":"p","apiVersion":"v1"}]');
	  	curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
	  	curl_setopt($curl, CURLOPT_HTTPHEADER, array('Content-type: application/json'));
	  	$curl_results = curl_exec ($curl);
	  	curl_close ($curl);
	  	$json = json_decode($curl_results, true);
	  	return intval( $json[0]['result']['metadata']['globalCounts']['count'] );
	} else {
		return 0;
	}
}

/* -------------------------------------------------------------------------*
 * 							MENU PARENT CLASS							*
 * -------------------------------------------------------------------------*/
add_filter( 'wp_nav_menu_objects', 'add_menu_parent_class' );
function add_menu_parent_class( $items ) {
	
	$parents = array();
	foreach ( $items as $item ) {
		if ( $item->menu_item_parent && $item->menu_item_parent > 0 ) {
			$parents[] = $item->menu_item_parent;
		}
	}
	
	foreach ( $items as $item ) {
		if ( in_array( $item->ID, $parents ) ) {
			$item->classes[] = 'menu-parent'; 
		}
	}
	
	return $items;    
}



function remove_br($subject) {
	$subject = str_replace("<p></p>", " ", $subject );
	$subject = str_replace("<br/>", " ", $subject );
	$subject = str_replace("<br>", " ", $subject );
	$subject = str_replace("<br />", " ", $subject );
	return $subject;
}

function get_query_string_paged() {
	global $query_string;
	$pos = strpos($query_string,"paged=");
	if($pos !== false ) {
		$sub = substr($query_string,$pos);
		$posand = strpos($sub,"&");
		if ($posand == 0) {$paged = substr($sub,6);}
		else { $paged = substr($sub,6,$posand-6);}
		return $paged;
	}
	return 0;
}

function get_contact_page() {
	$pages = get_pages();
	$pageID = array();
	foreach($pages as $p) {
		$meta = get_post_custom_values("_wp_page_template",$p->ID);
		if($meta[0] == "template-contact.php") {
			$pageID[]=$p->ID;
		}
	}
	return $pageID;
}

function get_portfolio_page() {
	$pages = get_pages();
	$pageID = array();
	foreach($pages as $p) {
		$meta = get_post_custom_values("_wp_page_template",$p->ID);
		if($meta[0] == "template-portfolio.php") {
			$pageID[]=$p->ID;
		}
	}
	return $pageID;
}


function get_special_offers_page() {
	$pages = get_pages();
	$pageID = array();
	foreach($pages as $p) {
		$meta = get_post_custom_values("_wp_page_template",$p->ID);
		if($meta[0] == "template-special-offers.php") {
			$pageID[]=$p->ID;
		}
	}
	return $pageID;
}


function get_fullwidth_page() {
	$pages = get_pages();
	$pageID = array();
	foreach($pages as $p) {
		$meta = get_post_custom_values("_wp_page_template",$p->ID);
		if($meta[0] == "template-full-width.php") {
			$pageID[]=$p->ID;
		}
	}
	return $pageID;
}

function get_accommodation_page() {
	$pages = get_pages();
	$pageID = array();
	foreach($pages as $p) {
		$meta = get_post_custom_values("_wp_page_template",$p->ID);
		if($meta[0] == "template-accommodation-1.php" || $meta[0] == "template-accommodation-2.php" || $meta[0] == "template-accommodation-3.php") {
			$pageID[]=$p->ID;
		}
	}
	return $pageID;

}

function get_features_page() {
	$pages = get_pages();
	$pageID = array();
	foreach($pages as $p) {
		$meta = get_post_custom_values("_wp_page_template",$p->ID);
		if($meta[0] == "template-features.php") {
			$pageID[]=$p->ID;
		}
	}
	return $pageID;

}

function get_home_page() {
	$pages = get_pages();
	$pageID = array();
	foreach($pages as $p) {
		$meta = get_post_custom_values("_wp_page_template",$p->ID);
		if($meta[0] == "template-homepage.php") {
			$pageID[]=$p->ID;
		}
	}
	return $pageID;

}

function df_get_page($name, $array=true) {
	$pages = get_pages();
	$pageID = array();
	foreach($pages as $p) {
		$meta = get_post_custom_values("_wp_page_template",$p->ID);
		if($meta[0] == "template-".$name.".php" || strpos($meta[0],"template-".$name.".php") !== false) {
			$pageID[]=$p->ID;
		}
	}
	if($array==false) {
		$pageID = $pageID[0];
	}
	return $pageID;
}

/* -------------------------------------------------------------------------*
 * 								WIDGET COUNTER								*
 * -------------------------------------------------------------------------*/
 
function widget_first_last_classes($params) {

	global $my_widget_num; // Global a counter array
	$this_id = $params[0]['id']; // Get the id for the current sidebar we're processing
	$arr_registered_widgets = wp_get_sidebars_widgets(); // Get an array of ALL registered widgets	

	if(!$my_widget_num) {// If the counter array doesn't exist, create it
		$my_widget_num = array();
	}

	if(!isset($arr_registered_widgets[$this_id]) || !is_array($arr_registered_widgets[$this_id])) { // Check if the current sidebar has no widgets
		return $params; // No widgets in this sidebar... bail early.
	}

	if(isset($my_widget_num[$this_id])) { // See if the counter array has an entry for this sidebar
		$my_widget_num[$this_id] ++;
	} else { // If not, create it starting with 1
		$my_widget_num[$this_id] = 1;
	}

	$class = 'class="widget-' . $my_widget_num[$this_id] . ' '; // Add a widget number class for additional styling options

	if($my_widget_num[$this_id] == 1) { // If this is the first widget
		$class .= 'first ';
	} elseif($my_widget_num[$this_id] == count($arr_registered_widgets[$this_id])) { // If this is the last widget
		$class .= 'last ';
	}

	$params[0]['before_widget'] = str_replace('class="', $class, $params[0]['before_widget']); // Insert our new classes into "before widget"

	return $params;

}

function different_themes_info_message($content) {
	?>
	<a href="javascript:{}" class="help"><img src="<?php echo THEME_IMAGE_CPANEL_URL; ?>ico-help-1.png" /></a>
	<i class="popup-help popup-help-hidden trans-1">
		<a href="javascript:{}" class="close"></a>
		<?php echo $content; ?>
	</i>
	<?php
}
	
$uploadsdir=wp_upload_dir();
define("THEME_UPLOADS_URL", $uploadsdir['url']);


/* -------------------------------------------------------------------------*
 * 								GET IMAGE HTML								*
 * -------------------------------------------------------------------------*/
 
 function df_image_html($id, $width=0, $height=0) {
 	$image = get_post_thumb($id,$width,$height);
	$return = '<img src="'.$image["src"].'" alt="'.get_the_title($id).'" title="'.get_the_title($id).'" />';
	return $return;
}



/* -------------------------------------------------------------------------*
 * 							GRAVATAR SETTUP									*
 * -------------------------------------------------------------------------*/
 
function get_gravatar( $email, $s = 80, $d = 'mm', $r = 'g', $img = false, $atts = array() ) {
	$url = 'http://www.gravatar.com/avatar/';
	$url .= md5(strtolower(trim($email)));
	$url .= "?s=$s&d=$d&r=$r";
	if ( $img ) {
		$url = '<img src="' . $url . '"';
		foreach ( $atts as $key => $val )
			$url .= ' ' . $key . '="' . $val . '"';
		$url .= ' />';
	}
	return $url;
}

/* -------------------------------------------------------------------------*
 * 					CUSTOM POST COUNT PER CATEGORY PAGE						*
 * -------------------------------------------------------------------------*/
 


function df_set_posts_per_page( $query ) {
  	global $wp_the_query;


    //blog style
    if(is_category()) {
    	//get current cat id
    	$catId = get_cat_id( single_cat_title("",false) );
        $blogStyle = df_get_option($catId,"blog_style");
    } else {
        $blogStyle = get_option(THEME_NAME."_blog_style");
    }
    
    if(!isset($blogStyle) || $blogStyle==""){
        $blogStyle = get_option(THEME_NAME."_blog_style");
    }

	//post count
	$posts_per_page = get_option(THEME_NAME.'_posts_count_grid');

	if($posts_per_page == "") {
		$posts_per_page = get_option('posts_per_page');
	}

  	if ( ( ! is_admin() ) && ( $query === $wp_the_query ) && ( $blogStyle=="3" )) {
    	$query->set( 'posts_per_page', $posts_per_page );
  	}

  	return $query;

}


/* -------------------------------------------------------------------------*
 * 								NEWS PAGE TITLE								*
 * -------------------------------------------------------------------------*/
 
function df_page_title() {
	if(!is_archive() && !is_category() && !is_search() && !isset($_REQUEST['s'])) {
		$title = get_the_title(df_page_id());
	} else if(is_search() || isset($_REQUEST['s'])) {
		$title = __("Search Results for", THEME_NAME)." \"".remove_html($_GET['s'])."\"";
	} else if(is_category()) {
		$category = get_category( get_query_var( 'cat' ) );
		$cat_id = $category->cat_ID;
		$catName = get_category($cat_id )->name;
		$title = $catName;
	} else if (is_author()) {
		$curauth = (get_query_var('author_name')) ? get_user_by('slug', get_query_var('author_name')) : get_userdata(get_query_var('author'));
		$title = __("Posts From", THEME_NAME). " ".$curauth->display_name;
	} else if(is_tag()) {
		$category = single_tag_title('',false);
		$title =  __("Tag", THEME_NAME)." \"".$category."\"";
	} else if(is_archive() && !get_post_type()) {
		$title = __("Archive", THEME_NAME);
	} else {
		$title = get_the_title();
	}
	echo $title;
}

/* -------------------------------------------------------------------------*
 * 							CONTENT CLASS							*
 * -------------------------------------------------------------------------*/
 
function DF_content_class($id) {
	$sidebarPosition = get_option ( THEME_NAME."_sidebar_position" ); 
	$sidebarPositionCustom = get_post_meta ( $id, THEME_NAME."_sidebar_position", true ); 
    if(is_category()) {
        $sidebarPositionCustom = df_get_option( $id, 'sidebar_position', false );
    }

	if( $sidebarPosition == "left" || ( $sidebarPosition == "custom" &&  $sidebarPositionCustom == "left") ) { 
		$contentClass = "right";
	} else if( $sidebarPosition == "right" || ( $sidebarPosition == "custom" &&  $sidebarPositionCustom == "right") ) { 
		$contentClass = "left";
	} else if ( $sidebarPosition == "custom" && !$sidebarPositionCustom ) { 
		$contentClass = "left";
	} else {
		$contentClass = "left";
	}
	echo $contentClass;
}

/* -------------------------------------------------------------------------*
 * 							CHECK WOOCOMMERCE								*
 * -------------------------------------------------------------------------*/
 
function df_is_woocommerce_activated() {
	if ( class_exists( 'woocommerce' ) ) { return true; } else { return false; }
}

/* -------------------------------------------------------------------------*
 * 							GET PAGE ID										*
 * -------------------------------------------------------------------------*/
 
function df_page_id() {
	$page_id = get_queried_object_id();

	if(isset($page_id) && $page_id!=0) {
		$page_id;	
	} elseif(df_is_woocommerce_activated() == true) {
		$page_id = woocommerce_get_page_id('shop');
	}

	if(function_exists("is_bbpress")) {
		if(is_bbpress()){
			$page_id = 0;
		}
	}	

	return $page_id;
}

/* -------------------------------------------------------------------------*
 * 							UPDATE POST VIEW COUNT							*
 * -------------------------------------------------------------------------*/
 
function df_setPostViews() {
	global $post;
	if(is_single() && isset($post)) {
		$postID = $post->ID;
		$count_key = THEME_NAME.'_post_views_count';
		$count = get_post_meta($postID, $count_key, true);
		
		if ( !current_user_can( 'manage_options' ) && !isset($_COOKIE[THEME_NAME."_post_views_count_".$postID])) {
			if ( $count=='' ) {
				delete_post_meta($postID, $count_key);
				add_post_meta($postID, $count_key, '0');
			} else {
				$count++;
				update_post_meta($postID, $count_key, $count, $count-1);
			}

			setcookie(THEME_NAME."_post_views_count_".$postID, "1", time()+2678400); 
		}

	}
}

/* -------------------------------------------------------------------------*
 * 							GET POST VIEW COUNT								*
 * -------------------------------------------------------------------------*/
 
function df_getPostViews($postID){
    $count_key = THEME_NAME.'_post_views_count';
    $count = get_post_meta($postID, $count_key, true);
   
   if( $count=='' ){
        delete_post_meta($postID, $count_key);
        add_post_meta($postID, $count_key, '0');
        return "0";
    }
	
    return $count;
}
/* -------------------------------------------------------------------------*
 * 								SIDEBAR CLASS								*
 * -------------------------------------------------------------------------*/
 
function DF_sidebarClass($id){
	wp_reset_query();
	$sidebarPosition = get_option ( THEME_NAME."_sidebar_position" ); 
	$sidebarPositionCustom = get_post_meta ( $id, THEME_NAME."_sidebar_position", true ); 
	if($sidebarPosition=="left" || ( $sidebarPosition == "custom" &&  $sidebarPositionCustom == "left") ) { $sidebarClass = 'df-left'; } else { $sidebarClass = 'df-right'; } 
    echo $sidebarClass;
}


/* -------------------------------------------------------------------------*
 * 								POST TYPE								*
 * -------------------------------------------------------------------------*/
 
	function OT_post_type($post_type) {
		switch ($post_type) {
			case "blog":
				$post_type="post";
				break;
			case "gallery":
				$post_type="gallery";
				break;
			case "all":
				$post_type=array("post","gallery");
				break;
			default:
				$post_type="post";
		}
		return $post_type;
	}

 /* -------------------------------------------------------------------------*
 * 						ADD CUSTOM TEXT FORMATTING BUTTONS					*
 * -------------------------------------------------------------------------*/
global $differentthemes_buttons;
$differentthemes_buttons=array("differentthemesdropcaps","differentthemespreformated","differentthemesmarker","differentthemesmiscellaneous","differentthemeslist","differentthemesbutton","|",
			"differentthemessocial","differentthemesicon", "differentthemesquote", "differentthemestabs", "differentthemesaccordion", "differentthemesgallery", "|", "differentthemescolumns", "differentthemestooltip", "differentthemesvideo", "differentthemesclear", "differentthemesspacer");

function add_differentthemes_buttons() {
   if ( get_user_option('rich_editing') == 'true') {
     add_filter('mce_external_plugins', 'add_differentthemes_btn_tinymce_plugin');
     add_filter('mce_buttons_3', 'register_differentthemes_buttons');
   }
}

function register_differentthemes_buttons($buttons) {
	global $differentthemes_buttons;
		
   array_push($buttons, implode(",",$differentthemes_buttons));
   return $buttons;
}

function add_differentthemes_btn_tinymce_plugin($plugin_array) {
	global $differentthemes_buttons;
	
	foreach($differentthemes_buttons as $btn){
		$plugin_array[$btn] = THEME_ADMIN_URL.'buttons-formatting/editor-plugin.js';
	}
	return $plugin_array;

}
 
 /* -------------------------------------------------------------------------*
 * 							GALLERY IMAGE COUNT								*
 * -------------------------------------------------------------------------*/
 
function DF_image_count($post_id = false) {
    //Get all images
   	$galleryImages = get_post_meta ( $post_id, THEME_NAME."_gallery_images", true ); 
   	$imageIDs = explode(",",$galleryImages);
   	$att_count = count(array_filter($imageIDs));

	return $att_count;
}

 /* ------------------------------------------------------------------------*
 * 							OTHER THEMES									*
 * -------------------------------------------------------------------------*/
 
 function other_themes () {
?>
		<!-- BEGIN more-orange-themes -->
		<div class="more-orange-themes">

			<div class="header">
				<img src="<?php echo THEME_IMAGE_MTHEMES_URL; ?>title-more-themes.png" alt="" width="447" height="23" />
				<p>
					<a href="http://www.themeforest.net/user/orange-themes/portfolio?ref=orange-themes" class="btn-1" target="_blank"><span><u class="themeforest">Check our portfolio at themeforest.net</u></span></a>
					<a href="http://www.twitter.com/#!/orangethemes" class="btn-1" target="_blank"><span><u class="twitter">Follow us on twitter</u></span></a>
					<a href="http://www.orange-themes.com" class="btn-1" target="_blank"><span><u class="orangethemes">Orange-themes.com</u></span></a>
				</p>
			</div>

			<?php 
				$xml = theme_get_latest_theme_version(THEME_NOTIFIER_CACHE_INTERVAL); 
				foreach ( $xml->item as $entry ) {
				$title = explode("Private: ", $entry->title);
			?>
			
			<!-- BEGIN .item -->
			<div class="item">
				<div class="image">
					<a href="<?php echo $entry->purchase; ?>"><img src="<?php echo $entry->image; ?>" /></a>
				</div>
				<div class="text">
					<h2><a href="<?php echo $entry->purchase; ?>"><?php echo $title[1]; ?></a></h2>
					<p><?php echo $entry->content; ?></p>
					<p class="link"><a href="<?php echo $entry->demo; ?>" target="_blank">Demo website</a></p>
					<p class="link"><a href="<?php echo $entry->purchase; ?>" target="_blank">Purchase at ThemeForest.net</a></p>
					<?php if ( $entry->html ) { ?>
						<p class="link"><a href="<?php echo $entry->html; ?>" target="_blank">HTML version</a></p>
					<?php } ?>
				</div>
			<!-- END .item -->
			</div>
			<?php } ?> 
			
		<!-- END more-orange-themes -->
		</div>
<?php
	
}

/* -------------------------------------------------------------------------*
 * 							COUNT ATTACHMENTS								*
 * -------------------------------------------------------------------------*/
 
function OT_attachment_count($post_id = false) {
	global $post;
    //Get all attachments
    $attachments = get_posts( array(
        'post_type' => 'attachment',
        'posts_per_page' => -1
    ) );

    $att_count = 0;
    if ( $attachments ) {
        foreach ( $attachments as $attachment ) {
            // Check for the post type based on individual attachment's parent
            if ( 'gallery' == get_post_type($attachment->post_parent) && $post_id == $attachment->post_parent ) {
                $att_count = $att_count + 1;
            } else if ('gallery' == get_post_type($attachment->post_parent) && $post_id == false) {
				$att_count = $att_count + 1;
			}
        }
    }
	 return $att_count;
}

/* -------------------------------------------------------------------------*
 * 							CHECK PAGE TEMPLATE								*
 * -------------------------------------------------------------------------*/
 
function is_pagetemplate_active($pagetemplate = '') {
	global $wpdb;
	$sql = "select meta_key from $wpdb->postmeta where meta_key like '_wp_page_template' and meta_value like '" . $pagetemplate . "'";
	$result = $wpdb->query($sql);
	if ($result) {
		return TRUE;
	} else {
		return FALSE;
	}
}

/* -------------------------------------------------------------------------*
 * 							GALLERY IMAGE SELECT							*
 * -------------------------------------------------------------------------*/
 
function df_gallery_image_select($id, $value) {
	global $post_id,$post;
	if(!$post_id) {
		$post_id = $post->ID;
	}
	
	?>
	<div id="df_images_container">
		<ul class="df_gallery_images">
			<?php
				if ( $value ) {
					$product_image_gallery = $value;
				} else {
					// Backwards compat
					$attachment_ids = get_posts( 'post_parent=' . $post_id . '&numberposts=-1&post_type=attachment&orderby=menu_order&order=ASC&post_mime_type=image&fields=ids&meta_value=0' );
					$attachment_ids = array_diff( $attachment_ids, array( get_post_thumbnail_id() ) );
					$product_image_gallery = implode( ',', $attachment_ids );
				}

				$attachments = array_filter( explode( ',', $product_image_gallery ) );

				if ( $attachments )
					foreach ( $attachments as $attachment_id ) {
						echo '<li class="image" data-attachment_id="' . $attachment_id . '">
							' . wp_get_attachment_image( $attachment_id, array(80,80) ) . '
							<ul class="actions">
								<li><a href="#" class="delete" title="' . __( 'Delete image', THEME_NAME ) . '">' . __( 'Delete', THEME_NAME ) . '</a></li>
							</ul>
						</li>';
					}
			?>
		</ul>

		<input type="hidden" id="<?php echo $id;?>" name="<?php echo $id;?>" value="<?php echo esc_attr( $product_image_gallery ); ?>" />

	</div>
	<p class="add_product_images hide-if-no-js">
		<a href="#"><?php _e( 'Add images', THEME_NAME ); ?></a>
	</p>
	<script type="text/javascript">
		jQuery(document).ready(function($){

			// Uploading files
			var product_gallery_frame;
			var $image_gallery_ids = $('#<?php echo $id;?>');
			var $df_gallery_images = $('#df_images_container ul.df_gallery_images');

			jQuery('.add_product_images').on( 'click', 'a', function( event ) {

				var $el = $(this);
				var attachment_ids = $image_gallery_ids.val();

				event.preventDefault();

				// If the media frame already exists, reopen it.
				if ( product_gallery_frame ) {
					product_gallery_frame.open();
					return;
				}

				// Create the media frame.
				product_gallery_frame = wp.media.frames.downloadable_file = wp.media({
					// Set the title of the modal.
					title: '<?php _e( 'Add Images to Product Gallery', THEME_NAME ); ?>',
					button: {
						text: '<?php _e( 'Add to gallery', THEME_NAME ); ?>',
					},
					multiple: true
				});

				// When an image is selected, run a callback.
				product_gallery_frame.on( 'select', function() {

					var selection = product_gallery_frame.state().get('selection');

					selection.map( function( attachment ) {

						attachment = attachment.toJSON();

						if ( attachment.id ) {
							attachment_ids = attachment_ids ? attachment_ids + "," + attachment.id : attachment.id;

							$df_gallery_images.append('\
								<li class="image" data-attachment_id="' + attachment.id + '">\
									<img src="' + attachment.url + '" width="80" height="80"/>\
									<ul class="actions">\
										<li><a href="#" class="delete" title="<?php _e( 'Delete image', THEME_NAME ); ?>"><?php _e( 'Delete', THEME_NAME ); ?></a></li>\
									</ul>\
								</li>');
						}

					} );

					$image_gallery_ids.val( attachment_ids );
				});

				// Finally, open the modal.
				product_gallery_frame.open();
			});

			// Image ordering
			$df_gallery_images.sortable({
				items: 'li.image',
				cursor: 'move',
				scrollSensitivity:40,
				forcePlaceholderSize: true,
				forceHelperSize: false,
				helper: 'clone',
				opacity: 0.65,
				placeholder: 'wc-metabox-sortable-placeholder',
				start:function(event,ui){
					ui.item.css('background-color','#f6f6f6');
				},
				stop:function(event,ui){
					ui.item.removeAttr('style');
				},
				update: function(event, ui) {
					var attachment_ids = '';

					$('#df_images_container ul li.image').css('cursor','default').each(function() {
						var attachment_id = jQuery(this).attr( 'data-attachment_id' );
						attachment_ids = attachment_ids + attachment_id + ',';
					});

					$image_gallery_ids.val( attachment_ids );
				}
			});

			// Remove images
			$('#df_images_container').on( 'click', 'a.delete', function() {

				$(this).closest('li.image').remove();

				var attachment_ids = '';

				$('#df_images_container ul li.image').css('cursor','default').each(function() {
					var attachment_id = jQuery(this).attr( 'data-attachment_id' );
					attachment_ids = attachment_ids + attachment_id + ',';
				});

				$image_gallery_ids.val( attachment_ids );

				return false;
			} );

		});
	</script>
	<?php

}


/* -------------------------------------------------------------------------*
 * 							IMAGE ICONS								*
 * -------------------------------------------------------------------------*/
 
function DF_image_icon($id) {
    //post_type
    $postType = get_post_meta ( $id, THEME_NAME."_post_type", true );
    

    switch ($postType) {
        case 'none':
            $icon = false;
            break;
        case 'video':
            $icon = '<i class="fa fa-play"></i>';
            break;
        case 'image':
            $icon = '<i class="fa fa-picture-o"></i>';
            break;
        case 'music':
            $icon = '<i class="fa fa-music"></i>';
            break;
        case 'photo':
            $icon = '<i class="fa fa-camera"></i>';
            break;
        default:
            $icon = false;
            break;
    }
    if($icon!=false) {
	   	$return='<div class="post-format">';
			$return.='<span>'.$icon.'</span>';
		$return.='</div>';
	} else {
		$return=false;
	}
	return $return;
}

/* -------------------------------------------------------------------------*
 * 								GET GOOGLE FONTS							*
 * -------------------------------------------------------------------------*/
 
 
function OT_get_google_fonts($sort = "alpha") {

	$font_list = get_option(THEME_NAME."_google_font_list");
	$font_list_time = get_option(THEME_NAME."_google_font_list_update");
	$now = time();
	$interval = 41600;
	
	if($font_list) {
		$font_list = $font_list;
	} else if(!$font_list || (( $now - $font_list_time ) > $interval)) {
		$url = "https://www.googleapis.com/webfonts/v1/webfonts?key=AIzaSyCpatq_HIaUbw1XUxVAellP4M1Uoa6oibU&sort=" . $sort;
		$result = json_response( $url );
		if($result!=false) {
			$font_list = array();
			foreach ( $result->items as $font ) {

				$font_list[] .= $font->family;
				
			}
		update_option(THEME_NAME."_google_font_list",$font_list);
		update_option(THEME_NAME."_google_font_list_update",time());
		} else {
			$font_list = false;
		}

	} else {
		$font_list = false;
	}
		
	return $font_list;
	
}
/* -------------------------------------------------------------------------*
 * 								JSON RESPONSE								*
 * -------------------------------------------------------------------------*/
 
if ( ! function_exists( 'json_response' ) )	{

	function json_response( $url )	{
			$args = array(
				 'timeout' => '10',
				 'redirection' => '10',
				 'sslverify' => false // for localhost
			);
			
			# Parse the given url
			$raw = wp_remote_get( $url, $args );
			if (!isset($raw->errors) && $raw['body']) {	
				$decoded = json_decode( $raw['body'] );
				return $decoded;
			} else {
				return false;	
			}
	}

}


/* -------------------------------------------------------------------------*
 * 								MENU NAME						*
 * -------------------------------------------------------------------------*/
 
function DF_et_theme_menu_name( $theme_location ) {
	if( ! $theme_location ) return false;
 
	$theme_locations = get_nav_menu_locations();
	if( ! isset( $theme_locations[$theme_location] ) ) return false;
 
	$menu_obj = get_term( $theme_locations[$theme_location], 'nav_menu' );
	if( ! $menu_obj ) $menu_obj = false;
	if( ! isset( $menu_obj->name ) ) return false;
 
	return $menu_obj->name;
}


/* -------------------------------------------------------------------------*
 * 							COMMENT FORMATION								*
 * -------------------------------------------------------------------------*/
 
 
function differentthemes_comment($comment, $args, $depth) {
	global $post;
	$GLOBALS['comment'] = $comment;
   ?>
	<li <?php comment_class('comment'); ?> id="li-comment-<?php comment_ID(); ?>">
		<article id="comment-<?php comment_ID(); ?>">
			<img src="<?php echo get_avatar_url(get_avatar( $comment, 60));?>" class="comment-avatar" alt="<?php _e( 'Avatar' , THEME_NAME );?>">
            <div class="comment-meta">
            	<span class="by-author"><?php printf(__('%1$s', THEME_NAME), get_comment_author_link());?></span>
            	<span class="by-date"><?php echo get_comment_date("F d, Y");?></span>
            </div>
            <div class="comment-content">
                <?php comment_text(); ?>
            </div>
            <div class="reply"><?php comment_reply_link(array_merge( $args, array('respond_id' => "respond", 'depth' => $depth, 'max_depth' => $args['max_depth'], 'reply_text' => ''.( __( 'Reply' , THEME_NAME )).''))) ?></div>
        </article>




<?php
       }
	   
add_action('init', 'add_differentthemes_buttons');

add_filter('dynamic_sidebar_params','widget_first_last_classes');
add_theme_support('automatic-feed-links' ); 
add_filter('wp', 'df_setPostViews');
//add_filter('walker_nav_menu_start_el', 'description_in_nav_el', 10, 4);

add_action( 'pre_get_posts',  'df_set_posts_per_page'  );


?>