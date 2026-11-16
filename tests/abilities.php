<?php
/**
 * Regression checks for datastore abilities against the installed WordPress API.
 *
 * Run: php tests/abilities.php path/to/wordpress
 */

$_SERVER['HTTP_HOST']      = 'noptin.test';
$_SERVER['REQUEST_SCHEME'] = 'https';
define( 'DISABLE_WP_CRON', true );
define( 'WP_HTTP_BLOCK_EXTERNAL', true );

// Load the library under test before another plugin's bundled controller.
$GLOBALS['wp_filter']['muplugins_loaded'][-999][] = array(
	'accepted_args' => 0,
	'function'      => static function () {
		require_once dirname( __DIR__ ) . '/src/REST_Controller.php';
		require_once dirname( __DIR__ ) . '/src/Abilities.php';
	},
);
require rtrim( $argv[1], '/\\' ) . '/wp-load.php';

/** Controller fixture that exercises permissions without querying fixture tables. */
class Ability_Test_Controller extends \Hizzle\Store\REST_Controller {
	/** @var bool Whether collection-wide reads are permitted. */
	public $allow_list = false;
	/** @var \Hizzle\Store\Collection In-memory fixture collection. */
	private $collection;

	/**
	 * Bind a fixture collection to an explicitly versioned route.
	 *
	 * @param \Hizzle\Store\Collection $collection Fixture collection.
	 */
	public function __construct( $collection ) {
		$this->collection = $collection;
		$this->namespace  = 'ability-fixture/v12';
		$this->rest_base  = 'people';
	}

	/** Retrieve the fixture collection. @return \Hizzle\Store\Collection */
	public function fetch_collection() {
		return $this->collection;
	}

	/**
	 * Require explicit permission for collection reads and the requested filter.
	 *
	 * @param \WP_REST_Request $request Requested operation.
	 * @return bool
	 */
	public function get_items_permissions_check( $request ) {
		return $this->allow_list && 'allowed' === $request['search'];
	}

	/**
	 * Permit exactly one record independently of collection list access.
	 *
	 * @param \WP_REST_Request $request Requested record or tab.
	 * @return bool
	 */
	public function get_item_permissions_check( $request ) {
		return 7 === (int) $request['id'] || (
			'email' === $request['hizzle_get_by'] &&
			'allowed+person@example.test' === rawurldecode( $request['hizzle_value'] ?? '' )
		);
	}

	/** Return one registered tab. @return array */
	public function get_record_tabs() {
		return array( 'activity' => array( 'callback' => '__return_empty_array' ) );
	}

	/**
	 * Return a fixture record through the actual REST endpoint.
	 *
	 * @param \WP_REST_Request $request Record request.
	 * @return \WP_REST_Response
	 */
	public function get_item( $request ) {
		return rest_ensure_response(
			array(
				'id'     => 7,
				'email'  => 'allowed+person@example.test',
				'amount' => 12.5,
			)
		);
	}
}

/**
 * Fail a regression check with a clear message.
 *
 * @param bool   $condition Expected condition.
 * @param string $message   Failure description.
 */
function ability_test_assert( $condition, $message ) {
	if ( ! $condition ) {
		throw new RuntimeException( $message );
	}
}

$store      = \Hizzle\Store\Store::init(
	'ability-fixture',
	array(
		'people' => array(
			'singular_name' => 'person',
			'props'         => array(
				'id'     => array( 'type' => 'BIGINT' ),
				'email'  => array(
					'type'   => 'VARCHAR',
					'length' => 255,
					'enum'   => array( 'allowed+person@example.test' => 'Allowed person' ),
				),
				'amount' => array( 'type' => 'DECIMAL' ),
			),
			'keys'          => array( 'unique' => array( 'email' ) ),
		),
	)
);
$collection = $store->get( 'people' );
$controller = new Ability_Test_Controller( $collection );

