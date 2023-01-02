<?php

namespace Halfpastfour\Collection\Collection;

use Halfpastfour\Collection\Collection;

/**
 * Class ArrayAccess
 * @package Halfpastfour\Collection\Collection
 */
class ArrayAccess extends Collection implements \ArrayAccess, \Countable, ArrayAccessInterface
{
	/**
	 * Check if the given offset exists in the set of data.
	 *
	 * @param mixed $offset The offset to check.
	 *
	 * @return bool
	 */
	public function offsetExists( $offset )
	{
		// Can not retrieve a key based on a value other than a string, integer or boolean
		if( !is_string( $offset ) && !is_int( $offset ) && !is_bool( $offset ) ) {
			return false;
		}

		return array_key_exists( $offset, $this->data );
	}

	/**
	 * Get a value from the given offset.
	 *
	 * @param mixed $offset The offset to get the value from.
	 *
	 * @return mixed
	 */
	public function offsetGet( $offset )
	{
		if( $this->offsetExists( $offset ) ) {
			return $this->data[ $offset ];
		}

		return null;
	}

	/**
	 * Set a value at the given offset.
	 *
	 * @param mixed $offset The offset to set the value at.
	 * @param mixed $value  The value to set.
	 *
	 * @return $this
	 */
	public function offsetSet( $offset, $value )
	{
		$this->data[ $offset ] = $value;

		return $this;
	}

	/**
	 * Unset a value at the given offset. Does nothing if the offset is not found.
	 *
	 * @param mixed $offset The offset to unset the value from.
	 *
	 * @return $this
	 */
	public function offsetUnset( $offset )
	{
		if( $this->offsetExists( $offset ) ) {
			unset( $this->data[ $offset ] );
		}

		return $this;
	}

	/**
	 * Provide a callback to use for sorting the data.
	 *
	 * @param \Closure $callback The callback to be used.
	 *
	 * @return $this
	 */
	public function usort( \Closure $callback )
	{
		usort( $this->data, $callback );
		reset( $this->data );

		return $this;
	}

	/**
	 * Sort the data by key.
	 *
	 * @param int $sortFlags Optional flags to provide to ksort.
	 *
	 * @return $this
	 */
	public function ksort( $sortFlags = null )
	{
		ksort( $this->data, $sortFlags );

		return $this;
	}

	/**
	 * Count the rows of data the collection contains.
	 *
	 * @return int
	 */
	public function count()
	{
		return count( $this->data );
	}

	/**
	 * @return ArrayIterator
	 */
	public function getIterator()
	{
		return new ArrayIterator( $this );
	}
}