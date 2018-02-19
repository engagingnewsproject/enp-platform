<?php
use PHPUnit\Framework\TestCase;
use Cme\Utility as Utility;
/**
 * @covers Cme_quiz_Save
 */
final class UtilityTest extends TreeTestCase
{
    /**
     * @covers Cme\Utility\is_id()
     * @dataProvider testValidateIDProvider
     */
    public function testValidateID($id, $expected) {
        // $cme_save = new Cme_quiz_Save();
        $valid = Utility\is_id($id);
        $this->evaluateAssert($valid, $expected);
    }

    public function testValidateIDProvider() {
        return [
                'valid-1'=>['123456789', true],
                'valid-2'=>[1, true],
                'valid-3'=>['999', true],
                'invalid-1'=>['0', false],
                'invalid-2'=>['h123', false],
                'invalid-3'=>['!12', false],
                'invalid-4'=>['', false],
                'invalid-5'=>[true, false],
                'invalid-6'=>[false, false]
        ];
    }

    /**
     * @covers Cme\Utility\is_slug()
     * @dataProvider testIsSlugProvider
     */
    public function testIsSlug($slug, $expected) {
        $valid = Utility\is_slug($slug);
        $this->evaluateAssert($valid, $expected);
    }

    public function testIsSlugProvider() {
        return [
                'valid-1'=>['yes', true],
                'valid-2'=>['wut-up-dawg', true],
                'valid-3'=>['of-curze-123', true],
                'invalid-1'=>['NoPe', false],
                'invalid-2'=>['notaslug!!!!', false],
                'invalid-3'=>[0, false],
                'invalid-4'=>[1, false],
                'invalid-5'=>['', false],
                'invalid-6'=>[true, false],
                'invalid-7'=>['-hi', false],
                'invalid-8'=>['hi-', false],
        ];
    }

    /**
     * @covers Cme\Utility\get_tree_slug_by_id($tree_id)
     * @dataProvider testGetTreeSlugByIdProvider
     */
    public function testGetTreeSlugById($id, $expected) {
        // $cme_save = new Cme_quiz_Save();
        $tree_slug = Utility\get_tree_slug_by_id($id);
        $this->assertEquals($tree_slug, $expected);
    }

    public function testGetTreeSlugByIdProvider() {
        return [
                'valid-1'=>[1, 'citizen'],
                'valid-2'=>['1', 'citizen'],
                'invalid-1'=>['alwiheawra848aasdlkfnalsdkfnadf', null],
                'invalid-2'=>['0', null],
                'invalid-4'=>['', false],
                'invalid-5'=>[true, false],
                'invalid-6'=>[false, false]
        ];
    }

    /**
     * @covers Cme\Utility\get_tree_id_by_slug($tree_id)
     * @dataProvider testGetTreeIdBySlugProvider
     */
    public function testGetTreeIdBySlug($slug, $expected) {
        $tree_id = Utility\get_tree_id_by_slug($slug);
        $this->assertEquals($tree_id, $expected);
    }

    public function testGetTreeIdBySlugProvider() {
        return [
                'valid-1'=>['citizen', '1'],
                'invalid-1'=>['alwiheawra848aasdlkfnalsdkfnadf', null],
                'invalid-4'=>['', false],
                'invalid-5'=>[true, false],
                'invalid-6'=>[false, false]
        ];
    }
}
