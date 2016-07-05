<?php
/**
 * Core: Torro_Form_Controller_Cache class
 *
 * @package TorroForms
 * @subpackage Core
 * @version 1.0.0-beta.6
 * @since 1.0.0-beta.1
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class for temporary response cache
 *
 * @since 1.0.0-beta.1
 */
class Torro_Form_Controller_Cache {

	/**
	 * Controller id
	 *
	 * @var null
	 * @since 1.0.0
	 */
	private $controller_id = null;

	public static function init() {
		if ( ! isset( $_SESSION ) && ! headers_sent() ) {
			session_start();
		}
	}

	/**
	 * Torro_Controller_Cache constructor.
	 *
	 * @return bool
	 * @since 1.0.0
	 */
	public function __construct( $controller_id ) {
		$this->controller_id = $controller_id;
	}

	/**
	 * Resetting controller
	 *
	 * @since 1.0.0
	 */
	public function reset(){
		unset( $_SESSION[ 'torro_forms' ][ $this->controller_id ] );
	}

	/**
	 * Setting up response
	 *
	 * @param $response
	 *
	 * @since 1.0.0
	 */
	public function set_response( $response ) {
		$this->set( 'response', $response );
	}

	/**
	 * Adds a global error.
	 *
	 * @since 1.1.0
	 *
	 * @param Torro_Error $error
	 */
	public function add_global_error( $error ) {
		$messages = $this->get( 'global_error' );

		$this->set( 'global_error', array_merge( $messages, $error->get_error_messages() ) );
	}

	/**
	 * Setting values by key
	 *
	 * @param $key
	 * @param $data
	 *
	 * @since 1.0.0
	 */
	private function set( $key, $data ) {
		$_SESSION[ 'torro_forms' ][ $this->controller_id ][ $key ] = $data;
	}

	/**
	 * Getting Response
	 *
	 * @return bool|mixed
	 * @since 1.0.0
	 */
	public function get_response() {
		return $this->get( 'response' );
	}

	/**
	 * Retrieves the current global error.
	 *
	 * @since 1.1.0
	 *
	 * @return array Array of error messages or empty array.
	 */
	public function get_global_error() {
		$error = $this->get( 'global_error' );

		$this->delete( 'global_error' );

		return $error;
	}

	/**
	 * Adding a Response
	 * @param $response
	 * @since 1.0.0
	 */
	public function add_response( $response ){
		$cached_response = $this->get_response();
		$response_merged = array_replace_recursive( $cached_response, $response );

		if( isset( $response[ 'containers' ] ) ) {
			// Replacing element values because of maybe empty values of checkboxes
			foreach ( $response[ 'containers' ] as $container_id => $container ) {
				foreach ( $container[ 'elements' ] as $element_id => $element ) {
					$response_merged[ 'containers' ][ $container_id ][ 'elements' ][ $element_id ] = $response[ 'containers' ][ $container_id ][ 'elements' ][ $element_id ];
				}
			}
		}

		return $this->set_response( $response_merged );
	}

	/**
	 * Getting values by key
	 *
	 * @param $key
	 *
	 * @return mixed
	 * @since 1.0.0
	 */
	private function get( $key ) {
		if ( isset( $_SESSION[ 'torro_forms' ][ $this->controller_id ][ $key ] ) ) {
			return $_SESSION[ 'torro_forms' ][ $this->controller_id ][ $key ];
		}

		return array();
	}

	/**
	 * Deleting response values
	 *
	 * @return bool
	 * @since 1.0.0
	 */
	public function delete_response() {
		return $this->delete( 'response' );
	}

	/**
	 * Deleting values by key
	 *
	 * @param $key
	 *
	 * @return bool
	 * @since 1.0.0
	 */
	public function delete( $key ) {
		if ( isset( $_SESSION[ 'torro_forms' ][ $this->controller_id ][ $key ] ) ) {
			unset( $_SESSION[ 'torro_forms' ][ $this->controller_id ][ $key ] );

			return true;
		}

		return false;
	}

	/**
	 * Setting finished
	 *
	 * @since 1.0.0
	 */
	public function set_finished(){
		$this->set( 'finished', true );
	}

	/**
	 * Checking if is finished
	 *
	 * @return bool|mixed
	 * @since 1.0.0
	 */
	public function is_finished(){
		return $this->get( 'finished' );
	}
}
