<?php

require_once('../config/config.php');

// -----------------------------------------------------------------------------
// Entities
//  An entity is either a problem or a directory of entities
// -----------------------------------------------------------------------------

class Entity {
	private $_parent;     // parent entity
	private $_dir_name;   // dir name (relative to parent)
	private $_path;       // full path (relative to data directory), ends in '/'
	private $_attributes; // key=>value pairs specified in "dir.conf" or NULL if not initialized
	private $_children;   // children array or NULL if not initialized
	
	// ---------------------------------------------------------------------
	// Construction: singleton based
	// ---------------------------------------------------------------------
	
	// the root entity
	static function get_root() {
		static $root;
		if (!isset($root)) $root = new Entity(NULL,'');
		return $root;
	}
	
	// singleton constructor for Entities
	static function get($path) {
		$parts = explode('/',$path);
		$here  = Entity::get_root();
		foreach ($parts as $part) {
			if ($part == '') continue;
			$here = $here->get_child($part);
			if ($here === NULL) {
				throw new Exception("Entity not found: $path");
			}
		}
		return $here;
	}
	
	private function __construct($parent, $dir_name) {
		$parent_path = isset($parent) ? $parent->_path : '';
		$this->_parent   = $parent;
		$this->_dir_name = $dir_name;
		$this->_path     = $parent_path . $dir_name . '/';
	}
	
	// ---------------------------------------------------------------------
	// Some simple attributes / properties
	// ---------------------------------------------------------------------
	
	// is this the root?
	function is_root() {
		return $this->_parent === NULL;
	}
	
	// get full path
	function path() {
		return $this->_path;
	}
	
	// ---------------------------------------------------------------------
	// Children
	// ---------------------------------------------------------------------
	
	// gives an array of child entities
	function children() {
		$this->load_children();
		return $this->_children;
	}
	// gets a single child entity, if it exists (null otherwise)
	function get_child($name) {
		$this->load_children(); // a bit overkill
		return isset($this->_children[$name]) ? $this->_children[$name] : NULL;
	}
	
	// load the directory listing of children
	private function load_children() {
		if (isset($this->_children)) return;
		$this->_children = array();
		foreach (new DirectoryIterator($this->data_path()) as $child) {
			if ($child->isDot() || !$child->isDir()) continue;
			// TODO: strip hidden files?
			$this->_children[$child->getFilename()] = new Entity($this, $child->getFilename());
		}
	}
	
	// ---------------------------------------------------------------------
	// Attributes
	// ---------------------------------------------------------------------
	
	// gets an array with all attributes
	// does not inherit all from parents!
	// can contain NULLs
	function attributes() {
		$this->load_attributes();
		return $this->_attributes;
	}
	
	// gets a specific attribute
	function attribute($key, $default = NULL) {
		$this->load_attributes();
		if (!array_key_exists($key, $this->_attributes) && isset($this->_parent)) {
			// if it is not set, look in the parent
			// note: key not set != null value
			//       the latter indicates the parent doesn't have the attribute either
			$attr = $this->_parent->attribute($key);
			if ($attr === NULL) {
				$attr = $this->_parent->attribute("child $key");
			}
			$this->_attributes[$key] = $attr;
		} else {
			$attr = $this->_attributes[$key];
		}
		return is_null($attr) ? $default : $attr;
	}
	
	// load the attributes from a file
	private function load_attributes() {
		if (isset($this->_attributes)) return;
		// default attributes
		$this->_attributes = array(
			'title' => $this->_dir_name
		);
		// load info file
		$lines = @file($this->data_path() . "info", FILE_IGNORE_NEW_LINES);
		if (!isset($lines) || $lines == false) {
			// No info file
		} else {
			// parse it
			foreach($lines as $i => $line) {
				$line = trim($line, " \r\n\0"); // keep tabs
				if ($line == '' || $line{0} == '#') {
					// comment, ignore
					unset($key);
				} else if ($line{0} == "\t") {
					// continuation of previous line
					if (!isset($key)) {
						throw new Exception("Error on line ".($i+1)." in info file for '$path':\n$line");
					}
					$this->_attributes[$key] .= ($key_first ? '' : "\n")
					                         .  substr($line,1);
					$key_first = false;
				} else {
					$kv = explode(':',$line,2);
					if (count($kv) < 2) {
						throw new Exception("Error on line ".($i+1)." in info file for '$path':\n$line");
					} else {
						$key   = trim($kv[0]);
						$value = trim($kv[1]);
						$this->_attributes[$key] = $value;
						$key_first = $value == '';
					}
				}
			}
		}
	}
	
	private function data_path() {
		global $course_dir;
		return $course_dir . $this->_path;
	}
};
