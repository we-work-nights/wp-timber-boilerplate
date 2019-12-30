<?php

if ( ! class_exists( 'Timber' ) ) {
	add_action( 'admin_notices', function() {
		echo '<div class="error"><p>Timber not activated. Make sure you activate the plugin in <a href="' . esc_url( admin_url( 'plugins.php#timber' ) ) . '">' . esc_url( admin_url( 'plugins.php') ) . '</a></p></div>';
	});

	add_filter('template_include', function($template) {
		return get_stylesheet_directory() . '/static/no-timber.html';
	});

	return;
}

Timber::$dirname = array('templates', 'views');

class wwnFunctions extends TimberSite {

	function __construct() {
		add_theme_support( 'post-formats' );
		add_theme_support( 'post-thumbnails' );
		add_theme_support( 'menus' );
		add_filter( 'timber_context', array( $this, 'add_to_context' ) );
		add_filter( 'get_twig', array( $this, 'add_to_twig' ) );
		add_action( 'init', array( $this, 'register_menus' ) );
		add_action( 'init', array( $this, 'register_post_types' ) );
		add_action( 'init', array( $this, 'register_taxonomies' ) );
		add_action( 'init', array( $this, 'register_options' ) );
		add_action( 'init', array( $this, 'styles_scripts' ) );
		parent::__construct();
	}

	function register_menus() {
		require('inc/register-menus.php');
	}

	function register_options() {
		require('inc/register-options.php');
	}

	function styles_scripts() {
		require('inc/styles-scripts.php');
  }

	function add_to_context( $context ) {

		// Set WP menus as context variables
		$context['main_nav'] = new TimberMenu('main_nav');
		$context['footer_nav'] = new TimberMenu('footer_nav');

		// Set ACF options section as context variable
    	// $context['options'] = get_fields('option');

		// Set up an archive object with date info for all posts
		$archive_args = array(
		  'post_type' => 'post'
		);
		$context['archives'] = new TimberArchives( $archive_args );

		// Set up an categories object with all non-empty categories
		$categories_args = get_categories( array(
			'orderby' => 'name',
			'order'   => 'ASC',
			'hide_empty' => '1'
		) );
		$context['categories'] = $categories_args;

		return $context;
	}

	function add_to_twig( $twig ) {
		$twig->addExtension( new Twig_Extension_StringLoader() );
		return $twig;
	}
}

new wwnFunctions();

// Other function files
require get_template_directory() . '/inc/emoji.php';


// Google Analytics snippet from HTML5 Boilerplate
// Cookie domain is 'auto' configured. See: http://goo.gl/VUCHKM

define('GOOGLE_ANALYTICS_ID', 'UA-XXXXXX-X');
function mtn_google_analytics() { ?>
<script>
  <?php if (WP_ENV === 'production') : ?>
	(function(b,o,i,l,e,r){b.GoogleAnalyticsObject=l;b[l]||(b[l]=
	function(){(b[l].q=b[l].q||[]).push(arguments)});b[l].l=+new Date;
	e=o.createElement(i);r=o.getElementsByTagName(i)[0];
	e.src='//www.google-analytics.com/analytics.js';
	r.parentNode.insertBefore(e,r)}(window,document,'script','ga'));
  <?php else : ?>
	function ga() {
	  console.log('GoogleAnalytics: ' + [].slice.call(arguments));
	}
  <?php endif; ?>
  ga('create','<?php echo GOOGLE_ANALYTICS_ID; ?>','auto');ga('send','pageview');
</script>

<?php }

if (GOOGLE_ANALYTICS_ID && (WP_ENV !== 'production' || !current_user_can('manage_options'))) {
  add_action('wp_footer', 'mtn_google_analytics', 20);
}


// Extend WordPress search to include custom fields
// http://adambalee.com

function cf_search_join( $join ) {
	global $wpdb;

	if ( is_search() ) {
		$join .=' LEFT JOIN '.$wpdb->postmeta. ' ON '. $wpdb->posts . '.ID = ' . $wpdb->postmeta . '.post_id ';
	}

	return $join;
}
add_filter('posts_join', 'cf_search_join' );

function cf_search_where( $where ) {
	global $pagenow, $wpdb;

	if ( is_search() ) {
		$where = preg_replace(
			"/\(\s*".$wpdb->posts.".post_title\s+LIKE\s*(\'[^\']+\')\s*\)/",
			"(".$wpdb->posts.".post_title LIKE $1) OR (".$wpdb->postmeta.".meta_value LIKE $1)", $where );
	}

	return $where;
}
add_filter( 'posts_where', 'cf_search_where' );

function cf_search_distinct( $where ) {
	global $wpdb;

	if ( is_search() ) {
		return "DISTINCT";
	}

	return $where;
}
add_filter( 'posts_distinct', 'cf_search_distinct' );
