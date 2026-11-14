<?php
/**
 * Candidate accounts and private CV storage.
 *
 * CV contents are encrypted into user meta rather than saved in the public
 * Media Library, and can only be attached to an application by the owner.
 *
 * @package Kajob
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

const KAJOB_CANDIDATE_CV_MAX_BYTES = 5 * 1024 * 1024;

/** Ensure the candidate account page exists. */
add_action( 'init', function () {
	$existing = get_posts( array(
		'post_type'      => 'page',
		'post_status'    => 'publish',
		'posts_per_page' => 1,
		'fields'         => 'ids',
		'meta_key'       => '_wp_page_template',
		'meta_value'     => 'page-candidate-account.php',
	) );

	if ( $existing ) {
		return;
	}

	$page_id = wp_insert_post( array(
		'post_title'   => __( 'Candidate Account', 'kajob' ),
		'post_name'    => 'candidate-account',
		'post_status'  => 'publish',
		'post_type'    => 'page',
		'post_content' => '',
	) );

	if ( $page_id && ! is_wp_error( $page_id ) ) {
		update_post_meta( $page_id, '_wp_page_template', 'page-candidate-account.php' );
	}
} );

/** Return the account page URL, with a stable fallback while it is created. */
function kajob_get_candidate_account_url() {
	$pages = get_posts( array(
		'post_type'      => 'page',
		'post_status'    => 'publish',
		'posts_per_page' => 1,
		'fields'         => 'ids',
		'meta_key'       => '_wp_page_template',
		'meta_value'     => 'page-candidate-account.php',
	) );

	return $pages ? get_permalink( $pages[0] ) : home_url( '/candidate-account/' );
}

/** Front-end sign-in URL that returns visitors to the page they requested. */
function kajob_get_signin_url( $redirect_to = '' ) {
	$url = kajob_get_candidate_account_url();
	if ( $redirect_to ) {
		$url = add_query_arg( 'redirect_to', esc_url_raw( $redirect_to ), $url );
		if ( kajob_is_employer_account_redirect( $redirect_to ) ) {
			$url = add_query_arg( 'account_type', 'employer', $url );
		}
	}
	return $url;
}

/** Check whether sign-in was requested from an employer workflow. */
function kajob_is_employer_account_redirect( $redirect_to ) {
	if ( ! function_exists( 'job_manager_get_page_id' ) ) {
		return false;
	}

	$redirect_path = wp_parse_url( $redirect_to, PHP_URL_PATH );
	foreach ( array( 'submit_job_form', 'job_dashboard' ) as $page_key ) {
		$page_id = absint( job_manager_get_page_id( $page_key ) );
		$page    = $page_id ? get_permalink( $page_id ) : '';
		if ( $page && untrailingslashit( $redirect_path ) === untrailingslashit( wp_parse_url( $page, PHP_URL_PATH ) ) ) {
			return true;
		}
	}

	return false;
}

/** Encryption helpers live in the required Kajob Core companion plugin. */
if ( ! function_exists( 'kajob_encrypt_candidate_cv' ) ) {
	function kajob_encrypt_candidate_cv( $contents ) {
		return false;
	}
}

/** Preserve a safe failure when Kajob Core is not active. */
if ( ! function_exists( 'kajob_decrypt_candidate_data' ) ) {
	function kajob_decrypt_candidate_data( $packed ) {
		return false;
	}
}

/** Read and decrypt the current user's saved CV. */
function kajob_get_candidate_cv( $user_id ) {
	$packed = get_user_meta( $user_id, 'kajob_candidate_cv', true );
	$name   = get_user_meta( $user_id, 'kajob_candidate_cv_name', true );

	if ( ! $packed || ! $name ) {
		return false;
	}

	$contents = kajob_decrypt_candidate_data( $packed );

	if ( false === $contents ) {
		return false;
	}

	return array(
		'contents'   => $contents,
		'name'       => sanitize_file_name( $name ),
		'size'       => strlen( $contents ),
		'uploaded_at' => (int) get_user_meta( $user_id, 'kajob_candidate_cv_uploaded_at', true ),
	);
}

/** Get the signed-in candidate's saved cover letter draft. */
function kajob_get_candidate_cover_letter( $user_id ) {
	$letter = kajob_decrypt_candidate_data( get_user_meta( $user_id, 'kajob_candidate_cover_letter', true ) );
	return false === $letter ? '' : $letter;
}

