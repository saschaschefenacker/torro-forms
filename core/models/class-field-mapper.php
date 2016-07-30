<?php
/**
 * Core: Torro_Field_Mapper class
 *
 * @package TorroForms
 * @subpackage CoreModels
 * @version 1.0.0-beta.6
 * @since 1.1.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Torro Forms Field Mapper Class
 *
 * This class allows to define mappings between a set of fields and elements of a specific form.
 * The class takes care of the functionality while the Field Mappers Manager handles the UI.
 *
 * @since 1.1.0
 */
abstract class Torro_Field_Mapper extends Torro_Base {

	/**
	 * The title to show in the admin.
	 *
	 * @since 1.1.0
	 * @var string
	 */
	protected $admin_title = '';

	/**
	 * The description to show in the admin.
	 *
	 * @since 1.1.0
	 * @var string
	 */
	protected $admin_description = '';

	/**
	 * The fields this mapper requires.
	 *
	 * These should be declared in the `init` method.
	 *
	 * @since 1.1.0
	 * @var array
	 */
	protected $fields = array();

	/**
	 * Initializes the field mapper.
	 *
	 * @since 1.1.0
	 */
	protected function __construct() {
		parent::__construct();
		$this->validate_fields();
		add_filter( 'torro_response_status', array( $this, 'check_response' ), 10, 5 );
	}

	/**
	 * Returns the fields for this mapper.
	 *
	 * @since 1.1.0
	 *
	 * @return array
	 */
	public function get_fields() {
		return $this->fields;
	}

	/**
	 * Returns the mappings of a specific form for this mapper.
	 *
	 * @since 1.1.0
	 *
	 * @param int $form_id
	 * @return array|Torro_Error
	 */
	public function get_mappings( $form_id ) {
		$form = torro()->forms()->get( $form_id );
		if ( is_wp_error( $form ) ) {
			return $form;
		}

		if ( ! $this->is_active_for_form( $form_id ) ) {
			return new Torro_Error( 'mapper_not_enabled', sprintf( __( 'The mapper %1$s is not enabled for form %2$s.', 'torro-forms' ), $this->title, $form_id ) );
		}

		$mappings = $this->fields;
		foreach ( $form->elements as $element ) {
			$settings = $element->settings;
			if ( ! isset( $settings[ $this->name . '_mapping' ] ) ) {
				continue;
			}

			if ( ! isset( $mappings[ $settings[ $this->name . '_mapping' ]->value ] ) ) {
				continue;
			}

			$mappings[ $settings[ $this->name . '_mapping' ]->value ]['element'] = $element;
		}

		$validated_mappings = array();
		foreach ( $mappings as $slug => $field ) {
			if ( ! isset( $field['element'] ) ) {
				if ( $field['required'] ) {
					return new Torro_Error( 'missing_mapping', sprintf( __( 'The required field %s is missing a mapping.', 'torro-forms' ), $field['title'] ) );
				}
				continue;
			}
			$validated_mappings[ $slug ] = $field;
		}

		return $validated_mappings;
	}

	/**
	 * Returns the mapped values of a specific form submission for this mapper.
	 *
	 * @since 1.1.0
	 *
	 * @param int $result_id
	 * @return array|Torro_Error
	 */
	public function get_mapped_values( $result_id ) {
		$result = torro()->results()->get( $result_id );
		if ( is_wp_error( $result ) ) {
			return $result;
		}

		$mappings = $this->get_mappings( $result->form_id );
		if ( is_wp_error( $mappings ) ) {
			return $mappings;
		}

		$lookup = array();
		foreach ( $mappings as $slug => $mapping ) {
			$lookup[ $mapping['element']->id ] = $slug;
		}

		foreach ( $result->values as $value ) {
			if ( ! isset( $lookup[ $value->element_id ] ) ) {
				continue;
			}
			$slug = $lookup[ $value->element_id ];
			$mappings[ $slug ]['value'] = $this->validate_from_choices( $value->value, $mappings[ $slug ]['choices'], $mappings[ $slug ]['type'] );
		}

		foreach ( $mappings as $slug => $mapping ) {
			if ( ! isset( $mapping['value'] ) ) {
				if ( $mapping['required'] ) {
					return new Torro_Error( 'missing_value', sprintf( __( 'The required field %s is missing a value.', 'torro-forms' ), $field['title'] ) );
				}
				$mappings[ $slug ]['value'] = $mapping['default'];
			}
		}

		return $mappings;
	}

	/**
	 * Checks the response (on form submission).
	 *
	 * This ensures that all fields required for the mapper were filled properly.
	 *
	 * @since 1.1.0
	 *
	 * @param bool $status
	 * @param int $form_id
	 * @param int $container_id
	 * @param bool $is_submit
	 * @param Torro_Form_Controller_Cache $cache
	 * @return bool
	 */
	public function check_response( $status, $form_id, $container_id, $is_submit, $cache ) {
		if ( ! $is_submit ) {
			return $status;
		}

		if ( ! $this->is_active_for_form( $form_id ) ) {
			return $status;
		}

		$result = $this->validate_response( $cache->get_response(), $form_id );
		if ( is_wp_error( $result ) ) {
			$cache->add_global_error( $result );
			return false;
		}

		return $status;
	}

