<?php 

header('Access-Control-Allow-Origin: *');

error_reporting(E_ALL);

ini_set("display_errors", 1);

$file = 'service04.txt';

$handle = fopen($file, 'a+'); 

$data = file_get_contents('php://input');

$datas = json_decode($data,true);

$Itemapi = new Itemapi();



if(isset($_GET) && $_GET['type'] == 'horse') {

	$value = $Itemapi->getitem($datas, $handle);

 

} elseif(isset($_GET) && $_GET['type'] == 'horse1') {

		$value = $Itemapi->inserthorse1($datas, $handle);



} elseif(isset($_GET) && $_GET['type'] == 'jockey') {

		$value = $Itemapi->insertJockeys($datas, $handle);



} elseif(isset($_GET) && $_GET['type'] == 'trainer') {

		$value = $Itemapi->insertTrainers($datas, $handle);



} elseif(isset($_GET) && $_GET['type'] == 'runs_data') {

		$value = $Itemapi->insertRuns($datas, $handle);
} elseif(isset($_GET) && $_GET['type'] == 'update_runs_data') {

		$value = $Itemapi->update_runs_data($datas, $handle);
}



exit(json_encode($value));

class Itemapi {

	public $conn;

	public function __construct() {

			// Create connection

			// $this->conn = new mysqli('127.0.0.1', 'rwitc_erp', 'S4Y@3tAZ@GvLJ1', 'rwitc_website');
			$this->conn = new mysqli('localhost', 'app_user', 'ho{HslC)jWaky${L', 'rwitc_website');

			// Check connection

			if ($this->conn->connect_error) {

				die("Connection failed: " . $this->conn->connect_error);

			}

			$this->query("SET sql_mode = ''", $this->conn);
			$this->query("SET SQL_SAFE_UPDATES = 0", $this->conn);

	}



	public function escape($value, $conn) {

			return $conn->real_escape_string($value);

	}



	public function getLastId($conn){

		return $conn->insert_id;

	}



	public function query($sql, $conn) {

			$query = $conn->query($sql);

			if (!$conn->errno){

				if (isset($query->num_rows)) {

					$data = array();

					while ($row = $query->fetch_assoc()) {

						$data[] = $row;

					}

					$result = new stdClass();

					$result->num_rows = $query->num_rows;

					$result->row = isset($data[0]) ? $data[0] : array();

					$result->rows = $data;

					unset($data);

					$query->close();

					return $result;

				} else{

					return true;

				}

			} else {

				throw new ErrorException('Error: ' . $conn->error . '<br />Error No: ' . $conn->errno . '<br />' . $sql);

				exit();

			}

	}



