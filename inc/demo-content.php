<?php
/**
 * One-click demo content.
 *
 * Seeds job categories, listings, companies and career-advice posts so a fresh
 * install presents a working board. Idempotent: re-running will not duplicate.
 *
 * @package Kajob
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Admin screen under Appearance.
 */
add_action( 'admin_menu', function () {
	add_theme_page(
		__( 'Kajob Demo Content', 'kajob' ),
		__( 'Demo Content', 'kajob' ),
		'manage_options',
		'kajob-demo',
		'kajob_render_demo_page'
	);
} );

/**
 * Handle the import / reset actions.
 */
add_action( 'admin_init', function () {
	if ( ! isset( $_POST['kajob_demo_action'] ) ) {
		return;
	}

	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}

	check_admin_referer( 'kajob_demo_content' );

	$action = sanitize_key( wp_unslash( $_POST['kajob_demo_action'] ) );

	if ( 'import' === $action ) {
		$result = kajob_import_demo_content();
		set_transient( 'kajob_demo_result', $result, 60 );
	}

	if ( 'reset' === $action ) {
		kajob_reset_demo_content();
		set_transient( 'kajob_demo_result', array( 'reset' => true ), 60 );
	}

	wp_safe_redirect( admin_url( 'themes.php?page=kajob-demo' ) );
	exit;
} );

/**
 * Render the admin screen.
 */
function kajob_render_demo_page() {
	$result   = get_transient( 'kajob_demo_result' );
	$imported = get_option( 'kajob_demo_imported' );
	delete_transient( 'kajob_demo_result' );
	?>
	<div class="wrap">
		<h1><?php esc_html_e( 'Kajob Demo Content', 'kajob' ); ?></h1>

		<?php if ( is_array( $result ) ) : ?>
			<div class="notice notice-success is-dismissible">
				<p>
					<?php
					if ( ! empty( $result['reset'] ) ) {
						esc_html_e( 'Demo content removed.', 'kajob' );
					} else {
						printf(
							/* translators: 1: jobs created, 2: categories created, 3: posts created. */
							esc_html__( 'Imported %1$d jobs, %2$d categories and %3$d articles.', 'kajob' ),
							(int) ( $result['jobs'] ?? 0 ),
							(int) ( $result['categories'] ?? 0 ),
							(int) ( $result['posts'] ?? 0 )
						);
					}
					?>
				</p>
			</div>
		<?php endif; ?>

		<p><?php esc_html_e( 'Populate the board with realistic sample listings, companies, categories and career-advice articles. Safe to run more than once â€” existing demo items are skipped.', 'kajob' ); ?></p>

		<?php if ( ! kajob_is_job_manager_active() ) : ?>
			<div class="notice notice-warning"><p><?php esc_html_e( 'Activate the WP Job Manager plugin first â€” it provides the job listing post type.', 'kajob' ); ?></p></div>
		<?php endif; ?>

		<form method="post" style="margin-top:18px">
			<?php wp_nonce_field( 'kajob_demo_content' ); ?>
			<input type="hidden" name="kajob_demo_action" value="import" />
			<?php submit_button( $imported ? __( 'Re-run Import', 'kajob' ) : __( 'Import Demo Content', 'kajob' ), 'primary', 'submit', false ); ?>
		</form>

		<?php if ( $imported ) : ?>
			<form method="post" style="margin-top:12px" onsubmit="return confirm('<?php echo esc_js( __( 'Remove all demo jobs, categories and articles?', 'kajob' ) ); ?>');">
				<?php wp_nonce_field( 'kajob_demo_content' ); ?>
				<input type="hidden" name="kajob_demo_action" value="reset" />
				<?php submit_button( __( 'Remove Demo Content', 'kajob' ), 'delete', 'submit', false ); ?>
			</form>
		<?php endif; ?>
	</div>
	<?php
}

/**
 * Companies used by the sample listings.
 */
function kajob_demo_companies() {
	return array(
		'northwind' => array(
			'name'     => 'Northwind Labs',
			'tagline'  => 'Developer infrastructure for teams that ship daily',
			'website'  => 'https://example.com/northwind',
			'twitter'  => 'northwindlabs',
			'benefits' => array( 'Four-day work week in July and August', 'â‚¬2,000 annual learning budget', 'Home office stipend and co-working allowance', '30 days paid holiday plus local public holidays' ),
		),
		'lumen' => array(
			'name'     => 'Lumen Studio',
			'tagline'  => 'A design practice for climate and health products',
			'website'  => 'https://example.com/lumen',
			'twitter'  => 'lumenstudio',
			'benefits' => array( 'Fully remote within EU time zones', 'Quarterly in-person studio week', 'Conference and course budget', 'Flexible hours with a 4-hour overlap window' ),
		),
		'meridian' => array(
			'name'     => 'Meridian Health',
			'tagline'  => 'Clinical software used by 400+ care networks',
			'website'  => 'https://example.com/meridian',
			'twitter'  => 'meridianhealth',
			'benefits' => array( 'Full medical, dental and vision cover', '401(k) with 5% match', 'Paid clinical volunteering days', 'On-site gym and commuter benefit' ),
		),
		'brightpath' => array(
			'name'     => 'Brightpath',
			'tagline'  => 'Learning tools for working adults',
			'website'  => 'https://example.com/brightpath',
			'twitter'  => 'brightpath',
			'benefits' => array( 'Unlimited PTO with a 15-day minimum', 'Free access to every Brightpath course', 'Equity for all full-time roles', 'Annual team retreat' ),
		),
		'cadence' => array(
			'name'     => 'Cadence CRM',
			'tagline'  => 'Relationship software for small revenue teams',
			'website'  => 'https://example.com/cadence',
			'twitter'  => 'cadencecrm',
			'benefits' => array( 'Remote-first across US time zones', 'Transparent salary bands published internally', 'Monthly wellness stipend', 'Sabbatical after four years' ),
		),
		'fathom' => array(
			'name'     => 'Fathom Analytics',
			'tagline'  => 'Privacy-first product analytics',
			'website'  => 'https://example.com/fathom',
			'twitter'  => 'fathomanalytics',
			'benefits' => array( 'Async-first, two meetings a week', 'Top-of-band compensation', 'Hardware refresh every two years', 'Four weeks minimum vacation' ),
		),
		'hopscotch' => array(
			'name'     => 'Hopscotch',
			'tagline'  => 'Consumer travel app, 3M monthly travellers',
			'website'  => 'https://example.com/hopscotch',
			'twitter'  => 'hopscotchapp',
			'benefits' => array( 'Annual travel credit', 'Work from anywhere for 8 weeks a year', 'Private health insurance', 'Stock options' ),
		),
		'vantage' => array(
			'name'     => 'Vantage Capital',
			'tagline'  => 'Technology-driven investment research',
			'website'  => 'https://example.com/vantage',
			'twitter'  => 'vantagecapital',
			'benefits' => array( 'Performance bonus', 'CFA and certification sponsorship', 'Hybrid schedule, 2 days in office', 'Enhanced parental leave' ),
		),
	);
}

