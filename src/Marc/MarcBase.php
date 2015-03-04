<?php
/**
 * Created by PhpStorm.
 * User: tony
 * Date: 3/5/15
 * Time: 3:00 AM
 */

namespace Marc;

use XMLWriter;

/**
 * Class MarcBase
 * The main MarcBase class provides common methods for MARC and
 * MarcXml - primarily for generating MarcXml output.
 *
 * @package Marc
 */
abstract class MarcBase {

	/**
	 * MARC records retrieved from a file
	 */
	const SOURCE_FILE = 1;

	/**
	 * MARC records retrieved from a binary string
	 */
	const SOURCE_STRING = 2;

	/**
	 * @var XMLWriter
	 */
	protected $xmlWriter;

	/**
	 * Read in MARCXML records
	 *
	 * This function reads in files or strings that
	 * contain one or more MARCXML records.
	 * @param string $source Name of the file, or a raw MARC string
	 * @param int    $type   Source of the input, either SOURCE_FILE or SOURCE_STRING
	 */
	public function __construct($source, $type) {
		$xmlWriter = new XMLWriter();
		$xmlWriter->openMemory();
		$xmlWriter->startDocument(1.0, 'UTF-8');
		$this->xmlWriter = $xmlWriter;
	}

	/**
	 * Initializes the MARCXML output of a record or collection of records
	 *
	 * This method produces an XML representation of a MARC record that
	 * attempts to adhere to the MARCXML standard documented at
	 * http://www.loc.gov/standards/marcxml/
	 */
	public function toXMLHeader() {
		$this->xmlWriter->startElement('collection');
		$this->xmlWriter->writeAttribute('xmlns', 'http://www.loc.gov/MARC21/slim');
	}

	/**
	 * Returns the XMLWriter object
	 *
	 * This method produces an XML representation of a MARC record that
	 * attempts to adhere to the MARCXML standard documented at
	 * http://www.loc.gov/standards/marcxml/
	 *
	 * @return XMLWriter XMLWriter instance
	 */
	public function getXMLWriter() {
		return $this->xmlWriter;
	}

	/**
	 * Returns the MARCXML collection footer
	 *
	 * This method produces an XML representation of a MARC record that
	 * attempts to adhere to the MARCXML standard documented at
	 * http://www.loc.gov/standards/marcxml/
	 *
	 * @return string           representation of MARC record in MARCXML format
	 */
	public function toXMLFooter() {
		$this->xmlwriter->endElement(); // end collection
		$this->xmlwriter->endDocument();
		return $this->xmlwriter->outputMemory();
	}

	/**
	 * Return next {@link MarcRecord} object
	 *
	 * Decodes the next raw MARC record and returns the {@link MarcRecord}
	 * object.
	 * <code>
	 * <?php
	 * // Retrieve a set of MARC records from a file
	 * $journals = new Marc('journals.mrc', SOURCE_FILE);
	 *
	 * // Iterate through the retrieved records
	 * while ($record = $journals->next()) {
	 *     print $record;
	 *     print "\n";
	 * }
	 *
	 * ?>
	 * </code>
	 *
	 * @return MarcRecord next record, or null if there are
	 * no more records
	 */
	abstract public function nextRecord();
}