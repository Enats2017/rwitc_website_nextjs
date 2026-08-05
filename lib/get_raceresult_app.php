<?php 
header('Access-Control-Allow-Origin: *');
error_reporting(E_ALL);
ini_set("display_errors", 1);
$file = 'service99.txt';
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

}elseif(isset($_GET) && $_GET['type'] == 'race_result') {
    $value = $Itemapi->insertAcceptanceapp($datas, $handle);

}
exit(json_encode($value));
class Itemapi {
    public $conn;
    public function __construct() {
        // Create connection
        $this->conn = new mysqli('localhost', 'rwitc_erp', 'S4Y@3tAZ@GvLJ1', 'rwitc_website');
        // Check connection
        if ($this->conn->connect_error) {
            die("Connection failed: " . $this->conn->connect_error);
        }
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

    public function insertAcceptanceapp($datas = array(), $handle){
      	fwrite($datas);
      	fwrite($handle, date('Y-m-d H:i:s') . ' - ' . print_r($datas, true)  . "\n");
      	
      	$insert_query = '';
        if(!empty($datas)){
            $data = serialize($datas);
            $race_date = date("Y-m-d", strtotime($datas['race_date']));
            $this->query("DELETE FROM erp_raceresult_app WHERE `race_date` = '".$this->escape($race_date,$this->conn)."'",$this->conn);
            $insert_query = $this->query("INSERT INTO `erp_raceresult_app` SET  `race_date` = '".$this->escape($race_date,$this->conn)."', 
                        `data` = '".$this->escape($data,$this->conn)."'", $this->conn);
            
        }       
        $json['status'] = $insert_query;
        return $json;
      
    }
}
?>