	public function getitem($data = array(), $handle){	 		

		fwrite($handle, date('Y-m-d H:i:s') . ' - ' . print_r('POST START', true)  . "\n");

		fwrite($handle, date('Y-m-d H:i:s') . ' - ' . print_r($data, true)  . "\n");

		fwrite($handle, date('Y-m-d H:i:s') . ' - ' . print_r('POST END', true)  . "\n");

			if(isset($data)){

							//$this->query("DELETE FROM hmaster_erp_test",$this->conn);

				 $this->query("TRUNCATE hmaster_erp_test",$this->conn);



		foreach ($data as $key => $value) {		

			//$this->query("UPDATE `hmaster_erp_test` SET `HORSENM` = '".$this->escape($value['HORSENM'],$this->conn)."', `age` = '".$value['age']."' ,`sex` = '".$this->escape($value['sex'],$this->conn)."', `color` = '".$this->escape($value['color'],$this->conn)."',`sire` =  '".$this->escape($value['sire_name'],$this->conn)."',`dam` = '".$this->escape($value['dam_name'],$this->conn)."',`rating` = '".$value['rating']."',`OWNERSHIP` = '".$this->escape($value['owner_name_1'],$this->conn)."',`OWNERSHIP1` = '".$this->escape($value['owner_name_2'],$this->conn)."',`OWNERSHIP2` = '".$this->escape($value['owner_name_3'],$this->conn)."',`OWNERSHIP3` = '".$this->escape($value['owner_name_4'],$this->conn)."' WHERE `horseseq` = '".$value['horseseq']."' ", $this->conn);	

					

				 

					

				 $this->query("INSERT INTO `hmaster_erp_test` SET `ASOFDATE` = '".$value['ASOFDATE']."', `HORSESEQ` = '".$value['HORSESEQ']."',  `HORSENM` = '".$this->escape($value['HORSENM'],$this->conn)."', `HORSENMOLD` = '".$this->escape($value['HORSENMOLD'],$this->conn)."', `DATEFOAL` = '".$value['DATEFOAL']."', `AGE` = '".$value['AGE']."' ,`SEX` = '".$this->escape($value['SEX'],$this->conn)."', `ORIGIN` = '".$this->escape($value['ORIGIN'],$this->conn)."',`COLOR` = '".$this->escape($value['COLOR'],$this->conn)."',`SIRE` =  '".$this->escape($value['SIRE'],$this->conn)."',`DAM` = '".$this->escape($value['DAM'],$this->conn)."',`RATING` = '".$value['RATING']."',`OWNERSHIP` = '".$this->escape($value['OWNERSHIP'],$this->conn)."',`SIRENAT` = '".$this->escape($value['SIRENAT'],$this->conn)."',`DAMNAT` = '".$this->escape($value['DAMNAT'],$this->conn)."',`TRAINER` = '".$this->escape($value['TRAINER'],$this->conn)."',`TRAINERNME` = '".$this->escape($value['TRAINERNME'],$this->conn)."',`BRANDMARKS` = '".$this->escape($value['BRANDMARKS'],$this->conn)."',`CHIPNO` = '".$this->escape($value['CHIPNO'],$this->conn)."',`PASSPORTNO` = '".$this->escape($value['PASSPORTNO'],$this->conn)."',`OCTROI` = '".$this->escape($value['OCTROI'],$this->conn)."',`STUDFOAL` = '".$this->escape($value['STUDFOAL'],$this->conn)."',`BREEDER` = '".$this->escape($value['BREEDER'],$this->conn)."',`EQPT` = '".$this->escape($value['EQPT'],$this->conn)."',`SADDLENO` = '".$this->escape($value['SADDLENO'],$this->conn)."'  ", $this->conn);

		}

			}

		$json['status'] = 1;		

			return $json;		    

	}



	public function inserthorse1($data = array(), $handle){	 		

		fwrite($handle, date('Y-m-d H:i:s') . ' - ' . print_r('POST START', true)  . "\n");

		//fwrite($handle, date('Y-m-d H:i:s') . ' - ' . print_r($data, true)  . "\n");

		fwrite($handle, date('Y-m-d H:i:s') . ' - ' . print_r('POST END', true)  . "\n");

		$this->query("SET sql_mode = ''", $this->conn);
		$this->query("SET SQL_SAFE_UPDATES = 0", $this->conn);
	
	    $this->query("TRUNCATE horse_erp", $this->conn);
		

		if(isset($data)){

			foreach ($data as $key => $value) {		

				$sire = $this->escape($value['SIRE'],$this->conn);

				$brandmarks = $this->escape($value['BRANDMARKS'],$this->conn);

				$studfoal = $this->escape($value['STUDFOAL'],$this->conn);

				$breeder = $this->escape($value['BREEDER'],$this->conn);

				$ownership = $this->escape($value['OWNERSHIP'],$this->conn);



				$this->query("INSERT INTO `horse_erp` SET `ASOFDATE` = '".$value['ASOFDATE']."', `HORSESEQ` = '".$value['HORSESEQ']."',  `HORSENM` = '".$this->escape($value['HORSENM'],$this->conn)."', `HORSENMOLD` = '".$this->escape($value['HORSENMOLD'],$this->conn)."', `DATEFOAL` = '".$value['DATEFOAL']."', `AGE` = '".$value['AGE']."' ,`SEX` = '".$this->escape($value['SEX'],$this->conn)."', `ORIGIN` = '".$this->escape($value['ORIGIN'],$this->conn)."',`COLOR` = '".$this->escape($value['COLOR'],$this->conn)."',`SIRE` =  '".$sire."',`DAM` = '".$this->escape($value['DAM'],$this->conn)."',`RATING` = '".$value['RATING']."',`OWNERSHIP` = '".$ownership."',`SIRENAT` = '".$this->escape($value['SIRENAT'],$this->conn)."',`DAMNAT` = '".$this->escape($value['DAMNAT'],$this->conn)."',`TRAINER` = '".$this->escape($value['TRAINER'],$this->conn)."',`TRAINERNME` = '".$this->escape($value['TRAINERNME'],$this->conn)."',`BRANDMARKS` = '".$brandmarks."',`CHIPNO` = '".$this->escape($value['CHIPNO'],$this->conn)."',`PASSPORTNO` = '".$this->escape($value['PASSPORTNO'],$this->conn)."',`OCTROI` = '".$this->escape($value['OCTROI'],$this->conn)."',`STUDFOAL` = '".$studfoal."',`BREEDER` = '".$breeder."',`EQPT` = '".$this->escape($value['EQPT'],$this->conn)."',`SADDLENO` = '".$this->escape($value['SADDLENO'],$this->conn)."'  ", $this->conn);


				if($value['HORSENMOLD'] != ''){  	
							// fwrite($handle, date('Y-m-d H:i:s') . ' - ' . print_r("UPDATE `hmaster` SET  `HORSENM` = '".$this->escape($value['HORSENM'],$this->conn)."', `HORSENMOLD` = '".$this->escape($value['HORSENMOLD'],$this->conn)."' WHERE `HORSESEQ` = '".$value['horse_id_old']."' ", true)  . "\n");			
					$this->query("UPDATE `hmaster` SET  `HORSENM` = '".$this->escape($value['HORSENM'],$this->conn)."', `HORSENMOLD` = '".$this->escape($value['HORSENMOLD'],$this->conn)."' WHERE `HORSESEQ` = '".$value['horse_id_old']."' ", $this->conn);
				}	         				

			}

		}

		$record_count = 0;

		$select_horse = $this->query("SELECT COUNT(*) AS record_count FROM horse_erp WHERE 1=1",$this->conn);

		if($select_horse->num_rows > 0){

			$record_count = $select_horse->row['record_count'];

		}

		$json['status'] = $record_count;

		//$json['status'] = 1;		

		echo $record_count;	
		exit;	    

	}

	

