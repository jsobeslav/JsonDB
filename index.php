<?php

function dump($string) {
    echo "<pre>";
    print_r($string);
    echo "</pre>";
    echo "<br/>";
}

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