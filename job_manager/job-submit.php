<?php
/**
 * Job submission form.
 *
 * Override of wp-job-manager/templates/job-submit.php. Field rendering still
 * goes through the plugin's own form-field templates so validation, uploads,
 * drafts and reCAPTCHA keep working; the theme adds the progress rail and
 * groups the fields into readable sections.
 *
 * @package Kajob
 * @var string $action
 * @var array  $job_fields
 * @var array  $company_fields
 * @var int    $job_id
 * @var int    $step
 * @var string $form
 * @var string $submit_button_text
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$kajob_steps = array(
	__( 'Role details', 'kajob' ),
	__( 'Company', 'kajob' ),
	__( 'Preview & publish', 'kajob' ),
);

$kajob_current = ! empty( $step ) ? max( 1, (int) $step ) : 1;

// reCAPTCHA v3 needs its click handler on the submit buttons.
$kajob_captcha = class_exists( 'WP_Job_Manager\WP_Job_Manager_Recaptcha' )
	&& 'v3' === WP_Job_Manager\WP_Job_Manager_Recaptcha::instance()->get_recaptcha_version();
$kajob_onclick = $kajob_captcha ? ' onclick="jm_job_submit_click(event)"' : '';
?>
<form action="<?php echo esc_url( $action ); ?>" method="post" id="submit-job-form" class="job-manager-form" enctype="multipart/form-data" data-submit-form>

	<ol class="submit-steps" aria-label="<?php esc_attr_e( 'Submission progress', 'kajob' ); ?>">
		<?php foreach ( $kajob_steps as $kajob_index => $kajob_label ) : ?>
			<li class="submit-step <?php echo $kajob_index + 1 === $kajob_current ? 'is-active' : ( $kajob_index + 1 < $kajob_current ? 'is-done' : '' ); ?>">
				<span class="submit-step__num" aria-hidden="true">
					<?php echo $kajob_index + 1 < $kajob_current ? kajob_get_icon( 'check', array( 'size' => 12 ) ) : esc_html( $kajob_index + 1 ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
				</span>
				<?php echo esc_html( $kajob_label ); ?>
			</li>
		<?php endforeach; ?>
	</ol>

	<?php if ( isset( $resume_edit ) && $resume_edit ) : ?>
		<div class="notice notice--info">
			<?php kajob_icon( 'edit', array( 'size' => 18 ) ); ?>
			<p>
				<?php esc_html_e( 'You are editing an existing listing.', 'kajob' ); ?>
				<a href="<?php echo esc_url( add_query_arg( array( 'job_manager_form' => 'submit-job', 'new' => '1', 'key' => $resume_edit ) ) ); ?>">
					<?php esc_html_e( 'Start a new one instead', 'kajob' ); ?>
				</a>
			</p>
		</div>
	<?php endif; ?>

	<?php
	do_action( 'submit_job_form_start' );

	if ( apply_filters( 'submit_job_form_show_signin', true ) ) {
		get_job_manager_template( 'account-signin.php' );
	}

	if ( job_manager_user_can_post_job() || job_manager_user_can_edit_job( $job_id ) ) :
		do_action( 'submit_job_form_job_fields_start' );

		foreach ( $job_fields as $kajob_key => $kajob_field ) :
			?>
			<fieldset class="fieldset-<?php echo esc_attr( $kajob_key ); ?> fieldset-type-<?php echo esc_attr( $kajob_field['type'] ); ?>">
				<label for="<?php echo esc_attr( $kajob_key ); ?>">
					<?php
					echo wp_kses_post( $kajob_field['label'] );
					echo wp_kses_post( apply_filters( 'submit_job_form_required_label', $kajob_field['required'] ? '' : ' <small>' . __( '(optional)', 'kajob' ) . '</small>', $kajob_field ) );
					?>
				</label>
				<div class="field <?php echo $kajob_field['required'] ? 'required-field' : ''; ?>">
					<?php get_job_manager_template( 'form-fields/' . $kajob_field['type'] . '-field.php', array( 'key' => $kajob_key, 'field' => $kajob_field ) ); ?>
				</div>
			</fieldset>
			<?php
		endforeach;

		do_action( 'submit_job_form_job_fields_end' );

		if ( $company_fields ) :
			?>
			<h2 class="form-section-title"><?php kajob_icon( 'building', array( 'size' => 20 ) ); ?><?php esc_html_e( 'Company details', 'kajob' ); ?></h2>
			<?php
			do_action( 'submit_job_form_company_fields_start' );

			foreach ( $company_fields as $kajob_key => $kajob_field ) :
				?>
				<fieldset class="fieldset-<?php echo esc_attr( $kajob_key ); ?> fieldset-type-<?php echo esc_attr( $kajob_field['type'] ); ?>">
					<label for="<?php echo esc_attr( $kajob_key ); ?>">
						<?php
						echo wp_kses_post( $kajob_field['label'] );
						echo wp_kses_post( apply_filters( 'submit_job_form_required_label', $kajob_field['required'] ? '' : ' <small>' . __( '(optional)', 'kajob' ) . '</small>', $kajob_field ) );
						?>
					</label>
					<div class="field <?php echo $kajob_field['required'] ? 'required-field' : ''; ?>">
						<?php get_job_manager_template( 'form-fields/' . $kajob_field['type'] . '-field.php', array( 'key' => $kajob_key, 'field' => $kajob_field ) ); ?>
					</div>
				</fieldset>
				<?php
			endforeach;

			do_action( 'submit_job_form_company_fields_end' );
		endif;

		do_action( 'submit_job_form_end' );
		?>

		<div class="submit-actions">
			<input type="hidden" name="job_manager_form" value="<?php echo esc_attr( $form ); ?>" />
			<input type="hidden" name="job_id" value="<?php echo esc_attr( $job_id ); ?>" />
			<input type="hidden" name="step" value="<?php echo esc_attr( $step ); ?>" />

			<input type="submit" name="submit_job" class="button" value="<?php echo esc_attr( $submit_button_text ); ?>"<?php echo $kajob_onclick; // phpcs:ignore WordPress.Security.EscapeOutput -- fixed handler string. ?> />

			<?php if ( isset( $can_continue_later ) && $can_continue_later ) : ?>
				<input type="submit" name="save_draft" class="button secondary save_draft" value="<?php esc_attr_e( 'Save draft', 'kajob' ); ?>" formnovalidate<?php echo $kajob_onclick; // phpcs:ignore WordPress.Security.EscapeOutput ?> />
			<?php endif; ?>

			<span class="spinner" aria-hidden="true"></span>
		</div>

		<p class="submit-note">
			<?php kajob_icon( 'shield', array( 'size' => 14 ) ); ?>
			<?php
			echo get_option( 'job_manager_submission_requires_approval' )
				? esc_html__( 'Listings are reviewed before they go live. You can edit or close a role at any time from your dashboard.', 'kajob' )
				: esc_html__( 'Your listing goes live immediately. You can edit or close it at any time from your dashboard.', 'kajob' );
			?>
		</p>

	<?php else : ?>
		<?php do_action( 'submit_job_form_disabled' ); ?>

		<div class="notice notice--warning">
			<?php kajob_icon( 'alert', array( 'size' => 18 ) ); ?>
			<p><?php esc_html_e( 'Your account cannot post listings right now. Sign in with an employer account, or contact the site owner.', 'kajob' ); ?></p>
		</div>
	<?php endif; ?>
</form>