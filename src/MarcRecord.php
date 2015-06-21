<?php

namespace Marc;


class MarcRecord {
	/**
	 * Contains a linked list of {@link MarcDataField} objects for
	 * this record
	 * @var MarcList
	 */
	protected $fields;

	/**
	 * Record leader
	 * @var string
	 */
	protected $leader;

	/**
	 * Non-fatal warnings generated during parsing
	 * @var array
	 */
	protected $warnings;

	/**
	 * XMLWriter for writing collections
	 *
	 * @var \XMLWriter
	 */
	protected $marcxml;

	/**
	 * MARC instance for access to the XML header/footer methods
	 * We need this so that we can properly wrap a collection of MARC records.
	 *
	 * @var Marc
	 */
	protected $marc;

	/**
	 * constructor
	 *
	 * Set all variables to defaults to create new File_MARC_Record object
	 *
	 * @param MarcBase $marc MARC record from Marc or MarcXML
	 */
	public function __construct(MarcBase $marc = null) {
		$this->fields = new MarcList();
		$this->setLeader(str_repeat(' ', 24));
		if (!$marc) {
			$marc = new Marc(null, Marc::SOURCE_STRING); // oh the hack
		}
		$this->marc = $marc;
		$this->marcxml = $marc->getXMLWriter();
	}

	/**
	 * Destroys the data field
	 */
	public function __destruct() {
		$this->fields = null;
		$this->warnings = null;
	}

	/**
	 * Get MARC leader
	 *
	 * Returns the leader for the MARC record. No validation
	 * on the specified leader is performed.
	 *
	 * @return string returns the leader
	 */
	public function getLeader() {
		return $this->leader;
	}

	/**
	 * Set MARC record leader
	 *
	 * Sets the leader for the MARC record. No validation
	 * on the specified leader is performed.
	 *
	 * @param string $leader Leader
	 */
	public function setLeader($leader) {
		$this->leader = $leader;
	}

	/**
	 * Appends field to MARC record
	 *
	 * Adds a {@link File_MARC_Control_Field} or {@link File_MARC_Data_Field}
	 * object to the end of the existing list of fields.
	 *
	 * @param MarcField $new_field The field to add
	 */
	public function appendField(MarcField $new_field) {
		/* Append as the last field in the record */
		$this->fields->appendNode($new_field);
	}

	/**
	 * Prepends field to MARC record
	 *
	 * Adds a {@link MarcControlField} or {@link MarcDataField}
	 * object to the start of to the existing list of fields.
	 *
	 * @param MarcField $new_field The field to add
	 */
	public function prependField(MarcField $new_field) {
		$this->fields->prependNode($new_field);
	}

	/**
	 * Inserts a field in the MARC record relative to an existing field
	 *
	 * Inserts a {@link MarcControlField} or {@link MarcDataField}
	 * object before or after a specified existing field.
	 *
	 * @param MarcField $new_field      The field to add
	 * @param MarcField  $existing_field The target field
	 * @param bool            $before         Insert the new field before the existing field if true, after the existing field if false
	 * @throws MarcException
	 */
	public function insertField(MarcField $new_field, MarcField $existing_field, $before = false) {
		switch ($before) {
			/* Insert before the specified field in the record */
			case true:
				$this->fields->insertNode($new_field, $existing_field, true);
				break;

			/* Insert after the specified field in the record */
			case false:
				$this->fields->insertNode($new_field, $existing_field);
				break;

			default:
				$errorMessage = MarcException::formatError(MarcException::ERROR_INSERTFIELD_MODE, array("mode" => $before));
				throw new MarcException($errorMessage, MarcException::ERROR_INSERTFIELD_MODE);
		}
	}

