<?php
/**
 * Access denied: single listing.
 *
 * Override of wp-job-manager/templates/access-denied-single-job_listing.php.
 *
 * @package Kajob
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<div class="empty-state">
	<div class="empty-state__icon"><?php kajob_icon( 'shield', array( 'size' => 30 ) ); ?></div>
	<h2 class="empty-state__title"><?php esc_html_e( 'This listing is private', 'kajob' ); ?></h2>
	<p class="empty-state__text"><?php esc_html_e( 'You do not have permission to view it. It may be reserved for signed-in candidates or still awaiting approval.', 'kajob' ); ?></p>
	<div class="empty-state__actions">
		<a class="btn btn--primary" href="<?php echo esc_url( kajob_get_jobs_page_url() ); ?>">
			<?php kajob_icon( 'compass', array( 'size' => 17 ) ); ?>
			<?php esc_html_e( 'Browse open jobs', 'kajob' ); ?>
		</a>
		<?php if ( ! is_user_logged_in() ) : ?>
			<a class="btn btn--outline" href="<?php echo esc_url( kajob_get_signin_url( get_permalink() ) ); ?>">
				<?php kajob_icon( 'user', array( 'size' => 17 ) ); ?>
				<?php esc_html_e( 'Sign in', 'kajob' ); ?>
			</a>
		<?php endif; ?>
	</div>
</div>
