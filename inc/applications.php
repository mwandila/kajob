<?php
/**
 * Candidate applications for email-based listings.
 *
 * Applications are sent to the employer's listing email and are not retained
 * in the Media Library or in the public uploads directory.
 *
 * @package Kajob
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

add_action( 'template_redirect', 'kajob_handle_application_submission' );

/**
 * Process one application from a single job page.
 */
function kajob_handle_application_submission() {
	if ( 'POST' !== strtoupper( $_SERVER['REQUEST_METHOD'] ?? '' ) || empty( $_POST['kajob_apply_submit'] ) ) {
		return;
	}

	$job_id = isset( $_POST['kajob_job_id'] ) ? absint( wp_unslash( $_POST['kajob_job_id'] ) ) : 0;
	$job    = get_post( $job_id );

	if ( ! $job || 'job_listing' !== $job->post_type || 'publish' !== $job->post_status ) {
		wp_die( esc_html__( 'This job is no longer available.', 'kajob' ), '', array( 'response' => 404 ) );
	}

	$redirect = get_permalink( $job_id );
	$nonce    = isset( $_POST['kajob_application_nonce'] ) ? sanitize_text_field( wp_unslash( $_POST['kajob_application_nonce'] ) ) : '';

	if ( ! wp_verify_nonce( $nonce, 'kajob_apply_' . $job_id ) ) {
		kajob_application_redirect( $redirect, 'invalid' );
	}

	// This invisible field is a basic bot trap. Human visitors leave it blank.
	$company_site = isset( $_POST['company_site'] ) ? trim( (string) wp_unslash( $_POST['company_site'] ) ) : '';
	if ( '' !== $company_site ) {
		kajob_application_redirect( $redirect, 'sent' );
	}

	$method = function_exists( 'get_the_job_application_method' ) ? get_the_job_application_method( $job_id ) : false;
	$apply  = kajob_job_apply( $job_id );

	if ( ! $apply['open'] || ! $method || 'email' !== $method->type ) {
		kajob_application_redirect( $redirect, 'closed' );
	}

	$name        = isset( $_POST['applicant_name'] ) ? sanitize_text_field( wp_unslash( $_POST['applicant_name'] ) ) : '';
	$email       = isset( $_POST['applicant_email'] ) ? sanitize_email( wp_unslash( $_POST['applicant_email'] ) ) : '';
	$phone       = isset( $_POST['applicant_phone'] ) ? sanitize_text_field( wp_unslash( $_POST['applicant_phone'] ) ) : '';
	$bio         = isset( $_POST['applicant_bio'] ) ? sanitize_textarea_field( wp_unslash( $_POST['applicant_bio'] ) ) : '';
	$cover_letter = isset( $_POST['applicant_cover_letter'] ) ? wp_kses_post( wp_unslash( $_POST['applicant_cover_letter'] ) ) : '';
	$cover_text    = trim( wp_strip_all_tags( $cover_letter ) );
	$cover_length  = function_exists( 'mb_strlen' ) ? mb_strlen( $cover_text, 'UTF-8' ) : strlen( $cover_text );
	$recipient   = sanitize_email( $method->email );
	if ( $cover_length > 4000 ) {
		kajob_application_redirect( $redirect, 'cover_error' );
	}

	if ( '' === $name ) {
		kajob_application_redirect( $redirect, 'name_required' );
	}
	if ( ! is_email( $email ) ) {
		kajob_application_redirect( $redirect, 'email_invalid' );
	}
	if ( '' === $bio ) {
		kajob_application_redirect( $redirect, 'bio_required' );
	}
	if ( ! is_email( $recipient ) ) {
		kajob_application_redirect( $redirect, 'employer_contact_error' );
	}

	$resume     = isset( $_FILES['applicant_resume'] ) ? $_FILES['applicant_resume'] : null;
	$cover_file = isset( $_FILES['applicant_cover_file'] ) ? $_FILES['applicant_cover_file'] : null;
	$has_resume = $resume && UPLOAD_ERR_NO_FILE !== (int) $resume['error'];
	$saved_cv   = is_user_logged_in() && ! $has_resume && ! empty( $_POST['use_saved_cv'] ) ? kajob_get_candidate_cv( get_current_user_id() ) : false;
	$has_resume = $has_resume || (bool) $saved_cv;
	$has_cover  = $cover_file && UPLOAD_ERR_NO_FILE !== (int) $cover_file['error'];

	if ( ! $has_resume && ! $has_cover && '' === $cover_text ) {
		kajob_application_redirect( $redirect, 'missing_document' );
	}

	$files = array();
	foreach ( array( $resume, $cover_file ) as $file ) {
		if ( ! $file || UPLOAD_ERR_NO_FILE === (int) $file['error'] ) {
			continue;
		}

		if ( UPLOAD_ERR_OK !== (int) $file['error'] || empty( $file['tmp_name'] ) || (int) $file['size'] > 5 * MB_IN_BYTES ) {
			kajob_application_redirect( $redirect, 'upload_error' );
		}
		$files[] = $file;
	}

	if ( array_sum( wp_list_pluck( $files, 'size' ) ) > 10 * MB_IN_BYTES ) {
		kajob_application_redirect( $redirect, 'upload_error' );
	}
	if ( $saved_cv && $saved_cv['size'] + array_sum( wp_list_pluck( $files, 'size' ) ) > 10 * MB_IN_BYTES ) {
		kajob_application_redirect( $redirect, 'upload_error' );
	}

	$temp_dir = trailingslashit( sys_get_temp_dir() ) . 'kajob-apply-' . wp_generate_password( 24, false, false );
	if ( ! wp_mkdir_p( $temp_dir ) ) {
		kajob_application_redirect( $redirect, 'upload_error' );
	}
	@chmod( $temp_dir, 0700 ); // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged -- best-effort private permissions on supported hosts.

	$attachments = array();
	$upload_error = false;
	if ( $saved_cv ) {
		$extension = strtolower( pathinfo( $saved_cv['name'], PATHINFO_EXTENSION ) );
		$saved_path = trailingslashit( $temp_dir ) . 'saved-cv-' . wp_generate_password( 12, false, false ) . '.' . $extension;
		if ( false === file_put_contents( $saved_path, $saved_cv['contents'] ) ) { // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_read_file_put_contents -- writes a validated, decrypted CV to a private temporary directory for the email only.
			kajob_cleanup_application_files( $temp_dir, $attachments );
			kajob_application_redirect( $redirect, 'upload_error' );
		}
		@chmod( $saved_path, 0600 ); // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged -- best-effort private permissions on supported hosts.
		$attachments[] = $saved_path;
	}
	$allowed_mimes = array(
		'pdf'  => 'application/pdf',
		'doc'  => 'application/msword',
		'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
	);

	foreach ( $files as $file ) {
		$upload_filter = static function ( $uploads ) use ( $temp_dir ) {
			$uploads['path']    = $temp_dir;
			$uploads['url']     = '';
			$uploads['subdir']  = '';
			$uploads['basedir'] = $temp_dir;
			$uploads['baseurl'] = '';

			return $uploads;
		};

		add_filter( 'upload_dir', $upload_filter, 99 );
		$upload = wp_handle_upload( $file, array( 'test_form' => false, 'mimes' => $allowed_mimes ) );
		remove_filter( 'upload_dir', $upload_filter, 99 );

		if ( ! empty( $upload['error'] ) || empty( $upload['file'] ) ) {
			$upload_error = true;
			break;
		}

		$attachments[] = $upload['file'];
	}

	if ( $upload_error ) {
		kajob_cleanup_application_files( $temp_dir, $attachments );
		kajob_application_redirect( $redirect, 'upload_error' );
	}

	$subject = sanitize_text_field( sprintf(
		/* translators: 1: job title, 2: applicant name. */
		__( 'Application for %1$s — %2$s', 'kajob' ),
		wp_specialchars_decode( get_the_title( $job_id ), ENT_QUOTES ),
		$name
	) );
	$message = '<p>' . esc_html__( 'A candidate applied through your Kajob listing.', 'kajob' ) . '</p>';
	$message .= '<p><strong>' . esc_html__( 'Candidate', 'kajob' ) . ':</strong> ' . esc_html( $name ) . '<br>';
	$message .= '<strong>' . esc_html__( 'Email', 'kajob' ) . ':</strong> ' . esc_html( $email ) . '<br>';
	$message .= '<strong>' . esc_html__( 'Phone', 'kajob' ) . ':</strong> ' . esc_html( $phone ? $phone : __( 'Not provided', 'kajob' ) ) . '</p>';
	$message .= '<p><strong>' . esc_html__( 'Short bio', 'kajob' ) . '</strong><br>' . nl2br( esc_html( $bio ) ) . '</p>';
	$message .= '<p><strong>' . esc_html__( 'Cover letter', 'kajob' ) . '</strong></p>';
	$message .= $cover_text ? wpautop( $cover_letter ) : '<p>' . esc_html__( 'See attached cover letter, if provided.', 'kajob' ) . '</p>';
	$message .= '<p><strong>' . esc_html__( 'Job listing', 'kajob' ) . ':</strong> <a href="' . esc_url( get_permalink( $job_id ) ) . '">' . esc_html( get_the_title( $job_id ) ) . '</a></p>';
	$headers = array(
		'Reply-To: ' . $name . ' <' . $email . '>',
		'Content-Type: text/html; charset=UTF-8',
	);
	$sent    = wp_mail( $recipient, $subject, $message, $headers, $attachments );
	$history_saved = false;
	if ( $sent && is_user_logged_in() ) {
		$document_names = array();
		if ( $saved_cv ) {
			$document_names[] = $saved_cv['name'];
		} elseif ( $has_resume && $resume ) {
			$document_names[] = $resume['name'];
		}
		if ( $has_cover && $cover_file ) {
			$document_names[] = $cover_file['name'];
		}
		$history_saved = kajob_record_candidate_application( get_current_user_id(), array(
			'job_id'          => $job_id,
			'job_title'       => get_the_title( $job_id ),
			'company'         => get_post_meta( $job_id, '_company_name', true ),
			'job_url'         => get_permalink( $job_id ),
			'applied_at'      => current_time( 'timestamp' ),
			'applicant_name'  => $name,
			'applicant_email' => $email,
			'phone'           => $phone,
			'bio'             => $bio,
			'cover_letter'    => $cover_letter,
			'documents'       => $document_names,
		) );
	}

	kajob_cleanup_application_files( $temp_dir, $attachments );
	$application_status = ! $sent ? 'mail_error' : ( ! is_user_logged_in() ? 'sent_guest' : ( $history_saved ? 'sent' : 'sent_untracked' ) );
	kajob_application_redirect( $redirect, $application_status );
}

/** Redirect after processing so a refresh cannot resubmit an application. */
function kajob_application_redirect( $url, $status ) {
	wp_safe_redirect( add_query_arg( 'kajob-application', sanitize_key( $status ), $url ) . '#apply' );
	exit;
}

/** Delete the temporary application documents after mail has been handed off. */
function kajob_cleanup_application_files( $directory, $attachments = array() ) {
	foreach ( $attachments as $file ) {
		if ( is_file( $file ) && 0 === strpos( wp_normalize_path( $file ), wp_normalize_path( trailingslashit( $directory ) ) ) ) {
			wp_delete_file( $file );
		}
	}

	if ( is_dir( $directory ) ) {
		@rmdir( $directory ); // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged -- empty temporary directory cleanup is best-effort.
	}
}
