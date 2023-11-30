<?php
namespace Engage\Models;

use Timber\Term;
// Not sure if we need this class/file 
// if the ResearchArticle.php file can get everything we need.
class VerticalTerm extends Term {

	public function init($termID = null)
    {
        parent::__construct($termID);
        var_dump( 'VerticalTerm.php --> init' );
    }
}