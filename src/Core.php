<?php

namespace TechSpokes\SearchSuggest;


use WP_Query;

/**
 * Class Core
 *
 * @package TechSpokes\SearchSuggest
 */
class Core {

	/** @var string BASENAME Settings page slug and scripts handle. */
	const BASENAME = 'search-suggest';

	/**
	 * @var \TechSpokes\SearchSuggest\Core|null $instance
	 */
	protected static ?Core $instance = null;

	/**
	 * @return \TechSpokes\SearchSuggest\Core
	 */
	public static function getInstance(): Core {

		if ( ! ( self::$instance instanceof Core ) ) {
			self::setInstance( new self() );
		}

		return self::$instance;
	}

	/**
	 * @param \TechSpokes\SearchSuggest\Core|null $instance
	 */
	protected static function setInstance( ?Core $instance = null ): void {

		self::$instance = $instance;
	}

	/**
	 * Core constructor.
	 */
	protected function __construct() {

		/** register UI */
		add_action( 'admin_menu', array( $this, 'register_ui' ), 10, 0 );
		add_action( 'admin_init', array( $this, 'register_settings' ), 10, 0 );

		/** register scripts */
		add_action( 'wp_enqueue_scripts', array( $this, 'register_scripts' ), 9, 0 );
		add_action( 'pre_get_search_form', array( $this, 'enqueue_scripts' ), 10, 0 );

		/** add ajax */
		add_action( 'wp_ajax_get_search_suggestions', array( $this, 'ajax_get_search_suggestions' ), 10, 0 );
		add_action( 'wp_ajax_nopriv_get_search_suggestions', array( $this, 'ajax_get_search_suggestions' ), 10, 0 );
		add_action( 'wp_ajax_get_suggestion_url', array( $this, 'get_suggestion_url' ), 10, 0 );
		add_action( 'wp_ajax_nopriv_get_suggestion_url', array( $this, 'get_suggestion_url' ), 10, 0 );
	}

	/**
	 * Prints suggestion URL in AJAX.
	 *
	 * @noinspection PhpUnnecessaryCurlyVarSyntaxInspection
	 * @noinspection PhpNoReturnAttributeCanBeAddedInspection
	 */
	public function get_suggestion_url(): void {

		/** check ajax referer */
		check_ajax_referer(
			sprintf( '%s-select', self::BASENAME ),
			'id'
		);

		global $wpdb;

		$title = is_string( $_POST['title'] ?? null ) ? sanitize_text_field( $_POST['title'] ) : '';

		if ( false === ( $post_id = wp_cache_get( $key = sprintf( 'tssspid_%s', md5( $title ) ), 'post' ) ) ) {

			/** @var array $post_types Allowed post types */
			$post_types = apply_filters(
				'search_suggest_allowed_post_types',
				array_diff(
					get_post_types( array( 'public' => true ) ),
					array_filter( (array) get_option( 'ts_search_suggest_excluded_post_types' ) )
				)
			);

			$in = join( ', ', array_fill( 0, count( $post_types ), '%s' ) );

			/** @noinspection SqlDialectInspection */
			/** @noinspection SqlNoDataSourceInspection */
			$post_id = $wpdb->get_var(
				$wpdb->prepare(
					"SELECT `ID` FROM {$wpdb->posts} WHERE `post_title` = %s AND `post_status` = %s AND `post_type` IN ( {$in} ) LIMIT 1;",
					array_merge(
						array(
							$title,
							'publish'
						),
						array_values( $post_types )
					)
				)
			);

			if ( ! empty( $post_id ) ) {
				wp_cache_set( $key, $post_id, 'post', 21600 );
			}
		}

		if ( 0 !== ( $post_id = absint( $post_id ) ) ) {
			echo get_permalink( $post_id );
		}

		wp_die();
	}

	/**
	 * Prints search suggestions in AJAX.
	 *
	 * @noinspection PhpNoReturnAttributeCanBeAddedInspection
	 */
	public function ajax_get_search_suggestions(): void {

		/** check ajax referer */
		check_ajax_referer(
			sprintf( '%s-suggestions', self::BASENAME ),
			'id'
		);

		$s = is_string( $_GET['q'] ?? null ) ? sanitize_text_field( $_GET['q'] ) : '';

		/** @var array $post_types Allowed post types */
		$post_types = apply_filters(
			'search_suggest_allowed_post_types',
			array_diff(
				get_post_types( array( 'public' => true ) ),
				array_filter( (array) get_option( 'ts_search_suggest_excluded_post_types' ) )
			)
		);

		/** @var array $query_args Contains query arguments */
		$query_args = apply_filters(
			'search_suggest_query_args',
			array(
				's'              => $s,
				'post_type'      => $post_types,
				'post_status'    => 'publish',
				'posts_per_page' => 7
			)
		);

		$query = new WP_Query( $query_args );

		if ( $query->have_posts() ) {
			$results = wp_list_pluck( $query->posts, 'post_title', 'ID' );
			array_walk( $results, function ( &$title ) {
				$title = strip_tags( $title );
			} );
			echo join( "\n", $results );
		}

		/** exit here */
		wp_die();
	}

	/**
	 * Enqueues scripts from inside the search form.
	 */
	public function enqueue_scripts(): void {

		/** enqueue javascript */
		wp_enqueue_script( self::BASENAME );
		wp_localize_script(
			self::BASENAME,
			'searchSuggest',
			array(
				'suggestionsUrl' => add_query_arg(
					array(
						'action' => 'get_search_suggestions',
						'id'     => wp_create_nonce( sprintf( '%s-suggestions', self::BASENAME ) )
					),
					admin_url( 'admin-ajax.php' )
				),
				'selectUrl'      => admin_url( 'admin-ajax.php' ),
				'selectAction'   => 'get_suggestion_url',
				'selectId'       => wp_create_nonce( sprintf( '%s-select', self::BASENAME ) ),
				'suggestDelay'   => absint( get_option( 'ts_search_suggest_suggest_delay', 750 ) )
			)
		);

		/** enqueue style */
		wp_enqueue_style( self::BASENAME );
	}

