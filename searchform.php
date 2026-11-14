<?php
/**
 * Search form partial.
 *
 * @package Kajob
 */

$kajob_search_id = 'kajob-search-' . wp_unique_id();
?>
<form role="search" method="get" class="search-form" action="<?php echo esc_url( home_url( '/' ) ); ?>">
	<label class="screen-reader-text" for="<?php echo esc_attr( $kajob_search_id ); ?>"><?php esc_html_e( 'Search articles', 'kajob' ); ?></label>
	<input type="search" id="<?php echo esc_attr( $kajob_search_id ); ?>" class="search-field" placeholder="<?php esc_attr_e( 'Search articles…', 'kajob' ); ?>" value="<?php echo esc_attr( get_search_query() ); ?>" name="s">
	<button type="submit" class="search-submit btn btn--primary">
		<?php kajob_icon( 'search', array( 'size' => 17 ) ); ?>
		<span class="screen-reader-text"><?php esc_html_e( 'Search', 'kajob' ); ?></span>
	</button>
</form>