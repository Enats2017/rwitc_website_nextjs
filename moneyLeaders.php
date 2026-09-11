<?php 
error_reporting(0);
ini_set("display_errors", 0);
include_once('bootstrap.php');
include_once('lib/race.class.php');
  
$pageTitle = 'Money Leaders';        
$design = new Design();
$design->css = "
<link rel='stylesheet' href='https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css'>
<style type='text/css'>

#infoWrapper.col-lg-12 {
    display: flex;                 
    flex-direction: row-reverse;       
    align-items: flex-start;       
    max-width: 1500px;      
    margin: 30px auto;     
    float: none;         
}

#leftArea.col-lg-9 {
    flex: 1 1 auto;
    min-width: 0;
    max-width: none;
    margin: 0;
    padding: 0 30px;
    box-sizing: border-box;
    float: none;
    width: auto;
    display: block;
}

.ml-card {
    background: #fff;
    border: 1px solid #e2e6e4;
    border-radius: 12px;
    padding: 24px;
    box-shadow: 0 1px 2px rgba(0,0,0,0.03);
    position: relative;
}

.ml-header {
    margin: 0 0 20px 0;
    padding: 0;
    border: 0;
}

.ml-header-title h2 {
    margin: 0;
    font-size: 24px;
    font-weight: 400;
    color: #2b332f;
    font-family: inherit;
    display: flex;
    align-items: center;
    gap: 12px;
}

.ml-header-title h2 i {
    color: #0f5c33;
    font-size: 24px;
}

.ml-header-title p {
    margin: 4px 0 0 36px;
    font-size: 13.5px;
    color: #687970;
}

.ml-nav-tabs {
    display: flex;
    gap: 8px;
    border-bottom: 1px solid #e2e6e4;
    margin: 0 0 20px 0;
    padding: 0 0 8px 0;
    flex-wrap: wrap;
    list-style: none;
}

.ml-nav-tabs li {
    margin: 0;
    padding: 0;
}

.ml-tab-btn {
    display: inline-flex;
    align-items: center;
    gap: 7px;
    padding: 9px 16px;
    background: #f6f8f7;
    color: #4a5c53;
    border: 1px solid #e2e6e4;
    border-radius: 6px;
    font-size: 13.5px;
    font-weight: 600;
    text-decoration: none !important;
    cursor: pointer;
}
 
.ml-tab-btn:hover {              
    background: #e6f4ec; 
    color: #0f5c33;           
    border-color: #b7ddc5;
}

.ml-nav-tabs li.active .ml-tab-btn {
    background: #0f5c33;
    color: #fff;
    border-color: #0f5c33;
}

.table-responsive-wrap {
    width: 100%;
    overflow-x: auto;
    -webkit-overflow-scrolling: touch;
    border: 1px solid #e2e6e4;
}

.ml-table {
    width: 100%;
    border-collapse: collapse;
    background: #fff;
    white-space: nowrap;
}

.ml-table th {
    background: #e0e0de;
    color: #000;
    font-size: 13px;
    font-weight: 600;
    padding: 10px 12px;
    text-align: center;
    border: 1px solid #d5d5d3;
    text-transform: none;
    letter-spacing: 0;
}

.ml-table th.align-left {
    text-align: left;
}

.ml-table td {
    padding: 10px 12px;
    font-size: 13.5px;
    color: #2b332f;
    text-align: center;
    border-bottom: 1px dotted #000;
    vertical-align: middle;
}

.ml-table td.align-left {
    text-align: left;
    font-weight: 600;
}

.ml-table tbody tr:hover {
    background: #f7faf8;
}

.ml-table tbody tr:last-child td {
    border-bottom: none;
}

.rank-badge {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 24px;
    height: 24px;
    border-radius: 50%;
    font-size: 12px;
    font-weight: 700;
}

.rank-badge.gold {
    background: #fff3c4;
    color: #856404;
    border: 1px solid #ffeeba;
}

.rank-badge.silver {
    background: #e2e3e5;
    color: #383d41;
    border: 1px solid #d6d8db;
}

.rank-badge.bronze {
    background: #f8d7da;
    color: #721c24;
    border: 1px solid #f5c6cb;
}

.rank-badge.normal {
    background: #f0f4f2;
    color: #55665e;
}

.win-count {
    color: #0f5c33;
    font-weight: 700;
}

.winnings-val {
    font-weight: 700;
    color: #1a7a45;
}

.ml-footer-note {
    padding: 10px 14px;
    background: #f8faf9;
    border-top: 1px solid #eef1ef;
    font-size: 13px;
    color: #687970;
    font-weight: 500;
}

html, body {
    scrollbar-width: none;
    -ms-overflow-style: none;
}

html::-webkit-scrollbar, body::-webkit-scrollbar {
    display: none;
}

@media (max-width: 700px) {
    #leftArea.col-lg-9 {
        padding: 0 16px;
    }

    .ml-card {
        padding: 16px;
    }

    .ml-nav-tabs {
        gap: 6px;
    }

    .ml-tab-btn {
        padding: 8px 12px;
        font-size: 13px;
    }

    .ml-header-title p {
        margin-left: 0;
    }
}

</style>
";