	/**
	 * Registers scripts.
	 */
	public function register_scripts(): void {

		/** register javascript */
		wp_register_script(
			self::BASENAME,
			plugins_url(
				$this->maybe_minify_resource( 'resources/js/search-suggest.js' ),
				dirname( __FILE__ )
			),
			array( 'suggest' ),
			null,
			true
		);

		/** register CSS */
		wp_register_style(
			self::BASENAME,
			plugins_url(
				$this->maybe_minify_resource( 'resources/css/search-suggest.css' ),
				dirname( __FILE__ )
			),
			null,
			null
		);
	}

	/**
	 * Registers UI.
	 */
	public function register_ui(): void {

		/** add settings page */
		add_options_page(
			__( 'Search Suggest', 'ts-search-suggest' ),
			__( 'Search Suggest', 'ts-search-suggest' ),
			'manage_options',
			self::BASENAME,
			array( $this, 'settings_page' )
		);

		/** add settings sections */
		add_settings_section(
			'default',
			'',
			'__return_empty_string',
			self::BASENAME
		);

		/** add settings field */
		add_settings_field(
			'excluded-post-types',
			__( 'Excluded post types', 'ts-search-suggest' ),
			array( $this, 'excluded_post_types_settings_field' ),
			self::BASENAME
		);

		add_settings_field(
			'suggest-delay',
			__( 'Suggest delay', 'ts-search-suggest' ),
			array( $this, 'suggest_delay_settings_field' ),
			self::BASENAME
		);
	}

	/**
	 * Registers settings.
	 */
	public function register_settings(): void {
		// excluded post types setting
		register_setting(
			self::BASENAME,
			'ts_search_suggest_excluded_post_types',
			array(
				'type'              => 'string',
				'description'       => __( 'Serialized array of post type names excluded from search suggestion', 'ts-search-suggest' ),
				'sanitize_callback' => array( $this, 'sanitize_option_excluded_post_types' )
			)
		);
		// the suggest delay setting
		register_setting(
			self::BASENAME,
			'ts_search_suggest_suggest_delay',
			array(
				'type'              => 'integer',
				'description'       => __( 'Delay in milliseconds before fetching suggestions', 'ts-search-suggest' ),
				'sanitize_callback' => 'absint'
			)
		);
	}

	/**
	 * @param mixed $value
	 *
	 * @return array
	 * @noinspection PhpUnused
	 */
	public function sanitize_option_excluded_post_types( mixed $value ): array {
		return array_filter( array_map( 'sanitize_key', (array) $value ) );
	}

	/**
	 * Displays settings page.
	 */
	public function settings_page(): void {
		/** @noinspection HtmlUnknownTarget */
		printf(
			'<div class="wrap %1$s-settings-page">%2$s<form action="%3$s" method="post">',
			sanitize_html_class( self::BASENAME ),
			sprintf( '<h2>%s</h2>', esc_html( get_admin_page_title() ) ),
			esc_url( admin_url( 'options.php' ) )
		);
		settings_fields( self::BASENAME );
		do_settings_sections( self::BASENAME );
		submit_button();
		echo '</form></div>';
	}

	/**
	 * Displays excluded post types settings field.
	 */
	public function excluded_post_types_settings_field(): void {
		$post_types = get_post_types( array( 'public' => true ), 'objects' );
		$current    = array_filter( (array) get_option( 'ts_search_suggest_excluded_post_types' ) );
		/** @var \WP_Post_Type $post_type */
		foreach ( $post_types as $post_type ) {
			$input_attributes = array_filter(
				array(
					'type'    => 'checkbox',
					'id'      => ( $id = sprintf( 'excluded-post-types-%s', $post_type->name ) ),
					'name'    => sprintf( 'ts_search_suggest_excluded_post_types[%s]', $post_type->name ),
					'value'   => $post_type->name,
					'checked' => in_array( $post_type->name, $current ) ? 'checked' : ''
				)
			);
			array_walk( $input_attributes, function ( &$value, $key ) {
				$value = sprintf( '%1$s="%2$s"', $key, esc_attr( $value ) );
			} );
			/** @noinspection HtmlUnknownAttribute */
			printf(
				'<p><label for="%1$s"><input %2$s/> %3$s</label></p>',
				esc_attr( $id ),
				join( ' ', $input_attributes ),
				$post_type->label
			);
		}
		printf(
			'<p class="description">%s</p>',
			__( 'If checked, the posts of that type will be excluded from the suggestions in search form', 'ts-search-suggest' )
		);
	}

	/**
	 * Displays suggest delay settings field.
	 */
	public function suggest_delay_settings_field(): void {
		$delay = absint( get_option( 'ts_search_suggest_suggest_delay', 750 ) );
		printf(
			'<input type="number" id="suggest-delay" name="ts_search_suggest_suggest_delay" value="%1$s" class="small-text" min="0"/> <span class="description">%2$s</span>',
			esc_attr( $delay ),
			__( 'Delay in milliseconds before fetching suggestions', 'ts-search-suggest' )
		);
	}

	/**
	 * Injects .min in resource path if SCRIPT_DEBUG is not defined.
	 *
	 * @param string $path
	 *
	 * @return string
	 */
	protected function maybe_minify_resource( string $path = '' ): string {
		return defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ? $path
			: preg_replace( '/\.(css|js)/i', '.min.$1', $path );
	}
}
