<?php

function getIfSetFromAddr($addr_array, $key) {
	
	if(isset($addr_array[$key]) && isset($addr_array[$key])) {
	  return $addr_array[$key];
	} else {
		return "";
	}
}

function deleteAddresses($part_sql) {

  global $keep_history, $domain_id, $base_from_where, $table, $table_grp_adr, $table_groups;

  $sql = "SELECT * FROM $base_from_where AND ".$part_sql;
  $result = mysql_query($sql);
  $resultsnumber = mysql_numrows($result);

  $is_valid = $resultsnumber > 0;

  if($is_valid) {
  	if($keep_history) {
  	  $sql = "UPDATE $table
  	          SET deprecated = now()
  	          WHERE deprecated is null AND ".$part_sql;
  	  mysql_query($sql);
  	  $sql = "UPDATE $table_grp_adr
  	          SET deprecated = now()
  	          WHERE deprecated is null AND ".$part_sql;
  	  mysql_query($sql);
  	} else {
  	  $sql = "DELETE FROM $table_grp_adr WHERE ".$part_sql;
  	  mysql_query($sql);
  	  $sql = "DELETE FROM $table         WHERE ".$part_sql;
  	  mysql_query($sql);
    }
  }

  return $is_valid;
}

function saveAddress($addr_array, $group_name = "") {
	
	  global $domain_id, $table, $table_grp_adr, $table_groups;

    if(isset($addr_array['id'])) {
    	$set_id = "'".$addr_array['id']."'";
    } else {
    	$set_id = "ifnull(max(id),0)+1"; // '0' is a bad ID
    }

    $sql = "INSERT INTO $table ( domain_id, id, firstname,    lastname,   company,    address,   home,   mobile,   work,   fax,   email,    email2,  homepage,   bday,  bmonth,   byear,    address2,    phone2,    notes,     created, modified)
                        SELECT   '$domain_id'                                     domain_id
                               , ".$set_id."                                      id
                               , '".getIfSetFromAddr($addr_array, 'firstname')."' firstname
                               , '".getIfSetFromAddr($addr_array, 'lastname')."'  lastname 
                               , '".getIfSetFromAddr($addr_array, 'company')."'   company  
                               , '".getIfSetFromAddr($addr_array, 'address')."'   address  
                               , '".getIfSetFromAddr($addr_array, 'home')."'      home     
                               , '".getIfSetFromAddr($addr_array, 'mobile')."'    mobile   
                               , '".getIfSetFromAddr($addr_array, 'work')."'      work     
                               , '".getIfSetFromAddr($addr_array, 'fax')."'       fax      
                               , '".getIfSetFromAddr($addr_array, 'email')."'     email    
                               , '".getIfSetFromAddr($addr_array, 'email2')."'    email2   
                               , '".getIfSetFromAddr($addr_array, 'homepage')."'  homepage 
                               , '".getIfSetFromAddr($addr_array, 'bday')."'      bday     
                               , '".getIfSetFromAddr($addr_array, 'bmonth')."'    bmonth   
                               , '".getIfSetFromAddr($addr_array, 'byear')."'     byear    
                               , '".getIfSetFromAddr($addr_array, 'address2')."'  address2 
                               , '".getIfSetFromAddr($addr_array, 'phone2')."'    phone2   
                               , '".getIfSetFromAddr($addr_array, 'notes')."'     notes    
                               , now(), now()
                          FROM $table;";
    $result = mysql_query($sql);

    $sql = "SELECT max(id) max_id from $table";
    $result = mysql_query($sql);
    $rec = mysql_fetch_array($result);
    $id = $rec['max_id'];

    if(!isset($addr_adday['id']) && $group_name) {
    	$sql = "INSERT INTO $table_grp_adr SELECT $domain_id domain_id, $id id, group_id, now(), now(), NULL FROM $table_groups WHERE group_name = '$group_name'";
    	$result = mysql_query($sql);
    }
}

