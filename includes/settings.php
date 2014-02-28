<?php
/**
 * Settings class for Stream Reports
 *
 * @author X-Team <x-team.com>
 * @author Shady Sharaf <shady@x-team.com>, Jaroslav Polakovič <dero@x-team.com>, Jonathan Bardo <jonathan.bardo@x-team.com>
 */
class WP_Stream_Reports_Settings {

	/**
	 * Contains the option fields for the settings
	 *
	 * @var array $fields
	 */
	public static $fields = array();

	/**
	 * Contains the array of user options for the plugin
	 *
	 * @var array $user_options
	 */
	private static $user_options;

	/**
	 * Public constructor
	 */
	public static function load() {
		// User and role caps
		add_filter( 'user_has_cap', array( __CLASS__, '_filter_user_caps' ), 10, 4 );
		add_filter( 'role_has_cap', array( __CLASS__, '_filter_role_caps' ), 10, 3 );

		// Add Reports settings tab to Stream settings
		add_filter( 'wp_stream_options_fields', array( __CLASS__, '_register_settings' ) );
	}

	public static function get_fields() {
		if ( empty( self::$fields ) ) {
			$fields = array(
				'reports' => array(
					'title'  => __( 'Reports', 'stream-reports' ),
					'fields' => array(
						array(
							'name'        => 'role_access',
							'title'       => __( 'Role Access', 'stream-reports' ),
							'type'        => 'multi_checkbox',
							'desc'        => __( 'Users from the selected roles above will have permission to view and edit Stream Reports. However, only site Administrators can access Stream Reports Settings.', 'stream-reports' ),
							'choices'     => WP_Stream_Settings::get_roles(),
							'default'     => array( 'administrator' ),
						),
					),
				),
			);

			self::$fields = apply_filters( 'wp_stream_reports_options_fields', $fields );
		}
		return self::$fields;
	}

	/**
	 * Appends Reports settings to Stream settings
	 *
	 * @filter wp_stream_options_fields
	 */
	public static function _register_settings( $stream_fields ) {
		return array_merge( $stream_fields, self::get_fields() );
	}

	/**
	 * Filter user caps to dynamically grant our view cap based on allowed roles
	 *
	 * @filter user_has_cap
	 *
	 * @param $allcaps
	 * @param $caps
	 * @param $args
	 * @param $user
	 *
	 * @return array
	 */
	public static function _filter_user_caps( $allcaps, $caps, $args, $user = null ) {
		$user = is_a( $user, 'WP_User' ) ? $user : wp_get_current_user();

		foreach ( $caps as $cap ) {
			if ( WP_Stream_Reports::VIEW_CAP === $cap ) {
				foreach ( $user->roles as $role ) {
					if ( self::_role_can_access( $role ) ) {
						$allcaps[ $cap ] = true;
						break 2;
					}
				}
			}
		}

		return $allcaps;
	}

	/**
	 * Filter role caps to dynamically grant our view cap based on allowed roles
	 *
	 * @filter role_has_cap
	 *
	 * @param $allcaps
	 * @param $cap
	 * @param $role
	 *
	 * @return array
	 */
	public static function _filter_role_caps( $allcaps, $cap, $role ) {
		if ( WP_Stream_Reports::VIEW_CAP === $cap && self::_role_can_access( $role ) ) {
			$allcaps[ $cap ] = true;
		}

		return $allcaps;
	}

	private static function _role_can_access( $role ) {
		// Default role if one is not set by default
		if ( ! isset( WP_Stream_Settings::$options['reports_role_access'] ) ) {
			WP_Stream_Settings::$options['reports_role_access'] = array( 'administrator' );
		}

		if ( in_array( $role, WP_Stream_Settings::$options['reports_role_access'] ) ) {
			return true;
		}

		return false;
	}

	/**
	 * Get user option and store it in a static var for easy access
	 *
	 * @return array
	 */
	public static function get_user_options() {
		if ( ! empty( self::$user_options ) ) {
			return self::$user_options;
		} else {
			self::$user_options = get_user_option( __CLASS__ );

			// Return empty array if no user option is in db
			return ( self::$user_options ) ?: array();
		}
	}

	/**
	 * Save user option
	 *
	 * @param string $key
	 * @param mixed $option The actual value of the key
	 *
	 * @return unknown
	 */
	public static function update_user_options( $key, $option ) {
		$user_options = self::get_user_options();
		$user_options[ $key ] = $option;
		return update_user_option( get_current_user_id(), __CLASS__, $user_options );
	}

}
