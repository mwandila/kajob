<?php
/**
 * "Nothing found" fallback.
 *
 * @package Kajob
 */

?>
<section class="empty-state">
	<div class="empty-state__icon"><?php kajob_icon( 'search', array( 'size' => 30 ) ); ?></div>
	<h2 class="empty-state__title"><?php esc_html_e( 'Nothing found', 'kajob' ); ?></h2>

	<?php if ( is_search() ) : ?>
		<p class="empty-state__text">
			<?php
			/* translators: %s: the search query. */
			printf( esc_html__( 'No article matched “%s”. Try a broader phrase, or search the job board instead.', 'kajob' ), esc_html( get_search_query() ) );
			?>
		</p>
	<?php elseif ( is_home() && current_user_can( 'publish_posts' ) ) : ?>
		<p class="empty-state__text"><?php esc_html_e( 'No articles have been published yet.', 'kajob' ); ?></p>
	<?php else : ?>
		<p class="empty-state__text"><?php esc_html_e( 'We could not find what you were looking for. A search usually helps.', 'kajob' ); ?></p>
	<?php endif; ?>

	<div class="error-404__search"><?php get_search_form(); ?></div>

	<div class="empty-state__actions">
		<a class="btn btn--primary" href="<?php echo esc_url( kajob_get_jobs_page_url() ); ?>">
			<?php kajob_icon( 'briefcase', array( 'size' => 17 ) ); ?>
			<?php esc_html_e( 'Browse jobs', 'kajob' ); ?>
		</a>
		<?php if ( is_home() && current_user_can( 'publish_posts' ) ) : ?>
			<a class="btn btn--outline" href="<?php echo esc_url( admin_url( 'post-new.php' ) ); ?>"><?php esc_html_e( 'Write the first article', 'kajob' ); ?></a>
		<?php endif; ?>
	</div>
</section>