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

		add_filter( 'torro_formbuilder_element_tabs', array( $this, 'admin_tabs' ), 10, 2 );
	}

	public function __call( $method_name, $args ) {
		switch ( $method_name ) {
			case 'admin_tabs':
				return call_user_func_array( array( $this, $method_name ), $args );
		}
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

	protected function admin_tabs( $tabs, $element ) {
		$mappers = $this->get_all_registered();
		if ( empty( $mappers ) ) {
			return $tabs;
		}

		$container = torro()->containers()->get( $element->container_id );
		if ( is_wp_error( $container ) ) {
			return $tabs;
		}

		foreach ( $mappers as $mapper ) {
			$tab = $this->get_mapper_element_tab( $mapper, $element->id, $container->form_id );
			if ( $tab ) {
				$tabs[] = $tab;
			}
		}

		return $tabs;
	}

	protected function get_mapper_element_tab( $mapper, $element_id, $form_id ) {
		$content = $this->render_mapper_element_tab_content( $mapper, $element_id, $form_id );
		if ( empty( $content ) ) {
			return false;
		}

		return array(
			'slug'		=> $mapper->name,
			'title'		=> $mapper->title,
			'content'	=> $content,
		);
	}

	protected function render_mapper_element_tab_content( $mapper, $element_id, $form_id ) {
		if ( ! $mapper->is_active_for_form( $form_id ) ) {
			return '';
		}

		$element = torro()->elements()->get( $element_id );
		if ( is_wp_error( $element ) ) {
			// Create a dummy element (needed for AJAX).
			$element = new Torro_Element( $element_id );
		}
		$element_type = $element->type_obj;

		$field = array(
			'title'			=> __( 'Mapped Field', 'torro-forms' ),
			'description'	=> __( 'Select the field you would like to map the element to.', 'torro-forms' ),
			'type'			=> 'select',
			'values'		=> array(
				''				=> __( 'Choose a field...', 'torro-forms' ),
			),
			'default'		=> '',
		);

		$mapping_fields = $mapper->get_fields();
		foreach ( $mapping_fields as $slug => $field ) {
			$field['values'][ $slug ] = $field['title'];
		}

		$output = $element_type->admin_widget_settings_field( $mapper->name . '_mapping', $field, $element );

		return $output;
	}
}
