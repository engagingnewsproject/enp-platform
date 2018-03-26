<?php while (have_posts()) : the_post(); ?>
<section class="home-layout">
    <section id="featured-content" class="container">
        <div class="row">
            <?php $featured = get_field('featured_post'); ?>
            <figure class="col-sm-6 featured-image">
                <?php echo get_the_post_thumbnail( $featured->ID, 'featured-post' ); ?>
            </figure>
            <div class="col-sm-6 featured-content">
                <div class="category"><?php echo get_the_term_list( $featured->ID, 'research-categories', '', ', ', '' ); ?></div>
                <h1 class="headline"><a href="<?php echo get_the_permalink($featured->ID); ?>"><?php echo $featured->post_title; ?></a></h1>
                <div class="entry-summary">
                    <p><?php echo $featured->post_excerpt; ?></p>
                </div>
                <p><a href="<?php echo get_the_permalink($featured->ID); ?>">Read more &rarr;</a></p>
            </div>
        </div>
    </section> <!-- END .featured-content -->
    <section class="enp-related-research">
        <div class="container">
            <!-- related research widget -->

            <section class="widget enp-widget-row">
                <?php
                $enp_frr = get_field('enp_featured_related_research');
                if(!empty($enp_frr)) {
                    echo do_shortcode('[enp-list-posts
					title="Related Research"
					type=research
				    include='.implode($enp_frr, ',').'
					excerpt=true
					posts='.count($enp_frr).']');
                } else {
                    enp_list_related_research($featured->ID, 3, 'true');
                }
                ?>
            </section>
            <?php dynamic_sidebar('sidebar-home'); ?>
        </div>
    </section>
    <section class="enp-latest">
        <div class="container widget">
            <h3>Latest</h3>
            <div class="row">
                <?php
                $recent_posts = wp_get_recent_posts(array('numberposts'=>3, 'post_status' => 'publish'));
                foreach( $recent_posts as $recent ){ ?>
                <div class="col-sm-4 clearfix"><figure><?php echo get_the_post_thumbnail( $recent["ID"], 'thumbnail' ); ?></figure>
                    <a href="<?= get_permalink($recent["ID"]) ?>"><?= $recent["post_title"] ?></a><br>
                    <time><?= get_the_date('', $recent["ID"])?></time>
                </div>
                <?php }
                ?>
            </div>
        </div>
    </section>

    <section id="about" class="callout">
        <div class="container">
            <div class="col-md-10 col-md-offset-1">
                <div class="row">
                    <?php the_field('homepage_about'); ?>
                </div>
            </div>
        </div>
    </section>

    <section id="funders">

        <h2>Funders and Partners</h2>

        <div class="container center-align">

            <?php
            //Columns must be a factor of 12 (1,2,3,4,6,12)
            $numOfCols = 4;
            $rowCount = 0;
            $bootstrapColWidth = 12 / $numOfCols;
            $fieldCount = -1;

            if( empty($funders) ) { $funders = get_post_funders(); }
            foreach($funders as $organization ) {
                $fieldCount++;
            }
            ?>
            <div class="row">
                <?php foreach( $funders as $organization ) : ?>
                <?php
                if($rowCount % $numOfCols != 3 && $rowCount != $fieldCount) {
                    $orgClass = "border-org";
                } else {
                    $orgClass = "";
                }

                ?>
                <div class="col-sm-<?php echo $bootstrapColWidth ?> organization <?php echo $orgClass?>">
                    <div class="col-internal">
                        <figure><img src="<?php echo get_field("organization_image", $organization->ID); ?>" alt="<?php echo $organization->post_title; ?>"></figure>
                        <?php echo wp_get_attachment_image( $organization->ID, 'thumbnail' ); ?>
                        <div class="author-content">
                            <div class="">
                                <!-- <p><?php echo $organization->post_title; ?></p> -->
                            </div>
                        </div>
                    </div>
                </div>

                <?php
                $rowCount++;
                if($rowCount % $numOfCols == 0) { echo '</div><div class="row">';}
                ?>
                <?php endforeach; ?>
            </div>


        </div>


    </section>

    <section class="section-layout">

        <?php //get_template_part('templates/page', 'header'); ?>
        <?php //get_template_part('templates/content', 'page'); ?>

    </section>

</section>
<?php endwhile; ?>