	/**
	 * Build record directory
	 *
	 * Generate the directory of the record according to the current contents
	 * of the record.
	 *
	 * @return array Array ($fields, $directory, $total, $base_address)
	 */
	private function buildDirectory() {
		// Vars
		$fields = array();
		$directory = array();
		$data_end = 0;

		foreach ($this->fields as $field) {
			// No empty fields allowed
			if (!$field->isEmpty()) {
				// Get data in raw format
				$str = $field->toRaw();
				$fields[] = $str;

				// Create directory entry
				$len = strlen($str);
				$direntry = sprintf("%03s%04d%05d", $field->getTag(), $len, $data_end);
				$directory[] = $direntry;
				$data_end += $len;
			}
		}

		/**
		 * Rules from MARC::Record::USMARC
		 */
		$base_address
			= Marc::LEADER_LEN +    // better be 24
			(count($directory) * Marc::DIRECTORY_ENTRY_LEN) +
			// all the directory entries
			1;              // end-of-field marker


		$total
			= $base_address +  // stuff before first field
			$data_end +      // Length of the fields
			1;              // End-of-record marker


		return array($fields, $directory, $total, $base_address);
	}

	/**
	 * Set MARC record leader lengths
	 *
	 * Set the Leader lengths of the record according to defaults specified in
	 * {@link http://www.loc.gov/marc/bibliographic/ecbdldrd.html}
	 *
	 * @param int $record_length Record length
	 * @param int $base_address  Base address of data
	 *
	 * @return bool              Success or failure
	 */
	public function setLeaderLengths($record_length, $base_address) {
		if (!is_int($record_length) || !is_int($base_address)) {
			return false;
		}

		// Set record length
		$this->setLeader(substr_replace($this->getLeader(), sprintf("%05d", $record_length), 0, 5));
		$this->setLeader(substr_replace($this->getLeader(), sprintf("%05d", $base_address), Marc::DIRECTORY_ENTRY_LEN, 5));
		$this->setLeader(substr_replace($this->getLeader(), '22', 10, 2));
		$this->setLeader(substr_replace($this->getLeader(), '4500', 20, 4));

		if (strlen($this->getLeader()) > Marc::LEADER_LEN) {
			// Avoid incoming leaders that are mangled to be overly long
			$this->setLeader(substr($this->getLeader(), 0, Marc::LEADER_LEN));
			$this->addWarning("Input leader was too long; truncated to " . Marc::LEADER_LEN . " characters");
		}
		return true;
	}

	/**
	 * Return the first {@link MarcDataField} or
	 * {@link MarcControlField} object that matches the specified tag
	 * name. Returns false if no match is found.
	 *
	 * @param string $spec tag name
	 * @param bool   $pcre if true, then match as a regular expression
	 *
	 * @return MarcField first field that matches the requested tag name
	 */
	public function getField($spec = null, $pcre = null) {
		foreach ($this->fields as $field) {
			if (($pcre
					&& preg_match("/$spec/", $field->getTag()))
				|| (!$pcre
					&& $spec == $field->getTag())
			) {
				return $field;
			}
		}
		return null;
	}

	/**
	 * Return an array or {@link MarcList} containing all
	 * {@link MarcDataField} or  {@link MarcControlField} objects
	 * that match the specified tag name. If the tag name is omitted all
	 * fields are returned.
	 *
	 * @param string $spec tag name
	 * @param bool   $pcre if true, then match as a regular expression
	 *
	 * @return MarcList|array {@link MarcDataField} or
	 * {@link MarcControlField} objects that match the requested tag name
	 */
	public function getFields($spec = null, $pcre = null) {
		if (!$spec) {
			return $this->fields;
		}

		// Okay, we're actually looking for something specific
		$matches = array();
		foreach ($this->fields as $field) {
			if (($pcre && preg_match("/$spec/", $field->getTag()))
				|| (!$pcre && $spec == $field->getTag())
			) {
				$matches[] = $field;
			}
		}
		return $matches;
	}

