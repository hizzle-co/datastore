<?php

/**
 * Store API: Exports a single collection of data.
 *
 * @since   1.0.0
 * @package Hizzle\Store
 */

namespace Hizzle\Store;

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

class Export {

	/**
	 * @var string $namespace.
	 */
	private $namespace;

	/**
	 * @var string $collection.
	 */
	private $collection;

	/**
	 * Number of records to process per batch during export.
	 *
	 * @var int
	 */
	const EXPORT_BATCH_SIZE = 500;

	/**
	 * Loads the class.
	 *
	 * @param string $namespace The store's namespace.
	 * @param string $collection The current collection.
	 */
	public function __construct( $store_namespace, $collection ) {
		$this->namespace  = $store_namespace;
		$this->collection = $collection;

		add_action( "{$this->namespace}_{$this->collection}_process_export", array( $this, 'process_export_task' ) );
		add_action( "{$this->namespace}_{$this->collection}_cleanup_export", array( $this, 'cleanup_export_file' ) );

		// Download & AJAX
		add_action( 'init', array( $this, 'maybe_download_export' ) );
		add_action( "wp_ajax_{$this->namespace}_{$this->collection}_run_export", array( $this, 'ajax_run_export' ) );
	}

	/**
	 * Retrieves the current store.
	 *
	 * @return Store|null The store, or null if not registered.
	 * @since 1.0.0
	 */
	public function fetch_store() {
		try {
			return Store::instance( $this->namespace );
		} catch ( Store_Exception $e ) {
			return null;
		}
	}

	/**
	 * Retrieves the current collection.
	 *
	 * @return Collection|null The collection, or null if not registered.
	 * @since 1.0.0
	 */
	public function fetch_collection() {
		$store = $this->fetch_store();
		return $store ? $store->get( $this->collection ) : null;
	}

	/**
	 * Downloads an exported CSV file.
	 */
	public function maybe_download_export() {
		$token = $_GET[ $this->namespace . '_' . $this->collection . '_download' ] ?? '';

		if ( empty( $token ) ) {
			return;
		}

		// Get export data from transient
		$export_data = get_transient( 'hizzle_export_' . $token );

		if ( false === $export_data ) {
			wp_die(
				'Invalid or expired download token.',
				'Error',
				array( 'response' => 404 )
			);
		}

		if ( get_current_user_id() !== $export_data['user_id'] ) {
			wp_die(
				'You are not authorized to download this file.',
				'Error',
				array( 'response' => 403 )
			);
		}

		// Verify file exists
		if ( ! file_exists( $export_data['file'] ) ) {
			delete_transient( 'hizzle_export_' . $token );
			wp_die(
				'Export file not found.',
				'Error',
				array( 'response' => 404 )
			);
		}

		// Send file
		$filename = sanitize_file_name( basename( $export_data['file'] ) );
		// Additional sanitization to prevent header injection
		$filename = preg_replace( '/[^a-zA-Z0-9._-]/', '', $filename );

		// Set headers for file download
		header( 'Content-Type: text/csv; charset=utf-8' );
		header( 'Content-Disposition: attachment; filename="' . $filename . '"' );
		header( 'Content-Length: ' . filesize( $export_data['file'] ) );
		header( 'Cache-Control: no-cache, no-store, must-revalidate' );
		header( 'Pragma: no-cache' );
		header( 'Expires: 0' );

		// Output file content
		readfile( $export_data['file'] );
		exit;
	}

    /**
	 * Trigger the export manually if Cron is slow.
	 */
	public function ajax_run_export() {
		check_ajax_referer( "{$this->namespace}_{$this->collection}_export" );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( 'Unauthorized', 403 );
		}

		$params = isset( $_POST['params'] ) ? map_deep( $_POST['params'], 'sanitize_text_field' ) : array();
		$result = $this->schedule_export_task( $params );

		if ( is_wp_error( $result ) ) {
			wp_send_json_error( $result->get_error_message() );
		}

