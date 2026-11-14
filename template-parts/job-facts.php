<?php
/**
 * "At a glance" facts for a single job.
 *
 * @package Kajob
 * @var array $args { post_id }
 */

$kajob_id    = isset( $args['post_id'] ) ? absint( $args['post_id'] ) : get_the_ID();
$kajob_facts = kajob_job_facts( $kajob_id );

if ( ! $kajob_facts ) {
	return;
}
?>
<section class="job-panel">
	<h2 class="job-panel__title"><?php kajob_icon( 'sparkle', array( 'size' => 20 ) ); ?><?php esc_html_e( 'At a glance', 'kajob' ); ?></h2>
	<div class="job-facts">
		<?php foreach ( $kajob_facts as $kajob_fact ) : ?>
			<div class="job-fact">
				<span class="job-fact__icon" aria-hidden="true"><?php kajob_icon( $kajob_fact[0], array( 'size' => 18 ) ); ?></span>
				<span>
					<span class="job-fact__label"><?php echo esc_html( $kajob_fact[1] ); ?></span>
					<span class="job-fact__value"><?php echo esc_html( $kajob_fact[2] ); ?></span>
				</span>
			</div>
		<?php endforeach; ?>
	</div>
</section>