<?php

namespace Marc;

/**
 * Class MarcDataField
 * @package Marc
 * The File_MARC_Data_Field class represents a single field in a MARC record.
 *
 * A MARC data field consists of a tag name, two indicators which may be null,
 * and zero or more subfields represented by {@link MarcSubfield} objects.
 * Subfields are held within a linked list structure.
 */
final class MarcDataField extends MarcField {

	/**
	 * Value of the first indicator
	 * @var string
	 */
	protected $ind1;

	/**
	 * Value of the second indicator
	 * @var string
	 */
	protected $ind2;

	/**
	 * Linked list of subfields
	 * @var MarcList
	 */
	protected $subfields;

	/**
	 * constructor
	 *
	 * Create a new {@link File_MARC_Data_Field} object. The only required
	 * parameter is a tag. This enables programs to build up new fields
	 * programmatically.
	 *
	 * <code>
	 * // Example: Create a new data field
	 *
	 * // We can optionally create an array of subfields first
	 * $subfields[] = new File_MARC_Subfield('a', 'Scott, Daniel.');
	 *
	 * // Create the new 100 field complete with a _a subfield and an indicator
	 * $new_field = new File_MARC_Data_Field('100', $subfields, 0, null);
	 * </code>
	 *
	 * @param string $tag       tag
	 * @param array  $subfields array of {@link File_MARC_Subfield} objects
	 * @param string $ind1      first indicator
	 * @param string $ind2      second indicator
	 */
	public function __construct($tag, array $subfields = null, $ind1 = null, $ind2 = null) {
		$this->subfields = new MarcList();

		parent::__construct($tag);

		$this->ind1 = $this->validateIndicator($ind1);
		$this->ind2 = $this->validateIndicator($ind2);

		// we'll let users add subfields after if they so desire
		if ($subfields) {
			$this->addSubfields($subfields);
		}
	}

	/**
	 * Destroys the data field
	 */
	public function __destruct() {
		parent::__destruct();
		$this->subfields = null;
		$this->ind1 = null;
		$this->ind2 = null;
	}

	/**
	 * Destroys the data field
	 */
	public function delete() {
		$this->__destruct();
	}

	/**
	 * Validates an indicator field
	 *
	 * Validates the value passed in for an indicator. This routine ensures
	 * that an indicator is a single character. If the indicator value is null,
	 * then this method returns a single character.
	 *
	 * If the indicator value contains more than a single character, this
	 * throws an exception.
	 *
	 * @param string $indicator Value of the indicator to be validated
	 *
	 * @return string Returns the indicator, or space if the indicator was null
	 */
	private function validateIndicator($indicator) {
		if ($indicator == null) {
			$indicator = ' ';
		} elseif (strlen($indicator) > 1) {
			$errorMessage = MarcException::formatError(MarcException::ERROR_INVALID_INDICATOR, array("tag" => $this->getTag(), "indicator" => $indicator));
			throw new MarcException($errorMessage, MarcException::ERROR_INVALID_INDICATOR);
		}
		return $indicator;
	}

	/**
	 * Appends subfield to subfield list
	 *
	 * Adds a File_MARC_Subfield object to the end of the existing list
	 * of subfields.
	 *
	 * @param MarcSubfield $new_subfield The subfield to add
	 *
	 * @return MarcSubfield  the new File_MARC_Subfield object
	 */
	public function appendSubfield(MarcSubfield $new_subfield) {
		/* Append as the last subfield in the field */
		return $this->subfields->appendNode($new_subfield);
	}

	/**
	 * Prepends subfield to subfield list
	 *
	 * Adds a MarcSubfield object to the  start of the existing list
	 * of subfields.
	 *
	 * @param MarcSubfield $new_subfield The subfield to add
	 *
	 * @return MarcSubfield the new MarcSubfield object
	 */
	public function prependSubfield(MarcSubfield $new_subfield) {
		$pos = 0;
		$new_subfield->setPosition($pos);
		$this->subfields->shift($new_subfield);
		$node = null;
		$this->subfields->rewind();
		while ($node = $this->subfields->next()) {
			$pos++;
			$node->setPosition($pos);
		}
		return $new_subfield;
	}

	/**
	 * Inserts a field in the MARC record relative to an existing field
	 *
	 * Inserts a {@link MarcSubfield} object before or after an existing
	 * subfield.
	 *
	 * @param MarcSubfield $new_field      The subfield to add
	 * @param MarcSubfield $existing_field The target subfield
	 * @param bool               $before         Insert the subfield before the existing subfield if true; after the existing subfield if false
	 *
	 * @return MarcSubfield              The new subfield
	 */
	public function insertSubfield(MarcSubfield $new_field, MarcSubfield  $existing_field, $before = false) {
		switch ($before) {
			/* Insert before the specified subfield in the record */
			case true:
				$this->subfields->insertNode($new_field, $existing_field, true);
				break;

			/* Insert after the specified subfield in the record */
			case false:
				$this->subfields->insertNode($new_field, $existing_field);
				break;

			default:
				$errorMessage = MarcException::formatError(MarcException::ERROR_INSERTSUBFIELD_MODE, array("mode" => $before));
				throw new MarcException($errorMessage, MarcException::ERROR_INSERTSUBFIELD_MODE);
		}
		return $new_field;
	}