function updateAddress($addr) {

  global $keep_history, $domain_id, $base_from_where, $table, $table_grp_adr, $table_groups;

  $sql = "SELECT * FROM $base_from_where AND $table.id = '".$addr['id']."';";
  $result = mysql_query($sql);
	$resultsnumber = mysql_numrows($result);

	$homepage = str_replace('http://', '', $addr['homepage']);
    
	$is_valid = $resultsnumber > 0;
    
	if($is_valid)
	{
		if($keep_history) {
	    $sql = "UPDATE $table 
	               SET deprecated = now()
		           WHERE deprecated is null
		             AND id       = '".$addr['id']."';";
    	$result = mysql_query($sql);
		  saveAddress($addr);
		} else {
	    $sql = "UPDATE $table SET firstname = '".$addr['firstname']."'
	                            , lastname  = '".$addr['lastname']."'
	                            , company   = '".$addr['company']."'
	                            , address   = '".$addr['address']."'
	                            , home      = '".$addr['home']."'
	                            , mobile    = '".$addr['mobile']."'
	                            , work      = '".$addr['work']."'
	                            , fax       = '".$addr['fax']."'
	                            , email     = '".$addr['email']."'
	                            , email2    = '".$addr['email2']."'
	                            , homepage  = '".$addr['homepage']."'
	                            , bday      = '".$addr['bday']."'
	                            , bmonth    = '".$addr['bmonth']."'
	                            , byear     = '".$addr['byear']."'
	                            , address2  = '".$addr['address2']."'
	                            , phone2    = '".$addr['phone2']."'
	                            , notes     = '".$addr['notes']."'
	                            , modified  = now()
		                        WHERE id        = '".$addr['id']."'
		                          AND domain_id = '$domain_id';";
		  $result = mysql_query($sql);
    }
		// header("Location: view?id=$id");
    }

	return $is_valid;
}
    
class Address {
	
    private $address;

    function __construct($data) {
    	$this->address = $data;
    }

    public function getData() {
        return $this->address;
    }
    
    public function getEMails() {
    	
      $result = array();
    	if($this->address["email"]  != "")  $result[] = $this->address["email"];    	
    	if($this->address["email2"]  != "") $result[] = $this->address["email2"];
    	return $result;
    }
    
    public function firstEMail() {
    	  
      $emails = $this->getEMails();
      return (count($emails) > 0 ? $emails[0] : "");
    }
    
    //    
    // Phone order home->mobile->work
    //
    public function getPhones() {
    	
      $phones = array();
    	if($this->address["home"]   != "") $phones[] = $this->address["home"];
    	if($this->address["mobile"] != "") $phones[] = $this->address["mobile"];
    	if($this->address["work"]   != "") $phones[] = $this->address["work"];    	  
   	  return $phones;
   	}
    	
    public function hasPhone() {
    	
      return (count($this->getPhones()) > 0);
   	}

    public function firstPhone() {
    	
      $phones = $this->getPhones();
      return ($this->hasPhone() ? $phones[0] : "");
    }

    public function shortPhone() {
    	
		  return str_replace("'", "", 
                         str_replace('/', "", 
                         str_replace("-", "", 
                         str_replace(" ", "", 
                         str_replace(".", "", $this->firstPhone())))));
    }

}

class Addresses {
	  	
    private $result;

    function __construct($searchstring, $alphabet = "") {
    	
	    global $base_from_where, $table;

     	$sql = "SELECT DISTINCT $table.* FROM $base_from_where";
        
      if ($searchstring) {
        
          $searchwords = split(" ", $searchstring);
        
          foreach($searchwords as $searchword) {
          	$sql .= "AND (   lastname  LIKE '%$searchword%' 
                          OR firstname LIKE '%$searchword%' 
                          OR company   LIKE '%$searchword%' 
                          OR address   LIKE '%$searchword%' 
                          OR home      LIKE '%$searchword%'
                          OR mobile    LIKE '%$searchword%'
                          OR work      LIKE '%$searchword%'
                          OR email     LIKE '%$searchword%'
                          OR email2    LIKE '%$searchword%'
                          OR address2  LIKE '%$searchword%' 
                          OR notes     LIKE '%$searchword%' 
                          )";
          }
      }
      if($alphabet) {
      	$sql .= "AND (   lastname  LIKE '$alphabet%'           	
                      OR firstname LIKE '$alphabet%' 
                      )";
      }
     
      if(true) {
          $sql .= "ORDER BY lastname, firstname ASC";
      } else {
        	$sql .= "ORDER BY firstname, lastname ASC";
      }
      $this->result = mysql_query($sql);
    }
    
    public function nextAddress() {
    	
    	$myrow = mysql_fetch_array($this->result);
    	if($myrow) {
		      return new Address($myrow);
		  } else {
		      return false;
		  }		  
    }

    public function getResults() {
    	return $this->result;
    }
}
?>