/**
 * Sample listings. Days-ago values are staggered so the recency filter has
 * something meaningful to narrow.
 */
function kajob_demo_jobs() {
	return array(
		array(
			'title' => 'Senior Frontend Engineer', 'company' => 'northwind', 'location' => 'Berlin, Germany',
			'remote' => true, 'type' => 'full-time', 'category' => 'engineering', 'min' => 95000, 'max' => 130000,
			'days' => 1, 'featured' => true,
			'summary' => 'Own the interface layer of our build dashboard, used by 40,000 engineers every working day. You will work alongside two designers and a platform team, with real authority over frontend architecture.',
			'doing' => array( 'Lead the migration of our dashboard to a typed component library', 'Cut interaction latency on the build log view, currently our slowest surface', 'Pair with design on accessibility to WCAG 2.2 AA', 'Mentor two mid-level engineers through code review and pairing' ),
			'looking' => array( 'Five or more years building production React or similar', 'Fluency with TypeScript and modern bundling', 'A track record of shipping accessible interfaces', 'Comfort reading backend code and profiling network waterfalls' ),
		),
		array(
			'title' => 'Product Designer', 'company' => 'lumen', 'location' => 'Remote (EU)',
			'remote' => true, 'type' => 'full-time', 'category' => 'design', 'min' => 80000, 'max' => 105000,
			'days' => 2, 'featured' => true,
			'summary' => 'Design end-to-end flows for a carbon accounting product used by sustainability teams at mid-size manufacturers. Small studio, direct client contact, no handoff wall.',
			'doing' => array( 'Run discovery interviews with sustainability leads', 'Take features from problem framing through shipped UI', 'Maintain and extend our Figma design system', 'Present work directly to client stakeholders' ),
			'looking' => array( 'A portfolio showing shipped product work, not only concepts', 'Strong interaction and information design judgement', 'Experience designing data-dense interfaces', 'Written communication good enough for an async studio' ),
		),
		array(
			'title' => 'Data Scientist, Clinical Outcomes', 'company' => 'meridian', 'location' => 'Boston, MA',
			'remote' => false, 'type' => 'full-time', 'category' => 'data', 'min' => 120000, 'max' => 155000,
			'days' => 3, 'featured' => false,
			'summary' => 'Build the models that flag patient risk earlier in the care pathway. You will work with clinicians directly and see your analysis change treatment decisions.',
			'doing' => array( 'Develop and validate risk models on de-identified clinical data', 'Partner with clinicians to define meaningful outcome measures', 'Publish clear, reproducible analysis for regulatory review', 'Instrument model performance in production' ),
			'looking' => array( 'Strong Python and SQL, with pandas or polars in daily use', 'Applied statistics: causal inference, survival analysis or similar', 'Experience with regulated or sensitive data', 'Ability to explain a model to a non-technical clinician' ),
		),
		array(
			'title' => 'DevOps Engineer', 'company' => 'northwind', 'location' => 'Berlin, Germany',
			'remote' => true, 'type' => 'full-time', 'category' => 'engineering', 'min' => 100000, 'max' => 140000,
			'days' => 5, 'featured' => false,
			'summary' => 'Keep a multi-region Kubernetes platform boring. Our developers deploy 200 times a day and expect not to think about infrastructure.',
			'doing' => array( 'Own our Terraform modules and cluster lifecycle', 'Reduce p95 deploy time and eliminate flaky pipeline stages', 'Improve observability with traces that actually get read', 'Take part in a humane on-call rotation' ),
			'looking' => array( 'Production Kubernetes and Terraform experience', 'One general-purpose language, ideally Go or Python', 'A bias for deleting complexity rather than adding tooling', 'Clear incident communication' ),
		),
		array(
			'title' => 'Content Marketing Lead', 'company' => 'brightpath', 'location' => 'Austin, TX',
			'remote' => false, 'type' => 'full-time', 'category' => 'marketing', 'min' => 75000, 'max' => 95000,
			'days' => 4, 'featured' => false,
			'summary' => 'Build the editorial engine behind a learning brand. We have 90,000 newsletter readers and want the content to be genuinely useful rather than a funnel.',
			'doing' => array( 'Own the editorial calendar across newsletter, blog and social', 'Commission and edit freelance contributors', 'Grow organic search traffic without chasing keyword filler', 'Report on what content actually drives enrolment' ),
			'looking' => array( 'Four or more years in content or editorial roles', 'A portfolio of pieces you are proud of', 'Working knowledge of SEO and analytics', 'Sharp editing instincts' ),
		),
		array(
			'title' => 'Customer Success Manager', 'company' => 'cadence', 'location' => 'Remote (US)',
			'remote' => true, 'type' => 'full-time', 'category' => 'customer-support', 'min' => 65000, 'max' => 85000,
			'days' => 6, 'featured' => false,
			'summary' => 'Own a book of 60 mid-market accounts. You will be the person customers email when something matters, with the product influence to fix it.',
			'doing' => array( 'Run onboarding and quarterly reviews for your accounts', 'Spot churn risk early and act on it', 'Turn recurring complaints into product requests with evidence', 'Hit a renewal target you help define' ),
			'looking' => array( 'Three or more years in SaaS customer success or account management', 'Comfort with CRM data and basic reporting', 'Calm, specific written communication', 'Genuine curiosity about how small teams sell' ),
		),
		array(
			'title' => 'Backend Engineer (Go)', 'company' => 'fathom', 'location' => 'Toronto, ON',
			'remote' => true, 'type' => 'full-time', 'category' => 'engineering', 'min' => 110000, 'max' => 145000,
			'days' => 0, 'featured' => true,
			'summary' => 'Work on an ingestion pipeline handling 4 billion events a month with a strict privacy model. Async team, two meetings a week, deep work encouraged.',
			'doing' => array( 'Extend our Go ingestion services and query layer', 'Design storage that keeps event data anonymous by construction', 'Improve query performance on ClickHouse', 'Write the design docs before the code' ),
			'looking' => array( 'Strong Go, or a willingness to learn it from another systems language', 'Experience with high-throughput data pipelines', 'Understanding of columnar storage and query planning', 'Comfort working asynchronously in writing' ),
		),
		array(
			'title' => 'UX Researcher', 'company' => 'lumen', 'location' => 'Amsterdam, Netherlands',
			'remote' => false, 'type' => 'part-time', 'category' => 'design', 'min' => 45000, 'max' => 60000,
			'days' => 9, 'featured' => false,
			'summary' => 'Three days a week establishing a research practice from scratch. You will decide how this studio learns about its users.',
			'doing' => array( 'Plan and run generative and evaluative studies', 'Build a reusable participant panel and consent process', 'Turn findings into decisions designers actually use', 'Coach designers to run their own lightweight tests' ),
			'looking' => array( 'Mixed-methods research experience in product settings', 'Ability to recruit hard-to-reach professional participants', 'Clear, short research reports', 'Comfort being the first researcher on a team' ),
		),
		array(
			'title' => 'Sales Development Representative', 'company' => 'cadence', 'location' => 'Chicago, IL',
			'remote' => false, 'type' => 'full-time', 'category' => 'sales', 'min' => 55000, 'max' => 75000,
			'days' => 8, 'featured' => false,
			'summary' => 'First conversation with prospective customers. We sell a product we like, to teams we understand, without a boiler-room culture.',
			'doing' => array( 'Research and reach out to a focused account list', 'Qualify inbound demo requests', 'Keep clean notes in the CRM', 'Hit a monthly qualified-meeting target' ),
			'looking' => array( 'One or more years in outbound sales, or a strong adjacent background', 'Resilience and genuine curiosity on cold calls', 'Excellent written follow-up', 'Interest in a path toward account executive' ),
		),
		array(
			'title' => 'Mobile Engineer (React Native)', 'company' => 'hopscotch', 'location' => 'Remote (Worldwide)',
			'remote' => true, 'type' => 'freelance', 'category' => 'engineering', 'min' => 70000, 'max' => 95000,
			'days' => 7, 'featured' => false,
			'summary' => 'Six-month contract, likely to extend, rebuilding our offline trip planner. Three million travellers rely on this screen when they have no signal.',
			'doing' => array( 'Rebuild offline caching and conflict resolution', 'Improve cold start time on low-end Android devices', 'Ship to both stores on a fortnightly cadence', 'Add integration coverage for sync edge cases' ),
			'looking' => array( 'Shipped React Native apps in both stores', 'Real experience with offline-first data and sync', 'Native module debugging on iOS and Android', 'Availability across at least four hours of CET' ),
		),
		array(
			'title' => 'Financial Analyst', 'company' => 'vantage', 'location' => 'New York, NY',
			'remote' => false, 'type' => 'full-time', 'category' => 'finance', 'min' => 85000, 'max' => 110000,
			'days' => 12, 'featured' => false,
			'summary' => 'Support research on public software companies. You will build the models behind published notes, with your name on the work.',
			'doing' => array( 'Build and maintain valuation models', 'Digest filings, transcripts and channel checks', 'Draft sections of published research', 'Automate recurring data pulls in Python' ),
			'looking' => array( 'Two or more years in equity research, banking or corporate finance', 'Advanced Excel and solid accounting fundamentals', 'Python or SQL for data work', 'Progress toward CFA is welcome' ),
		),
		array(
			'title' => 'Registered Nurse, Care Coordination', 'company' => 'meridian', 'location' => 'Boston, MA',
			'remote' => false, 'type' => 'full-time', 'category' => 'healthcare', 'min' => 70000, 'max' => 95000,
			'days' => 10, 'featured' => false,
			'summary' => 'A clinical role with unusual influence over software. Coordinate patient pathways and tell our product team what the tools get wrong.',
			'doing' => array( 'Coordinate care plans across a patient caseload', 'Triage inbound clinical questions', 'Document outcomes in our own platform', 'Join weekly product reviews as the clinical voice' ),
			'looking' => array( 'Active RN licence in Massachusetts', 'Three or more years of clinical practice', 'Care coordination or case management experience', 'Patience for software that is still improving' ),
		),
		array(
			'title' => 'Machine Learning Intern', 'company' => 'fathom', 'location' => 'Remote (Worldwide)',
			'remote' => true, 'type' => 'internship', 'category' => 'data', 'min' => 30000, 'max' => 40000,
			'days' => 14, 'featured' => false,
			'summary' => 'A twelve-week paid internship with a scoped, real problem: detecting anomalous traffic patterns without storing identifiers.',
			'doing' => array( 'Prototype anomaly detection on aggregated event data', 'Evaluate approaches against a labelled internal set', 'Present findings to the engineering team', 'Ship one small production change' ),
			'looking' => array( 'Coursework or projects in machine learning', 'Python and a dataframe library', 'Curiosity about privacy-preserving measurement', 'Available for twelve consecutive weeks' ),
		),
		array(
			'title' => 'Technical Writer', 'company' => 'brightpath', 'location' => 'Remote (US)',
			'remote' => true, 'type' => 'part-time', 'category' => 'marketing', 'min' => 50000, 'max' => 68000,
			'days' => 16, 'featured' => false,
			'summary' => 'Twenty-five hours a week making our API documentation something developers recommend. Currently it is merely accurate.',
			'doing' => array( 'Rewrite API reference and guides for clarity', 'Build runnable examples that stay tested in CI', 'Own the docs information architecture', 'Work through support tickets to find gaps' ),
			'looking' => array( 'Published developer documentation you can point to', 'Ability to read code and call an API yourself', 'Familiarity with docs-as-code workflows', 'Ruthless editing of your own drafts' ),
		),
		array(
			'title' => 'Engineering Manager, Platform', 'company' => 'northwind', 'location' => 'Berlin, Germany',
			'remote' => false, 'type' => 'full-time', 'category' => 'engineering', 'min' => 140000, 'max' => 175000,
			'days' => 2, 'featured' => true,
			'summary' => 'Lead a team of seven platform engineers. Roughly half your week is people, half is technical direction â€” we do not ask managers to stop reading code.',
			'doing' => array( 'Run hiring, onboarding and growth for seven engineers', 'Set platform roadmap with product and infrastructure leads', 'Keep on-call sustainable and incident follow-through real', 'Stay close enough to the code to make good trade-offs' ),
			'looking' => array( 'Three or more years managing infrastructure or platform teams', 'A strong systems background before management', 'Evidence of growing engineers into senior roles', 'Comfort with distributed, partially remote teams' ),
		),
		array(
			'title' => 'Brand Designer', 'company' => 'hopscotch', 'location' => 'Lisbon, Portugal',
			'remote' => false, 'type' => 'freelance', 'category' => 'design', 'min' => 60000, 'max' => 85000,
			'days' => 18, 'featured' => false,
			'summary' => 'Four-month contract refreshing our visual identity ahead of a major release. Illustration skill matters as much as layout here.',
			'doing' => array( 'Develop a refreshed identity system and illustration language', 'Design campaign assets across app stores, web and out-of-home', 'Produce a practical brand guide the team will use', 'Pair with our in-house product designer' ),
			'looking' => array( 'A portfolio with at least one full identity system', 'Original illustration ability', 'Confidence presenting and defending creative direction', 'Availability to start within six weeks' ),
		),
		array(
			'title' => 'Security Engineer', 'company' => 'vantage', 'location' => 'Remote (US)',
			'remote' => true, 'type' => 'full-time', 'category' => 'security', 'min' => 125000, 'max' => 160000,
			'days' => 4, 'featured' => false,
			'summary' => 'First dedicated security hire. Build the programme rather than inherit one, with a mandate and budget already approved.',
			'doing' => array( 'Run threat modelling on new research tooling', 'Own vulnerability management and remediation tracking', 'Build detection coverage in our cloud environment', 'Prepare the firm for its annual client security review' ),
			'looking' => array( 'Five or more years in application or cloud security', 'Hands-on AWS or GCP security experience', 'Ability to write remediation guidance engineers accept', 'Clear written risk communication for non-technical partners' ),
		),
		array(
			'title' => 'Operations Coordinator', 'company' => 'cadence', 'location' => 'Denver, CO',
			'remote' => false, 'type' => 'temporary', 'category' => 'operations', 'min' => 45000, 'max' => 58000,
			'days' => 20, 'featured' => false,
			'summary' => 'Nine-month contract covering parental leave, with a strong track record of converting to permanent. Keep a 40-person company running smoothly.',
			'doing' => array( 'Own office, vendor and event logistics', 'Keep people-ops records accurate', 'Support quarterly planning cycles', 'Improve the processes you inherit' ),
			'looking' => array( 'Two or more years in operations or office management', 'Exceptional organisation and follow-through', 'Discretion with confidential information', 'Comfort with ambiguity in a small company' ),
		),
		array(
			'title' => 'Growth Product Manager', 'company' => 'brightpath', 'location' => 'Austin, TX',
			'remote' => false, 'type' => 'full-time', 'category' => 'product', 'min' => 115000, 'max' => 145000,
			'days' => 3, 'featured' => false,
			'summary' => 'Own activation and retention for a consumer learning product. You will run experiments weekly and be judged on learner outcomes, not vanity metrics.',
			'doing' => array( 'Define and run the activation experiment roadmap', 'Partner with data on measurement that survives scrutiny', 'Work with design and engineering in a tight weekly loop', 'Say no to growth tactics that hurt learners' ),
			'looking' => array( 'Four or more years of product management, some in growth', 'Fluency with SQL and experiment design', 'A record of shipped, measured improvements', 'Good judgement about ethical growth' ),
		),
		array(
			'title' => 'Support Engineer', 'company' => 'fathom', 'location' => 'Remote (EU)',
			'remote' => true, 'type' => 'full-time', 'category' => 'customer-support', 'min' => 60000, 'max' => 80000,
			'days' => 11, 'featured' => false,
			'summary' => 'Technical support with real debugging. You will read customer code, reproduce bugs, and often ship the fix yourself.',
			'doing' => array( 'Diagnose integration issues across web and server SDKs', 'Write reproductions and file precise bug reports', 'Ship small fixes directly', 'Turn recurring tickets into documentation' ),
			'looking' => array( 'Comfort reading JavaScript and at least one backend language', 'Experience in technical support or a junior engineering role', 'Patient, precise written communication', 'Availability during EU business hours' ),
		),
		array(
			'title' => 'Curriculum Designer', 'company' => 'brightpath', 'location' => 'Remote (Worldwide)',
			'remote' => true, 'type' => 'part-time', 'category' => 'education', 'min' => 48000, 'max' => 64000,
			'days' => 22, 'featured' => false,
			'summary' => 'Design short technical courses for adults studying alongside full-time jobs. Twenty-five hours a week, genuinely flexible.',
			'doing' => array( 'Design course outlines with measurable learning outcomes', 'Write lessons, exercises and assessments', 'Review learner feedback and revise', 'Work with subject-matter experts as an editor' ),
			'looking' => array( 'Instructional design or teaching experience', 'Ability to explain technical topics plainly', 'Familiarity with adult learning principles', 'Strong self-management in a remote role' ),
		),
		array(
			'title' => 'Platform Engineer', 'company' => 'hopscotch', 'location' => 'London, United Kingdom',
			'remote' => false, 'type' => 'full-time', 'category' => 'engineering', 'min' => 105000, 'max' => 135000,
			'days' => 6, 'featured' => false,
			'summary' => 'Scale the services behind a travel app with sharp seasonal peaks. Black Friday for travel is January, and we intend to be ready.',
			'doing' => array( 'Improve autoscaling and load shedding ahead of peak season', 'Own service-level objectives with product teams', 'Reduce cloud spend without reducing reliability', 'Build tooling that makes deploys uneventful' ),
			'looking' => array( 'Strong distributed systems fundamentals', 'Production experience with AWS or GCP', 'Load testing and capacity planning experience', 'Pragmatism about reliability trade-offs' ),
		),
	);
}

