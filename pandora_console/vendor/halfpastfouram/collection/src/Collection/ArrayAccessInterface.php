<?php

namespace Halfpastfour\Collection\Collection;

/**
 * Interface ArrayAccessInterface
 * @package Halfpastfour\Collection\Collection
 */
interface ArrayAccessInterface
{
	/**
	 * Should set the data for the collection and return the previous set of data.
	 *
	 * @param array $data
	 *
	 * @return array
	 */
	public function exchangeArray( array $data );

	/**
	 * Should perform the php function usort on the dataset.
	 *
	 * @param \Closure $callback
	 *
	 * @return $this
	 */
	public function usort( \Closure $callback );

	/**
	 * Should perform the php function ksort on the dataset.
	 *
	 * @return $this
	 */
	public function ksort();

	/**
	 * Count the rows of data the collection contains.
	 *
	 * @return int
	 */
	public function count();
}