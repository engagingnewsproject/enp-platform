<?
/*
*   settings-display-functions.php
*   Functions for displaying the settings form
*
*/



/*
*
*   Create Settings Button HTML
*
*/
function buttonCreateForm($enp_buttons, $registered_content_types) {
    $formHTML = '';
    if($enp_buttons === false || empty($enp_buttons)) {
        $enp_buttons = array(array('btn_slug' => '', 'btn_type'=>'')); // create an empty array for it to squasy bugs
        $formHTML .= buttonCreateFormHTML($enp_buttons, $registered_content_types);
    } else {
        $i = 0;
        foreach($enp_buttons as $enp_button) {
            $args['btn_slug'] = $enp_button['btn_slug'];
            $enp_btn_obj = new Enp_Button($args);
            $formHTML .= buttonCreateFormHTML($enp_buttons, $registered_content_types, $i, $enp_btn_obj);
            $i++;
        }

        // if we want to add buttons later, we'd add more after this loop
        // $formHTML .= buttonCreateFormHTML($enp_buttons, $registered_content_types, $i, $enp_btn_obj);

    }

    echo $formHTML;

}


function buttonCreateFormHTML($enp_buttons, $registered_content_types, $i = 0, $enp_btn_obj = false ) {
    $formHTML = '
                <div class="enp-btn-table-wrap">
                    <table class="form-table enp-btn-form" data-button="'.$i.'">
                        <tbody>
                            <tr class="btn-slug">
                                <th scope="row">
                                    <label for="enp-button-type">Button</label>
                                </th>
                                <td class="btn-select-slug">
                                    <fieldset>'
                                        .buttonCreateSlug($enp_buttons, $i, $enp_btn_obj).
                                    '</fieldset>
                                </td>
                            </tr>
                            <tr class="btn-type">
                                <th scope="row">
                                    <label for="enp-button-content-type">Where to Use this Button</label>
                                </th>
                                <td class="btn-select-type">
                                    <fieldset>'.
                                        buttonCreateBtnType($enp_buttons, $i, $registered_content_types)
                                    .'</fieldset>
                                    <p id="enp-button-content-type-description" class="description">Where do you want this button to display?</p>
                                </td>
                            </tr>
                            <tr class="btn-display-popular">
                                <th scope="row">
                                    <label for="enp-display-popular">Show most <span class="most-clicked-name">Clicked</span> posts list.</label>
                                </th>
                                <td class="btn-select-type">
                                    <fieldset>'.
                                        buttonCreateDisplayPopular($enp_buttons, $i)
                                    .'</fieldset>
                                    <p id="enp-display-popular-description" class="description">Display a list of the top 5 <span class="most-clicked-name">Clicked</span> posts at the bottom of each post, page, and custom post type.</p>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>';

    return $formHTML;
}



function buttonCreateSlug($enp_buttons, $i = 0, $enp_btn_obj) {
    $buttonSlugHTML = '';

    $buttonSlugHTML .= buttonCreateSlugHTML($enp_buttons, $i, $enp_btn_obj);

    return $buttonSlugHTML;
}

function buttonCreateDisplayPopular($enp_buttons, $i) {
    $displayPopularHTML = '';

    // set our default value to false
    $checked_val = false;

    if(isset($enp_buttons[$i]['display_popular'])) {
        // set the value
        $checked_val = $enp_buttons[$i]['display_popular'];
    }

    $displayPopularHTML = '<label>
                            <input class="btn-display-popular-input" type="checkbox" name="enp_buttons['.$i.'][display_popular]" aria-describedby="enp-display-popular-description" value="1" '.checked(true, $checked_val, false).' /> Display Most <span class="most-clicked-name">Clicked</span> Posts List
                        </label>';

    return $displayPopularHTML;
}

function buttonCreateSlugHTML($enp_buttons, $i = 0, $enp_btn_obj) {
    // if there's no object or there are
    if($enp_btn_obj === false || $enp_btn_obj->btn_lock === false) {
        $buttonSlugHTML ='<label>
                            <input class="btn-slug-input btn-slug-input-respect" type="radio" name="enp_buttons['.$i.'][btn_slug]" aria-describedby="enp-button-slug-description" value="respect" '.checked('respect', $enp_buttons[$i]["btn_slug"], false).' /> Respect
                        </label>
                        <label>
                            <input class="btn-slug-input btn-slug-input-recommend" type="radio" name="enp_buttons['.$i.'][btn_slug]" aria-describedby="enp-button-slug-description" value="recommend" '.checked('recommend', $enp_buttons[$i]["btn_slug"], false).' /> Recommend
                        </label>
                        <label>
                            <input class="btn-slug-input btn-slug-input-important" type="radio" name="enp_buttons['.$i.'][btn_slug]" aria-describedby="enp-button-slug-description" value="important" '.checked('important', $enp_buttons[$i]["btn_slug"], false).' /> Important
                        </label>
                        <label>
                            <input class="btn-slug-input btn-slug-input-thoughtful" type="radio" name="enp_buttons['.$i.'][btn_slug]" aria-describedby="enp-button-slug-description" value="thoughtful" '.checked('thoughtful', $enp_buttons[$i]["btn_slug"], false).' /> Thoughtful
                        </label>
                        <label>
                            <input class="btn-slug-input btn-slug-input-useful" type="radio" name="enp_buttons['.$i.'][btn_slug]" aria-describedby="enp-button-slug-description" value="useful" '.checked('useful', $enp_buttons[$i]["btn_slug"], false).' /> Useful
                        </label>
                        <p id="enp-button-slug-description"class="description">Which button do you want to use on your site?</p>
                        <p class="description">Have an idea for other button text options? Let us know! ____@engagingnewsproject.org';
    } else {
        // the button object exists and it's locked, so we can't let people change it
        // without resetting everything to 0
        $buttonSlugHTML =  '<label>
                                <input type="radio" name="enp_buttons['.$i.']['.$enp_btn_obj->get_btn_slug().']" aria-describedby="enp-button-slug-description" value="respect" '.checked('respect', $enp_buttons[$i]["btn_slug"], false).' /> '.$enp_btn_obj->get_btn_name()
                          .'</label>
                          <p class="description">This button is locked because people have already clicked on it.</p>
                          <p class="description">You have to delete it and create a new button to change the button name.</p>';
    }

    return $buttonSlugHTML;
}


function buttonCreateBtnType($enp_buttons, $i, $registered_content_types) {
    $checklist_html = '';

    foreach($registered_content_types as $content_type) {
        $checklist_html .= buttonCreateBtnTypeHTML($enp_buttons, $i, $content_type);
    }

    return $checklist_html;
}

function buttonCreateBtnTypeHTML($enp_buttons, $i, $content_type) {
    $checklist_html ='';

    $name = 'enp_buttons['.$i.'][btn_type]['.$content_type['slug'].']';

    // set our default value to false
    $checked_val = false;
    // this is absurdly convoluted, but it works... Improvements are welcome
    if(isset($enp_buttons[$i]['btn_type'][$content_type['slug']])) {
        // set the value
        $checked_val = $enp_buttons[$i]['btn_type'][$content_type['slug']];
    }

    $checklist_html .= '<label>
                            <input type="checkbox" name="'.$name.'" value="1" '.checked(true, $checked_val, false).' aria-describedby="enp-button-content-type-description"/> '.$content_type['label_name'].'
                        </label>';

    return $checklist_html;
}


?>
