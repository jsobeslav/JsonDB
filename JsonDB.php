<?php

// Trigger error when the 'database connection' couldn't be created
// Notably when the filepath is invalid
function JsonDB_warning_handler($errno, $errstr) {
    trigger_error("Encountered error while initializing JsonDB: $errstr", E_USER_ERROR);
}

class JsonDB {

    private $database;  // database file name
    private $stream;    // php file stream
    private $content;   // current database content

    // LOW LEVEL FUNCTIONS 
    // open or close the stream
    function __construct($filename = "default", $filepath = "./db/") {
        set_error_handler("JsonDB_warning_handler", E_WARNING);
        // create database file if neccessary
        if (!file_exists($filepath)) {
            mkdir($filepath, 0755, true);
        }
        $this->database = "$filepath/$filename.json";
        if (!file_exists($this->database)) {
            $this->purge();
            $this->write_file();
        }
        restore_error_handler();

        // read initial database content 
        $this->read_file();
    }

    private function open_stream($access = "a") {
// open_stream the stream 
        try {
            $this->stream = fopen($this->database, $access);
        } catch (Exception $e) {
            throw $e;
        }
    }

    private function close_stream() {
// close the stream
        if (!empty($this->stream)) {
            fclose($this->stream);
        }
    }

    // MEDIUM LEVEL FUNCTIONS
    // manipulate file contents
    private function write_file() {
// copy current content to file
        $this->open_stream("w");
        fwrite($this->stream, json_encode($this->content, JSON_UNESCAPED_UNICODE || JSON_NUMERIC_CHECK));
        $this->close_stream();
    }

    private function read_file() {
// get file to current content
        $this->open_stream("r");
        $file_content = fread($this->stream, filesize($this->database) + 1);
        $this->content = json_decode($file_content, JSON_UNESCAPED_UNICODE || JSON_NUMERIC_CHECK);
        $this->close_stream();
    }

    // HIGH LEVEL FUNCTIONS
    // provide functions to navigate through JSON and modify its content
    private function explode_path($path) {
// get array of path nodes
        $nodes = explode("/", $path);
        foreach ($nodes as $index => $node) {
            if (empty($node)) {
                unset($nodes[$index]);
            }
        }
        return $nodes;
    }

    private function write_node($active_node, $nodes, $value) {
        $active_node = (array) $active_node;
// recursive function that iterate over JSON and writes value on given path
        if (sizeof($nodes) <= 0) {
// if we reached the destination, write it
            $active_node = $value;
        } else {
// else we need to go deeper
            $next_index = array_values($nodes)[0];
            $nodes = array_slice($nodes, 1, sizeof($nodes) - 1);

// if the next index does not exist, create array
            if (is_scalar($active_node)) {
                $backup = $active_node;
                $active_node = [];
                $active_node[] = $backup;
            }
            if (!isset($active_node[$next_index]) || is_scalar($active_node[$next_index])) {
                $active_node[$next_index] = [];
            }
            $active_node[$next_index] = $this->write_node($active_node[$next_index], $nodes, $value);
        }
        return $active_node;
    }

    private function read_node($active_node, $nodes) {
// recursive function that iterate over JSON and retrieves value on given path
        if (sizeof($nodes) <= 0) {
// if we reached the destination, return its content
            return $active_node;
        } else {
// else we need to go deeper
            $next_index = array_values($nodes)[0];
            $nodes = array_slice($nodes, 1, sizeof($nodes) - 1);

// if the next index does not exist, return null
            if (empty($active_node[$next_index])) {
                return null;
            }
            return $this->read_node($active_node[$next_index], $nodes);
        }
        return $active_node;
    }

    // PUBLIC USER FUNCTIONS
    public function purge() {
        $this->content = (object) array();
        $this->write_file();
    }

    public function write($path, $value) {
// write contents of given node
        $nodes = $this->explode_path($path);
        $this->content = $this->write_node($this->content, $nodes, $value);
        $this->write_file();
    }

    public function read($path) {
// read contents of given node
        $nodes = $this->explode_path($path);
        return $this->read_node($this->content, $nodes);
    }

}
