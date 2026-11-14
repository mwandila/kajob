<?php
/**
 * Kajob Assistant — a public, tool-calling AI agent.
 *
 * Any visitor can chat with it. The model answers from the site's real data
 * by calling tools (search jobs, inspect a job, browse categories, search the
 * blog) instead of hallucinating listings.
 *
 * Architecture ported from the TicketTrail agent stack:
 * - NVIDIA hosted inference API (OpenAI-compatible, free key) — no OpenAI bill.
 * - Primary model + fallback chain (models get retired and answer 410 Gone).
 * - Bounded tool loop, strict input sanitation, per-IP rate limiting.
 * - Disabled entirely (no widget, no endpoint) until an API key is set.
 *
 * @package Kajob
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/* -------------------------------------------------------------------------
 * Configuration
 * ---------------------------------------------------------------------- */

/**
 * Base URL of the OpenAI-compatible NVIDIA inference endpoint.
 */
function kajob_ai_api_url() {
	return defined( 'KAJOB_AI_API_URL' ) ? KAJOB_AI_API_URL : 'https://integrate.api.nvidia.com/v1/chat/completions';
}

/**
 * Primary model; overridden by constant or option.
 */
function kajob_ai_model() {
	if ( defined( 'KAJOB_AI_MODEL' ) ) {
		return KAJOB_AI_MODEL;
	}

	$model = get_option( 'kajob_ai_model', 'z-ai/glm-5.3-flash' );

	return $model ? $model : 'z-ai/glm-5.3-flash';
}

/**
 * Fallback chain when the primary model fails (retired models answer 410).
 */
function kajob_ai_fallback_models() {
	$raw = defined( 'KAJOB_AI_FALLBACK_MODELS' )
		? KAJOB_AI_FALLBACK_MODELS
		: get_option( 'kajob_ai_fallback_models', 'nvidia/nemotron-3-super-120b-a12b,openai/gpt-oss-20b,qwen/qwen3-235b-a22b' );

	$models = array_filter( array_map( 'trim', explode( ',', (string) $raw ) ) );
	$models = array_unique( array_merge( array( kajob_ai_model() ), $models ) );

	return array_values( $models );
}

/**
 * The API key: wp-config constant first (safe for production), Customizer option second.
 */
function kajob_ai_api_key() {
	if ( defined( 'KAJOB_AI_API_KEY' ) && KAJOB_AI_API_KEY ) {
		return KAJOB_AI_API_KEY;
	}

	return (string) get_option( 'kajob_ai_api_key', '' );
}

/**
 * Whether the assistant is usable at all.
 */
function kajob_agent_enabled() {
	return (bool) kajob_ai_api_key() && (bool) apply_filters( 'kajob_agent_enabled', true );
}

/* -------------------------------------------------------------------------
 * Customizer: AI section
 * ---------------------------------------------------------------------- */

add_action( 'customize_register', function ( $wp_customize ) {
	$wp_customize->add_section( 'kajob_ai_assistant', array(
		'title'       => __( 'AI Assistant', 'kajob' ),
		'panel'       => 'kajob_options',
		'description' => __( 'The public "Kajob Assistant" chat widget. Get a free API key at build.nvidia.com, or define KAJOB_AI_API_KEY in wp-config.php (recommended). Without a key the assistant is hidden and its endpoint is disabled.', 'kajob' ),
	) );

	$wp_customize->add_setting( 'kajob_ai_api_key', array(
		'default'           => '',
		'sanitize_callback' => 'sanitize_text_field',
	) );
	$wp_customize->add_control( 'kajob_ai_api_key', array(
		'label'   => __( 'API Key', 'kajob' ),
		'section' => 'kajob_ai_assistant',
		'type'    => 'password',
	) );

	$wp_customize->add_setting( 'kajob_ai_model', array(
		'default'           => 'z-ai/glm-5.3-flash',
		'sanitize_callback' => 'sanitize_text_field',
	) );
	$wp_customize->add_control( 'kajob_ai_model', array(
		'label'       => __( 'Model ID', 'kajob' ),
		'description' => __( 'Primary NVIDIA NIM model, e.g. z-ai/glm-5.3-flash', 'kajob' ),
		'section'     => 'kajob_ai_assistant',
		'type'        => 'text',
	) );

	$wp_customize->add_setting( 'kajob_agent_greeting', array(
		'default'           => __( "Hi! I'm the Kajob assistant. Tell me what kind of job you're looking for — role, city, remote, salary — and I'll find real openings for you.", 'kajob' ),
		'sanitize_callback' => 'sanitize_textarea_field',
	) );
	$wp_customize->add_control( 'kajob_agent_greeting', array(
		'label'   => __( 'Greeting Message', 'kajob' ),
		'section' => 'kajob_ai_assistant',
		'type'    => 'textarea',
	) );
}, 20 );