/** Return the current candidate's recent application records. */
function kajob_get_candidate_applications( $user_id ) {
	$applications = get_user_meta( $user_id, 'kajob_candidate_applications', true );
	if ( ! is_array( $applications ) ) {
		return array();
	}
	foreach ( $applications as &$application ) {
		if ( ! empty( $application['private_details'] ) ) {
			$details_json = kajob_decrypt_candidate_data( $application['private_details'] );
			$details      = false === $details_json ? array() : json_decode( $details_json, true );
			if ( is_array( $details ) ) {
				$application = array_merge( $application, $details );
			}
		}
	}
	unset( $application );

	usort( $applications, function ( $left, $right ) {
		return (int) ( $right['applied_at'] ?? 0 ) <=> (int) ( $left['applied_at'] ?? 0 );
	} );
	return $applications;
}

/** Register Kajob's candidate account exporter with WordPress. */
function kajob_register_candidate_exporter( $exporters ) {
	$exporters['kajob-candidate-data'] = array(
		'exporter_friendly_name' => __( 'Kajob candidate account data', 'kajob' ),
		'callback'               => 'kajob_export_candidate_personal_data',
	);
	return $exporters;
}
add_filter( 'wp_privacy_personal_data_exporters', 'kajob_register_candidate_exporter' );

/** Register Kajob's candidate account eraser with WordPress. */
function kajob_register_candidate_eraser( $erasers ) {
	$erasers['kajob-candidate-data'] = array(
		'eraser_friendly_name' => __( 'Kajob candidate account data', 'kajob' ),
		'callback'             => 'kajob_erase_candidate_personal_data',
	);
	return $erasers;
}
add_filter( 'wp_privacy_personal_data_erasers', 'kajob_register_candidate_eraser' );

/** Export Kajob profile data associated with a WordPress account. */
function kajob_export_candidate_personal_data( $email_address, $page = 1 ) {
	$user = get_user_by( 'email', sanitize_email( $email_address ) );
	$data = array();

	if ( $user && 1 === (int) $page ) {
		$cv = kajob_get_candidate_cv( $user->ID );
		if ( $cv ) {
			$data[] = array( 'name' => __( 'Saved CV file name', 'kajob' ), 'value' => $cv['name'] );
			$data[] = array( 'name' => __( 'Saved CV upload date', 'kajob' ), 'value' => $cv['uploaded_at'] ? wp_date( 'c', $cv['uploaded_at'] ) : '' );
			$data[] = array( 'name' => __( 'Saved CV file contents (hex)', 'kajob' ), 'value' => bin2hex( $cv['contents'] ) );
		}

		$cover_letter = kajob_get_candidate_cover_letter( $user->ID );
		if ( '' !== $cover_letter ) {
			$data[] = array( 'name' => __( 'Saved cover letter', 'kajob' ), 'value' => wp_strip_all_tags( $cover_letter ) );
		}

		foreach ( kajob_get_candidate_applications( $user->ID ) as $index => $application ) {
			unset( $application['private_details'] );
			$data[] = array(
				'name'  => sprintf( /* translators: %d: application number. */ __( 'Application %d', 'kajob' ), $index + 1 ),
				'value' => wp_json_encode( $application ),
			);
		}
	}

	return array( 'data' => $data, 'done' => true );
}

/** Erase Kajob candidate data for a WordPress account matched by email. */
function kajob_erase_candidate_personal_data( $email_address, $page = 1 ) {
	$user = get_user_by( 'email', sanitize_email( $email_address ) );
	$removed = 0;

	if ( $user && 1 === (int) $page ) {
		$keys = array(
			'kajob_candidate_cv',
			'kajob_candidate_cv_name',
			'kajob_candidate_cv_uploaded_at',
			'kajob_candidate_cover_letter',
			'kajob_candidate_applications',
			'kajob_saved_jobs',
		);
		foreach ( $keys as $key ) {
			if ( metadata_exists( 'user', $user->ID, $key ) ) {
				delete_user_meta( $user->ID, $key );
				$removed++;
			}
		}
	}

	return array(
		'items_removed'  => $removed > 0,
		'items_retained' => false,
		'messages'       => array(),
		'done'           => true,
	);
}

