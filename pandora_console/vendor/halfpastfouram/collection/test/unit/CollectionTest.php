<?php

namespace Test;

use \Halfpastfour\Collection\ArraySerializableInterface;
use \Halfpastfour\Collection\Collection;
use \Halfpastfour\Collection\CollectionInterface;

/**
 * Class MyCollection
 * @package Test
 */
class MyCollection extends Collection
{
}

/**
 * Class CollectionTest
 * @package Test
 */
class CollectionTest extends \PHPUnit_Framework_TestCase
{
	/**
	 * Array of data used for testing.
	 *
	 * @var array
	 */
	private $data = [ 'foo' => 'bar', 2, 3, 4, 5.0, 10 => true ];

	/**
	 * Test the constructor.
	 */
	public function testConstructor()
	{
		$collection = new MyCollection();
		$this->assertSame( [], $collection->getArrayCopy() );

		$collection = new MyCollection( $this->data );
		$this->assertSame( $this->data, $collection->getArrayCopy() );
	}

	/**
	 * Test the correct implementation of classes and interfaces.
	 */
	public function testImplementation()
	{
		$collection = new MyCollection();
		$this->assertInstanceOf( Collection::class, $collection );
		$this->assertInstanceOf( CollectionInterface::class, $collection );
		$this->assertInstanceOf( \IteratorAggregate::class, $collection );
		$this->assertInstanceOf( ArraySerializableInterface::class, $collection );
	}

	/**
	 *
	 */
	public function testArrayExchange()
	{
		$collection = new MyCollection();
		$this->assertSame( [], $collection->exchangeArray( $this->data ) );
		$this->assertSame( $this->data, $collection->exchangeArray( [] ) );
		$this->assertSame( [], $collection->getArrayCopy() );
	}

	/**
	 * Test if a value can be appended.
	 */
	public function testAppend()
	{
		$collection = new MyCollection( $this->data );
		$data       = $this->data;
		array_push( $data, 'Bar' );

		$this->assertInstanceOf( Collection::class, $collection->append( 'Bar' ) );
		$this->assertSame( $data, $collection->getArrayCopy() );
	}

	/**
	 * Test if a value can be prepended.
	 */
	public function testPrepend()
	{
		$collection = new MyCollection( $this->data );
		$data       = $this->data;
		array_unshift( $data, 'Foo' );

		$this->assertInstanceOf( Collection::class, $collection->prepend( 'Foo' ) );
		$this->assertSame( $data, $collection->getArrayCopy() );
	}

	/**
	 * Test if trimming left and right values.
	 */
	public function testTrim()
	{
		$collection = new MyCollection( $this->data );

		// Trim left side
		$data      = $this->data;
		$leftValue = array_shift( $data );
		$this->assertSame( $leftValue, $collection->trimLeft() );
		$this->assertSame( $data, $collection->getArrayCopy() );

		// ... and again
		$leftValue = array_shift( $data );
		$this->assertSame( $leftValue, $collection->trimLeft() );
		$this->assertSame( $data, $collection->getArrayCopy() );

		// Trim right side
		$rightValue = array_pop( $data );
		$this->assertSame( $rightValue, $collection->trimRight() );
		$this->assertSame( $data, $collection->getArrayCopy() );

		// ... and again
		$rightValue = array_pop( $data );
		$this->assertSame( $rightValue, $collection->trimRight() );
		$this->assertSame( $data, $collection->getArrayCopy() );
	}

	/**
	 * Test if the class is traversable.
	 */
	public function testIterator()
	{
		$collection = new MyCollection( $this->data );
		$iterator   = $collection->getIterator();
		$this->assertInstanceOf( \ArrayIterator::class, $iterator );

		$values = array_values( $this->data );
		$keys   = array_keys( $this->data );
		$index  = 0;
		foreach( $collection as $key => $value ) {
			$this->assertSame( $key, $keys[ $index ] );
			$this->assertSame( $value, $values[ $index ] );
			$index++;
		}
	}
}