/* -------------------------------------------------------------------------
 * AI Mode page assets + auto-created page
 * ---------------------------------------------------------------------- */

add_action( 'wp_enqueue_scripts', function () {
	if ( ! kajob_agent_enabled() || ! is_page_template( 'page-ai-mode.php' ) ) {
		return;
	}

	wp_enqueue_style( 'kajob-agent', KAJOB_URI . '/assets/css/agent.css', array( 'kajob-main' ), KAJOB_VERSION );
	wp_enqueue_script( 'kajob-agent', KAJOB_URI . '/assets/js/agent.js', array(), KAJOB_VERSION, true );

	wp_localize_script( 'kajob-agent', 'kajobAgent', array(
		'ajaxUrl'  => admin_url( 'admin-ajax.php' ),
		'nonce'    => wp_create_nonce( 'kajob_public' ),
		'welcome'  => kajob_agent_welcome_text(),
		'chips'    => array(
			__( 'Show me remote jobs', 'kajob' ),
			__( 'How can I negotiate my offer?', 'kajob' ),
			__( 'How can I make my application stand out?', 'kajob' ),
			__( 'How can I attract strong candidates?', 'kajob' ),
		),
		'i18n'     => array(
			'thinking'    => __( 'Thinking…', 'kajob' ),
			'error'       => __( 'Sorry, I encountered an error. Please try again.', 'kajob' ),
			'rateLimited' => __( 'You are sending messages too quickly. Please wait a bit and try again.', 'kajob' ),
		),
	) );
}, 30 );

/**
 * Make sure the AI Mode page exists (created lazily on the first admin visit).
 */
add_action( 'admin_init', function () {
	if ( ! kajob_agent_enabled() ) {
		return;
	}

	$page_id = absint( get_option( 'kajob_ai_mode_page', 0 ) );

	if ( $page_id && 'publish' === get_post_status( $page_id ) ) {
		return;
	}

	$existing = get_posts( array(
		'post_type'      => 'page',
		'post_status'    => 'publish',
		'posts_per_page' => 1,
		'fields'         => 'ids',
		'meta_key'       => '_wp_page_template',
		'meta_value'     => 'page-ai-mode.php',
	) );

	if ( ! empty( $existing ) ) {
		update_option( 'kajob_ai_mode_page', (int) $existing[0] );
		return;
	}

	$page_id = wp_insert_post( array(
		'post_title'   => __( 'AI Mode', 'kajob' ),
		'post_name'    => 'ai-mode',
		'post_status'  => 'publish',
		'post_type'    => 'page',
		'post_content' => '',
	) );

	if ( $page_id && ! is_wp_error( $page_id ) ) {
		update_post_meta( $page_id, '_wp_page_template', 'page-ai-mode.php' );
		update_option( 'kajob_ai_mode_page', (int) $page_id );
	}
} );

/**
 * Multi-line intro used by the AI Mode hero and first conversation turn.
 */
function kajob_agent_welcome_text() {
	$custom = get_option( 'kajob_agent_greeting', '' );

	if ( $custom && '' !== trim( (string) $custom ) ) {
		return trim( (string) $custom );
	}

	return trim( sprintf(
		/* translators: %s: site name. */
		__( "👋 Welcome to %s!\n\nI can guide you whether you're looking for work or hiring:\n• Find jobs, salary bands, and companies hiring\n• Get practical help with applications, interviews, and salary negotiation\n• Learn how to write clear job listings and attract strong candidates\n\nTell me which side you're on and what you need help with.", 'kajob' ),
		get_bloginfo( 'name' )
	) );
}

