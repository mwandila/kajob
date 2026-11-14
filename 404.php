<?php
/**
 * 404 — offers a way back rather than a dead end.
 *
 * @package Kajob
 */

get_header();

$kajob_latest = kajob_get_job_cards(
	array( 'post_status' => 'publish', 'has_password' => false ),
	array( 'per_page' => 3, 'variant' => 'list' )
);

$kajob_topics = get_terms( array(
	'taxonomy'   => 'job_listing_category',
	'hide_empty' => true,
	'orderby'    => 'count',
	'order'      => 'DESC',
	'number'     => 8,
) );
?>

<div class="board notfound-board">
	<section class="board-hero kajob-grain">
		<div class="aurora" aria-hidden="true"><span class="aurora__blob aurora__blob--1"></span><span class="aurora__blob aurora__blob--2"></span></div>
		<div class="container board-hero__inner">
			<span class="section__eyebrow board-hero__eyebrow"><?php esc_html_e( 'Error 404', 'kajob' ); ?></span>
			<h1 class="board-hero__title"><?php esc_html_e( 'That page has moved on', 'kajob' ); ?></h1>
			<p class="board-hero__sub"><?php esc_html_e( 'The link may be out of date, or the listing was filled and taken down. The board below is always current.', 'kajob' ); ?></p>

			<form role="search" method="get" class="board-hero__search" action="<?php echo esc_url( home_url( '/' ) ); ?>">
				<div class="filter-input">
					<?php kajob_icon( 'search', array( 'size' => 18 ) ); ?>
					<label class="screen-reader-text" for="kajob-404-search"><?php esc_html_e( 'Search jobs and articles', 'kajob' ); ?></label>
					<input type="search" id="kajob-404-search" name="s" placeholder="<?php esc_attr_e( 'Role, skill, company or topic', 'kajob' ); ?>" />
				</div>
				<button type="submit" class="btn btn--primary"><?php esc_html_e( 'Search', 'kajob' ); ?></button>
			</form>

			<div class="board-hero__meta">
				<a class="chip chip--mint" href="<?php echo esc_url( kajob_get_jobs_page_url() ); ?>"><?php kajob_icon( 'briefcase', array( 'size' => 13 ) ); ?><?php esc_html_e( 'All jobs', 'kajob' ); ?></a>
				<a class="chip chip--violet" href="<?php echo esc_url( kajob_get_blog_url() ); ?>"><?php kajob_icon( 'file', array( 'size' => 13 ) ); ?><?php esc_html_e( 'Career advice', 'kajob' ); ?></a>
				<a class="chip" href="<?php echo esc_url( home_url( '/' ) ); ?>"><?php kajob_icon( 'home', array( 'size' => 13 ) ); ?><?php esc_html_e( 'Homepage', 'kajob' ); ?></a>
			</div>
		</div>
	</section>

	<?php if ( ! is_wp_error( $kajob_topics ) && count( $kajob_topics ) > 1 ) : ?>
		<nav class="term-nav container" aria-label="<?php esc_attr_e( 'Popular categories', 'kajob' ); ?>">
			<span class="term-nav__label"><?php esc_html_e( 'Popular', 'kajob' ); ?></span>
			<div class="term-nav__list">
				<?php foreach ( $kajob_topics as $kajob_topic ) : ?>
					<a class="chip" href="<?php echo esc_url( get_term_link( $kajob_topic ) ); ?>">
						<?php echo esc_html( $kajob_topic->name ); ?>
						<span class="term-nav__count"><?php echo esc_html( number_format_i18n( $kajob_topic->count ) ); ?></span>
					</a>
				<?php endforeach; ?>
			</div>
		</nav>
	<?php endif; ?>

	<?php if ( $kajob_latest['html'] ) : ?>
		<div class="container notfound-body">
			<header class="search-section__head">
				<h2 class="search-section__title"><?php kajob_icon( 'sparkle', array( 'size' => 19 ) ); ?><?php esc_html_e( 'Newest on the board', 'kajob' ); ?></h2>
				<a class="section__link" href="<?php echo esc_url( kajob_get_jobs_page_url() ); ?>">
					<?php esc_html_e( 'See every role', 'kajob' ); ?>
					<?php kajob_icon( 'arrow-right', array( 'size' => 15 ) ); ?>
				</a>
			</header>
			<div class="job-list"><?php echo $kajob_latest['html']; // phpcs:ignore WordPress.Security.EscapeOutput -- escaped in card helper. ?></div>
		</div>
	<?php endif; ?>
</div>

<?php
get_footer();