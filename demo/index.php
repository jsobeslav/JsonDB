<?php

function dump($string)
{
	echo "<pre>";
	print_r($string);
	echo "</pre>";
	echo "<br/>";
}

//INIT
include '../JsonDB.php';
$db = new JsonDB("example");

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
$db->write("/array", ["one", "two", "three"]);
$db->write("/associativeArray", ["one" => "foo", "two" => "bar", "three" => "baz"]);

// Nested structures:
$db->write("/nested/structure/test", "foo");
$db->write("/nested/anotherStructure", "bar");
$db->write("/nested/anotherStructure/arrayValue", "bar");
// note: anotherStructure will be rewritten to array, losing its content

// Complete objects:
$db->write("structure", (object) [
	"nodeone"  => [
		"nodetwo"   => [],
		"nodethree" => "string",
		"nodefour"  => (object) [
			"property" => "value",
		],
	],
	"nodefive" => 734
]
);

// PRINT DATA
echo 'All data:<br/>';
dump($db->read("/"));
// note: insert any existing path
echo '/nested/structure:<br/>';
dump( $db->read("/nested/structure") );

// SEARCH DATA for scalar value index
echo 'Search results:<br/>';

// Strict type searches

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

// Medium strength searches
$db->setSearchStrenght(JsonDB::SEARCH_STRENGTH_COMPARE);
echo 'Medium strength search for "foo":<br/>';
dump($db->searchAll("foo"));
echo 'Medium strength search for 1:<br/>';
dump($db->searchAll(1));
echo 'Medium strength search for "1":<br/>';
dump($db->searchAll("1"));
echo 'Medium strength search for true:<br/>';
dump($db->searchAll(true));
echo 'Medium strength search for false:<br/>';
dump($db->searchAll(false));
echo 'Medium strength search for null:<br/>';
dump($db->searchAll(null));

// Substring search
echo 'Medium strength search for "ba":<br/>';
dump($db->searchAll("ba"));
$db->setSearchStrenght(JsonDB::SEARCH_STRENGTH_CONTAINS);
echo 'Partial search for "ba":<br/>';
dump($db->searchAll("ba"));