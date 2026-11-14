<?php
/**
 * Footer template, plus the overlays every page shares: job alerts,
 * command palette, toast stack and back-to-top.
 *
 * @package Kajob
 */

?>
</main><!-- #content -->


<?php
$kajob_jobs_page_id = function_exists( 'job_manager_get_page_id' ) ? absint( job_manager_get_page_id( 'jobs' ) ) : 0;
$kajob_show_alerts  = is_front_page() || is_post_type_archive( 'job_listing' ) || is_tax( array( 'job_listing_category', 'job_listing_type' ) ) || ( $kajob_jobs_page_id && is_page( $kajob_jobs_page_id ) );
$kajob_alerts_url   = $kajob_show_alerts ? '#job-alerts' : home_url( '/#job-alerts' );
?>

<?php if ( $kajob_show_alerts && ! is_page_template( 'page-ai-mode.php' ) && get_theme_mod( 'kajob_enable_alerts', true ) ) : ?>
	<section class="section section--tight" id="job-alerts">
		<div class="container">
			<div class="newsletter" data-reveal>
				<div>
					<span class="section__eyebrow"><?php esc_html_e( 'Job alerts', 'kajob' ); ?></span>
					<h2 class="u-mb-0"><?php esc_html_e( 'Let the right job find you.', 'kajob' ); ?></h2>
					<p class="u-muted u-mt-5">
						<?php esc_html_e( 'One short email when a role matches your search. No digests, no spam, unsubscribe in a click.', 'kajob' ); ?>
					</p>
				</div>
				<div>
					<form class="newsletter__form" data-alerts-form novalidate>
						<label class="screen-reader-text" for="kajob-alert-email"><?php esc_html_e( 'Email address', 'kajob' ); ?></label>
						<input type="email" id="kajob-alert-email" name="email" required placeholder="<?php esc_attr_e( 'you@example.com', 'kajob' ); ?>" autocomplete="email">
						<button type="submit" class="btn btn--primary">
							<span class="btn__spinner" aria-hidden="true"></span>
							<span><?php esc_html_e( 'Notify me', 'kajob' ); ?></span>
						</button>
					</form>
					<p class="newsletter__note" data-alerts-note>
						<?php
						printf(
							/* translators: %s: number of live listings. */
							esc_html__( 'Watching %s live listings right now.', 'kajob' ),
							esc_html( number_format_i18n( kajob_get_job_count() ) )
						);
						?>
					</p>
				</div>
			</div>
		</div>
	</section>
<?php endif; ?>

