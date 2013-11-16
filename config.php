<?php
	$db_engine = "pdo";
	$db_vars = array("server" 	=> '127.0.0.1',	
					 "username"	=> 'root',
					 "password"	=> 'tiger4',
					 "database"	=> 'fk_test');
	
	
	//prepend all requests with the following string
	$api_url = 'api/v1';
	
	class Person{		
		var $table = "person";
		var $required = array("name");
		var $methods = array("GET","POST","PUT","PATCH","DELETE");
		var $endpoint = "people";
		//var $related = True; NOT SURE WHAT THIS IS FOR?
		var $depth = 2;
		//var $pk = "first_name";
	}

	class Address{
		var $table = "address";
		//var $display_null = True;	//default is false
	}

	class Building{
		var $table = "building";
	}
?>