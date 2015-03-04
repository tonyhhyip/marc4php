<?php

namespace Marc;

/**
 * Class Marc
 * The main Marc class enables you to return MarcRecord
 * objects from a stream or string.
 * @package Marc
 */
class Marc extends MarcBase {

	/**
	 * Hexadecimal value for Subfield indicator
	 */
	const SUBFIELD_INDICATOR = "\x1F";

	/**
	 * Hexadecimal value for End of Field
	 */
	const END_OF_FIELD = "\x1E";

	/**
	 * Hexadecimal value for End of Record
	 */
	const END_OF_RECORD = "\x1D";

	/**
	 * Length of the Directory
	 */
	const DIRECTORY_ENTRY_LEN = 12;

	/**
	 * Length of the Leader
	 */
	const LEADER_LEN = 24;

	/**
	 * Maximum record length
	 */
	const MAX_RECORD_LENGTH = 99999;

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
	 * Read in MARC records
	 *
	 * This function reads in MARC record files or strings that
	 * contain one or more MARC records.
	 *
	 * @param string $source Name of the file, or a raw MARC string
	 * @param int    $type   Source of the input, either SOURCE_FILE or SOURCE_STRING
	 */
	public function __construct($source, $type = MarcBase::SOURCE_FILE) {

		if (in_array($type, array(MarcBase::SOURCE_FILE, MarcBase::SOURCE_STRING)))
			$this->type = $type;
		else
			throw new MarcException('', MarcException::ERROR_INVALID_SOURCE);

		parent::__construct($source, $type);

		switch ($type) {
			case MarcBase::SOURCE_FILE:
				if (!$this->source = fopen($source, 'rb')) {
					$errorMessage = MarcException::formatError(MarcException::ERROR_INVALID_FILE, array('filename' => $source));
					throw new MarcException($errorMessage, MarcException::ERROR_INVALID_FILE);
				}
			break;

			case MarcBase::SOURCE_STRING:
				$this->source = explode(Marc::END_OF_RECORD, $source);
		}
	}

	/**
	 * Return the next raw MARC record
	 *
	 * Returns the next raw MARC record, unless all records already have
	 * been read.
	 *
	 * @return string Either a raw record or null
	 */
	public function nextRaw() {
		if (MarcBase::SOURCE_FILE === $this->type) {
			$record = stream_get_line($this->source, Marc::MAX_RECORD_LENGTH, Marc::END_OF_RECORD);

			// Remove illegal stuff that sometimes occurs between records
			$record = preg_replace('/^[\\x0a\\x0d\\x00]+/', "", $record);
		} elseif (MarcBase::SOURCE_STRING === $this->type) {
			$record = array_shift($this->source);
		}

		if (!$record)
			return null;

		return $record . Marc::END_OF_RECORD;
	}

	/**
	 * {@inheritdoc}
	 */
	public function nextRecord(){
		if ($raw = $this->nextRaw())
			$this->decode($raw);
		else
			return null;
	}