/**
 * Career-advice articles.
 */
function kajob_demo_posts() {
	return array(
		array(
			'title' => 'How to read a salary band before you apply',
			'category' => 'Salary',
			'days' => 3,
			'hue' => array( '#6d4aff', '#d629ac' ),
			'image' => 'https://images.unsplash.com/photo-1454165804606-c3d57bc86b40?w=1200&q=80&fm=jpg&fit=crop',
			'content' => "<p>A published salary band tells you more than the midpoint. It tells you how a company thinks about levelling, how much room there is to negotiate, and whether the role has been scoped honestly.</p><h2>Start with the width</h2><p>A band of <strong>95,000 to 130,000</strong> is roughly 37% wide. That is normal for a senior role spanning two levels. A band 60% wide usually means the company has not decided what seniority it wants, which is worth asking about in the first call.</p><h2>Ask where the midpoint sits</h2><p>Most companies intend to hire near the middle of a band. If a recruiter opens at the bottom, ask directly what would need to be true for an offer at the top. The answer is either a concrete list of skills or a vague deflection, and both are useful information.</p><ul><li>Ask whether the band includes bonus or equity, or is base only.</li><li>Ask how the band was set â€” market data, internal equity, or last year's budget.</li><li>Ask when it was last reviewed. A stale band in a fast-moving market is a negotiation opportunity.</li></ul><h2>Watch for the missing band</h2><p>Where a range is absent, you are being asked to name a number first. Politely decline and ask for their range. If they will not give one, assume the top of your expectation is above their budget and price your time accordingly.</p><blockquote>The band is the company's first honest statement about how it values the work. Read it before you write a cover letter.</blockquote><p>Once you have the band, decide your own walk-away number before the first interview. Negotiating against a figure you chose in a calm moment is far easier than inventing one on a call.</p>",
		),
		array(
			'title' => 'The remote interview questions that actually predict fit',
			'category' => 'Interviews',
			'days' => 8,
			'hue' => array( '#0284c7', '#00b483' ),
			'image' => 'https://images.unsplash.com/photo-1568992687947-868a62a9f521?w=1200&q=80&fm=jpg&fit=crop',
			'content' => "<p>Most remote interview advice focuses on lighting and background noise. Those matter less than whether the company has thought about how asynchronous work actually functions.</p><h2>Ask about the writing</h2><p>Request an example of a recent decision that was made in writing â€” a design doc, a proposal, a thread. Companies that work well remotely produce these constantly. Companies that struggle will not be able to find one.</p><h2>Ask about the meeting load</h2><p>\"How many recurring meetings would I have in a normal week?\" is a precise question with a precise answer. Two or three suggests genuine async practice. Twelve suggests a co-located team that went remote without changing anything.</p><ul><li>What hours are you expected to overlap with the team?</li><li>How are decisions recorded so someone in another time zone can catch up?</li><li>What happened the last time someone disagreed with a decision asynchronously?</li></ul><h2>Ask about the bad week</h2><p>Every remote team has a week where something breaks across time zones. Ask what happened last time. You are listening for whether they blame the individual or fix the process.</p><p>Finally, ask what the team does in person and how often. A quarterly on-site is a real investment. \"We would love to at some point\" is not a plan.</p>",
		),
		array(
			'title' => 'Writing a portfolio case study that survives a six-second scan',
			'category' => 'Portfolio',
			'days' => 15,
			'hue' => array( '#d98800', '#e63757' ),
			'image' => 'https://images.unsplash.com/photo-1486312338219-ce68d2c6f44d?w=1200&q=80&fm=jpg&fit=crop',
			'content' => "<p>Reviewers spend seconds on a first pass. A case study that opens with three paragraphs of company history will not be read at all. Lead with the outcome, then earn the detail.</p><h2>Open with the result</h2><p>One sentence: what changed, and by how much. \"Cut onboarding drop-off from 41% to 22% over two releases.\" Now the reviewer has a reason to keep reading.</p><h2>Show the constraint, not just the solution</h2><p>Anyone can show a polished screen. What distinguishes a senior portfolio is the constraint you designed within: a legacy API, a legal requirement, a two-week deadline, an accessibility target. State it early.</p><ul><li>Include one artefact that is deliberately ugly â€” a sketch, a spreadsheet, a rejected direction.</li><li>Name what you did versus what the team did.</li><li>Include something you got wrong and what you changed.</li></ul><h2>Cut the process theatre</h2><p>Double-diamond diagrams and stock persona cards signal a bootcamp template. Replace them with the specific research you ran: how many participants, recruited how, and what surprised you.</p><blockquote>A portfolio is an argument about your judgement, not a gallery of your output.</blockquote><p>End with what you would do next. It shows you still think about the work after it shipped, which is exactly the trait hiring managers are trying to detect.</p>",
		),
		array(
			'title' => 'The two-column CV that gets past a human, not just a parser',
			'category' => 'Applications',
			'days' => 1,
			'hue' => array( '#00b483', '#38bdf8' ),
			'image' => 'https://images.unsplash.com/photo-1542744173-8e7e53415bb0?w=1200&q=80&fm=jpg&fit=crop',
			'content' => "<p>Applicant tracking systems are less of a barrier than the advice industry suggests. Most rejections happen in the six seconds a recruiter spends deciding whether to keep reading. Design for that reader first.</p><h2>Put the evidence above the fold</h2><p>The top third of page one should answer three questions: what you do, at what level, and where you have done it. A summary paragraph that says \"passionate professional seeking a challenging role\" answers none of them.</p><h2>Write achievements as cause and effect</h2><p>\"Responsible for the checkout flow\" describes a job. \"Rebuilt checkout and recovered 8% of abandoned carts\" describes an outcome. The second is shorter and does more work.</p><ul><li>One page per decade of experience, two at most.</li><li>Dates in a single consistent format, right-aligned so gaps are easy to verify.</li><li>Skills listed as tools you have shipped with, not tools you have heard of.</li></ul><h2>Keep the layout boring on purpose</h2><p>Single column, one accent colour, real text rather than images of text. A two-column layout with a sidebar of skill bars fails every automated parse and impresses no human.</p><blockquote>If your CV needs a legend, it is a design problem, not a candidate problem.</blockquote><p>Finally, name the file <code>firstname-lastname-role.pdf</code>. Recruiters download dozens at a time and \"CV_final_v3.pdf\" is instantly lost.</p>",
		),
		array(
			'title' => 'Negotiating an offer without burning the goodwill',
			'category' => 'Negotiation',
			'days' => 5,
			'hue' => array( '#6d4aff', '#0284c7' ),
			'image' => 'https://images.unsplash.com/photo-1556761175-b413da4baf72?w=1200&q=80&fm=jpg&fit=crop',
			'content' => "<p>An offer is the start of a conversation, not a verdict. The companies worth joining expect you to negotiate, and the ones that punish you for it have told you something valuable.</p><h2>Never negotiate on the call</h2><p>Thank them, ask for the details in writing, and ask when they need an answer. Then negotiate by email where you can choose your words and they can consult a budget holder without losing face.</p><h2>Anchor on the band, not on your history</h2><p>Your previous salary is not a market rate. If they published a range, reference it: \"Given the scope we discussed, I was expecting something in the upper half of the published band.\" That is a claim about the role, not about you.</p><ul><li>Name one number, not a range â€” a range is read as its bottom.</li><li>Give a reason tied to the work: scope, on-call, a competing process, a specific skill.</li><li>Ask once, clearly. Repeated small asks read as indecision.</li></ul><h2>Trade across the whole package</h2><p>If base is fixed, there is usually room in signing bonus, equity, review timing, title, or a defined remote arrangement. A guaranteed six-month review with written criteria is often worth more than a small bump now.</p><blockquote>The goal is an offer you can accept without resentment, not the largest number you can extract.</blockquote><p>Once agreed, ask for the revised offer in writing before you resign anything. Verbal commitments survive exactly as long as the person who made them stays in the role.</p>",
		),
		array(
			'title' => 'What a good remote job description quietly tells you',
			'category' => 'Remote work',
			'days' => 11,
			'hue' => array( '#0284c7', '#8265ff' ),
			'image' => 'https://images.unsplash.com/photo-1522202176988-66273c2fd55f?w=1200&q=80&fm=jpg&fit=crop',
			'content' => "<p>\"Remote-friendly\" and \"remote-first\" describe two different companies. The job description usually reveals which one you are dealing with, if you read the operational details rather than the perks.</p><h2>Look for the time zone sentence</h2><p>A listing that says \"overlap 13:00â€“17:00 CET\" has thought about scheduling. One that says \"flexible hours\" without qualification often means the hours are flexible until someone in headquarters needs you.</p><h2>Count the location mentions</h2><p>If a remote role mentions a specific office three times, expect to be in it. \"Occasional travel\" should come with a number: twice a year is a plan, \"as needed\" is a blank cheque.</p><ul><li>Is equipment provided, or is there a stipend with an amount?</li><li>Is there a written handbook you can read before joining?</li><li>Are salaries location-adjusted, and is that stated up front?</li></ul><h2>Read the interview process</h2><p>A fully remote company runs a fully remote process. If the final stage is an on-site day, that is the culture you are joining regardless of what the header says.</p><blockquote>The perks list describes what the company buys. The process description describes how it works.</blockquote><p>Ask for one async artefact in the first call â€” a decision doc, a recorded demo. Teams that genuinely work this way have one ready within a day.</p>",
		),
		array(
			'title' => 'Reading a career ladder before you accept the level',
			'category' => 'Career growth',
			'days' => 19,
			'hue' => array( '#d629ac', '#ffb020' ),
			'image' => 'https://images.unsplash.com/photo-1573496359142-b8d87734a5a2?w=1200&q=80&fm=jpg&fit=crop',
			'content' => "<p>The title on the offer letter matters less than the level it maps to internally. Two companies can both call you \"Senior\" while placing you three years apart on their own ladders.</p><h2>Ask for the written criteria</h2><p>Any company with a real ladder has a document describing scope, autonomy and influence at each level. Ask to see the one you are being hired into and the one above it. If neither exists, promotion will be a negotiation rather than a process.</p><h2>Find out how calibration works</h2><p>Ask who decides promotions and how often. A manager who can promote alone is a single point of failure. A committee that meets twice a year with written packets is slower but far more predictable.</p><ul><li>What percentage of the team was promoted in the last cycle?</li><li>What is the typical time in level before a promotion is considered?</li><li>Is there an individual-contributor track above this level, or does progress require managing people?</li></ul><h2>Check the level below and above</h2><p>If everyone on the team is at your level, there is nobody to learn from and no obvious next step. If you are the most junior by two levels, expect to be stretched â€” which may be exactly what you want, but should be a choice.</p><blockquote>Accept a level, not a title. The title is what you are called; the level is what you are paid and promoted against.</blockquote><p>Get the level confirmed in the offer. It is the single detail most often left verbal and most expensive to discover later.</p>",
		),
		array(
			'title' => 'A week-by-week plan for the first ninety days',
			'category' => 'Career growth',
			'days' => 26,
			'hue' => array( '#00d69a', '#6d4aff' ),
			'image' => 'https://images.unsplash.com/photo-1521737604893-d14cc237f11d?w=1200&q=80&fm=jpg&fit=crop',
			'content' => "<p>The first ninety days set the assumptions people will hold about you for years. Spend them deliberately rather than waiting to be assigned work.</p><h2>Weeks one and two: map the system</h2><p>Ship something trivial end to end â€” a copy change, a small fix â€” purely to learn how work reaches production. Then write down every step that surprised you. That document is useful to you now and to the next hire later.</p><h2>Weeks three to six: find the recurring pain</h2><p>Ask five people the same question: \"What do you do every week that you wish you did not?\" The overlap in their answers is your first high-leverage project, and it comes pre-validated.</p><ul><li>Book thirty minutes with every adjacent team, not just your own.</li><li>Read the last two postmortems before you propose any change.</li><li>Keep a private log of decisions and who made them.</li></ul><h2>Weeks seven to twelve: deliver one visible thing</h2><p>Choose something small enough to finish and visible enough to be noticed. A finished improvement to a shared tool beats an unfinished redesign of a core system.</p><blockquote>Credibility is built from completed, visible work â€” not from the ambition of what you started.</blockquote><p>At day ninety, ask your manager directly: \"What would make the next ninety days a success?\" Write the answer down and check against it at day one hundred and eighty.</p>",
		),
	);
}

