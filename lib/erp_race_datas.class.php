<?php
   
    class Erpracedatas{
        var $db;
        function __construct($dbobj) {
            $this->website_url = "http://erp-1.rwitc.com:81/rwitc_erp/service/rwitc_apis/";
            $this->db = $dbobj;    
        }
        
        function getPreRaceDates() {
            $result = array();
            $url = $this->website_url."getPreRaceDates.php";
            $handle = curl_init();
            curl_setopt($handle, CURLOPT_URL, $url);
            curl_setopt($handle, CURLOPT_RETURNTRANSFER, true);
            $output = curl_exec($handle);
            
            $result = json_decode($output, true);
            return $result;
            
            curl_close($handle);
        }
        
        
        function getAccptDeclRcardDates($table_name, $racedates) {
            $datestr = implode('_', $racedates);
            $result = array();
            $url = $this->website_url."getAccptDeclrRcard.php?table_name=".$table_name."&dates=".$datestr;
            $handle = curl_init();
            curl_setopt($handle, CURLOPT_URL, $url);
            curl_setopt($handle, CURLOPT_RETURNTRANSFER, true);
            $output = curl_exec($handle);
            $result = json_decode($output, true);
            curl_close($handle);
            
            return $result;
        }
        
        
        function getPostRaceDates() {
            $result = array();
            $url = $this->website_url."getPostRaceDates.php";
            $handle = curl_init();
            curl_setopt($handle, CURLOPT_URL, $url);
            curl_setopt($handle, CURLOPT_RETURNTRANSFER, true);
            $output = curl_exec($handle);
            
            $result = json_decode($output, true);
            return $result;
            
            curl_close($handle);
        }
        function getPreRace4() {
            return $this->db->getSingleValueArray(self::sqlGetPreRace4());
        }
        
        private static function sqlGetPreRace4(){
            return "SELECT racedate 
                    FROM erp_pre_race GROUP BY racedate  ORDER BY racedate DESC LIMIT 4
                    ";
        }
         function getPreRace() {
            return $this->db->getMultiDimensionalArray(self::sqlGetPreRace());
        }
        
        private static function sqlGetPreRace(){
            return "SELECT id,racedate 
                    FROM erp_pre_race GROUP BY racedate  ORDER BY id DESC 
                    ";
        }

        function deletepreraceByID($id) {
            return $this->db->query(self::sqlDeletepreraceByID($id));
        }
        private static function sqlDeletepreraceByID($id) {
            return "DELETE FROM erp_pre_race 
                    WHERE id=$id
                   ";
        }
        function insertPreRaceDate($date) {
            $this->db->insert(self::sqlInsertPreRaceDate($date));
        }
    
        private static function sqlInsertPreRaceDate($date) {
            return "INSERT INTO erp_pre_race(racedate) VALUES
                   ('$date');
                   ";
        }
        function getPostrace4() {
            return $this->db->getSingleValueArray(self::sqlGetPostrace4());
        }
        
        private static function sqlGetPostrace4(){
            return "SELECT racedate 
                    FROM erp_post_race GROUP BY racedate  ORDER BY racedate DESC LIMIT 4
                    ";
        }
         function getPostrace() {
            return $this->db->getMultiDimensionalArray(self::sqlGetPostrace());
        }
        
        private static function sqlGetPostrace(){
            return "SELECT id,racedate 
                    FROM erp_post_race GROUP BY racedate  ORDER BY id DESC 
                    ";
        }

        function deletePostraceByID($id) {
            return $this->db->query(self::sqlDeletePostraceByID($id));
        }
        private static function sqlDeletePostraceByID($id) {
            return "DELETE FROM erp_post_race 
                    WHERE id=$id
                   ";
        }
        function insertPostraceDate($date) {
            $this->db->insert(self::sqlInsertPostraceDate($date));
        }

        private static function sqlInsertPostraceDate($date) {
            return "INSERT INTO erp_post_race(racedate) VALUES
                   ('$date');
                   ";
        }
    }
    
    ?>