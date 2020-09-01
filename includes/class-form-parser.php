<?php
/**
 * Form Parser class
 *
 * @author Eric Sloan
 * @link https://github.com/dusthazard/form-parser
 */
class form_parser {

	public $DOMforms,$forms = array();

	private $DOM,$xpath;



	/**
	 * CONSTRUCT FUNCTION
	 *
	 * IF CODE INCLUDES A WEB FORM, AUTOMATICALLY PARSE IT
	 */



	function __construct( $optincode = '' ) {

		if ( $optincode !== '' ) {

			// load the optin form code

			$this->DOM = new DOMDocument();
			@$this->DOM->loadHTML( '' . $optincode );
			$this->xpath = new DOMXpath( $this->DOM );

			// extract the necessary elements from the code

			$this->DOMforms = $this->xpath->query( '//form' );

			// if there is a form element, parse it

			if ( $this->DOMforms->length > 0 ) {

				$this->parse_forms();

			}
		}

	}



	/**
	 * PARSE THE FORM
	 */



	private function parse_forms() {

		// loop through each form element

		foreach ( $this->DOMforms as $i => $form ) {

			// get the method and action

			$this->forms[ $i ]['method'] = $form->getAttribute( 'method' );
			$this->forms[ $i ]['action'] = $form->getAttribute( 'action' );
			$this->forms[ $i ]['target'] = $form->getAttribute( 'target' );

			// get the stylesheets

			$stylesheets = $this->xpath->query( '//link | //style' );

			$this->forms[ $i ]['stylesheets'] = $this->getHTML( $stylesheets );

			// get the scripts

			$scripts = $this->xpath->query( '//script' );

			$this->forms[ $i ]['scripts'] = $this->getHTML( $scripts );

			// get all of the form elements relative to the form being looped over

			$formElements = $this->xpath->query( './/input | .//select | .//textarea | .//label', $form );

			$this->forms[ $i ]['formElements'] = $this->getElements( $formElements );

		}

	}



	/**
	 * GET THE HTML OF AN ARRAY OF ELEMENTS
	 *
	 * @return array of elements
	 *
	 * USED FOR: SCRIPTS, LINKS, STYLES
	 */



	private function getHTML( $elements ) {

		$result = array();

		foreach ( $elements as $element ) {

			$result[] = $this->DOM->saveXml( $element );

		}

		return $result;

	}



	/**
	 * GET ELEMENT DETAILS FROM AN ARRAY OF INPUTS
	 *
	 * @return array of form elements
	 *
	 * USED FOR: ALL FORM ELEMENTS
	 */



	private function getElements( $elements ) {

		$result = array();

		foreach ( $elements as $i => $element ) {

			switch ( $element->tagName ) {

				case 'input':
				case 'select':
				case 'textarea':
					if ( $element->getAttribute( 'type' ) == 'submit' ) {
						break;
					}

					$arrayKey = ( $element->getAttribute( 'id' ) !== '' ) ? $element->getAttribute( 'id' ) : $i;

					$result[ $arrayKey ]['value']    = $element->getAttribute( 'value' );
					$result[ $arrayKey ]['class']    = $element->getAttribute( 'class' );
					$result[ $arrayKey ]['name']     = $element->getAttribute( 'name' );
					$result[ $arrayKey ]['required'] = $element->hasAttribute( 'required' ) ? 'required' : '';
					$result[ $arrayKey ]['type']     = ( $element->getAttribute( 'type' ) !== '' ) ? $element->getAttribute( 'type' ) : $element->tagName;

					if ( $element->tagName == 'select' ) {

						$options = $this->xpath->query( './/option', $element );

						foreach ( $options as $j => $option ) {

							$result[ $arrayKey ]['options'][ $j ] = [
								'value'  => $option->getAttribute( 'value' ),
								'option' => $option->nodeValue,
							];

						}
					} else {

							$result[ $arrayKey ]['text'] = $element->nodeValue;

					}

					break;

				case 'label':
					if ( $element->getAttribute( 'for' ) ) {
						$result[ $element->getAttribute( 'for' ) ]['label'] = $element->nodeValue;
					}

					break;

			}
		}

		return $result;

	}



	/**
	 * RENDER FORM ELEMENTS ON A PAGE
	 */



	public function render_elements( $args ) {

		if ( ! isset( $this->forms ) || count( $this->forms ) === 0 ) {
			return false;
		}

		$output = '';

		foreach ( $this->forms[0]['formElements'] as $inputID => $inputTag ) {

			// check for a hidden or removed element

			if ( isset( $args[ urlencode( $inputTag['name'] ) ] ) ) {

				if ( isset( $args[ urlencode( $inputTag['name'] ) ]['hide'] ) ) {
					$inputTag['type'] = 'hidden';
				}

				if ( isset( $args[ urlencode( $inputTag['name'] ) ]['remove'] ) ) {
					continue;
				}
			}

			// determine if required field or not

			$required = '';

			$required = ( isset( $inputTag['required'] ) && ( $inputTag['required'] === true || $inputTag['required'] == 'required' ) ) ? ' required' : '';

			// render the elements

			switch ( $inputTag['type'] ) {

				case 'submit':
					// do nothing
					break;
				case 'hidden':
					$output .= "<input type='hidden' value='" . $inputTag['value'] . "' name='" . $inputTag['name'] . "' id='" . $inputID . "'>";
					break;
				case 'select':
					if ( isset( $inputTag['options'] ) && count( $inputTag['options'] ) > 0 ) {

						$output .= "<select name='" . $inputTag['name'] . "' class='" . $inputTag['class'] . $required . "' id='" . $inputID . "'" . $required . '>';

						foreach ( $inputTag['options'] as $i => $option ) {

							if ( $i === 0 && $option['option'] == '' ) {
								$option['option'] = $inputTag['label'];
							}

							$output .= "<option value='" . $option['value'] . "'>" . $option['option'] . '</option>';

						}

						$output .= '</select>';

					}

					break;
				case 'textarea':
					$inputTag['label'] = isset( $inputTag['label'] ) ? $inputTag['label'] : '';
					$output           .= "<textarea placeholder='" . $inputTag['label'] . "' name='" . $inputTag['name'] . "' class='" . $inputTag['class'] . $required . "' id='" . $inputID . "'" . $required . '>' . $inputTag['value'] . '</textarea>';
					break;
				case 'radio':
				case 'checkbox':
					$output .= "<label><input type='" . $inputTag['type'] . "' value='" . $inputTag['value'] . "' name='" . $inputTag['name'] . "' class='" . $inputTag['class'] . $required . "' id='" . $inputID . "'" . $required . '>' . $inputTag['label'] . '</label>';
					break;
				default:
					if ( isset( $inputTag['label'] ) ) {
						$output .= "<input type='" . $inputTag['type'] . "' placeholder='" . $inputTag['label'] . "' value='" . $inputTag['value'] . "' name='" . $inputTag['name'] . "' class='" . $inputTag['class'] . $required . "' id='" . $inputID . "'" . $required . '>';
					} else {
						$output .= "<input type='" . $inputTag['type'] . "' placeholder='" . $inputTag['name'] . "' value='" . $inputTag['value'] . "' name='" . $inputTag['name'] . "' class='" . $inputTag['class'] . $required . "' id='" . $inputID . "'" . $required . '>';
					}
					break;

			}
		}

		return $output;

	}

}