	/**
	 * Delete all occurrences of a field matching a tag name from the record.
	 *
	 * @param string $tag  tag for the fields to be deleted
	 * @param bool   $pcre if true, then match as a regular expression
	 *
	 * @return int         number of fields that were deleted
	 */
	public function deleteFields($tag, $pcre = null) {
		$cnt = 0;
		foreach ($this->getFields() as $field) {
			if (($pcre
					&& preg_match("/$tag/", $field->getTag()))
				|| (!$pcre
					&& $tag == $field->getTag())
			) {
				$field->delete();
				$cnt++;
			}
		}
		return $cnt;
	}

	/**
	 * Add a warning to the MARC record that something non-fatal occurred during
	 * parsing.
	 *
	 * @param string $warning warning message
	 *
	 * @return true
	 */
	public function addWarning($warning) {
		$this->warnings[] = $warning;
	}

	/**
	 * Return the array of warnings from the MARC record.
	 *
	 * @return array warning messages
	 */
	public function getWarnings() {
		return $this->warnings;
	}

	/**
	 * Return the record in raw MARC format.
	 *
	 * If you have modified an existing MARC record or created a new MARC
	 * record, use this method to save the record for use in other programs
	 * that accept the MARC format -- for example, your integrated library
	 * system.
	 *
	 * @return string Raw MARC data
	 */
	public function toRaw() {
		list($fields, $directory, $record_length, $base_address) = $this->buildDirectory();
		$this->setLeaderLengths($record_length, $base_address);

		/**
		 * Glue together all parts
		 */
		return $this->getLeader() . implode("", $directory) . Marc::END_OF_FIELD.implode("", $fields) . Marc::END_OF_RECORD;
	}

	/**
	 * Return the MARC record in a pretty printed string
	 *
	 * This method produces an easy-to-read textual display of a MARC record.
	 *
	 * The structure is roughly:
	 * <tag> <ind1> <ind2> _<code><data>
	 *                     _<code><data>
	 *
	 * @return string Formatted representation of MARC record
	 */
	public function __toString() {
		// Begin output
		$formatted = "LDR " . $this->getLeader() . "\n";
		foreach ($this->fields as $field) {
			if (!$field->isEmpty()) {
				$formatted .= $field->__toString() . "\n";
			}
		}
		return $formatted;
	}

	/**
	 * Return the MARC record in JSON format
	 *
	 * This method produces a JSON representation of a MARC record. The input
	 * encoding must be UTF8, otherwise the returned values will be corrupted.
	 *
	 * @return string          representation of MARC record in JSON format
	 */
	function toJSON() {
		$json = array();
		$json['leader'] = utf8_encode($this->getLeader());

		/* Start fields */
		$fields = array();
		foreach ($this->fields as $field) {
			if (!$field->isEmpty()) {
				switch(get_class($field)) {
					case "File_MARC_Control_Field":
						$fields[] = array(utf8_encode($field->getTag()) => utf8_encode($field->getData()));
						break;

					case "File_MARC_Data_Field":
						$subs = array();
						foreach ($field->getSubfields() as $sf) {
							$subs[] = array(utf8_encode($sf->getCode()) => utf8_encode($sf->getData()));
						}
						$contents = array();
						$contents['ind1'] = utf8_encode($field->getIndicator(1));
						$contents['ind2'] = utf8_encode($field->getIndicator(2));
						$contents['subfields'] = $subs;
						$fields[] = array(utf8_encode($field->getTag()) => $contents);
						break;
				}
			}
		}
		/* End fields and record */

		$json['fields'] = $fields;
		$json_rec = json_encode($json);
		// Required because json_encode() does not let us stringify integer keys
		return preg_replace('/("subfields":)(.*?)\["([^\"]+?)"\]/', '\1\2{"0":"\3"}', $json_rec);
	}

