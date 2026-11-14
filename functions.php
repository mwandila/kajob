<?php
/**
 * Kajob theme bootstrap.
 *
 * @package Kajob
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/** Freemius SDK integration for Kajob licensing and updates. */
if ( ! function_exists( 'kaj_fs' ) ) {
	function kaj_fs() {
		global $kaj_fs;

		if ( ! isset( $kaj_fs ) ) {
			// Load the bundled SDK directly; this theme does not use a root Composer autoloader.
			require_once get_template_directory() . '/vendor/freemius/start.php';

			$kaj_fs = fs_dynamic_init( array(
				'id'                  => '44814',
				'slug'                => 'kajob',
				'type'                => 'theme',
				'public_key'          => 'pk_2ae5220d38caa7ce760f87d7c7c6e',
				'is_premium'          => true,
				'premium_suffix'      => 'premium',
				'has_premium_version' => true,
				'has_addons'          => false,
				'has_paid_plans'      => true,
				'is_org_compliant'    => true,
				'wp_org_gatekeeper'   => 'OA7#BoRiBNqdf52FvzEf!!074aRLPs8fspif$7K1#4u4Csys1fQlCecVcUTOs2mcpeVHi#C2j9d09fOTvbC0HloPT7fFee5WdS3G',
				'trial'               => array(
					'days'               => 3,
					'is_require_payment' => true,
				),
				'menu'                => array(
					'slug'    => 'kajob',
					'support' => false,
				),
			) );
		}

		return $kaj_fs;
	}

	kaj_fs();
	do_action( 'kaj_fs_loaded' );
}

/** Tell administrators when the companion plugin is missing. */
function kajob_core_missing_admin_notice() {
	if ( defined( 'KAJOB_CORE_VERSION' ) || ! current_user_can( 'activate_plugins' ) ) {
		return;
	}
	?>
	<div class="notice notice-error"><p><?php esc_html_e( 'Kajob Core is required for Kajob job alerts, shortcode overrides, and encrypted candidate files. Install the separate Kajob Core companion plugin supplied with the theme release.', 'kajob' ); ?></p></div>
	<?php
}
add_action( 'admin_notices', 'kajob_core_missing_admin_notice' );

/** Offer the optional Resend mailer download from the Plugins screen. */
function kajob_resend_missing_admin_notice() {
	if ( function_exists( 'kajob_resend_is_ready' ) || ! current_user_can( 'install_plugins' ) ) {
		return;
	}

	$screen = function_exists( 'get_current_screen' ) ? get_current_screen() : null;
	if ( ! $screen || 'plugins' !== $screen->id ) {
		return;
	}
	?>
	<div class="notice notice-info"><p><?php esc_html_e( 'Optional: Kajob Resend Mailer is distributed as a separate plugin ZIP. You will need your own Resend API key and verified sender domain.', 'kajob' ); ?></p></div>
	<?php
}
add_action( 'admin_notices', 'kajob_resend_missing_admin_notice' );

define( 'KAJOB_VERSION', '2.8.9' );
define( 'KAJOB_DIR', get_template_directory() );
define( 'KAJOB_URI', get_template_directory_uri() );

/**
 * Theme setup.
 */
function kajob_setup() {
	load_theme_textdomain( 'kajob', KAJOB_DIR . '/languages' );

	add_theme_support( 'title-tag' );
	add_theme_support( 'automatic-feed-links' );
	add_theme_support( 'post-thumbnails' );
	add_theme_support( 'responsive-embeds' );
	add_theme_support( 'align-wide' );
	add_theme_support( 'wp-block-styles' );
	add_theme_support( 'html5', array( 'search-form', 'comment-form', 'comment-list', 'gallery', 'caption', 'style', 'script', 'navigation-widgets' ) );
	add_theme_support( 'custom-logo', array(
		'height'      => 60,
		'width'       => 220,
		'flex-height' => true,
		'flex-width'  => true,
	) );
	add_theme_support( 'customize-selective-refresh-widgets' );

	add_editor_style( array( 'assets/css/main.css', 'assets/css/theme.css' ) );

	register_nav_menus( array(
		'primary' => __( 'Primary Menu', 'kajob' ),
		'footer'  => __( 'Footer Menu', 'kajob' ),
	) );

	add_image_size( 'kajob-card', 640, 420, true );
	add_image_size( 'kajob-logo', 160, 160, true );
}
add_action( 'after_setup_theme', 'kajob_setup' );