	public function insertJockeys($data = array(), $handle){	 		

		fwrite($handle, date('Y-m-d H:i:s') . ' - ' . print_r('POST START JOCKEY', true)  . "\n");

		//fwrite($handle, date('Y-m-d H:i:s') . ' - ' . print_r($data, true)  . "\n");

		//fwrite($handle, date('Y-m-d H:i:s') . ' - ' . print_r('POST END JOCKEY', true)  . "\n");

				if(isset($data)){

				 $this->query("DELETE FROM erp_jockeys",$this->conn);

				foreach ($data as $key => $value) {	

								$this->query("INSERT INTO `erp_jockeys` SET  `JOCKEY` = '".$this->escape($value['JOCKEY'],$this->conn)."', `JOCKEYNM` = '".$this->escape($value['JOCKEYNM'],$this->conn)."', `CATEGORY` = '".$value['CATEGORY']."', `FI` = '".$value['FI']."' ,`BANST` = '".$this->escape($value['BANST'],$this->conn)."', `BANEN` = '".$this->escape($value['BANEN'],$this->conn)."',`LISCENCE` = '".$this->escape($value['LISCENCE'],$this->conn)."',`SEASON` =  '".$this->escape($value['SEASON'],$this->conn)."',`BIRTHDATE` = '".$this->escape($value['BIRTHDATE'],$this->conn)."',`FILE_NO` = '".$value['FILE_NO']."',`APPRENTICE` = '".$this->escape($value['APPRENTICE'],$this->conn)."',`ALLOWANCE` = '".$this->escape($value['ALLOWANCE'],$this->conn)."',`OSWINS` = '".$this->escape($value['OSWINS'],$this->conn)."',`OSWINSNARR` = '".$this->escape($value['OSWINSNARR'],$this->conn)."',`BLOODGROUP` = '".$this->escape($value['BLOODGROUP'],$this->conn)."',`RIDINGWT` = '".$this->escape($value['RIDINGWT'],$this->conn)."',`DTISSUELIC` = '".$this->escape($value['DTISSUELIC'],$this->conn)."',`FEEPAID` = '".$this->escape($value['FEEPAID'],$this->conn)."',`REMARKS` = '".$this->escape($value['REMARKS'],$this->conn)."',`BANST1` = '".$this->escape($value['BANST1'],$this->conn)."',`BANEN1` = '".$this->escape($value['BANEN1'],$this->conn)."',`STARTLISC` = '".$this->escape($value['STARTLISC'],$this->conn)."',`ENDLISC` = '".$this->escape($value['ENDLISC'],$this->conn)."',`GSTIN` = '".$this->escape($value['GSTIN'],$this->conn)."',`NOWHIP` = '".$this->escape($value['NOWHIP'],$this->conn)."' ,`STAY` = '".$this->escape($value['STAY'],$this->conn)."' ,`APPEAL` = '".$this->escape($value['APPEAL'],$this->conn)."'  ", $this->conn);

				}

				}

				$record_count = 0;

				$select_horse = $this->query("SELECT COUNT(*) AS record_count FROM erp_jockeys WHERE 1=1",$this->conn);

				if($select_horse->num_rows > 0){

					$record_count = $select_horse->row['record_count'];

				}

		$json['status'] = $record_count;

		//$json['status'] = 1;		

			return $json;		    

	}

	

