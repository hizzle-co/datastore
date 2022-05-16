<?php

namespace Hizzle\Store;

/**
 * Store API: Handles CRUD operations on a single object.
 *
 * @since   1.0.0
 * @package Hizzle\Store
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Handles CRUD operations on a single object.
 *
 * @since 1.0.0
 */
class Record {

	/**
	 * ID for this object.
	 *
	 * @since 1.0.0
	 * @var int
	 */
	protected $id = 0;

	/**
	 * Core data for this object. Name value pairs (name + default value).
	 *
	 * @since 1.0.0
	 * @var array
	 */
	protected $data = array();

	/**
	 * Core data changes for this object.
	 *
	 * @since 1.0.0
	 * @var array
	 */
	protected $changes = array();

	/**
	 * Set to _data on construct so we can track and reset data if needed.
	 *
	 * @since 1.0.0
	 * @var array
	 */
	protected $default_data = array();

	/**
	 * This is false until the object is read from the DB.
	 *
	 * @since 1.0.0
	 * @var bool
	 */
	protected $object_read = false;

	/**
	 * The collection.
	 *
	 * @since 1.0.0
	 * @var string
	 */
	protected $collection_name = '';

	/**
	 * This is the name of this object type. Used for hooks, etc.
	 *
	 * @since 1.0.0
	 * @var string
	 */
	protected $object_type = 'data';

	/**
	 * Default constructor.
	 *
	 * This class is not meant to be init directly.
	 * @see Collection::get()
	 * @param int|Record|array $object ID to load from the DB (optional) or already queried data.
	 * @throws Store_Exception Throws exception if ID is set & invalid.
	 */
	public function __construct( $object = 0, $args = array() ) {

		// Init class properties.
		foreach ( $args as $key => $value ) {
			if ( property_exists( $this, $key ) ) {
				$this->$key = $value;
			}
		}

		// Set the default data.
		$this->default_data = $this->data;

		// If we have an ID, load the data from the DB.
		if ( ! empty( $object ) && is_callable( array( $object, 'get_id' ) ) ) {
			$object = call_user_func( array( $object, 'get_id' ) );
		}

		// If we have an array of data, check id.
		if ( is_array( $object ) && ! empty( $object['id'] ) ) {
			$object = $object['id'];
		}

		// Read the object from the DB.
		if ( ! empty( $object ) && is_numeric( $object ) ) {
			$this->set_id( $object );
			Collection::instance( $this->collection_name )->read( $this );
		}

		$this->set_object_read( true );
	}

	/**
	 * Only store the object ID to avoid serializing the data object instance.
	 *
	 * @return array
	 */
	public function __sleep() {
		return array( 'id' );
	}

	/**
	 * Re-run the constructor with the object ID.
	 *
	 * If the object no longer exists, remove the ID.
	 */
	public function __wakeup() {
		try {
			$this->__construct( absint( $this->id ) );
		} catch ( Store_Exception $e ) {
			$this->set_id( 0 );
			$this->set_object_read( true );
		}
	}

	/**
	 * Change data to JSON format.
	 *
	 * @since  1.0.0
	 * @return string Data in JSON format.
	 */
	public function __toString() {
		return wp_json_encode( $this->get_data() );
	}

	/**
	 * Set the object ID.
	 *
	 * @since 1.0.0
	 * @param int $id Object ID.
	 */
	public function set_id( $id ) {
		$this->id = absint( $id );
	}

	/**
	 * Returns the object ID.
	 *
	 * @since 1.0.0
	 * @return int
	 */
	public function get_id() {
		return absint( $this->id );
	}

	/**
	 * Check if the object exists in the DB.
	 *
	 * @since 1.0.0
	 * @return bool
	 */
	public function exists() {
		return ! empty( $this->id );
	}

	/**
	 * Reads the object from the DB (or cache).
	 *
	 * @since 1.0.0
	 * @throws Store_Exception exception if object not found.
	 */
	protected function read() {
		$result = $this->db->read( $this );

		if ( empty( $result ) ) {
			throw new Store_Exception( $this->object_type . '_not_found', $this->not_found_error() );
		}
	}

