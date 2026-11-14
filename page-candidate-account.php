<?php
/** Template Name: Candidate Account */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

get_header();

$kajob_account_url = kajob_get_candidate_account_url();
$kajob_redirect_to = isset( $_GET['redirect_to'] ) ? wp_validate_redirect( esc_url_raw( wp_unslash( $_GET['redirect_to'] ) ), '' ) : '';
$kajob_employer_flow = ! empty( $_GET['account_type'] ) && 'employer' === sanitize_key( wp_unslash( $_GET['account_type'] ) );
$kajob_status      = isset( $_GET['candidate-status'] ) ? sanitize_key( wp_unslash( $_GET['candidate-status'] ) ) : '';
$kajob_messages    = array(
	'registered'     => array( 'success', __( 'Your candidate account is ready.', 'kajob' ) ),
	'signed_in'      => array( 'success', __( 'You are signed in.', 'kajob' ) ),
	'login_error'    => array( 'error', __( 'We could not sign you in. Check your email and password.', 'kajob' ) ),
	'register_error' => array( 'error', __( 'Please check the form. Use a new email, a password of at least 12 characters, and accept CV storage.', 'kajob' ) ),
	'cv_saved'       => array( 'success', __( 'Your CV is saved to your account.', 'kajob' ) ),
	'cv_deleted'     => array( 'success', __( 'Your saved CV has been deleted.', 'kajob' ) ),
	'cv_error'       => array( 'error', __( 'We could not save that file. Use a PDF, DOC or DOCX file no larger than 5 MB.', 'kajob' ) ),
	'cover_saved'    => array( 'success', __( 'Your cover letter draft has been saved.', 'kajob' ) ),
	'cover_deleted'  => array( 'success', __( 'Your saved cover letter draft has been deleted.', 'kajob' ) ),
	'cover_error'    => array( 'error', __( 'Your cover letter could not be saved. Please keep it under 4,000 characters and try again.', 'kajob' ) ),
	'invalid'        => array( 'error', __( 'That form expired. Please try again.', 'kajob' ) ),
);
$kajob_user = wp_get_current_user();
$kajob_cv   = is_user_logged_in() ? kajob_get_candidate_cv( get_current_user_id() ) : false;
$kajob_cover_letter = is_user_logged_in() ? kajob_get_candidate_cover_letter( get_current_user_id() ) : '';
$kajob_applications = is_user_logged_in() ? kajob_get_candidate_applications( get_current_user_id() ) : array();
?>

