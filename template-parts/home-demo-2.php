<?php
/** Editorial, candidate-first homepage demo. */
get_header();
$jobs_url = kajob_get_jobs_page_url();
$total    = kajob_get_job_count();
$cards    = kajob_get_job_cards( array( 'orderby' => 'date', 'order' => 'DESC', 'no_found_rows' => true ), array( 'per_page' => 3 ) );
?>
<section class="home-editorial hero kajob-grain">
	<div class="container home-editorial__grid">
		<div class="home-editorial__copy">
			<span class="home-demo-eyebrow"><i></i><?php esc_html_e( 'A better way to work', 'kajob' ); ?></span>
			<h1><?php esc_html_e( 'Make your next move a good one.', 'kajob' ); ?></h1>
			<p><?php esc_html_e( 'Thoughtful opportunities from teams that value your time, your talent and the work you want to do next.', 'kajob' ); ?></p>
			<form class="home-editorial__search" role="search" method="get" action="<?php echo esc_url( $jobs_url ); ?>">
				<label class="screen-reader-text" for="editorial-keyword"><?php esc_html_e( 'Search jobs', 'kajob' ); ?></label>
				<input id="editorial-keyword" name="search_keywords" placeholder="<?php esc_attr_e( 'Job title, skill or company', 'kajob' ); ?>">
				<button class="btn btn--primary" type="submit"><?php esc_html_e( 'Explore roles', 'kajob' ); ?> <?php kajob_icon( 'arrow-right', array( 'size' => 16 ) ); ?></button>
			</form>
			<div class="home-editorial__proof"><div class="home-editorial__avatars"><span>AM</span><span>JL</span><span>RK</span></div><span><strong><?php echo esc_html( number_format_i18n( $total ) ); ?>+</strong> <?php esc_html_e( 'opportunities to explore', 'kajob' ); ?></span></div>
		</div>
		<div class="home-editorial__feature">
			<div class="home-editorial__art"><img src="<?php echo esc_url( KAJOB_URI . '/assets/images/home-demo/teamwork-scene.svg' ); ?>" alt="" width="1200" height="800" loading="eager" decoding="async"></div>
			<div class="home-editorial__feature-top"><span><?php esc_html_e( 'Editor’s pick', 'kajob' ); ?></span><span class="home-demo-live"><i></i><?php esc_html_e( 'Hiring now', 'kajob' ); ?></span></div>
			<div class="home-editorial__feature-icon">N</div>
			<p class="home-editorial__company">Northstar Studio · Design</p>
			<h2><?php esc_html_e( 'Senior Product Designer', 'kajob' ); ?></h2>
			<p><?php esc_html_e( 'Shape digital products used by millions, with a team that gives great ideas room to grow.', 'kajob' ); ?></p>
			<div class="home-editorial__tags"><span>Remote friendly</span><span>Full-time</span></div>
			<a href="<?php echo esc_url( $jobs_url ); ?>" class="home-editorial__feature-link"><?php esc_html_e( 'Discover this opportunity', 'kajob' ); ?> <?php kajob_icon( 'arrow-right', array( 'size' => 16 ) ); ?></a>
		</div>
	</div>
</section>
<section class="section home-editorial-jobs"><div class="container">
	<div class="section__head"><div><span class="section__eyebrow"><?php esc_html_e( 'The latest opportunities', 'kajob' ); ?></span><h2 class="section__title"><?php esc_html_e( 'Good work starts here.', 'kajob' ); ?></h2><p class="section__sub"><?php esc_html_e( 'A considered shortlist of new roles from ambitious teams.', 'kajob' ); ?></p></div><a class="section__link" href="<?php echo esc_url( $jobs_url ); ?>"><?php esc_html_e( 'Browse all jobs', 'kajob' ); ?> <?php kajob_icon( 'arrow-right', array( 'size' => 16 ) ); ?></a></div>
	<?php if ( ! empty( $cards['html'] ) ) : ?><div class="job-list"><?php echo $cards['html']; // phpcs:ignore WordPress.Security.EscapeOutput ?></div><?php else : ?><div class="home-demo-empty"><h3><?php esc_html_e( 'Your next opportunity is on its way.', 'kajob' ); ?></h3><p><?php esc_html_e( 'New roles will appear here as soon as teams start hiring.', 'kajob' ); ?></p></div><?php endif; ?>
</div></section>
<section class="home-editorial-note"><div class="container"><span><?php esc_html_e( 'Find your fit', 'kajob' ); ?></span><p><?php esc_html_e( 'Clear salary ranges. Flexible work. Teams with a point of view.', 'kajob' ); ?></p><a href="<?php echo esc_url( $jobs_url ); ?>"><?php esc_html_e( 'See where you could go', 'kajob' ); ?> <?php kajob_icon( 'arrow-right', array( 'size' => 16 ) ); ?></a></div></section>
<?php get_footer(); ?>
