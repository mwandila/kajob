<?php
/**
 * No listings found (plugin widgets and legacy loops).
 *
 * Override of wp-job-manager/templates/content-no-jobs-found.php.
 *
 * @package Kajob
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( defined( 'DOING_AJAX' ) && DOING_AJAX ) : ?>
	<li class="no_job_listings_found"><?php esc_html_e( 'No listings match your search.', 'kajob' ); ?></li>
<?php else : ?>
	<div class="empty-state">
		<div class="empty-state__icon"><?php kajob_icon( 'inbox', array( 'size' => 30 ) ); ?></div>
		<h3 class="empty-state__title"><?php esc_html_e( 'No vacancies right now', 'kajob' ); ?></h3>
		<p class="empty-state__text"><?php esc_html_e( 'New roles are posted every week. Leave your email and we will tell you the moment one lands.', 'kajob' ); ?></p>
		<div class="empty-state__actions">
			<a class="btn btn--primary" href="#job-alerts"><?php kajob_icon( 'mail', array( 'size' => 17 ) ); ?><?php esc_html_e( 'Get job alerts', 'kajob' ); ?></a>
		</div>
	</div>
<?php endif; ?>