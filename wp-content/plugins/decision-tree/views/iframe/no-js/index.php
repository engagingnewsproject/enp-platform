<?php include_once 'header.php';?>
<?php
    // on prod this would already be compiled locally and pushed - no compiling on production
    new Cme\Template\Compile('tree');
    $template = new Cme\Template\Render('tree', $tree_slug);
    echo $template->render();
?>
<?php include_once 'footer.php';?>
