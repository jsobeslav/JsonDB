<?php

// Trigger error when the 'database connection' couldn't be created
// Notably when the filepath is invalid
function JsonDB_warning_handler($errno, $errstr)
{
	trigger_error("Encountered error while initializing JsonDB: $errstr", E_USER_ERROR);
}

class JsonDB
{

	private $database;  // database file name
	private $stream;    // php file stream
	private $content;   // current database content

	// LOW LEVEL FUNCTIONS
	// open or close the stream
	function __construct($filename = "default", $filepath = "./db/")
	{
		set_error_handler("JsonDB_warning_handler", E_WARNING);
		// create database file if neccessary
		if (! file_exists($filepath)) {
			mkdir($filepath, 0755, true);
		}
		$this->database = "$filepath/$filename.json";
		if (! file_exists($this->database)) {
			$this->purge();
			$this->write_file();
		}
		restore_error_handler();

		// read initial database content
		$this->read_file();
	}

	private function open_stream($access = "a")
	{
// open_stream the stream
		try {
			$this->stream = fopen($this->database, $access);
		} catch (Exception $e) {
			throw $e;
		}
	}

	private function close_stream()
	{
// close the stream
		if (! empty($this->stream)) {
			fclose($this->stream);
		}
	}

	// MEDIUM LEVEL FUNCTIONS
	// manipulate file contents
	private function write_file()
	{
// copy current content to file
		$this->open_stream("w");
		fwrite($this->stream, json_encode($this->content, JSON_UNESCAPED_UNICODE || JSON_NUMERIC_CHECK));
		$this->close_stream();
	}

	private function read_file()
	{
// get file to current content
		$this->open_stream("r");
		$file_content  = fread($this->stream, filesize($this->database) + 1);
		$this->content = json_decode($file_content, JSON_UNESCAPED_UNICODE || JSON_NUMERIC_CHECK);
		$this->close_stream();
	}

	// HIGH LEVEL FUNCTIONS
	// provide functions to navigate through JSON and modify its content
	private function explode_path($path)
	{
// get array of path nodes
		$nodes = explode("/", $path);
		foreach ($nodes as $index => $node) {
			if (empty($node)) {
				unset($nodes[$index]);
			}
		}

		return $nodes;
	}

	private function write_node($active_node, $nodes, $value)
	{
		$active_node = (array) $active_node;
// recursive function that iterate over JSON and writes value on given path
		if (sizeof($nodes) <= 0) {
// if we reached the destination, write it
			$active_node = $value;
		} else {
// else we need to go deeper
			$next_index = array_values($nodes)[0];
			$nodes      = array_slice($nodes, 1, sizeof($nodes) - 1);

// if the next index does not exist, create array
			if (is_scalar($active_node)) {
				$backup        = $active_node;
				$active_node   = [];
				$active_node[] = $backup;
			}
			if (! isset($active_node[$next_index]) || is_scalar($active_node[$next_index])) {
				$active_node[$next_index] = [];
			}
			$active_node[$next_index] = $this->write_node($active_node[$next_index], $nodes, $value);
		}

		return $active_node;
	}

	private function read_node($active_node, $nodes)
	{
// recursive function that iterate over JSON and retrieves value on given path
		if (sizeof($nodes) <= 0) {
// if we reached the destination, return its content
			return $active_node;
		} else {
// else we need to go deeper
			$next_index = array_values($nodes)[0];
			$nodes      = array_slice($nodes, 1, sizeof($nodes) - 1);

// if the next index does not exist, return null
			if (empty($active_node[$next_index])) {
				return null;
			}

			return $this->read_node($active_node[$next_index], $nodes);
		}

		return $active_node;
	}

	// PUBLIC USER FUNCTIONS
	public function purge()
	{
		$this->content = (object) [];
		$this->write_file();
	}

	public function write($path, $value)
	{
// write contents of given node
		$nodes         = $this->explode_path($path);
		$this->content = $this->write_node($this->content, $nodes, $value);
		$this->write_file();
	}

	public function read(string $path)
	{
// read contents of given node
		$nodes = $this->explode_path($path);

		return $this->read_node($this->content, $nodes);
	}

	/**
	 *
	 * SEARCH
	 *
	 */

	/** @var int $searchStrength : type of comparison used in search functions */
	private $searchStrenght = 3;

	/** @var int SEARCH_STRENGTH_CONTAINS : scalar property contains needle; probably just for strings */
	public const SEARCH_STRENGTH_CONTAINS = 1;

	/** @var int SEARCH_STRENGHT_COMPARE : == operator */
	public const SEARCH_STRENGTH_COMPARE = 2;

	/** @var int SEARCH_STRENGHT_STRICT_TYPE : === operator */
	public const SEARCH_STRENGTH_STRICT_TYPE = 3;

