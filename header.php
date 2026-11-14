<?php
/**
 * Header template.
 *
 * @package Kajob
 */

?>
<!doctype html>
<html <?php language_attributes(); ?>>
<head>
	<meta charset="<?php bloginfo( 'charset' ); ?>">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<meta name="theme-color" content="#0a0e1f">
	<?php wp_head(); ?>
</head>
<body <?php body_class(); ?>>
<?php wp_body_open(); ?>
<a class="skip-link" href="#content"><?php esc_html_e( 'Skip to content', 'kajob' ); ?></a>

<header id="masthead" class="site-header" data-header>
	<div class="container site-header__inner">

		<div class="site-branding">
			<?php if ( has_custom_logo() ) : ?>
				<?php the_custom_logo(); ?>
			<?php else : ?>
				<a class="site-branding__link" href="<?php echo esc_url( home_url( '/' ) ); ?>" rel="home">
					<?php echo kajob_brand_mark(); // phpcs:ignore WordPress.Security.EscapeOutput ?>
					<span><?php bloginfo( 'name' ); ?><span class="site-branding__dot">.</span></span>
				</a>
			<?php endif; ?>
		</div>

		<nav id="site-navigation" class="main-navigation" data-nav aria-label="<?php esc_attr_e( 'Primary Menu', 'kajob' ); ?>">
			<?php
			wp_nav_menu( array(
				'theme_location' => 'primary',
				'menu_id'        => 'primary-menu',
				'container'      => false,
				'fallback_cb'    => 'kajob_nav_fallback',
				'depth'          => 2,
			) );
			?>
		</nav>

		<div class="site-header__actions">
			<?php if ( kajob_agent_enabled() && kajob_get_ai_mode_url() ) : ?>
				<a class="ai-mode-btn" href="<?php echo esc_url( kajob_get_ai_mode_url() ); ?>">
					<?php kajob_icon( 'sparkle', array( 'size' => 15 ) ); ?>
					<span><?php esc_html_e( 'AI Mode', 'kajob' ); ?></span>
				</a>
			<?php endif; ?>

			<?php if ( get_theme_mod( 'kajob_enable_dark_mode', true ) ) : ?>
				<button type="button" class="icon-btn" data-theme-toggle aria-label="<?php esc_attr_e( 'Toggle dark mode', 'kajob' ); ?>" title="<?php esc_attr_e( 'Toggle dark mode', 'kajob' ); ?>">
					<span class="theme-toggle__sun"><?php kajob_icon( 'sun', array( 'size' => 19 ) ); ?></span>
					<span class="theme-toggle__moon"><?php kajob_icon( 'moon', array( 'size' => 19 ) ); ?></span>
				</button>
			<?php endif; ?>

			<a class="icon-btn" href="<?php echo esc_url( kajob_get_saved_jobs_url() ); ?>" aria-label="<?php esc_attr_e( 'Saved jobs', 'kajob' ); ?>" title="<?php esc_attr_e( 'Saved jobs', 'kajob' ); ?>">
				<?php kajob_icon( 'bookmark', array( 'size' => 19 ) ); ?>
				<span class="icon-btn__count" data-saved-count>0</span>
			</a>

			<?php if ( get_theme_mod( 'kajob_header_cta', true ) ) : ?>
				<div class="site-header__cta">
					<a class="btn btn--primary btn--sm" href="<?php echo esc_url( get_theme_mod( 'kajob_header_cta_url', kajob_get_jm_page_url( 'submit_job_form' ) ) ); ?>">
						<?php kajob_icon( 'plus', array( 'size' => 15 ) ); ?>
						<?php echo esc_html( get_theme_mod( 'kajob_header_cta_text', __( 'Post a Job', 'kajob' ) ) ); ?>
					</a>
				</div>
			<?php endif; ?>

			<?php if ( is_page_template( 'page-ai-mode.php' ) ) : ?>
				<a class="icon-btn ai-mode-close" href="<?php echo esc_url( home_url( '/' ) ); ?>" title="<?php esc_attr_e( 'Close AI Mode', 'kajob' ); ?>" aria-label="<?php esc_attr_e( 'Close AI Mode', 'kajob' ); ?>">
					<?php kajob_icon( 'x', array( 'size' => 19 ) ); ?>
				</a>
			<?php endif; ?>

			<button class="main-navigation__toggle" data-nav-toggle aria-expanded="false" aria-controls="site-navigation">
				<span class="main-navigation__bars" aria-hidden="true"></span>
				<span class="screen-reader-text"><?php esc_html_e( 'Menu', 'kajob' ); ?></span>
			</button>
		</div>

	</div>
</header>

<div class="nav-scrim" data-nav-scrim hidden></div>

<main id="content" class="site-content">
<?php

/**
 * Menu fallback so a fresh install still has working navigation.
 */
function kajob_nav_fallback() {
	$items = array(
		kajob_get_jobs_page_url()                       => __( 'Find Jobs', 'kajob' ),
		kajob_get_jm_page_url( 'submit_job_form' )      => __( 'Post a Job', 'kajob' ),
		kajob_get_jm_page_url( 'job_dashboard' )        => __( 'For Employers', 'kajob' ),
	);

	echo '<ul id="primary-menu" class="menu">';
	echo kajob_home_demos_menu_item_markup(); // phpcs:ignore WordPress.Security.EscapeOutput

	foreach ( $items as $url => $label ) {
		printf( '<li class="menu-item"><a href="%1$s">%2$s</a></li>', esc_url( $url ), esc_html( $label ) );
	}

	echo '</ul>';
}