/* -------------------------------------------------------------------------
 * Endpoint
 * ---------------------------------------------------------------------- */

add_action( 'wp_ajax_kajob_agent', 'kajob_agent_ajax' );
add_action( 'wp_ajax_nopriv_kajob_agent', 'kajob_agent_ajax' );

function kajob_agent_ajax() {
	kajob_ajax_guard();

	$privacy_consent = isset( $_REQUEST['privacy_consent'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['privacy_consent'] ) ) : '';
	if ( '1' !== $privacy_consent ) {
		wp_send_json_error( array( 'message' => __( 'Please confirm the AI data-sharing notice before sending a message.', 'kajob' ) ), 400 );
	}

	if ( ! kajob_agent_enabled() ) {
		wp_send_json_error( array( 'message' => __( 'The assistant is not configured.', 'kajob' ) ), 503 );
	}

	// Rate limit per IP: 20 exchanges / 30 minutes, filterable.
	$ip     = isset( $_SERVER['REMOTE_ADDR'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) ) : 'unknown';
	$limit  = (int) apply_filters( 'kajob_agent_rate_limit', 20 );
	$key    = 'kajob_agent_' . md5( $ip );
	$window = 30 * MINUTE_IN_SECONDS;

	if ( (int) get_transient( $key ) >= $limit ) {
		wp_send_json_error( array( 'message' => __( 'You are sending messages too quickly. Please wait a bit and try again.', 'kajob' ) ), 429 );
	}

	set_transient( $key, (int) get_transient( $key ) + 1, $window );

	// Validate history: last 10 turns, plain text, capped length.
	$raw      = isset( $_REQUEST['messages'] ) ? wp_unslash( $_REQUEST['messages'] ) : '';
	$messages = array();

	if ( is_string( $raw ) ) {
		$decoded = json_decode( $raw, true );
		$raw     = is_array( $decoded ) ? $decoded : array();
	}

	if ( ! is_array( $raw ) ) {
		$raw = array();
	}

	foreach ( $raw as $item ) {
		if ( ! is_array( $item ) ) {
			continue;
		}

		$role    = isset( $item['role'] ) && in_array( $item['role'], array( 'user', 'assistant' ), true ) ? $item['role'] : '';
		$content = isset( $item['content'] ) ? sanitize_textarea_field( (string) $item['content'] ) : '';

		if ( ! $role || '' === trim( $content ) ) {
			continue;
		}

		$messages[] = array(
			'role'    => $role,
			'content' => mb_substr( $content, 0, 1500 ),
		);

		if ( count( $messages ) >= 10 ) {
			break;
		}
	}

	if ( empty( $messages ) ) {
		wp_send_json_error( array( 'message' => __( 'Nothing to answer.', 'kajob' ) ), 400 );
	}

	$used_ids = array();
	$reply    = kajob_agent_run( $messages, $used_ids );

	if ( is_wp_error( $reply ) ) {
		wp_send_json_error( array( 'message' => $reply->get_error_message() ), 500 );
	}

	// Attach real job cards for every job the agent surfaced this turn.
	$cards   = '';
	$job_ids = array_slice( array_values( array_unique( $used_ids ) ), 0, 4 );

	foreach ( $job_ids as $job_id ) {
		if ( 'job_listing' === get_post_type( $job_id ) && 'publish' === get_post_status( $job_id ) ) {
			$cards .= kajob_job_card( $job_id );
		}
	}

	wp_send_json_success( array(
		'reply' => $reply,
		'cards' => $cards ? '<div class="kajob-agent__cards">' . $cards . '</div>' : '',
	) );
}

/* -------------------------------------------------------------------------
 * Tool-calling conversation loop
 * ---------------------------------------------------------------------- */

