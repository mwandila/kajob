<?php
/**
 * Post card used in grids (blog, archives, search, front page).
 *
 * @package Kajob
 */

?>
<article <?php post_class( 'post-card' ); ?>>
	<a class="post-card__thumbnail" href="<?php the_permalink(); ?>" tabindex="-1" aria-hidden="true">
		<?php if ( kajob_has_article_photo_thumbnail( get_the_ID() ) ) : ?>
			<?php the_post_thumbnail( 'kajob-card', array( 'loading' => 'lazy' ) ); ?>
		<?php else : ?>
			<img class="post-card__fallback-image" src="<?php echo esc_url( kajob_get_article_cover_image( get_the_ID() ) ); ?>" alt="" loading="lazy" decoding="async">
		<?php endif; ?>
	</a>

	<div class="post-card__body">
		<div class="post-card__meta">
			<?php
			$card_categories = get_the_category();

			if ( $card_categories ) {
				printf( '<span class="chip chip--violet">%s</span>', esc_html( $card_categories[0]->name ) );
			}
			?>
			<span><?php echo esc_html( get_the_date() ); ?></span>
			<span class="job-card__sep" aria-hidden="true"></span>
			<span><?php echo esc_html( kajob_reading_time() ); ?></span>
		</div>

		<h2 class="post-card__title">
			<a href="<?php the_permalink(); ?>"><?php the_title(); ?></a>
		</h2>

		<p class="post-card__excerpt"><?php echo esc_html( get_the_excerpt() ); ?></p>

		<a class="post-card__link" href="<?php the_permalink(); ?>">
			<?php esc_html_e( 'Read article', 'kajob' ); ?>
			<?php kajob_icon( 'arrow-right', array( 'size' => 15 ) ); ?>
		</a>
	</div>
</article>
