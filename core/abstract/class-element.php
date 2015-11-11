<?php
/**
 * Elements abstraction class
 *
 * @author  awesome.ug, Author <support@awesome.ug>
 * @package AwesomeForms/Core
 * @version 1.0.0
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

if( !defined( 'ABSPATH' ) )
{
	exit;
}

abstract class AF_Form_Element
{

	/**
	 * ID of instanced Element
	 *
	 * @since 1.0.0
	 */
	var $id = NULL;

	/**
	 * Contains the Form ID of the Element
	 *
	 * @since 1.0.0
	 */
	var $form_id;

	/**
	 * Name of the Element
	 *
	 * @since 1.0.0
	 */
	var $name;

	/**
	 * Title of Element which will be shown in admin
	 *
	 * @since 1.0.0
	 */
	var $title;

	/**
	 * Description of the Element
	 *
	 * @since 1.0.0
	 */
	var $description;

	/**
	 * Icon URl of the Element
	 *
	 * @since 1.0.0
	 */
	var $icon_url;

	/**
	 * Element Label
	 *
	 * @since 1.0.0
	 */
	var $label;

	/**
	 * Sort number where to display the Element
	 *
	 * @since 1.0.0
	 */
	var $sort = 0;

	/**
	 * Does this element have a content tab
	 *
	 * @since 1.0.0
	 */
	var $has_content = TRUE;

	/**
	 * If value is true, Awesome Forms will try to create charts from results
	 *
	 * @todo  is_analyzable: Is this a self spelling name?
	 * @since 1.0.0
	 */
	var $is_analyzable = FALSE;

	/**
	 * Does this elements has own answers? For example on multiple choice or one choice has answers.
	 *
	 * @todo  has_answers: Is this a self spelling name?
	 * @since 1.0.0
	 */
	var $has_answers = FALSE;

	/**
	 * Only for Form splitter Element!
	 *
	 * @var bool
	 */
	var $splits_form = FALSE;

	/**
	 * Sections for answers
	 *
	 * @since 1.0.0
	 */
	var $sections = array();

	/**
	 * Element answers
	 *
	 * @since 1.0.0
	 */
	var $answers = array();

	/**
	 * Contains users response of an Element
	 *
	 * @since 1.0.0
	 */
	var $response;

	/**
	 * Contains Admin tabs
	 *
	 * @var array
	 * @since 1.0.0
	 */
	private $admin_tabs = array();

	/**
	 * The settings fields
	 *
	 * @since 1.0.0
	 */
	protected $settings_fields = array();

	/**
	 * Contains all settings of the element
	 *
	 * @var array
	 * @since 1.0.0
	 */
	var $settings = array();

	/**
	 * Has element moltiple Answers?
	 *
	 * @var bool
	 * @since 1.0.0
	 */
	var $answer_is_multiple = FALSE;

	/**
	 * Is Element initialized
	 *
	 * @var bool
	 * @since 1.0.0
	 */
	var $initialized = FALSE;

	/**
	 * Constructor
	 *
	 * @param int $id ID of the element
	 *
	 * @since 1.0.0
	 */
	public function __construct( $id = NULL )
	{
		$this->init();

		if( NULL != $id && '' != $id )
		{
			$this->populate( $id );
		}

		$this->settings_fields();
	}

	/**
	 * Base Element Function
	 * @return mixed
	 */
	abstract function init();

	/**
	 * Populating element object with data
	 *
	 * @param int $id Element id
	 *
	 * @since 1.0.0
	 */
	private function populate( $id )
	{
		global $wpdb, $af_global;

		$this->label = '';
		$this->answers = array();

		$sql = $wpdb->prepare( "SELECT * FROM {$af_global->tables->elements} WHERE id = %s", $id );
		$row = $wpdb->get_row( $sql );

		$this->id = $id;
		$this->set_label( $row->label );
		$this->form_id = $row->form_id;

		$this->sort = $row->sort;

		$sql = $wpdb->prepare( "SELECT * FROM {$af_global->tables->element_answers} WHERE element_id = %s ORDER BY sort ASC", $id );
		$results = $wpdb->get_results( $sql );

		if( is_array( $results ) ):
			foreach( $results AS $result ):
				$this->add_answer( $result->answer, $result->sort, $result->id, $result->section );
			endforeach;
		endif;

		$sql = $wpdb->prepare( "SELECT * FROM {$af_global->tables->settings} WHERE element_id = %s", $id );
		$results = $wpdb->get_results( $sql );

		if( is_array( $results ) ):
			foreach( $results AS $result ):
				$this->add_setting( $result->name, $result->value );
			endforeach;
		endif;
	}

	/**
	 * Setting Label for Element
	 *
	 * @param string $label
	 *
	 * @since 1.0.0
	 * @return boolean
	 */
	private function set_label( $label, $order = NULL )
	{
		if( '' == $label )
		{
			return FALSE;
		}

		if( NULL != $order )
		{
			$this->sort = $order;
		}

		$this->label = $label;

		return TRUE;
	}

	/**
	 * Adding answer to object data
	 *
	 * @param string $text    Answer text
	 * @param int    $sort    Sort number
	 * @param int    $id      Answer ID from DB
	 * @param string $section Section of answer
	 *
	 * @return boolean $is_added TRUE if answer was added, False if not
	 * @since 1.0.0
	 */
	private function add_answer( $text, $sort = FALSE, $id = NULL, $section = NULL )
	{
		if( '' == $text )
		{
			return FALSE;
		}

		$this->answers[ $id ] = array( 'id' => $id, 'text' => $text, 'sort' => $sort, 'section' => $section );

		return TRUE;
	}

	/**
	 * Add setting to object data
	 *
	 * @param string $name  Name of setting
	 * @param string $value Value of setting
	 *
	 * @since 1.0.0
	 */
	private function add_setting( $name, $value )
	{

		$this->settings[ $name ] = $value;
	}

	/**
	 * Settings fields
	 */
	public function settings_fields()
	{
	}

	/**
	 * Function to register element in Awesome Forms
	 *
	 * After registerung was successfull the new element will be shown in the elements list.
	 *
	 * @return boolean $is_registered Returns TRUE if registering was succesfull, FALSE if not
	 * @since 1.0.0
	 */
	public function _register()
	{
		global $af_global;

		if( TRUE == $this->initialized )
		{
			return FALSE;
		}

		if( !is_object( $af_global ) )
		{
			return FALSE;
		}

		if( '' == $this->name )
		{
			$this->name = get_class( $this );
		}

		if( '' == $this->title )
		{
			$this->title = ucwords( get_class( $this ) );
		}

		if( '' == $this->description )
		{
			$this->description = esc_attr__( 'This is a Awesome Forms Element.', 'af-locale' );
		}

		if( array_key_exists( $this->name, $af_global->element_types ) )
		{
			return FALSE;
		}

		if( !is_array( $af_global->element_types ) )
		{
			$af_global->element_types = array();
		}

		$this->initialized = TRUE;

		return $af_global->add_form_element( $this->name, $this );
	}

	/**
	 * Validate user input - Have to be overwritten by child classes if element needs validation
	 *
	 * @return bool
	 * @since 1.0.0
	 */
	public function validate( $input )
	{

		return TRUE;
	}

	/**
	 * Drawing Element on frontend
	 *
	 * @return string $html Element HTML
	 * @since 1.0.0
	 */
	public function draw()
	{
		global $af_response_errors;

		$errors = '';
		if( is_array( $af_response_errors ) && array_key_exists( $this->id, $af_response_errors ) )
		{
			$errors = $af_response_errors[ $this->id ];
		}

		$html = '';

		$element_classes = array( 'af-element', 'af-element-' . $this->id );
		$element_classes = apply_filters( 'af_element_classes', $element_classes, $this );

		$html .= '<div class="' . implode( ' ', $element_classes ) . '">';

		ob_start();
		do_action( 'af_form_element_start', $this->id );
		$html .= ob_get_clean();

		// Echo Errors
		if( is_array( $errors ) && count( $errors ) > 0 ):
			$html .= '<div class="af-element-error">';
			$html .= '<div class="af-element-error-message">';
			$html .= '<ul class="af-error-messages">';
			foreach( $errors AS $error ):
				$html .= '<li>' . $error . '</li>';
			endforeach;
			$html = apply_filters( 'draw_element_errors', $html, $this );
			$html .= '</ul></div>';
		endif;

		// Fetching user response data
		$this->get_response();

		if( 0 == count( $this->answers ) && $this->has_answers == TRUE )
		{
			$html .= '<p>' . esc_attr__( 'You don´t entered any answers. Please add some to display answers here.', 'af-locale' ) . '</p>';
		}
		else
		{
			$html .= $this->input_html();
		}

		// End Echo Errors
		if( is_array( $errors ) && count( $errors ) > 0 ):
			$html .= '</div>';
		endif;

		ob_start();
		do_action( 'af_form_element_end', $this->id );
		$html .= ob_get_clean();

		$html .= '</div>';

		return $html;
	}

	/**
	 * Getting element data from Session
	 *
	 * @return array $response The post response
	 * @since 1.0.0
	 */
	private function get_response()
	{
		global $ar_form_id;

		$this->response = FALSE;

		// Getting value/s
		if( !empty( $ar_form_id ) ):
			if( isset( $_SESSION[ 'af_response' ] ) ):
				if( isset( $_SESSION[ 'af_response' ][ $ar_form_id ] ) ):
					if( isset( $_SESSION[ 'af_response' ][ $ar_form_id ][ $this->id ] ) ):
						$this->response = $_SESSION[ 'af_response' ][ $ar_form_id ][ $this->id ];
					endif;
				endif;
			endif;
		endif;

		return $this->response;
	}

	/**
	 * Contains element HTML on frontend - Have to be overwritten by child classes
	 *
	 * @return string $html Element frontend HTML
	 */
	public function input_html()
	{
		return '<p>' . esc_attr__( 'No HTML for Element given. Please check element sourcecode.', 'af-locale' ) . '</p>';
	}

	/**
	 * Draws element box in Admin
	 *
	 * @return string $html The admin element HTML code
	 * @since 1.0.0
	 */
	public function draw_admin()
	{
		$id_name = $this->admin_get_widget_id();

		/**
		 * Widget
		 */
		if( NULL == $this->id )
		{
			$html = '<div id="' . $id_name . '" data-element-type="' . $this->name . '" class="formelement formelement-' . $this->name . '">';
		}
		else
		{
			$html = '<div id="' . $id_name . '" data-element-type="' . $this->name . '" class="widget formelement formelement-' . $this->name . '">';
		}

		/**
		 * Widget head
		 */
		$title = empty( $this->label ) ? $this->title : $this->label;
		$title = strip_tags( $title );

		if( strlen( $title ) > 120 )
		{
			$title = substr( $title, 0, 120 ) . '...';
		}

		$html .= '<div class="widget-top">';
		$html .= '<div class="widget-title-action"><a class="widget-action hide-if-no-js"></a></div>';
		$html .= '<div class="widget-title">';

		if( '' != $this->icon_url )
		{
			$html .= '<img class="form-elements-widget-icon" src ="' . $this->icon_url . '" />';
		}
		$html .= '<h4>' . $title . '</h4>';

		$html .= '</div>';
		$html .= '</div>';

		/**
		 * Widget inside
		 */
		$widget_id = $this->admin_get_widget_id();
		$jquery_widget_id = str_replace( '#', '', $widget_id );

		$html .= '<div class="widget-inside">';
		$html .= '<div class="widget-content">';

		/**
		 * Tab Navi
		 */
		$this->admin_add_tab( esc_attr__( 'Content', 'af-locale' ), $this->admin_widget_content_tab() );

		$settings = $this->admin_widget_settings_tab();
		if( FALSE !== $settings )
		{
			$this->admin_add_tab(  esc_attr__( 'Settings', 'af-locale' ), $settings );
		}

		$admin_tabs = apply_filters( 'af_formbuilder_element_tabs', $this->admin_tabs );

		if( count( $admin_tabs ) > 1 )
		{
			$html .= '<div class="form_element_tabs">';
			$html .= '<ul class="tabs">';

			foreach( $admin_tabs AS $key => $tab )
			{
				$html .= '<li><a href="#tab_' . $jquery_widget_id . '_' . $key .  '">' . $tab[ 'title' ] . '</a></li>';
			}

			$html .= '</ul>';
		}

		$html .= '<div class="clear"></div>'; // Underline of tabs

		/**
		 * Content of Tabs
		 */
		if( count( $admin_tabs ) > 1 )
		{
			foreach( $admin_tabs AS $key => $tab )
			{
				$html .= '<div id="tab_' . $jquery_widget_id . '_' . $key .   '">';
				$html .= $tab[ 'content' ];
				$html .= '</div>';
			}

			$html .= '</div>';
		}
		else
		{
			foreach( $admin_tabs AS $key => $tab )
			{
				$html.= $tab[ 'content' ];
			}
		}

		// Adding further content
		ob_start();
		do_action( 'af_element_admin_tabs_content', $this );
		$html .= ob_get_clean();

		$html .= $this->admin_widget_action_buttons();

		// Adding content at the bottom
		ob_start();
		do_action( 'af_element_admin_tabs_bottom', $this );
		$html .= ob_get_clean();

		$html .= '</div>';
		$html .= '</div>';

		$html .= $this->admin_widget_hidden_fields();

		$html .= '</div>';

		return $html;
	}

	/**
	 * Adds Tab for Element
	 *
	 * @param $title
	 * @param $content
	 */
	public function admin_add_tab( $title, $content )
	{
		$this->admin_tabs[] = array(
			'title' => $title,
			'content' => $content
		);
	}

	/**
	 * Returns the widget id which will be used in HTML
	 *
	 * @return string $widget_id The widget id
	 * @since 1.0.0
	 */
	protected function admin_get_widget_id()
	{
		// Getting Widget ID
		if( NULL == $this->id )
		{
			// New Element
			$widget_id = 'widget_formelement_XXnrXX';
		}
		else
		{
			// Existing Element
			$widget_id = 'widget_formelement_' . $this->id;
		}

		return $widget_id;
	}

	/**
	 * Content of the content tab
	 *
	 * @since 1.0.0
	 */
	private function admin_widget_content_tab()
	{
		$widget_id = $this->admin_get_widget_id();
		$content_html = $this->admin_content_html();

		if( FALSE === $content_html )
		{
			// Label
			$html = '<label for="elements[' . $widget_id . '][label]">' . __( 'Label ', 'af-locale' ) . '</label><input type="text" name="elements[' . $widget_id . '][label]" value="' . $this->label . '" class="form-label" />';

			// Answers
			if( $this->has_answers ):

				// Answers have sections
				if( property_exists( $this, 'sections' ) && is_array( $this->sections ) && count( $this->sections ) > 0 ):
					foreach( $this->sections as $section_key => $section_name ):
						$html .= '<div class="element-section" id="section_' . $section_key . '">';
						$html .= '<p>' . $section_name . '</p>';
						$html .= $this->admin_widget_content_answers( $section_key );
						$html .= '<input type="hidden" name="section_key" value="' . $section_key . '" />';
						$html .= '</div>';
					endforeach;
				// Answers without sections
				else:
					$html .= '<p>' . esc_attr__( 'Answer/s:', 'af-locale' ) . '</p>';
					$html .= $this->admin_widget_content_answers();
				endif;

			endif;

			$html .= '<div class="clear"></div>';
		}
		else
		{
			$html = $content_html;
		}

		return $html;
	}

	/**
	 * Overwriting Admin Content HTML for totally free editing Element
	 */
	public function admin_content_html()
	{
		return FALSE;
	}

	/**
	 * Content of the answers under the form element
	 *
	 * @param string $section Name of the section
	 *
	 * @return string $html The answers HTML
	 * @since 1.0.0
	 */
	private function admin_widget_content_answers( $section = NULL )
	{
		$widget_id = $this->admin_get_widget_id();

		$html = '';

		if( is_array( $this->answers ) ):

			$html .= '<div class="answers">';

			foreach( $this->answers AS $answer ):

				// If there is a section
				if( NULL != $section )
				{
					if( $answer[ 'section' ] != $section ) // Continue if answer is not of the section
					{
						continue;
					}
				}

				$html .= '<div class="answer" id="answer_' . $answer[ 'id' ] . '">';
				$html .= '<p><input type="text" name="elements[' . $widget_id . '][answers][id_' . $answer[ 'id' ] . '][answer]" value="' . $answer[ 'text' ] . '" class="element-answer" /></p>';
				$html .= '<input type="button" value="' . esc_attr__( 'Delete', 'af-locale' ) . '" class="delete_answer button answer_action">';
				$html .= '<input type="hidden" name="elements[' . $widget_id . '][answers][id_' . $answer[ 'id' ] . '][id]" value="' . $answer[ 'id' ] . '" />';
				$html .= '<input type="hidden" name="elements[' . $widget_id . '][answers][id_' . $answer[ 'id' ] . '][sort]" value="' . $answer[ 'sort' ] . '" />';

				if( NULL != $section )
				{
					$html .= '<input type="hidden" name="elements[' . $widget_id . '][answers][id_' . $answer[ 'id' ] . '][section]" value="' . $section . '" />';
				}
				$html .= '</div>';

			endforeach;

			$html .= '</div><div class="clear"></div>';

		else:
			if( $this->has_answers ):

				$param_arr[] = $this->create_answer_syntax;
				$temp_answer_id = 'id_' . time() * rand();

				$html .= '<div class="answers">';
				$html .= '<div class="answer" id="answer_' . $temp_answer_id . '">';
				$html .= '<p><input type="text" name="elements[' . $widget_id . '][answers][' . $temp_answer_id . '][answer]" value="" class="element-answer" /></p>';
				$html .= ' <input type="button" value="' . esc_attr__( 'Delete', 'af-locale' ) . '" class="delete_answer button answer_action">';
				$html .= '<input type="hidden" name="elements[' . $widget_id . '][answers][' . $temp_answer_id . '][id]" value="" />';
				$html .= '<input type="hidden" name="elements[' . $widget_id . '][answers][' . $temp_answer_id . '][sort]" value="0" />';
				if( NULL != $section )
				{
					$html .= '<input type="hidden" name="elements[' . $widget_id . '][answers][' . $temp_answer_id . '][section]" value="' . $section . '" />';
				}

				$html .= '</div>';
				$html .= '</div><div class="clear"></div>';

			endif;

		endif;

		$html .= '<a class="add-answer" rel="' . $widget_id . '">+ ' . esc_attr__( 'Add Answer', 'af-locale' ) . ' </a>';

		return $html;
	}

	/**
	 * Content of the settings tab
	 *
	 * @return string $html The settings tab HTML
	 * @since 1.0.0
	 */
	private function admin_widget_settings_tab()
	{
		$html = '';

		// Running each setting field
		if( is_array( $this->settings_fields ) && count( $this->settings_fields ) > 0 )
		{
			foreach( $this->settings_fields AS $name => $field )
			{
				$html .= $this->admin_widget_settings_field( $name, $field );
			}

			return $html;
		}

		return FALSE;
	}

	/**
	 * Creating a settings field
	 *
	 * @param string $name  Internal name of the field
	 * @param array  $field Field settings
	 *
	 * @return string $html The field HTML
	 * @since 1.0.0
	 */
	private function admin_widget_settings_field( $name, $field )
	{
		// @todo Handle with class-settingsform.php
		$widget_id = $this->admin_get_widget_id();
		$value = '';

		if( array_key_exists( $name, $this->settings ) )
		{
			$value = $this->settings[ $name ];
		}

		if( '' == $value )
		{
			$value = $field[ 'default' ];
		}

		$name = 'elements[' . $widget_id . '][settings][' . $name . ']';

		$input = '';
		switch ( $field[ 'type' ] )
		{
			case 'text':

				$input = '<input type="text" name="' . $name . '" value="' . $value . '" />';
				break;

			case 'textarea':

				$input = '<textarea name="' . $name . '">' . $value . '</textarea>';
				break;


			case 'wp_editor':
				$settings = array(
			        'textarea_name' => $name
			    );
			    ob_start();
			    wp_editor( $value, 'af_wp_editor_' . substr( md5( time() * rand() ), 0, 7 ) . '_tinymce', $settings );
			    $input = ob_get_clean();

			    break;

			case 'radio':

				$input = '';

				foreach( $field[ 'values' ] AS $field_key => $field_value ):
					$checked = '';

					if( $value == $field_key )
					{
						$checked = ' checked="checked"';
					}

					$input .= '<span class="af-form-fieldset-input-radio"><input type="radio" name="' . $name . '" value="' . $field_key . '"' . $checked . ' /> ' . $field_value . '</span>';
				endforeach;

				break;
		}

		$html = '<div class="af-form-fieldset">';

		$html .= '<div class="af-form-fieldset-title">';
		$html .= '<label for="' . $name . '">' . $field[ 'title' ] . '</label>';
		$html .= '</div>';

		$html .= '<div class="af-form-fieldset-input">';
		$html .= $input . '<br />';
		$html .= '<small>' . $field[ 'description' ] . '</small>';
		$html .= '</div>';

		$html .= '<div class="clear"></div>';

		$html .= '</div>';

		return $html;
	}

	private function admin_widget_action_buttons()
	{
		// Adding action Buttons
		$bottom_buttons = apply_filters( 'af_element_bottom_actions', array(
			'delete_form_element' => array(
				'text'    => esc_attr__( 'Delete element', 'af-locale' ),
				'classes' => 'delete_form_element'
			)
		) );

		$html = '<div class="form-element-buttom">';
		$html .= '<ul>';
		foreach( $bottom_buttons AS $button ):
			$html .= '<li><a class="' . $button[ 'classes' ] . ' form-element-bottom-action button">' . $button[ 'text' ] . '</a></li>';
		endforeach;
		$html .= '</ul>';
		$html .= '</div>';

		return $html;
	}

	private function admin_widget_hidden_fields()
	{
		$widget_id = $this->admin_get_widget_id();

		// Adding hidden Values for element
		$html = '<input type="hidden" name="elements[' . $widget_id . '][id]" value="' . $this->id . '" />';
		$html .= '<input type="hidden" name="elements[' . $widget_id . '][sort]" value="' . $this->sort . '" />';
		$html .= '<input type="hidden" name="elements[' . $widget_id . '][type]" value="' . $this->name . '" />';
		$html .= '<input type="hidden" name="elements[' . $widget_id . '][has_answers]" value="' . ( $this->has_answers ? 'yes' : 'no' ) . '" />';
		$html .= '<input type="hidden" name="elements[' . $widget_id . '][sections]" value="' . ( property_exists( $this, 'sections' ) && is_array( $this->sections ) && count( $this->sections ) > 0 ? 'yes' : 'no' ) . '" />';

		return $html;
	}

	/**
	 * Returns the name of an input element
	 *
	 * @return string $input_name The name of the input
	 * @since 1.0.0
	 */
	public function get_input_name()
	{
		$input_name = 'af_response[' . $this->id . ']';

		return $input_name;
	}

	/**
	 * Returns the name of an input element
	 *
	 * @return string $input_name The name of the input
	 * @since 1.0.0
	 */
	public function get_selector_input_name()
	{
		$input_name = 'af_response\\\[' . $this->id . '\\\]';

		return $input_name;
	}

	/**
	 * Get all saved results of an element
	 *
	 * @return mixed $responses The results as array or FALSE if failed to get responses
	 * @since 1.0.0
	 */
	public function get_results()
	{

		global $wpdb, $af_global;

		$sql = $wpdb->prepare( "SELECT * FROM {$af_global->tables->results} AS r, {$af_global->tables->result_answers} AS a WHERE r.id=a.result_id AND a.element_id=%d", $this->id );
		$responses = $wpdb->get_results( $sql );

		$result_answers = array();
		$result_answers[ 'label' ] = $this->label;
		$result_answers[ 'sections' ] = FALSE;
		$result_answers[ 'array' ] = $this->answer_is_multiple;

		if( is_array( $this->answers ) && count( $this->answers ) > 0 ):
			// If element has predefined answers
			foreach( $this->answers AS $answer_id => $answer ):
				$value = FALSE;
				if( $this->answer_is_multiple ):
					foreach( $responses AS $response ):
						if( $answer[ 'text' ] == $response->value ):
							$result_answers[ 'responses' ][ $response->respond_id ][ $answer[ 'text' ] ] = esc_attr__( 'Yes' );
						elseif( !isset( $result_answers[ 'responses' ][ $response->respond_id ][ $answer[ 'text' ] ] ) ):
							$result_answers[ 'responses' ][ $response->respond_id ][ $answer[ 'text' ] ] = esc_attr__( 'No' );
						endif;
					endforeach;
				else:
					foreach( $responses AS $response ):
						if( $answer[ 'text' ] == $response->value ):
							$result_answers[ 'responses' ][ $response->respond_id ] = $response->value;
						endif;
					endforeach;
				endif;

			endforeach;
		else:
			// If element has no predefined answers
			if( is_array( $responses ) && count( $responses ) > 0 ):
				foreach( $responses AS $response ):
					$result_answers[ 'responses' ][ $response->respond_id ] = $response->value;
				endforeach;
			endif;
		endif;

		if( is_array( $result_answers ) && count( $result_answers ) > 0 )
		{
			return $result_answers;
		}
		else
		{
			return FALSE;
		}
	}
}

/**
 * Register a new Group Extension.
 *
 * @param $element_type_class name of the element type class.
 *
 * @return bool|null Returns false on failure, otherwise null.
 */
function af_register_form_element( $element_type_class )
{
	if( class_exists( $element_type_class ) )
	{
		$element_type = new $element_type_class();

		return $element_type->_register();
	}

	return FALSE;
}

/**
 * Gets an element object
 *
 * @param int    $element_id
 * @param string $type
 *
 * @return object|bool
 * @since 1.0.0
 */
function af_get_element( $element_id, $type = '' )
{
	global $wpdb, $af_global;

	if( '' == $type )
	{
		$sql = $wpdb->prepare( "SELECT type FROM {$af_global->tables->elements} WHERE id = %d ORDER BY sort ASC", $element_id );
		$type = $wpdb->get_var( $sql );
	}

	if( class_exists( 'AF_Form_Element_' . $type ) )
	{
		$class = 'AF_Form_Element_' . $type;

		return new $class( $element_id );
	}

	return FALSE;
}