$raceObj = new Racedata($db);
$design->startPage("$pageTitle");
$design->writeLogoTickerMenu();
$design->openDiv("contentWrapper");
$design->openDiv("infoWrapper","col-lg-12");
$design->openDiv("leftArea",'col-lg-9');

$moneyLeaders = $raceObj->getWebstatsData();
$sortedML = array();
if (!empty($moneyLeaders) && is_array($moneyLeaders)) {
    foreach ($moneyLeaders as $money) {
        if (isset($money["CATEGORY"])) {
            $sortedML[$money["CATEGORY"]][] = $money;
        }
    }
}

function formatIndianNumber($num) {
    $num = preg_replace('/[^\d.]/', '', (string)$num);
    if (!is_numeric($num) || $num == 0) {
        return $num;
    }
    $expl = explode('.', $num);
    $intPart = $expl[0];
    $lastThree = substr($intPart, -3);
    $otherNumbers = substr($intPart, 0, -3);
    if ($otherNumbers != '') {
        $lastThree = ',' . $lastThree;
    }
    $formattedInt = preg_replace('/\B(?=(\d{2})+(?!\d))/', ',', $otherNumbers) . $lastThree;
    if (count($expl) > 1 && !empty($expl[1])) {
        return $formattedInt . '.' . $expl[1];
    }
    return $formattedInt;
}

function renderLeaderTable($dataArr) {
    $html = '<div class="table-responsive-wrap">';
    $html .= '<table class="ml-table">';
    $html .= '<thead>';
    $html .= '<tr>';
    $html .= '<th style="width:50px;">#</th>';
    $html .= '<th class="align-left">Name</th>';
    $html .= '<th>Starts</th>';
    $html .= '<th>Wins</th>';
    $html .= '<th>Second</th>';
    $html .= '<th>Third</th>';
    $html .= '<th>Winnings</th>';
    $html .= '</tr>';
    $html .= '</thead>';
    $html .= '<tbody>';

    for ($i = 0; $i < 10; $i++) {
        if (isset($dataArr[$i])) {
            $rank = $i + 1;
            $badgeClass = ($rank == 1) ? 'gold' : (($rank == 2) ? 'silver' : (($rank == 3) ? 'bronze' : 'normal'));
            $rawWinnings = isset($dataArr[$i]['WINNINGS']) ? trim($dataArr[$i]['WINNINGS']) : '';
            $winnings = formatIndianNumber($rawWinnings);
            if (empty($winnings)) {
                $winnings = '-';
            }

            $html .= '<tr>';
            $html .= '<td><span class="rank-badge '.$badgeClass.'">'.$rank.'</span></td>';
            $html .= '<td class="align-left">'.htmlspecialchars($dataArr[$i]['NAME']).'</td>';
            $html .= '<td>'.$dataArr[$i]['STARTS'].'</td>';
            $html .= '<td><span class="win-count">'.$dataArr[$i]['WINS'].'</span></td>';
            $html .= '<td>'.$dataArr[$i]['SECOND'].'</td>';
            $html .= '<td>'.$dataArr[$i]['THIRD'].'</td>';
            $html .= '<td><span class="winnings-val">'.$winnings.'</span></td>';
            $html .= '</tr>';
        }
    }

    $html .= '</tbody>';
    $html .= '</table>';

    if (isset($dataArr[10]['NAME'])) {
        $html .= '<div class="ml-footer-note"><i class="far fa-clock"></i> '.htmlspecialchars($dataArr[10]['NAME']).'</div>';
    }

    $html .= '</div>';
    return $html;
}
?>

<div class="ml-card">
    <div class="ml-header">
        <div class="ml-header-title">
            <h2><i class="fas fa-trophy"></i> Money Leaders</h2>
            <p>Current standings &amp; earnings across Owners, Jockeys, Horses, and Trainers.</p>
        </div>
    </div>

    <ul class="nav ml-nav-tabs" role="tablist">
        <li class="active"><a class="ml-tab-btn" data-toggle="tab" href="#ownersTab">OWNERS</a></li>
        <li><a class="ml-tab-btn" data-toggle="tab" href="#jockeysTab">JOCKEYS</a></li>
        <li><a class="ml-tab-btn" data-toggle="tab" href="#horsesTab">HORSES</a></li>
        <li><a class="ml-tab-btn" data-toggle="tab" href="#trainersTab">TRAINERS</a></li>
    </ul>

    <div class="tab-content">
        <div id="ownersTab" class="tab-pane fade in active">
            <?php echo renderLeaderTable(isset($sortedML['Owners']) ? $sortedML['Owners'] : array()); ?>
        </div>
        <div id="jockeysTab" class="tab-pane fade">
            <?php echo renderLeaderTable(isset($sortedML['Jockeys']) ? $sortedML['Jockeys'] : array()); ?>
        </div>
        <div id="horsesTab" class="tab-pane fade">
            <?php echo renderLeaderTable(isset($sortedML['Horses']) ? $sortedML['Horses'] : array()); ?>
        </div>
        <div id="trainersTab" class="tab-pane fade">
            <?php echo renderLeaderTable(isset($sortedML['Trainers']) ? $sortedML['Trainers'] : array()); ?>
        </div>
    </div>
</div>

<?php
$design->closeDiv();
$design->writeLeftPanel();
$design->closeDiv();
$design->closeDiv();
$design->endPage();
$design = NULL;
?>
