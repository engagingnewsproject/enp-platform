<?
// we need to set a few states here to figure out what links are allowed
// And what those links should be
// $enp_current_page is set in the public/includes/class that loads the page
$quiz_id = $quiz->get_quiz_id();
$enp_create_url = (!empty($quiz_id) ? ENP_QUIZ_CREATE_URL.$quiz_id.'/' : ENP_QUIZ_CREATE_URL.'new');
$enp_create_class = ($enp_current_page === 'create' ? ' enp-quiz-breadcrumbs__link--active' : ' enp-quiz-breadcrumbs__link--disabled');
$enp_preview_url = (!empty($quiz_id) ? ENP_QUIZ_PREVIEW_URL.$quiz_id.'/' : '#');
$enp_preview_class = ($enp_current_page === 'preview' ? ' enp-quiz-breadcrumbs__link--active' : ' enp-quiz-breadcrumbs__link--disabled');
$enp_publish_url = (!empty($quiz_id) ? ENP_QUIZ_PUBLISH_URL.$quiz_id.'/' : '#');
$enp_publish_class = ($enp_current_page === 'publish' ? ' enp-quiz-breadcrumbs__link--active' : ' enp-quiz-breadcrumbs__link--disabled');

if($quiz->get_quiz_status() === 'published') {
    $enp_preview_name = 'Settings';
    $enp_publish_name = 'Embed';
} else {
    $enp_preview_name = 'Preview';
    $enp_publish_name = 'Publish';
}
?>


<nav id="enp-quiz-breadcrumbs" class="enp-quiz-breadcrumbs">
    <ul class="enp-quiz-breadcrumbs__list">
        <li class="enp-quiz-breadcrumbs__item">
            <a href="<?php echo $enp_create_url;?>"
               class="enp-quiz-breadcrumbs__link<?php echo $enp_create_class;?>">
               Create
            </a>
        </li>
        <li class="enp-quiz-breadcrumbs__item"><svg class="enp-icon">
         <use xlink:href="#icon-chevron-right" />
        </svg></li>
        <li class="enp-quiz-breadcrumbs__item">
            <a class="enp-quiz-breadcrumbs__link enp-quiz-breadcrumbs__link--preview<?php echo $enp_preview_class;?>" href="<? echo $enp_preview_url;?>"><?echo $enp_preview_name;?></a>
        </li>
        <li class="enp-quiz-breadcrumbs__item"><svg class="enp-icon">
         <use xlink:href="#icon-chevron-right" />
        </svg></li>
        <li class="enp-quiz-breadcrumbs__item">
            <a class="enp-quiz-breadcrumbs__link enp-quiz-breadcrumbs__link--publish<?php echo $enp_publish_class;?>" href="<? echo $enp_publish_url;?>"><?echo $enp_publish_name;?></a>
        </li>
    </ul>
</nav>