<?php if ( ! is_page_template( 'page-ai-mode.php' ) ) : ?>
<footer id="colophon" class="site-footer kajob-grain">
	<div class="site-footer__top">
		<div class="container footer-grid">

			<div class="footer-brand">
				<a class="footer-brand__link" href="<?php echo esc_url( home_url( '/' ) ); ?>" rel="home">
					<?php echo kajob_brand_mark(); // phpcs:ignore WordPress.Security.EscapeOutput ?>
					<span><?php bloginfo( 'name' ); ?></span>
				</a>
				<p class="footer-brand__text">
					<?php echo esc_html( get_theme_mod( 'kajob_footer_tagline', get_bloginfo( 'description' ) ? get_bloginfo( 'description' ) : __( 'A job board that respects your time: real salaries, real remote flags, and no recruiter spam.', 'kajob' ) ) ); ?>
				</p>

				<div class="footer-social">
					<?php
					$socials = array(
						'linkedin' => array( 'linkedin', __( 'LinkedIn', 'kajob' ) ),
						'x'        => array( 'x-social', __( 'X', 'kajob' ) ),
						'github'   => array( 'github', __( 'GitHub', 'kajob' ) ),
						'facebook' => array( 'facebook', __( 'Facebook', 'kajob' ) ),
					);

					foreach ( $socials as $key => $data ) {
						$url = get_theme_mod( 'kajob_social_' . $key, '' );

						if ( $url ) {
							printf(
								'<a href="%1$s" target="_blank" rel="noopener noreferrer" aria-label="%2$s">%3$s</a>',
								esc_url( $url ),
								esc_attr( $data[1] ),
								kajob_get_icon( $data[0], array( 'size' => 17 ) ) // phpcs:ignore WordPress.Security.EscapeOutput
							);
						}
					}
					?>
					<a href="<?php echo esc_url( 'mailto:' . antispambot( get_option( 'admin_email' ) ) ); ?>" aria-label="<?php esc_attr_e( 'Email us', 'kajob' ); ?>" title="<?php esc_attr_e( 'Email us', 'kajob' ); ?>">
						<?php kajob_icon( 'mail', array( 'size' => 17 ) ); ?>
					</a>
				</div>
			</div>

			<?php
			$has_footer_widgets = false;

			for ( $i = 1; $i <= 3; $i++ ) {
				if ( is_active_sidebar( 'footer-' . $i ) ) {
					$has_footer_widgets = true;
					break;
				}
			}

			if ( $has_footer_widgets ) :
				for ( $i = 1; $i <= 3; $i++ ) :
					?>
					<div class="footer-col">
						<?php if ( is_active_sidebar( 'footer-' . $i ) ) : ?>
							<?php dynamic_sidebar( 'footer-' . $i ); ?>
						<?php endif; ?>
					</div>
					<?php
				endfor;
			else :
				?>
				<div class="footer-col">
					<h4 class="footer-col__title"><?php esc_html_e( 'For candidates', 'kajob' ); ?></h4>
					<ul class="footer-col__list">
						<li><a href="<?php echo esc_url( kajob_get_jobs_page_url() ); ?>"><?php esc_html_e( 'Browse all jobs', 'kajob' ); ?></a></li>
						<li><a href="<?php echo esc_url( add_query_arg( 'remote_position', '1', kajob_get_jobs_page_url() ) ); ?>"><?php esc_html_e( 'Remote only', 'kajob' ); ?></a></li>
						<li><a href="<?php echo esc_url( kajob_get_saved_jobs_url() ); ?>"><?php esc_html_e( 'Saved jobs', 'kajob' ); ?></a></li>
						<li><a href="<?php echo esc_url( kajob_get_candidate_account_url() ); ?>"><?php echo esc_html( is_user_logged_in() ? __( 'My account and CV', 'kajob' ) : __( 'Create candidate account', 'kajob' ) ); ?></a></li>
						<li><a href="<?php echo esc_url( kajob_get_blog_url() ); ?>"><?php esc_html_e( 'Career advice', 'kajob' ); ?></a></li>
						<li><a href="<?php echo esc_url( $kajob_alerts_url ); ?>"><?php esc_html_e( 'Job alerts', 'kajob' ); ?></a></li>
					</ul>
				</div>

				<div class="footer-col">
					<h4 class="footer-col__title"><?php esc_html_e( 'For employers', 'kajob' ); ?></h4>
					<ul class="footer-col__list">
						<li><a href="<?php echo esc_url( kajob_get_jm_page_url( 'submit_job_form' ) ); ?>"><?php esc_html_e( 'Post a job', 'kajob' ); ?></a></li>
						<li><a href="<?php echo esc_url( kajob_get_jm_page_url( 'job_dashboard' ) ); ?>"><?php esc_html_e( 'Employer dashboard', 'kajob' ); ?></a></li>
						<li><a href="<?php echo esc_url( add_query_arg( 'orderby', 'salary', kajob_get_jobs_page_url() ) ); ?>"><?php esc_html_e( 'Highest paying roles', 'kajob' ); ?></a></li>
						<li><a href="<?php echo esc_url( kajob_get_signin_url( kajob_get_jm_page_url( 'job_dashboard' ) ) ); ?>"><?php esc_html_e( 'Sign in', 'kajob' ); ?></a></li>
					</ul>
				</div>

				<div class="footer-col">
					<h4 class="footer-col__title"><?php esc_html_e( 'Top categories', 'kajob' ); ?></h4>
					<ul class="footer-col__list">
						<?php
						$footer_terms = array();

						if ( kajob_is_job_manager_active() && taxonomy_exists( 'job_listing_category' ) ) {
							$footer_terms = get_terms( array(
								'taxonomy'   => 'job_listing_category',
								'hide_empty' => true,
								'number'     => 5,
								'orderby'    => 'count',
								'order'      => 'DESC',
							) );

							if ( is_wp_error( $footer_terms ) ) {
								$footer_terms = array();
							}
						}

						if ( $footer_terms ) {
							foreach ( $footer_terms as $term ) {
								printf(
									'<li><a href="%1$s">%2$s <span class="u-muted">(%3$s)</span></a></li>',
									esc_url( get_term_link( $term ) ),
									esc_html( $term->name ),
									esc_html( number_format_i18n( $term->count ) )
								);
							}
						} else {
							printf( '<li><a href="%1$s">%2$s</a></li>', esc_url( kajob_get_jobs_page_url() ), esc_html__( 'All open roles', 'kajob' ) );
						}
						?>
					</ul>
				</div>
			<?php endif; ?>

		</div>
	</div>

	<div class="site-footer__bottom">
		<div class="container site-footer__bottom-inner">
			<div class="site-footer__copyright">
				<?php echo esc_html( get_theme_mod( 'kajob_footer_copyright', sprintf( __( '© %1$s %2$s. All rights reserved.', 'kajob' ), date_i18n( 'Y' ), get_bloginfo( 'name' ) ) ) ); ?>
			</div>
			<?php
			wp_nav_menu( array(
				'theme_location' => 'footer',
				'container'      => false,
				'menu_class'     => 'footer-menu',
				'depth'          => 1,
				'fallback_cb'    => false,
			) );
			?>
		</div>
	</div>
