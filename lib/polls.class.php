<?php
Class Polls {

    var $db;
    function __construct($db) {
        $this->db = $db;
    }
    
    function addPollQuestion($question,$date,$active) {
       return $this->db->insert(self::sqlAddPollQuestion($question,$date,$active)); 
    }
    function sqlAddPollQuestion($question,$date,$active) {       
       return "INSERT INTO poll_questions(question,date,active) VALUES ('$question',now(),'$active')"; 
    }
    
    function addPollOptions($questionID,$optionsArray) {
        return $this->db->insert(self::sqlAddPollOptions($questionID,$optionsArray)); 
    }
    
    function sqlAddPollOptions($questionID,$optionsArray)  {
        $insertQry = "INSERT INTO poll_options(`option`,questionID) VALUES ";
        foreach ($optionsArray as $options) {
            $insertQry .= "('{$options['option']}','$questionID'),";            
        }
        return substr($insertQry,0,strlen($insertQry)-1);
    }
    
    
    function getALLPolls() {
        return $this->db->getMultiDimensionalArray(self::sqlGetAllPolls());
    }
    
    function sqlGetAllPolls() {
        return "SELECT id,question,date,active
                FROM poll_questions
                Order by date DESC
               ";
    }

function deletePoll($pollID){
	$this->db->query(self::sqlDeletePollQuestion($pollID));
	$this->db->query(self::sqlDeletePollOptions($pollID));
}

function sqlDeletePollQuestion($pollID) {
	return "delete from poll_questions where id=$pollID";
}
function sqlDeletePollOptions($pollID) {
	return "delete from poll_options where questionID=$pollID";
}

function getPollQuestionAndOptions($pollID) {
     return $this->db->getMultiDimensionalArray(self::sqlGetPollQuestionAndOptions($pollID));
}
 
function sqlGetPollQuestionAndOptions($pollID)  {
   return "SELECT * 
            FROM poll_questions pq
            JOIN poll_options po ON (pq.id=po.`questionID`)
            WHERE pq.`id`=$pollID
"; 
}

function getActivePoll() {
   return $this->db->getMultiDimensionalArray(self::sqlGetActivePoll()); 
}
private static function sqlGetActivePoll(){
   return "SELECT * 
            FROM poll_questions pq
            JOIN poll_options po ON (pq.id=po.`questionID`)
            WHERE pq.`active`='Y' ORDER BY date DESC,po.id ASC
";  
}

function addReply($questionID,$optionID) {   
     return $this->db->insert(self::sqlAddReply($questionID,$optionID));
}

private static function sqlAddReply($questionID,$optionID) {   
    return "INSERT INTO poll_replies(questionID,optionID,ip_address,date) VALUES ('$questionID','$optionID','{$_SERVER['REMOTE_ADDR']}',now())";
}

function getOptionCount($questionID,$optionID) {
   return $this->db->getSingleValue(self::sqlGetOptionCount($questionID,$optionID));  
}

private static function sqlGetOptionCount($questionID,$optionID) {
    return "SELECT COUNT(*) 
            FROM poll_replies
            WHERE questionID=$questionID AND optionID=$optionID";
}

function getPollByID($questionID) {
    return $this->db->getSingleRowAssoc(self::sqlGetPollByID($questionID));     
}

private static function sqlGetPollByID($questionID) {
    return "SELECT * 
            FROM poll_questions
            WHERE id=$questionID";
}

function changePollState($questionID,$active) {
   $this->db->update(self::sqlChangePollState($questionID,$active)); 
}

private static function sqlChangePollState($questionID,$active) {
     return "UPDATE poll_questions
             SET active='$active' 
             WHERE id='$questionID'
             ";  
}

function deactivatePolls() {
   $this->db->update(self::sqlDeactivatePolls());  
}

private static function sqlDeactivatePolls() {
    return "UPDATE poll_questions SET active='N'";
}

function getVotesCount($questionID) {
   return $this->db->getMultiDimensionalArray(self::sqlGetVotesCount($questionID));
}

private static function sqlGetVotesCount($questionID) {
    return  "SELECT pq.`question`,po.`option`,pr.*,COUNT(*) as votes
            FROM poll_replies pr
            INNER JOIN poll_options po ON pr.`optionID`=po.`id`
            INNER JOIN poll_questions pq ON pr.`questionID`=pq.`id`
            WHERE pr.questionID=$questionID
            GROUP BY pr.`optionID`";
}

function getPolls($poll_id) {
    return $this->db->getSingleValue(self::sqlGetPolls($poll_id));
}

function sqlGetPolls($poll_id) {
    return "SELECT id,question,date,active FROM poll_questions WHERE id = '".$poll_id."' Order by date DESC";
}

}
?>