	/**
	 * Adds an array of subfields to a {@link MarcDataField} object
	 *
	 * Appends subfields to existing subfields in the order in which
	 * they appear in the array. For finer grained control of the subfield
	 * order, use {@link appendSubfield()}, {@link prependSubfield()},
	 * or {@link insertSubfield()} to add each subfield individually.
	 *
	 * @param array $subfields array of {@link MarcSubfield} objects
	 *
	 * @return int returns the number of subfields that were added
	 */
	public function addSubfields(array $subfields) {
		/*
		 * Just in case someone passes in a single MarcSubfield
		 * instead of an array
		 */
		if ($subfields instanceof MarcSubfield) {
			$this->appendSubfield($subfields);
			return 1;
		}

		foreach ($subfields as $subfield) {
			$this->appendSubfield($subfield);
		}
		return count($subfields);
	}

	/**
	 * Delete a subfield from the field.
	 *
	 * @param MarcSubfield $subfield The subfield to delete.
	 */
	public function deleteSubfield(MarcSubfield $subfield) {
		$this->subfields->deleteNode($subfield);
	}

	/**
	 * Get the value of an indicator
	 *
	 * @param int $ind number of the indicator (1 or 2)	 *
	 * @return string returns indicator value if it exists, otherwise false
	 */
	function getIndicator($ind) {
		if ($ind == 1) {
			return (string)$this->ind1;
		} elseif ($ind == 2) {
			return (string)$this->ind2;
		} else {
			$errorMessage = MarcException::formatError(MarcException::ERROR_INVALID_INDICATOR_REQUEST, array("indicator" => $ind));
			throw new MarcException($errorMessage, MarcException::ERROR_INVALID_INDICATOR_REQUEST);
		}
	}

	/**
	 * Set the value of an indicator
	 *
	 * @param int    $ind   number of the indicator (1 or 2)
	 * @param string $value value of the indicator
	 *
	 * @return string       returns indicator value if it exists, otherwise false
	 */
	public function setIndicator($ind, $value) {
		switch ($ind) {

			case 1:
				$this->ind1 = $this->validateIndicator($value);
				break;

			case 2:
				$this->ind2 = $this->validateIndicator($value);
				break;

			default:
				$errorMessage = MarcException::formatError(MarcException::ERROR_INVALID_INDICATOR_REQUEST, array("indicator" => $ind));
				throw new MarcException($errorMessage, MarcException::ERROR_INVALID_INDICATOR_REQUEST);
		}

		return $this->getIndicator($ind);
	}

	/**
	 * Returns the first subfield that matches a requested code.
	 *
	 * @param string $code subfield code for which the
	 * {@link MarcSubfield} is retrieved
	 *
	 * @return MarcSubfield returns the first subfield that matches
	 * $code, or false if no codes match $code
	 */
	public function getSubfield($code = null) {
		// iterate merrily through the subfields looking for the requested code
		foreach ($this->subfields as $sf) {
			if ($sf->getCode() === $code) {
				return $sf;
			}
		}

		// No matches were found
		return false;
	}

	/**
	 * Returns an array of subfields that match a requested code,
	 * or a {@link MarcList} that contains all of the subfields
	 * if the requested code is null.
	 *
	 * @param string $code subfield code for which the
	 * {@link MarcSubfield} is retrieved
	 *
	 * @return MarcList|array returns a linked list of all subfields
	 * if $code is null, an array of {@link File_MARC_Subfield} objects if
	 * one or more subfields match, or false if no codes match $code
	 */
	public function getSubfields($code = null) {
		if ($code === null)
			return null;
		$results = array();

		foreach ($this->subfields as $sf) {
			if ($sf->getCode() === $code)
				$results[] = $sf;
		}


		return $results;
	}

	/**
	 * Checks if the field is empty.
	 *
	 * Checks if the field is empty. If the field has at least one subfield
	 * with data, it is not empty.
	 *
	 * @return bool Returns true if the field is empty, otherwise false
	 */
	public function isEmpty() {
		// If $this->subfields is null, we must have deleted it
		if (!$this->subfields) {
			return true;
		}

		// Iterate through the subfields looking for some data
		foreach ($this->subfields as $subfield) {
			// Check if subfield has data
			if (!$subfield->isEmpty()) {
				return false;
			}
		}
		// It is empty
		return true;
	}

	/**
	 * Return Field formatted
	 *
	 * Return Field as a formatted string.
	 *
	 * @return string Formatted output of Field
	 */
	public function __toString() {
		// Variables
		$lines = array();
		// Process tag and indicators
		$pre = sprintf("%3s %1s%1s", $this->tag, $this->ind1, $this->ind2);

		// Process subfields
		foreach ($this->subfields as $subfield) {
			$lines[] = sprintf("%6s _%1s%s", $pre, $subfield->getCode(), $subfield->getData());
			$pre = "";
		}

		return join("\n", $lines);
	}

	/**
	 * Return Field in Raw MARC
	 *
	 * Return the Field formatted in Raw MARC for saving into MARC files
	 *
	 * @return string Raw MARC
	 */
	public function toRaw() {
		$subfields = array();
		foreach ($this->subfields as $subfield) {
			if (!$subfield->isEmpty()) {
				$subfields[] = $subfield->toRaw();
			}
		}
		return $this->ind1 . $this->ind2 . implode("", $subfields) . Marc::END_OF_FIELD;
	}
}