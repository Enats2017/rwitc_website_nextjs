<?php
Class Dividend {
    var $db;
    function __construct($dbobj) {
        $this->db = $dbobj;    
    }
    
    
    function insertDividend($date,$centreid,$filename) {
        $this->db->insert(self::sqlInsertDividend($date,$centreid,$filename));
    }
    
    private static function sqlInsertDividend($date,$centreid,$filename) {
        return "INSERT INTO dividends(div_date,centreid,filename) VALUES
               ('$date',$centreid,'$filename');
               ";
    }
    
    function updateDividend($id,$centreid,$date,$filename) {
        return $this->db->update(self::sqlUpdateDividend($id,$centreid,$date,$filename));
    }
    
    private static function sqlUpdateDividend($id,$centreid,$date,$filename){
        if (trim($filename) !=="") {
            return "UPDATE dividends
                    SET div_date='$date', 
                    centreid=$centreid,                
                    filename='$filename'
                    WHERE id=$id
                    ";
        }
        if (trim($filename) == "") {
            return "UPDATE dividends
                    SET div_date='$date', 
                    centreid=$centreid,                
                    WHERE id=$id
                    ";
        }
    }
    
    function getAlldividends() {
        return $this->db->getMultiDimensionalArray(self::sqlGetAlldividends());
    }
    
    private static function sqlGetAlldividends(){
        return "SELECT id,div_date,centreid,filename 
                FROM dividends ORDER BY centreid ASC,div_date DESC
                ";
    }
    
    function getDividendById($id) {
        return $this->db->getSingleRowAssoc(self::sqlGetDividendById($id));
    }
    
    private static function sqlGetDividendById($id){
        return "SELECT id,div_date,centreid,filename 
                FROM dividends
                WHERE id=$id
                ";
    }
    
    function deleteDividendByID($id) {
        return $this->db->query(self::sqlDeleteDividendByID($id));
    }
    
    private static function sqlDeleteDividendByID($id) {
        return "DELETE FROM dividends 
                WHERE id=$id
               ";
    }
    
    function getCentresList(){
         return $this->db->getMultiDimensionalArray(self::sqlGetCentresList());
    }
    
    private static function sqlGetCentresList() {
        return "SELECT id,centre FROM centres ORDER BY id ASC";
    }
    
}
