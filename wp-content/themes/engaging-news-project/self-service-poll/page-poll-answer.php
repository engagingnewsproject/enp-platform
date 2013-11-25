<?php
/*
Template Name: Poll Answer
*/
?>

<div class="content">
    <?php 
    $poll = $wpdb->get_row("SELECT * FROM enp_poll WHERE ID = " . $_GET["id"] ); 
    ?>
    
    <p>Congratulations...(a) 1 year...is the correct answer!</p>
    
    <p>Thanks for taking our poll!</p>

</div> <!-- end #main_content -->

<?php get_footer(); ?>