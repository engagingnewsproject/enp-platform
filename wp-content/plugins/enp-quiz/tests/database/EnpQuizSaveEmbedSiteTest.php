<?php
use PHPUnit\Framework\TestCase;

/**
 * @covers Enp_quiz_Save_embed_site
 */
final class EnpQuizSaveEmbedSiteTest extends EnpTestCase
{
    protected static $save_embed_site;

    protected function setUp()
    {
        $this->enpSetUp();
        self::$save_embed_site = new Enp_quiz_Save_embed_site();
    }

    public function tearDown() {
      $this->enpTearDown();
    }

    /**
     * @covers Enp_quiz_Save_embed_site->save_embed_site()
     * @covers Enp_quiz_Save_embed_site->sanitize_embed_site()
     * @covers Enp_quiz_Save_embed_site->validate_before_insert()
     * @covers Enp_quiz_Save_embed_site->insert_embed_site()
     * @dataProvider saveEmbedSiteProvider
     */
    public function testSaveEmbedSiteInsert($embed_save, $expected) {
        $response = self::$save_embed_site->save_embed_site($embed_save);

        // if $response['embed_site_id'] exists
        // && $response['embed_site_id'] is a valid ID
        // && there are no errors
        // then it's valid = true
        if( array_key_exists('embed_site_id', $response) && self::$save_embed_site->is_id($response['embed_site_id']) && empty(self::$save_embed_site->has_errors($response)) ) {
            $valid = true;
        } else {
            $valid = false;
        }
        $this->evaluateAssert($valid, $expected);
    }

    public function saveEmbedSiteProvider() {
        $date = date("Y-m-d H:i:s");

        return [
                'valid_no_name'=>[array(
                                'action'=>'insert',
                                'embed_site_name'=>'Test Case',
                                'embed_site_url'=>'http://www.'.uniqid('test-case-').'.com',
                                'embed_site_updated_at'=>$date
                            ),
                         true],
                'valid_with_name'=>[array(
                                 'action'=>'insert',
                                 'embed_site_name'=>'Test Case',
                                 'embed_site_url'=>'http://www.'.uniqid('test-case-').'.com',
                                 'embed_site_updated_at'=>$date
                             ),
                          true],
                'valid_existing_site_url'=>[array(
                                    'action'=>'insert',
                                    'embed_site_name'=>'Test Case',
                                    'embed_site_url'=>'http://jeremyjon.es',
                                    'embed_site_updated_at'=>$date
                                ),
                             true],
                'invalid_url'=>[array(
                                        'action'=>'insert',
                                        'embed_site_name'=>'Test Case',
                                        'embed_site_url'=>'www.wut.com',
                                        'embed_site_updated_at'=>$date
                                    ),
                                 false],
                'invalid_action'=>[array(
                                         'action'=>'wut',
                                         'embed_site_name'=>'Test Case',
                                         'embed_site_url'=>'http://www.'.uniqid('test-case-').'.com',
                                         'embed_site_updated_at'=>$date
                                     ),
                                  false],
                'invalid_date'=>[array(
                                           'action'=>'insert',
                                           'embed_site_name'=>'Test Case',
                                           'embed_site_url'=>'http://www.'.uniqid('test-case-').'.com',
                                           'embed_site_updated_at'=>'2017-04-31 02:45:29'
                                       ),
                                    false],
                'invalid_no_name_set'=>[array(
                                         'action'=>'insert',
                                         'embed_site_url'=>'http://www.'.uniqid('test-case-').'.com',
                                         'embed_site_updated_at'=>$date
                                     ),
                                  false],
                'invalid_empty_name'=>[array(
                                         'action'=>'insert',
                                         'embed_site_name'=>'',
                                         'embed_site_url'=>'http://www.'.uniqid('test-case-').'.com',
                                         'embed_site_updated_at'=>$date
                                     ),
                                  false],
                ];

    }

}