/**
 * Download a demo image from the web into the media library.
 * Idempotent: re-runs reuse the previously attached file.
 *
 * @param string $url  Direct image URL.
 * @param string $slug Unique slug for the file name.
 * @return int Attachment ID, or 0 on failure.
 */
function kajob_fetch_demo_image( $url, $slug ) {
	$upload = wp_upload_dir();

	if ( ! empty( $upload['error'] ) || ! $url ) {
		return 0;
	}

	$dir      = trailingslashit( $upload['basedir'] ) . 'kajob-demo';
	$filename = 'photo-' . sanitize_title( $slug ) . '.jpg';

	if ( ! wp_mkdir_p( $dir ) ) {
		return 0;
	}

	$existing = get_posts( array(
		'post_type'      => 'attachment',
		'post_status'    => 'any',
		'meta_key'       => '_wp_attached_file',
		'meta_value'     => 'kajob-demo/' . $filename,
		'posts_per_page' => 1,
		'fields'         => 'ids',
	) );

	if ( ! empty( $existing ) ) {
		return (int) $existing[0];
	}

	$response = wp_remote_get( $url, array(
		'timeout'    => 30,
		'user-agent' => 'Kajob-Demo-Importer',
	) );

	if ( is_wp_error( $response ) || 200 !== (int) wp_remote_retrieve_response_code( $response ) ) {
		return 0;
	}

	$content_type = '';

	if ( isset( $response['headers']['content-type'] ) ) {
		$content_type = (string) $response['headers']['content-type'];
	}

	if ( ! preg_match( '#^image/#', $content_type ) ) {
		return 0;
	}

	$bytes = wp_remote_retrieve_body( $response );

	if ( ! $bytes || strlen( $bytes ) < 5000 ) {
		return 0;
	}

	$path = trailingslashit( $dir ) . $filename;

	if ( false === file_put_contents( $path, $bytes ) ) {
		return 0;
	}

	$attachment_id = wp_insert_attachment( array(
		'post_mime_type' => 'image/jpeg',
		'post_title'     => sanitize_title( $slug ),
		'post_content'   => '',
		'post_status'    => 'inherit',
	), $path );

	if ( is_wp_error( $attachment_id ) || ! $attachment_id ) {
		return 0;
	}

	require_once ABSPATH . 'wp-admin/includes/image.php';
	wp_update_attachment_metadata( $attachment_id, wp_generate_attachment_metadata( $attachment_id, $path ) );

	return (int) $attachment_id;
}

