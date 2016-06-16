<?php
    $quiz_id = $quiz->get_quiz_id();
    $quiz_status = $quiz->get_quiz_status();
    // see if we should go to quiz create or preview
    if($quiz_status === 'published') {
    } else {

        $quiz_primary_action_link = '<a href="'.ENP_QUIZ_CREATE_URL.$quiz_id.'">Edit</a>';
        $quiz_secondary_action_link = '<a href="'.ENP_QUIZ_PREVIEW_URL.$quiz_id.'">Preview</a>';
    }

?>

<li class="enp-dash-item enp-dash-item--<?php echo $quiz_status;?>">
    <h3 class="enp-dash-item__title"><?php echo $this->get_quiz_dashboard_item_title($quiz);?></h3>
    <div class="enp-dash-item__controls">
        <div class="enp-dash-item__status"><? echo $quiz_status;?></div>
        <ul class="enp-dash-item__nav">
            <?php
                $quiz_actions = $this->get_quiz_actions($quiz);
                foreach($quiz_actions as $quiz_action) {
                    echo '<li class="enp-dash-item__nav__item"><a href="'.$quiz_action['url'].'">'.$quiz_action['title'].'</a><?php echo $quiz_primary_action_link;?></li>';
                }
            ?>

        </ul>
    </div>
</li>
