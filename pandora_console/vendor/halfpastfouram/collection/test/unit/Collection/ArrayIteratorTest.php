<?php

namespace Test\Collection;

use Halfpastfour\Collection\Collection\ArrayAccess;
use Halfpastfour\Collection\Collection\ArrayIterator;

/**
 * Class ArrayIteratorTest
 * @package Test\Collection
 */
class ArrayIteratorTest extends \PHPUnit_Framework_TestCase
{
	/**
	 * Array of data used for testing.
	 *
	 * @var array
	 */
	private $data = [ 'foo' => 'bar', 2, 3, 4, 5.0, 10 => true ];

	/**
	 * @var ArrayIterator
	 */
	private $iterator;

	/**
	 * Create a new instance of a collection.
	 */
	public function setUp()
	{
		$collection		= new ArrayAccess();
		$collection->exchangeArray( $this->data );

		$this->iterator = new ArrayIterator( $collection );
	}

	/**
	 * Test the getKeyMap method.
	 */
	public function testKeyMap()
	{
		$this->assertSame( array_keys( $this->data ), $this->iterator->getKeyMap() );
	}

	/**
	 * Test the getKey method.
	 */
	public function testGetKey()
	{
		foreach( array_keys( $this->data ) as $index => $key ) {
			$this->assertSame( $key, $this->iterator->getKey( $index ) );
		}

		$this->assertNull( $this->iterator->getKey( -1 ) );
	}

	/**
	 * Test the current method.
	 */
	public function testCurrent()
	{
		foreach( $this->data as $value ) {
			$this->assertSame( $value, $this->iterator->current() );
			$this->iterator->next();
		}

		$this->assertNull( $this->iterator->current() );
	}

	/**
	 * Test the rewind method.
	 */
	public function testRewind()
	{
		for( $i = 0; $i < $this->iterator->count() - 1; $i++ ) {
			$this->iterator->next();
		}

		$data = $this->data;
		$this->assertSame( end( $data ), $this->iterator->current() );
		$this->assertSame( $data['foo'], $this->iterator->rewind() );
	}

	/**
	 * Test manual traversing using available methods.
	 */
	public function testManualTraverse()
	{
		$this->assertSame( 0, $this->iterator->getCursor() );
		$this->assertSame( 'foo', $this->iterator->key() );

		$this->iterator->next();
		$this->assertSame( 1, $this->iterator->getCursor() );
		$this->assertTrue( $this->iterator->valid() );
		$this->assertSame( 0, $this->iterator->key() );

		$this->iterator->previous();
		$this->assertSame( 0, $this->iterator->getCursor() );
		$this->assertTrue( $this->iterator->valid() );
		$this->assertSame( 'foo', $this->iterator->key() );
	}
}