/** Add Kajob's candidate data handling summary to the site privacy policy. */
function kajob_add_candidate_privacy_policy_content() {
	if ( ! function_exists( 'wp_add_privacy_policy_content' ) ) {
		return;
	}

	$content  = '<p>' . esc_html__( 'Kajob stores candidate CVs and saved cover letters encrypted in WordPress user metadata. For signed-in candidates, it can also store up to 100 recent application records, including encrypted application details. Candidates can delete their saved CV and cover letter in their account. Site administrators can export or erase Kajob candidate account data from Tools → Export Personal Data and Tools → Erase Personal Data. Erasure does not remove application emails already sent to employers.', 'kajob' ) . '</p>';
	$content .= '<p>' . esc_html__( 'Email applications are sent to the employer address on the listing. Uploaded application documents are kept in a private temporary directory and removed after the site mail transport accepts the message; mail providers may retain copies under their own policies. If the optional AI assistant is configured, visitor messages and relevant public site content are sent to the site owner’s configured inference provider.', 'kajob' ) . '</p>';
	wp_add_privacy_policy_content( __( 'Kajob Theme', 'kajob' ), wp_kses_post( $content ) );
}
add_action( 'admin_init', 'kajob_add_candidate_privacy_policy_content' );

/** Save a private snapshot of an application after the employer email succeeds. */
function kajob_record_candidate_application( $user_id, $application ) {
	$user_id = absint( $user_id );
	if ( ! $user_id || ! get_user_by( 'id', $user_id ) ) {
		return false;
	}

	$applications = get_user_meta( $user_id, 'kajob_candidate_applications', true );
	$applications = is_array( $applications ) ? $applications : array();
	$private_details = array(
		'applicant_name'  => sanitize_text_field( $application['applicant_name'] ?? '' ),
		'applicant_email' => sanitize_email( $application['applicant_email'] ?? '' ),
		'phone'           => sanitize_text_field( $application['phone'] ?? '' ),
		'bio'             => sanitize_textarea_field( $application['bio'] ?? '' ),
		'cover_letter'    => wp_kses_post( $application['cover_letter'] ?? '' ),
		'documents'       => array_values( array_filter( array_map( 'sanitize_file_name', (array) ( $application['documents'] ?? array() ) ) ) ),
	);
	$encrypted_details = kajob_encrypt_candidate_cv( wp_json_encode( $private_details ) );
	if ( false === $encrypted_details ) {
		return false;
	}
	$application['id']           = wp_generate_uuid4();
	$application['job_id']       = absint( $application['job_id'] ?? 0 );
	$application['job_title']    = sanitize_text_field( $application['job_title'] ?? '' );
	$application['company']      = sanitize_text_field( $application['company'] ?? '' );
	$application['job_url']      = esc_url_raw( $application['job_url'] ?? '' );
	$application['applied_at']   = absint( $application['applied_at'] ?? current_time( 'timestamp' ) );
	$application['private_details'] = $encrypted_details;
	unset( $application['applicant_name'], $application['applicant_email'], $application['phone'], $application['bio'], $application['cover_letter'], $application['documents'] );
	$application['status']       = 'sent';
	array_unshift( $applications, $application );
	$applications = array_slice( $applications, 0, 100 );

	return update_user_meta( $user_id, 'kajob_candidate_applications', $applications );
}

/** Build an authenticated, owner-only CV view URL. */
function kajob_get_candidate_cv_url( $user_id ) {
	$url = add_query_arg( 'action', 'kajob_candidate_view_cv', admin_url( 'admin-post.php' ) );
	return wp_nonce_url( $url, 'kajob_view_cv_' . absint( $user_id ) );
}

add_action( 'admin_post_kajob_candidate_view_cv', 'kajob_stream_candidate_cv' );
add_action( 'admin_post_nopriv_kajob_candidate_view_cv', 'kajob_candidate_cv_login_required' );

/** Stream only the current owner's decrypted CV; never expose a public file URL. */
function kajob_stream_candidate_cv() {
	$user_id = get_current_user_id();
	$nonce   = isset( $_GET['_wpnonce'] ) ? sanitize_text_field( wp_unslash( $_GET['_wpnonce'] ) ) : '';
	if ( ! $user_id || ! wp_verify_nonce( $nonce, 'kajob_view_cv_' . $user_id ) ) {
		wp_die( esc_html__( 'This CV link has expired. Open your account and try again.', 'kajob' ), '', array( 'response' => 403 ) );
	}

	$cv = kajob_get_candidate_cv( $user_id );
	if ( ! $cv ) {
		wp_die( esc_html__( 'No saved CV was found in this account.', 'kajob' ), '', array( 'response' => 404 ) );
	}

	$extension = strtolower( pathinfo( $cv['name'], PATHINFO_EXTENSION ) );
	$types = array(
		'pdf'  => 'application/pdf',
		'doc'  => 'application/msword',
		'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
	);
	if ( ! isset( $types[ $extension ] ) ) {
		wp_die( esc_html__( 'This CV file type cannot be displayed.', 'kajob' ), '', array( 'response' => 415 ) );
	}

	nocache_headers();
	header( 'X-Content-Type-Options: nosniff' );
	header( 'Content-Type: ' . $types[ $extension ] );
	header( 'Content-Length: ' . strlen( $cv['contents'] ) );
	header( 'Content-Disposition: ' . ( 'pdf' === $extension ? 'inline' : 'attachment' ) . '; filename="' . rawurlencode( $cv['name'] ) . '"' );
	echo $cv['contents']; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- validated CV bytes are streamed to their owner.
	exit;
}

