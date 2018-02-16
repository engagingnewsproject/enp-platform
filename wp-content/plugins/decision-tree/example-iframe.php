<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset='UTF-8'>
    <meta name='viewport' content='width=device-width, initial-scale=1'>
    <title>Example Decision Tree</title>
    <style>

        body {
            padding: 20px;
            max-width: 1000px;
            margin: 0 auto;
        }
    </style>
</head>
<body>
<main>
    <h2>Main content block</h2>
    <?php
        if($_SERVER['HTTP_HOST'] !== 'localhost:3000' && $_SERVER['HTTP_HOST'] !== 'dev' ) {
            $site = 'https://tree.mediaengagement.org';
        } else {
            $site = 'http://dev/decision-tree';
        }
    ?>
    <script src="<?php echo $site;?>/dist/js/iframe-parent.js"></script>
    <iframe width="100%" height="500px" style="border: none;" id="cme-tree__1" class="cme-tree__iframe" src="<?php echo $site;?>/api/v1/trees/citizen/iframe"></iframe>

</main>
<footer>
    <h2>Footer</h2>
</footer>
</body>
</html>
