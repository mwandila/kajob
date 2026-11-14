<?php
/**
 * Template Name: AI Mode
 *
 * Full-page AI assistant in the style of an "AI Mode" experience: sidebar,
 * centered ask box with suggestion chips, and a chat conversation view.
 *
 * @package Kajob
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

get_header();

$chips = array(
	array( 'briefcase', __( 'Browse Jobs', 'kajob' ), __( 'Show me the latest jobs', 'kajob' ) ),
	array( 'globe', __( 'Remote Roles', 'kajob' ), __( 'Show me remote jobs', 'kajob' ) ),
	array( 'chart', __( 'Negotiate an Offer', 'kajob' ), __( 'I have a job offer. How can I negotiate the salary and benefits?', 'kajob' ) ),
	array( 'star', __( 'Stand Out to Employers', 'kajob' ), __( 'How can I make my CV and application stand out for the roles I want?', 'kajob' ) ),
	array( 'building', __( 'Hiring Advice', 'kajob' ), __( 'I am hiring. How can I write a clear, fair job listing that attracts strong candidates?', 'kajob' ) ),
);

$browse = array(
	array( 'briefcase', __( 'Find Jobs', 'kajob' ), kajob_get_jobs_page_url() ),
	array( 'plus', __( 'Post a Job', 'kajob' ), kajob_get_jm_page_url( 'submit_job_form' ) ),
	array( 'building', __( 'For Employers', 'kajob' ), kajob_get_jm_page_url( 'job_dashboard' ) ),
	array( 'file', __( 'Career Advice', 'kajob' ), kajob_get_blog_url() ),
);

?>

<div class="aimode" data-aimode>

	<aside class="aimode__side">
		<button type="button" class="aimode__new" data-ai-new>
			<?php kajob_icon( 'plus', array( 'size' => 16 ) ); ?>
			<span><?php esc_html_e( 'New chat', 'kajob' ); ?></span>
		</button>

		<div class="aimode__sidegroup"><?php esc_html_e( 'History', 'kajob' ); ?></div>
		<div class="aimode__history" data-ai-history></div>

		<div class="aimode__sidegroup"><?php esc_html_e( 'Browse', 'kajob' ); ?></div>
		<nav class="aimode__browse" aria-label="<?php esc_attr_e( 'Browse the board', 'kajob' ); ?>">
			<?php foreach ( $browse as $item ) : ?>
				<a href="<?php echo esc_url( $item[2] ); ?>">
					<?php kajob_icon( $item[0], array( 'size' => 16 ) ); ?>
					<span><?php echo esc_html( $item[1] ); ?></span>
				</a>
			<?php endforeach; ?>
		</nav>

		<div class="aimode__note">
			<strong><?php esc_html_e( 'Public AI', 'kajob' ); ?></strong>
			<p><?php esc_html_e( 'Get general job seeker or hiring guidance, plus details from public Kajob listings.', 'kajob' ); ?></p>
		</div>
	</aside>

	<section class="aimode__main">

		<div class="aimode__topbar">
			<button type="button" class="aimode__new aimode__new--compact" data-ai-new>
				<?php kajob_icon( 'plus', array( 'size' => 15 ) ); ?>
				<span><?php esc_html_e( 'New chat', 'kajob' ); ?></span>
			</button>
			<a class="aimode__close" href="<?php echo esc_url( home_url( '/' ) ); ?>" title="<?php esc_attr_e( 'Close AI Mode', 'kajob' ); ?>" aria-label="<?php esc_attr_e( 'Close AI Mode', 'kajob' ); ?>">
				<?php kajob_icon( 'x', array( 'size' => 17 ) ); ?>
			</a>
		</div>

		<div class="aimode__idle" data-ai-idle>
			<p class="aimode__eyebrow">
				<?php kajob_icon( 'sparkle', array( 'size' => 15 ) ); ?>
				<?php esc_html_e( 'Discover more with Kajob AI', 'kajob' ); ?>
			</p>
			<h1 class="aimode__title"><?php esc_html_e( 'Guidance for your next career move', 'kajob' ); ?></h1>
			<p class="aimode__sub"><?php esc_html_e( 'Choose your path: get job search and career advice, or practical guidance for hiring.', 'kajob' ); ?></p>

			<form class="aimode__ask" data-ai-form>
				<span class="aimode__askplus" aria-hidden="true"><?php kajob_icon( 'plus', array( 'size' => 18 ) ); ?></span>
				<input type="text" data-ai-input maxlength="500" autocomplete="off"
					placeholder="<?php esc_attr_e( 'Are you looking for work or hiring? Tell us what guidance you need.', 'kajob' ); ?>"
					aria-label="<?php esc_attr_e( 'Ask the assistant', 'kajob' ); ?>">
				<button type="submit" class="aimode__asksend" aria-label="<?php esc_attr_e( 'Send', 'kajob' ); ?>">
					<?php kajob_icon( 'arrow-up', array( 'size' => 18 ) ); ?>
				</button>
			</form>
			<label class="aimode__consent">
				<input type="checkbox" data-ai-consent required>
				<span><?php esc_html_e( 'I understand that my messages and relevant public site information will be sent to the AI provider configured by this site. I will not include sensitive personal information.', 'kajob' ); ?></span>
			</label>

			<div class="aimode__chips">
				<?php foreach ( $chips as $chip ) : ?>
					<button type="button" class="aimode__chip" data-ai-chip="<?php echo esc_attr( $chip[2] ); ?>">
						<?php kajob_icon( $chip[0], array( 'size' => 14 ) ); ?>
						<span><?php echo esc_html( $chip[1] ); ?></span>
					</button>
				<?php endforeach; ?>
			</div>

			<p class="aimode__disclaimer"><?php esc_html_e( 'AI can make mistakes. Check important job and company details before you apply.', 'kajob' ); ?></p>
		</div>

		<div class="aimode__chat" data-ai-chat hidden>
			<div class="aimode__log" data-ai-log aria-live="polite"></div>
			<form class="aimode__ask aimode__ask--docked" data-ai-form>
				<span class="aimode__askplus" aria-hidden="true"><?php kajob_icon( 'plus', array( 'size' => 18 ) ); ?></span>
				<input type="text" data-ai-input maxlength="500" autocomplete="off"
					placeholder="<?php esc_attr_e( 'Ask a follow-up…', 'kajob' ); ?>"
					aria-label="<?php esc_attr_e( 'Message the assistant', 'kajob' ); ?>">
				<button type="submit" class="aimode__asksend" aria-label="<?php esc_attr_e( 'Send', 'kajob' ); ?>">
					<?php kajob_icon( 'arrow-up', array( 'size' => 18 ) ); ?>
				</button>
			</form>
		</div>

	</section>
</div>

<?php
get_footer();
