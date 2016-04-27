<?php
/**
 * One Choice Form Element
 *
 * @author  awesome.ug, Author <support@awesome.ug>
 * @package TorroForms/Core/Elements
 * @version 1.0.0alpha1
 * @since   1.0.0
 * @license GPL 2
 *
 * Copyright 2015 awesome.ug (support@awesome.ug)
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License, version 2, as
 * published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class Torro_Form_Element_Onechoice extends Torro_Form_Element {
	/**
	 * Initializing.
	 *
	 * @since 1.0.0
	 */
	protected function init() {
		parent::init();

		$this->type = $this->name = 'onechoice';
		$this->title = __( 'One Choice', 'torro-forms' );
		$this->description = __( 'Add an Element which can be answered by selecting one of the given answers.', 'torro-forms' );
		$this->icon_url = torro()->get_asset_url( 'icon-onechoice', 'png' );

		$this->input_answers = true;
		$this->answer_array = false;
		$this->input_answers = true;
	}

	protected function get_input_html() {
		$star_required = '';
		$aria_required = '';
		if ( isset( $this->settings['required'] ) && 'yes' === $this->settings['required']->value ) {
			$star_required = ' <span class="required">*</span>';
			$aria_required = ' aria-required="true"';
		}

		$html  = '<fieldset' . $aria_required . ' role="radiogroup">';
		$html .= '<legend>' . esc_html( $this->label ) . $star_required . '</legend>';

		$i = 0;
		foreach ( $this->answers as $answer ) {
			$checked = '';
			if ( $this->response === $answer->answer ) {
				$checked = ' checked="checked"';
			}

			$html .= '<div class="torro_element_radio"><input id="' . $this->get_input_id() .'_' . $i . '" type="radio" aria-describedby="' . $this->get_input_id() . '_description ' . $this->get_input_id() . '_errors" name="' . $this->get_input_name() . '" value="' . esc_attr( $answer->answer ) . '" ' . $checked . '/> <label for="' . $this->get_input_id() .'_' . $i . '">' . esc_html( $answer->answer ) . '</label></div>';
			$i++;
		}

		if ( ! empty( $this->settings['description']->value ) ) {
			$html .= '<div id="' . $this->get_input_id() . '_description" class="element-description">';
			$html .= esc_html( $this->settings['description']->value );
			$html .= '</div>';
		}

		$html .= '</fieldset>';

		return $html;
	}

	public function settings_fields() {
		$this->settings_fields = array(
			'description'	=> array(
				'title'			=> __( 'Description', 'torro-forms' ),
				'type'			=> 'textarea',
				'description'	=> __( 'The description will be shown after the field.', 'torro-forms' ),
				'default'		=> ''
			),
			'required'		=> array(
				'title'			=> __( 'Required?', 'torro-forms' ),
				'type'			=> 'radio',
				'values'		=> array(
					'yes'			=> __( 'Yes', 'torro-forms' ),
					'no'			=> __( 'No', 'torro-forms' ),
				),
				'description'	=> __( 'Whether the user must select a value.', 'torro-forms' ),
				'default'		=> 'yes',
			),
		);
	}

	public function validate( $input ) {
		$input = stripslashes( $input );

		if ( isset( $this->settings['required'] ) && 'yes' === $this->settings['required']->value && empty( $input ) ) {
			return new Torro_Error( 'missing_value', __( 'Please select a value.', 'torro-forms' ) );
		}

		return $input;
	}
}

torro()->elements()->register( 'Torro_Form_Element_Onechoice' );
