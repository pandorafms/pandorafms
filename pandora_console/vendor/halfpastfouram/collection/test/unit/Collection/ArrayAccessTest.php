<?php

namespace Test\Collection;

use Halfpastfour\Collection\ArraySerializableInterface;
use Halfpastfour\Collection\Collection;
use Halfpastfour\Collection\CollectionInterface;
use Halfpastfour\Collection\Collection\ArrayAccess;
use Halfpastfour\Collection\Collection\ArrayAccessInterface;

/**
 * Class MyCollection
 * @package Test
 */
class MyArrayAccessCollection extends ArrayAccess
{
}

/**
 * @see http://stackoverflow.com/questions/4102777/php-random-shuffle-array-maintaining-key-value
 *
 * @param array $list
 *
 * @return array
 */
function shuffle_assoc( array $list )
{
	if( !is_array( $list ) ) return $list;

	$keys = array_keys( $list );
	shuffle( $keys );
	$random = [];
	foreach( $keys as $key ) {
		$random[ $key ] = $list[ $key ];
	}

	return $random;
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
	 * @var MyArrayAccessCollection
	 */
	private $collection;

	/**
	 * Create a new instance of a collection.
	 */
	public function setUp()
	{
		$this->collection = new MyArrayAccessCollection();
	}

	/**
	 * Test the correct implementation of classes and interfaces.
	 */
	public function testImplementation()
	{
		$this->assertInstanceOf( Collection::class, $this->collection );
		$this->assertInstanceOf( CollectionInterface::class, $this->collection );
		$this->assertInstanceOf( ArrayAccessInterface::class, $this->collection );
		$this->assertInstanceOf( \IteratorAggregate::class, $this->collection );
		$this->assertInstanceOf( ArraySerializableInterface::class, $this->collection );
	}

	/**
	 * Test if arrays can be exchanged.
	 */
	public function testArrayExchange()
	{
		$this->assertSame( [], $this->collection->exchangeArray( $this->data ) );
		$this->assertSame( $this->data, $this->collection->exchangeArray( [] ) );
		$this->assertSame( [], $this->collection->getArrayCopy() );
	}

	/**
	 * Test checking if offset exists.
	 */
	public function testOffsetExists()
	{
		$this->collection->exchangeArray( $this->data );

		$this->assertTrue( $this->collection->offsetExists( 10 ) );
		$this->assertTrue( $this->collection->offsetExists( 'foo' ) );
		$this->assertFalse( $this->collection->offsetExists( 'nonExisting' ) );
		$this->assertFalse( $this->collection->offsetExists( [ 1, 2, 3 ] ) );
		$this->assertFalse( $this->collection->offsetExists( new \stdClass() ) );
		$this->assertFalse( $this->collection->offsetExists( new \stdClass() ) );
	}

	/**
	 * Test getting values by offset.
	 */
	public function testOffsetGet()
	{
		$this->collection->exchangeArray( $this->data );

		foreach( $this->data as $key => $value ) {
			$this->assertSame( $value, $this->collection->offsetGet( $key ) );
			$this->assertSame( $value, $this->collection[ $key ] );
		}

		// Non existing offsets should return null
		$this->assertNull( $this->collection->offsetGet( 'nonExisting' ) );
		$this->assertNull( $this->collection['nonExisting'] );
	}

	/**
	 * Test setting values by offset.
	 */
	public function testOffsetSet()
	{
		$data = $this->data;
		shuffle_assoc( $data );

		foreach( $data as $key => $value ) {
			$this->assertSame( $this->collection, $this->collection->offsetSet( $key, $value ) );
		}

		$this->assertSame( $this->data, $this->collection->getArrayCopy() );

		// Empty the collection
		$this->collection->exchangeArray( [] );

		foreach( $data as $key => $value ) {
			$this->collection[ $key ] = $value;
		}

		$this->assertSame( $this->data, $this->collection->getArrayCopy() );
	}

	/**
	 * Test if values can be unset
	 */
	public function testOffsetUnset()
	{
		$this->collection->exchangeArray( $this->data );

		foreach( array_keys( $this->data ) as $key ) {
			$this->assertSame( $this->collection, $this->collection->offsetUnset( $key ) );
			$this->assertFalse( $this->collection->offsetExists( $key ) );
		}

		$this->assertSame( [], $this->collection->getArrayCopy() );

		// Reset the collection
		$this->collection->exchangeArray( $this->data );

		foreach( array_keys( $this->data ) as $key ) {
			unset( $this->collection[ $key ] );
			$this->assertFalse( $this->collection->offsetExists( $key ) );
		}

		$this->assertSame( [], $this->collection->getArrayCopy() );
	}

	/**
	 * Test usort method.
	 */
	public function testUsort()
	{
		$this->collection->exchangeArray( $this->data );
		$data    = $this->data;
		$closure = function ( $a, $b ) {
			return strnatcmp( $a, $b );
		};

		$this->assertSame( $this->collection, $this->collection->usort( $closure ) );
		usort( $data, $closure );

		$this->assertSame( $data, $this->collection->getArrayCopy() );
	}

	/**
	 * Test ksort method.
	 */
	public function testKsort()
	{
		$this->collection->exchangeArray( $this->data );
		$data = $this->data;

		$this->assertSame( $this->collection, $this->collection->ksort( SORT_DESC ) );
		ksort( $data, SORT_DESC );

		$this->assertSame( $data, $this->collection->getArrayCopy() );
	}

	/**
	 * Test count method.
	 */
	public function testCount()
	{
		$this->collection->exchangeArray( $this->data );

		$this->assertSame( count( $this->data ), $this->collection->count() );
	}

	/**
	 * Test getIterator method.
	 */
	public function testGetIterator()
	{
		$iterator	= $this->collection->getIterator();
		$this->assertInstanceOf( Collection\ArrayIterator::class, $iterator );
		$this->assertNotSame( $iterator, $this->collection->getIterator() );
	}
}