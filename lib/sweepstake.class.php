<?php
Class Sweepstake {
    var $db;
    function __construct($dbobj) {
        $this->db = $dbobj;    
    }
    
    
    function insertSweepstake($date,$title,$comment,$filename) {
        $this->db->insert(self::sqlInsertSweepstake($date,$title,$comment,$filename));
    }
    
    private static function sqlInsertSweepstake($date,$title,$comment,$filename) {
        $filenames = addslashes($filename);
        // echo "INSERT INTO sweepstakes(sweepstake_date,title,comments,filename) VALUES
        //       ('$date','".$title."','$comment','$val1');
        //       ";
        //       exit;
        return "INSERT INTO sweepstakes(sweepstake_date,title,comments,filename) VALUES
               ('$date','".$title."','$comment','$filenames');
               ";
    }
    
    function updateSweepstake($id,$title,$date,$comment,$filename) {
        return $this->db->update(self::sqlUpdateSweepstake($id,$title,$date,$comment,$filename));
    }
    
    private static function sqlUpdateSweepstake($id,$title,$date,$comment,$filename){
        $filenames = addslashes($filename);
        if (trim($filename) !=="") {
            return "UPDATE sweepstakes
                    SET sweepstake_date='$date', 
                    title='$title',                
                    comments='$comment',
                    filename='$filenames'
                    WHERE id=$id
                    ";
        }
        if (trim($filename) == "") {
            return "UPDATE sweepstakes
                    SET sweepstake_date='$date', 
                    title='$title',                
                    comments='$comment'
                    WHERE id=$id
                    ";
        }
    }
    
    function getAllSweepstakes() {
        return $this->db->getMultiDimensionalArray(self::sqlGetAllSweepstakes());
    }
    
    private static function sqlGetAllSweepstakes(){
        return "SELECT id,sweepstake_date,title,comments,filename 
                FROM sweepstakes ORDER BY sweepstake_date ASC
                ";
    }
    
    function getSweepstakeById($id) {
        return $this->db->getSingleRowAssoc(self::sqlGetSweepstakeById($id));
    }
    
    private static function sqlGetSweepstakeById($id){
        return "SELECT id,sweepstake_date,title,comments,filename 
                FROM sweepstakes
                WHERE id=$id
                ";
    }
    
    function deleteSweepstakeByID($id) {
        return $this->db->query(self::sqlDeleteSweepstakeByID($id));
    }
    
    private static function sqlDeleteSweepstakeByID($id) {
        return "DELETE FROM sweepstakes 
                WHERE id=$id
               ";
    }
    
}