/** Build the homepage demo dropdown shared by custom and fallback menus. */
function kajob_home_demos_menu_item_markup() {
	$current_demo = isset( $_GET['home-demo'] ) ? absint( wp_unslash( $_GET['home-demo'] ) ) : 1; // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- read-only demo preview selection.
	$current_demo = max( 1, min( 5, $current_demo ) );
	$home_url     = remove_query_arg( 'home-demo', home_url( '/' ) );
	$labels       = array(
		1 => __( 'Home Page 01', 'kajob' ),
		2 => __( 'Home Page 02', 'kajob' ),
		3 => __( 'Home Page 03', 'kajob' ),
		4 => __( 'Home Page 04', 'kajob' ),
		5 => __( 'Home Page 05', 'kajob' ),
	);
	$items = '';

	foreach ( $labels as $number => $label ) {
		$url    = 1 === $number ? $home_url : add_query_arg( 'home-demo', $number, $home_url );
		$active = $current_demo === $number;
		$items .= sprintf(
			'<li class="menu-item%1$s"><a href="%2$s"%3$s>%4$s</a></li>',
			$active ? ' current-menu-item' : '',
			esc_url( $url ),
			$active ? ' aria-current="page"' : '',
			esc_html( $label )
		);
	}

	return sprintf(
		'<li class="menu-item menu-item-has-children kajob-home-demo-menu%1$s"><a href="%2$s" aria-haspopup="true" aria-expanded="false">%3$s</a><ul class="sub-menu">%4$s</ul></li>',
		is_front_page() ? ' current-menu-ancestor' : '',
		esc_url( $home_url ),
		esc_html__( 'Home', 'kajob' ),
		$items
	);
}

/** Prepend the demo selector to a configured primary menu. */
function kajob_add_home_demos_to_primary_menu( $items, $args ) {
	if ( isset( $args->theme_location ) && 'primary' === $args->theme_location && false === strpos( $items, 'kajob-home-demo-menu' ) ) {
		$items = kajob_home_demos_menu_item_markup() . $items;
	}
	if ( isset( $args->theme_location ) && 'primary' === $args->theme_location && function_exists( 'kajob_get_candidate_account_url' ) && false === strpos( $items, 'kajob-candidate-account' ) ) {
		$label = is_user_logged_in() ? __( 'My account', 'kajob' ) : __( 'Candidate account', 'kajob' );
		$items .= sprintf( '<li class="menu-item kajob-candidate-account"><a href="%1$s">%2$s</a></li>', esc_url( kajob_get_candidate_account_url() ), esc_html( $label ) );
	}

	return $items;
}
add_filter( 'wp_nav_menu_items', 'kajob_add_home_demos_to_primary_menu', 10, 2 );

/**
 * Content width for embeds.
 */
function kajob_content_width() {
	$GLOBALS['content_width'] = absint( get_theme_mod( 'kajob_container_width', 1240 ) );
}
add_action( 'after_setup_theme', 'kajob_content_width', 0 );

/**
 * Widget areas.
 */
function kajob_widgets_init() {
	register_sidebar( array(
		'name'          => __( 'Blog Sidebar', 'kajob' ),
		'id'            => 'sidebar-1',
		'description'   => __( 'Widgets shown beside blog content.', 'kajob' ),
		'before_widget' => '<section id="%1$s" class="widget %2$s">',
		'after_widget'  => '</section>',
		'before_title'  => '<h3 class="widget-title">',
		'after_title'   => '</h3>',
	) );

	for ( $i = 1; $i <= 3; $i++ ) {
		register_sidebar( array(
			/* translators: %d: footer column number. */
			'name'          => sprintf( __( 'Footer Column %d', 'kajob' ), $i ),
			'id'            => 'footer-' . $i,
			'before_widget' => '<section id="%1$s" class="widget %2$s">',
			'after_widget'  => '</section>',
			'before_title'  => '<h4 class="widget-title widget-title--footer">',
			'after_title'   => '</h4>',
		) );
	}
}
add_action( 'widgets_init', 'kajob_widgets_init' );

/**
 * Front-end assets.
 */
