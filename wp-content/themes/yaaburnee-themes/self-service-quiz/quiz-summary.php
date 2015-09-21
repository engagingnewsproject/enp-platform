  <h3 style="margin-top:0;"><span class="glyphicon glyphicon-check"></span> Quiz Completed!</h3><!-- ||KVB -->
  <div class="alert alert-success" style="width:80%;">
    <p><span class="summary-message_top" id="summary_message_top"><?php echo $summary_message_top; ?></span></p>
      <p><span class="summary-message" id="summary_message"><?php echo ($summary_message) ? $summary_message: 'Thanks for taking our quiz!'; ?></span></p>
  </div>

  <script>

    // We only want this code to run on the iframe page
    if(document.querySelector('.quiz-iframe')) {
      var correctAnswers = 0;
      var incorrectAnswers = 0;
      var answersLength = (parseInt(localStorage.length));
      var numAnswers = 0;
      for (var i = 0; i < answersLength; i++){
          if (localStorage.getItem(localStorage.key(i)) == 'correct') {
              correctAnswers++;
              numAnswers++;
          }
          if (localStorage.getItem(localStorage.key(i)) == 'incorrect') {
              incorrectAnswers++;
              numAnswers++;
          }
      }
    } else {
      // we're on the summary page, so grab localStorage.getItem('questionCount') instead
      var numAnswers = localStorage.getItem('questionCount');
      // let's be optimistic!
      var correctAnswers = localStorage.getItem('questionCount');
    }

    var contentString = 'You got ' + correctAnswers + ' out of ' + numAnswers + ' correct!';
    document.getElementById('summary_message_top').innerHTML = contentString;
  </script>
