<?php

namespace Artica\PHPChartJS\Collection;

/**
 * Interface ArraySerializableInterface
 * @package Artica\PHPChartJS\Collection
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