/**
 * Generate a solid-color abstract cover so the blog grid is never image-less.
 * Used as the fallback when a remote photo cannot be downloaded.
 * Uses GD, which WordPress already requires for image handling.
 */
function kajob_generate_cover( $slug, $hues ) {
	if ( ! function_exists( 'imagecreatetruecolor' ) ) {
		return 0;
	}

	$upload = wp_upload_dir();

	if ( ! empty( $upload['error'] ) ) {
		return 0;
	}

	$dir = trailingslashit( $upload['basedir'] ) . 'kajob-demo';

	if ( ! wp_mkdir_p( $dir ) ) {
		return 0;
	}

	$filename = 'cover-' . sanitize_title( $slug ) . '.png';
	$path     = trailingslashit( $dir ) . $filename;

	$w   = 1200;
	$h   = 800;
	$img = imagecreatetruecolor( $w, $h );

	$from = sscanf( $hues[0], '#%02x%02x%02x' );
	$bg   = imagecolorallocate( $img, $from[0], $from[1], $from[2] );
	imagefilledrectangle( $img, 0, 0, $w, $h, $bg );

	// Solid color plus translucent geometry — no gradient.
	$soft  = imagecolorallocatealpha( $img, 255, 255, 255, 104 );
	$softer = imagecolorallocatealpha( $img, 255, 255, 255, 116 );

	for ( $i = 0; $i < 5; $i++ ) {
		$d = 300 + $i * 190;
		imagearc( $img, (int) ( $w * 0.78 ), (int) ( $h * 0.24 ), $d, $d, 0, 360, $soft );
		imagearc( $img, (int) ( $w * 0.78 ), (int) ( $h * 0.24 ), $d + 2, $d + 2, 0, 360, $softer );
	}

	for ( $x = 0; $x < $w; $x += 60 ) {
		imageline( $img, $x, 0, $x, $h, imagecolorallocatealpha( $img, 255, 255, 255, 118 ) );
	}

	for ( $y = 0; $y < $h; $y += 60 ) {
		imageline( $img, 0, $y, $w, $y, imagecolorallocatealpha( $img, 255, 255, 255, 118 ) );
	}

	imagepng( $img, $path, 6 );
	imagedestroy( $img );

	$existing = get_posts( array(
		'post_type'      => 'attachment',
		'post_status'    => 'any',
		'meta_key'       => '_wp_attached_file',
		'meta_value'     => 'kajob-demo/' . $filename,
		'posts_per_page' => 1,
		'fields'         => 'ids',
	) );

	if ( ! empty( $existing ) ) {
		return (int) $existing[0];
	}

	$attachment_id = wp_insert_attachment( array(
		'post_mime_type' => 'image/png',
		'post_title'     => sanitize_title( $slug ),
		'post_content'   => '',
		'post_status'    => 'inherit',
	), $path );

	if ( is_wp_error( $attachment_id ) || ! $attachment_id ) {
		return 0;
	}

	require_once ABSPATH . 'wp-admin/includes/image.php';
	wp_update_attachment_metadata( $attachment_id, wp_generate_attachment_metadata( $attachment_id, $path ) );

	return (int) $attachment_id;
}