/**
 * Run the conversation against the model, executing tools as requested.
 *
 * @param array   $messages      Chat history (role/content pairs).
 * @param array   $used_job_ids  Populated with job IDs surfaced this turn.
 * @return string|WP_Error Final assistant text.
 */
function kajob_agent_run( $messages, &$used_job_ids = array() ) {
	$payload_messages = array_merge(
		array( array(
			'role'    => 'system',
			'content' => kajob_agent_system_prompt(),
		) ),
		$messages
	);

	$tools = kajob_agent_tools();

	// Bounded loop: the model may chain at most 4 tool rounds.
	for ( $round = 0; $round < 4; $round++ ) {
		$body = array(
			'model'       => kajob_ai_model(),
			'messages'    => $payload_messages,
			'tools'       => $tools,
			'tool_choice' => 'auto',
			'temperature' => 0.4,
			'max_tokens'  => 700,
			'stream'      => false,
		);

		$response = kajob_ai_chat( $body );

		if ( is_wp_error( $response ) ) {
			return $response;
		}

		$message = $response['choices'][0]['message'] ?? null;

		if ( ! $message ) {
			return new WP_Error( 'kajob_agent_empty', __( 'The assistant could not produce an answer. Please try again.', 'kajob' ) );
		}

		$tool_calls = $message['tool_calls'] ?? array();

		if ( empty( $tool_calls ) || ! is_array( $tool_calls ) ) {
			$content = trim( (string) ( $message['content'] ?? '' ) );

			if ( '' !== $content ) {
				return $content;
			}

			return new WP_Error( 'kajob_agent_empty', __( 'The assistant could not produce an answer. Please try again.', 'kajob' ) );
		}

		// Record the assistant's tool request, execute, append tool results.
		$payload_messages[] = array(
			'role'       => 'assistant',
			'content'    => (string) ( $message['content'] ?? '' ),
			'tool_calls' => $tool_calls,
		);

		foreach ( $tool_calls as $call ) {
			$name      = isset( $call['function']['name'] ) ? sanitize_key( $call['function']['name'] ) : '';
			$arguments = isset( $call['function']['arguments'] ) ? (string) $call['function']['arguments'] : '{}';
			$args      = json_decode( $arguments, true );
			$args      = is_array( $args ) ? $args : array();

			$result = kajob_agent_execute_tool( $name, $args, $used_job_ids );

			$payload_messages[] = array(
				'role'         => 'tool',
				'tool_call_id' => (string) ( $call['id'] ?? '' ),
				'content'      => wp_json_encode( $result ),
			);
		}
	}

	return new WP_Error( 'kajob_agent_loop', __( 'The assistant took too many steps. Please rephrase your question.', 'kajob' ) );
}

/**
 * One chat completion request with model fallbacks.
 *
 * @param array $body OpenAI-compatible request body.
 * @return array|WP_Error Parsed JSON response.
 */
function kajob_ai_chat( $body ) {
	$api_key = kajob_ai_api_key();
	$last    = '';

	foreach ( kajob_ai_fallback_models() as $model ) {
		$body['model'] = $model;

		$response = wp_remote_post( kajob_ai_api_url(), array(
			'timeout' => 40,
			'headers' => array(
				'Authorization' => 'Bearer ' . $api_key,
				'Content-Type'  => 'application/json',
			),
			'body'    => wp_json_encode( $body ),
		) );

		if ( is_wp_error( $response ) ) {
			$last = $response->get_error_message();
			continue;
		}

		$code = (int) wp_remote_retrieve_response_code( $response );
		$raw  = wp_remote_retrieve_body( $response );

		if ( $code < 200 || $code >= 300 ) {
			$last = sprintf( 'Model %s API error: %d %s', $model, $code, mb_substr( $raw, 0, 200 ) );
			continue;
		}

		$data = json_decode( $raw, true );

		if ( ! is_array( $data ) || empty( $data['choices'][0]['message'] ) ) {
			$last = sprintf( 'Model %s returned an empty response.', $model );
			continue;
		}

		return $data;
	}

	return new WP_Error( 'kajob_agent_provider', __( 'The assistant is temporarily unavailable. Please try again in a moment.', 'kajob' ) );
}