</footer>
<?php endif; ?>

<button type="button" class="to-top" data-to-top aria-label="<?php esc_attr_e( 'Back to top', 'kajob' ); ?>">
	<?php kajob_icon( 'arrow-up', array( 'size' => 19 ) ); ?>
</button>

<div class="toast-stack" data-toasts role="status" aria-live="polite"></div>

<div class="palette" data-palette role="dialog" aria-modal="true" aria-hidden="true" aria-label="<?php esc_attr_e( 'Quick search', 'kajob' ); ?>">
	<div class="palette__panel">
		<div class="palette__head">
			<?php kajob_icon( 'search', array( 'size' => 19 ) ); ?>
			<label class="screen-reader-text" for="kajob-palette-input"><?php esc_html_e( 'Search jobs, companies and pages', 'kajob' ); ?></label>
			<input
				type="text"
				id="kajob-palette-input"
				class="palette__input"
				data-palette-input
				role="combobox"
				aria-expanded="false"
				aria-controls="kajob-palette-results"
				aria-autocomplete="list"
				autocomplete="off"
				spellcheck="false"
				placeholder="<?php esc_attr_e( 'Search jobs, companies, pages…', 'kajob' ); ?>">
			<span class="palette__esc" aria-hidden="true">ESC</span>
		</div>
		<div class="palette__results" id="kajob-palette-results" data-palette-results role="listbox" aria-label="<?php esc_attr_e( 'Search results', 'kajob' ); ?>"></div>
		<div class="palette__foot">
			<span><kbd>&uarr;</kbd><kbd>&darr;</kbd> <?php esc_html_e( 'navigate', 'kajob' ); ?></span>
			<span><kbd>&crarr;</kbd> <?php esc_html_e( 'open', 'kajob' ); ?></span>
			<span><kbd>esc</kbd> <?php esc_html_e( 'close', 'kajob' ); ?></span>
		</div>
	</div>
</div>

<?php wp_footer(); ?>
</body>
</html>
