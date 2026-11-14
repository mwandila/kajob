<?php
/**
 * Single job content for the [job] shortcode and the submission preview.
 *
 * Override of wp-job-manager/templates/content-single-job_listing.php. The
 * theme's single-job_listing.php draws the full page itself; this compact
 * version is what appears wherever the plugin injects a listing inline.
 *
 * @package Kajob
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

global $post;

if ( ( post_password_required( $post ) && ! is_super_admin() ) || ! job_manager_user_can_view_job_listing( $post->ID ) ) {
	get_job_manager_template_part( 'access-denied', 'single-job_listing' );

	return;
}
?>
<div class="single_job_listing kajob-embedded-job">
	<?php if ( get_option( 'job_manager_hide_expired_content', 1 ) && 'expired' === $post->post_status ) : ?>
		<div class="notice notice--warning">
			<?php kajob_icon( 'alert', array( 'size' => 18 ) ); ?>
			<p><?php esc_html_e( 'This listing has expired.', 'kajob' ); ?></p>
		</div>
	<?php else : ?>
		<?php
		do_action( 'single_job_listing_start' );

		get_template_part( 'template-parts/job', 'facts', array( 'post_id' => $post->ID ) );
		?>

		<section class="job-panel">
			<h2 class="job-panel__title"><?php kajob_icon( 'file', array( 'size' => 20 ) ); ?><?php esc_html_e( 'About the role', 'kajob' ); ?></h2>
			<div class="job-description"><?php wpjm_the_job_description(); ?></div>
		</section>

		<?php if ( candidates_can_apply() ) : ?>
			<section class="job-panel">
				<h2 class="job-panel__title"><?php kajob_icon( 'send', array( 'size' => 20 ) ); ?><?php esc_html_e( 'How to apply', 'kajob' ); ?></h2>
				<?php
				$kajob_embed_apply = kajob_job_apply( $post->ID );

				if ( $kajob_embed_apply['open'] ) :
					?>
					<a class="btn btn--primary" href="<?php echo esc_url( $kajob_embed_apply['href'] ); ?>"
						<?php if ( 'url' === $kajob_embed_apply['type'] ) : ?>target="_blank" rel="nofollow noopener"<?php endif; ?>
						data-apply-click="<?php echo esc_attr( $post->ID ); ?>">
						<?php kajob_icon( 'url' === $kajob_embed_apply['type'] ? 'external' : 'send', array( 'size' => 17 ) ); ?>
						<?php echo esc_html( $kajob_embed_apply['label'] ); ?>
					</a>
				<?php endif; ?>

				<?php get_job_manager_template( 'job-application.php', array( 'post_id' => $post->ID ) ); ?>
			</section>
		<?php endif; ?>

		<?php
		do_action( 'single_job_listing_end' );

		get_template_part( 'template-parts/job', 'company', array( 'post_id' => $post->ID ) );
		?>
	<?php endif; ?>
</div>