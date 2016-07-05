<?php
/**
 * Core: Torro_Field_Mappers_Manager class
 *
 * @package TorroForms
 * @subpackage CoreManagers
 * @version 1.0.0-beta.6
 * @since 1.1.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Torro Forms extension manager class
 *
 * @since 1.1.0
 */
final class Torro_Field_Mappers_Manager extends Torro_Manager {

	/**
	 * Instance
	 *
	 * @var null|Torro_Field_Mappers_Manager
	 * @since 1.1.0
	 */
	private static $instance = null;

	public static function instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	protected function __construct() {
		parent::__construct();
	}

	protected function allowed_modules(){
		$allowed = array(
			'field_mappers'	=> 'Torro_Field_Mapper'
		);
		return $allowed;
	}

	protected function get_category() {
		return 'field_mappers';
	}
}
