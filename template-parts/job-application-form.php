<?php
/** Per-listing application form for jobs that accept applications by email. */
$job_id = isset( $args['post_id'] ) ? absint( $args['post_id'] ) : get_the_ID();
$status = isset( $_GET['kajob-application'] ) ? sanitize_key( wp_unslash( $_GET['kajob-application'] ) ) : '';
$applicant_user = is_user_logged_in() ? wp_get_current_user() : false;
$saved_cv       = $applicant_user ? kajob_get_candidate_cv( $applicant_user->ID ) : false;
$saved_cover_letter = $applicant_user ? kajob_get_candidate_cover_letter( $applicant_user->ID ) : '';
$messages = array(
	'sent'            => array( 'success', __( 'Your application has been sent and added to your candidate account history.', 'kajob' ) ),
	'sent_guest'      => array( 'success', __( 'Your application has been sent. Applications sent while signed in appear in your account history.', 'kajob' ) ),
	'sent_untracked'  => array( 'success', __( 'Your application was sent, but Kajob could not save it to your account history. Please contact Kajob if you need a copy.', 'kajob' ) ),
	'mail_error'      => array( 'error', __( 'The email could not be sent right now. Please try again later or use the employer’s email address.', 'kajob' ) ),
	'upload_error'    => array( 'error', __( 'Upload a PDF, DOC or DOCX file under 5 MB. You can attach up to 10 MB total.', 'kajob' ) ),
	'missing_document' => array( 'error', __( 'Add a résumé, attach a cover letter, or write a cover letter before submitting.', 'kajob' ) ),
	'incomplete'      => array( 'error', __( 'Recheck your email address and short bio. If both are valid, this job’s application contact may need an update.', 'kajob' ) ),
	'name_required'   => array( 'error', __( 'Add your full name to continue.', 'kajob' ) ),
	'email_invalid'   => array( 'error', __( 'Enter a complete, valid email address, such as name@example.com.', 'kajob' ) ),
	'bio_required'    => array( 'error', __( 'Add a short professional bio to continue.', 'kajob' ) ),
	'employer_contact_error' => array( 'error', __( 'This job’s application email is not configured correctly. Please contact Kajob for help.', 'kajob' ) ),
	'cover_error'     => array( 'error', __( 'Keep your cover letter under 4,000 characters and try again.', 'kajob' ) ),
	'invalid'         => array( 'error', __( 'Your form expired. Please review it and submit again.', 'kajob' ) ),
	'closed'          => array( 'error', __( 'Applications for this job are closed.', 'kajob' ) ),
);
?>
<div class="kajob-application" id="kajob-application-form">
	<?php if ( isset( $messages[ $status ] ) ) : ?>
		<div class="kajob-application__notice kajob-application__notice--<?php echo esc_attr( $messages[ $status ][0] ); ?>" role="status"><?php echo esc_html( $messages[ $status ][1] ); ?></div>
	<?php endif; ?>
	<details class="kajob-application__details" open>
		<summary><?php esc_html_e( 'Apply with your résumé', 'kajob' ); ?></summary>
		<form class="kajob-application__form" method="post" enctype="multipart/form-data">
			<?php wp_nonce_field( 'kajob_apply_' . $job_id, 'kajob_application_nonce' ); ?>
			<input type="hidden" name="kajob_job_id" value="<?php echo esc_attr( $job_id ); ?>">
			<input type="hidden" name="kajob_apply_submit" value="1">
			<div class="kajob-application__trap" aria-hidden="true"><label for="company-site-<?php echo esc_attr( $job_id ); ?>"><?php esc_html_e( 'Leave this field empty', 'kajob' ); ?></label><input type="text" name="company_site" id="company-site-<?php echo esc_attr( $job_id ); ?>" tabindex="-1" autocomplete="off"></div>
			<div class="kajob-application__fields">
				<p><label for="applicant-name-<?php echo esc_attr( $job_id ); ?>"><?php esc_html_e( 'Full name', 'kajob' ); ?> <span aria-hidden="true">*</span></label><input id="applicant-name-<?php echo esc_attr( $job_id ); ?>" name="applicant_name" type="text" required autocomplete="name" maxlength="120" value="<?php echo $applicant_user ? esc_attr( $applicant_user->display_name ) : ''; ?>"></p>
				<p><label for="applicant-email-<?php echo esc_attr( $job_id ); ?>"><?php esc_html_e( 'Email address', 'kajob' ); ?> <span aria-hidden="true">*</span></label><input id="applicant-email-<?php echo esc_attr( $job_id ); ?>" name="applicant_email" type="email" required autocomplete="email" maxlength="190" value="<?php echo $applicant_user ? esc_attr( $applicant_user->user_email ) : ''; ?>"></p>
				<p><label for="applicant-phone-<?php echo esc_attr( $job_id ); ?>"><?php esc_html_e( 'Phone (optional)', 'kajob' ); ?></label><input id="applicant-phone-<?php echo esc_attr( $job_id ); ?>" name="applicant_phone" type="tel" autocomplete="tel" maxlength="50"></p>
				<p class="kajob-application__wide"><label for="applicant-bio-<?php echo esc_attr( $job_id ); ?>"><?php esc_html_e( 'A short professional bio', 'kajob' ); ?> <span aria-hidden="true">*</span></label><textarea id="applicant-bio-<?php echo esc_attr( $job_id ); ?>" name="applicant_bio" rows="4" maxlength="1800" required placeholder="<?php esc_attr_e( 'Introduce yourself and tell the employer why your experience fits this role.', 'kajob' ); ?>"></textarea></p>
				<p><label for="applicant-resume-<?php echo esc_attr( $job_id ); ?>"><?php esc_html_e( 'Résumé / CV', 'kajob' ); ?></label><?php if ( $saved_cv ) : ?><label class="kajob-application__saved-cv"><input type="checkbox" name="use_saved_cv" value="1"> <?php printf( esc_html__( 'Attach my saved CV: %s', 'kajob' ), esc_html( $saved_cv['name'] ) ); ?></label><small><?php esc_html_e( 'Choose this box to attach your saved CV, or upload a different one below.', 'kajob' ); ?></small><?php endif; ?><input id="applicant-resume-<?php echo esc_attr( $job_id ); ?>" name="applicant_resume" type="file" accept=".pdf,.doc,.docx,application/pdf,application/msword,application/vnd.openxmlformats-officedocument.wordprocessingml.document"><small><?php esc_html_e( 'PDF, DOC or DOCX · max 5 MB', 'kajob' ); ?></small><?php if ( ! is_user_logged_in() ) : ?><small><a href="<?php echo esc_url( kajob_get_candidate_account_url() ); ?>"><?php esc_html_e( 'Create an account to save your CV for later.', 'kajob' ); ?></a></small><?php endif; ?></p>
				<p><label for="applicant-cover-file-<?php echo esc_attr( $job_id ); ?>"><?php esc_html_e( 'Cover letter file (optional)', 'kajob' ); ?></label><input id="applicant-cover-file-<?php echo esc_attr( $job_id ); ?>" name="applicant_cover_file" type="file" accept=".pdf,.doc,.docx,application/pdf,application/msword,application/vnd.openxmlformats-officedocument.wordprocessingml.document"><small><?php esc_html_e( 'PDF, DOC or DOCX · max 5 MB', 'kajob' ); ?></small></p>
				<div class="kajob-application__wide kajob-application__cover-editor">
					<label for="applicant-cover-letter-<?php echo esc_attr( $job_id ); ?>"><?php esc_html_e( 'Or write a cover letter', 'kajob' ); ?></label>
					<?php wp_editor( $saved_cover_letter, 'applicant-cover-letter-' . $job_id, array(
						'textarea_name' => 'applicant_cover_letter',
						'textarea_rows' => 6,
						'media_buttons' => false,
						'teeny'         => false,
						'quicktags'     => false,
						'tinymce'       => array(
							'toolbar1' => 'bold,italic,bullist,numlist,link,unlink,undo,redo',
							'toolbar2' => '',
						),
					) ); ?>
				</div>
			</div>
			<p class="kajob-application__privacy"><?php esc_html_e( 'Your application and attachments are emailed to this job’s employer. Uploaded application documents are not saved on Kajob. A saved CV is attached only when you select it above; your saved cover-letter draft is prefilled for you to edit before applying.', 'kajob' ); ?></p>
			<?php if ( ! is_user_logged_in() ) : ?><p class="kajob-application__track-note"><a href="<?php echo esc_url( kajob_get_candidate_account_url() ); ?>"><?php esc_html_e( 'Sign in or create a candidate account', 'kajob' ); ?></a> <?php esc_html_e( 'before applying to keep this application in your account history.', 'kajob' ); ?></p><?php endif; ?>
			<button class="btn btn--primary btn--block" type="submit"><?php kajob_icon( 'send', array( 'size' => 16 ) ); ?><?php esc_html_e( 'Send application', 'kajob' ); ?></button>
		</form>
	</details>
</div>
