<?php
/**
 * Company panel for a single job.
 *
 * @package Kajob
 * @var array $args { post_id, compact }
 */

$kajob_id      = isset( $args['post_id'] ) ? absint( $args['post_id'] ) : get_the_ID();
$kajob_company = get_post_meta( $kajob_id, '_company_name', true );

if ( ! $kajob_company ) {
	return;
}

$kajob_tagline = get_post_meta( $kajob_id, '_company_tagline', true );
$kajob_website = get_post_meta( $kajob_id, '_company_website', true );
$kajob_twitter = ltrim( (string) get_post_meta( $kajob_id, '_company_twitter', true ), '@' );
$kajob_video   = get_post_meta( $kajob_id, '_company_video', true );
$kajob_open    = kajob_company_role_count( $kajob_company, $kajob_id );
?>
<section class="job-panel company-card">
	<div class="company-card__head">
		<?php echo kajob_get_company_logo( $kajob_id, 'company-card__logo', 'kajob-logo' ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
		<div>
			<h2 class="company-card__name"><?php echo esc_html( $kajob_company ); ?></h2>
			<?php if ( $kajob_tagline ) : ?>
				<p class="company-card__tagline"><?php echo esc_html( $kajob_tagline ); ?></p>
			<?php endif; ?>
		</div>
	</div>

	<p class="u-muted u-mb-0">
		<?php
		printf(
			/* translators: 1: company name, 2: number of other open roles. */
			esc_html( _n( '%1$s has %2$s other open role on Kajob.', '%1$s has %2$s other open roles on Kajob.', $kajob_open, 'kajob' ) ),
			esc_html( $kajob_company ),
			esc_html( number_format_i18n( $kajob_open ) )
		);
		?>
	</p>

	<div class="company-card__links">
		<a class="btn btn--outline btn--sm" href="<?php echo esc_url( add_query_arg( 'search_keywords', $kajob_company, kajob_get_jobs_page_url() ) ); ?>">
			<?php kajob_icon( 'briefcase', array( 'size' => 15 ) ); ?>
			<?php esc_html_e( 'All roles here', 'kajob' ); ?>
		</a>

		<?php if ( $kajob_website ) : ?>
			<a class="btn btn--ghost btn--sm" href="<?php echo esc_url( $kajob_website ); ?>" target="_blank" rel="nofollow noopener">
				<?php kajob_icon( 'external', array( 'size' => 15 ) ); ?>
				<?php esc_html_e( 'Website', 'kajob' ); ?>
			</a>
		<?php endif; ?>

		<?php if ( $kajob_twitter ) : ?>
			<a class="btn btn--ghost btn--sm" href="<?php echo esc_url( 'https://x.com/' . $kajob_twitter ); ?>" target="_blank" rel="nofollow noopener">
				<?php kajob_icon( 'x-social', array( 'size' => 15 ) ); ?>
				@<?php echo esc_html( $kajob_twitter ); ?>
			</a>
		<?php endif; ?>
	</div>

	<?php
	if ( $kajob_video ) {
		$kajob_embed = wp_oembed_get( $kajob_video );

		if ( $kajob_embed ) {
			echo '<div class="company_video">' . $kajob_embed . '</div>'; // phpcs:ignore WordPress.Security.EscapeOutput -- oEmbed HTML is filtered by core.
		}
	}
	?>
</section>