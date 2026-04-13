<?php

class Database {

	// Function to create the tables and fill them with the default data
	function create_tables($data)
	{
		// Connect to the database
		$mysqli = false;
		try {
			$mysqli = mysqli_connect($data['hostname'],$data['username'],$data['password'],$data['database']);
		} catch (mysqli_sql_exception $e) {
			// "localhost" can fail on socket-based connection in local macOS setups.
			if ($data['hostname'] === 'localhost') {
				try {
					$mysqli = mysqli_connect('127.0.0.1',$data['username'],$data['password'],$data['database']);
				} catch (mysqli_sql_exception $inner) {
					$mysqli = false;
				}
			}
		}
                                    

		// Check for errors
		if(!$mysqli || mysqli_connect_errno())
			return false;

		// Open the default SQL file
		$query = $data['dbtables']; 

		// Execute a multi query
		$mysqli->multi_query($query);

		// Close the connection
		$mysqli->close();
		
		return true;
	}
}
