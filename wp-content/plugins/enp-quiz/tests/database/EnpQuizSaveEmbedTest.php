<?php
use PHPUnit\Framework\TestCase;

/**
 * @covers Enp_quiz_Save_embed
 */
final class EnpQuizSaveEmbedTest extends EnpTestCase
{
    protected static $save_embed_site;

    protected function setUp()
    {
        $this->enpSetUp();

    }

    public function tearDown() {
      $this->enpTearDown();
    }

    /**
     * @covers Enp_quiz_Save_embed->save_embed_site()
     * @covers Enp_quiz_Save_embed->save_embed_quiz()
     * @covers Enp_quiz_Save_embed->decode()
     * @covers Enp_quiz_Save_embed->get_response()
     * @dataProvider saveEmbedSiteProvider
     */
    public function testSaveEmbedSite($embed_save, $expected) {
        $type = $embed_save['save'];
        // create a new cURL resource
        $ch = curl_init();

        // set URL and other appropriate options
        curl_setopt($ch, CURLOPT_URL, "http://local.quiz/wp-content/plugins/enp-quiz/database/class-enp_quiz_save_embed.php");
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($embed_save));

        // grab URL and pass it to the browser
        $response = curl_exec($ch);

        // close cURL resource, and free up system resources
        curl_close($ch);

        $response = json_decode($response);
        $response = (array) $response;

        // quick check to make sure everything is OK
        $validate = new Enp_quiz_Save();

        if(!$validate->has_errors($response)) {
            $response[$type.'_url'] = urldecode($response[$type.'_url']);
            // unset the variables we can't control
            unset($response[$type.'_updated_at']);
            unset($response[$type.'_id']);
        }


        $this->assertEquals($response, $expected);
    }

    public function saveEmbedSiteProvider() {
        $date = date("Y-m-d H:i:s");
        $embed_site_url = 'http://www.'.uniqid('test-case-').'.com';
        $embed_quiz_url = 'http://jeremyjon.es/'.uniqid('test-case_');

        return [
                'valid_embed_site'=>[
                            array(
                                'save' => 'embed_site',
                                'action'=>'insert',
                                'embed_site_name'=>'Test Case',
                                'embed_site_url'=> urlencode($embed_site_url),
                                'doing_ajax' => 'true'
                            ),
                            array(
                                'save' => 'embed_site',
                                'action'=>'insert',
                                'status' => 'success',
                                'embed_site_name'=>'Test Case',
                                'embed_site_url'=> $embed_site_url,
                                'doing_ajax' => 'true',
                                'error'=>array(),
                            )],
                'invalid_embed_site_url'=>[
                            array(
                                'save' => 'embed_site',
                                'action'=>'insert',
                                'embed_site_name'=>'Test Case',
                                'embed_site_url'=> urlencode('applewebdata://'.$embed_site_url),
                                'doing_ajax' => 'true'
                            ),
                            array(
                                'error'=>array('Invalid Site URL.'),
                            )],
                'valid_embed_quiz_insert'=>[
                            array(
                                'save' => 'embed_quiz',
                                'quiz_id'=>'1',
                                'embed_site_id'=>'1',
                                'embed_quiz_url'=> urlencode($embed_quiz_url),
                                'doing_ajax' => 'true'
                            ),
                            array(
                                'save' => 'embed_quiz',
                                'action'=>'insert',
                                'quiz_id'=>'1',
                                'embed_site_id'=>'1',
                                'embed_quiz_url'=> $embed_quiz_url,
                                'doing_ajax' => 'true',
                                'status' => 'success',
                                'error'=>array(),
                            )],
                'valid_embed_quiz_save_load'=>[
                            array(
                                'save' => 'embed_quiz',
                                'embed_quiz_url'=> urlencode('https://jeremyjon.es/quizzes'),
                                'quiz_id'=>'44',
                                'doing_ajax' => 'true'
                            ),
                            array(
                                'save' => 'embed_quiz',
                                'action'=>'updated_quiz_embed_loads',
                                'embed_quiz_url'=> 'http://jeremyjon.es/quizzes',
                                'quiz_id'=>'44',
                                'doing_ajax' => 'true',
                                'status' => 'success',
                                'error'=>array(),
                            )],
                'invalid_embed_quiz_url'=>[
                            array(
                                'save' => 'embed_quiz',
                                'quiz_id'=>'1',
                                'embed_site_id'=>'1',
                                'embed_quiz_url'=> urlencode('applewebdata://'.$embed_quiz_url),
                                'doing_ajax' => 'true'
                            ),
                            array(
                                'error'=>array('Invalid Quiz URL.'),
                            )],
            ];
    }

}
