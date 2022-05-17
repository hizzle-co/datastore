<?php

namespace Hizzle\Store;

/**
 * Wrapper for PHP DateTime which adds support for gmt/utc offset when a
 * timezone is absent
 *
 * @since   1.0.0
 */

defined( 'ABSPATH' ) || exit;

/**
 * Datetime class.
 */
class Date_Time extends \DateTime {

	/**
	 * Output an ISO 8601 date string in local (WordPress) timezone.
	 *
	 * @since  1.0.0
	 * @return string
	 */
	public function __toString() {
		return $this->format( DATE_ATOM );
	}

	/**
	 * Get the timestamp with the WordPress timezone offset added or subtracted.
	 *
	 * @since  1.0.0
	 * @return int
	 */
	public function getOffsetTimestamp() {
		return $this->getTimestamp() + $this->getOffset();
	}

	/**
	 * Format a date based on the UTC timestamp.
	 *
	 * @since  1.0.0
	 * @param  string $format Date format.
	 * @return string
	 */
	public function utc( $format = 'Y-m-d H:i:s' ) {
		return gmdate( $format, $this->getTimestamp() );
	}

	/**
	 * Format a date based on the offset timestamp.
	 *
	 * @since  1.0.0
	 * @param  string $format Date format.
	 * @return string
	 */
	public function date( $format = 'Y-m-d H:i:s' ) {
		return gmdate( $format, $this->getOffsetTimestamp() );
	}

	/**
	 * Return a localised date based on offset timestamp. Wrapper for date_i18n function.
	 *
	 * @since  1.0.0
	 * @param  string $format Date format.
	 * @return string
	 */
	public function date_i18n( $format = 'Y-m-d' ) {
		return date_i18n( $format, $this->getOffsetTimestamp() );
	}

}
