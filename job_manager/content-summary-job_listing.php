<?php
/**
 * Compact listing summary, used by the plugin's summary contexts.
 *
 * Override of wp-job-manager/templates/content-summary-job_listing.php.
 *
 * @package Kajob
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

global $post;

if ( ! $post || post_password_required( $post ) || ! job_manager_user_can_view_job_listing( $post->ID ) ) {
	return;
}

$kajob_location = get_post_meta( $post->ID, '_job_location', true );

if ( ! $kajob_location && kajob_is_remote( $post->ID ) ) {
	$kajob_location = __( 'Remote', 'kajob' );
}
?>
<a class="job-summary" href="<?php the_permalink(); ?>">
	<?php echo kajob_get_company_logo( $post->ID, 'job-summary__logo' ); // phpcs:ignore WordPress.Security.EscapeOutput -- escaped in helper. ?>
	<span class="job-summary__body">
		<span class="job-summary__title"><?php wpjm_the_job_title(); ?></span>
		<span class="job-summary__meta">
			<?php echo esc_html( get_post_meta( $post->ID, '_company_name', true ) ); ?>
			<?php if ( $kajob_location ) : ?>
				<span aria-hidden="true">&middot;</span> <?php echo esc_html( $kajob_location ); ?>
			<?php endif; ?>
		</span>
		<?php if ( get_option( 'job_manager_enable_types' ) ) : ?>
			<span class="job-summary__chips"><?php echo kajob_get_job_type_chips( $post->ID, 2 ); // phpcs:ignore WordPress.Security.EscapeOutput -- escaped in helper. ?></span>
		<?php endif; ?>
	</span>
	<span class="job-summary__arrow" aria-hidden="true"><?php kajob_icon( 'arrow-right', array( 'size' => 17 ) ); ?></span>
</a>