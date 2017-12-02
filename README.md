# jsondb

A PHP class which utilizes local JSON file as simple database. Fast and simple; useful for creating and reading logs quickly.

## Usage
```php
//INIT
include 'jsondb.php';
$db = new JsonDB();

// REMOVE ALL CONTENTS
$db->purge(); 

// INSERT DATA TO GIVEN PATHS
// Scalar values:
$db->write("/string", "test"); 
$db->write("/integer", 1);
$db->write("/boolean/true", true); 
// note: /boolean node does not need to exist; will be created on the way
$db->write("/boolean/false", false);
$db->write("/boolean/null", null);

// Arrays:
$db->write("/array", array("one", "two", "three"));
$db->write("/associativeArray", array("one" => "foo", "two" => "bar", "three" => "baz"));

// Nested structures:
$db->write("/nested/structure/test", "foo");
$db->write("/nested/anotherStructure", "bar");
$db->write("/nested/anotherStructure/arrayValue", "bar");
// note: anotherStructure will be rewritten to array, losing its content

// Complete objects:
$db->write("structure", (object) array(
            "nodeone" => array(
                "nodetwo" => array(),
                "nodethree" => "string",
                "nodefour" => (object) array(
                    "property" => "value"
                )
            ),
            "nodefive" => 1
        )
);

// PRINT DATA 
dump($db->read("/")); 
// note: insert any existing path
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
                        (
                        )

                    [nodethree] => string
                    [nodefour] => stdClass Object
                        (
                            [property] => value
                        )

                )

            [nodefive] => 1
        )

)
```
