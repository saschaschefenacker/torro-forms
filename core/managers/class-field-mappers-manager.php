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

		torro()->ajax()->register_action( 'get_field_mapper_tab', array(
			'callback'	=> array( $this, 'ajax_get_field_mapper_tab' ),
		) );
	}

	public function __call( $method_name, $args ) {
		switch ( $method_name ) {
			case 'admin_tabs':
			case 'ajax_get_field_mapper_tab':
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
		if ( ! $mapper->is_active_for_form( $form_id ) ) {
			return false;
		}

		$content = $this->render_mapper_element_tab_content( $mapper, $element_id, $form_id );

		return array(
			'slug'		=> $mapper->name,
			'title'		=> $mapper->admin_title,
			'content'	=> $content,
		);
	}

	protected function render_mapper_element_tab_content( $mapper, $element_id, $form_id ) {
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

		$output = '';

		if ( ! empty( $mapper->admin_description ) ) {
			$output .= '<p class="description">' . $mapper->admin_description . '</p>';
		}

		$output .= $element_type->admin_widget_settings_field( $mapper->name . '_mapping', $field, $element );

		return $output;
	}

	protected function ajax_get_field_mapper_tab( $data ) {
		if ( ! isset( $data['name'] ) ) {
			return new Torro_Error( 'missing_name', __( 'Missing mapper name.', 'torro-forms' ) );
		}

		$mapper = $this->get_registered( $data['name'] );
		if ( is_wp_error( $mapper ) ) {
			return new Torro_Error( 'invalid_name', __( 'Invalid mapper name.', 'torro-forms' ) );
		}

		if ( ! isset( $data['form_id'] ) ) {
			return new Torro_Error( 'missing_form_id', __( 'Missing form ID.', 'torro-forms' ) );
		}

		if ( ! isset( $data['element_ids'] ) ) {
			return new Torro_Error( 'missing_element_ids', __( 'Missing element IDs.', 'torro-forms' ) );
		}

		$tabs = array();
		foreach ( $data['element_ids'] as $element_id ) {
			$tabs[ $element_id ] = array(
				'slug'		=> $mapper->name,
				'title'		=> $mapper->admin_title,
				'content'	=> $this->render_mapper_element_tab_content( $mapper, $element_id, $form_id ),
			);
		}

		return $tabs;
	}
}
