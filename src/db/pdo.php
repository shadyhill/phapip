<?php

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

	//recursive function 
	public function inspectDB($table,$depth){
		
		if(empty($depth) && $depth !== 0) $depth = 1;
		
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
	public function get_request2($table,$qData,$obj){
		$response = array();

		//build the sql query
		$sql = "SELECT * FROM $table";
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
				if($val == NULL && !$obj->display_null) continue;
				$row[$key] = $val;
			}
			//add relational data recursively
			//TODO: Limit number of recursive calls
			//TODO: Return recursive info in short/long form (ids/fields)
			if($table != "address"){
				$row['address'] = $this->get_request2('address',array('person_id' => 1),$obj);
			}

			$response[] = $row;
		}
		return $response;
	}

	public function get_request($table,$pk = 0){
		$response = array();
		
		$sql = "SELECT * FROM $table";
	    if($pk != 0){
	    	$sql .= " WHERE id = :pk";
	    	$stmt = $this->_pdo->prepare($sql);
	    	$stmt->execute(array("pk" => $pk));
	    }else{	    		
	    	$stmt = $this->_pdo->prepare($sql);
	    	$stmt->execute();
	    }
	    while($qobj = $stmt->fetch()){
	    	$row = array();
	    	foreach($qobj as $key => $val){
	    		if($val == NULL && !$this->_obj->display_null) continue;	    		
	    		$row[$key] = $val;
	    	}
	    	$response[] = $row;
	    }
	    return $response;
	}

	public function post_request($table,$payload){
		$sqlA = $sqlB = "";	
		$params = array();	//stores parameters in order (same as $this->_row, but may need it)
	    
		$sql = "INSERT INTO $table (";			
			
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
			$res = $stmt->execute($this->_row);
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