// Preserve a property literally named "sanitize_callback" inside nested schemas.
add_filter(
	$collection->hook_prefix( 'query_schema' ),
	static function ( $schema ) {
		$schema['nested'] = array(
			'type'       => 'object',
			'properties' => array(
				'sanitize_callback' => array(
					'type'  => 'array',
					'items' => array(
						'type'              => array(
							0 => 'string',
							4 => 'null',
						),
						'sanitize_callback' => 'sanitize_text_field',
						'arg_options'       => array( 'validate_callback' => '__return_true' ),
					),
				),
			),
		);
		return $schema;
	}
);
\Hizzle\Store\Abilities::init( 'ability-fixture', 'people', $controller );
add_action( 'rest_api_init', array( $controller, 'register_routes' ) );

$get = wp_get_ability( 'ability-fixture/get-person' );
ability_test_assert( (bool) $get, 'Get ability missing.' );
ability_test_assert( $get->check_permissions( array( 'id' => 7 ) ), 'Record access incorrectly requires list access.' );
ability_test_assert( ! $get->check_permissions( array( 'id' => 8 ) ), 'Protected record allowed.' );
ability_test_assert( $get->check_permissions( array( 'email' => 'allowed+person@example.test' ) ), 'Unique selector missing from permission request.' );
foreach ( array(
	array(),
	array(
		'id'    => 7,
		'email' => 'allowed+person@example.test',
	),
) as $invalid ) {
	ability_test_assert( is_wp_error( $get->validate_input( $invalid ) ), 'Invalid selector accepted.' );
}
foreach ( array( array( 'id' => 7 ), array( 'email' => 'allowed+person@example.test' ) ) as $input ) {
	ability_test_assert( ! is_wp_error( $get->execute( $input ) ), 'Allowed record execution failed.' );
}
$tab = wp_get_ability( 'ability-fixture/get-person-tab' );
ability_test_assert(
	$tab->check_permissions(
		array(
			'id'     => 7,
			'tab_id' => 'activity',
		)
	),
	'Tab denied for allowed record.'
);
ability_test_assert(
	! $tab->check_permissions(
		array(
			'id'     => 8,
			'tab_id' => 'activity',
		)
	),
	'Protected tab allowed.'
);

$list = wp_get_ability( 'ability-fixture/list-people' );
ability_test_assert( ! $list->check_permissions( array( 'search' => 'allowed' ) ), 'Denied list access allowed.' );
$options = wp_get_ability( 'ability-fixture/get-person-field-options' );
ability_test_assert( ! $options->check_permissions( array( 'field' => 'email' ) ), 'Field options bypassed collection permissions.' );
$controller->allow_list = true;
ability_test_assert( $list->check_permissions( array( 'search' => 'allowed' ) ), 'List filter missing from permission request.' );
ability_test_assert( ! $list->check_permissions( array( 'search' => 'denied' ) ), 'List filter ignored.' );
$nested = $list->get_input_schema()['properties']['nested']['properties']['sanitize_callback']['items'];
ability_test_assert( array( 'string', 'null' ) === $nested['type'], 'Nested types were not normalized.' );
ability_test_assert( ! isset( $nested['sanitize_callback'] ) && ! isset( $nested['arg_options'] ), 'Nested REST callbacks survived cleanup.' );
ability_test_assert( isset( $get->get_output_schema()['properties']['amount'] ), 'Record field schema missing.' );
ability_test_assert( isset( $list->get_output_schema()['properties']['people']['items']['properties']['email'] ), 'List item schema missing.' );
$aggregate = wp_get_ability( 'ability-fixture/aggregate-people' );
ability_test_assert( is_wp_error( $aggregate->validate_input( array( 'aggregate' => array( 'email' => 'AVG' ) ) ) ), 'Text average accepted.' );
ability_test_assert( ! is_wp_error( $aggregate->validate_input( array( 'aggregate' => array( 'amount' => 'AVG' ) ) ) ), 'Decimal average rejected.' );
ability_test_assert( $aggregate->check_permissions( array( 'search' => 'allowed' ) ), 'Aggregate filter missing from permission request.' );
ability_test_assert( isset( $aggregate->get_output_schema()['properties']['results'] ), 'Aggregate result wrapper missing.' );
echo "Ability permission and schema regressions passed.\n";
