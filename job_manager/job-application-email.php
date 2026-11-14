<?php
/**
 * Apply-by-email route, with a copy action so the address is usable on
 * machines without a mail client.
 *
 * Override of wp-job-manager/templates/job-application-email.php.
 *
 * @package Kajob
 * @var stdClass $apply
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<p>
	<?php esc_html_e( 'Send your CV and a short note to', 'kajob' ); ?>
	<button type="button" class="btn btn--ghost btn--sm" data-copy="<?php echo esc_attr( $apply->raw_email ); ?>">
		<?php kajob_icon( 'copy', array( 'size' => 14 ) ); ?>
		<span class="job_application_email"><?php echo esc_html( $apply->email ); ?></span>
	</button>
</p>