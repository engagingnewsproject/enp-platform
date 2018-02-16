<?php
use PHPUnit\Framework\TestCase;

/**
 * Functions for use by tests
 */
class EnpTestCase extends TestCase
{

    public function evaluateAssert($val, $expected) {
        if($expected === false) {
            $this->assertFalse($val);
        } else {
            $this->assertTrue($val);
        }
    }

    public function enpSetUp() {
        $_SERVER["DOCUMENT_ROOT"] = "/Users/jj/Dropbox/mamp/sites/quiz";
    }

    public function enpTearDown() {
      unset($_SERVER["DOCUMENT_ROOT"]);
    }

}
