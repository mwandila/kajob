<?php
/**
 * Application card. The primary button is the employer's real application
 * route — a mailto with a prefilled subject, or their ATS URL — so applying
 * never depends on JavaScript.
 *
 * @package Kajob
 * @var array $args { post_id }
 */

$kajob_id     = isset( $args['post_id'] ) ? absint( $args['post_id'] ) : get_the_ID();
$kajob_apply  = kajob_job_apply( $kajob_id );
$kajob_salary = kajob_get_salary_label( $kajob_id );
$kajob_rows   = array();

$kajob_location = get_post_meta( $kajob_id, '_job_location', true );

if ( $kajob_location ) {
	$kajob_rows[] = array( 'pin', __( 'Location', 'kajob' ), $kajob_location );
}

if ( function_exists( 'wpjm_get_the_job_types' ) ) {
	$kajob_types = wpjm_get_the_job_types( $kajob_id );

	if ( $kajob_types && ! is_wp_error( $kajob_types ) ) {
		$kajob_rows[] = array( 'briefcase', __( 'Contract', 'kajob' ), implode( ', ', wp_list_pluck( $kajob_types, 'name' ) ) );
	}
}

$kajob_rows[] = array( 'globe', __( 'Remote', 'kajob' ), kajob_is_remote( $kajob_id ) ? __( 'Yes', 'kajob' ) : __( 'No', 'kajob' ) );
$kajob_rows[] = array(
	'clock',
	__( 'Posted', 'kajob' ),
	/* translators: %s: human-readable time difference. */
	sprintf( __( '%s ago', 'kajob' ), human_time_diff( get_post_time( 'U', true, $kajob_id ), current_time( 'timestamp' ) ) ),
);

$kajob_deadline = kajob_job_deadline( $kajob_id );

if ( $kajob_deadline ) {
	$kajob_rows[] = array( 'calendar', __( 'Closes', 'kajob' ), $kajob_deadline );
}
?>
<section class="apply-card" id="apply">
	<div class="apply-card__head">
		<?php if ( $kajob_salary ) : ?>
			<p class="apply-card__salary">
				<?php echo esc_html( $kajob_salary ); ?>
				<small><?php echo esc_html( kajob_salary_unit_label( $kajob_id ) ); ?></small>
			</p>
		<?php else : ?>
			<p class="apply-card__salary">
				<?php esc_html_e( 'Salary on request', 'kajob' ); ?>
				<small><?php esc_html_e( 'Ask the recruiter for the band', 'kajob' ); ?></small>
			</p>
		<?php endif; ?>
	</div>

	<div class="apply-card__body">
		<dl class="apply-card__rows">
			<?php foreach ( $kajob_rows as $kajob_row ) : ?>
				<div class="apply-card__row">
					<dt><?php kajob_icon( $kajob_row[0], array( 'size' => 15 ) ); ?><?php echo esc_html( $kajob_row[1] ); ?></dt>
					<dd><?php echo esc_html( $kajob_row[2] ); ?></dd>
				</div>
			<?php endforeach; ?>
		</dl>

		<?php if ( $kajob_deadline && strtotime( get_post_meta( $kajob_id, '_job_expires', true ) ) < current_time( 'timestamp' ) + ( 7 * DAY_IN_SECONDS ) ) : ?>
			<p class="apply-card__deadline">
				<?php kajob_icon( 'alert', array( 'size' => 16 ) ); ?>
				<?php echo esc_html( sprintf( __( 'Closing soon — apply before %s.', 'kajob' ), $kajob_deadline ) ); ?>
			</p>
		<?php endif; ?>

		<div class="apply-card__actions">
			<?php if ( $kajob_apply['open'] ) : ?>
				<a class="btn btn--primary btn--lg btn--block" href="<?php echo esc_url( 'email' === $kajob_apply['type'] ? '#kajob-application-form' : $kajob_apply['href'] ); ?>"
					<?php if ( 'url' === $kajob_apply['type'] ) : ?>target="_blank" rel="nofollow noopener"<?php endif; ?>
					data-apply-click="<?php echo esc_attr( $kajob_id ); ?>">
					<?php kajob_icon( 'url' === $kajob_apply['type'] ? 'external' : 'send', array( 'size' => 18 ) ); ?>
					<?php echo esc_html( 'email' === $kajob_apply['type'] ? __( 'Apply with Kajob', 'kajob' ) : $kajob_apply['label'] ); ?>
				</a>

				<?php get_job_manager_template( 'job-application.php', array( 'post_id' => $kajob_id ) ); ?>
				<?php if ( 'email' === $kajob_apply['type'] ) : ?>
					<?php get_template_part( 'template-parts/job', 'application-form', array( 'post_id' => $kajob_id ) ); ?>
				<?php endif; ?>
			<?php else : ?>
				<span class="btn btn--primary btn--lg btn--block" aria-disabled="true">
					<?php kajob_icon( 'alert', array( 'size' => 18 ) ); ?>
					<?php esc_html_e( 'Applications closed', 'kajob' ); ?>
				</span>
				<p class="apply-card__note"><?php echo esc_html( $kajob_apply['reason'] ); ?></p>
			<?php endif; ?>

			<div class="apply-card__secondary">
				<button type="button" class="btn btn--outline btn--sm" data-job-save="<?php echo esc_attr( $kajob_id ); ?>" aria-pressed="false">
					<?php kajob_icon( 'bookmark', array( 'size' => 16 ) ); ?>
					<span data-save-label><?php esc_html_e( 'Save job', 'kajob' ); ?></span>
				</button>
				<button type="button" class="btn btn--outline btn--sm" data-share="<?php echo esc_url( get_permalink( $kajob_id ) ); ?>" data-share-title="<?php echo esc_attr( get_the_title( $kajob_id ) ); ?>">
					<?php kajob_icon( 'share', array( 'size' => 16 ) ); ?>
					<?php esc_html_e( 'Share', 'kajob' ); ?>
				</button>
			</div>

			<p class="apply-card__note">
				<?php kajob_icon( 'shield', array( 'size' => 13 ) ); ?>
				<?php esc_html_e( 'Kajob never charges candidates. Never pay to apply.', 'kajob' ); ?>
			</p>
		</div>
	</div>
</section>