	/**
	 * @var searchStrength setter
	 *
	 * @param int $strenght
	 *
	 * @return void
	 */
	public function setSearchStrenght(int $strenght): void
	{
		$this->searchStrenght = $strenght;
	}

	/**
	 * Single comparison for @method recursiveSearch
	 *
	 * @param string|integer|bool|null $property : any scalar property value
	 * @param string|integer|bool|null $needle   : any scalar searched value
	 *
	 * @return bool
	 *
	 * @throws Exception : when invalid @var searchStrenght
	 */
	private function compareSearchValues($property, $needle): bool
	{
		switch ($this->searchStrenght) {
			case 1: // contains
				$stringProperty = (string) $property;
				$stringNeedle   = (string) $needle;
				$stringPosition = strpos($stringProperty, $stringNeedle);

				return ($stringPosition !== false);
			case 2: // compare
				return $property == $needle;
			case 3: // strict type compare
				return $property === $needle;
			default:
				throw new Exception('Invalid search strength');
		}
	}

	/**
	 * Performs recursive search for first occurrence of scalar value in structure
	 *
	 * Each scalar structure property is compared to $needle, and if there is a match, full path to the property is
	 * returned
	 *
	 * For non-scalar properties, recursion call is performed
	 *
	 * @param string|integer|bool|null $needle        : searched variable; any scalar value
	 * @param array                    $struct        : structure upon which the search is performed
	 *                                                $this->content in first recursion, later any non-scalar property
	 * @param array                    $ignorePaths   : do not return any of theese paths as success
	 *                                                required mostly for @method searchAll
	 * @param string                   $iterationPath : current path position
	 *                                                required for recursions, but not for first call
	 *
	 * @return bool|string : false if $needle was not found, first string path otherwise
	 *
	 * @throws Exception : when $needle is not scalar
	 */
	private function recursiveSearch($needle, array $struct, array $ignorePaths = [], string $iterationPath = '')
	{
		// argument check
		if (! is_scalar($needle) && ! is_null($needle)) {
			throw new \Exception('Search value not scalar');
		}

		// iterate over all properties
		foreach ($struct as $propertyKey => $property) {
			// increment propertyPath
			$propertyPath = $iterationPath . '/' . $propertyKey;

			if (! is_array($property) && ! is_object($property)) {
				// if the value is scalar, do the check

				$comparison = $this->compareSearchValues($property, $needle);

				if ($comparison !== false) {
					// if found...
					if (! in_array($propertyPath, $ignorePaths)) {
						// ... and if this path is not explicitly ignored
						// return the success
						return $propertyPath;
					}
				}
				// else the search continues...
			} else {
				// if the value is array, continue search recursively

				$childSearch = $this->recursiveSearch($needle, (array) $property, $ignorePaths, $propertyPath);
				if ($childSearch) {
					// if child search was successful ...
					if (! in_array($childSearch, $ignorePaths)) {
						// ... and if this path is not explicitly ignored
						// report the success back to parent
						return $childSearch;
					}
				}
				// else the search continues...
			}
		}

		return false;
	}

	/**
	 * Public interface for @method recursiveSearch
	 *
	 * @param string|integer|bool|null $needle : searched variable; any scalar value
	 *
	 * @return bool|string : false if $needle was not found, first string path otherwise
	 */

	public function search($needle): string
	{
		$searchResult = $this->recursiveSearch($needle, (array) $this->content);

		return $searchResult;
	}

	/** @var SEARCH_MAX_ITERATIONS : Safe threshold for @method searchAll */
	private const SEARCH_MAX_ITERATIONS = 100;

	/**
	 * Performs a iterative search for instances of scalar value
	 *
	 * Repeats @method recursiveSearch with growing list of found (ignored) paths, until no new path is found.
	 * Then, returns that list.
	 *
	 * @param string|integer|bool|null $needle      : searched variable; any scalar value
	 * @param integer|null             $resultCount : (optional) overrides standard SEARCH_MAX_ITERATIONS constant
	 *
	 * @return array : array of strings (paths containing the scalar value)
	 *
	 * @throws Exception : when suspicious number of iterations was performed
	 */
	public function searchAll($needle, $resultCount = null): array
	{
		$occurrences = [];
		$allFound    = false;
		$safeCounter = 0;

		while (! $allFound) {
			$searchResult = $this->recursiveSearch($needle, (array) $this->content, $occurrences);

			if ($searchResult === false) {
				// nothing new was found, end the cycle
				$allFound = true;
			} else {
				// new path found: append it to occurrence list
				$occurrences[] = $searchResult;

				// if there was suspicious count of iterations performed, throw exception
				$safeCounter++;
				if ($safeCounter >= ($resultCount ?? self::SEARCH_MAX_ITERATIONS)) {
					throw new \Exception('Suspicious number of iterations performed');
				}
			}
		}

		return $occurrences;
	}
}
