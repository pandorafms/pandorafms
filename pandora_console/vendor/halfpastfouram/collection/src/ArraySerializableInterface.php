<?php

namespace Halfpastfour\Collection;

/**
 * Interface ArraySerializableInterface
 * @package Halfpastfour\Collection
 */
interface ArraySerializableInterface
{
	/**
	 * Should return an array containing all values.
	 *
	 * @return array
	 */
	public function getArrayCopy();
}