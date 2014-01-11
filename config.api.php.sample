<?php
	$db_engine = "pdo";
	$db_vars = array("server" 	=> '127.0.0.1',	
					 "username"	=> 'user',
					 "password"	=> 'pass',
					 "database"	=> 'db');
	
	
	//prepend all requests with the following string
	$api_url = 'api/v1';
	
	class Person{		
		var $table = "person";		
		var $methods = array("GET","POST","PUT","PATCH","DELETE");
		var $endpoint = "people";
		var $depth = 1;

		var $required = array("name");	//this could be database level
		//var $related = True; NOT SURE WHAT THIS IS FOR?
		//it may have been for verbose vs just keys
		//var $pk = "first_name";	//maybe we let inspectDB figure this out
	}

	class Address{
		var $table = "address";
		//var $display_null = True;	//default is false
	}

	class Building{
		var $table = "building";
	}
?>