<?php
// we need to set a few states here to figure out what links are allowed
// And what those links should be
// $enp_current_page is set in the public/includes/class that loads the page
$breadcrumbs = new Enp_quiz_Breadcrumbs($enp_current_page, $quiz->get_quiz_id(), $quiz->get_quiz_status());

?>


<nav id="enp-quiz-breadcrumbs" class="enp-quiz-breadcrumbs">
    <ul class="enp-quiz-breadcrumbs__list">
        <li class="enp-quiz-breadcrumbs__item">
            <?php echo $breadcrumbs->get_create_link();?>
        </li>
        <li class="enp-quiz-breadcrumbs__item"><svg class="enp-icon">
         <use xlink:href="#icon-chevron-right" />
        </svg></li>
        <li class="enp-quiz-breadcrumbs__item">
            <?php echo $breadcrumbs->get_preview_link();?>
        </li>
        <li class="enp-quiz-breadcrumbs__item"><svg class="enp-icon">
         <use xlink:href="#icon-chevron-right" />
        </svg></li>
        <li class="enp-quiz-breadcrumbs__item">
            <?php echo $breadcrumbs->get_publish_link();?>
        </li>
    </ul>
</nav>
