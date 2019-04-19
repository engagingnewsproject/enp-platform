// invali
$invalid_quiz = array (
 "quiz_id"=> "6",
 "quiz_title"=> "Quiz title",
 "quiz_status"=>   "draft",
 "quiz_finish_message"=>   "Thanks for taking our quiz!",
 "quiz_owner"=>   "1",
 "quiz_created_by"=>   "1",
 "quiz_created_at"=>   "2016-03-30 20:24:23",
 "quiz_title_display"=>   "hide",
 "quiz_width"=>   "99rem",
 "quiz_bg_color"=>   "#fc0fc0",
 "quiz_text_color"=>   "#444444",
 "question"=> array(
                array(
                   "question_id"=>  "12",
                   "question_title"=>  "",
                   "question_image"=>  "Screen-Shot-2016-03-29-at-3.13.42-PM--original.png",
                   "question_image_alt"=>  "Screenshot",
                   "question_type"=>  "mc",
                   "mc_option"=> array(
                                    array(
                                         "mc_option_id"=> "26",
                                         "mc_option_content"=> "MC option 1",
                                         "mc_option_order"=> 0,
                                         "mc_option_correct"=> "0",
                                         "mc_option_is_deleted"=> "0"
                                     ),
                                     array(
                                          "mc_option_id"=> "28",
                                          "mc_option_content"=> "MC option 2",
                                          "mc_option_order"=> 1,
                                          "mc_option_correct"=> "0",
                                          "mc_option_is_deleted"=> "0"
                                    )
                  ),

                "question_explanation"=>  "Explain!",
                "question_is_deleted"=> 0,
                "question_order"=> 0,
                "slider"=> array(),
            ),
            array(
                      "question_id"=>  "16",
                      "question_title"=>  "",
                      "question_image"=>  "",
                      "question_image_alt"=>  "",
                      "question_type"=>  "mc",
                      "mc_option"=> array(
                                        array(
                                            "mc_option_id"=> "32",
                                            "mc_option_content"=> "",
                                            "mc_option_order"=> 0,
                                            "mc_option_correct"=> "0",
                                            "mc_option_is_deleted"=> "0"
                                        ),
                                    ),
                       "question_explanation"=>   "",
                       "question_is_deleted"=> 0,
                       "question_order"=> 1,
                       "slider"=> array(),
        ),
    ),
);

$valid_quiz = array (
 "quiz_id"=> "6",
 "quiz_title"=> "Quiz title",
 "quiz_status"=>   "draft",
 "quiz_finish_message"=>   "Thanks for taking our quiz!",
 "quiz_owner"=>   "1",
 "quiz_created_by"=>   "1",
 "quiz_created_at"=>   "2016-03-30 20:24:23",
 "quiz_title_display"=>   "hide",
 "quiz_width"=>   "99rem",
 "quiz_bg_color"=>   "#fc0fc0",
 "quiz_text_color"=>   "#444444",
 "question"=> array(
                array(
                   "question_id"=>  "12",
                   "question_title"=>  "Question 1",
                   "question_image"=>  "Screen-Shot-2016-03-29-at-3.13.42-PM--original.png",
                   "question_image_alt"=>  "Screenshot",
                   "question_type"=>  "mc",
                   "mc_option"=> array(
                                    array(
                                         "mc_option_id"=> "26",
                                         "mc_option_content"=> "MC option 1",
                                         "mc_option_order"=> 0,
                                         "mc_option_correct"=> "1",
                                         "mc_option_is_deleted"=> "0"
                                     ),
                                     array(
                                          "mc_option_id"=> "28",
                                          "mc_option_content"=> "MC option 2",
                                          "mc_option_order"=> 1,
                                          "mc_option_correct"=> "0",
                                          "mc_option_is_deleted"=> "0"
                                    )
                  ),

                "question_explanation"=>  "Explain!",
                "question_is_deleted"=> 0,
                "question_order"=> 0,
                "slider"=> array(),
            ),
            array(
                      "question_id"=>  "16",
                      "question_title"=>  "Question 2",
                      "question_image"=>  "",
                      "question_image_alt"=>  "",
                      "question_type"=>  "mc",
                      "mc_option"=> array(
                                        array(
                                            "mc_option_id"=> "32",
                                            "mc_option_content"=> "Option 1 on Q2",
                                            "mc_option_order"=> 0,
                                            "mc_option_correct"=> "0",
                                            "mc_option_is_deleted"=> "0"
                                        ),
                                        array(
                                            "mc_option_id"=> "33",
                                            "mc_option_content"=> "Option 2 on Q2",
                                            "mc_option_order"=> 1,
                                            "mc_option_correct"=> "1",
                                            "mc_option_is_deleted"=> "0"
                                        ),
                                    ),
                       "question_explanation"=>   "Explain Q2",
                       "question_is_deleted"=> 0,
                       "question_order"=> 1,
                       "slider"=> array(),
        ),
    ),
);

$response = new Enp_quiz_Save_quiz_Response();
$validate = $response->validate_quiz_and_questions($invalid_quiz);
var_dump($validate); // should return 'invalid'
var_dump($response->message['error']); // should be array of error messages
$response = new Enp_quiz_Save_quiz_Response();
$validate = $response->validate_quiz_and_questions($valid_quiz);
var_dump($validate); // should return 'valid'
var_dump($response->message['error']); // should be empty array
