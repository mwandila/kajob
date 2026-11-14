<?php
/**
 * Saved jobs.
 *
 * Server-renders the signed-in user's list; for guests the same shell is filled
 * from localStorage through kajob_saved_cards, so the page works either way.
 *
 * @package Kajob
 */

get_header();

$kajob_saved_ids = kajob_get_saved_job_ids();
$kajob_logged_in = is_user_logged_in();
$kajob_cards     = '';
$kajob_count     = 0;

if ( $kajob_saved_ids ) {
	$kajob_query = new WP_Query( array(
		'post_type'      => 'job_listing',
		'post_status'    => 'publish',
		'post__in'       => $kajob_saved_ids,
		'orderby'        => 'post__in',
		'posts_per_page' => count( $kajob_saved_ids ),
		'no_found_rows'  => true,
		'has_password'   => false,
	) );

	foreach ( $kajob_query->posts as $kajob_job ) {
		if ( function_exists( 'job_manager_user_can_view_job_listing' ) && ! job_manager_user_can_view_job_listing( $kajob_job->ID ) ) {
			continue;
		}

		$kajob_cards .= kajob_job_card( $kajob_job->ID, array( 'variant' => 'grid' ) );
		$kajob_count++;
	}
}
?>

<div class="board saved-board" data-saved-page data-logged-in="<?php echo $kajob_logged_in ? '1' : '0'; ?>">

	<section class="board-hero kajob-grain">
		<div class="container board-hero__inner">
			<span class="section__eyebrow board-hero__eyebrow"><?php esc_html_e( 'Your shortlist', 'kajob' ); ?></span>
			<h1 class="board-hero__title"><?php esc_html_e( 'Saved jobs', 'kajob' ); ?></h1>
			<p class="board-hero__sub">
				<?php
				echo $kajob_logged_in
					? esc_html__( 'Every role you bookmarked, synced to your account so it is here on any device you sign in on.', 'kajob' )
					: esc_html__( 'Bookmarks are kept in this browser. Sign in and they follow you to every device you use.', 'kajob' );
				?>
			</p>
			<div class="board-hero__meta">
				<span class="chip chip--coral"><?php kajob_icon( 'bookmark', array( 'size' => 13 ) ); ?><span data-saved-count><?php echo esc_html( number_format_i18n( $kajob_count ) ); ?></span> <?php esc_html_e( 'saved', 'kajob' ); ?></span>
				<?php if ( ! $kajob_logged_in ) : ?>
					<a class="chip chip--violet" href="<?php echo esc_url( kajob_get_signin_url( get_permalink() ) ); ?>"><?php kajob_icon( 'user', array( 'size' => 13 ) ); ?><?php esc_html_e( 'Sign in to sync', 'kajob' ); ?></a>
				<?php endif; ?>
			</div>
		</div>
	</section>

	<div class="container board-body">
		<div class="jobs-main">

			<div class="jobs-toolbar">
				<p class="jobs-toolbar__count">
					<strong data-saved-count><?php echo esc_html( number_format_i18n( $kajob_count ) ); ?></strong>
					<span><?php esc_html_e( 'roles on your shortlist', 'kajob' ); ?></span>
				</p>

				<div class="jobs-toolbar__controls">
					<label class="screen-reader-text" for="kajob-saved-sort"><?php esc_html_e( 'Sort saved jobs', 'kajob' ); ?></label>
					<select id="kajob-saved-sort" name="saved_sort" data-saved-sort>
						<option value="saved"><?php esc_html_e( 'Recently saved', 'kajob' ); ?></option>
						<option value="salary"><?php esc_html_e( 'Highest pay', 'kajob' ); ?></option>
						<option value="title"><?php esc_html_e( 'Title A–Z', 'kajob' ); ?></option>
						<option value="company"><?php esc_html_e( 'Company A–Z', 'kajob' ); ?></option>
					</select>

					<div class="view-toggle" role="group" aria-label="<?php esc_attr_e( 'Result layout', 'kajob' ); ?>">
						<button type="button" data-view="list" aria-pressed="false" aria-label="<?php esc_attr_e( 'List view', 'kajob' ); ?>"><?php kajob_icon( 'list', array( 'size' => 16 ) ); ?></button>
						<button type="button" data-view="grid" class="is-active" aria-pressed="true" aria-label="<?php esc_attr_e( 'Grid view', 'kajob' ); ?>"><?php kajob_icon( 'grid', array( 'size' => 16 ) ); ?></button>
					</div>

					<button type="button" class="btn btn--ghost btn--sm" data-saved-clear>
						<?php kajob_icon( 'trash', array( 'size' => 15 ) ); ?>
						<?php esc_html_e( 'Clear all', 'kajob' ); ?>
					</button>
				</div>
			</div>

			<div class="jobs-results" data-saved-results aria-busy="false">
				<div class="job-list job-list--grid" data-saved-list><?php echo $kajob_cards; // phpcs:ignore WordPress.Security.EscapeOutput -- escaped in card helper. ?></div>

				<div class="empty-state" data-saved-empty <?php echo $kajob_count ? 'hidden' : ''; ?>>
					<div class="empty-state__icon"><?php kajob_icon( 'bookmark', array( 'size' => 30 ) ); ?></div>
					<h2 class="empty-state__title"><?php esc_html_e( 'Nothing saved yet', 'kajob' ); ?></h2>
					<p class="empty-state__text"><?php esc_html_e( 'Tap the bookmark on any listing to keep it here. Your shortlist stays private to you.', 'kajob' ); ?></p>
					<div class="empty-state__actions">
						<a class="btn btn--primary" href="<?php echo esc_url( kajob_get_jobs_page_url() ); ?>">
							<?php kajob_icon( 'search', array( 'size' => 17 ) ); ?>
							<?php esc_html_e( 'Browse jobs', 'kajob' ); ?>
						</a>
					</div>
				</div>
			</div>

			<section class="alerts-card" data-reveal>
				<div class="alerts-card__body">
					<h2 class="alerts-card__title"><?php kajob_icon( 'mail', array( 'size' => 20 ) ); ?><?php esc_html_e( 'Let the next one come to you', 'kajob' ); ?></h2>
					<p class="alerts-card__text"><?php esc_html_e( 'One email a week with new roles that match your shortlist. No spam, unsubscribe in a click.', 'kajob' ); ?></p>
				</div>
				<form class="alerts-form" data-alerts-form>
					<label class="screen-reader-text" for="kajob-saved-alert-email"><?php esc_html_e( 'Email address', 'kajob' ); ?></label>
					<input type="email" id="kajob-saved-alert-email" name="email" required placeholder="<?php esc_attr_e( 'you@example.com', 'kajob' ); ?>" autocomplete="email" />
					<button type="submit" class="btn btn--primary"><?php esc_html_e( 'Notify me', 'kajob' ); ?></button>
				</form>
			</section>
		</div>
	</div>
</div>

<?php
get_footer();