	public function insertTrainers($data = array(), $handle){	 		

		

		

				if(isset($data)){

						$this->query("DELETE FROM erp_trainers",$this->conn);

						foreach ($data as $key => $value) {	

								fwrite($handle, date('Y-m-d H:i:s') . ' - ' . print_r('POST START TRAINER', true)  . "\n");

				fwrite($handle, date('Y-m-d H:i:s') . ' - ' . print_r($data, true)  . "\n");

				

						 

							$this->query("INSERT INTO `erp_trainers` SET  

												`TRAINER` = '".$this->escape($value['TRAINER'],$this->conn)."', 

												`TRAINERNM` = '".$this->escape($value['TRAINERNM'],$this->conn)."', 

												`TRAINERNME` = '".$this->escape($value['TRAINERNME'],$this->conn)."', 

												`TRAINTYPE` = '".$this->escape($value['TRAINTYPE'],$this->conn)."', 

												`ACTIVE` = '".$this->escape($value['ACTIVE'],$this->conn)."', 

												`HORSET` = '".$this->escape($value['HORSET'],$this->conn)."', 

												`HORSE2` = '".$this->escape($value['HORSE2'],$this->conn)."', 

												`HORSE3` = '".$this->escape($value['HORSE3'],$this->conn)."', 

												`HORSE4` = '".$this->escape($value['HORSE4'],$this->conn)."', 

												`HORSE5` = '".$this->escape($value['HORSE5'],$this->conn)."', 

												`HORSE6` = '".$this->escape($value['HORSE6'],$this->conn)."', 

												`HORSE7` = '".$this->escape($value['HORSE7'],$this->conn)."', 

												`HORSE89` = '".$this->escape($value['HORSE89'],$this->conn)."', 

												`HORSE10` = '".$this->escape($value['HORSE10'],$this->conn)."', 

												`HORSENA` = '".$this->escape($value['HORSENA'],$this->conn)."', 

												`HACTIVE` = '".$this->escape($value['HACTIVE'],$this->conn)."', 

												`BANST` = '".$this->escape($value['BANST'],$this->conn)."', 

												`BANEN` = '".$this->escape($value['BANEN'],$this->conn)."', 

												`LISCENCE` = '".$this->escape($value['LISCENCE'],$this->conn)."', 

												`SEASON` = '".$this->escape($value['SEASON'],$this->conn)."', 

												`BIRTHDATE` = '".$this->escape($value['BIRTHDATE'],$this->conn)."', 

												`FILE_NO` = '".$this->escape($value['FILE_NO'],$this->conn)."', 

												`DTISSUELIC` = '".$this->escape($value['DTISSUELIC'],$this->conn)."', 

												`FEEPAID` = '".$this->escape($value['FEEPAID'],$this->conn)."', 

												`REMARKS` = '".$this->escape($value['REMARKS'],$this->conn)."', 

												`BANST1` = '".$this->escape($value['BANST1'],$this->conn)."', 

												`BANEN1` = '".$this->escape($value['BANEN1'],$this->conn)."', 

												`STAY` = '".$this->escape($value['STAY'],$this->conn)."', 

												`APPEAL` = '".$this->escape($value['APPEAL'],$this->conn)."', 

												`OWNCODE1` = '".$this->escape($value['OWNCODE1'],$this->conn)."', 

												`OWNCODE2` = '".$this->escape($value['OWNCODE2'],$this->conn)."', 

												`OWNCODE3` = '".$this->escape($value['OWNCODE3'],$this->conn)."', 

												`OWNCODE4` = '".$this->escape($value['OWNCODE4'],$this->conn)."', 

												`QUOTA2YO` = '".$this->escape($value['QUOTA2YO'],$this->conn)."', 

												`QUOTAFULL` = '".$this->escape($value['QUOTAFULL'],$this->conn)."', 

												`QUOTADT` = '".$this->escape($value['QUOTADT'],$this->conn)."', 

												`QUOTAHIST` = '".$this->escape($value['QUOTAHIST'],$this->conn)."', 

												`SEL` = '".$this->escape($value['SEL'],$this->conn)."' ,

												`ACCOUNT` = '".$this->escape($value['ACCOUNT'],$this->conn)."', 

												`APPEAL1` = '".$this->escape($value['APPEAL1'],$this->conn)."' 

										", $this->conn);

						}

				}fwrite($handle, date('Y-m-d H:i:s') . ' - ' . print_r('POST END TRAINER', true)  . "\n");

				$record_count = 0;

				$select_horse = $this->query("SELECT COUNT(*) AS record_count FROM erp_trainers WHERE 1=1",$this->conn);

				if($select_horse->num_rows > 0){

					$record_count = $select_horse->row['record_count'];

				}
		$json['status'] = $record_count;
		//$json['status'] = 1;		
		return $json;	
	}

	public function insertRuns($data = array(), $handle){	 		
		$sql_multiple_query = '';
		 fwrite($handle, date('Y-m-d H:i:s') . ' - ' . print_r('im in', true)  . "\n");
		 fwrite($handle, date('Y-m-d H:i:s') . ' - ' . print_r($data, true)  . "\n");	
		if(isset($data)){
			foreach ($data['runs_date'] as $key => $value) {	
				$this->query("DELETE FROM runs_data WHERE `cracedate` = '".$value."' ",$this->conn);
			}
			//$this->query("DELETE FROM runs_data WHERE `cracedate` >= '2023-02-04' ",$this->conn);
			foreach ($data['runs_data'] as $key => $value) {	
				 fwrite($handle, date('Y-m-d H:i:s') . ' - ' . print_r('POST START TRAINER', true)  . "\n");
				 fwrite($handle, date('Y-m-d H:i:s') . ' - ' . print_r($data, true)  . "\n");
				$insert_query = ("INSERT INTO `runs_data` SET  
					`id` = '".$this->escape($value['id'],$this->conn)."',
					`horseseq` = '".$this->escape($value['horseseq'],$this->conn)."',
					`horsenm` = '".$this->escape($value['horsenm'],$this->conn)."',
					`age` = '".$this->escape($value['age'],$this->conn)."',
					`sex` = '".$this->escape($value['sex'],$this->conn)."',
					`color` = '".$this->escape($value['color'],$this->conn)."',
					`foal_date` = '".$this->escape($value['foal_date'],$this->conn)."',
					`venue` = '".$this->escape($value['venue'],$this->conn)."',
					`racedate` = '".$this->escape($value['racedate'],$this->conn)."',
					`cracedate` = '".$this->escape($value['cracedate'],$this->conn)."',
					`raceseason` = '".$this->escape($value['raceseason'],$this->conn)."',
					`raceno` = '".$this->escape($value['raceno'],$this->conn)."',
					`racecat` = '".$this->escape($value['racecat'],$this->conn)."',
					`distance` = '".$this->escape($value['distance'],$this->conn)."',
					`weighthd` = '".$this->escape($value['weighthd'],$this->conn)."',
					`weightcd` = '".$this->escape($value['weightcd'],$this->conn)."',
					`placing` = '".$this->escape($value['placing'],$this->conn)."',
					`timingmts` = '".$this->escape($value['timingmts'],$this->conn)."',
					`timingsec` = '".$this->escape($value['timingsec'],$this->conn)."',
					`timingsecd` = '".$this->escape($value['timingsecd'],$this->conn)."',
					`stakes` = '".$this->escape($value['stakes'],$this->conn)."',
					`wingross` = '".$this->escape($value['wingross'],$this->conn)."',
					`plastakes` = '".$this->escape($value['plastakes'],$this->conn)."',
					`trainer` = '".$this->escape($value['trainer'],$this->conn)."',
					`jockey` = '".$this->escape($value['jockey'],$this->conn)."',
					`trainernm` = '".$this->escape($value['trainernm'],$this->conn)."',
					`jockeynm` = '".$this->escape($value['jockeynm'],$this->conn)."',
					`shoe` = '".$this->escape($value['shoe'],$this->conn)."',
					`shoedet` = '".$this->escape($value['shoedet'],$this->conn)."',
					`bitsdet` = '".$this->escape($value['bitsdet'],$this->conn)."',
					`eqpt` = '".$this->escape($value['eqpt'],$this->conn)."',
					`startstall` = '".$this->escape($value['startstall'],$this->conn)."',
					`cardno` = '".$this->escape($value['cardno'],$this->conn)."',
					`opodds1` = '".$this->escape($value['opodds1'],$this->conn)."',
					`opodds2` = '".$this->escape($value['opodds2'],$this->conn)."',
					`miodds1` = '".$this->escape($value['miodds1'],$this->conn)."',
				 	`miodds2` = '".$this->escape($value['miodds2'],$this->conn)."',
					`bkm1odds` = '".$this->escape($value['bkm1odds'],$this->conn)."',
					`bkm2odds` = '".$this->escape($value['bkm2odds'],$this->conn)."',
					`grade` = '".$this->escape($value['grade'],$this->conn)."',
					`ch` = '".$this->escape($value['ch'],$this->conn)."',
					`aft` = '".$this->escape($value['aft'],$this->conn)."',
					`horsewt` = '".$this->escape($value['horsewt'],$this->conn)."',
					`horsewtrem` = '".$this->escape($value['horsewtrem'],$this->conn)."',
					`wintime` = '".$this->escape($value['wintime'],$this->conn)."',
					`verdict` = '".$this->escape($value['verdict'],$this->conn)."',
					`racename` = '".$this->escape($value['racename'],$this->conn)."',
					`raceterm` = '".$this->escape($value['raceterm'],$this->conn)."',
					`penitrom` = '".$this->escape($value['penitrom'],$this->conn)."',
					`weather` = '".$this->escape($value['weather'],$this->conn)."',
					`falserails` = '".$this->escape($value['falserails'],$this->conn)."',
					`recordno` = '".$this->escape($value['recordno'],$this->conn)."',
					`incident`  = '".$this->escape($value['incident'],$this->conn)."',
					 `length`  = '".$this->escape($value['length'],$this->conn)."',
					`day_race_no`  = '".$this->escape($value['day_race_no'],$this->conn)."',
					`sirename_damname`  = '".$this->escape($value['sirename_damname'],$this->conn)."',
					`rating_locked`  = '".$this->escape($value['rating_locked'],$this->conn)."'
				");
				$this->query($insert_query,$this->conn);
				// $sql_multiple_query = $sql_multiple_query.$insert_query;
			}
		}
		// $record_count = 0;
		// $select_horse = $this->query("SELECT COUNT(*) AS record_count FROM runs_data WHERE 1=1",$this->conn);
		// if($select_horse->num_rows > 0){
		// 	$record_count = $select_horse->row['record_count'];
		// }
		// fwrite($handle, date('Y-m-d H:i:s') . ' - ' . print_r($select_horse, true)  . "\n");
		// $json['status'] = $record_count;
		//fwrite($handle, date('Y-m-d H:i:s') . ' - ' . print_r($sql_multiple_query, true)  . "\n");
		// mysqli_multi_query($this->conn,$sql_multiple_query);
		//fwrite($handle, date('Y-m-d H:i:s') . ' - ' . print_r('POST END TRAINER', true)  . "\n");
		$json['status'] = 1;		
		return $json;	
	}

	public function update_runs_data($data = array(), $handle){	 		
		fwrite($handle, date('Y-m-d H:i:s') . ' - ' . print_r('in update_runs_data', true)  . "\n");
		fwrite($handle, date('Y-m-d H:i:s') . ' - ' . print_r($data, true)  . "\n");	
		if(isset($data)){
			foreach ($data as $key => $value) {
				$update_sql = "UPDATE `runs_data` SET `stakes_new` = '".$value['stakes_new']."' WHERE `horseseq` = '".$value['horseseq']."' AND `cracedate` = '".$value['cracedate']."' ";
				$this->query($update_sql,$this->conn);
				fwrite($handle, date('Y-m-d H:i:s') . ' - ' . print_r($update_sql, true)  . "\n");	
			}
		}
		$json['status'] = 1;
		return $json;
	}
}
?>
