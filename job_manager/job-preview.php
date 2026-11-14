<?php
/**
 * Final preview step of the submission form.
 *
 * Override of wp-job-manager/templates/job-preview.php. The preview reuses the
 * single-listing panels so what the employer approves is what candidates see.
 *
 * @package Kajob
 * @var WP_Job_Manager_Form $form
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$kajob_id      = get_the_ID();
$kajob_renew   = class_exists( 'WP_Job_Manager_Helper_Renewals' ) && WP_Job_Manager_Helper_Renewals::is_renew_action();
$kajob_facts   = kajob_job_facts( $kajob_id );
$kajob_company = get_post_meta( $kajob_id, '_company_name', true );
?>
<form method="post" id="job_preview" class="job-preview" action="<?php echo esc_url( $form->get_action() ); ?>">
	<?php do_action( 'preview_job_form_start' ); ?>

	<div class="job-preview__bar">
		<div>
			<span class="chip chip--violet"><?php kajob_icon( 'eye', array( 'size' => 13 ) ); ?><?php esc_html_e( 'Preview', 'kajob' ); ?></span>
			<h2 class="job-preview__heading"><?php esc_html_e( 'This is what candidates will see', 'kajob' ); ?></h2>
		</div>
		<div class="job-preview__actions">
			<?php if ( ! $kajob_renew ) : ?>
				<button type="submit" name="edit_job" class="btn btn--outline job-manager-button-edit-listing">
					<?php kajob_icon( 'edit', array( 'size' => 16 ) ); ?>
					<?php esc_html_e( 'Edit listing', 'kajob' ); ?>
				</button>
			<?php endif; ?>
			<button type="submit" name="continue" id="job_preview_submit_button" class="btn btn--primary job-manager-button-submit-listing">
				<?php kajob_icon( 'send', array( 'size' => 16 ) ); ?>
				<?php echo esc_html( apply_filters( 'submit_job_step_preview_submit_text', __( 'Publish listing', 'kajob' ) ) ); ?>
			</button>
		</div>
	</div>

	<div class="job_listing_preview single_job_listing kajob-embedded-job">
		<header class="job-preview__head">
			<?php echo kajob_get_company_logo( $kajob_id, 'job-preview__logo' ); // phpcs:ignore WordPress.Security.EscapeOutput -- escaped in helper. ?>
			<div>
				<h1 class="job-preview__title"><?php wpjm_the_job_title(); ?></h1>
				<p class="job-preview__company"><?php echo esc_html( $kajob_company ); ?></p>
				<div class="job-hero__chips"><?php echo kajob_get_job_type_chips( $kajob_id, 3 ); // phpcs:ignore WordPress.Security.EscapeOutput -- escaped in helper. ?></div>
			</div>
		</header>

		<?php if ( $kajob_facts ) : ?>
			<?php get_template_part( 'template-parts/job-facts', null, array( 'post_id' => $kajob_id ) ); ?>
		<?php endif; ?>

		<section class="job-panel">
			<h2 class="job-panel__title"><?php kajob_icon( 'file', array( 'size' => 18 ) ); ?><?php esc_html_e( 'About the role', 'kajob' ); ?></h2>
			<div class="job-description entry-content"><?php wpjm_the_job_description(); ?></div>
		</section>

		<?php
		$kajob_apply = kajob_job_apply( $kajob_id );

		if ( $kajob_apply['open'] ) :
			?>
			<section class="job-panel">
				<h2 class="job-panel__title"><?php kajob_icon( 'send', array( 'size' => 18 ) ); ?><?php esc_html_e( 'How candidates apply', 'kajob' ); ?></h2>
				<p class="jm-muted">
					<?php echo esc_html( $kajob_apply['detail'] ); ?>
					<strong><?php echo esc_html( $kajob_apply['copy'] ); ?></strong>
				</p>
			</section>
		<?php endif; ?>

		<input type="hidden" name="job_id" value="<?php echo esc_attr( $form->get_job_id() ); ?>" />
		<input type="hidden" name="step" value="<?php echo esc_attr( $form->get_step() ); ?>" />
		<input type="hidden" name="job_manager_form" value="<?php echo esc_attr( $form->get_form_name() ); ?>" />
	</div>

	<div class="job-preview__foot">
		<p class="jm-muted">
			<?php
			echo job_manager_submission_requires_approval()
				? esc_html__( 'Publishing sends the listing for review. It goes live once an editor approves it.', 'kajob' )
				: esc_html__( 'Publishing makes the listing public immediately. You can edit or close it from your dashboard.', 'kajob' );
			?>
		</p>
		<button type="submit" name="continue" class="btn btn--primary btn--lg job-manager-button-submit-listing">
			<?php echo esc_html( apply_filters( 'submit_job_step_preview_submit_text', __( 'Publish listing', 'kajob' ) ) ); ?>
			<?php kajob_icon( 'arrow-right', array( 'size' => 17 ) ); ?>
		</button>
	</div>

	<?php do_action( 'preview_job_form_end' ); ?>
</form>