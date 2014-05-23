<?php

namespace App\Libraries;

/**
 * Field value must match CSRF Token value
 *
 * @package Phmisk
 **/
class CsrfRule implements \HybridLogic\Validation\Rule {


	/**
	 * Validate this Rule
	 *
	 * @param string Field name
	 * @param string Field value
	 * @param Validator Validator object
	 * @return bool True if rule passes
	 **/
	public function validate($field, $value, $validator) {
		
		// Run CSRF check, on POST data, in boolea mode, with a validity of 2 hours, in one-time mode.
		return ( \NoCSRF::check( 'csrf_token', $_POST, FALSE, 60*60*2, false ) );
		
	} // end func: validate



	/**
	 * Return error message for this Rule
	 *
	 * @param string Field name
	 * @param string Field value
	 * @param Validator Validator object
	 * @return string Error message
	 **/
	public function get_error_message($field, $value, $validator) {
		return $validator->get_label($field) . ' is not correct';
	} // end func: get_error_message


} // end class: CsrfRule