function kajob_scripts() {
	wp_enqueue_style( 'kajob-main', KAJOB_URI . '/assets/css/main.css', array(), KAJOB_VERSION );
	wp_enqueue_style( 'kajob-theme', KAJOB_URI . '/assets/css/theme.css', array( 'kajob-main' ), KAJOB_VERSION );

	// Job Manager ships jQuery-based filter JS; our layer is independent of it.
	wp_enqueue_script( 'kajob-main', KAJOB_URI . '/assets/js/main.js', array(), KAJOB_VERSION, true );

	wp_localize_script( 'kajob-main', 'kajobData', array(
		'ajaxUrl'      => admin_url( 'admin-ajax.php' ),
		'nonce'        => wp_create_nonce( 'kajob_public' ),
		'jobsUrl'      => kajob_get_jobs_page_url(),
		'savedUrl'     => kajob_get_saved_jobs_url(),
		'homeUrl'      => home_url( '/' ),
		'isLoggedIn'   => is_user_logged_in(),
		'savedJobs'    => kajob_get_saved_job_ids(),
		'salaryMax'    => kajob_get_salary_ceiling(),
		'currency'     => kajob_currency_symbol(),
		'i18n'         => array(
			'saved'          => __( 'Job saved', 'kajob' ),
			'unsaved'        => __( 'Removed from saved jobs', 'kajob' ),
			'viewSaved'      => __( 'View saved', 'kajob' ),
			'copied'         => __( 'Link copied to clipboard', 'kajob' ),
			'copyFailed'     => __( 'Could not copy the link', 'kajob' ),
			'noResults'      => __( 'No matching jobs', 'kajob' ),
			'searching'      => __( 'Searching…', 'kajob' ),
			'loadMore'       => __( 'Load more jobs', 'kajob' ),
			'loading'        => __( 'Loading…', 'kajob' ),
			'allLoaded'      => __( 'You have seen every matching job', 'kajob' ),
			'resultsFor'     => __( 'jobs found', 'kajob' ),
			'filterApplied'  => __( 'Filters applied', 'kajob' ),
			'resetFilters'   => __( 'Reset filters', 'kajob' ),
			'undo'           => __( 'Undo', 'kajob' ),
			'error'          => __( 'Something went wrong. Please try again.', 'kajob' ),
			'subscribed'     => __( 'You are on the list. Watch your inbox.', 'kajob' ),
			'invalidEmail'   => __( 'Enter a valid email address', 'kajob' ),
			'minSalary'      => __( 'Any salary', 'kajob' ),
		),
	) );

	if ( is_singular() && comments_open() && get_option( 'thread_comments' ) ) {
		wp_enqueue_script( 'comment-reply' );
	}
}
add_action( 'wp_enqueue_scripts', 'kajob_scripts' );

/**
 * Resolve the colour scheme before first paint so switching themes never
 * flashes the wrong background.
 */
function kajob_theme_boot() {
	if ( ! get_theme_mod( 'kajob_enable_dark_mode', true ) ) {
		return;
	}
	?>
	<script>
	(function () {
		try {
			var stored = localStorage.getItem('kajob-theme');
			var mode = (stored === 'dark' || stored === 'light')
				? stored
				: <?php echo wp_json_encode( get_theme_mod( 'kajob_default_scheme', 'dark' ) ); ?>;
			document.documentElement.setAttribute('data-theme', mode === 'dark' ? 'dark' : 'light');
		} catch (e) {
			document.documentElement.setAttribute('data-theme', 'light');
		}
	})();
	</script>
	<?php
}
add_action( 'wp_head', 'kajob_theme_boot', 1 );

/**
 * Preload the display font: it draws the hero headline.
 */
function kajob_resource_hints() {
	printf(
		'<link rel="preload" href="%s" as="font" type="font/woff2" crossorigin>' . "\n",
		esc_url( KAJOB_URI . '/assets/fonts/space-grotesk-var.woff2' )
	);
	printf(
		'<link rel="preload" href="%s" as="font" type="font/woff2" crossorigin>' . "\n",
		esc_url( KAJOB_URI . '/assets/fonts/inter-var.woff2' )
	);
}
add_action( 'wp_head', 'kajob_resource_hints', 2 );

/**
 * Customizer-driven CSS variables.
 */