/**
 * Replace a generated demo cover with a real photo on re-imports.
 * Only touches thumbnails that are missing or were generated by this importer.
 *
 * @param int   $post_id Post ID.
 * @param array $article Article data array.
 */
function kajob_maybe_upgrade_cover( $post_id, $article ) {
	$thumb_id = (int) get_post_thumbnail_id( $post_id );

	if ( $thumb_id ) {
		$file = (string) get_post_meta( $thumb_id, '_wp_attached_file', true );

		// Keep real photos; only replace generated covers.
		if ( $file && false === strpos( $file, 'kajob-demo/cover-' ) ) {
			return;
		}
	}

	$attachment_id = 0;

	if ( ! empty( $article['image'] ) ) {
		$attachment_id = kajob_fetch_demo_image( $article['image'], $article['title'] );
	}

	if ( ! $attachment_id ) {
		$attachment_id = kajob_generate_cover( $article['title'], $article['hue'] );
	}

	if ( $attachment_id && $attachment_id !== $thumb_id ) {
		set_post_thumbnail( $post_id, $attachment_id );
	}
}

/**
 * Build the listing body from role-specific content.
 */
function kajob_demo_job_content( $job, $company ) {
	$html  = '<p>' . esc_html( $job['summary'] ) . '</p>';
	$html .= '<h2>' . esc_html__( 'What you will be doing', 'kajob' ) . '</h2><ul>';

	foreach ( $job['doing'] as $item ) {
		$html .= '<li>' . esc_html( $item ) . '</li>';
	}

	$html .= '</ul><h2>' . esc_html__( 'What we are looking for', 'kajob' ) . '</h2><ul>';

	foreach ( $job['looking'] as $item ) {
		$html .= '<li>' . esc_html( $item ) . '</li>';
	}

	$html .= '</ul><h2>' . esc_html__( 'Benefits', 'kajob' ) . '</h2><ul>';

	foreach ( $company['benefits'] as $item ) {
		$html .= '<li>' . esc_html( $item ) . '</li>';
	}

	$html .= '</ul>';
	$html .= '<h2>' . esc_html__( 'How to apply', 'kajob' ) . '</h2>';
	$html .= '<p>' . esc_html__( 'Apply with a CV or a link to your work. We reply to every application within five working days, and the process is three conversations: a 30-minute introduction, a practical discussion with the team, and a final chat about scope and compensation. No unpaid take-home projects.', 'kajob' ) . '</p>';

	return $html;
}

