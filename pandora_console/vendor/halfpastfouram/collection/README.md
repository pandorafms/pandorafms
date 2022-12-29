[![TeamCity Build Status](http://halfpastfour.am:8111/app/rest/builds/buildType:(id:Collection_UnitTesting)/statusIcon)](http://halfpastfour.am:8111/viewType.html?buildTypeId=Collection_UnitTesting)
[![Build Status](https://travis-ci.org/halfpastfouram/collection.svg?branch=master)](https://travis-ci.org/halfpastfouram/collection)
[![Code Climate](https://codeclimate.com/github/halfpastfouram/collection/badges/gpa.svg)](https://codeclimate.com/github/halfpastfouram/collection)
[![Test Coverage](https://codeclimate.com/github/halfpastfouram/collection/badges/coverage.svg)](https://codeclimate.com/github/halfpastfouram/collection/coverage)
[![Total Downloads](https://poser.pugx.org/halfpastfouram/collection/d/total.png)](https://packagist.org/packages/halfpastfouram/collection)
[![Latest Stable Version](https://poser.pugx.org/halfpastfouram/collection/v/stable.png)](https://packagist.org/packages/halfpastfouram/collection)

# Collection
A flexible PHP Collection complete with custom Iterator.

This library is still in active development and will be updated until it is deemed completed.

## What can you do with a collection?
A collection is a tool you can use to have a certain level of control over the data you store inside it. Where you could use an array in most situations a collection provides a more flexible way to deal with your data.

It is particularly useful to extend this class if you need to perform actions on a list of items or objects when they are added, removed, replaced or otherwise modified.

## Control over collections
You can traverse all objects that extend the `Collection` class. To give you more flexibility, all collections in this project extends the `Collection\ArrayAccess` class which provides direct access as if you were talking to an array. This class also provides an iterator that can be used in loops or even manually.

### Array access example

````php
// Assuming MyCollection extends Halfpastfouram\Collection\ArrayAccess
$collection = new MyCollection();
$collection[] = 0;
$collection[5] = 12;
````

### Traversing

````php
foreach( $collection as $key => $value ) {
    var_dump( $key, $value );
}
````

### Manual traversing

````php
$collection = new MyCollection();
$iterator = $collection->getIterator();

// Jump forward to next position
$iterator->next();
var_dump( $iterator->current() );

// Go back one position
$iterator->previous();
var_dump( $iterator->getKey(), $iterator->current() );

// Receive the list of keys in the dataset.
var_dump( $iterator->calculateKeyMap() );
````

## Installation

### Using composer
    $ composer require halfpastfouram/collection dev-master

### Development
This project uses composer, which should be installed on your system. Most
Linux systems have composer available in their PHP packages.
Alternatively you can download composer from [getcomposer.org](http://getcomposer.org).

If you use the PhpStorm IDE then you can simply init composer through the IDE. However,
full use requires the commandline. See PhpStorm help, search for composer.

To start development, do `composer install` from the project directory. 

**Remark** Do not use `composer update` unless you changed the dependency requirements in composer.json.
The difference is that `composer install` will use composer.lock read-only, 
while `composer update` will update your composer.lock file regardless of any change.
As the composer.lock file is committed to the repo, other developers might conclude 
dependencies have changed, while they have not.
