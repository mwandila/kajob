<?php
/**
 * Application details, shown under the apply button.
 *
 * Override of wp-job-manager/templates/job-application.php. The theme's apply
 * card already renders the primary action, so this only prints the contact
 * route and fires the plugin's hooks for application add-ons.
 *
 * @package Kajob
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$kajob_apply = kajob_job_apply( isset( $post_id ) ? $post_id : get_the_ID() );

if ( ! $kajob_apply['open'] ) {
	return;
}
?>
<div class="job_application application">
	<?php do_action( 'job_application_start', (object) array( 'type' => $kajob_apply['type'] ) ); ?>

	<div class="application_details">
		<?php
		$method = get_the_job_application_method( isset( $post_id ) ? $post_id : get_the_ID() );

		/**
		 * Plugin hook: job_manager_application_details_email or _url. Add-ons
		 * such as Applications replace this with their own form.
		 */
		do_action( 'job_manager_application_details_' . $kajob_apply['type'], $method );
		?>
	</div>

	<?php do_action( 'job_application_end', $method ); ?>
</div>