	/**
	 * Returns an error when a record is not found.
	 *
	 * @return string
	 */
	protected function not_found_error() {
		return __( 'Record not found.', 'hizzle-store' );
	}

	/**
	 * Set object read property.
	 *
	 * @since 3.0.0
	 * @param boolean $read Should read?.
	 */
	public function set_object_read( $read = true ) {
		$this->object_read = (bool) $read;
	}

	/**
	 * Get object read property.
	 *
	 * @since  3.0.0
	 * @return boolean
	 */
	public function get_object_read() {
		return (bool) $this->object_read;
	}

	/**
	 * Delete an object, set the ID to 0, and return result.
	 *
	 * @since  1.0.0
	 * @param  bool $force_delete Should the data be deleted permanently.
	 * @return bool result
	 */
	public function delete( $force_delete = false ) {
		if ( $this->db ) {
			call_user_func( array( $this->db, 'delete' ), $this, $force_delete );
			$this->set_id( 0 );
			return true;
		}
		return false;
	}

	/**
	 * Save should create or update based on object existence.
	 *
	 * @since  1.0.0
	 * @return int|WP_Error
	 */
	public function save() {
		if ( ! $this->db ) {
			return $this->get_id();
		}

		do_action( 'hpay_before_' . $this->object_type . '_object_save', $this, $this->db );

		try {

			if ( $this->get_id() ) {
				call_user_func_array( array( $this->db, 'update' ), array( &$this ) );
			} else {
				call_user_func_array( array( $this->db, 'create' ), array( &$this ) );
			}
		} catch ( Store_Exception $e ) {
			return new \WP_Error( $e->getErrorCode(), $e->getMessage(), $e->getErrorData() );
		}

		do_action( 'hpay_after_' . $this->object_type . '_object_save', $this, $this->db );

		return $this->get_id();
	}

	/**
	 * Returns all data for this object.
	 *
	 * @since  2.6.0
	 * @return array
	 */
	public function get_data() {
		return array_merge( array( 'id' => $this->get_id() ), $this->data );
	}

	/**
	 * Set all props to default values.
	 *
	 * @since 3.0.0
	 */
	public function set_defaults() {
		$this->data    = $this->default_data;
		$this->changes = array();
		$this->set_object_read( false );
	}

	/**
	 * Set a collection of props in one go, collect any errors, and return the result.
	 * Only sets using public methods.
	 *
	 * @since  3.0.0
	 *
	 * @param array  $props Key value pairs to set. Key is the prop and should map to a setter function name.
	 * @param string $context In what context to run this.
	 *
	 * @return bool|WP_Error
	 */
	public function set_props( $props ) {
		$errors = false;

		foreach ( $props as $prop => $value ) {
			try {
				/**
				 * Checks if the prop being set is allowed, and the value is not null.
				 */
				if ( in_array( $prop, array( 'prop', 'date_prop' ), true ) ) {
					continue;
				}
				$setter = "set_$prop";

				if ( is_callable( array( $this, $setter ) ) ) {
					$this->{$setter}( $value );
				}
			} catch ( Store_Exception $e ) {
				if ( ! $errors ) {
					$errors = new \WP_Error();
				}
				$errors->add( $e->getErrorCode(), $e->getMessage(), $e->getErrorData() );
			}
		}

		return $errors && count( $errors->get_error_codes() ) ? $errors : true;
	}

	/**
	 * Sets a prop for a setter method.
	 *
	 * This stores changes in a special array so we can track what needs saving
	 * the the DB later.
	 *
	 * @since 1.0.0
	 * @param string $prop Name of prop to set.
	 * @param mixed  $value Value of the prop.
	 */
	protected function set_prop( $prop, $value ) {
		if ( array_key_exists( $prop, $this->data ) ) {
			if ( true === $this->object_read ) {
				if ( $value !== $this->data[ $prop ] || array_key_exists( $prop, $this->changes ) ) {
					$this->changes[ $prop ] = $value;
				}
			} else {
				$this->data[ $prop ] = $value;
			}
		}
	}

