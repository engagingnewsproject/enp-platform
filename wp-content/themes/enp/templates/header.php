<?php use Roots\Sage\Assets; ?>

<header class="banner">
  <div class="container">
    
    <div class="navbar-header navbar-default">
      <button type="button" class="navbar-toggle collapsed" data-toggle="collapse" data-target=".navbar-collapse">
           <span class="sr-only">Toggle navigation</span>
           <span class="icon-bar"></span>
           <span class="icon-bar"></span>
           <span class="icon-bar"></span>
      </button>
      <a class="brand" href="<?= esc_url(home_url('/')); ?>"><img src="<?php echo Assets\asset_path('images/enp_logo_62@2x.png'); ?>" alt="<?php bloginfo('name'); ?>" width="74" height="62"></a>
      </div>
      <div class="nav-primary navbar-default">
      <nav class="collapse navbar-collapse" role="navigation">
        <?php
        if (has_nav_menu('primary_navigation')) :
          wp_nav_menu([
            'theme_location'  => 'primary_navigation',
            'depth'             => 2,
            'menu_class'      => 'nav navbar navbar-nav',
            'items_wrap'      => '<ul id="%1$s" class="%2$s dropdown">%3$s</ul>',
            'fallback_cb'       => 'wp_bootstrap_navwalker::fallback',
            'walker'          => new wp_bootstrap_navwalker()
            ]);
        endif;
        ?>
      </nav>
      </div>
    </div>
  </div>
</header>
