<?php
function dump($string){
    echo "<pre>";
    print_r($string);
    echo "</pre>";
    echo "<br/>";
}

include 'jsondb.php'; // init
$db = new JsonDB(); 

$db->purge(); // remove all contents

$db->write("/string", "test"); // insert data to given path
$db->write("/integer", 1);
$db->write("/array", array("one", "two", "three"));
$db->write("/assocArray", array("one" => "foo", "two" => "bar", "three" => "baz"));
$db->write("/nested/structure/test", "foo");
$db->write("/nested/anotherStructure", "bar");
$db->write("/nested/anotherStructure/rewriteWithArray", "bar"); // anotherStructure will change to array, losing its content

dump($db->read("/")); // print data from given path