	/**
	 * Return data changes only.
	 *
	 * @since 3.0.0
	 * @return array
	 */
	public function get_changes() {
		return $this->changes;
	}

	/**
	 * Merge changes with data and clear.
	 *
	 * @since 3.0.0
	 */
	public function apply_changes() {
		$this->data    = array_replace_recursive( $this->data, $this->changes ); // @codingStandardsIgnoreLine
		$this->changes = array();
	}

	/**
	 * Prefix for action and filter hooks on data.
	 *
	 * @since  3.0.0
	 * @return string
	 */
	protected function get_hook_prefix() {
		return 'hpay_' . $this->object_type . '_get_';
	}

	/**
	 * Gets a prop for a getter method.
	 *
	 * Gets the value from either current pending changes, or the data itself.
	 * Context controls what happens to the value before it's returned.
	 *
	 * @since  3.0.0
	 * @param  string $prop Name of prop to get.
	 * @param  string $context What the value is for. Valid values are view and edit.
	 * @return mixed
	 */
	protected function get_prop( $prop, $context = 'view' ) {
		$value = null;

		if ( array_key_exists( $prop, $this->data ) ) {
			$value = array_key_exists( $prop, $this->changes ) ? $this->changes[ $prop ] : $this->data[ $prop ];

			if ( 'view' === $context ) {
				$value = apply_filters( $this->get_hook_prefix() . $prop, $value, $this );
			}
		}

		return $value;
	}

	/**
	 * Sets a date prop whilst handling formatting and datetime objects.
	 *
	 * @since 3.0.0
	 * @param string         $prop Name of prop to set.
	 * @param string|integer $value Value of the prop.
	 */
	protected function set_date_prop( $prop, $value ) {
		try {
			if ( empty( $value ) ) {
				$this->set_prop( $prop, null );
				return;
			}

			// Create date/time object from passed date value.
			if ( is_a( $value, 'Hpay_DateTime' ) ) {
				$datetime = $value;
			} elseif ( is_numeric( $value ) ) {
				// Timestamps are handled as UTC timestamps in all cases.
				$datetime = new \Hpay_DateTime( "@{$value}", new \DateTimeZone( 'UTC' ) );
			} else {
				// Strings are defined in local WP timezone. Convert to UTC.
				if ( 1 === preg_match( '/^(\d{4})-(\d{2})-(\d{2})T(\d{2}):(\d{2}):(\d{2})(Z|((-|\+)\d{2}:\d{2}))$/', $value, $date_bits ) ) {
					$offset    = ! empty( $date_bits[7] ) ? iso8601_timezone_to_offset( $date_bits[7] ) : hpay_timezone_offset();
					$timestamp = gmmktime( $date_bits[4], $date_bits[5], $date_bits[6], $date_bits[2], $date_bits[3], $date_bits[1] ) - $offset;
				} else {
					$timestamp = hpay_strtotime( get_gmt_from_date( gmdate( 'Y-m-d H:i:s', hpay_strtotime( $value ) ) ) );
				}
				$datetime = new \Hpay_DateTime( "@{$timestamp}", new \DateTimeZone( 'UTC' ) );
			}

			// Set local timezone or offset.
			$datetime->setTimezone( wp_timezone() );

			$this->set_prop( $prop, $datetime );
		} catch ( \Exception $e ) {} // @codingStandardsIgnoreLine.
	}

	/**
	 * When invalid data is found, throw an exception unless reading from the DB.
	 *
	 * @throws Store_Exception Data Exception.
	 * @since 3.0.0
	 * @param string $code             Error code.
	 * @param string $message          Error message.
	 * @param int    $http_status_code HTTP status code.
	 * @param array  $data             Extra error data.
	 */
	protected function error( $code, $message, $http_status_code = 400, $data = array() ) {
		throw new Store_Exception( $code, $message, $http_status_code, $data );
	}

}
