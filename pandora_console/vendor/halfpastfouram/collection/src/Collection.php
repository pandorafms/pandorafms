<?php

namespace Halfpastfour\Collection;

/**
 * Class Collection
 * @package Halfpastfour\Collection
 */
abstract class Collection implements CollectionInterface, \IteratorAggregate, ArraySerializableInterface
{
	/**
	 * The internal set of data.
	 *
	 * @var array
	 */
	protected $data = [];

	/**
	 * Collection constructor.
	 *
	 * @param array|null $data
	 */
	public function __construct( array $data = null )
	{
		if( !is_null( $data ) ) {
			$this->data = $data;
		}
	}

	/**
	 * Add a value to the beginning of the set of data. This will change existing keys.
	 *
	 * @param mixed $value The value to prepend.
	 *
	 * @return $this
	 */
	public function prepend( $value )
	{
		array_unshift( $this->data, $value );

		return $this;
	}

	/**
	 * Add a value to the end of the set of data.
	 *
	 * @param mixed $value The value to append.
	 *
	 * @return $this
	 */
	public function append( $value )
	{
		array_push( $this->data, $value );

		return $this;
	}

	/**
	 * Shift an element off of the left of the collection.
	 *
	 * @return mixed
	 */
	public function trimLeft()
	{
		return array_shift( $this->data );
	}

	/**
	 * Pop an element off of the right of the collection.
	 *
	 * @return mixed
	 */
	public function trimRight()
	{
		return array_pop( $this->data );
	}

	/**
	 * Should return an array containing all values.
	 *
	 * @return array
	 */
	public function getArrayCopy()
	{
		return $this->data;
	}

	/**
	 * Should set the data for the collection and return the previous set of data.
	 *
	 * @param array $data
	 *
	 * @return array
	 */
	public function exchangeArray( array $data )
	{
		// Gather return data
		$returnArray = $this->getArrayCopy();
		// Set the new data
		$this->data = $data;

		return $returnArray;
	}

	/**
	 * Get (and create if not exists) an iterator.
	 *
	 * @return \ArrayIterator
	 */
	public function getIterator()
	{
		return new \ArrayIterator( $this->data );
	}
}