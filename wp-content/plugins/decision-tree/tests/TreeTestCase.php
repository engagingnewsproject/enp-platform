<?php
use PHPUnit\Framework\TestCase;

/**
 * Functions for use by tests
 */
class TreeTestCase extends TestCase
{

    public function evaluateAssert($val, $expected) {
        if($expected === false) {
            $this->assertFalse($val);
        } else {
            $this->assertTrue($val);
        }
    }

    public function treeSetUp() {
        // $_SERVER["DOCUMENT_ROOT"] = "/Users/jj/Dropbox/mamp/sites/quiz";
    }

    public function treeTearDown() {
      // unset($_SERVER["DOCUMENT_ROOT"]);
    }

}
