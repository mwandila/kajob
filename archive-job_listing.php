<?php
/**
 * Job archive. The board owns its own query so filters, sorting and paging
 * behave identically on the first load and on every AJAX refresh.
 *
 * @package Kajob
 */

get_header();

echo kajob_render_board(); // phpcs:ignore WordPress.Security.EscapeOutput -- built by the theme renderer.

get_footer();