<?php
/**
 * Single job listing.
 *
 * The theme renders the description directly rather than through the_content,
 * so the layout is fully controlled here while the plugin's own hooks still
 * fire for add-ons.
 *
 * @package Kajob
 */

get_header();

while ( have_posts() ) :
	the_post();

	$kajob_id       = get_the_ID();
	$kajob_company  = get_post_meta( $kajob_id, '_company_name', true );
	$kajob_location = get_post_meta( $kajob_id, '_job_location', true );
	$kajob_apply    = kajob_job_apply( $kajob_id );
	$kajob_salary   = kajob_get_salary_label( $kajob_id );
	$kajob_related  = kajob_related_jobs( $kajob_id, 3 );

	if ( post_password_required() ) {
		?>
		<div class="container section">
			<div class="empty-state">
				<div class="empty-state__icon"><?php kajob_icon( 'shield', array( 'size' => 30 ) ); ?></div>
				<h1 class="empty-state__title"><?php esc_html_e( 'This listing is protected', 'kajob' ); ?></h1>
				<div class="empty-state__text"><?php echo get_the_password_form(); // phpcs:ignore WordPress.Security.EscapeOutput ?></div>
			</div>
		</div>
		<?php
		continue;
	}
	?>

	<article <?php post_class( 'job-single' ); ?>>

		<header class="job-hero kajob-grain">
			<div class="container">
				<div class="job-hero__layout">
					<div class="job-hero__aside">
						<?php echo kajob_job_breadcrumbs( $kajob_id ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
						<?php echo kajob_get_company_logo( $kajob_id, 'job-hero__logo', 'kajob-logo' ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
					</div>

					<div class="job-hero__main">
						<div class="job-hero__body">
							<h1 class="job-hero__title"><?php the_title(); ?></h1>

							<p class="job-hero__company">
							<?php if ( $kajob_company ) : ?>
								<strong><?php echo esc_html( $kajob_company ); ?></strong>
							<?php endif; ?>
							<?php if ( $kajob_location ) : ?>
								<span><?php kajob_icon( 'pin', array( 'size' => 14 ) ); ?> <?php echo esc_html( $kajob_location ); ?></span>
							<?php endif; ?>
							<span>
								<?php kajob_icon( 'clock', array( 'size' => 14 ) ); ?>
								<?php
								/* translators: %s: human-readable time difference. */
								printf( esc_html__( 'Posted %s ago', 'kajob' ), esc_html( human_time_diff( get_post_time( 'U', true ), current_time( 'timestamp' ) ) ) );
								?>
							</span>
							</p>

							<div class="job-hero__chips">
							<?php echo kajob_get_job_type_chips( $kajob_id, 3 ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
							<?php if ( kajob_is_remote( $kajob_id ) ) : ?>
								<span class="chip chip--sky"><?php kajob_icon( 'globe', array( 'size' => 12 ) ); ?><?php esc_html_e( 'Remote friendly', 'kajob' ); ?></span>
							<?php endif; ?>
							<?php if ( $kajob_salary ) : ?>
								<span class="chip chip--mint"><?php kajob_icon( 'cash', array( 'size' => 12 ) ); ?><?php echo esc_html( $kajob_salary ); ?></span>
							<?php endif; ?>
							<?php if ( '1' === (string) get_post_meta( $kajob_id, '_featured', true ) ) : ?>
								<span class="chip chip--amber"><?php kajob_icon( 'star', array( 'size' => 12, 'fill' => true ) ); ?><?php esc_html_e( 'Featured', 'kajob' ); ?></span>
							<?php endif; ?>
							<?php if ( ! $kajob_apply['open'] ) : ?>
								<span class="chip"><?php kajob_icon( 'alert', array( 'size' => 12 ) ); ?><?php esc_html_e( 'Closed', 'kajob' ); ?></span>
							<?php endif; ?>
 							</div>
						</div>

						<div class="job-hero__actions">
							<button type="button" class="job-save" data-job-save="<?php echo esc_attr( $kajob_id ); ?>" aria-pressed="false" aria-label="<?php echo esc_attr( sprintf( __( 'Save %s', 'kajob' ), get_the_title() ) ); ?>">
								<?php kajob_icon( 'bookmark', array( 'size' => 19 ) ); ?>
							</button>
							<button type="button" class="btn btn--on-dark btn--sm" data-share="<?php echo esc_url( get_permalink() ); ?>" data-share-title="<?php echo esc_attr( get_the_title() ); ?>">
								<?php kajob_icon( 'share', array( 'size' => 16 ) ); ?>
								<?php esc_html_e( 'Share', 'kajob' ); ?>
							</button>
						</div>
					</div>
				</div>
			</div>
		</header>

		<div class="container">
			<div class="job-layout">
				<div class="job-layout__main">

					<?php if ( ! $kajob_apply['open'] ) : ?>
						<div class="notice notice--warning">
							<?php kajob_icon( 'alert', array( 'size' => 18 ) ); ?>
							<p><?php echo esc_html( $kajob_apply['reason'] ); ?></p>
						</div>
					<?php endif; ?>

					<?php
					/**
					 * Plugin/add-on hook. The theme removed the two default callbacks
					 * because it renders its own meta and company panels.
					 */
					do_action( 'single_job_listing_start' );

					get_template_part( 'template-parts/job', 'facts', array( 'post_id' => $kajob_id ) );
					?>

					<section class="job-panel">
						<h2 class="job-panel__title"><?php kajob_icon( 'file', array( 'size' => 20 ) ); ?><?php esc_html_e( 'About the role', 'kajob' ); ?></h2>
						<div class="job-description">
							<?php
							if ( function_exists( 'wpjm_the_job_description' ) ) {
								wpjm_the_job_description();
							} else {
								the_content();
							}
							?>
						</div>
					</section>

					<?php
					do_action( 'single_job_listing_end' );

					get_template_part( 'template-parts/job', 'company', array( 'post_id' => $kajob_id ) );
					?>
				</div>

				<aside class="job-sidebar">
					<?php
					get_template_part( 'template-parts/job', 'apply-card', array( 'post_id' => $kajob_id ) );

					if ( $kajob_related ) :
						?>
						<section class="job-panel">
							<h2 class="job-panel__title"><?php kajob_icon( 'compass', array( 'size' => 20 ) ); ?><?php esc_html_e( 'Similar roles', 'kajob' ); ?></h2>
							<div class="job-list job-list--grid">
								<?php
								foreach ( $kajob_related as $kajob_job ) {
									echo kajob_job_card( $kajob_job->ID, array( 'variant' => 'grid' ) ); // phpcs:ignore WordPress.Security.EscapeOutput
								}
								?>
							</div>
						</section>
					<?php endif; ?>
				</aside>
			</div>
		</div>
	</article>

	<?php if ( $kajob_apply['open'] ) : ?>
		<div class="apply-bar" data-apply-bar>
			<div class="apply-bar__info">
				<span class="apply-bar__salary"><?php echo esc_html( $kajob_salary ? $kajob_salary : get_the_title() ); ?></span>
				<span class="apply-bar__label"><?php echo esc_html( $kajob_company ? $kajob_company : __( 'Apply for this role', 'kajob' ) ); ?></span>
			</div>
			<a class="btn btn--primary" href="<?php echo esc_url( 'email' === $kajob_apply['type'] ? '#kajob-application-form' : $kajob_apply['href'] ); ?>"
				<?php if ( 'url' === $kajob_apply['type'] ) : ?>target="_blank" rel="nofollow noopener"<?php endif; ?>
				data-apply-click="<?php echo esc_attr( $kajob_id ); ?>">
				<?php echo esc_html( 'email' === $kajob_apply['type'] ? __( 'Apply with Kajob', 'kajob' ) : $kajob_apply['label'] ); ?>
			</a>
		</div>
	<?php endif; ?>

	<?php
endwhile;

get_footer();
