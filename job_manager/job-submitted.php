<?php
/**
 * Confirmation after a listing is submitted.
 *
 * Override of wp-job-manager/templates/job-submitted.php.
 *
 * @package Kajob
 * @var WP_Post $job
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

do_action( 'job_manager_job_submitted_content_before', $job );

$kajob_job   = get_post( $job->ID );
$kajob_state = $kajob_job ? $kajob_job->post_status : 'pending';
$kajob_live  = 'publish' === $kajob_state;

$kajob_copy = array(
	'publish' => array(
		'check-circle',
		'mint',
		__( 'Your listing is live', 'kajob' ),
		__( 'Candidates can find it on the board right now. Share the link to send people straight to it.', 'kajob' ),
	),
	'pending' => array(
		'clock',
		'amber',
		__( 'Submitted for review', 'kajob' ),
		__( 'An editor checks every listing before it goes live. You will find it under “In review” on your dashboard, and you can still edit it while you wait.', 'kajob' ),
	),
	'draft'   => array(
		'file',
		'violet',
		__( 'Draft saved', 'kajob' ),
		__( 'Nothing is public yet. Pick it up from your dashboard whenever you are ready to finish and publish.', 'kajob' ),
	),
	'preview' => array(
		'file',
		'violet',
		__( 'Draft saved', 'kajob' ),
		__( 'Nothing is public yet. Pick it up from your dashboard whenever you are ready to finish and publish.', 'kajob' ),
	),
);

if ( ! isset( $kajob_copy[ $kajob_state ] ) ) {
	$kajob_copy[ $kajob_state ] = array( 'check-circle', 'mint', __( 'Listing submitted', 'kajob' ), __( 'Your listing was saved to your account.', 'kajob' ) );
}

list( $kajob_icon, $kajob_tone, $kajob_title, $kajob_text ) = $kajob_copy[ $kajob_state ];

ob_start();
?>
<div class="submit-result submit-result--<?php echo esc_attr( $kajob_tone ); ?>">
	<span class="submit-result__icon" aria-hidden="true"><?php kajob_icon( $kajob_icon, array( 'size' => 30 ) ); ?></span>
	<h2 class="submit-result__title"><?php echo esc_html( $kajob_title ); ?></h2>
	<p class="submit-result__text"><?php echo esc_html( $kajob_text ); ?></p>

	<?php if ( $kajob_job ) : ?>
		<div class="submit-result__card">
			<?php echo kajob_get_company_logo( $kajob_job->ID, 'submit-result__logo' ); // phpcs:ignore WordPress.Security.EscapeOutput -- escaped in helper. ?>
			<div>
				<strong><?php echo esc_html( get_the_title( $kajob_job ) ); ?></strong>
				<span class="jm-muted"><?php echo esc_html( get_post_meta( $kajob_job->ID, '_company_name', true ) ); ?></span>
			</div>
			<span class="chip chip--<?php echo esc_attr( $kajob_tone ); ?>"><?php echo esc_html( kajob_listing_state( $kajob_job->ID )[1] ); ?></span>
		</div>
	<?php endif; ?>

	<div class="submit-result__actions">
		<?php if ( $kajob_live && $kajob_job ) : ?>
			<a class="btn btn--primary btn--lg" href="<?php echo esc_url( get_permalink( $kajob_job->ID ) ); ?>">
				<?php kajob_icon( 'eye', array( 'size' => 17 ) ); ?>
				<?php esc_html_e( 'View listing', 'kajob' ); ?>
			</a>
			<button type="button" class="btn btn--outline btn--lg" data-copy="<?php echo esc_url( get_permalink( $kajob_job->ID ) ); ?>">
				<?php kajob_icon( 'link', array( 'size' => 17 ) ); ?>
				<?php esc_html_e( 'Copy link', 'kajob' ); ?>
			</button>
		<?php endif; ?>
		<a class="btn <?php echo $kajob_live ? 'btn--ghost' : 'btn--primary'; ?> btn--lg" href="<?php echo esc_url( kajob_get_jm_page_url( 'job_dashboard' ) ); ?>">
			<?php kajob_icon( 'chart', array( 'size' => 17 ) ); ?>
			<?php esc_html_e( 'Go to dashboard', 'kajob' ); ?>
		</a>
		<?php if ( job_manager_user_can_submit_job_listing() ) : ?>
			<a class="btn btn--ghost btn--lg" href="<?php echo esc_url( kajob_get_jm_page_url( 'submit_job_form' ) ); ?>"><?php esc_html_e( 'Post another', 'kajob' ); ?></a>
		<?php endif; ?>
	</div>
</div>
<?php
$kajob_content = ob_get_clean();

/** This filter is documented in wp-job-manager/templates/job-submitted.php. */
$kajob_content = apply_filters( 'job_manager_job_submitted_content', $kajob_content, $kajob_job );

echo $kajob_content; // phpcs:ignore WordPress.Security.EscapeOutput -- built escaped above, filterable by design.

do_action( 'job_manager_job_submitted_content_' . str_replace( '-', '_', sanitize_title( $kajob_state ) ), $kajob_job );
do_action( 'job_manager_job_submitted_content_after', sanitize_title( $kajob_state ), $kajob_job );