/* -------------------------------------------------------------------------
 * Tools
 * ---------------------------------------------------------------------- */

/**
 * Tool definitions exposed to the model (OpenAI function-calling schema).
 */
function kajob_agent_tools() {
	$tools = array(
		array(
			'type'     => 'function',
			'function' => array(
				'name'        => 'search_jobs',
				'description' => 'Search published job listings on this site. ALWAYS use this before mentioning any job — never invent listings.',
				'parameters'  => array(
					'type'       => 'object',
					'properties' => array(
						'query'      => array( 'type' => 'string', 'description' => 'Free-text: role, skill or keyword.' ),
						'location'   => array( 'type' => 'string', 'description' => 'City, country, or "remote".' ),
						'job_type'   => array( 'type' => 'string', 'description' => 'One of: full-time, part-time, freelance, temporary, internship.' ),
						'remote_only'=> array( 'type' => 'boolean', 'description' => 'Set true to return remote positions only.' ),
						'limit'      => array( 'type' => 'integer', 'description' => 'Max results, 1-8. Default 6.' ),
					),
				),
			),
		),
		array(
			'type'     => 'function',
			'function' => array(
				'name'        => 'get_job',
				'description' => 'Get full details of one job listing by its numeric ID (use an ID returned by search_jobs).',
				'parameters'  => array(
					'type'       => 'object',
					'properties' => array(
						'id' => array( 'type' => 'integer', 'description' => 'Job listing ID.' ),
					),
					'required'   => array( 'id' ),
				),
			),
		),
		array(
			'type'     => 'function',
			'function' => array(
				'name'        => 'list_categories',
				'description' => 'List job categories with how many open jobs each has.',
				'parameters'  => array( 'type' => 'object', 'properties' => array() ),
			),
		),
		array(
			'type'     => 'function',
			'function' => array(
				'name'        => 'search_articles',
				'description' => 'Search the site blog for career advice and company articles.',
				'parameters'  => array(
					'type'       => 'object',
					'properties' => array(
						'query' => array( 'type' => 'string' ),
						'limit' => array( 'type' => 'integer', 'description' => 'Max results, 1-5. Default 3.' ),
					),
					'required'   => array( 'query' ),
				),
			),
		),
	);

	return $tools;
}

/**
 * Execute one tool call.
 *
 * @param string $name          Tool name.
 * @param array  $args          Sanitized tool arguments.
 * @param array  $used_job_ids  Accumulator for surfaced job IDs.
 * @return array Tool result.
 */
function kajob_agent_execute_tool( $name, $args, &$used_job_ids = array() ) {
	switch ( $name ) {
		case 'search_jobs':
			return kajob_agent_tool_search_jobs( $args, $used_job_ids );

		case 'get_job':
			return kajob_agent_tool_get_job( $args, $used_job_ids );

		case 'list_categories':
			return kajob_agent_tool_list_categories();

		case 'search_articles':
			return kajob_agent_tool_search_articles( $args );

		default:
			return array( 'error' => 'Unknown tool.' );
	}
}

/**
 * search_jobs tool.
 */