	/**
	 * Checks whether this mapper is active for a specific form.
	 *
	 * @since 1.1.0
	 *
	 * @param int $form_id
	 * @return bool
	 */
	public function is_active_for_form( $form_id ) {
		return (bool) get_post_meta( $form_id, $this->name . '_mapping_active', true );
	}

	/**
	 * Validates a response with this mapper.
	 *
	 * @since 1.1.0
	 *
	 * @param array $response
	 * @param int $form_id
	 * @return bool|Torro_Error
	 */
	public function validate_response( $response, $form_id ) {
		//TODO
	}

	/**
	 * Validates the fields of this mapper.
	 *
	 * This method is invoked in the constructor.
	 *
	 * @since 1.1.0
	 */
	protected function validate_fields() {
		$defaults = array(
			'type'				=> 'string',
			'title'				=> '',
			'description'		=> '',
			'required'			=> false,
			'choices'			=> false,
		);

		foreach ( $this->fields as $slug => $field ) {
			$this->fields[ $slug ] = wp_parse_args( $field, $defaults );
			if ( isset( $this->fields[ $slug ]['element'] ) ) {
				unset( $this->fields[ $slug ]['element'] );
			}
			if ( isset( $this->fields[ $slug ]['value'] ) ) {
				unset( $this->fields[ $slug ]['value'] );
			}
			if ( ! isset( $this->fields[ $slug ]['default'] ) ) {
				$this->fields[ $slug ]['default'] = $this->get_default_from_choices( $this->fields[ $slug ]['choices'], $this->fields[ $slug ]['type'] );
			}
		}
	}

	/**
	 * Validates a result value.
	 *
	 * @since 1.1.0
	 *
	 * @param mixed $value
	 * @param mixed $choices
	 * @param string $type
	 * @return mixed|Torro_Error
	 */
	protected function validate_from_choices( $value, $choices, $type ) {
		switch ( $type ) {
			case 'float':
				$value = floatval( $value );
				break;
			case 'int':
				$value = intval( $value );
				break;
			case 'bool':
				$value = ( ! $value || in_array( $value, array( 'no', 'false', 'NO', 'FALSE' ) ) ) ? false : true;
				break;
			case 'string':
			default:
				$value = strval( $value );
		}

		if ( is_array( $choices ) && 0 < count( $choices ) ) {
			if ( isset( $choices[0] ) ) {
				if ( ! in_array( $value, $choices ) ) {
					return new Torro_Error( 'invalid_value', sprintf( __( 'The value %s does not match any of the given choices.', 'torro-forms' ), $value ) );
				}
			} else {
				if ( ! isset( $choices[ $value ] ) ) {
					$key = array_search( $value, $choices );
					if ( false === $key ) {
						return new Torro_Error( 'invalid_value', sprintf( __( 'The value %s does not match any of the given choices.', 'torro-forms' ), $value ) );
					}
					$value = $key;
				}
			}
		} elseif ( is_string( $choices ) && false !== strpos( $choices, '-' ) ) {
			list( $min, $max ) = array_map( 'trim', explode( '-', $choices ) );
			if ( 'float' === $type ) {
				$min = floatval( $min );
				$max = floatval( $max );
			} else {
				$min = intval( $min );
				$max = intval( $max );
			}

			if ( $value > $max ) {
				return new Torro_Error( 'invalid_value', sprintf( __( 'The value %s is too high.', 'torro-forms' ), number_format_i18n( $value ) ) );
			} elseif ( $value < $min ) {
				return new Torro_Error( 'invalid_value', sprintf( __( 'The value %s is too low.', 'torro-forms' ), number_format_i18n( $value ) ) );
			}
		}

		return $value;
	}

	/**
	 * Returns the default value of a field of this mapper.
	 *
	 * This method is used if no default is provided.
	 *
	 * @since 1.1.0
	 *
	 * @param mixed $choices
	 * @param string $type
	 * @return mixed
	 */
	protected function get_default_from_choices( $choices, $type ) {
		if ( is_array( $choices ) && 0 < count( $choices ) ) {
			if ( isset( $choices[0] ) ) {
				return $choices[0];
			}
			return array_keys( $choices)[0];
		}

		if ( is_string( $choices ) && false !== strpos( $choices, '-' ) ) {
			list( $min, $max ) = array_map( 'trim', explode( '-', $choices ) );
			if ( 'float' === $type ) {
				return floatval( $min );
			}
			return intval( $min );
		}

		switch ( $type ) {
			case 'float':
				return 0.0;
			case 'int':
				return 0;
			case 'bool':
				return false;
			case 'string':
			default:
				return '';
		}
	}
}
