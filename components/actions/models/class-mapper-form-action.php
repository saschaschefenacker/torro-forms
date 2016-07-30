<?php
/**
 * Components: Torro_Mapper_Form_Action class
 *
 * @package TorroForms
 * @subpackage Components
 * @version 1.0.0-beta.6
 * @since 1.1.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

abstract class Torro_Mapper_Form_Action extends Torro_Form_Action {

	/**
	 * Name of the mapper for this action.
	 *
	 * Must be filled in all cases.
	 *
	 * @since 1.1.0
	 * @var string
	 */
	protected $mapper_name = '';

	/**
	 * Returns the mapped values for this mapper action for a specific response.
	 *
	 * This method should be called from the `handle()` method implementation. It returns
	 * the mapper fields and their values for the response so that it can be processed
	 * afterwards.
	 *
	 * @since 1.1.0
	 *
	 * @param int $response_id
	 * @return array|Torro_Error
	 */
	protected function get_mapped_values( $response_id ) {
		$mapper = torro()->field_mappers()->get_registered( $this->mapper_name );
		if ( is_wp_error( $mapper ) ) {
			return $mapper;
		}

		return $mapper->get_mapped_values( $response_id );
	}

	/**
	 * Renders the content of the option in form builder.
	 *
	 * When overriding this, make sure to still call the `render_enable_field()` method.
	 *
	 * @since 1.1.0
	 *
	 * @param int $form_id
	 * @return string
	 */
	public function option_content( $form_id ) {
		$html = '<div id="' . $this->name . '">';
		$html .= $this->render_enable_field( $form_id );
		$html .= '</div>';

		return $html;
	}

	/**
	 * Renders the field to enable/disable this mapper action.
	 *
	 * @since 1.1.0
	 *
	 * @param int $form_id
	 * @return string
	 */
	protected function render_enable_field( $form_id ) {
		$status = get_post_meta( $form_id, $this->mapper_name . '_mapping_active', true );

		$html = '<table class="form-table">';
		$html .= '<tr>';

		$html .= '<td>';
		$html .= '<label for="' . $this->mapper_name . '_mapping_active">' . __( 'Activation Status', 'torro-forms' ) . '</label>';
		$html .= '</td>';

		$html .= '<td>';
		$html .= '<select id="' . $this->mapper_name . '_mapping_active" name="' . $this->mapper_name . '_mapping_active" class="torro-field-mapper-toggle">';
		$html .= '<option value="yes"' . ( $status ? ' selected="selected"' : '' ) . '>' . __( 'Enabled', 'torro-forms' ) . '</option>';
		$html .= '<option value="no"' . ( $status ? '' : ' selected="selected"' ) . '>' . __( 'Disabled', 'torro-forms' ) . '</option>';
		$html .= '</select>';
		$html .= '</td>';

		$html .= '</tr>';
		$html .= '</table>';

		return $html;
	}

	/**
	 * Handles saving data from the option content.
	 *
	 * When overriding this function, make sure to include a call to `parent::save( $form_id )`.
	 * The function will return true/false according to whether the mapper action is enabled or disabled.
	 *
	 * @since 1.1.0
	 *
	 * @param $form_id
	 */
	public function save( $form_id ) {
		if ( isset( $_POST[ $this->mapper_name . '_mapping_active'] ) && 'yes' === wp_unslash( $_POST[ $this->mapper_name . '_mapping_active'] ) ) {
			update_post_meta( $form_id, $this->mapper_name . '_mapping_active', '1' );
			return true;
		} else {
			delete_post_meta( $form_id, $this->mapper_name . '_mapping_active' );
			return false;
		}
	}
}
