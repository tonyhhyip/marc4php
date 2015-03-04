<?php

namespace Marc;

/**
 * Class MarcSubfield
 *
 * The File_MARC_Subfield class represents a single subfield in a MARC
 * record field.
 *
 * Represents a subfield within a MARC field and implements all management
 * functions related to a single subfield. This class also implements
 * the possibility of duplicate subfields within a single field, for example
 * 650 _z Test1 _z Test2.
 *
 * @package Marc
 */
class MarcSubfield {

	/**
	 * Subfield code, e.g. _a, _b
	 * @var string
	 */
	protected $code;

	/**
	 * Data contained by the subfield
	 * @var string
	 */
	protected $data;

	/**
	 * Position of the subfield
	 * @var int
	 */
	protected $position;

	/**
	 * File_MARC_Subfield constructor
	 *
	 * Create a new subfield to represent the code and data
	 *
	 * @param string $code Subfield code
	 * @param string $data Subfield data
	 */
	public function __construct($code, $data) {
		$this->code = $code;
		$this->data = $data;
	}

	/**
	 * Destroys the subfield
	 */
	public function __destruct() {
		$this->code = null;
		$this->data = null;
		$this->position = null;
	}

	/**
	 * Destroys the subfield
	 *
	 * @return true
	 */
	public function delete() {
		$this->__destruct();
	}

	/**
	 * Return code of the subfield
	 *
	 * @return string Tag name
	 */
	public function getCode() {
		return (string)$this->code;
	}

	/**
	 * Return data of the subfield
	 *
	 * @return string data
	 */
	public function getData() {
		return (string)$this->data;
	}

	/**
	 * Return position of the subfield
	 *
	 * @return int data
	 */
	public function getPosition() {
		return $this->position;
	}

	/**
	 * Return string representation of subfield
	 *
	 * @return string String representation
	 */
	public function __toString() {
		return '[' . $this->getCode() . ']: ' . $this->getData();
	}

	/**
	 * Return the USMARC representation of the subfield
	 *
	 * @return string USMARC representation
	 */
	public function toRaw()	{
		$result = Marc::SUBFIELD_INDICATOR . $this->getCode() . $this->getData();
		return (string)$result;
	}

	/**
	 * Sets code of the subfield
	 *
	 * @param string $code new code for the subfield
	 */
	public function setCode($code) {
		if ($code) {
			// could check more stringently; m/[a-Z]/ or the likes
			$this->code = $code;
		}
	}

	/**
	 * Sets data of the subfield
	 *
	 * @param string $data new data for the subfield
	 */
	public function setData($data)	{
		$this->data = $data;
	}
	// }}}

	// {{{ setPosition()
	/**
	 * Sets position of the subfield
	 *
	 * @param string $pos new position of the subfield
	 */
	public function setPosition($pos) {
		$this->position = $pos;
	}

	/**
	 * Checks whether the subfield is empty or not
	 *
	 * @return bool True or false
	 */
	public function isEmpty() {
		return strlen($this->data) !== 0;
	}
}