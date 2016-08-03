<form id="enp-search-quizzes" class="enp-search-quizzes" method="get" action="<?php echo htmlentities(ENP_QUIZ_DASHBOARD_URL.'user/'); ?>">
    <?php
    // set our variables for the search
    $order_by = (isset($_GET['order_by']) ? $_GET['order_by'] : '');
    $search = (isset($_GET['search']) ? $_GET['search'] : '');
    $include = (isset($_GET['include']) ? $_GET['include'] : '');
    $user_quizzes = $user->get_quizzes();
    if(!empty($user_quizzes) || current_user_can('manage_options')) {
    ?>
        <div class="enp-search-quizzes__form-item enp-quiz-search">
            <label class="enp-label enp-search-quizzes__label" for="enp-quiz-search">Search Quizzes</label>
            <input id="enp-quiz-search" class="enp-input enp-quiz-search__input" type="search" name="search" value="<?php echo $search;?>"/>
            <svg class="enp-quiz-search__icon enp-icon">
              <use xlink:href="#icon-search"><title>Search</title></use>
            </svg>
        </div>
    <?php
    }
    
    $published_quizzes = $user->get_published_quizzes();
    if(!empty($published_quizzes) || current_user_can('manage_options')) { ?>

        <div class="enp-search-quizzes__form-item">
            <label for="enp-quiz-order-by" class="enp-label enp-search-quizzes__label">Order<span class="enp-screen-reader-text"> Quizzes</span> By</label>

            <select id="enp-quiz-order-by" name="order_by" class="enp-search-quizzes__select">
                <option <?php selected( $order_by, "quiz_created_at" ); ?> value="quiz_created_at">Created at</option>
                <option <?php selected( $order_by, "quiz_score_average" ); ?> value="quiz_score_average">Average Score</option>
                <option <?php selected( $order_by, "quiz_views"); ?> value="quiz_views">Views</option>
                <option <?php selected( $order_by, "quiz_start_rate" ); ?> value="quiz_start_rate">Start Rate</option>
                <option <?php selected( $order_by, "quiz_completion_rate" ); ?> value="quiz_completion_rate">Completion Rate</option>
                <?php
                $include_draft_published = $this->include_draft_published_option($include);
                if( $include_draft_published === true  ) { ?>
                    <option <?php selected( $order_by, "draft" ); ?> value="draft">Draft</option>
                    <option <?php selected( $order_by, "published" ); ?> value="published">Published</option>
                <?php } ?>
            </select>
        </div>
    <?php
    }

    if(current_user_can('manage_options')) {
        echo '<div class="enp-search-quizzes__form-item"><label class="enp-label enp-search-quizzes__label" for="enp-quiz-include">Include</label>';
        echo '<select id="enp-quiz_include" name="include" class="enp-search-quizzes__select">
        <option '.selected( $include, "user", false ).' value="user">My Quizzes</option>
        <option '.selected( $include, "all_users", false ).' value="all_users">All User\'s Quizzes</option>
        </select></div>';
    }?>

    <button class="enp-btn enp-search-quizzes__button">Search</button>

</form>
