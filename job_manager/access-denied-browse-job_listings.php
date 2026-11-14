<?php
/**
 * Access denied: browsing listings.
 *
 * Override of wp-job-manager/templates/access-denied-browse-job_listings.php.
 *
 * @package Kajob
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<div class="empty-state">
	<div class="empty-state__icon"><?php kajob_icon( 'shield', array( 'size' => 30 ) ); ?></div>
	<h2 class="empty-state__title"><?php esc_html_e( 'The board is members-only', 'kajob' ); ?></h2>
	<p class="empty-state__text"><?php esc_html_e( 'Sign in with a candidate account to browse and filter open roles.', 'kajob' ); ?></p>
	<div class="empty-state__actions">
		<a class="btn btn--primary" href="<?php echo esc_url( kajob_get_signin_url( kajob_get_jobs_page_url() ) ); ?>">
			<?php kajob_icon( 'user', array( 'size' => 17 ) ); ?>
			<?php esc_html_e( 'Sign in', 'kajob' ); ?>
		</a>
		<a class="btn btn--outline" href="<?php echo esc_url( home_url( '/' ) ); ?>">
			<?php kajob_icon( 'home', array( 'size' => 17 ) ); ?>
			<?php esc_html_e( 'Back to home', 'kajob' ); ?>
		</a>
	</div>
</div>
