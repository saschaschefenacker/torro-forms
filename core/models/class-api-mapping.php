<?php

abstract class Torro_API_Mapping {
	private $api_endpoint = null;
	private $api_fields = array();
	private $element_types = array();

	/**
	 * Torro_API_Mapping constructor.
	 *
	 * @param Torro_API $api
	 */
	public function __construct( $api ) {
		add_filter( 'torro_formbuilder_element_tabs', array( $this, 'add_tab' ), 10, 2 );
	}

	public function add_tab( $tabs, $element ) {

		return $tabs;
	}

	public function get_api_fields() {
		return $this->api_fields;
	}
}
