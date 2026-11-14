<?php
/**
 * Sidebar area.
 *
 * @package Kajob
 */

if ( ! is_active_sidebar( 'sidebar-1' ) ) {
	return;
}
?>

<aside id="secondary" class="page-layout__sidebar widget-area">
	<?php dynamic_sidebar( 'sidebar-1' ); ?>
</aside>
