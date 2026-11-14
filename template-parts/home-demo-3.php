<?php
/** Employer-led, conversion-focused homepage demo. */
get_header();
$jobs_url   = kajob_get_jobs_page_url();
$post_url   = kajob_get_jm_page_url( 'submit_job_form' );
$stats      = kajob_get_board_stats();
$companies  = kajob_get_board_companies( 8 );
$cards      = kajob_get_job_cards( array( 'orderby' => 'date', 'order' => 'DESC', 'no_found_rows' => true ), array( 'per_page' => 3 ) );
?>
<section class="home-employer hero">
	<div class="container home-employer__grid">
		<div class="home-employer__copy"><span class="home-demo-eyebrow"><i></i><?php esc_html_e( 'The talent platform for teams that build', 'kajob' ); ?></span>
			<h1><?php esc_html_e( 'Meet the people who move you forward.', 'kajob' ); ?></h1>
			<p><?php esc_html_e( 'A clear, focused place to find your next great hire. Reach thoughtful candidates and make every opportunity count.', 'kajob' ); ?></p>
			<div class="home-employer__actions"><a class="btn btn--primary" href="<?php echo esc_url( $post_url ); ?>"><?php esc_html_e( 'Post a position', 'kajob' ); ?> <?php kajob_icon( 'arrow-right', array( 'size' => 16 ) ); ?></a><a class="btn btn--outline" href="<?php echo esc_url( $jobs_url ); ?>"><?php esc_html_e( 'Explore open roles', 'kajob' ); ?></a></div>
			<div class="home-employer__metric"><strong><?php echo esc_html( number_format_i18n( (int) $stats['companies'] ) ); ?></strong><span><?php esc_html_e( 'companies building their teams', 'kajob' ); ?></span></div>
		</div>
		<div class="home-employer__visual" aria-label="<?php esc_attr_e( 'A simple three-step hiring process', 'kajob' ); ?>">
			<div class="home-employer__orbit home-employer__orbit--one"></div><div class="home-employer__orbit home-employer__orbit--two"></div><div class="home-employer__center"><span>K</span><small><?php esc_html_e( 'Great people. Good work.', 'kajob' ); ?></small></div>
			<div class="home-employer__bubble home-employer__bubble--top"><b>01</b><span><?php esc_html_e( 'Create your role', 'kajob' ); ?></span></div>
			<div class="home-employer__bubble home-employer__bubble--right"><b>02</b><span><?php esc_html_e( 'Meet your shortlist', 'kajob' ); ?></span></div>
			<div class="home-employer__bubble home-employer__bubble--bottom"><b>03</b><span><?php esc_html_e( 'Make your next hire', 'kajob' ); ?></span></div>
		</div>
	</div>
</section>
<?php if ( $companies ) : ?><section class="home-employer-trust"><div class="container"><span><?php esc_html_e( 'A growing community of ambitious teams', 'kajob' ); ?></span><div><?php foreach ( array_slice( $companies, 0, 6 ) as $company ) : ?><b><?php echo esc_html( $company['name'] ); ?></b><?php endforeach; ?></div></div></section><?php endif; ?>
<section class="section"><div class="container"><div class="section__head"><div><span class="section__eyebrow"><?php esc_html_e( 'Fresh opportunities', 'kajob' ); ?></span><h2 class="section__title"><?php esc_html_e( 'For the people ready to make a difference.', 'kajob' ); ?></h2><p class="section__sub"><?php esc_html_e( 'Give candidates a clear view of the work, the team and the compensation.', 'kajob' ); ?></p></div><a class="section__link" href="<?php echo esc_url( $jobs_url ); ?>"><?php esc_html_e( 'View all opportunities', 'kajob' ); ?> <?php kajob_icon( 'arrow-right', array( 'size' => 16 ) ); ?></a></div><?php if ( ! empty( $cards['html'] ) ) : ?><div class="job-list"><?php echo $cards['html']; // phpcs:ignore WordPress.Security.EscapeOutput ?></div><?php else : ?><div class="home-demo-empty"><h3><?php esc_html_e( 'A clearer way to hire.', 'kajob' ); ?></h3><p><?php esc_html_e( 'Publish your first role to start meeting candidates.', 'kajob' ); ?></p><a class="btn btn--primary" href="<?php echo esc_url( $post_url ); ?>"><?php esc_html_e( 'Create your first listing', 'kajob' ); ?></a></div><?php endif; ?></div></section>
<section class="home-employer-cta"><div class="container"><div><span><?php esc_html_e( 'Built for better hiring', 'kajob' ); ?></span><h2><?php esc_html_e( 'The right people are closer than you think.', 'kajob' ); ?></h2><p><?php esc_html_e( 'Make your next role easy to find and even easier to trust.', 'kajob' ); ?></p></div><a class="btn btn--primary" href="<?php echo esc_url( $post_url ); ?>"><?php esc_html_e( 'Get started', 'kajob' ); ?> <?php kajob_icon( 'arrow-right', array( 'size' => 16 ) ); ?></a></div></section>
<?php get_footer(); ?>
