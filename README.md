# JsonDB

A PHP class which utilizes local JSON file as simple database. Fast and simple; is useful for creating and reading logs quickly, but lacks any kind of security.

## Usage
### Initialize
```php
// INIT OBJECT
include 'JsonDB.php';
$db = new JsonDB();
```

### Drop contents
```php
// REMOVE ALL CONTENTS
$db->purge(); 
```

### Write contents
```php
// INSERT DATA TO GIVEN PATHS
// Scalar values:
$db->write("/string", "test");
$db->write("/integer", 1);
$db->write("/boolean/true", true);
// note: /boolean node does not need to exist beforehand; will be created on the way
$db->write("/boolean/false", false);
$db->write("/boolean/null", null);

// Arrays:
$db->write("/array", array("one", "two", "three"));
$db->write("/associativeArray", array("one" => "foo", "two" => "bar", "three" => "baz"));

// Nested structures:
$db->write("/nested/structure/test", "foo");
$db->write("/nested/anotherStructure", "bar");
$db->write("/nested/anotherStructure/arrayValue", "bar");
// note: anotherStructure will be rewritten to array, losing its content ("bar" string)

// Complete objects:
$db->write("/structure", (object) array(
            "nodeone" => array(
                "nodetwo" => array(),
                "nodethree" => "string",
                "nodefour" => (object) array(
                    "property" => "value"
                )
            ),
            "nodefive" => 735
        )
);
```

### Read

```php
// PRINT DATA
// Print all database contents
print_r( $db->read("/") );
```

Results:

```
Array
(
    [string] => test
    [integer] => 1
    [boolean] => Array
        (
            [true] => 1
            [false] => 
            [null] => 
        )

    [array] => Array
        (
            [0] => one
            [1] => two
            [2] => three
        )

    [associativeArray] => Array
        (
            [one] => foo
            [two] => bar
            [three] => baz
        )

    [nested] => Array
        (
            [structure] => Array
                (
                    [test] => foo
                )

            [anotherStructure] => Array
                (
                    [arrayValue] => bar
                )

        )

    [structure] => stdClass Object
        (
            [nodeone] => Array
                (
                    [nodetwo] => Array
  
                        )

                    [nodethree] => string
                    [nodefour] => stdClass Object
                        (
                            [property] => value
                        )

                )

            [nodefive] => 735
        )

)
```

```php
// Print /nested/structure node data
print_r( $db->read("/nested/structure") );
```

Results:
```
Array
(
    [test] => foo
)
```

### Search


Moreover, you can now use search function:

```php
// SEARCH DATA for scalar value index
echo 'Search results:<br/>';

// Strict type comparison (===) search (default setting)
echo 'Strict search for "foo":<br/>';
dump($db->searchAll("foo"));
echo 'Strict search for 1:<br/>';
dump($db->searchAll(1));
echo 'Strict search for "1":<br/>';
dump($db->searchAll("1"));
echo 'Strict search for true:<br/>';
dump($db->searchAll(true));
echo 'Strict search for false:<br/>';
dump($db->searchAll(false));
echo 'Strict search for null:<br/>';
dump($db->searchAll(null));

// Medium strenght comparison (==) search
$db->setSearchStrenght(JsonDB::SEARCH_STRENGTH_COMPARE);
echo 'Medium strength search for 1:<br/>';
dump($db->searchAll(1));

// Partial substring search
$db->setSearchStrenght(JsonDB::SEARCH_STRENGTH_CONTAINS);
echo 'Partial search for "ba":<br/>';
dump($db->searchAll("ba"));
```


Should return:

```
Search results:
Strict search for "foo":
Array
(
    [0] => /associativeArray/one
    [1] => /nested/structure/test
)

Strict search for 1:
Array
(
    [0] => /integer
)

Strict search for "1":
Array
(
)

Strict search for true:
Array
(
    [0] => /boolean/true
)

Strict search for false:
Array
(
    [0] => /boolean/false
)

Strict search for null:
Array
(
    [0] => /boolean/null
)


Medium strength search for 1:
Array
(
    [0] => /integer
    [1] => /boolean/true
)


Partial search for "ba":
Array
(
    [0] => /associativeArray/two
    [1] => /associativeArray/three
    [2] => /nested/anotherStructure/arrayValue
)
```