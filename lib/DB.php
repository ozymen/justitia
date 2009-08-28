<?php

// -----------------------------------------------------------------------------
// Database connection
// -----------------------------------------------------------------------------

class DB {
	// Get the database connection
	static function get() {
		static $db;
		if (!isset($db)) {
			$db = new PDO(DB_PATH, DB_USERNAME, DB_PASSWORD, array(
				PDO::ATTR_PERSISTENT => DB_PERSISTENT
			));
		}
		return $db;
	}
	
	// Prepare a query
	static function prepare($sql) {
		return DB::get()->prepare($sql);
	}
	static function prepare_query(&$query, $sql) {
		if (!isset($query)) {
			$query = DB::get()->prepare($sql);
		}
	}
	
	// Check for errors
	static function check_errors($query) {
		$status = $query->errorInfo();
		if ($status[0] != 0) {
			die($status[2]);
		}
	}
	// TODO
}