<?php

namespace Marc\Field;


final class ControlField extends Field
{

	/**
	 * Value of field, if field is a Control field
	 * @var string
	 */
	private $data;

	/**
	 * Field init function
	 *
	 * Create a new {@link MarcControlField} object from passed arguments
	 *
	 * @param string $tag  tag
	 * @param string $data control field data
	 * @param string $ind1 placeholder for class strictness
	 * @param string $ind2 placeholder for class strictness
	 */
	public function __construct($tag, $data, $ind1 = null, $ind2 = null)
	{
		parent::__construct($tag);
		$this->data = $data;

	}

	/**
	 * Destroys the control field
	 */
	public function __destruct()
	{
		parent::__destruct();
		$this->data = null;
	}

	/**
	 * Destroys the control field
	 */
	public function delete()
	{
		$this->__destruct();
	}

	/**
	 * Get control field data
	 *
	 * @return string returns data in control field
	 */
	public function getData()
	{
		return $this->data;
	}

	/**
	 * Is empty
	 *
	 * Checks if the field contains data
	 *
	 * @return bool Returns true if the field is empty, otherwise false
	 */
	public function isEmpty()
	{
		return empty($this->data);
	}

	/**
	 * Set control field data
	 *
	 * @param string $data data for the control field
	 *
	 * @return bool returns the new data in the control field
	 */
	public function setData($data)
	{
		return $this->data = $data;
	}

	/**
	 * Return as a formatted string
	 *
	 * Return the control field as a formatted string for pretty printing
	 *
	 * @return string Formatted output of control Field
	 */
	public function __toString()
	{
		return sprintf("%3s     %s", $this->tag, $this->data);
	}

	/**
	 * Return as raw MARC
	 *
	 * Return the control field formatted in Raw MARC for saving into MARC files
	 *
	 * @return string Raw MARC
	 */
	public function toRaw()
	{
		return $this->getData() . Marc::END_OF_FIELD;
	}
}