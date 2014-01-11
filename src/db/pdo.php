<?php

//the goal is to cache everything, especially the describe and information_schema
//and then queries should utilize as much joining as possible
//by possibly saving query until as much possible is known

class DB{

	protected $_pdo;

	public function __construct($connection = array()){
		$this->_server 	= $connection['server'];
		$this->_user 	= $connection['username'];
		$this->_pass 	= $connection['password'];
		$this->_db 		= $connection['database'];	

		try{	
			$this->_pdo = new PDO("mysql:host=$this->_server;dbname=$this->_db",$this->_user,$this->_pass,
    				array(PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8",
    					  PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_OBJ,
    					  PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION));
		}catch(PDOException $e){  
    		echo $e->getMessage();
    		exit();
    	}
	}

	//recursive function for getting field info and related tables
	//TODO: verify we can't get in an infinte loop
	//TODO: need to but the query in a try/catch
	public function inspectDB($table,$depth){
		
		//check the session
		//if(!isset($_SESSION[$table]))
		
		$fields = array();
		$related = array();
		$sql = "DESCRIBE $table";
			
		$stmt = $this->_pdo->prepare($sql);		
		$stmt->execute();
		while($qobj = $stmt->fetch()){			    		
    		$fields[$qobj->Field] = array("type" => $qobj->Type,
    										"allow_null" => $qobj->Null,
    										"key" => $qobj->Key);
		}		
		
		//recursively build related data
		if($depth > 0){
			$related = $this->findRelations($table);
			
			foreach($related as $key => $r){
				$dbData = $this->inspectDB($r["table"],$depth-1);
				$related[$key]["fields"] = $dbData["fields"];
				$related[$key]["related"] = $dbData["related"];
			}			
		}

		$sTable[$table] = array("fields" => $fields, "related" => $related);

		return array("fields" => $fields, "related" => $related);
	}

	public function findRelations($table){
		$related = array();
		$sql = "SELECT TABLE_NAME, COLUMN_NAME, REFERENCED_COLUMN_NAME
				FROM information_schema.KEY_COLUMN_USAGE
				WHERE REFERENCED_TABLE_NAME = :table AND TABLE_SCHEMA = :db";
		$stmt = $this->_pdo->prepare($sql);
		$stmt->execute(array("table" => $table, "db" => $this->_db));
		while($qobj = $stmt->fetch()){
			$related[] = array( "table" => $qobj->TABLE_NAME,
								"column" => $qobj->COLUMN_NAME,
								"host_column" => $qobj->REFERENCED_COLUMN_NAME);			
		}
		return $related;
	}

	//table is the db table
	//qData is query data including field and value
	//obj is the class instance from api.php
	public function get_request($obj,$qData){
		
		$response = array();

		//in the event that an empty obj gets passed in
		if(empty($obj)) return $response;

		//build the sql query
		$sql = "SELECT * FROM $obj->table";
		$first = true;
		foreach($qData as $key => $val){
			if($first){
				$sql .= " WHERE $key = :$key";
				$first = false;
			}else{
				$sql .= " AND $key = :$key";
			}			
		}		
		//prepare in pdo
		$stmt = $this->_pdo->prepare($sql);
		$stmt->execute($qData);

		while($qobj = $stmt->fetch()){
			$row = array();
			foreach($qobj as $key => $val){
				if($val == NULL && (empty($obj->display_null) || !$obj->display_null)) continue;
				$row[$key] = $val;
			}

			//rather than building nested array, maybe should build join?
			//hard to do when returning one-to-many and many-to-many
			if(is_array($obj->_related)){
				foreach($obj->_related as $key => $rel){
					$rObj = $rel["obj"];
					$column = $rel["column"];
					$host_column = $rel["host_column"];
					if(isset($row[$host_column])){
						$rData = array($column => $row[$host_column]);
						$row[$key] = $this->get_request($rObj,$rData);				
					}
				}
			}

			$response[] = $row;
		}
		return $response;
	}	

	public function post_request($obj,$payload){
		$sqlA = $sqlB = "";	
		$params = array();	//stores parameters in order (same as $this->_row, but may need it)
	    
		$sql = "INSERT INTO $obj->table (";			
			
		foreach ($payload as $key => $value) {
			$sqlA .= " $key,";
			$sqlB .= " :$key,";								
		}
		$sqlA = rtrim($sqlA,",");
		$sqlB = rtrim($sqlB,",");

		$sql .= "$sqlA) VALUES ($sqlB)";

		$stmt = $this->_pdo->prepare($sql);		

		if($stmt === false){
			return (object)array("status"=>false,"type" => "pdo_error", "error"=>$this->_pdo->errorInfo());
		}

		try{
			$res = $stmt->execute($payload);
		}catch(PDOException $e){
    		return (object)array("status"=>false,"type" => "pdo_exception", "error"=>$e);    		
    	}

    	//TODO: Return the location of the object created
    	return (object)array("status"=>true);
	}

	public function put_request($table, $payload){
		$sql = "UPDATE $table SET";

		foreach ($payload as $key => $value) {				
			//skip the id field
			if($key != "id"){
				$sql .= " $key = :$key,";				
			}
		}
		//remove trailing comma
		$sql = rtrim($sql,",");
		
		//build the where
		$sql .= " WHERE id = :id";
		$stmt = $this->_pdo->prepare($sql);
		if($stmt === false){
			return (object)array("status"=>false,"type" => "pdo_error", "error"=>$this->_pdo->errorInfo());
		}
		try{
			$res = $stmt->execute($this->_row);
		}catch(PDOException $e){
    		return (object)array("status"=>false,"type" => "pdo_exception", "error"=>$e);    		
    	}

    	//TODO: Return status of update with location
    	return (object)array("status"=>true);
	}


} 
?>