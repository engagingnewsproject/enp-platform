<?
// we need to set a few states here to figure out what links are allowed
// And what those links should be
// $enp_current_page is set in the public/includes/class that loads the page
$quiz_id = $quiz->get_quiz_id();
$quiz_status = $quiz->get_quiz_status();
$enp_create_url = (!empty($quiz_id) ? ENP_QUIZ_CREATE_URL.$quiz_id.'/' : ENP_QUIZ_CREATE_URL.'new');

$enp_create_class = '';
$enp_preview_class = '';
$enp_publish_class = '';

// create classes
if($enp_current_page === 'create') {
    $enp_create_class = ' enp-quiz-breadcrumbs__link--active';

    if($quiz_status !== 'published') {
        $enp_publish_class = ' enp-quiz-breadcrumbs__link--disabled';
    }
}

$enp_preview_url = (!empty($quiz_id) ? ENP_QUIZ_PREVIEW_URL.$quiz_id.'/' : '#');

// preview classes
if($enp_current_page === 'preview') {
    $enp_preview_class = ' enp-quiz-breadcrumbs__link--active';
}
if(empty($quiz_id)) {
    $enp_preview_class .= ' enp-quiz-breadcrumbs__link--disabled';
}

$enp_publish_url = (!empty($quiz_id) ? ENP_QUIZ_PUBLISH_URL.$quiz_id.'/' : '#');
// publish class
if($enp_current_page === 'publish') {
    $enp_publish_class = ' enp-quiz-breadcrumbs__link--active';
}

if($quiz_status === 'published') {
    $enp_preview_name = 'Settings';
    $enp_publish_name = 'Embed';
    $enp_create_name = 'Edit';
} else {
    $enp_create_name = 'Create';
    $enp_preview_name = 'Preview';
    $enp_publish_name = 'Publish';
}
?>


<nav id="enp-quiz-breadcrumbs" class="enp-quiz-breadcrumbs">
    <ul class="enp-quiz-breadcrumbs__list">
        <li class="enp-quiz-breadcrumbs__item">
            <a href="<?php echo $enp_create_url;?>"
               class="enp-quiz-breadcrumbs__link<?php echo $enp_create_class;?>">
               <?php echo $enp_create_name;?>
            </a>
        </li>
        <li class="enp-quiz-breadcrumbs__item"><svg class="enp-icon">
         <use xlink:href="#icon-chevron-right" />
        </svg></li>
        <li class="enp-quiz-breadcrumbs__item">
            <a class="enp-quiz-breadcrumbs__link enp-quiz-breadcrumbs__link--preview<?php echo $enp_preview_class;?>" href="<?php echo $enp_preview_url;?>"><?php echo $enp_preview_name;?></a>
        </li>
        <li class="enp-quiz-breadcrumbs__item"><svg class="enp-icon">
         <use xlink:href="#icon-chevron-right" />
        </svg></li>
        <li class="enp-quiz-breadcrumbs__item">
            <a class="enp-quiz-breadcrumbs__link enp-quiz-breadcrumbs__link--publish<?php echo $enp_publish_class;?>" href="<?php echo $enp_publish_url;?>"><?php echo $enp_publish_name;?></a>
        </li>
    </ul>
</nav>
