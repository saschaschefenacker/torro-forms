<?php

abstract class Torro_API {
	private $type = null;
	private $endpoint = null;
	private $fields = array();

	public function get_type() {
		return $this->type;
	}

	public function get_endpoint() {
		return $this->endpoint;
	}

	public function add_field( $field ) {
		$defaults = array(
			'title'  => __( 'Your field title' ),
			'name'   => '',
			'type'   => 'string',
			'length' => 0
		);

		$field        = wp_parse_args( $field, $defaults );
		$this->fields = array_merge( $this->fields, array( $field ) );

		return true;
	}

	public function get_fields() {
		return $this->fields;
	}

	private function get( $headers, $body ) {
		$request = array(
			'headers' => $headers,
			'body'    => $body
		);

		$request = wp_remote_get( $this->endpoint, $request );

		return $request;
	}

	private function post() {
	}

	private function set_type( $type ) {
		$this->type = $type;
	}

	private function set_endpoint( $url ) {
		$this->endpoint = $url;
	}

	private function request() {
		$result = wp_remote_request( $request );
	}
}
