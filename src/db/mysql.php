<?php

class DB{

	protected $_mysqli;

	public function __construct($connection = array()){
		$mysqli = new mysqli($connection['server'],
							 $connection['username'],
							 $connection['password'],
							 $connection['database']);
		if($mysqli->connect_error){
    		die('Connect Error (' . $mysqli->connect_errno . ') '
    	        . $mysqli->connect_error);
		}else{
			$this->_mysqli = $mysqli;
		}
	}

	public function inspect_db($table){
		$model = array();
		$sql = "DESCRIBE $table";
    	$res = $this->_mysqli->query($sql);
    	while($qobj = $res->fetch_object()){				
    		$dbField = $qobj->Field;
    		$dbType = $qobj->Type;
    		$dbNull = $qobj->Null;
    		$dbKey = $qobj->Key;				
    		
    		$model[$dbField] = array("type" => $dbType,
    										"allow_null" => $dbNull,
    										"key" => $dbKey);	
    	}
    	return $model;
	}

	public function get_request($table,$pk = 0){
		$response = array();
		$sql = "SELECT * FROM $table";

	    if($pk != 0){
	    	$sql .= " WHERE id = ?";
	    	$stmt = $this->_mysqli->prepare($sql);
	    	$stmt->bind_param('i',$bPK);	
	    	$bPK = ($pk);
	    	$stmt->execute();
	
	    	$res = $stmt->get_result();
	    }else{							
	    	$res = $this->_mysqli->query($sql);
	    }
	    
	    while($qobj = $res->fetch_object()){
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
	    $types 	= "";		//stores the types for binding
	    $params = array();
	    
	    $sql = "INSERT INTO $table (";
	    
	    foreach ($payload as $key => $value) {
	    	//we want to check if the payload key is in the model
	    	
	    	$sqlA .= "$key";
	    
	    	$sqlB .= "?";
	    	
	    	//want to do this based on model field Types
	    	//check the type
	    	if(is_int($value)) 			$types .= "i";	//ints
	    	else if(is_float($value)) 	$types .= "d";	//floats and doubles
	    	else if(is_string($value)) 	$types .= "s";	//strings
	    	else						$types .= "b";	//blobs and others
	    	
	    	$params[] 	= $value;
	    	$sqlA .= ",";
	    	$sqlB .= ",";

	    }
	    $sqlA = rtrim($sqlA,",");
	    $sqlB = rtrim($sqlB,",");
	    
	    $sql .= $sqlA;
	    $sql .= ") VALUES (";
	    $sql .= $sqlB;
	    $sql .= ");";
	    
	    $stmt = $this->_mysqli->prepare($sql);
	    	
	    //got this code from http://www.devmorgan.com/blog/?s=dydl
	    $bind_names[] = $types;
	    
	    for ($i=0; $i<count($params);$i++) {//go through incoming params and added to array
    	    $bind_name = 'bind'.$i;       //give them an arbitrary name
    	    $$bind_name = $params[$i];      //add the parameter to the variable variable
    	    $bind_names[] = &$$bind_name;   //now associate the variable
    	}        
    	call_user_func_array(array($stmt,'bind_param'),$bind_names);
	
	    //run the statement		
	    $res = $stmt->execute();
	}

	public function put_request($table, $payload){
		$sqlA = $sqlB = "";		
	    $types 	= "";		//stores the types for binding
	    $params = array();	//stores parameters in order (same as $this->_row, but may need it)
	
	    //need a string for the types			
	    $sql = "UPDATE $table SET ";

	    foreach ($payload as $key => $value) {
	    	//add the variable line
	    	$sql .= " $key = ?";
	    	 
	    	//check the type
	    	if(is_int($value)) 			$types .= "i";	//ints
	    	else if(is_float($value)) 	$types .= "d";	//floats and doubles
	    	else if(is_string($value)) 	$types .= "s";	//strings
	    	else						$types .= "b";	//blobs and others 
	    	
	    	$keys[] 	= $$key;
	    	$params[] 	= $value;
	    	
	    	//handle concatination
	    	$sql .= ",";
	    	
	    }
	    $sql = rtrim($sql,",");
	    
	    //build the where and add the info for the id
	    $sql .= " WHERE id = ?;";
	    $types .= "i";
	    $params[] = $this->_pk;
	    
	    
	    $stmt = $this->_mysqli->prepare($sql);
	    	
	    //got this code from http://www.devmorgan.com/blog/?s=dydl
	    $bind_names[] = $types;
	    
	    for ($i=0; $i<count($params);$i++) {//go through incoming params and added to array
    	    $bind_name = 'bind'.$i;       //give them an arbitrary name
    	    $$bind_name = $params[$i];      //add the parameter to the variable variable
    	    $bind_names[] = &$$bind_name;   //now associate the variable
    	}        
    	call_user_func_array(array($stmt,'bind_param'),$bind_names);
	
	    //run the statement		
	    $res = $stmt->execute();
	}


}
?>