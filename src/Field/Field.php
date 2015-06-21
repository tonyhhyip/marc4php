<?php

namespace Marc\Field;

use Marc\MarcList;

class Field extends MarcList {

	/**
	 * The tag name of the Field
	 * @var string
	 */
	protected $tag;

	/**
	 * constructor
	 *
	 * Create a new {@link MarcField} object from passed arguments. We
	 * define placeholders for the arguments required by child classes.
	 *
	 * @param string $tag       tag
	 * @param string $subfields placeholder for subfields or control data
	 * @param string $ind1      placeholder for first indicator
	 * @param string $ind2      placeholder for second indicator
	 */
	public function __construct($tag, $subfields = null, $ind1 = null, $ind2 = null) {
		$this->tag = $tag;

		// Check if valid tag
		if (!preg_match("/^[0-9A-Za-z]{3}$/", $tag)) {
			$errorMessage = MarcException::formatError(MarcException::ERROR_INVALID_TAG, array("tag" => $tag));
			throw new MarcException($errorMessage, MarcException::ERROR_INVALID_TAG);
		}

	}

	/**
	 * Destructor.
	 */
	public function __destruct() {
		$this->tag = null;
	}

	/**
	 * Returns the tag for this {@link MarcField} object
	 *
	 * @return string returns the tag number of the field
	 */
	public function getTag() {
		return $this->tag->toString();
	}

	/**
	 * Sets the tag for this {@link MarcField} object
	 *
	 * @param string $tag new value for the tag
	 *
	 * @return string returns the tag number of the field
	 */
	public function setTag($tag) {
		return $this->tag = $tag;
	}

	/**
	 * Is empty
	 *
	 * Checks if the field is empty.
	 *
	 * @return bool Returns true if the field is empty, otherwise false
	 */
	public function isEmpty() {
		return $this->count() === 0;
	}

	/**
	 * Is control field
	 *
	 * Checks if the field is a control field.
	 *
	 * @return bool Returns true if the field is a control field, otherwise false
	 */
	public function isControlField() {
		return $this instanceof ControlField;
	}

	/**
	 * Is data field
	 *
	 * Checks if the field is a data field.
	 *
	 * @return bool Returns true if the field is a data field, otherwise false
	 */
	public function isDataField() {
		return $this instanceof DataField;
	}

	/**
	 * Return Field formatted
	 *
	 * Return Field as a formatted string.
	 *
	 * @return string Formatted output of Field
	 */
	public function __toString() {
		return $this->getTag();
	}

	/**
	 * Return field in raw MARC format (stub)
	 *
	 * Return the field formatted in raw MARC for saving into MARC files. This
	 * stub method is extended by the child classes.
	 *
	 * @return bool Raw MARC
	 */
	public function toRaw() {
		return false;
	}

	/**
	 * Pretty print a MarcField object without tags, indicators, etc.
	 *
	 * @param array $exclude Subfields to exclude from formatted output
	 *
	 * @return string Returns the formatted field data
	 */
	public function formatField($exclude = array('2')) {
		if ($this->isControlField()) {
			return $this->getData();
		} else {
			$out = '';
			foreach ($this->getSubfields() as $subfield) {
				if (substr($this->getTag(), 0, 1) == '6' and (in_array($subfield->getCode(), array('v','x','y','z')))) {
					$out .= ' -- ' . $subfield->getData();
				} elseif (!in_array($subfield->getCode(), $exclude)) {
					$out .= ' ' . $subfield->getData();
				}
			}
			return trim($out);
		}
	}
}