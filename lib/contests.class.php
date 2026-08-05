https://www.rwitc<?php
/* 
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */
class Contests {

public $db;
function  __construct($db) {https://www.rwitc
        $this->db = $db;
}

function logVote($racedate,$raceno,$userid,$horseseq) {
    return $this->db->insert(self::sqlLogVote($racedate,$raceno,$userid,$horseseq));
}

private static function sqlLogVote($racedate,$raceno,$userid,$horseseq) {    
    return "INSERT INTO user_votes(racedate,raceno,userid,horseseq,voted_on) VALUES
            ('$racedate',$raceno,$userid,$horseseq,now())
           ";
}

function checkContestActive($date,$raceno) {
    $retVal =  $this->db->getSingleValueNoEx(self::sqlCheckContestActive($date,$raceno));
    if ($retVal == "") {
       return false;
   } else {
       return true;
   } 
}

private static function  sqlCheckContestActive($date,$raceno) {
   return "SELECT 1 FROM race_contest WHERE racedate='$date' AND raceno=$raceno AND closing_time>=now()";
}

function checkValidContest($date,$raceno) {
   $retVal =  $this->db->getSingleValueNoEx(self::sqlCheckValidContest($date,$raceno));
   if ($retVal == "") {
       return false;
   } else {
       return true;
   }
}

private static function  sqlCheckValidContest($date,$raceno) { 
   return "SELECT 1 FROM race_contest WHERE racedate='$date' AND raceno=$raceno";
}

function getContests() {
    return $this->db->getMultiDimensionalArray(self::sqlGetContests());
}

private static function sqlGetContests(){
    return "SELECT * 
            FROM race_contest ORDER BY racedate DESC, raceno ASC
            ";    
}

function getContestClosingTime($date,$raceno) {
    return $this->db->getSingleValue(self::sqlGetContestClosingTime($date,$raceno));
}

private static function sqlGetContestClosingTime($date,$raceno){
    return "SELECT closing_time 
            FROM race_contest WHERE racedate='$date' AND raceno=$raceno
            ";    
}

function getVoterList($date,$raceno) {
    return $this->db->getMultiDimensionalArray(self::sqlGetVoterlist($date,$raceno));
}

private static function sqlGetVoterlist($date,$raceno) {   
    return "SELECT uv.*,u.email,CONCAT(u.firstname,' ',u.lastname) AS `name`,h.HORSENM FROM user_votes uv
            INNER JOIN users u ON uv.userid=u.id 
            INNER JOIN hmaster h ON uv.horseseq=h.HORSESEQ
            AND uv.raceno=$raceno AND uv.racedate='$date' ORDER BY horseseq ASC
           ";
}

function getWinningVoterList($date,$raceno,$horseseq) {
    return $this->db->getMultiDimensionalArray(self::sqlGetWinningVoterList($date,$raceno,$horseseq));
}

private static function sqlGetWinningVoterList($date,$raceno,$horseseq) {   
    return "SELECT uv.*,u.email,CONCAT(u.firstname,' ',u.lastname) AS `name`,h.HORSENM FROM user_votes uv
            INNER JOIN users u ON uv.userid=u.id 
            INNER JOIN hmaster h ON uv.horseseq=h.HORSESEQ
            AND uv.raceno=$raceno AND uv.racedate='$date' AND uv.horseseq=$horseseq
           ";
}

function updateWinner($racedate,$raceno,$userid) {
    return $this->db->update(self::sqlUpdateWinner($racedate,$raceno,$userid));
}
private static function sqlUpdateWinner($racedate,$raceno,$userid) {
    return "UPDATE user_votes SET won='Y'
            WHERE racedate='$racedate' AND raceno=$raceno AND userid=$userid
    ";
}

function getContestRaceName($racedate,$raceno) {
    return $this->db->getSingleValue(self::sqlGetContestRaceName($racedate,$raceno));
}
private static function sqlGetContestRaceName($racedate,$raceno) {
    return "SELECT p.NAME 
            FROM prospect p WHERE p.`DATE`='$racedate' AND p.SRNO=(
            SELECT f.LINK FROM fdecl f WHERE f.RACEDATE='$racedate' AND f.RACENO_SEA=$raceno GROUP BY f.LINK) 
    ";
}





}