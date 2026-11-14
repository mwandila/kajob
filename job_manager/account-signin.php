<?php
/**
 * Sign-in / registration block above the submission form.
 *
 * Override of wp-job-manager/templates/account-signin.php.
 *
 * @package Kajob
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( is_user_logged_in() ) :
	$kajob_user = wp_get_current_user();
	?>
	<div class="account-card">
		<span class="account-card__avatar" aria-hidden="true"><?php echo get_avatar( $kajob_user->ID, 44 ); // phpcs:ignore WordPress.Security.EscapeOutput ?></span>
		<div class="account-card__body">
			<strong><?php echo esc_html( $kajob_user->display_name ); ?></strong>
			<span><?php echo esc_html( $kajob_user->user_email ); ?></span>
		</div>
		<div class="account-card__actions">
			<a class="btn btn--outline btn--sm" href="<?php echo esc_url( kajob_get_jm_page_url( 'job_dashboard' ) ); ?>">
				<?php kajob_icon( 'briefcase', array( 'size' => 15 ) ); ?>
				<?php esc_html_e( 'My listings', 'kajob' ); ?>
			</a>
			<a class="btn btn--ghost btn--sm" href="<?php echo esc_url( apply_filters( 'submit_job_form_logout_url', wp_logout_url( get_permalink() ) ) ); ?>">
				<?php kajob_icon( 'logout', array( 'size' => 15 ) ); ?>
				<?php esc_html_e( 'Sign out', 'kajob' ); ?>
			</a>
		</div>
	</div>
<?php else : ?>
	<div class="account-card account-card--guest">
		<span class="account-card__badge" aria-hidden="true"><?php kajob_icon( 'user', array( 'size' => 22 ) ); ?></span>
		<div class="account-card__body">
			<strong><?php esc_html_e( 'Sign in to post a role', 'kajob' ); ?></strong>
			<span>
				<?php
				echo job_manager_enable_registration()
					? esc_html__( 'New here? Enter your email below and we will create your employer account as you submit.', 'kajob' )
					: esc_html__( 'An employer account is required before a listing can be published.', 'kajob' );
				?>
			</span>
		</div>
		<div class="account-card__actions">
			<a class="btn btn--primary btn--sm" href="<?php echo esc_url( apply_filters( 'submit_job_form_login_url', kajob_get_signin_url( get_permalink() ) ) ); ?>">
				<?php kajob_icon( 'user', array( 'size' => 15 ) ); ?>
				<?php esc_html_e( 'Sign in', 'kajob' ); ?>
			</a>
		</div>
	</div>

	<?php
	$kajob_registration_fields = job_manager_enable_registration() ? wpjm_get_registration_fields() : array();

	foreach ( $kajob_registration_fields as $kajob_key => $kajob_field ) :
		?>
		<fieldset class="fieldset-<?php echo esc_attr( $kajob_key ); ?>">
			<label for="<?php echo esc_attr( $kajob_key ); ?>">
				<?php
				echo esc_html( $kajob_field['label'] );
				echo wp_kses_post( apply_filters( 'submit_job_form_required_label', $kajob_field['required'] ? '' : ' <small>' . __( '(optional)', 'kajob' ) . '</small>', $kajob_field ) );
				?>
			</label>
			<div class="field <?php echo $kajob_field['required'] ? 'required-field draft-required' : ''; ?>">
				<?php get_job_manager_template( 'form-fields/' . $kajob_field['type'] . '-field.php', array( 'key' => $kajob_key, 'field' => $kajob_field ) ); ?>
			</div>
		</fieldset>
		<?php
	endforeach;

	do_action( 'job_manager_register_form' );
endif;
