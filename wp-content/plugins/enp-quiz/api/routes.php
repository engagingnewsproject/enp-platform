<?php
// @usage /wp-json/enp-quiz/v1/sites/{siteID}
// add emmis_related_content_ids call
add_action( 'rest_api_init', function () {
    $version = '1';
    $namespace = 'enp-quiz/v'.$version;

  register_rest_route( $namespace, '/sites', array(
    'methods'   => 'GET',
    'callback'  => 'getQuizSitesAPI',
    'args'      => getQuizSitesAPIArgs()
  ) );

  register_rest_route( $namespace, '/sites/(?P<siteID>\d+)', array(
    'methods'   => 'GET',
    'callback'  => 'getQuizSiteAPI',
    'args'      => getQuizSiteAPIArgs()
  ) );


  register_rest_route( $namespace, '/sites/(?P<siteID>\d+)/embeds', array(
    'methods'   => 'GET',
    'callback'  => 'getQuizEmbedsAPI',
    'args'      => getQuizEmbedsAPIArgs()
  ) );

  register_rest_route( $namespace, '/embeds/', array(
    'methods'   => 'GET',
    'callback'  => 'getQuizEmbedsAPI',
    'args'      => getQuizEmbedsAPIArgs()
  ) );

  register_rest_route( $namespace, '/embeds/(?P<embedID>\d+)', array(
    'methods'   => 'GET',
    'callback'  => 'getQuizEmbedAPI',
    'args'      => getQuizEmbedAPIArgs()
  ) );

  // duplicate of the above one, just with a sites base url
  register_rest_route( $namespace, '/sites/(?P<siteID>\d+)/embeds/(?P<embedID>\d+)', array(
    'methods'   => 'GET',
    'callback'  => 'getQuizEmbedAPI',
    'args'      => getQuizEmbedAPIArgs()
  ) );

  register_rest_route( $namespace, '/quizzes/', array(
    'methods'   => 'GET',
    'callback'  => 'getQuizzesAPI',
    'args'      => getQuizzesAPIArgs()
  ) );

  register_rest_route( $namespace, '/quizzes/(?P<quizID>\d+)', array(
    'methods'   => 'GET',
    'callback'  => 'getQuizAPI',
    'args'      => getQuizAPIArgs()
  ) );

  register_rest_route( $namespace, '/totals/', array(
    'methods'   => 'GET',
    'callback'  => 'getQuizTotalsAPI',
    'args'      => getQuizTotalsAPIArgs()
  ) );
} );

function getQuizSitesAPI($request) {
  $db = new enp_quiz_Db();
  $where = [];

  // exclude dev sites by default
  /*$where['embed_site_is_dev'] = '0';
  if(isset($request['include_dev']) && $request['include_dev']) {
    unset($where['embed_site_is_dev']);
  }*/

  $dbSites = $db->getSites($where); 

  $sites = [];
  foreach($dbSites as $site) {
    $sites[] = new Enp_quiz_Embed_site($site['embed_site_id']);
  }

  return $sites;
}

function getQuizSitesAPIArgs() {
    $args = [];
    // Here we are registering the schema for the filter argument.
    // how many quizzes do they need published? (this is they have at least # of quizzes)
    /*$args['quizzes'] = [
        // description should be a human readable description of the argument.
        'description' => 'How many quizzes does the site need to have embedded?',
        // type specifies the type of data that the argument should be.
        'type'        => 'integer',
        // enum specified what values filter can take on.
        // 'enum'        => array( 'default', 'amp' ),
    ];

    // what categories is this site a part of?
    $args['type'] = [
        // description should be a human readable description of the argument.
        'description' => 'What category do you want sites from?',
        // type specifies the type of data that the argument should be.
        'type'        => 'string',
        // enum specified what values filter can take on.
        // 'enum'        => array( 'default', 'amp' ),
    ];*/

    $args['include_dev'] = [
        // description should be a human readable description of the argument.
        'description' => 'Include dev sites?',
        // type specifies the type of data that the argument should be.
        'type'        => 'boolean',
        // enum specified what values filter can take on.
        // 'enum'        => array( 'default', 'amp' ),
    ];
    return $args;
}

function getQuizSiteAPI($request) {
  $site =  new Enp_quiz_Embed_site($request['siteID']);
  return $site;
}

function getQuizSiteAPIArgs() {
    $args =[];
    return $args;
}