/**
 * Run the import.
 *
 * @return array Counts of created items.
 */
function kajob_import_demo_content() {
	$result = array( 'jobs' => 0, 'categories' => 0, 'posts' => 0 );

	if ( ! kajob_is_job_manager_active() ) {
		return $result;
	}

	$companies = kajob_demo_companies();

	// Categories.
	$category_names = array(
		'engineering'      => __( 'Engineering', 'kajob' ),
		'design'           => __( 'Design', 'kajob' ),
		'data'             => __( 'Data & Analytics', 'kajob' ),
		'marketing'        => __( 'Marketing', 'kajob' ),
		'sales'            => __( 'Sales', 'kajob' ),
		'customer-support' => __( 'Customer Support', 'kajob' ),
		'finance'          => __( 'Finance', 'kajob' ),
		'healthcare'       => __( 'Healthcare', 'kajob' ),
		'security'         => __( 'Security', 'kajob' ),
		'operations'       => __( 'Operations', 'kajob' ),
		'product'          => __( 'Product', 'kajob' ),
		'education'        => __( 'Education', 'kajob' ),
	);

	$category_ids = array();

	foreach ( $category_names as $slug => $name ) {
		$term = get_term_by( 'slug', $slug, 'job_listing_category' );

		if ( ! $term ) {
			$created = wp_insert_term( $name, 'job_listing_category', array( 'slug' => $slug ) );

			if ( ! is_wp_error( $created ) ) {
				$category_ids[ $slug ] = (int) $created['term_id'];
				$result['categories']++;
			}
		} else {
			$category_ids[ $slug ] = (int) $term->term_id;
		}
	}

	// Job types are seeded by the plugin; make sure the ones we use exist.
	foreach ( array( 'full-time' => 'Full Time', 'part-time' => 'Part Time', 'freelance' => 'Freelance', 'temporary' => 'Temporary', 'internship' => 'Internship' ) as $slug => $name ) {
		if ( ! get_term_by( 'slug', $slug, 'job_listing_type' ) ) {
			wp_insert_term( $name, 'job_listing_type', array( 'slug' => $slug ) );
		}
	}

	$admin = get_users( array( 'role' => 'administrator', 'number' => 1, 'fields' => 'ID' ) );
	$author = ! empty( $admin ) ? (int) $admin[0] : 1;

	// Listings.
	foreach ( kajob_demo_jobs() as $job ) {
		$existing = get_posts( array(
			'post_type'      => 'job_listing',
			'post_status'    => 'any',
			'title'          => $job['title'],
			'posts_per_page' => 1,
			'fields'         => 'ids',
		) );

		if ( ! empty( $existing ) ) {
			continue;
		}

		$company = $companies[ $job['company'] ];
		$posted  = gmdate( 'Y-m-d H:i:s', strtotime( '-' . (int) $job['days'] . ' days -' . wp_rand( 1, 9 ) . ' hours' ) );

		$job_id = wp_insert_post( array(
			'post_type'    => 'job_listing',
			'post_status'  => 'publish',
			'post_title'   => $job['title'],
			'post_content' => kajob_demo_job_content( $job, $company ),
			'post_author'  => $author,
			'post_date'    => $posted,
			'post_date_gmt' => $posted,
		), true );

		if ( is_wp_error( $job_id ) || ! $job_id ) {
			continue;
		}

		update_post_meta( $job_id, '_company_name', $company['name'] );
		update_post_meta( $job_id, '_company_tagline', $company['tagline'] );
		update_post_meta( $job_id, '_company_website', $company['website'] );
		update_post_meta( $job_id, '_company_twitter', $company['twitter'] );
		update_post_meta( $job_id, '_job_location', $job['location'] );
		update_post_meta( $job_id, '_remote_position', $job['remote'] ? '1' : '0' );
		update_post_meta( $job_id, '_application', 'careers@' . $job['company'] . '.example.com' );
		update_post_meta( $job_id, '_kajob_salary_min', (int) $job['min'] );
		update_post_meta( $job_id, '_kajob_salary_max', (int) $job['max'] );
		update_post_meta( $job_id, '_job_salary', kajob_format_salary_range( $job['min'], $job['max'], false ) );
		update_post_meta( $job_id, '_job_salary_currency', get_option( 'job_manager_default_salary_currency', 'USD' ) );
		update_post_meta( $job_id, '_job_salary_unit', 'YEAR' );
		update_post_meta( $job_id, '_filled', 0 );
		update_post_meta( $job_id, '_featured', $job['featured'] ? 1 : 0 );
		update_post_meta( $job_id, '_job_expires', gmdate( 'Y-m-d', strtotime( '+' . wp_rand( 18, 45 ) . ' days' ) ) );

		if ( isset( $category_ids[ $job['category'] ] ) ) {
			wp_set_object_terms( $job_id, array( (int) $category_ids[ $job['category'] ] ), 'job_listing_category' );
		}

		wp_set_object_terms( $job_id, array( $job['type'] ), 'job_listing_type' );

		$result['jobs']++;
	}

	// Blog categories and articles.
	foreach ( kajob_demo_posts() as $article ) {
		$existing = get_posts( array(
			'post_type'      => 'post',
			'post_status'    => 'any',
			'title'          => $article['title'],
			'posts_per_page' => 1,
			'fields'         => 'ids',
		) );

		if ( ! empty( $existing ) ) {
			// Upgrade demo posts that still carry an old generated cover.
			kajob_maybe_upgrade_cover( (int) $existing[0], $article );
			continue;
		}

		$cat = get_term_by( 'slug', sanitize_title( $article['category'] ), 'category' );

		if ( ! $cat ) {
			$created = wp_insert_term( $article['category'], 'category' );
			$cat_id  = is_wp_error( $created ) ? 0 : (int) $created['term_id'];
		} else {
			$cat_id = (int) $cat->term_id;
		}

		$posted = gmdate( 'Y-m-d H:i:s', strtotime( '-' . (int) $article['days'] . ' days' ) );

		$post_id = wp_insert_post( array(
			'post_type'     => 'post',
			'post_status'   => 'publish',
			'post_title'    => $article['title'],
			'post_content'  => $article['content'],
			'post_author'   => $author,
			'post_date'     => $posted,
			'post_date_gmt' => $posted,
			'post_category' => $cat_id ? array( $cat_id ) : array(),
		), true );

		if ( is_wp_error( $post_id ) || ! $post_id ) {
			continue;
		}

		$attachment_id = 0;

		if ( ! empty( $article['image'] ) ) {
			$attachment_id = kajob_fetch_demo_image( $article['image'], $article['title'] );
		}

		if ( ! $attachment_id ) {
			$attachment_id = kajob_generate_cover( $article['title'], $article['hue'] );
		}

		if ( $attachment_id ) {
			set_post_thumbnail( $post_id, $attachment_id );
		}

		$result['posts']++;
	}

	delete_transient( 'kajob_board_stats' );
	delete_transient( 'kajob_salary_ceiling' );
	update_option( 'kajob_demo_imported', time() );

	return $result;
}

/**
 * Remove demo content. Only touches items this importer created.
 */
function kajob_reset_demo_content() {
	$job_titles  = wp_list_pluck( kajob_demo_jobs(), 'title' );
	$post_titles = wp_list_pluck( kajob_demo_posts(), 'title' );

	foreach ( $job_titles as $title ) {
		$found = get_posts( array( 'post_type' => 'job_listing', 'post_status' => 'any', 'title' => $title, 'posts_per_page' => 1, 'fields' => 'ids' ) );

		foreach ( $found as $id ) {
			wp_delete_post( $id, true );
		}
	}

	foreach ( $post_titles as $title ) {
		$found = get_posts( array( 'post_type' => 'post', 'post_status' => 'any', 'title' => $title, 'posts_per_page' => 1, 'fields' => 'ids' ) );

		foreach ( $found as $id ) {
			wp_delete_post( $id, true );
		}
	}

	delete_option( 'kajob_demo_imported' );
	delete_transient( 'kajob_board_stats' );
	delete_transient( 'kajob_salary_ceiling' );
}