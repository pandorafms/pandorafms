<?php

namespace Halfpastfour\Collection\Collection;

use Halfpastfour\Collection\Collection;

/**
 * Class Iterator
 * @package Halfpastfour\Collection\Collection
 */
class ArrayIterator implements \Iterator, \Countable
{
	/**
	 * @var Collection
	 */
	protected $collection;

	/**
	 * The current internal cursor position.
	 *
	 * @var int
	 */
	protected $cursor = 0;

	/**
	 * Iterator constructor.
	 *
	 * @param Collection\ArrayAccess $collection
	 *
	 * @internal param array $data
	 */
	public function __construct( Collection\ArrayAccess $collection )
	{
		$this->collection = $collection;
	}

	/**
	 * The calculated key map.
	 *
	 * @var array
	 */
	protected $keyMap = [];

	/**
	 * Get the internal cursor.
	 *
	 * @return int
	 */
	public function getCursor()
	{
		return $this->cursor;
	}

	/**
	 * Calculates the keymap for the collection.
	 *
	 * @return $this
	 */
	protected function calculateKeyMap()
	{
		$this->keyMap = array_keys( $this->collection->getArrayCopy() );

		return $this;
	}

	/**
	 * Returns the calculated keymap for the collection.
	 *
	 * @return array
	 */
	public function getKeyMap()
	{
		$this->calculateKeyMap();

		return $this->keyMap;
	}

	/**
	 * Returns key by given cursor position.
	 *
	 * @param int $cursor The cursor position to check.
	 *
	 * @return mixed
	 */
	public function getKey( $cursor )
	{
		$this->calculateKeyMap();
		if( !array_key_exists( $cursor, $this->keyMap ) ) {
			return null;
		}

		return $this->keyMap[ $cursor ];
	}

	/**
	 * Return the value of the current internal cursor position.
	 *
	 * @return mixed
	 */
	public function current()
	{
		return $this->collection->offsetGet( $this->getKey( $this->getCursor() ) );
	}

	/**
	 * Decrease the internal cursor by one.
	 */
	public function previous()
	{
		$this->cursor--;
	}

	/**
	 * Increase the internal cursor by one.
	 */
	public function next()
	{
		$this->cursor++;
	}

	/**
	 * Return the current key according to the internal cursor position.
	 *
	 * @return string|int|bool
	 */
	public function key()
	{
		return $this->getKey( $this->getCursor() );
	}

	/**
	 * Test if the current internal cursor position is valid.
	 *
	 * @return bool
	 */
	public function valid()
	{
		return !is_null( $this->key() );
	}

	/**
	 * Set the internal cursor to the first value in the array of data.
	 *
	 * @return mixed
	 */
	public function rewind()
	{
		$this->cursor = 0;
		$data         = $this->collection->getArrayCopy();
		reset( $data );
		$this->collection->exchangeArray( $data );

		return $this->current();
	}

	/**
	 * @return int
	 */
	public function count()
	{
		return $this->collection->count();
	}
}