	/**
	 * Return the MARC record in Bill Dueber's MARC-HASH JSON format
	 *
	 * This method produces a JSON representation of a MARC record as defined
	 * at http://robotlibrarian.billdueber.com/new-interest-in-marc-hash-json/
	 * The input * encoding must be UTF8, otherwise the returned values will
	 * be corrupted.
	 *
	 * @return string          representation of MARC record in JSON format
	 */
	public function toJSONHash() {
		$json = new StdClass();
		$json->type = "marc-hash";
		$json->version = array(1, 0);
		$json->leader = utf8_encode($this->getLeader());

		/* Start fields */
		$fields = array();
		foreach ($this->fields as $field) {
			if (!$field->isEmpty()) {
				switch(get_class($field)) {
					case "File_MARC_Control_Field":
						$fields[] = array(utf8_encode($field->getTag()), utf8_encode($field->getData()));
						break;

					case "File_MARC_Data_Field":
						$subs = array();
						foreach ($field->getSubfields() as $sf) {
							$subs[] = array(utf8_encode($sf->getCode()), utf8_encode($sf->getData()));
						}
						$contents = array(
							utf8_encode($field->getTag()),
							utf8_encode($field->getIndicator(1)),
							utf8_encode($field->getIndicator(2)),
							$subs
						);
						$fields[] = $contents;
						break;
				}
			}
		}
		/* End fields and record */

		$json->fields = $fields;
		return json_encode($json);
	}

	/**
	 * Return the MARC record in MARCXML format
	 *
	 * This method produces an XML representation of a MARC record that
	 * attempts to adhere to the MARCXML standard documented at
	 * http://www.loc.gov/standards/marcxml/
	 *
	 * @param string $encoding output encoding for the MARCXML record
	 * @param bool   $indent   pretty-print the MARCXML record
	 * @param bool   $single   wrap the <record> element in a <collection> element
	 *
	 * @return string          representation of MARC record in MARCXML format
	 */
	public function toXML($encoding = "UTF-8", $indent = true, $single = true) {
		$this->marcxml->setIndent($indent);
		if ($single) {
			$this->marcxml->startElement("collection");
			$this->marcxml->writeAttribute("xmlns", "http://www.loc.gov/MARC21/slim");
			$this->marcxml->startElement("record");
		} else {
			$this->marcxml->startElement("record");
			$this->marcxml->writeAttribute("xmlns", "http://www.loc.gov/MARC21/slim");
		}


		// MARCXML schema has some strict requirements
		// We'll set reasonable defaults to avoid invalid MARCXML
		$xmlLeader = $this->getLeader();

		// Record status
		if ($xmlLeader[5] == " ") {
			// Default to "n" (new record)
			$xmlLeader[5] = "n";
		}

		// Type of record
		if ($xmlLeader[6] == " ") {
			// Default to "a" (language material)
			$xmlLeader[6] = "a";
		}

		$this->marcxml->writeElement("leader", $xmlLeader);

		foreach ($this->fields as $field) {
			if (!$field->isEmpty()) {
				switch(get_class($field)) {
					case "File_MARC_Control_Field":
						$this->marcxml->startElement("controlfield");
						$this->marcxml->writeAttribute("tag", $field->getTag());
						$this->marcxml->text($field->getData());
						$this->marcxml->endElement(); // end control field
						break;

					case "File_MARC_Data_Field":
						$this->marcxml->startElement("datafield");
						$this->marcxml->writeAttribute("tag", $field->getTag());
						$this->marcxml->writeAttribute("ind1", $field->getIndicator(1));
						$this->marcxml->writeAttribute("ind2", $field->getIndicator(2));
						foreach ($field->getSubfields() as $subfield) {
							$this->marcxml->startElement("subfield");
							$this->marcxml->writeAttribute("code", $subfield->getCode());
							$this->marcxml->text($subfield->getData());
							$this->marcxml->endElement(); // end subfield
						}
						$this->marcxml->endElement(); // end data field
						break;
				}
			}
		}

		$this->marcxml->endElement(); // end record
		if ($single) {
			$this->marcxml->endElement(); // end collection
			$this->marcxml->endDocument();
		}
		return $this->marcxml->outputMemory();
	}

}