function kajob_agent_tool_search_jobs( $args, &$used_job_ids = array() ) {
	$query_args = array(
		'post_type'           => 'job_listing',
		'post_status'         => 'publish',
		'posts_per_page'      => max( 1, min( 8, isset( $args['limit'] ) ? absint( $args['limit'] ) : 6 ) ),
		'no_found_rows'       => true,
		'ignore_sticky_posts' => true,
		'has_password'        => false,
	);

	$query_text = isset( $args['query'] ) ? sanitize_text_field( (string) $args['query'] ) : '';
	$location   = isset( $args['location'] ) ? sanitize_text_field( (string) $args['location'] ) : '';

	if ( $query_text ) {
		$query_args['s'] = $query_text;
	}

	$meta_query = array();

	if ( $location && 'remote' === strtolower( $location ) ) {
		$meta_query[] = array(
			'key'   => '_remote_position',
			'value' => '1',
		);
	} elseif ( $location ) {
		$meta_query[] = array(
			'key'     => '_job_location',
			'value'   => $location,
			'compare' => 'LIKE',
		);
	}

	if ( ! empty( $args['remote_only'] ) ) {
		$meta_query[] = array(
			'key'   => '_remote_position',
			'value' => '1',
		);
	}

	$job_type = isset( $args['job_type'] ) ? sanitize_key( (string) $args['job_type'] ) : '';

	if ( $job_type && taxonomy_exists( 'job_listing_type' ) ) {
		$query_args['tax_query'] = array(
			array(
				'taxonomy' => 'job_listing_type',
				'field'    => 'slug',
				'terms'    => $job_type,
			),
		);
	}

	if ( $meta_query ) {
		$query_args['meta_query'] = $meta_query;
	}

	if ( ! kajob_is_job_manager_active() ) {
		return array(
			'count' => 0,
			'jobs'  => array(),
			'note'  => 'The jobs engine is not installed on this site yet.',
		);
	}

	$query = new WP_Query( $query_args );
	$jobs  = array();

	foreach ( $query->posts as $job ) {
		$used_job_ids[] = (int) $job->ID;

		$jobs[] = array(
			'id'       => (int) $job->ID,
			'title'    => get_the_title( $job ),
			'company'  => get_post_meta( $job->ID, '_company_name', true ),
			'location' => get_post_meta( $job->ID, '_job_location', true ),
			'remote'   => (bool) kajob_is_remote( $job->ID ),
			'salary'   => kajob_get_salary_label( $job->ID ),
			'url'      => get_permalink( $job->ID ),
			'posted'   => get_the_date( '', $job ),
		);
	}

	return array(
		'count'  => count( $jobs ),
		'jobs'   => $jobs,
		'total_on_board' => kajob_get_job_count(),
		'note'   => $jobs ? 'Link jobs with their url. Suggest opening the listing to apply.' : 'No jobs matched. Suggest broader terms or a job alert.',
	);
}

/**
 * get_job tool.
 */
function kajob_agent_tool_get_job( $args, &$used_job_ids = array() ) {
	$id = isset( $args['id'] ) ? absint( $args['id'] ) : 0;

	if ( ! $id || 'job_listing' !== get_post_type( $id ) || 'publish' !== get_post_status( $id ) ) {
		return array( 'error' => 'That job ID is not a published listing.' );
	}

	$used_job_ids[] = $id;

	$content = get_post_field( 'post_content', $id );
	$content = wp_strip_all_tags( $content );

	$deadline = get_post_meta( $id, '_job_expires', true );

	return array(
		'id'        => $id,
		'title'     => get_the_title( $id ),
		'company'   => get_post_meta( $id, '_company_name', true ),
		'location'  => get_post_meta( $id, '_job_location', true ),
		'remote'    => (bool) kajob_is_remote( $id ),
		'salary'    => kajob_get_salary_label( $id ),
		'types'     => wp_get_object_terms( $id, 'job_listing_type', array( 'fields' => 'names' ) ),
		'apply_url' => get_permalink( $id ),
		'deadline'  => $deadline ? $deadline : 'none',
		'summary'   => mb_substr( $content, 0, 900 ),
	);
}

/**
 * list_categories tool.
 */
function kajob_agent_tool_list_categories() {
	if ( ! taxonomy_exists( 'job_listing_category' ) ) {
		return array( 'categories' => array() );
	}

	$terms = get_terms( array(
		'taxonomy'   => 'job_listing_category',
		'hide_empty' => true,
		'number'     => 12,
	) );

	if ( is_wp_error( $terms ) || ! $terms ) {
		return array( 'categories' => array() );
	}

	$out = array();

	foreach ( $terms as $term ) {
		$out[] = array(
			'name'  => $term->name,
			'count' => (int) $term->count,
			'url'   => get_term_link( $term ),
		);
	}

	return array( 'categories' => $out );
}

/**
 * search_articles tool.
 */
