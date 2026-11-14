<?php
/**
 * Job listing in the plugin's own loop.
 *
 * Override of wp-job-manager/templates/content-job_listing.php. The plugin
 * wraps its loop in <ul class="job_listings">, so the card must stay inside an
 * <li>. The card markup itself comes from the theme renderer.
 *
 * @package Kajob
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

global $post;

if ( ( post_password_required( $post ) && ! is_super_admin() ) || ! job_manager_user_can_view_job_listing( $post->ID ) ) {
	return;
}
?>
<li class="job-list__item">
	<?php echo kajob_job_card( $post->ID ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
</li>