		wp_send_json_success( array( 'message' => 'Export started in background.' ) );
	}

	/**
	 * Schedules an export task.
	 *
	 * @param array $params Filters for the export.
	 * @return int|WP_Error Export task ID on success, WP_Error on failure.
	 */
	public function schedule_export_task( $params ) {
		// Get the current user.
		$user = wp_get_current_user();

		if ( empty( $user ) || empty( $user->ID ) ) {
			return new \WP_Error( 'user_not_found', 'User not found.', array( 'status' => 401 ) );
		}

		// Prepare export data.
		$export_data = array(
			'namespace'    => $this->namespace,
			'collection'   => $this->collection,
			'params'       => $params,
			'user_id'      => $user->ID,
			'user_email'   => $user->user_email,
			'timestamp'    => time(),
			'file_id'      => sprintf(
				'%s-%s-%s.csv',
				$this->namespace,
				$this->collection,
				uniqid()
			),
			'current_page' => 1,
		);

		// Schedule the task.
		$scheduled = wp_schedule_single_event(
			time() + MINUTE_IN_SECONDS,
			"{$this->namespace}_{$this->collection}_process_export",
			array( $export_data )
		);

		if ( false === $scheduled ) {
			return new \WP_Error( 'schedule_failed', 'Failed to schedule export task.', array( 'status' => 500 ) );
		}

		// Return a unique export ID (timestamp).
		return $export_data['timestamp'];
	}

	/**
	 * Processes the export task.
	 *
	 * @param array $export_data Export task data.
	 */
	public function process_export_task( $export_data ) {
		try {

			// Raise the time limit.
			self::raise_time_limit();

			// Ignore user aborts.
			ignore_user_abort( true );

			// Get the collection.
			$collection = $this->fetch_collection();

			if ( empty( $collection ) ) {
				throw new Store_Exception( 'missing_store', wp_sprintf( 'Store %s not found.', esc_html( $this->namespace ) ) );
			}

			// Generate CSV with batch processing.
			$csv_path = $this->generate_csv_in_batches( $export_data, $collection );

			if ( is_wp_error( $csv_path ) ) {
				self::send_export_error_email( $export_data['user_email'], $csv_path->get_error_message() );
				return;
			}

			// Schedule cleanup.
			wp_schedule_single_event(
				time() + ( 24 * HOUR_IN_SECONDS ), // Delete after 24 hours
				"{$this->namespace}_{$this->collection}_cleanup_export",
				array( $csv_path )
			);

			// Send email with download link.
			self::send_export_email( $export_data, $csv_path );

		} catch ( Store_Exception $e ) {
			self::send_export_error_email( $export_data['user_email'], $e->getMessage() );
		}
	}

	/**
	 * Generates a CSV file from items using batch processing.
	 *
	 * @param array      $export_data Export task data.
	 * @param Collection $collection  The collection.
	 * @return string|WP_Error CSV file path on success, WP_Error on failure.
	 */
	protected function generate_csv_in_batches( $export_data, $collection ) {
		// Get upload directory.
		$upload_dir = wp_upload_dir();

		if ( ! empty( $upload_dir['error'] ) ) {
			return new \WP_Error( 'upload_dir_error', $upload_dir['error'] );
		}

		// Create exports directory if it doesn't exist.
		$exports_dir = trailingslashit( $upload_dir['basedir'] ) . $this->namespace . '-exports/' . $this->collection;

		if ( ! file_exists( $exports_dir ) ) {
			wp_mkdir_p( $exports_dir );

			// Add .htaccess to protect the directory from direct access.
			$htaccess_file = trailingslashit( $exports_dir ) . '.htaccess';
			if ( ! file_exists( $htaccess_file ) ) {
				// Block direct access but allow PHP to read
				$htaccess_content  = "# Protect export files\n";
				$htaccess_content .= "<Files *>\n";
				$htaccess_content .= "Order Deny,Allow\n";
				$htaccess_content .= "Deny from all\n";
				$htaccess_content .= "</Files>\n";
				file_put_contents( $htaccess_file, $htaccess_content );
			}
		}

		// Generate unique filename.
		$filename = sprintf(
			'%s-%s-%s-%s.csv',
			$this->namespace,
			$this->collection,
			$export_data['timestamp'],
			wp_generate_password( 12, false )
		);

		$file_path = trailingslashit( $exports_dir ) . $filename;

		// Open file for writing.
		$file = fopen( $file_path, 'w' );

		if ( false === $file ) {
			return new \WP_Error( 'file_open_error', 'Failed to create CSV file.' );
		}

		// Get fields to export.
		$fields = array();

		if ( ! empty( $export_data['params']['__fields'] ) ) {
			$fields = wp_parse_list( $export_data['params']['__fields'] );
		} else {
			// Get all non-hidden fields.
			// Convert hidden array to hashmap for faster lookups
			$hidden     = is_array( $collection->hidden ) ? $collection->hidden : array();
			$hidden_map = array_flip( $hidden );
			foreach ( $collection->get_props() as $prop ) {
				if ( ! isset( $hidden_map[ $prop->name ] ) && ! $prop->is_dynamic ) {
					$fields[] = $prop->name;
				}
			}
		}

		// Write CSV header.
		fputcsv( $file, $fields );

		// Process items in batches to avoid memory issues.
		$params             = $export_data['params'];
		$params['per_page'] = self::EXPORT_BATCH_SIZE;
		$page               = 1;
		$total_pages        = 1;

		do {
			$params['paged'] = $page;

			// Query a batch of items.
			$query = $collection->query( $params );
			$items = $query->get_results();

			$total_pages = ceil( $query->get_total() / self::EXPORT_BATCH_SIZE );

			// Write batch to CSV.
			foreach ( $items as $item ) {
				$row = array();

				foreach ( $fields as $field ) {
					$value = $item->get( $field );

					// Handle null values.
					if ( null === $value ) {
						$value = '';
					}

					// Convert dates to string.
					if ( $value instanceof \DateTime ) {
						$value = $value->format( DATE_ATOM );
					}

					// Convert objects with __toString method to string.
					if ( is_object( $value ) && method_exists( $value, '__toString' ) ) {
						$value = (string) $value;
					}

					// Convert arrays to comma-separated strings.
					if ( is_array( $value ) ) {
						$value = implode( ', ', $value );
					}

					// Convert booleans to 0/1.
					if ( is_bool( $value ) ) {
						$value = (int) $value;
					}

					$row[] = is_scalar( $value ) ? $value : maybe_serialize( $value );
				}

				fputcsv( $file, $row );
			}

			++$page;

			// Free memory after each batch.
			unset( $items, $query );

		} while ( $page < $total_pages );

		fclose( $file );

		return $file_path;
	}

	/**
	 * Sends an export email with download link.
	 *
	 * @param array  $export_data Export data including namespace and user info.
	 * @param string $csv_path    CSV file path.
	 */
	protected function send_export_email( $export_data, $csv_path ) {
		// Generate a secure download token with additional entropy
		$filename    = basename( $csv_path );
		$random_salt = wp_generate_password( 32, true, true );
		$token       = wp_hash( $filename . $export_data['user_id'] . $export_data['timestamp'] . $random_salt );

		// Store the token temporarily (24 hours)
		set_transient(
			'hizzle_export_' . $token,
			array(
				'file'    => $csv_path,
				'user_id' => $export_data['user_id'],
			),
			24 * HOUR_IN_SECONDS
		);

		// Create download URL with token
		$download_url = add_query_arg(
			$this->namespace . '_' . $this->collection . '_download',
			$token,
			home_url( '/' )
		);

		$subject = 'Your Export is Ready';

		// Build email message
		$message_parts = array(
			'Your export has been generated successfully. You can download it from the link below:',
			'',
			esc_url( $download_url ),
			'',
			'Please note that this file will be automatically deleted in 24 hours.',
			'',
			'Thank you!',
		);
		$message       = implode( "\n", $message_parts );

		$attachments = array();
		// Attach if less than 5MB
		if ( filesize( $csv_path ) <= 5 * 1024 * 1024 ) {
			$attachments[] = $csv_path;
		}

		$sent = wp_mail( $export_data['user_email'], $subject, $message, '', $attachments );

		// Log if email failed
		if ( ! $sent ) {
			error_log(
				sprintf(
					'Failed to send export email to %s for file %s',
					$export_data['user_email'],
					$filename
				)
			);
		}
	}

	/**
	 * Sends an error email when export fails.
	 *
	 * @param string $email   User email.
	 * @param string $message Error message.
	 */
	protected static function send_export_error_email( $email, $message ) {
		$subject = 'Export Failed';

		// Build email message
		$message_parts = array(
			'Unfortunately, your export failed with the following error:',
			'',
			$message,
			'',
			'Please try again or contact support if the problem persists.',
		);
		$email_message = implode( "\n", $message_parts );

		$sent = wp_mail( $email, $subject, $email_message );

		// Log if email failed
		if ( ! $sent ) {
			error_log(
				sprintf(
					'Failed to send export error email to %s. Error: %s',
					$email,
					$message
				)
			);
		}
	}

	/**
	 * Cleans up an exported file.
	 *
	 * @param string $file_path File path to delete.
	 */
	public function cleanup_export_file( $file_path ) {
		if ( file_exists( $file_path ) ) {
			wp_delete_file( $file_path );
		}
	}

	/**
	 * Attempts to raise the PHP timeout for time intensive processes.
	 *
	 * Only allows raising the existing limit and prevents lowering it.
	 *
	 * @param int $limit The time limit in seconds.
	 */
	public static function raise_time_limit( $limit = 0 ) {
		$limit              = (int) $limit;
		$max_execution_time = (int) ini_get( 'max_execution_time' );

		/*
		 * If the max execution time is already unlimited (zero), or if it exceeds or is equal to the proposed
		 * limit, there is no reason for us to make further changes (we never want to lower it).
		 */
		if ( 0 === $max_execution_time || ( $max_execution_time >= $limit && 0 !== $limit ) ) {
			return;
		}

		if ( function_exists( 'set_time_limit' ) && false === strpos( ini_get( 'disable_functions' ), 'set_time_limit' ) && ! ini_get( 'safe_mode' ) ) { // phpcs:ignore PHPCompatibility.IniDirectives.RemovedIniDirectives.safe_modeDeprecatedRemoved
			@set_time_limit( $limit ); // @codingStandardsIgnoreLine
		}
	}
}