function getQuizEmbedsAPI($request) {
  $db = new enp_quiz_Db();
  $where = [];
  
  if($request['siteID']) {
    $where = ['embed_site_id' => $request['siteID']];
  }
  if($request['quizID']) {
    $where = ['quiz_id' => $request['quizID']];
  }
  // exclude dev sites by default
  /*$where['embed_quiz_is_dev'] = '0';
  if(isset($request['include_dev']) && $request['include_dev']) {
    unset($where['embed_quiz_is_dev']);
  }*/

  $dbEmbeds = $db->getEmbeds($where); 

  $embeds = [];
  foreach($dbEmbeds as $embed) {
    $embeds[] = new Enp_quiz_Embed_quiz($embed['embed_quiz_id']);
  }

  return $embeds;
}


function getQuizEmbedsAPIArgs() {
    $args = [];
    // Here we are registering the schema for the filter argument.

    $args['include_dev'] = [
        // description should be a human readable description of the argument.
        'description' => 'Include dev site embeds?',
        // type specifies the type of data that the argument should be.
        'type'        => 'boolean',
        // enum specified what values filter can take on.
        // 'enum'        => array( 'default', 'amp' ),
    ];
    return $args;
}

function getQuizEmbedAPI($request) {
  return new Enp_quiz_Embed_quiz($request['embedID']);
}

function getQuizEmbedAPIArgs() {
    $args =[];
    return $args;
}

function getQuizzesAPI($request) {
  $db = new enp_quiz_Db();
  $where = [];
  
  // exclude dev sites by default
  /*$where['embed_quiz_is_dev'] = '0';
  if(isset($request['include_dev']) && $request['include_dev']) {
    unset($where['embed_quiz_is_dev']);
  }*/

  $dbQuizzes = $db->getQuizzes($where); 

  $quizzes = [];
  foreach($dbQuizzes as $dbQuiz) {
    $quiz = new Enp_quiz_Quiz($dbQuiz['quiz_id']);
    $quizzes[] = $quiz;
  }

  return $quizzes;
}


function getQuizzesAPIArgs() {
    $args = [];
    // Here we are registering the schema for the filter argument.

    $args['include_dev'] = [
        // description should be a human readable description of the argument.
        'description' => 'Include dev site embeds?',
        // type specifies the type of data that the argument should be.
        'type'        => 'boolean',
        // enum specified what values filter can take on.
        // 'enum'        => array( 'default', 'amp' ),
    ];
    return $args;
}

function getQuizAPI($request) {
  $quiz = new Enp_quiz_Quiz($request['quizID']);
  $quiz->scores = $quiz->get_quiz_scores_group_count();
  return $quiz;
}

function getQuizAPIArgs() {
    $args =[];
    return $args;
}

function getQuizGlobalStats() {
  // total responses
  // total responses correct
  // total responses incorrect
  // total questions
  // total responses
  // published quizzes
  // site
}

function getQuizTotalsAPI($request) {
  $db = new enp_quiz_Db();
  // total responses
  // total responses correct
  // total responses incorrect
  // total questions
  // total responses
  // published quizzes
  // site
  $responsesCorrect = $db->getResponsesCorrectTotal();
  $responsesIncorrect = $db->getResponsesIncorrectTotal();
  $responsesTotal = $responsesCorrect + $responsesIncorrect;

  $sliderQuestions = $db->getSliderQuestionsTotal();
  $mcQuestions = $db->getMCQuestionsTotal();
  $questionsTotal = $sliderQuestions + $mcQuestions;

  return [
    'responses' => [
      'mc'          => [
        
      ],
      'slider'      => [

      ],
      'correct'     => $responsesCorrect,
      'incorrect'   => $responsesIncorrect,
      'total'       => $responsesTotal,
      'users'      => $db->getUniqueUsersTotal()
    ],
    'questions' => [
      'description' => 'Published quiz question totals.',
      'mc'          => $mcQuestions,
      'slider'      => $sliderQuestions,
      'total'       => $questionsTotal
    ]
  ];
}

function getQuizTotalsAPIArgs() {
    $args =[];
    return $args;
}

/**
 * Pass an object and what keys you'd like removed.
 * This will remove the keys and turn it into an array to return
 *
 *
 */
/*function enpQuizArrayify($object, $removeKeys = []) {

      $return = [];
      // loop the properties to make it into an array
      foreach($object as $property => $val) {
          $return[$property] = $val;
      }
      // unset properties, and anything else
      if($removeKeys) {
        foreach($removeKeys as $key) {
          unset($return[$key]);
        }
      }

      return $return;
}*/