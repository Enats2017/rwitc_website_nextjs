<?php
class dbTool
{
	private $mysqli;
	
	function __construct()
	{	
			
			
        //     $user = "rwitc_erp";
		// 	$pass = "S4Y@3tAZ@GvLJ1";
			
			
        //  //live server
        //  //test server
		// 	$schema = 'rwitc_website';
		
		
		// $this->mysqli = new mysqli('127.0.0.1',$user,$pass,$schema);	
		$user = "root";
		$pass = "vcare@2025";
		$schema = "rwitc_website";
		$this->mysqli = new mysqli("localhost", $user, $pass, $schema);
		
		if (mysqli_connect_errno()) {
		   throw new Exception(printf("Can't connect to MySQL Server. Errorcode: %s\n", mysqli_connect_error()));	   
		}
		
		/* change character set to utf8 */
	/*	if (!$this->mysqli->set_charset("utf8")) {
		    throw new Exception(printf("Error loading character set utf8: %s\n", $this->mysqli->error));
		} */
		$this->mysqli->query("SET NAMES 'utf8'");

	}
	function close()
	{
		$this->mysqli->close();
	}
	
	function insert($sql)
	{
		$err = $this->mysqli->query($sql);

		if(!$err)
		{
			throw new Exception("Query failed! ".$sql."  ".$this->mysqli->error);
		}
		
		return $this->mysqli->insert_id;
	}
	
	function update($sql)
	{		
		if(!$this->mysqli->query($sql))
		{
			throw new Exception("Query failed! ".$sql."  ".$this->mysqli->error);
		}
        return $this->mysqli->affected_rows;
	}
	
	function query($sql)
	{
		if(!$this->mysqli->query($sql))
		{
			throw new Exception("Query failed! ".$sql."  ".$this->mysqli->error);
		}
	}
	
	// get single record
	// returns an array
	function getSingleRow($sql)
	{
		if($result = $this->mysqli->query($sql))
		{
			if($result->num_rows == 0)
			{
				//throw new Exception(sprintf("Failed! Couldn't find result. '%s'",$sql));
				
			}
			else if($result->num_rows > 1)
			{
				throw new Exception(printf("Failed! Duplicate results(%d) found for '%s'",$result->num_rows,$sql));
			}
			$row = $result->fetch_row();
			$result->close();
			return $row;
		}
		else
		{
			throw new Exception("Query failed! ".$this->mysqli->error);
		}
		return null;
	}
	
	function getSingleRowAssoc($sql)
	{
		if($result = $this->mysqli->query($sql))
		{
			if($result->num_rows == 0)
			{
				//throw new Exception(sprintf("Failed! Couldn't find result. '%s'",$sql));
				
			}
			else if($result->num_rows > 1)
			{
				throw new Exception(printf("Failed! Duplicate results(%d) found for '%s'",$result->num_rows,$sql));
			}
			$row = $result->fetch_assoc();
			$result->close();
			return $row;
		}
		else
		{
			throw new Exception("Query failed! ".$this->mysqli->error);
		}
		return null;
	}
	
	function getResults($sql)
	{
		if($result = $this->mysqli->query($sql))
		{
			return $result;
		}
		else
			throw( new Exception("Query failed! ".$this->mysqli->error));
	}
	
	function getMultiDimensionalArray($sql)
	{
        $data = array();
		if($result = $this->mysqli->query($sql))
		{
     		for($i=0;$i< $result->num_rows;$i++)
			{
				$data[$i] = $result->fetch_assoc();
			}
			$result->close();
		}
		
		return $data;
	}
	
	function getSingleValue($sql)
	{
      
		$row = $this->getSingleRow($sql);
     	if(isset($row[0])) {
          return $row[0];

        }else {
          return '';
        }
		
	}
	function getSingleValueNoEx($sql)
	{
		try
		{
			$row = $this->getSingleRow($sql);
			return $row[0];
		}
		catch (Exception $e)
		{
			return "";
		}
	}
	
	/* Return an array of results, single column query returned as an array */
	
	function getSingleValueArray($sql)
	{
		
		if($result = $this->mysqli->query($sql))
		{
			if($result->num_rows >= 0)
			{
				$vals = Array(); 
				for($i=0;$i< $result->num_rows;$i++)
				{
					$row=$result->fetch_array();
					$vals[] = $row[0];	
				}
				$result->close();
				
			}
			else
				return null;
		}
		else 
			return Array();
			
		return $vals;	
	}
	
	function getArrayAssoc($sql,$keyCol,$valCol)
	{
		
		if($result = $this->mysqli->query($sql))
		{
			if($result->num_rows >= 0)
			{
				$vals = Array(); 
				for($i=0;$i< $result->num_rows;$i++)
				{
					$row=$result->fetch_assoc();
					$vals[$row[$keyCol]] = $row[$valCol];	
				}
				$result->close();
				
			}
			else
				return null;
		}
		else 
			return Array();
			
		return $vals;	
	}
	function escape($str)
	{
		return $this->mysqli->escape_string($str);	
	}	
	function stat()
	{
		return $this->mysqli->stat();
	}
}


?>
