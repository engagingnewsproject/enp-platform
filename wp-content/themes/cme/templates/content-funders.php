
<?php
if( empty($funders) ) { $funders = get_post_funders(); }
?>

<ul class="enp-author-list">
    <?php
    foreach( $funders as $organization ) :
    ?>
    <li class="clearfix" id="funders-list">
        <figure><img src="<?php echo get_field("organization_image", $organization->ID); ?>" alt="<?php echo $organization->post_title; ?>" width="90"></figure>
        <?php echo wp_get_attachment_image( $organization->ID, 'thumbnail' ); ?>
        <div class="author-content">
            <div class="entry-header" id="<?php echo $organization->post_name; ?>"><p><span class="author-name"><strong><?php echo $organization->post_title; ?></strong></span><br>
            </div>
        </div>
    </li>

    <?php endforeach; ?>
</ul>