	/**
	 * Decode a given raw MARC record
	 *
	 * Port of Andy Lesters MARC::File::USMARC->decode() Perl function into PHP.
	 *
	 * @param string $text Raw MARC record
	 *
	 * @return MarcRecord MarcRecord object
	 */
	private function decode($text) {
		$marc = new MarcRecord($this);

		// fallback on the actual byte length
		$recordLength = strlen($text);

		$matches = array();
		if (preg_match("/^(\d{5})/", $text, $matches) && strlen($text) !== ($recordLength = $matches[1])) {
			$marc->addWarning(MarcException::formatError(MarcException::ERROR_INCORRECT_LENGTH,
				array(
					"record_length" => $recordLength,
					"actual" => strlen($text)
				)
			));
			// Real beats declared byte length
			$recordLength = strlen($text);
		} else {
			$marc->addWarning(MarcException::formatError(MarcException::ERROR_NONNUMERIC_LENGTH,
				array(
					"record_length" => substr($text, 0, 5)
				)));
		}

		if (substr($text, -1, 1) !== Marc::END_OF_RECORD)
			throw new MarcException('', MarcException::ERROR_INVALID_TERMINATOR);

		// Store leader
		$marc->setLeader(substr($text, 0, Marc::LEADER_LEN));

		// bytes 12 - 16 of leader give offset to the body of the record
		$data_start = intval(substr($text, 12, 5));

		// immediately after the leader comes the directory (no separator)
		$dir = substr($text, Marc::LEADER_LEN, $data_start - Marc::LEADER_LEN - 1);  // -1 to allow for \x1e at end of directory

		// character after the directory must be \x1e
		if (Marc::END_OF_FIELD !== substr($text, $data_start-1, 1)) {
			$marc->addWarning(MarcException::formatError(MarcException::ERROR_NO_DIRECTORY, array()));
		}

		// All directory entries 12 bytes long, so length % 12 must be 0
		if (strlen($dir) % Marc::DIRECTORY_ENTRY_LEN != 0) {
			$marc->addWarning(MarcException::formatError(MarcException::ERROR_INVALID_DIRECTORY_LENGTH, array()));
		}

		// go through all the fields
		$nfields = strlen($dir) / Marc::DIRECTORY_ENTRY_LEN;
		for ($n = 0; $n  < $nfields; $n++) {
			// As pack returns to key 1, leave place 0 in list empty
			list($_, $tag) = unpack("A3", substr($dir, $n * Marc::DIRECTORY_ENTRY_LEN, Marc::DIRECTORY_ENTRY_LEN));
			list($_, $len) = unpack("A3/A4", substr($dir, $n * Marc::DIRECTORY_ENTRY_LEN, Marc::DIRECTORY_ENTRY_LEN));
			list($_, $offset) = unpack("A3/A4/A5", substr($dir, $n * Marc::DIRECTORY_ENTRY_LEN, Marc::DIRECTORY_ENTRY_LEN));

			// Check directory validity
			if (!preg_match("/^[0-9A-Za-z]{3}$/", $tag)) {
				$marc->addWarning(MarcException::formatError(MarcException::ERROR_INVALID_DIRECTORY_TAG, array("tag" => $tag)));
			}
			if (!preg_match("/^\d{4}$/", $len)) {
				$marc->addWarning(MarcException::formatError(MarcException::ERROR_INVALID_DIRECTORY_TAG_LENGTH, array("tag" => $tag, "len" => $len)));
			}
			if (!preg_match("/^\d{5}$/", $offset)) {
				$marc->addWarning(MarcException::formatError(MarcException::ERROR_INVALID_DIRECTORY_OFFSET, array("tag" => $tag, "offset" => $offset)));
			}
			if ($offset + $len > $recordLength) {
				$marc->addWarning(MarcException::formatError(MarcException::ERROR_INVALID_DIRECTORY, array("tag" => $tag)));
			}

			$tag_data = substr($text, $data_start + $offset, $len);

			if (Marc::END_OF_FIELD === substr($tag_data, -1, 1)) {
				/* get rid of the end-of-tag character */
				$tag_data = substr($tag_data, 0, -1);
				$len--;
			} else {
				$marc->addWarning(MarcException::formatError(MarcException::ERROR_FIELD_EOF, array("tag" => $tag)));
			}

			if (preg_match("/^\d+$/", $tag) and ($tag < 10)) {
				$marc->appendField(new MarcControlField($tag, $tag_data));
			} else {
				$subfields = explode(Marc::SUBFIELD_INDICATOR, $tag_data);
				$indicators = array_shift($subfields);

				if (strlen($indicators) != 2) {
					$errorMessage = MarcException::formatError(MarcException::ERROR_INVALID_INDICATORS, array("tag" => $tag, "indicators" => $indicators));
					$marc->addWarning($errorMessage);
					// Do the best with the indicators we've got
					if (strlen($indicators) == 1) {
						$ind1 = $indicators;
						$ind2 = " ";
					} else {
						list($ind1,$ind2) = array(" ", " ");
					}
				} else {
					$ind1 = substr($indicators, 0, 1);
					$ind2 = substr($indicators, 1, 1);
				}

				// Split the subfield data into subfield name and data pairs
				$subfield_data = array();
				foreach ($subfields as $subfield) {
					if (strlen($subfield) > 0) {
						$subfield_data[] = new MarcSubfield(substr($subfield, 0, 1), substr($subfield, 1));
					} else {
						$errorMessage = MarcException::formatError(MarcException::ERROR_EMPTY_SUBFIELD, array("tag" => $tag));
						$marc->addWarning($errorMessage);
					}
				}

				if (!isset($subfield_data)) {
					$errorMessage = MarcException::formatError(MarcException::ERROR_EMPTY_SUBFIELD, array("tag" => $tag));
					$marc->addWarning($errorMessage);
				}


				// If the data is invalid, let's just ignore the one field
				try {
					$new_field = new MarcDataField($tag, $subfield_data, $ind1, $ind2);
					$marc->appendField($new_field);
				} catch (Exception $e) {
					$marc->addWarning($e->getMessage());
				}
			}
		}

		return $marc;
	}
}