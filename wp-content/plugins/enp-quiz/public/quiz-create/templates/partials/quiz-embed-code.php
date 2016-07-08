<p>Copy and paste the embed code onto your website where you'd like it to appear.</p>
<label class="enp-label" for="enp-embed-code">Embed Code</label>
<textarea id="enp-embed-code" class="enp-embed-code enp-publish-page__embed-code" rows="7" readonly><script type="text/javascript" src="<?php echo ENP_QUIZ_PLUGIN_URL;?>public/quiz-take/js/dist/iframe-parent.min.js"></script>
<iframe id="enp-quiz-iframe-<?php echo $quiz->get_quiz_id();?>" class="enp-quiz-iframe" src="<?php echo ENP_QUIZ_URL.$quiz->get_quiz_id();?>" style="width: <? echo $quiz->get_quiz_width();?>; height: 500px;"></iframe>
</textarea>