<section class="section candidate-account">
	<div class="container container--narrow">
		<header class="candidate-account__head">
			<span class="section__eyebrow"><?php echo $kajob_employer_flow ? esc_html__( 'For employers', 'kajob' ) : esc_html__( 'For job seekers', 'kajob' ); ?></span>
			<h1><?php echo $kajob_employer_flow ? esc_html__( 'Employer sign in', 'kajob' ) : esc_html__( 'Candidate account', 'kajob' ); ?></h1>
			<p class="u-muted"><?php echo $kajob_employer_flow ? esc_html__( 'Sign in to manage your Kajob listings.', 'kajob' ) : esc_html__( 'Manage your private CV and cover-letter draft, then reuse them when you apply for jobs.', 'kajob' ); ?></p>
		</header>

		<?php if ( isset( $kajob_messages[ $kajob_status ] ) ) : ?>
			<div class="candidate-account__notice candidate-account__notice--<?php echo esc_attr( $kajob_messages[ $kajob_status ][0] ); ?>" role="status">
				<?php echo esc_html( $kajob_messages[ $kajob_status ][1] ); ?>
			</div>
		<?php endif; ?>

		<?php if ( is_user_logged_in() ) : ?>
			<section class="candidate-account__panel">
				<div class="candidate-account__identity">
					<div>
						<h2><?php echo esc_html( $kajob_user->display_name ); ?></h2>
						<p class="u-muted"><?php echo esc_html( $kajob_user->user_email ); ?></p>
					</div>
					<a href="<?php echo esc_url( wp_logout_url( home_url( '/' ) ) ); ?>"><?php esc_html_e( 'Sign out', 'kajob' ); ?></a>
				</div>

				<h2><?php esc_html_e( 'Your saved CV', 'kajob' ); ?></h2>
				<?php if ( $kajob_cv ) : ?>
					<p class="candidate-account__saved-file">
						<strong><?php echo esc_html( $kajob_cv['name'] ); ?></strong>
						<span><?php echo esc_html( size_format( $kajob_cv['size'] ) ); ?></span>
					</p>
					<p><a class="btn btn--outline btn--sm" href="<?php echo esc_url( kajob_get_candidate_cv_url( get_current_user_id() ) ); ?>" target="_blank" rel="noopener"><?php esc_html_e( 'View / download CV', 'kajob' ); ?></a></p>
					<form method="post" class="candidate-account__delete">
						<?php wp_nonce_field( 'kajob_candidate_delete_cv', 'kajob_candidate_nonce' ); ?>
						<input type="hidden" name="kajob_candidate_action" value="delete_cv">
						<input type="text" name="website" class="candidate-account__trap" tabindex="-1" autocomplete="off" aria-hidden="true">
						<button type="submit" class="btn btn--outline btn--sm"><?php esc_html_e( 'Delete saved CV', 'kajob' ); ?></button>
					</form>
				<?php else : ?>
					<p class="u-muted"><?php esc_html_e( 'You have not saved a CV yet.', 'kajob' ); ?></p>
				<?php endif; ?>

				<form method="post" enctype="multipart/form-data" class="candidate-account__upload">
					<?php wp_nonce_field( 'kajob_candidate_save_cv', 'kajob_candidate_nonce' ); ?>
					<input type="hidden" name="kajob_candidate_action" value="save_cv">
					<label for="candidate-cv"><?php echo $kajob_cv ? esc_html__( 'Replace with a new CV', 'kajob' ) : esc_html__( 'Upload your CV', 'kajob' ); ?></label>
					<input id="candidate-cv" type="file" name="candidate_cv" required accept=".pdf,.doc,.docx,application/pdf,application/msword,application/vnd.openxmlformats-officedocument.wordprocessingml.document">
					<small><?php esc_html_e( 'PDF, DOC or DOCX · max 5 MB', 'kajob' ); ?></small>
					<p class="u-muted"><?php esc_html_e( 'Your CV is encrypted in your account and is not publicly downloadable. It is emailed to an employer only when you choose to use it in an application.', 'kajob' ); ?></p>
					<input type="text" name="website" class="candidate-account__trap" tabindex="-1" autocomplete="off" aria-hidden="true">
					<button type="submit" class="btn btn--primary"><?php esc_html_e( 'Save CV', 'kajob' ); ?></button>
				</form>

				<form method="post" class="candidate-account__cover-letter">
					<?php wp_nonce_field( 'kajob_candidate_save_cover_letter', 'kajob_candidate_nonce' ); ?>
					<input type="hidden" name="kajob_candidate_action" value="save_cover_letter">
					<label for="candidate-cover-letter"><?php esc_html_e( 'Cover letter draft', 'kajob' ); ?></label>
					<?php wp_editor( $kajob_cover_letter, 'candidate-cover-letter', array(
						'textarea_name' => 'candidate_cover_letter',
						'textarea_rows' => 8,
						'media_buttons' => false,
						'teeny'         => false,
						'quicktags'     => false,
						'tinymce'       => array(
							'toolbar1' => 'bold,italic,bullist,numlist,link,unlink,undo,redo',
							'toolbar2' => '',
						),
					) ); ?>
					<p class="u-muted"><?php esc_html_e( 'This draft is encrypted in your account. It will only be sent to an employer when you submit an application.', 'kajob' ); ?></p>
					<input type="text" name="website" class="candidate-account__trap" tabindex="-1" autocomplete="off" aria-hidden="true">
					<button type="submit" class="btn btn--outline"><?php esc_html_e( 'Save cover letter draft', 'kajob' ); ?></button>
				</form>

				<section class="candidate-account__applications">
					<h2><?php esc_html_e( 'Applications you have sent', 'kajob' ); ?></h2>
					<?php if ( $kajob_applications ) : ?>
						<div class="candidate-account__application-list">
							<?php foreach ( $kajob_applications as $kajob_application ) : ?>
								<article class="candidate-account__application">
									<div class="candidate-account__application-head">
										<div>
											<h3><?php if ( ! empty( $kajob_application['job_url'] ) ) : ?><a href="<?php echo esc_url( $kajob_application['job_url'] ); ?>"><?php echo esc_html( $kajob_application['job_title'] ); ?></a><?php else : echo esc_html( $kajob_application['job_title'] ); endif; ?></h3>
											<p class="u-muted"><?php echo esc_html( $kajob_application['company'] ?? '' ); ?></p>
										</div>
										<span class="candidate-account__application-status"><?php esc_html_e( 'Sent', 'kajob' ); ?></span>
									</div>
									<p class="candidate-account__application-date"><?php echo esc_html( wp_date( get_option( 'date_format' ), (int) $kajob_application['applied_at'] ) ); ?></p>
									<details>
										<summary><?php esc_html_e( 'View application details', 'kajob' ); ?></summary>
										<div class="candidate-account__application-details">
											<p><strong><?php esc_html_e( 'Name', 'kajob' ); ?>:</strong> <?php echo esc_html( $kajob_application['applicant_name'] ?? '' ); ?><br><strong><?php esc_html_e( 'Email', 'kajob' ); ?>:</strong> <?php echo esc_html( $kajob_application['applicant_email'] ?? '' ); ?></p>
											<p><strong><?php esc_html_e( 'Professional bio', 'kajob' ); ?></strong><br><?php echo nl2br( esc_html( $kajob_application['bio'] ?? '' ) ); ?></p>
											<?php if ( ! empty( $kajob_application['cover_letter'] ) ) : ?><div><strong><?php esc_html_e( 'Cover letter', 'kajob' ); ?></strong><?php echo wp_kses_post( wpautop( $kajob_application['cover_letter'] ) ); ?></div><?php endif; ?>
											<?php if ( ! empty( $kajob_application['documents'] ) && is_array( $kajob_application['documents'] ) ) : ?><p><strong><?php esc_html_e( 'Attachments emailed', 'kajob' ); ?>:</strong> <?php echo esc_html( implode( ', ', $kajob_application['documents'] ) ); ?></p><?php endif; ?>
										</div>
									</details>
								</article>
							<?php endforeach; ?>
						</div>
					<?php else : ?>
						<p class="u-muted"><?php esc_html_e( 'Applications you submit while signed in will appear here.', 'kajob' ); ?></p>
					<?php endif; ?>
				</section>
			</section>
		<?php else : ?>
			<div class="candidate-account__columns<?php echo $kajob_employer_flow ? ' candidate-account__columns--employer' : ''; ?>">
				<?php if ( ! $kajob_employer_flow ) : ?>
				<section class="candidate-account__panel">
					<h2><?php esc_html_e( 'Create an account', 'kajob' ); ?></h2>
					<p class="u-muted"><?php esc_html_e( 'Create a free account, then upload a CV to save it for future applications.', 'kajob' ); ?></p>
					<form method="post" class="candidate-account__form">
						<?php wp_nonce_field( 'kajob_candidate_register', 'kajob_candidate_nonce' ); ?>
						<input type="hidden" name="kajob_candidate_action" value="register">
						<p><label for="candidate-name"><?php esc_html_e( 'Full name', 'kajob' ); ?></label><input id="candidate-name" name="name" type="text" required autocomplete="name" maxlength="120"></p>
						<p><label for="candidate-register-email"><?php esc_html_e( 'Email address', 'kajob' ); ?></label><input id="candidate-register-email" name="email" type="email" required autocomplete="email" maxlength="190"></p>
						<p><label for="candidate-register-password"><?php esc_html_e( 'Password', 'kajob' ); ?></label><input id="candidate-register-password" name="password" type="password" required minlength="12" autocomplete="new-password"><small><?php esc_html_e( 'Use at least 12 characters.', 'kajob' ); ?></small></p>
						<p><label for="candidate-register-password-confirm"><?php esc_html_e( 'Confirm password', 'kajob' ); ?></label><input id="candidate-register-password-confirm" name="password_confirm" type="password" required minlength="12" autocomplete="new-password"></p>
						<p class="candidate-account__consent"><label><input type="checkbox" name="cv_consent" value="1" required> <?php esc_html_e( 'I agree that Kajob may securely store a CV I upload so I can reuse or delete it. A cover-letter draft is stored only if I choose to save one. These are sent only with an application I submit.', 'kajob' ); ?></label></p>
						<input type="text" name="website" class="candidate-account__trap" tabindex="-1" autocomplete="off" aria-hidden="true">
						<button type="submit" class="btn btn--primary"><?php esc_html_e( 'Create candidate account', 'kajob' ); ?></button>
					</form>
				</section>
				<?php endif; ?>

				<section class="candidate-account__panel">
					<h2><?php echo $kajob_employer_flow ? esc_html__( 'Sign in to your employer account', 'kajob' ) : esc_html__( 'Sign in', 'kajob' ); ?></h2>
					<form method="post" class="candidate-account__form">
						<?php wp_nonce_field( 'kajob_candidate_login', 'kajob_candidate_nonce' ); ?>
						<input type="hidden" name="kajob_candidate_action" value="login">
						<input type="hidden" name="redirect_to" value="<?php echo esc_attr( $kajob_redirect_to ); ?>">
						<p><label for="candidate-login-email"><?php esc_html_e( 'Email address', 'kajob' ); ?></label><input id="candidate-login-email" name="email" type="email" required autocomplete="username"></p>
						<p><label for="candidate-login-password"><?php esc_html_e( 'Password', 'kajob' ); ?></label><input id="candidate-login-password" name="password" type="password" required autocomplete="current-password"></p>
						<input type="text" name="website" class="candidate-account__trap" tabindex="-1" autocomplete="off" aria-hidden="true">
						<button type="submit" class="btn btn--outline"><?php esc_html_e( 'Sign in', 'kajob' ); ?></button>
						<a class="candidate-account__forgot" href="<?php echo esc_url( wp_lostpassword_url( $kajob_account_url ) ); ?>"><?php esc_html_e( 'Forgot password?', 'kajob' ); ?></a>
					</form>
					<?php if ( $kajob_employer_flow ) : ?>
						<p class="u-muted candidate-account__employer-signup"><?php esc_html_e( 'New employer? Start a job post and create your account as you submit it.', 'kajob' ); ?> <a href="<?php echo esc_url( kajob_get_jm_page_url( 'submit_job_form' ) ); ?>"><?php esc_html_e( 'Post a job', 'kajob' ); ?></a></p>
					<?php endif; ?>
				</section>
			</div>
		<?php endif; ?>
	</div>
</section>

<?php get_footer(); ?>
