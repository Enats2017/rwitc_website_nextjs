<?php

Class Racedata {

    var $db;

    function __construct($dbobj) {

        // echo'<pre>';print_r($dbobj);exit;

        $this->db = $dbobj;    

    }

    

    function getProspectByDate($date) {

        return $this->db->getMultiDimensionalArray(self::sqlGetProspectByDate($date));

    }

    

    private static function sqlGetProspectByDate($date) {

        return "

                  SELECT UNIX_TIMESTAMP(p.`DATE`) as DATE, p.`SRNO`, p.`NAME` as RACENAME, p.`DAYNARR`, p.`NARRENT`, p.`DISTANCE`, p.`FJOCK`, p.`HTERMS`, p.`RACECAT`, p.`GRADE`, p.`RAISELOWER`, p.`RAISEACP1`, p.`RAISEACP2`, p.`RAISEACP3`, p.`RACETIME1`, p.`RACETIME2`, 

p.`VOID_HACP`, p.`VOID_ACCP`

                    FROM prospect p   

                    WHERE p.`DATE`='$date' ORDER BY p.`SRNO` ASC

               ";

    }

    

    

    function getWeightsByDate($date,$srno) {

        return $this->db->getMultiDimensionalArray(self::sqlGetWeightsByDate($date,$srno));

    }

    

    private static function sqlGetWeightsByDate($date,$srno) {

     return "   

    SELECT w.`SRNOCTRL`, UNIX_TIMESTAMP(w.`RACEDATE`) as RACEDATE, w.`WEIGHT`,w.`NAME`,w.`SRNO`,w.`HORSESEQ`,w.`ACCPFLAG`,w.`HRATING`,w.`RAISELOWER`,w.`FRT`, w.`SSBAN`, w.`VOBAN`, w.`MKBAN`, w.`SSREQD`, w.`SHOE`, w.`SHOEDET`, w.`BITSDET`,w.`SORDER`,w.`TRAINERNME`,h.SIRE,h.DAM,h.DAMNAT

    FROM weights w

    INNER JOIN hmaster h ON w.`HORSESEQ`=h.`HORSESEQ`

    WHERE w.`RACEDATE`='$date' AND w.`SRNO`=$srno ORDER BY w.`SORDER` ASC";

    }

    

    function getWeightsUniqueSRNOByDate($date) {

        return $this->db->getSingleValueArray(self::sqlGetWeightsUniqueSRNOByDate($date));

    }

    

    private static function sqlGetWeightsUniqueSRNOByDate($date) {

       return "SELECT DISTINCT(w.`SRNO`)

    FROM weights w

    WHERE w.`RACEDATE`='$date' AND w.`SRNO`>0 ORDER BY w.`SRNO` ASC";

    }

    

    

    //function getDECLAndWeightsDataByDateAndLink($date,$srNo) {                

    function getDeclAndWeightsDataByDateAndLink($date,$srno) {                

        $sql = self::sqlGetDeclAndWeightsDataByDateAndLink($date,$srno);

        

        try {

         return $this->db->getMultiDimensionalArray(self::sqlGetDeclAndWeightsDataByDateAndLink($date,$srno));

        } catch (Exception $err) {

            echo $err->getMessage();

        }

    }

    

    private static function sqlGetDeclAndWeightsDataByDateAndLink($date,$srno) {                      

        return "SELECT d.`RACEDATE`, d.`NAME`, d.`RACENO`, d.`WEIGHT`, d.`RTIME`, d.`DIV`, d.`LINK`, d.`TRAINER`, d.`CARDNO`, d.`HORSESEQ`, d.`DATEGELD`, d.`HORSEWT`, d.`STAKES`, d.`AGECODE`, d.`SHOE`, d.`SHOEDET`, d.`BITSDET`, w.`HRATING`

                FROM decl d 

                INNER JOIN weights w ON d.`HORSESEQ`=w.`HORSESEQ`

                INNER JOIN hmaster h ON d.`HORSESEQ`=h.`HORSESEQ`

                INNER JOIN trainers t ON d.TRAINER=t.TRAINER

                WHERE d.`RACEDATE`='$date' AND w.`RACEDATE`='$date' AND d.`LINK`=$srno";

        /*return "SELECT * 

                FROM decl d INNER JOIN weights w ON d.`HORSESEQ`=w.`HORSESEQ`

                WHERE d.`RACEDATE`='$date' AND w.`RACEDATE`='$date' AND d.`LINK`=$srno ORDER BY d.`WEIGHT` ASC

               ";*/

    }

    

    

    function getDeclDataByLinkAndDateAndRaceNo($date,$srno,$raceNo) {

        return $this->db->getMultiDimensionalArray(self::sqlGetDeclDataByLinkAndDateAndRaceNo($date,$srno,$raceNo));

    }

    

    private static function sqlGetDeclDataByLinkAndDateAndRaceNo($date,$srno,$raceNo) {   

    return "SELECT d.`RACEDATE`, d.`NAME`, d.`RACENO`, d.`WEIGHT`, d.`RTIME`, d.`DIV`, d.`LINK`, d.`TRAINER`, d.`CARDNO`, d.`HORSESEQ`, d.`DATEGELD`, d.`HORSEWT`, d.`STAKES`, d.`AGECODE`, d.`SHOE`, d.`SHOEDET`, d.`BITSDET`,d.`HRATING`,t.`TRAINERNME`,h.`SIRE`,h.`DAM`,h.`DAMNAT`

                FROM decl d

                INNER JOIN hmaster h ON d.`HORSESEQ`=h.`HORSESEQ`

                INNER JOIN trainers t ON d.TRAINER=t.TRAINER                

                WHERE d.`RACEDATE`='$date' AND d.`LINK`=$srno AND d.`RACENO`=$raceNo ORDER BY d.`WEIGHT` DESC, d.`NAME` ASC

               ";

    }

    

    

    function getRatingByHorseSeqFromWeights($date,$horseseq) {

        return $this->db->getSingleValue(self::sqlGetRatingByHorseSeqFromWeights($date,$horseseq));

    }

    

    private static function sqlGetRatingByHorseSeqFromWeights($date,$horseseq) {

        return "SELECT DISTINCT(w.HRATING) as HRATING

                FROM weights w

                WHERE w.HORSESEQ=$horseseq AND w.RACEDATE='$date'; 

               ";

    }

    

    function getProspectDeclDataJoinOnSrnoLinkGroupedRaceno($date) {

        return $this->db->getMultiDimensionalArray(self::sqlGetProspectDeclDataJoinOnSrnoLinkGroupedRaceno($date));

    }

    

    private static function sqlGetProspectDeclDataJoinOnSrnoLinkGroupedRaceno($date) {      

        return "SELECT UNIX_TIMESTAMP(p.`DATE`) as DATE, p.`SRNO`, p.`NAME` as RACENAME, p.`DAYNARR`, p.`NARRENT`, p.`DISTANCE`, p.`FJOCK`, p.`HTERMS`, p.`RACECAT`, p.`GRADE`, p.`RAISELOWER`, p.`RAISEACP1`, p.`RAISEACP2`, p.`RAISEACP3`, p.`RACETIME1`, p.`RACETIME2`, d.`SRNOCTRL`, p.`VOID_HACP`, p.`VOID_ACCP`,  d.`RACEDATE`, d.`NAME` as HORSENAME, d.`RACENO`, d.`WEIGHT`, d.`RTIME`, d.`DIV`, d.`LINK`, d.`TRAINER`, d.`CARDNO`, d.`HORSESEQ`, d.`DATEGELD`, d.`HORSEWT`, d.`STAKES`, d.`AGECODE`, d.`SHOE`, d.`SHOEDET`, d.`BITSDET`,d.`HRATING`

                FROM prospect p 

                INNER JOIN decl d ON p.SRNO=d.LINK

                WHERE p.DATE='$date' AND d.RACEDATE='$date' ORDER BY RACENO ASC

               ";

    }

    

    

    function getProspectFDeclDataJoinOnSrnoLinkGroupedRaceno($date) {

       return $this->db->getMultiDimensionalArray(self::sqlGetProspectFDeclDataJoinOnSrnoLinkGroupedRaceno($date));

    }

    

    

    private static function sqlGetProspectFDeclDataJoinOnSrnoLinkGroupedRaceno($date) {

    return "SELECT UNIX_TIMESTAMP(p.`DATE`) as DATE, p.`SRNO`, p.`NAME` as RACENAME, 

p.`DAYNARR`, p.`NARRENT`, p.`DISTANCE`, p.`FJOCK`, p.`HTERMS`, p.`RACECAT`, p.`GRADE`, 

p.`RAISELOWER`, p.`RAISEACP1`, p.`RAISEACP2`, p.`RAISEACP3`, p.`RACETIME1`, p.`RACETIME2`, p.`VOID_HACP`, p.`VOID_ACCP`, f.`RACEDATE`, f.`NAME` as HORSENAME, f.`RACENO`, f.`WEIGHT`, f.`CARDNO`, f.`RTIME`, f.`DIV`, f.`LINK`, f.`TRAINER`, f.`JOCKEY`, f.`JOCKEYNM`, f.`CATEGORY`, f.`SHOE`, f.`SHOEDET`, f.`DRAWNO`, f.`HORSEWT`, f.`HORSESEQ`, f.`RATING`, f.`RACECAT`, f.`CENTRE`, f.`RACENO_SEA`, f.`DISTANCE`, f.`TRN_NM`, f.`HT`, f.`LATENAME`, f.`OWNCODE`, f.`BITSDET`, f.`SERIALNO` 

FROM prospect p 

INNER JOIN fdecl f ON p.SRNO=f.LINK

WHERE p.DATE='$date' AND f.RACEDATE='$date'  ORDER BY RACENO ASC

            ";

    }

    

    

    function getProspectFDeclDataJoinProspectByRacenoAndDate($searaceno,$date) {

       return $this->db->getMultiDimensionalArray(self::sqlGetProspectFDeclDataJoinProspectByRacenoAndDate($searaceno,$date));

    }

    

    private static function sqlGetProspectFDeclDataJoinProspectByRacenoAndDate($searaceno,$date) {

    return "SELECT UNIX_TIMESTAMP(p.`DATE`) as DATE, p.`SRNO`, p.`NAME` as RACENAME, 

p.`DAYNARR`, p.`NARRENT`, p.`DISTANCE`, p.`FJOCK`, p.`HTERMS`, p.`RACECAT`, p.`GRADE`, 

p.`RAISELOWER`, p.`RAISEACP1`, p.`RAISEACP2`, p.`RAISEACP3`, p.`RACETIME1`, p.`RACETIME2`, p.`VOID_HACP`, p.`VOID_ACCP`, f.`RACEDATE`, f.`NAME` as HORSENAME, f.`RACENO`, f.`WEIGHT`, f.`CARDNO`, f.`RTIME`, f.`DIV`, f.`LINK`, f.`TRAINER`, f.`JOCKEY`, f.`JOCKEYNM`, f.`CATEGORY`, f.`SHOE`, f.`SHOEDET`, f.`DRAWNO`, f.`HORSEWT`, f.`HORSESEQ`, f.`RATING`, f.`RACECAT`, f.`CENTRE`, f.`RACENO_SEA`, f.`DISTANCE`, f.`TRN_NM`, f.`HT`, f.`LATENAME`, f.`OWNCODE`, f.`BITSDET`, f.`SERIALNO` 

FROM prospect p 

INNER JOIN fdecl f ON p.SRNO=f.LINK

WHERE f.`RACENO_SEA`=$searaceno AND p.`DATE`='$date'

GROUP BY f.RACENO ORDER BY f.RACENO ASC

            ";

    }     

    

    

    

    function getFDeclDataByLinkAndDateAndRaceNo($date,$srno,$raceNo) {

        return $this->db->getMultiDimensionalArray(self::sqlGetFDeclDataByLinkAndDateAndRaceNo($date,$srno,$raceNo));

    }

    

    private static function sqlGetFDeclDataByLinkAndDateAndRaceNo($date,$srno,$raceNo) {

        return "SELECT

f.`RACEDATE`, f.`NAME`, f.`RACENO`, f.`WEIGHT`, f.`CARDNO`, f.`RTIME`, f.`DIV`, f.`LINK`, f.`TRAINER`, f.`JOCKEY`, f.`JOCKEYNM`,  f.`CATEGORY`,f.`SHOE`,f.`SHOEDET`,f.`DRAWNO`,f.`HORSEWT`,f.`HORSESEQ`,f.`RATING`,f.`RACECAT`,f.`CENTRE`,f.`RACENO_SEA`, f.`DISTANCE`,f.`TRN_NM`,f.`HT`,f.`LATENAME`,f.`OWNCODE`,f.`BITSDET`,f.`SERIALNO`,h.`SIRE`,h.`DAM`,h.`DAMNAT`

FROM fdecl f

INNER JOIN hmaster h ON f.HORSESEQ=h.HORSESEQ

WHERE f.`RACEDATE`='$date' AND f.`LINK`=$srno AND f.`RACENO`=$raceNo ORDER BY f.`CARDNO` ASC

";

        

    }

    

    

    function getFcardDataByLinkAndDate($date,$racenoSEA) {

        return $this->db->getMultiDimensionalArray(self::sqlGetFcardDataByLinkAndDate($date,$racenoSEA));

    }

    

    

    private static function sqlGetFcardDataByLinkAndDate($date,$racenoSEA) {

        // temporary changed condition RACENO_SEA=$racenoSEA to RACENO=$racenoSEA 

        return "SELECT

fc.`RACENO`, fc.`CARDNO`, fc.`HORSESEQ`, fc.`WEIGHT`,fc.`TRAINERNM`,fc.`HORSENAME`,fc.`LATENAME`,fc.`SIREDAM`,

fc.`COLOR`, fc.`SEX`, fc.`AGE`,fc.`EQPT`, fc.`ACCOWN1`, fc.`ACCOWN2`, fc.`ACCOWN3`, fc.`ACCOWN4`,fc.`ENTO1`,

fc.`ENTO2`, fc.`ENTO3`,fc.`ENTO4`,fc.`FINALNAME`,fc.`FINALNAME1`,fc.`FINALNAME2`,fc.`FINALNAME3`,fc.`SEXETC`,fc.`COLOURS1`,fc.`LRUNDATE`,fc.`COLNO`,fc.`DATEFOAL`,

fc.`RUNGELD`,fc.`RUNSDATA`,fc.`RATING`,fc.`STUD`,fc.`DATEGELD`,fc.`PBREEDER`,fc.`RACEDATE`,fc.`SHOE`,fc.`SHOEDET`,fc.`JOCKEYNM`,fc.`DRAWNO`,

fc.`BITSDET`,fc.`RACENO_SEA`,fc.`HRATACH`,fc.`DISTWON`,hm.`DAMNAT`

FROM fcard fc

INNER JOIN hmaster hm ON fc.`HORSESEQ`=hm.`HORSESEQ`

WHERE fc.RACEDATE='$date' AND RACENO_SEA=$racenoSEA ORDER BY CARDNO ASC";



    }

    

    

    function getDaynarrFromProspectByDate($date) {

        return $this->db->getSingleValue(self::sqlGetDaynarrFromProspectByDate($date));

    }

    

    private static function sqlGetDaynarrFromProspectByDate($date) {

        //  echo"<pre>";

        //     print_r("SELECT DAYNARR

        //     FROM prospect 

        //     WHERE DATE='$date' AND DAYNARR <>''");exit;

       return "SELECT DAYNARR

            FROM prospect 

            WHERE DATE='$date' AND DAYNARR <> '' LIMIT 1";

    }

    

           

                

    function getPoolsByDate($date) {

        return $this->db->getSingleRowAssoc(self::sqlGetPoolsByDate($date));

    }

    

    private static function sqlGetPoolsByDate($date) {

        return "SELECT FLDSTR1,FLDSTR2,FLDSTR3,FLDSTR4,FLDSTR5,FLDSTR6,FLDSTR7,FLDSTR8,FLDSTR9,FLDSTR10,FLDSTR11,FLDSTR12,FLDSTR13,FLDSTR14,FLDSTR15

                FROM pools 

                WHERE RACEDATE='$date'

               ";

    }

    

    function getHmasterDataJoinfHorse5ByDateAndRaceNo($date,$raceno) {

        return $this->db->getMultiDimensionalArray(self::sqlGetHmasterDataJoinfHorse5ByDateAndRaceNo($date,$raceno));

    }

    

    private static function sqlGetHmasterDataJoinfHorse5ByDateAndRaceNo($date,$raceno) {

        /*return "    SELECT h.HORSENM,h.TRAINERNME,h.SIRE,h.DAM,h.OWNERSHIP,h.BREEDER,f.*

                    FROM fhorse5 f

                    INNER JOIN hmaster h ON f.HORSESEQ=h.HORSESEQ

                    WHERE f.RACEDATE='$date' AND RACENO=$raceno

                    ORDER BY f.RACENO ASC, f.PLACING ASC, f.CARDNO ASC;

               "; */

        return "    SELECT fc.HORSENAME,fc.TRAINERNM,h.SIRE,h.DAM,fc.FINALNAME,fc.FINALNAME1,fc.FINALNAME2,fc.FINALNAME3,h.BREEDER,f.*

                    FROM fhorse5 f

                    INNER JOIN fcard fc ON f.HORSESEQ=fc.HORSESEQ

                    INNER JOIN hmaster h ON f.HORSESEQ=h.HORSESEQ

                    WHERE f.RACEDATE='$date' AND f.RACENO=$raceno AND fc.RACEDATE='$date'

                    ORDER BY f.RACENO ASC, f.PLACING ASC, f.CARDNO ASC

               ";

    }

    function getDateByRacenosea($searaceno) {

        return $this->db->getSingleValue(self::sqlGetDateByRacenosea($searaceno));

    }

    private static function sqlGetDateByRacenosea($searaceno) {

       return "SELECT DISTINCT(RACEDATE) FROM fdecl f WHERE RACENO_SEA=$searaceno";

    }

    

    

    

    function getDivSinglInfoByDateAndRaceNo($date,$raceno) {

        return $this->db->getSingleRowAssoc(self::sqlGetDivSinglInfoByDateAndRaceNo($date,$raceno));

    }

    

    private static function sqlGetDivSinglInfoByDateAndRaceNo($date,$raceno) {

        return "SELECT * 

                FROM divsingl d

                WHERE RACEDATE='$date' AND RACENO=$raceno

               ";

    }

    

    function getJockeynameAndAllowanceByJockey($jockey) {

        return $this->db->getSingleRowAssoc(self::sqlGetJockeynameAndAllowanceByJockey($jockey));

    }

    

    private static function sqlGetJockeynameAndAllowanceByJockey($jockey) {

        return "SELECT j.`JOCKEYNM`,j.`ALLOWANCE`

                FROM jockeys j

                WHERE j.`JOCKEY`='$jockey'

               ";

    }

    

    function getDistinctRacedatesByRange($start,$end) {

      return  $this->db->getSingleValueArray(self::sqlGetDistinctRacedatesByRange($start,$end));

    }

    

    private static function sqlGetDistinctRacedatesByRange($start,$end) {  

      //echo "SELECT DISTINCT(`DATE`) FROM prospect t WHERE `DATE`>='$start' AND `DATE`<='$end'";exit;

        return "SELECT DISTINCT(`DATE`) FROM prospect t

                WHERE `DATE`>='$start' AND `DATE`<='$end';

                ";

    }

    

    function getDivMultiInfoByRacedate($raceDate) {

         return $this->db->getSingleRowAssoc(self::sqlGetDivMultiInfoByRacedate($raceDate));

    }

    

    private static function sqlGetDivMultiInfoByRacedate($raceDate) {

        return "SELECT dm.*,p.*

                FROM divmulti dm

                INNER JOIN pools p ON dm.RACEDATE=p.RACEDATE

                WHERE dm.RACEDATE='$raceDate'

                ";

    }

    

    public function getWinningHorseNameByRacedateAndRaceNos($racedate,$racenos) {

        return $this->db->getSingleValueArray(self::sqlGetWinningHorseNameByRacedateAndRaceNos($racedate,$racenos));

    }

    

    private static function sqlGetWinningHorseNameByRacedateAndRaceNos($racedate,$racenos) {

        return "SELECT h.HORSENM

                FROM fhorse5 f

                INNER JOIN hmaster h ON f.HORSESEQ=h.HORSESEQ

                WHERE f.RACEDATE='$racedate' AND f.RACENO IN ($racenos) AND PLACING=1

                ORDER BY f.RACENO ASC, f.PLACING ASC, f.CARDNO ASC";    

    }

    

    

    

    function getAllTrainerNames() {

        return $this->db->getSingleValueArray(self::sqlGetAllTrainerNames());

    }

    

    private static function sqlGetAllTrainerNames() {   

        /*return "SELECT DISTINCT(TRAINERNME) 

                FROM hmaster h ORDER BY TRAINERNME";*/

        return "SELECT DISTINCT(TRAINERNM)

                FROM trainers t WHERE ((LISCENCE='A' OR LISCENCE='B') AND (TRAINER != 'CLENT') AND HORSET > 0)

                ORDER BY TRAINERNME";

        

    }

    

    function getAllActiveTrainerNames() {

        return $this->db->getSingleValueArray(self::sqlGetAllActiveTrainerNames());

    }

    

    private static function sqlGetAllActiveTrainerNames() {   

        /*return "SELECT DISTINCT(TRAINERNME) 

                FROM hmaster h ORDER BY TRAINERNME";*/

        return "SELECT DISTINCT(t.TRAINERNM) 

                FROM trainers t 

                INNER JOIN hmaster h ON h.`TRAINER` = t.`TRAINER`

                WHERE ((t.LISCENCE='A' OR t.LISCENCE='B') AND (t.TRAINER != 'CLENT') AND HORSET > 0)

                ORDER BY t.TRAINERNM";

        

    }

    

    

    function getTrainerCode($trainerName) {

        return $this->db->getSingleValue(self::sqlGetTrainerCode($trainerName));

    }

    private static function sqlGetTrainerCode($trainerName) {

        return "SELECT TRAINER

                FROM trainers t WHERE TRAINERNM='$trainerName';    

               ";

    }

    

    

    

    function getHorsesDetailsForTrainers($trainerCode) {

        return $this->db->getMultiDimensionalArray(self::sqlGetHorsesDetailsForTrainers($trainerCode));

    }

    private static function sqlGetHorsesDetailsForTrainers($trainerCode) {

        return "SELECT HORSESEQ,HORSENM,AGE,SEX,COLOR,SIRE,DAM,OWNERSHIP,OWNERSHIP1,OWNERSHIP2,OWNERSHIP3,DAMNAT,RATING

            FROM hmaster h

            WHERE TRAINER='$trainerCode' ORDER BY HORSENM";

    }

    

    function getHorseDetailsByHorseseq($horseseq) {

        return $this->db->getMultiDimensionalArray(self::sqlGetHorseDetailsByHorseseq($horseseq));

    }

    

    private static function sqlGetHorseDetailsByHorseseq($horseseq) {        

        return "SELECT h.HORSENM, h.TRAINERNME,h.SIRE,h.DAM,h.DAMNAT,j.JOCKEYNM,f.*,t.TRAINERNM,t.LISCENCE,d.RACENO as DAYRACENO

                FROM fhorse5 f

                LEFT JOIN hmaster h ON f.HORSESEQ=h.HORSESEQ

                LEFT JOIN jockeys j ON f.JOCKEY=j.JOCKEY

                LEFT JOIN trainers t ON h.TRAINER=t.TRAINER

        LEFT JOIN decl d ON f.RACEDATE = d.RACEDATE AND d.HORSESEQ = $horseseq

                WHERE f.HORSESEQ=$horseseq AND d.`RACENO_SEA` <> '0'

                ORDER BY f.RACEDATE DESC;

               ";

    }

    

    function getOldHorseDetailsByHorseseq($horseseq) {

        return $this->db->getMultiDimensionalArray(self::sqlGetOldHorseDetailsByHorseseq($horseseq));

    }

    

    private static function sqlGetOldHorseDetailsByHorseseq($horseseq) {

        return "SELECT h.HORSENM, h.TRAINERNME,h.SIRE,h.DAM,h.DAMNAT,j.JOCKEYNM,f.*,t.TRAINERNM,t.LISCENCE,d.RACENO as DAYRACENO

                FROM ofhorse5 f

                LEFT JOIN hmaster h ON f.HORSESEQ=h.HORSESEQ

                LEFT JOIN jockeys j ON f.JOCKEY=j.JOCKEY

                LEFT JOIN trainers t ON h.TRAINER=t.TRAINER

        LEFT JOIN decl d ON f.RACEDATE = d.RACEDATE AND d.HORSESEQ = $horseseq

                WHERE f.HORSESEQ=$horseseq

                ORDER BY f.RACEDATE DESC;

               ";

    }

    

    

    // used in the results table for results of past seasons in performance profiles

    function getOldHorseDetailsByDateAndRaceNo($date,$raceno) {

        return $this->db->getMultiDimensionalArray(self::sqlGetOldHorseDetailsByDateAndRaceNo($date,$raceno));

    }

    

    private static function sqlGetOldHorseDetailsByDateAndRaceNo($date,$raceno) {

        return "SELECT h.HORSENM, h.SIRE,h.DAM,h.OWNERSHIP,t.TRAINERNME,j.JOCKEYNM,f.*

                FROM ofhorse5 f

                INNER JOIN hmaster h ON f.HORSESEQ=h.HORSESEQ 

                INNER JOIN trainers t ON f.TRAINER=t.TRAINER

                INNER JOIN jockeys j ON f.JOCKEY=j.JOCKEY

                WHERE f.RACEDATE='$date' AND f.RACENO=$raceno

                ORDER BY f.PLACING ASC;

               ";

    }

    

    // used in the results table of the current season in performance profiles 

    function getHorseDetailsByDateAndRaceNo($date,$raceno) {

        return $this->db->getMultiDimensionalArray(self::sqlGetHorseDetailsByDateAndRaceNo($date,$raceno));

    }

    

    private static function sqlGetHorseDetailsByDateAndRaceNo($date,$raceno) {

        

        return "SELECT h.HORSENM,h.SIRE,h.DAM,h.OWNERSHIP,t.TRAINERNME,j.JOCKEYNM,f.*

                FROM fhorse5 f

                INNER JOIN hmaster h ON f.HORSESEQ=h.HORSESEQ

                INNER JOIN trainers t ON f.TRAINER=t.TRAINER

                INNER JOIN jockeys j ON f.JOCKEY=j.JOCKEY

                WHERE f.RACEDATE='$date' AND f.RACENO=$raceno

                ORDER BY f.PLACING ASC;

               ";

    }

    

    

    

    

    

    function searchHorseName($horseNameString) {

        return $this->db->getMultiDimensionalArray(self::sqlSearchHorseName($horseNameString));

    }

    

    private static function sqlSearchHorseName($horseNameString) {        

        return "SELECT h.HORSESEQ,h.HORSENM 

                FROM hmaster h WHERE h.HORSENM LIKE '%$horseNameString%';

               ";

    }

    

    function getAllActiveHorses() { 

        return $this->db->getMultiDimensionalArray(self::sqlGetAllActiveHorses());

    }

    

    private static function sqlGetAllActiveHorses() {

        return "SELECT h.HORSESEQ,h.HORSENM

                FROM hmaster h ORDER BY h.HORSENM ASC

                ";    

    }

    

    function getAllActiveHorsesByLetter($letter,$limit) { 

        return $this->db->getMultiDimensionalArray(self::sqlGetAllActiveHorsesByLetter($letter,$limit));

    }

    

    private static function sqlGetAllActiveHorsesByLetter($letter,$limit) {      

        return "SELECT h.HORSESEQ,h.HORSENM

                FROM hmaster h 

                WHERE h.HORSENM LIKE '$letter%'

                ORDER BY h.HORSENM ASC

                LIMIT 0,$limit                

                ";    

    }

    

    function getScaleTopInfoByDate($date) {

        return $this->db->getSingleRowAssoc(self::sqlGetScaleTopInfoByDate($date));

    }

    

    private static function sqlGetScaleTopInfoByDate($date) {

        return "SELECT `WEATHER`,`PENITROM`,`FALSERAILS`,`OTHER`

                FROM scaletop

                WHERE `RACEDATE`='$date'

               ";

    }

    

    function getOperationInfoByDateAndRaceno($date,$raceno) {

        return $this->db->getMultiDimensionalArray(self::sqlGetOperationInfoByDateAndRaceno($date,$raceno));

    }

    

    private static function sqlGetOperationInfoByDateAndRaceno($date,$raceno) {

        return "SELECT `HORSE`,`HORSESEQ`,`DATE`,`NARR`

                FROM operrace

                WHERE `RACEDATE`='$date' AND RACENO=$raceno

               ";

    }

    

    

    function checkDateInWeights($date) {

        return $this->db->getSingleValue(self::sqlCheckDateInWeights($date));

    }

    

    private static function sqlCheckDateInWeights($date) {

        return "SELECT DISTINCT(1) FROM weights WHERE RACEDATE='$date'

               ";

    }

    function checkDateInProspect($date) {

        return $this->db->getSingleValue(self::sqlCheckDateInProspect($date));

    }

    

    private static function sqlCheckDateInProspect($date) {

        return "SELECT DISTINCT(1) FROM prospect WHERE DATE='$date'

               ";

    }

    function checkDateInDecl($date) {

        return $this->db->getSingleValue(self::sqlCheckDateInDecl($date));

    }

    

    private static function sqlCheckDateInDecl($date) {

        //echo "SELECT DISTINCT(1) FROM decl WHERE RACEDATE='$date'<br />";

        return "SELECT DISTINCT(1) FROM decl WHERE RACEDATE='$date'";

    }

    function checkDateInFDecl($date) {

        return $this->db->getSingleValue(self::sqlCheckDateInFDecl($date));

    }

    

    private static function sqlCheckDateInFDecl($date) {

        return "SELECT DISTINCT(1) FROM fdecl WHERE RACEDATE='$date'

               ";

    }

    function checkDateInFCard($date) {

        return $this->db->getSingleValue(self::sqlCheckDateInFCard($date));

    }

    

    private static function sqlCheckDateInFCard($date) {

        return "SELECT DISTINCT(1) FROM fcard WHERE RACEDATE='$date'

               ";

    }

    function checkDateInFHorse5($date) {

        return $this->db->getSingleValue(self::sqlCheckDateInFHorse5($date));

    }

    

    private static function sqlCheckDateInFHorse5($date) {

        return "SELECT DISTINCT(1) FROM fhorse5 WHERE RACEDATE='$date'

               ";

    }

    

    function getMaxDate($field,$table) {

        return $this->db->getSingleValue(self::sqlGetMaxDate($field,$table));        

    }

    

    private static function sqlGetMaxDate($field,$table) {

        return "SELECT MAX($field)

                 FROM $table

               ";

    }

    

    function getTrainerStats() {

        return $this->db->getMultiDimensionalArray(self::sqlGetTrainerStats());

    }

    

    private static function sqlGetTrainerStats() {

        return "SELECT NAME,LMOUNTS,LWIN,LSEC,LTHI,LFOU

                FROM statt ORDER BY LWIN DESC,LSEC DESC,LTHI DESC, LFOU DESC";

    } 

    

    function getJockeyStats() {

        return $this->db->getMultiDimensionalArray(self::sqlGetJockeyStats());

    }

    

    private static function sqlGetJockeyStats() {

        return "SELECT NAME,LMOUNTS,LWIN,LSEC,LTHI,LFOU

                FROM statj ORDER BY LWIN DESC,LSEC DESC,LTHI DESC, LFOU DESC";

    }

    

    function getHorseBodyWeight(){

        return $this->db->getMultiDimensionalArray(self::sqlGetHorseBodyWeight());

    }

    private static function sqlGetHorseBodyWeight() {

        return "SELECT * FROM horsewt ORDER BY HORSENAME";

    } 

    

    function getHorseBodyWeightBySearch($search){

        return $this->db->getMultiDimensionalArray(self::sqlGetHorseBodyWeightBySearch($search));

    }

    private static function sqlGetHorseBodyWeightBySearch($search) {

        return "SELECT * FROM horsewt WHERE HORSENAME LIKE '%$search%' ORDER BY HORSENAME";

    }

    

    function getracedate($search, $race_no){

        return $this->db->getMultiDimensionalArray(self::sqlGetracedate($search, $race_no));

    }

    

     private static function sqlGetracedate($search, $race_no) {

         

        // echo"<pre>";print_r($search);

        $var = date("Y-m-d", strtotime($search) );

        if($race_no != ''){

             return "SELECT `RACEDATE`, `HORSENM`, `CARDNO`, `RACENO`, `TIMEIN`, `PLACING`, `TIMINGMTS`, `TIMINGSEC`, `TIMINGSECD` FROM `scale` WHERE  RACEDATE = '$var' AND RACENO ='$race_no' ";

            

        } else {

            return "SELECT `RACEDATE`, `HORSENM`, `CARDNO`, `RACENO`, `TIMEIN`, `PLACING`, `TIMINGMTS`, `TIMINGSEC`, `TIMINGSECD` FROM `scale` WHERE  RACEDATE = '$var' ";

        }

    }   

    

    function getrace_datas($export){

        return $this->db->getMultiDimensionalArray(self::sqlGetrace_datas($export));

    }

    private static function sqlGetrace_datas($export) {

        // echo"<pre>";print_r($export);

        $var = date("Y-m-d", strtotime($export) );

        // echo "SELECT * FROM scale WHERE RACEDATE = '$var' ";

        // exit;

           return "SELECT * FROM scale WHERE RACEDATE = '$var' ";

    }

    

    function getrace_api($api, $race_no){

        return $this->db->getMultiDimensionalArray(self::sqlGetrace_api($api, $race_no));

    }

    private static function sqlGetrace_api($api, $race_no) {

        // echo"<pre>";print_r($export);

        $var = date("Y-m-d", strtotime($api) );

        // echo "SELECT * FROM scale WHERE RACEDATE = '$var' ";

        // exit;

    

            if($race_no != ''){

               return "SELECT * FROM scale WHERE RACEDATE = '$var' AND RACENO ='$race_no' ";

        } else {

            return "SELECT * FROM scale WHERE RACEDATE = '$var' ";

        }

    }

    

    function getrace_dec($api){

        return $this->db->getMultiDimensionalArray(self::sqlGetrace_dec($api));

    }

    private static function sqlGetrace_dec($api) {

        // echo"<pre>";print_r($export);

        $var = date("Y-m-d", strtotime($api) );

        // echo "SELECT * FROM fdecl WHERE RACEDATE = '$var' ";

        // exit;

           return "SELECT * FROM fcard WHERE RACEDATE = '$var' GROUP BY RACENO  ";

    }

    

    function getracedatas_dec($sha,$race){

        return $this->db->getMultiDimensionalArray(self::sqlGetracedatas_dec($sha,$race));

    }

    private static function sqlGetracedatas_dec($sha,$race) {

        // echo"<pre>";print_r($export);

         $var = date("Y-m-d", strtotime($race) );

        // echo "SELECT * FROM fcard WHERE RACENO = '$sha' AND RACEDATE = '$var' ORDER BY CARDNO ASC  ";

        // exit;

           return "SELECT * FROM fcard WHERE RACENO = '$sha' AND RACEDATE = '$var' ORDER BY CARDNO ASC ";

    }

    

    

    function getWebstatsData(){

        return $this->db->getMultiDimensionalArray(self::sqlGetWebstatsData());

    }

    

    private static function sqlGetWebstatsData(){

        return "SELECT * FROM webstats";

    }

    

    function purgeTable($tableName) {

        return $this->db->query(self::sqlPurgeTable($tableName));

    }

    

    private static function sqlPurgeTable($tableName){       

        return "DELETE FROM $tableName";

    }

    

    

    function getRecent4PreRaceDates() {

       return $this->db->getSingleValueArray(self::sqlGetRecent4PreRaceDates()); 

    }

    

    private static function  sqlGetRecent4PreRaceDates() {

        //return "SELECT DISTINCT RACEDATE FROM weights ORDER BY RACEDATE DESC LIMIT 0,4";

        return "SELECT RACEDATE FROM (SELECT DISTINCT RACEDATE FROM weights

                UNION

                SELECT DISTINCT RACEDATE FROM decl) as tmp 

                ORDER BY RACEDATE DESC LIMIT 4";

    }

    

    function getRecent4PostRaceDates() {

       return $this->db->getSingleValueArray(self::sqlGetRecent4PostRaceDates()); 

    }

    

    private static function  sqlGetRecent4PostRaceDates() {

        return "SELECT DISTINCT racedate FROM videos ORDER BY racedate DESC LIMIT 0,4";

        //return "SELECT DISTINCT RACEDATE FROM `fhorse5` ORDER BY RACEDATE DESC LIMIT 0 , 4";

    }                                                  

    

    function checkDatesInTableByDates($tableName,$datesArray){

        return  $this->db->getSingleValueArray(self::sqlCheckDatesInTableByDates($tableName,$datesArray));         

    }

    

    private static function sqlCheckDatesInTableByDates($tableName,$datesArray){

       return "SELECT DISTINCT d.RACEDATE

                FROM {$tableName} d

                WHERE d.RACEDATE='{$datesArray[0]}' OR d.RACEDATE='{$datesArray[1]}' OR d.RACEDATE='{$datesArray[2]}' OR d.RACEDATE='{$datesArray[3]}'

              ";     

    }



    

    /******************* Live REsults ******************************/

    

     function getLiveResults() {

         return $this->db->getMultiDimensionalArray(self::sqlGetLiveResults());

     }

     

     private static function sqlGetLiveResults() {

        /* return "SELECT * FROM scale

                 WHERE PLACING > 0 ORDER BY RACENO ASC,PLACING ASC

                ";*/

        return "SELECT s.*,fc.TRAINERNM,fc.FINALNAME, fc.FINALNAME1, fc.FINALNAME2 FROM scale s

                INNER JOIN fcard fc 

                ON s.HORSESEQ=fc.HORSESEQ

                WHERE PLACING > 0 AND s.RACEDATE=fc.RACEDATE ORDER BY RACENO ASC,PLACING ASC";

     }

    

     function getDateFromScale() {

         return $this->db->getSingleValue(self::sqlGetDateFromScale());

     }

     

      private static function sqlGetDateFromScale() {

         return "SELECT DISTINCT RACEDATE FROM scale

                ";     

      }

    

     function getProspectByLinkAndDate($date,$link) {

         return $this->db->getSingleRowAssoc(self::sqlGetProspectByLinkAndDate($date,$link));

     }

     

     private static function sqlGetProspectByLinkAndDate($date,$link) {

        return "SELECT * FROM prospect

WHERE DATE='$date' AND SRNO=$link";       

      }                                                           

      

      function getLanSinglInfoByDateAndRaceNo($date,$raceno) {

        return $this->db->getSingleRowAssoc(self::sqlGetLanSinglInfoByDateAndRaceNo($date,$raceno));

    }

    

    private static function sqlGetLanSinglInfoByDateAndRaceNo($date,$raceno) {

        return "SELECT * 

                FROM lansingl l

                WHERE RACEDATE='$date' AND RACENO=$raceno

               ";

    }

    

    

    function getWinningHorseseqFromFHORSE5($racedate,$raceno) {

        return$this->db->getSingleValue(self::sqlGetWinningHorseseqFromFHORSE5($racedate,$raceno));

    }

     private static function sqlGetWinningHorseseqFromFHORSE5($racedate,$raceno) {

         return "SELECT HORSESEQ FROM fhorse5 WHERE RACEDATE='$racedate' AND RACENO=$raceno AND PLACING=1";

     }

     

     function getWinningHorseseqFromScale($racedate,$raceno) {

        return $this->db->getSingleValue(self::sqlGetWinningHorseseqFromScale($racedate,$raceno));

    }

     private static function sqlGetWinningHorseseqFromScale($racedate,$raceno) {

         return "SELECT HORSESEQ FROM scale WHERE RACEDATE='$racedate' AND RACENO_SEA=$raceno AND PLACING=1";

     }

     

     function getRaceName($racedate,$searaceno) {

       return $this->db->getSingleRowAssoc(self::sqlGetRaceName($racedate,$searaceno));

     }

         

     private static function sqlGetRaceName($racedate,$searaceno) {

       

        return "SELECT RACENAME,RACETERM,DISTANCE

                FROM racenm r

                WHERE r.RACEDATE='$racedate' AND r.RACENO=$searaceno

               ";

     }   

         

    /******************* Live REsults ******************************/

    

    

    function getFoalDataByMareName($mareName,$damNat) {

       return $this->db->getMultiDimensionalArray(self::sqlGetFoalDataByMareName($mareName,$damNat)); 

    }

    

    private static function sqlGetFoalDataByMareName($mareName,$damNat) { 

        /*$cond = '';

        if ($damNat != '') {

            $cond = 'AND MARENAT="'.$damNat.'"';

        } */

        return "SELECT * 

                FROM niranjan

                WHERE MARENAME='$mareName' AND MARENAT='$damNat' ORDER BY YROFFLNG ASC";

    }

    

    function getHorsepowerResults(){

         return $this->db->getMultiDimensionalArray(self::sqlGetHorsepowerResults());

    }

    

    private static function sqlGetHorsepowerResults() {       

        return "SELECT * 

                FROM first25";                

    }

    

    

    function getHorseseqFromName($horseName) {

        return $this->db->getSingleValue(self::sqlGetHorseseqFromName($horseName));

    }

    

    private static function sqlGetHorseseqFromName($horseName) {       

        return "SELECT horseseq 

                FROM hmaster WHERE HORSENM='$horseName' ORDER BY horseseq DESC LIMIT 1";                

    }



    function getbanner_datas(){

        return $this->db->getMultiDimensionalArray(self::sqlGetBannerDatas());

    }



    private static function sqlGetBannerDatas() {       

        return "SELECT * FROM banner WHERE 1=1 ORDER BY `sort_order`";                

    } 



    function insertBannerimage($sql){

        $this->db->insert(self::sqlInsertBanner($sql));

    }



    private static function sqlInsertBanner($sql){

        return $sql;

    }  



    function getBannerById($id) {

        return $this->db->getSingleValue(self::sqlGetBannerById($id));

    }

    

    private static function sqlGetBannerById($id) {       

        return "SELECT `source` FROM `banner` WHERE `id` = '".$id."' ";                

    }



    function deleteBannerByID($id) {

        $this->db->query(self::sqlDeleteBanner($id));

    }

    

    private static function sqlDeleteBanner($id) {

        return "DELETE FROM `banner` WHERE `id`= '".$id."' ";

    }



    function updateBanner($id,$title,$link,$sort_order) {

        return $this->db->update(self::sqlUpdateBanner($id,$title,$link,$sort_order));

    }

    

    private static function sqlUpdateBanner($id,$title,$link,$sort_order) {

        return "UPDATE `banner` SET title='".$title."', `link` = '".$link."', `sort_order` = '".$sort_order."' WHERE `id` = '".$id."' ";

    }



    function getsponsor_datas(){

        return $this->db->getMultiDimensionalArray(self::sqlGetSponsorDatas());

    }



    private static function sqlGetSponsorDatas() {       

        return "SELECT * FROM sponsor WHERE 1=1 ORDER BY `sort_order` ";                

    } 



    function insertSponsorimage($sql){

        

        $this->db->insert(self::sqlInsertSponsor($sql));

    }

    function inserthomepopup($sql){

        $this->db->insert(self::sqlInserthomepopup($sql));

    }

    private static function sqlInserthomepopup($sql){

        return $sql;

    } 

    



    private static function sqlInsertSponsor($sql){

        

        return $sql;

    }  



    function getSponsorById($id) {

        return $this->db->getSingleValue(self::sqlGetSponsorById($id));

    }

    

    private static function sqlGetSponsorById($id) {       

        return "SELECT `source` FROM `sponsor` WHERE `id` = '".$id."' ";                

    }



    function deleteSponsorByID($id) {

        $this->db->query(self::sqlDeleteSponsor($id));

    }

    

    private static function sqlDeleteSponsor($id) {

        return "DELETE FROM `sponsor` WHERE `id`= '".$id."' ";

    }



    function updateSponsor($id,$title,$link,$sort_order) {

        return $this->db->update(self::sqlUpdateSponsor($id,$title,$link,$sort_order));

    }

    

    private static function sqlUpdateSponsor($id,$title,$link,$sort_order) {

        return "UPDATE `sponsor` SET title='".$title."', `link` = '".$link."', `sort_order` = '".$sort_order."' WHERE `id` = '".$id."' ";

    }



    function getticker_datas(){

        return $this->db->getMultiDimensionalArray(self::sqlGetTickerDatas());

    }



    private static function sqlGetTickerDatas() {       

        return "SELECT * FROM tickers WHERE `published` = 'Y' ORDER BY `sort_order` ";                

    }



    function getsponsoroftheday_datas(){

        return $this->db->getMultiDimensionalArray(self::sqlGetSponsorofthedayDatas());

    }



    private static function sqlGetSponsorofthedayDatas() {       

        return "SELECT * FROM sponsoroftheday WHERE 1=1 ORDER BY `sort_order` ";                

    } 



    function insertSponsorofthedayimage($sql){

        $this->db->insert(self::sqlInsertSponsoroftheday($sql));

    }



    private static function sqlInsertSponsoroftheday($sql){

        return $sql;

    }  



    function getSponsorofthedayById($id) {

        return $this->db->getSingleValue(self::sqlGetSponsorofthedayById($id));

    }

    

    private static function sqlGetSponsorofthedayById($id) {       

        return "SELECT `source` FROM `sponsoroftheday` WHERE `id` = '".$id."' ";                

    }



    function deleteSponsorofthedayByID($id) {

        $this->db->query(self::sqlDeleteSponsoroftheday($id));

    }

    

    private static function sqlDeleteSponsoroftheday($id) {

        return "DELETE FROM `sponsoroftheday` WHERE `id`= '".$id."' ";

    }



    function updateSponsoroftheday($id,$title,$link,$sort_order) {

        return $this->db->update(self::sqlUpdateSponsoroftheday($id,$title,$link,$sort_order));

    }

    

    private static function sqlUpdateSponsoroftheday($id,$title,$link,$sort_order) {

        return "UPDATE `sponsoroftheday` SET title='".$title."', `link` = '".$link."', `sort_order` = '".$sort_order."' WHERE `id` = '".$id."' ";

    }



    function updateconfig($id, $value){

       return $this->db->update(self::sqlUpdateConfig($id,$value)); 

    }



    private static function sqlUpdateConfig($id,$value) {

        if($id == 7){

            return "UPDATE `config` SET `value1` = '".$value."' WHERE `id` = '".$id."' ";

        } else {

            return "UPDATE `config` SET `value` = '".$value."' WHERE `id` = '".$id."' ";

        }

    }



    function getconfig_datas($id) {

        // echo '<pre>';

        // print_r($id);exit;

        return $this->db->getSingleValue(self::sqlGetConfigById($id));

    }

    

    private static function sqlGetConfigById($id) {

        if($id == 7){       

            return "SELECT `value1` FROM `config` WHERE `id` = '".$id."' ";                

        } else {

            return "SELECT `value` FROM `config` WHERE `id` = '".$id."' ";

        }

    }

    function updatehomepopup($feed,$videourl,$status) {

        return $this->db->update(self::sqlUpdatehomepopup($feed,$videourl,$status));

    }

    

    private static function sqlUpdatehomepopup($feed,$videourl,$status) {

        return "UPDATE `home_popup` SET `feed`='".$feed."', `video_url` = '".$videourl."', `status` = '".$status."' WHERE `id` = '6' ";

    }

    // echo'<pre>';

    // print_r(updatehomepopup($feed,$videourl,$status));exit;



    function clearhorsebodyweight() {

        return $this->db->query(self::sqlUpdateClearHorseBodyWeight());

    }

    

    private static function sqlUpdateClearHorseBodyWeight() {

        //return "UPDATE `horsewt` SET `R1`= '', `W1` = '', `R2`= '', `W2` = '', `R3`= '', `W3` = '', `R4`= '', `W4` = '', `R5`= '', `W5` = '', `R6`= '', `W6` = '', `R7`= '', `W7` = '', `R8`= '', `W8` = '', `R9`= '', `W9` = '', `R10`= '', `W10` = '', `R11`= '', `W11` = '', `R12`= '', `W12` = '', `R13`= '', `W13` = '', `R14`= '', `W14` = '', `R15`= '', `W15` = '', `R16`= '', `W16` = '', `R17`= '', `W17` = '', `R18`= '', `W18` = '', `R19`= '', `W19` = '', `R20`= '', `W20` = '' WHERE 1=1 ";

        return "TRUNCATE `horsewt` ";

    }



    function clearracedata($race_date, $race_type, $race_type_1) {

        if($race_type == 1){

            if($race_type_1 == 1){

                return $this->db->query("DELETE FROM `weights` WHERE `RACEDATE` = '".$race_date."' ");

            } elseif($race_type_1 == 2){

                return $this->db->query("DELETE FROM `fdecl` WHERE `RACEDATE` = '".$race_date."' ");        

            } elseif($race_type_1 == 3){

                return $this->db->query("DELETE FROM `fcard` WHERE `RACEDATE` = '".$race_date."' ");        

            } else if($race_type_1 == 4) {

                return $this->db->query("DELETE FROM `decl` WHERE `RACEDATE` = '".$race_date."' ");        

            }

        } else {

            if($race_type_1 == 4){

                return $this->db->query("DELETE FROM `fhorse5` WHERE `RACEDATE` = '".$race_date."' ");        

            } elseif($race_type_1 == 5){

                return $this->db->query("DELETE FROM `ratings_change` WHERE `RACEDATE` = '".$race_date."' ");        

            } elseif($race_type_1 == 6){

                return $this->db->query("DELETE FROM `raceday_report` WHERE `RACEDATE` = '".$race_date."' ");        

            } elseif($race_type_1 == 7){

                return $this->db->query("DELETE FROM `gallery` WHERE `racedate` = '".$race_date."' ");        

            } elseif($race_type_1 == 8){

                return $this->db->query("DELETE FROM `videos` WHERE `racedate` = '".$race_date."' ");        

            }

        }

    }

}

?>