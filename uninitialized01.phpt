--TEST--
Uninitialized zval type
--FILE--
<?php
/**
 * Special constant (and zval type) called *uninitialized*, which invokes magic callback when accessing object's property
 *  with this value, which can initialize it. Useful for lazy-loading, e.g. in database layers.
 * Magic callbacks are:
 *  - method __initialize($propertyName) if defined within the class
 *  - global function __initialize($object, $propertyName) if not initialized by class' method
 *  - registered initializers by spl_initialize_register similar to spl_autoload_register
 *
 * This allows to create any lazy-loading logic needed, either class known how to initialize it's properties, or a common
 *  initializer (e.g. a database library) can be registered to lazy-load object's it has created.
 *
 * For isset() and empty(), *uninitialized* value behaves like null. Function isInitialized() is added which tests only
 *  if value is of type *uninitialized*.
 *
 *
 * See the examples below:
 */


// Dummy initializer
function dummy_initializer($obj, $propertyName) {
	echo "Initializing '$propertyName'\n";
	switch($propertyName) {
		case 'name':
		default:
			$obj->$propertyName = "dummy $propertyName $obj->id";
			break;

		case 'published':
			$obj->$propertyName = null;
			break;
	}
}
spl_initialize_register('dummy_initializer');


// Sample class
class Article {
	public $id;
	public $name;
	public $published;
}

// Partially initialized instance
function createArticle() {
	$article = new Article;
	$article->id = 1;
	$article->name = uninitialized;
	$article->published = uninitialized;
	return $article;
}

// direct work with uninitialized values
$x = uninitialized;
var_dump($x); // UNINITIALIZED
var_dump(isset($x)); // false
var_dump(empty($x)); // true
var_dump(isInitialized($x)); // false
echo "\n";



// Testing isInitialized language construct
$article = createArticle();
var_dump(isInitialized($article->id)); // true
var_dump(isInitialized($article->name)); // false
var_dump(isInitialized($article->published)); // false
echo "\n";

// not initialized byt isset
$article = createArticle();
var_dump(isset($article->name)); // false

// gets initialized by accessor
$article = createArticle();
var_dump($article->name); // "dummy name 1"

// gets initialized by reflection
$article = createArticle();
$prop = new ReflectionProperty('Article', 'name');
var_dump($prop->getValue($article)); // "dummy name 1"
?>
--EXPECTF--
UNINITIALIZED
bool(false)
bool(true)
bool(false)

bool(true)
bool(false)
bool(false)

bool(false)
Initializing 'name'
string(%d) "dummy name 1"
Initializing 'name'
string(%d) "dummy name 1"