function kajob_agent_tool_search_articles( $args ) {
	$query_text = isset( $args['query'] ) ? sanitize_text_field( (string) $args['query'] ) : '';

	if ( ! $query_text ) {
		return array( 'articles' => array() );
	}

	$posts = get_posts( array(
		'post_type'           => 'post',
		'post_status'         => 'publish',
		's'                   => $query_text,
		'posts_per_page'      => max( 1, min( 5, isset( $args['limit'] ) ? absint( $args['limit'] ) : 3 ) ),
		'no_found_rows'       => true,
		'ignore_sticky_posts' => true,
	) );

	$out = array();

	foreach ( $posts as $post ) {
		$out[] = array(
			'title'   => get_the_title( $post ),
			'url'     => get_permalink( $post ),
			'excerpt' => wp_strip_all_tags( get_the_excerpt( $post ) ),
		);
	}

	return array( 'articles' => $out );
}

/* -------------------------------------------------------------------------
 * System prompt
 * ---------------------------------------------------------------------- */

/**
 * Ground the model in this site's live context and set hard rules.
 */
function kajob_agent_system_prompt() {
	$stats = kajob_get_board_stats();
	$date  = gmdate( 'Y-m-d' );

	$categories = '';
	$cat_list   = kajob_agent_tool_list_categories();

	foreach ( $cat_list['categories'] as $category ) {
		$categories .= sprintf( "- %s (%d jobs)\n", $category['name'], $category['count'] );
	}

	$pages = array(
		'jobs'       => kajob_get_jobs_page_url(),
		'saved'      => kajob_get_saved_jobs_url(),
		'blog'       => kajob_get_blog_url(),
		'post a job' => kajob_get_jm_page_url( 'submit_job_form' ),
	);

	$page_lines = '';
	foreach ( $pages as $label => $url ) {
		if ( $url ) {
			$page_lines .= sprintf( "- %s: %s\n", $label, $url );
		}
	}

	$salary = kajob_get_salary_ceiling();

	return trim( sprintf(
		"You are the Kajob Assistant, the friendly public career helper for the job board \"%s\" (today: %s).

Site facts (verify anything else with tools):
- Open jobs right now: %d. Hiring companies: %d. Remote jobs: %d.
- Salary ceiling on board: %s. Use the currency exactly as reported by tools.
- Job categories:\n%s
- Site pages:\n%s

Guidance:
- First identify whether the visitor needs help as a job seeker or as a recruiter/employer. If it is unclear, ask one short question to find out.
- Give practical, respectful guidance for job seekers on CVs/resumes, applications, interviews, salary and benefits negotiation, career choices, and how to stand out.
- Give practical guidance for recruiters and employers on clear job descriptions, salary transparency, inclusive hiring, interviews, candidate communication, and hiring well.
- For general career or hiring questions, you may answer from established general knowledge. Be candid when context is missing, offer useful next steps, and do not present advice as a guaranteed outcome. Search the site's articles when they could add relevant site-specific advice.

Hard rules:
1. NEVER invent, estimate, or remember jobs. Every job you mention MUST come from a tool result in this conversation. If no tool result matches, say there are no current matches.
2. For anything about specific openings, salaries, or companies, call search_jobs or get_job first.
3. Share job links as plain URLs from tool results. Do not fabricate URLs.
4. Structure answers so they are easy to scan. Start with a direct answer, then use a short descriptive heading and bullets for advice with several points, or numbered steps when order matters. Keep simple answers to a few sentences, avoid unnecessary sections, and bold key terms with **text**.
5. Reply in the visitor's language.
6. Never request personal data (ID numbers, passwords, payment details) and never promise visas, guarantees, or outcomes.
7. If nothing matched, suggest broadening the search, browsing categories above, or the site pages listed.
8. Only discuss jobs, applications, hiring, salaries, careers, and this site. Politely decline anything else.",
		get_bloginfo( 'name' ),
		$date,
		(int) $stats['jobs'],
		(int) $stats['companies'],
		(int) $stats['remote'],
		$salary ? kajob_compact_money( $salary ) . ' ' . kajob_currency_symbol() . '/month (max on board)' : 'not set',
		$categories ? $categories : "- (none yet)\n",
		$page_lines
	) );
}