function kajob_inline_css() {
	$primary   = get_theme_mod( 'kajob_primary_color', '#ffb020' );
	$secondary = get_theme_mod( 'kajob_secondary_color', '#0a0e1f' );
	$width     = absint( get_theme_mod( 'kajob_container_width', 1240 ) );
	$radius    = absint( get_theme_mod( 'kajob_corner_radius', 18 ) );

	$primary   = sanitize_hex_color( $primary ) ? $primary : '#ffb020';
	$secondary = sanitize_hex_color( $secondary ) ? $secondary : '#0a0e1f';
	$width     = max( 960, min( 1600, $width ) );
	$radius    = min( 32, $radius );

	// Text/icon color on brand surfaces: bright hues (yellow) get dark ink,
	// deep hues keep white — decided by relative luminance.
	$on_brand = kajob_hex_luminance( $primary ) > 0.5 ? '#141001' : '#ffffff';

	$css = sprintf(
		':root{--kajob-primary:%1$s;--kajob-secondary:%2$s;--kajob-container:%3$dpx;--r-lg:%4$dpx;--r-xl:%5$dpx;--on-brand:%6$s;}',
		$primary,
		$secondary,
		$width,
		$radius,
		max( $radius + 8, 12 ),
		$on_brand
	);

	wp_add_inline_style( 'kajob-main', $css );
}
add_action( 'wp_enqueue_scripts', 'kajob_inline_css', 20 );

/**
 * Perceived luminance of a hex color (0 = black, 1 = white).
 */
function kajob_hex_luminance( $hex ) {
	$hex = ltrim( (string) $hex, '#' );

	if ( 3 === strlen( $hex ) ) {
		$hex = $hex[0] . $hex[0] . $hex[1] . $hex[1] . $hex[2] . $hex[2];
	}

	if ( 6 !== strlen( $hex ) ) {
		return 0;
	}

	$channels = array_map( function ( $part ) {
		$value     = hexdec( $part ) / 255;
		$linearized = ( $value <= 0.03928 ) ? $value / 12.92 : pow( ( $value + 0.055 ) / 1.055, 2.4 );

		return $linearized;
	}, str_split( $hex, 2 ) );

	return 0.2126 * $channels[0] + 0.7152 * $channels[1] + 0.0722 * $channels[2];
}

/**
 * Body classes used by the stylesheet.
 */
function kajob_body_classes( $classes ) {
	if ( ! is_active_sidebar( 'sidebar-1' ) ) {
		$classes[] = 'kajob-no-sidebar';
	}

	if ( kajob_is_job_manager_active() ) {
		$classes[] = 'kajob-jm-active';
	}

	if ( is_singular( 'job_listing' ) ) {
		$classes[] = 'kajob-single-job';
	}

	return $classes;
}
add_filter( 'body_class', 'kajob_body_classes' );

/**
 * Excerpt tweaks.
 */
add_filter( 'excerpt_length', function () {
	return 22;
} );

add_filter( 'excerpt_more', function () {
	return '&hellip;';
} );

/**
 * Saved jobs are stored per user; guests fall back to localStorage and the
 * client sends its list on the next authenticated request.
 */
function kajob_get_saved_job_ids() {
	if ( ! is_user_logged_in() ) {
		return array();
	}

	$saved = get_user_meta( get_current_user_id(), 'kajob_saved_jobs', true );

	return is_array( $saved ) ? array_values( array_filter( array_map( 'absint', $saved ) ) ) : array();
}

/**
 * Load an include only when it exists. Guards against white-screening the
 * whole site when a theme file is missing or mid-transfer during development.
 *
 * @param string $relative_path Path relative to the theme directory.
 */
function kajob_require( $relative_path ) {
	$path = KAJOB_DIR . '/' . ltrim( $relative_path, '/' );

	if ( file_exists( $path ) ) {
		require $path;
	}
}

kajob_require( 'inc/customizer.php' );
kajob_require( 'inc/template-tags.php' );
kajob_require( 'inc/job-manager.php' );
kajob_require( 'inc/ajax.php' );
kajob_require( 'inc/jobs-board.php' );
kajob_require( 'inc/employer.php' );
kajob_require( 'inc/applications.php' );
kajob_require( 'inc/candidates.php' );
kajob_require( 'inc/demo-content.php' );
kajob_require( 'inc/agent.php' );
