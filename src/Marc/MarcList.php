<?php

namespace Marc;

use SplDoublyLinkedList;
use Exception;

/**
 * Class MarcList
 * The MarcList class extends the SplDoublyLinkedList class
 * to override the key() method in a meaningful way for foreach() iterators.
 *
 * For the list of {@link MarcField} objects in a {@link MarcRecord}
 * object, the key() method returns the tag name of the field.
 *
 * For the list of {@link MarcSubfield} objects in a {@link
 * MarcDataField} object, the key() method returns the code of
 * the subfield.
 *
 * @package Marc
 */
class MarcList extends SplDoublyLinkedList {

	/**
	 * Position of the subfield
	 * @var int
	 */
	protected $position;

	/**
	 * Returns the tag for a {@link File_MARC_Field} object, or the code
	 * for a {@link File_MARC_Subfield} object.
	 *
	 * This method enables you to use a foreach iterator to retrieve
	 * the tag or code as the key for the iterator.
	 *
	 * @return string returns the tag or code
	 */
	public function key() {
		if ($this->current() instanceof MarcField) {
			return $this->current()->getTag();
		} elseif ($this->current() instanceof MarcSubfield) {
			return $this->current()->getCode();
		}
		return false;
	}

	/**
	 * Inserts a node into the linked list, based on a reference node that
	 * already exists in the list.
	 *
	 * @param mixed $new_node      New node to add to the list
	 * @param mixed $existing_node Reference position node
	 * @param bool  $before        Insert new node before or after the existing node
	 *
	 * @return bool Success or failure
	 **/
	public function insertNode($new_node, $existing_node, $before = false) {
		$pos = 0;
		$exist_pos = $existing_node->getPosition();
		$temp_list = unserialize(serialize($this));
		$this->rewind();
		$temp_list->rewind();

		// Now add the node according to the requested mode
		if ($before) {
			$new_node->setPosition($exist_pos);

			if ($exist_pos == 0) {
				$this->unshift($new_node);
				while ($n = $temp_list->next()) {
					$pos++;
					$this->next()->setPosition($pos);
				}
			} else {
				//$prev_node = $temp_list->offsetGet($existing_node->getPosition());
				$num_nodes = $this->count();
				$this->rewind();
				// Copy up to the existing position, add in node, copy rest
				try {
					while ($n = $temp_list->shift()) {
						$this->next();
						$pos++;
						if ($pos < $exist_pos) {
							continue;
						} elseif ($pos == $exist_pos) {
							$this->offsetSet($pos, $new_node);
						} elseif ($pos == $num_nodes) {
							$n->setPosition($pos);
							$this->push($n);
						} elseif ($pos > $exist_pos) {
							$n->setPosition($pos);
							$this->offsetSet($pos, $n);
						}
					}
				} catch (Exception $e) {
				}
			}
		} else {
				//$prev_node = $temp_list->offsetGet($existing_node->getPosition());
				$num_nodes = $this->count();
				$this->rewind();
				// Copy up to the existing position inclusively, add node, copy rest
				try {
					while ($n = $temp_list->shift()) {
						$this->next();
						$pos++;
						if ($pos <= $exist_pos) {
							continue;
						} elseif ($pos == $exist_pos + 1) {
							$this->offsetSet($pos, $new_node);
						} elseif ($pos == $num_nodes) {
							$n->setPosition($pos);
							$this->push($n);
						} elseif ($pos > $exist_pos + 1) {
							$n->setPosition($pos);
							$this->offsetSet($pos, $n);
						}
					}
				}
				catch (Exception $e) {
				}
		}

		return true;
	}

	/**
	 * Adds a node onto the linked list.
	 *
	 * @param mixed $new_node New node to add to the list
	 *
	 * @return void
	 **/
	public function appendNode($new_node) {
		$new_node->setPosition($this->count());
		$this->push($new_node);
	}

	/**
	 * Adds a node to the start of the linked list.
	 *
	 * @param mixed $new_node New node to add to the list
	 *
	 * @return void
	 **/
	public function prependNode($new_node) {
		$this->insertNode($new_node, $this->bottom(), true);
	}

	/**
	 * Deletes a node from the linked list.
	 *
	 * @param mixed $node Node to delete from the list
	 *
	 * @return void
	 **/
	public function deleteNode($node) {
		$target_pos = $node->getPosition();
		$this->rewind();
		$pos = 0;

		// Omit target node and adjust pos of remainder
		try {
			while ($n = $this->current()) {
				if ($pos == $target_pos) {
					$this->offsetUnset($pos);
				} elseif ($pos > $target_pos) {
					$n->setPosition($pos);
				}
				$pos++;
				$this->next();
			}
		}
		catch (Exception $e) {
		}

	}

	/**
	 * Sets position of the subfield
	 *
	 * @param string $pos new position of the subfield
	 *
	 * @return void
	 */
	public function setPosition($pos) {
		$this->position = $pos;
	}

	/**
	 * Return position of the subfield
	 *
	 * @return int data
	 */
	function getPosition() {
		return $this->position;
	}

}