/** Send signed-out visitors to the branded account page before CV streaming. */
function kajob_candidate_cv_login_required() {
	wp_safe_redirect( kajob_get_signin_url( kajob_get_candidate_account_url() ) );
	exit;
}

/** Validate, encrypt, and save an uploaded CV for one candidate. */
function kajob_store_candidate_cv( $user_id, $file ) {
	if ( empty( $file ) || ! isset( $file['error'] ) || UPLOAD_ERR_OK !== (int) $file['error'] || empty( $file['tmp_name'] ) ) {
		return new WP_Error( 'kajob_cv_upload', __( 'Choose a CV file to upload.', 'kajob' ) );
	}

	if ( (int) $file['size'] < 1 || (int) $file['size'] > KAJOB_CANDIDATE_CV_MAX_BYTES ) {
		return new WP_Error( 'kajob_cv_size', __( 'Your CV must be 5 MB or smaller.', 'kajob' ) );
	}

	$allowed = array(
		'pdf'  => 'application/pdf',
		'doc'  => 'application/msword',
		'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
	);
	$checked = wp_check_filetype_and_ext( $file['tmp_name'], $file['name'], $allowed );
	$ext     = isset( $checked['ext'] ) ? strtolower( $checked['ext'] ) : '';
	$type    = isset( $checked['type'] ) ? $checked['type'] : '';

	if ( ! isset( $allowed[ $ext ] ) || $allowed[ $ext ] !== $type ) {
		return new WP_Error( 'kajob_cv_type', __( 'Upload a PDF, DOC or DOCX CV.', 'kajob' ) );
	}

	$contents = file_get_contents( $file['tmp_name'] ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents -- reads the validated temporary upload.
	$encrypted = false === $contents ? false : kajob_encrypt_candidate_cv( $contents );
	if ( false === $encrypted ) {
		return new WP_Error( 'kajob_cv_encrypt', __( 'The CV could not be saved securely. Please try again.', 'kajob' ) );
	}

	update_user_meta( $user_id, 'kajob_candidate_cv', $encrypted );
	update_user_meta( $user_id, 'kajob_candidate_cv_name', sanitize_file_name( $file['name'] ) );
	update_user_meta( $user_id, 'kajob_candidate_cv_uploaded_at', time() );

	return true;
}

/** Candidate account POST actions. */
add_action( 'template_redirect', 'kajob_handle_candidate_account_submission', 1 );
function kajob_handle_candidate_account_submission() {
	if ( 'POST' !== strtoupper( $_SERVER['REQUEST_METHOD'] ?? '' ) || empty( $_POST['kajob_candidate_action'] ) ) {
		return;
	}

	$action = sanitize_key( wp_unslash( $_POST['kajob_candidate_action'] ) );
	$nonce  = isset( $_POST['kajob_candidate_nonce'] ) ? sanitize_text_field( wp_unslash( $_POST['kajob_candidate_nonce'] ) ) : '';
	$url    = kajob_get_candidate_account_url();

	if ( ! wp_verify_nonce( $nonce, 'kajob_candidate_' . $action ) ) {
		kajob_candidate_account_redirect( $url, 'invalid' );
	}

	$trap = isset( $_POST['website'] ) ? trim( (string) wp_unslash( $_POST['website'] ) ) : '';
	if ( '' !== $trap ) {
		kajob_candidate_account_redirect( $url, 'saved' );
	}

	if ( 'login' === $action ) {
		$email    = isset( $_POST['email'] ) ? sanitize_email( wp_unslash( $_POST['email'] ) ) : '';
		$password = isset( $_POST['password'] ) ? (string) wp_unslash( $_POST['password'] ) : '';
		$account  = get_user_by( 'email', $email );
		$user     = wp_signon( array(
			'user_login'    => $account ? $account->user_login : $email,
			'user_password' => $password,
			'remember'      => ! empty( $_POST['remember'] ),
		), is_ssl() );
		if ( is_wp_error( $user ) ) {
			kajob_candidate_account_redirect( $url, 'login_error' );
		}

		$redirect_to = isset( $_POST['redirect_to'] ) ? esc_url_raw( wp_unslash( $_POST['redirect_to'] ) ) : '';
		$destination = $redirect_to ? wp_validate_redirect( $redirect_to, $url ) : $url;
		if ( untrailingslashit( $destination ) === untrailingslashit( $url ) ) {
			$destination = add_query_arg( 'candidate-status', 'signed_in', $destination );
		}
		wp_safe_redirect( $destination );
		exit;
	}

	if ( 'register' === $action ) {
		$name      = isset( $_POST['name'] ) ? sanitize_text_field( wp_unslash( $_POST['name'] ) ) : '';
		$email     = isset( $_POST['email'] ) ? sanitize_email( wp_unslash( $_POST['email'] ) ) : '';
		$password  = isset( $_POST['password'] ) ? (string) wp_unslash( $_POST['password'] ) : '';
		$confirm   = isset( $_POST['password_confirm'] ) ? (string) wp_unslash( $_POST['password_confirm'] ) : '';
		$consented = ! empty( $_POST['cv_consent'] );

		if ( '' === $name || ! is_email( $email ) || email_exists( $email ) || strlen( $password ) < 12 || $password !== $confirm || ! $consented ) {
			kajob_candidate_account_redirect( $url, 'register_error' );
		}

		$base = sanitize_user( current( explode( '@', $email ) ), true );
		$base = $base ? $base : 'candidate';
		$username = $base;
		$suffix   = 1;
		while ( username_exists( $username ) ) {
			$username = $base . $suffix;
			$suffix++;
		}

		$user_id = wp_insert_user( array(
			'user_login'   => $username,
			'user_email'   => $email,
			'user_pass'    => $password,
			'display_name' => $name,
			'role'         => 'subscriber',
		) );

		if ( is_wp_error( $user_id ) ) {
			kajob_candidate_account_redirect( $url, 'register_error' );
		}

		wp_set_current_user( $user_id );
		wp_set_auth_cookie( $user_id, true, is_ssl() );
		kajob_candidate_account_redirect( $url, 'registered' );
	}

	if ( 'save_cv' === $action && is_user_logged_in() ) {
		$result = isset( $_FILES['candidate_cv'] ) ? kajob_store_candidate_cv( get_current_user_id(), $_FILES['candidate_cv'] ) : new WP_Error( 'kajob_cv_missing', __( 'Choose a CV file to upload.', 'kajob' ) );
		kajob_candidate_account_redirect( $url, is_wp_error( $result ) ? 'cv_error' : 'cv_saved' );
	}

	if ( 'save_cover_letter' === $action && is_user_logged_in() ) {
		$cover_letter = isset( $_POST['candidate_cover_letter'] ) ? wp_kses_post( wp_unslash( $_POST['candidate_cover_letter'] ) ) : '';
		$cover_length = function_exists( 'mb_strlen' ) ? mb_strlen( wp_strip_all_tags( $cover_letter ), 'UTF-8' ) : strlen( wp_strip_all_tags( $cover_letter ) );
		if ( $cover_length > 4000 ) {
			kajob_candidate_account_redirect( $url, 'cover_error' );
		}
		if ( '' === trim( $cover_letter ) ) {
			delete_user_meta( get_current_user_id(), 'kajob_candidate_cover_letter' );
			kajob_candidate_account_redirect( $url, 'cover_deleted' );
		}
		$encrypted = kajob_encrypt_candidate_cv( $cover_letter );
		if ( false === $encrypted ) {
			kajob_candidate_account_redirect( $url, 'cover_error' );
		}
		update_user_meta( get_current_user_id(), 'kajob_candidate_cover_letter', $encrypted );
		kajob_candidate_account_redirect( $url, 'cover_saved' );
	}

	if ( 'delete_cv' === $action && is_user_logged_in() ) {
		delete_user_meta( get_current_user_id(), 'kajob_candidate_cv' );
		delete_user_meta( get_current_user_id(), 'kajob_candidate_cv_name' );
		delete_user_meta( get_current_user_id(), 'kajob_candidate_cv_uploaded_at' );
		kajob_candidate_account_redirect( $url, 'cv_deleted' );
	}

	kajob_candidate_account_redirect( $url, 'invalid' );
}

/** Redirect account form responses using short, non-sensitive status values. */
function kajob_candidate_account_redirect( $url, $status ) {
	wp_safe_redirect( add_query_arg( 'candidate-status', sanitize_key( $status ), $url ) );
	exit;
}
