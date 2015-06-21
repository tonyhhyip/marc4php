<?php

/**
 * marc4php
 *
 * PHP version 5
 *
 * Copyright (C) Tony Yip 2015.
 *
 * @category Guardian
 * @author   Tony Yip <tony@opensource.hk>
 */


namespace Marc;

/**
 * Class MarcXML
 * The main MarcXML class enables you to return MarcRecord
 * objects from an XML stream or string.
 * @package Marc
 */
class MarcXML extends MarcBase
{

	/**
	 * Source containing raw records
	 *
	 * @var resource
	 */
	protected $source;

	/**
	 * Source type (SOURCE_FILE or SOURCE_STRING)
	 *
	 * @var int
	 */
	protected $type;

	/**
	 * Counter for MARCXML records in a collection
	 *
	 * @var int
	 */
	protected $counter;

	/**
	 * Read in MARCXML records
	 *
	 * This function reads in files or strings that
	 * contain one or more MARCXML records.
	 *
	 * @param string $source    Name of the file, or a raw MARC string
	 * @param int    $type      Source of the input, either SOURCE_FILE or SOURCE_STRING
	 * @param string $ns        URI or prefix of the namespace
	 * @param bool   $prefix TRUE if $ns is a prefix, FALSE if it's a URI; defaults to FALSE
	 */
	public function __construct($source, $type = MarcBase::SOURCE_FILE, $ns = '', $prefix = false)
	{
		if (in_array($type, array(MarcBase::SOURCE_FILE, MarcBase::SOURCE_STRING)))
			$this->type = $type;
		else
			throw new MarcException('', MarcException::ERROR_INVALID_SOURCE);

		parent::__construct($source, $type);

		switch ($type) {
			case MarcBase::SOURCE_FILE:
				$this->source = simplexml_load_file($source, 'SimpleXMLElement', 0, $ns, $prefix);
				break;

			case MarcBase::SOURCE_STRING:
				$this->source = simplexml_load_string($source, 'SimpleXMLElement', 0, $ns, $prefix);
		}
	}

	/**
	 * {@inheritdoc}
	 */
	public function next()
	{
		if (isset($this->source->record[$this->counter])) {
			$record = $this->source->record[$this->counter++];
		} elseif ($this->source->getName() == "record" && $this->counter == 0) {
			$record = $this->source;
			$this->counter++;
		} else {
			return null;
		}

		if ($record) {
			return $this->decode($record);
		} else {
			return null;
		}
	}

	/**
	 * Decode a given MARCXML record
	 *
	 * @param string $text MARCXML record element
	 *
	 * @return MarcRecord Decoded File_MARC_Record object
	 */
	private function decode($text)
	{
		$marc = new MarcRecord($this);

		// Store leader
		$marc->setLeader($text->leader);

		// go through all the control fields
		foreach ($text->controlfield as $controlfield) {
			$controlfieldattributes = $controlfield->attributes();
			$marc->appendField(new MarcControlField((string)$controlfieldattributes['tag'], $controlfield));
		}

		// go through all the data fields
		foreach ($text->datafield as $datafield) {
			$datafieldattributes = $datafield->attributes();
			$subfield_data = array();
			foreach ($datafield->subfield as $subfield) {
				$subfieldattributes = $subfield->attributes();
				$subfield_data[] = new MarcSubfield((string)$subfieldattributes['code'], $subfield);
			}

			// If the data is invalid, let's just ignore the one field
			try {
				$new_field = new MarcDataField((string)$datafieldattributes['tag'], $subfield_data, $datafieldattributes['ind1'], $datafieldattributes['ind2']);
				$marc->appendField($new_field);
			} catch (Exception $e) {
				$marc->addWarning($e->getMessage());
			}
		}

		return $marc;
	}
}