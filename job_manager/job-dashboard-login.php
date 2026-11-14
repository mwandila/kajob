<?php
/**
 * Dashboard shown to signed-out visitors.
 *
 * Override of wp-job-manager/templates/job-dashboard-login.php.
 *
 * @package Kajob
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$kajob_login = apply_filters( 'job_manager_job_dashboard_login_url', kajob_get_signin_url( get_permalink() ) );
?>
<div id="job-manager-job-dashboard" class="jm-dashboard">
	<div class="gate">
		<span class="gate__icon" aria-hidden="true"><?php kajob_icon( 'briefcase', array( 'size' => 28 ) ); ?></span>
		<h2 class="gate__title"><?php esc_html_e( 'Sign in to manage your listings', 'kajob' ); ?></h2>
		<p class="gate__text"><?php esc_html_e( 'Your dashboard tracks every role you have posted — live, in review, filled and draft — and lets you edit, duplicate or close them in one click.', 'kajob' ); ?></p>

		<ul class="gate__list">
			<li><?php kajob_icon( 'check', array( 'size' => 16 ) ); ?><?php esc_html_e( 'Edit a live listing without taking it down', 'kajob' ); ?></li>
			<li><?php kajob_icon( 'check', array( 'size' => 16 ) ); ?><?php esc_html_e( 'Mark a role filled and stop new applications', 'kajob' ); ?></li>
			<li><?php kajob_icon( 'check', array( 'size' => 16 ) ); ?><?php esc_html_e( 'Duplicate a past listing to repost in seconds', 'kajob' ); ?></li>
		</ul>

		<div class="gate__actions">
			<a class="btn btn--primary btn--lg" href="<?php echo esc_url( $kajob_login ); ?>">
				<?php kajob_icon( 'user', array( 'size' => 17 ) ); ?>
				<?php esc_html_e( 'Sign in', 'kajob' ); ?>
			</a>
			<?php if ( job_manager_user_can_submit_job_listing() ) : ?>
				<a class="btn btn--outline btn--lg" href="<?php echo esc_url( kajob_get_jm_page_url( 'submit_job_form' ) ); ?>"><?php esc_html_e( 'Post a job', 'kajob' ); ?></a>
			<?php endif; ?>
		</div>
	</div>
</div>
