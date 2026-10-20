<?php
include_once('../bootstrap.php');
require_once('../lib/users.class.php');
require_once('../lib/userchecks.php');
require_once('../lib/permissions.php');

session_start();
$userObj = new Users($db);

// ---------------------------------------------------------------
// SETTINGS
// ---------------------------------------------------------------
$pageTitleText = 'Best Timings';
$moduleKey     = 'record_timings';
$pageFile      = 'recordTimingsManager.php';
$sessionKey    = 'record_timings_form';

// ---------------------------------------------------------------
// LOGIN + MODULE ACCESS CHECK
// ---------------------------------------------------------------
$secmsg = '';
if (!isAdminlogin()) {
    $secmsg = 'You do not have access to this page.';
} elseif (!hasModuleAccess($moduleKey)) {
    $secmsg = 'You do not have access to this module.';
}

// CSRF token
if (empty($_SESSION[$sessionKey . '_csrf'])) {
    $_SESSION[$sessionKey . '_csrf'] = bin2hex(random_bytes(16));
}
$csrf = $_SESSION[$sessionKey . '_csrf'];

// Flash message
$flash     = isset($_SESSION[$sessionKey . '_flash']['text']) ? $_SESSION[$sessionKey . '_flash']['text'] : '';
$flashType = isset($_SESSION[$sessionKey . '_flash']['type']) ? $_SESSION[$sessionKey . '_flash']['type'] : '';
unset($_SESSION[$sessionKey . '_flash']);

// ---------------------------------------------------------------
// HELPERS
// ---------------------------------------------------------------
function rtSetFlash($text, $type)
{
    global $sessionKey;
    $_SESSION[$sessionKey . '_flash'] = array('text' => $text, 'type' => $type);
}

function rtH($v)
{
    return htmlspecialchars((string) $v, ENT_QUOTES, 'UTF-8');
}

/**
 * Common fields (horse, weight, date, min, sec, notes) ko validate + clean karta hai.
 * Success: array, Error: null (aur $err me message).
 */
function rtClean($src, &$err)
{
    $horse = trim(isset($src['horse_name']) ? (string) $src['horse_name'] : '');
    if ($horse === '' || mb_strlen($horse) > 150) {
        $err = 'Horse name is required (max 150 characters).';
        return null;
    }

    $weight = isset($src['weight']) ? trim((string) $src['weight']) : '';
    if ($weight === '' || !is_numeric($weight) || (float) $weight <= 0 || (float) $weight > 99.9) {
        $err = 'Weight must be a number between 0.1 and 99.9.';
        return null;
    }

    $date = isset($src['race_date']) ? trim((string) $src['race_date']) : '';
    $dt   = DateTime::createFromFormat('Y-m-d', $date);
    if (!$dt || $dt->format('Y-m-d') !== $date) {
        $err = 'Please choose a valid race date.';
        return null;
    }

    $minRaw = isset($src['time_min']) ? trim((string) $src['time_min']) : '0';
    if ($minRaw === '') {
        $minRaw = '0';
    }
    if (!ctype_digit($minRaw) || (int) $minRaw > 9) {
        $err = 'Timing minutes must be between 0 and 9.';
        return null;
    }

    $secRaw = isset($src['time_sec']) ? trim((string) $src['time_sec']) : '';
    if (!preg_match('/^(\d{1,2})(\.\d{1,3})?$/', $secRaw, $m) || (int) $m[1] > 59) {
        $err = 'Timing seconds must look like 56.513 (0 to 59.999).';
        return null;
    }
    $sec = str_pad($m[1], 2, '0', STR_PAD_LEFT) . (isset($m[2]) ? $m[2] : '');

    if ((int) $minRaw === 0 && (float) $sec <= 0) {
        $err = 'Timing cannot be zero.';
        return null;
    }

    $notes = trim(isset($src['notes']) ? (string) $src['notes'] : '');
    if (mb_strlen($notes) > 255) {
        $err = 'Notes can be at most 255 characters.';
        return null;
    }

    return array(
        'horse_name' => $horse,
        'weight'     => number_format((float) $weight, 1, '.', ''),
        'race_date'  => $date,
        'time_min'   => (int) $minRaw,
        'time_sec'   => $sec,
        'notes'      => $notes
    );
}

// ---------------------------------------------------------------
// WHICH TRACK (mumbai | pune)
// ---------------------------------------------------------------
$trackReq = ($_SERVER['REQUEST_METHOD'] === 'POST')
    ? (isset($_POST['track']) ? $_POST['track'] : '')
    : (isset($_GET['track']) ? $_GET['track'] : '');
$track = ($trackReq === 'pune') ? 'pune' : 'mumbai';

// Table bana hai ya nahi
$tableOk = false;
if ($secmsg === '') {
    $chk     = $db->getMultiDimensionalArray("SHOW TABLES LIKE 'record_timings'");
    $tableOk = !empty($chk);
}

// ---------------------------------------------------------------
// POST ACTIONS: save_rows | add | delete
// ---------------------------------------------------------------
if (empty($secmsg) && $tableOk && $_SERVER['REQUEST_METHOD'] === 'POST') {

    $redirect = $pageFile . '?track=' . $track;

    if (!isset($_POST['csrf']) || !hash_equals($csrf, (string) $_POST['csrf'])) {
        rtSetFlash('Invalid request. Please try again.', 'error');
        header('Location: ' . $redirect);
        exit;
    }

    $act = isset($_POST['action']) ? $_POST['action'] : '';

    try {

        /* ---------------- SAVE ALL ROWS ---------------- */
        if ($act === 'save_rows') {

            $rows  = (isset($_POST['rows']) && is_array($_POST['rows'])) ? $_POST['rows'] : array();
            $clean = array();
            $err   = '';

            foreach ($rows as $id => $r) {
                $id = (int) $id;
                if ($id <= 0 || !is_array($r)) {
                    continue;
                }
                $e = '';
                $c = rtClean($r, $e);
                if ($c === null) {
                    $name = isset($r['horse_name']) ? trim((string) $r['horse_name']) : '';
                    $err  = ($name !== '' ? '"' . $name . '": ' : '') . $e;
                    break;
                }
                $c['visible'] = (isset($r['visible']) && $r['visible'] === '1') ? 1 : 0;
                $clean[$id]   = $c;
            }

            if ($err !== '') {
                // Koi bhi row galat ho to kuch save nahi hota
                rtSetFlash('Nothing was saved. ' . $err, 'error');
            } else {
                foreach ($clean as $id => $c) {
                    $db->update(
                        "UPDATE record_timings SET
                            horse_name = '" . $db->escape($c['horse_name']) . "',
                            weight     = " . $c['weight'] . ",
                            race_date  = '" . $db->escape($c['race_date']) . "',
                            time_min   = " . (int) $c['time_min'] . ",
                            time_sec   = '" . $db->escape($c['time_sec']) . "',
                            notes      = " . ($c['notes'] === '' ? 'NULL' : "'" . $db->escape($c['notes']) . "'") . ",
                            is_visible = " . (int) $c['visible'] . "
                         WHERE id = " . (int) $id . " AND track = '" . $track . "'"
                    );
                }
                rtSetFlash('Changes saved successfully.', 'success');
            }
        }

        /* ---------------- ADD NEW RECORD ---------------- */ elseif ($act === 'add') {

            $err      = '';
            $distance = isset($_POST['distance']) ? trim((string) $_POST['distance']) : '';
            $surface  = 'main';
            if ($track === 'pune' && isset($_POST['surface']) && $_POST['surface'] === 'monsoon') {
                $surface = 'monsoon';
            }

            if (!ctype_digit($distance) || (int) $distance < 100 || (int) $distance > 9999) {
                $err = 'Distance must be a number of metres (100 to 9999).';
                $c   = null;
            } else {
                $c = rtClean($_POST, $err);
            }

            if ($c === null) {
                rtSetFlash($err, 'error');
            } else {
                $db->insert(
                    "INSERT INTO record_timings
                        (track, surface, distance, horse_name, weight, race_date, time_min, time_sec, notes, is_visible)
                     VALUES (
                        '" . $track . "',
                        '" . $surface . "',
                        " . (int) $distance . ",
                        '" . $db->escape($c['horse_name']) . "',
                        " . $c['weight'] . ",
                        '" . $db->escape($c['race_date']) . "',
                        " . (int) $c['time_min'] . ",
                        '" . $db->escape($c['time_sec']) . "',
                        " . ($c['notes'] === '' ? 'NULL' : "'" . $db->escape($c['notes']) . "'") . ",
                        1
                     )"
                );
                rtSetFlash('Record added successfully.', 'success');
            }
        }

        /* ---------------- DELETE ---------------- */ elseif ($act === 'delete') {

            $id = isset($_POST['id']) ? (int) $_POST['id'] : 0;
            if ($id > 0) {
                $db->query("DELETE FROM record_timings WHERE id = " . $id . " AND track = '" . $track . "'");
                rtSetFlash('Record deleted.', 'success');
            }
        }
    } catch (Exception $e) {
        error_log('Record Timings save failed: ' . $e->getMessage());
        rtSetFlash('Database error. Could not save.', 'error');
    }

    header('Location: ' . $redirect);
    exit;
}

// ---------------------------------------------------------------
// PAGE DATA
// ---------------------------------------------------------------
$bySurface = array('main' => array(), 'monsoon' => array());

if ($secmsg === '' && $tableOk) {
    $list = $db->getMultiDimensionalArray(
        "SELECT * FROM record_timings WHERE track = '" . $track . "' ORDER BY distance ASC, id ASC"
    );
    if (is_array($list)) {
        foreach ($list as $r) {
            $s = ($r['surface'] === 'monsoon') ? 'monsoon' : 'main';
            $bySurface[$s][] = $r;
        }
    }
}

/**
 * Ek editable block print karta hai (Mumbai = 1 block, Pune = 2 blocks).
 * Table ki jagah CSS-grid rows: badi screen pe table jaisa, chhoti screen pe card jaisa.
 */
function rtRenderTable($rows, $title)
{
    ?>
    <div class="rt-block">
        <div class="rt-block-title"><?php echo rtH($title); ?></div>

        <div class="rt-grid">

            <div class="rt-cols rt-head">
                <div>Distance<small>(Metres)</small></div>
                <div>Horse Name<small>(Indian Horses)</small></div>
                <div>Weight<small>(Kgs)</small></div>
                <div>Race Date</div>
                <div class="rt-h-timing">Timing<small>(Min : Sec)</small></div>
                <div>Notes (Optional)</div>
                <div>Action</div>
            </div>

            <?php if (empty($rows)) { ?>
                <div class="rt-empty">No records yet. Use "Add Custom Distance" below.</div>
            <?php } ?>

            <?php foreach ($rows as $r) {
                $id  = (int) $r['id'];
                $vis = ((int) $r['is_visible'] === 1);
                ?>
                <div class="rt-cols rt-row <?php echo $vis ? '' : 'rt-off'; ?>">

                    <div class="c-dist"><span class="rt-dist"><?php echo (int) $r['distance']; ?> Metres</span></div>

                    <div class="c-horse">
                        <span class="rt-lbl">Horse Name</span>
                        <input type="text" name="rows[<?php echo $id; ?>][horse_name]" value="<?php echo rtH($r['horse_name']); ?>" maxlength="150">
                    </div>

                    <div class="c-weight">
                        <span class="rt-lbl">Weight (Kgs)</span>
                        <input type="number" step="0.1" min="0.1" max="99.9" name="rows[<?php echo $id; ?>][weight]" value="<?php echo rtH(number_format((float) $r['weight'], 1, '.', '')); ?>">
                    </div>

                    <div class="c-date">
                        <span class="rt-lbl">Race Date</span>
                        <input type="date" name="rows[<?php echo $id; ?>][race_date]" value="<?php echo rtH($r['race_date']); ?>">
                    </div>

                    <div class="c-min">
                        <span class="rt-lbl">Min</span>
                        <input type="number" min="0" max="9" name="rows[<?php echo $id; ?>][time_min]" value="<?php echo (int) $r['time_min']; ?>">
                    </div>

                    <div class="c-sec">
                        <span class="rt-lbl">Sec</span>
                        <input type="text" name="rows[<?php echo $id; ?>][time_sec]" value="<?php echo rtH($r['time_sec']); ?>" maxlength="6">
                    </div>

                    <div class="c-notes">
                        <span class="rt-lbl">Notes (Optional)</span>
                        <input type="text" name="rows[<?php echo $id; ?>][notes]" value="<?php echo rtH($r['notes']); ?>" maxlength="255">
                    </div>

                    <div class="c-act rt-actions">
                        <input type="hidden" class="rt-vis" name="rows[<?php echo $id; ?>][visible]" value="<?php echo $vis ? '1' : '0'; ?>">
                        <button type="button" class="rt-icon rt-eye" title="<?php echo $vis ? 'Visible on website (click to hide)' : 'Hidden on website (click to show)'; ?>">
                            <i class="fas <?php echo $vis ? 'fa-eye' : 'fa-eye-slash'; ?>"></i>
                        </button>
                        <button type="submit" form="rtDeleteForm" name="id" value="<?php echo $id; ?>" class="rt-icon rt-del" title="Delete" formnovalidate
                            onclick="return confirm('Delete this record? This cannot be undone.');">
                            <i class="fas fa-trash-alt"></i>
                        </button>
                    </div>

                </div>
            <?php } ?>

        </div>
    </div>
    <?php
}

$pageTitle = $pageTitleText;
$design = new Design();
$design->js = '';
$design->css = '';
$design->jqueryJs = '';
$design->startPage($pageTitle);
$design->writeLogoTickerMenu();
$design->openDiv('contentWrapper');
$design->openDiv('infoWrapper', 'col-lg-12');
$design->openDiv('leftArea', 'col-lg-9');
$design->writeContentPageStyles();
?>
<style>
.message{padding:12px 16px;border-radius:8px;margin-bottom:15px;font-size:15px;background:#fff3cd;border:1px solid #ffe08a}.message.success{background:#e3f6ea;border-color:#a9dcbd;color:#0f5c33}.message.error{background:#fde8e8;border-color:#f5b5b5;color:#a12626}
.main-content{max-width:100%;min-width:0}
.ar-header{margin-bottom:18px}.ar-title{font-size:26px;font-weight:800;color:#0d2a1c;margin:0}.ar-subtitle{font-size:14px;color:#7a8c84;margin:6px 0 0}
.rt-label{font-size:13px;color:#667066;margin:0 0 8px}
.rt-tabs{display:flex;gap:10px;margin-bottom:20px;flex-wrap:wrap}
.rt-tab{display:inline-flex;align-items:center;justify-content:center;min-width:130px;padding:10px 22px;border-radius:8px;border:1px solid #d3d9d6;background:#fff;color:#2b332f;font-size:14px;font-weight:600;text-decoration:none}
.rt-tab:hover{border-color:#0f5c33;color:#0f5c33}
.rt-tab.active{background:#0f5c33;border-color:#0f5c33;color:#fff}

/* card: container-query ke liye width yahin se naapi jaati hai */
.rt-card{background:#fff;border:1px solid #e2e6e4;border-radius:12px;padding:14px;box-shadow:0 1px 2px rgba(0,0,0,.03);margin-bottom:24px;container-type:inline-size;container-name:rt;min-width:0;max-width:100%}
.rt-block{margin-bottom:22px}.rt-block:last-of-type{margin-bottom:0}
.rt-block-title{background:#e6f4ec;color:#0b3d24;font-size:17px;font-weight:800;padding:13px 16px;border-radius:8px 8px 0 0;line-height:1.3}

/* ---------- GRID ROWS (wide screen = table jaisa) ---------- */
.rt-grid{border:1px solid #e2e6e4;border-top:0;border-radius:0 0 8px 8px;overflow:hidden}
.rt-cols{display:grid;grid-template-columns:104px minmax(0,1.5fr) 78px 142px 60px 92px minmax(0,1.5fr) 84px;gap:8px;align-items:center;padding:8px 10px}
.rt-head{background:#f3f8f5;border-bottom:1px solid #dfe8e2;font-size:13px;font-weight:700;color:#0b3d24;text-align:center;line-height:1.25}
.rt-head small{display:block;font-weight:500;color:#5f6f66;font-size:11px}
.rt-head .rt-h-timing{grid-column:span 2}
.rt-row{border-bottom:1px solid #eef1ef}.rt-row:last-child{border-bottom:0}
.rt-row>div{min-width:0}
.rt-dist{color:#d11a1a;font-weight:700;font-size:13.5px;white-space:nowrap}
.rt-lbl{display:none}
.rt-row input[type=text],.rt-row input[type=number],.rt-row input[type=date],.rt-add input,.rt-add select{display:block;width:100%;min-width:0;height:38px;padding:0 10px;border:1px solid #d9dfdc;border-radius:7px;font-size:13.5px;box-sizing:border-box;background:#fff;color:#2b332f;margin:0}
.rt-row input:focus,.rt-add input:focus,.rt-add select:focus{outline:none;border-color:#0f5c33;box-shadow:0 0 0 3px rgba(15,92,51,.1)}
.rt-row.rt-off{background:#f6f6f6}.rt-row.rt-off input{opacity:.55}
.rt-actions{display:flex;gap:6px;justify-content:center;align-items:center}
.rt-icon{width:34px;height:34px;border-radius:8px;border:1px solid transparent;display:inline-flex;align-items:center;justify-content:center;cursor:pointer;font-size:14px;margin:0;padding:0;flex-shrink:0}
.rt-eye{background:#e6f4ec;color:#0f5c33}.rt-eye:hover{background:#d4ecdc}
.rt-del{background:#fdecec;color:#d11a1a}.rt-del:hover{background:#fad8d8}
.rt-empty{text-align:center;padding:26px 10px;color:#7a8c84;font-size:14px}

.rt-save-bar{display:flex;align-items:center;gap:12px;margin-top:16px;flex-wrap:wrap}
.rt-hint{font-size:12.5px;color:#7a8c84;flex:1 1 220px;min-width:0}
.rt-btn{display:inline-flex;align-items:center;justify-content:center;gap:8px;padding:11px 22px;font-size:14.5px;font-weight:700;border-radius:8px;border:1px solid transparent;cursor:pointer;background:#0f5c33;color:#fff;max-width:100%}
.rt-btn:hover{background:#1a7a45}

/* ---------- ADD FORM (kabhi bahar nahi jaata: auto-fit grid) ---------- */
.rt-add-title{background:#e6f4ec;color:#0b3d24;font-size:17px;font-weight:800;padding:13px 16px;border-radius:8px 8px 0 0}
.rt-add{display:grid;grid-template-columns:repeat(auto-fit,minmax(170px,1fr));gap:14px;align-items:end;padding:16px;border:1px solid #e2e6e4;border-top:0;border-radius:0 0 8px 8px}
.rt-add>div{min-width:0}
.rt-add label{display:block;font-size:12.5px;font-weight:700;color:#2b332f;margin-bottom:5px}.rt-add label small{font-weight:500;color:#5f6f66}
.rt-add input,.rt-add select{height:40px}
.rt-add .rt-add-btn{grid-column:1/-1}
.rt-warn{background:#fff3cd;border:1px solid #ffe08a;border-radius:8px;padding:14px 16px;font-size:14px;margin-bottom:16px}
html,body{scrollbar-width:none;-ms-overflow-style:none}html::-webkit-scrollbar,body::-webkit-scrollbar{display:none}

/* ---------- CARD MODE: jab card ki width kam ho (laptop + sidebar / tablet / mobile) ---------- */
@container rt (max-width: 940px){
  .rt-head{display:none}
  .rt-grid{border:0;border-radius:0}
  .rt-row{grid-template-columns:1fr 1.5fr .8fr 1fr;grid-template-areas:"dist dist act act" "horse horse horse horse" "weight date min sec" "notes notes notes notes";gap:10px;align-items:end;border:1px solid #e2e6e4;border-radius:10px;padding:12px;margin-top:12px;background:#fff}
  .rt-row:last-child{border-bottom:1px solid #e2e6e4}
  .rt-row.rt-off{background:#f6f6f6}
  .c-dist{grid-area:dist;align-self:center}.c-act{grid-area:act;justify-content:flex-end}
  .c-horse{grid-area:horse}.c-weight{grid-area:weight}.c-date{grid-area:date}.c-min{grid-area:min}.c-sec{grid-area:sec}.c-notes{grid-area:notes}
  .rt-lbl{display:block;font-size:11.5px;font-weight:700;color:#5f6f66;margin-bottom:4px}
  .rt-dist{font-size:15px}
  .rt-block-title{border-radius:8px}
}
@container rt (max-width: 560px){
  .rt-row{grid-template-columns:1fr 1fr;grid-template-areas:"dist act" "horse horse" "weight date" "min sec" "notes notes"}
  .rt-btn{width:100%}
  .rt-block-title{font-size:15px}
}
@media(max-width:560px){.ar-title{font-size:21px}.rt-card{padding:10px}.rt-tab{flex:1;min-width:0}}
</style>

<?php if ($secmsg !== '') { ?><div class="message"><?php echo rtH($secmsg); ?></div><?php } ?>

<?php if ($secmsg === '') { ?>
<div class="main-content">

    <div class="ar-header">
        <h1 class="ar-title"><?php echo rtH($pageTitleText); ?></h1>
        <p class="ar-subtitle">Manage best timings by Indian horses for Mumbai and Pune race tracks. Whatever you save here appears on the website.</p>
    </div>

    <?php if ($flash !== '') { ?><div class="message <?php echo rtH($flashType); ?>"><?php echo rtH($flash); ?></div><?php } ?>

    <?php if (!$tableOk) { ?>
        <div class="rt-warn"><strong>Database table missing.</strong> Run <code>record_timings.sql</code> once in phpMyAdmin, then reload this page.</div>
    <?php } else { ?>

        <p class="rt-label">Select Race Track</p>
        <div class="rt-tabs">
            <a class="rt-tab <?php echo $track === 'mumbai' ? 'active' : ''; ?>" href="turf-console/<?php echo rtH($pageFile); ?>?track=mumbai">Mumbai</a>
            <a class="rt-tab <?php echo $track === 'pune' ? 'active' : ''; ?>" href="turf-console/<?php echo rtH($pageFile); ?>?track=pune">Pune</a>
        </div>

        <!-- Delete ke liye alag form (buttons "form" attribute se isse judte hain) -->
        <form id="rtDeleteForm" method="post" action="turf-console/<?php echo rtH($pageFile); ?>">
            <input type="hidden" name="csrf" value="<?php echo rtH($csrf); ?>">
            <input type="hidden" name="action" value="delete">
            <input type="hidden" name="track" value="<?php echo rtH($track); ?>">
        </form>

        <!-- EDIT + SAVE ALL -->
        <form id="rtForm" method="post" action="turf-console/<?php echo rtH($pageFile); ?>">
            <input type="hidden" name="csrf" value="<?php echo rtH($csrf); ?>">
            <input type="hidden" name="action" value="save_rows">
            <input type="hidden" name="track" value="<?php echo rtH($track); ?>">

            <div class="rt-card">
                <?php if ($track === 'mumbai') { ?>

                    <?php rtRenderTable($bySurface['main'], 'Best Timings By Indian Horses On The Mumbai Race Track'); ?>

                <?php } else { ?>

                    <?php rtRenderTable($bySurface['main'], 'Pune Race Track - Main Track'); ?>
                    <?php rtRenderTable($bySurface['monsoon'], 'Pune Race Track - Monsoon Track'); ?>

                <?php } ?>

                <div class="rt-save-bar">
                    <button type="submit" class="rt-btn"><i class="fas fa-save"></i> Save Changes</button>
                    <span class="rt-hint"><i class="fas fa-eye"></i> Eye icon: show / hide that record on the website. Click "Save Changes" after editing.</span>
                </div>
            </div>
        </form>

        <!-- ADD CUSTOM DISTANCE -->
        <form method="post" action="turf-console/<?php echo rtH($pageFile); ?>">
            <input type="hidden" name="csrf" value="<?php echo rtH($csrf); ?>">
            <input type="hidden" name="action" value="add">
            <input type="hidden" name="track" value="<?php echo rtH($track); ?>">

            <div class="rt-card">
                <div class="rt-add-title">Add Custom Distance (If required)</div>
                <div class="rt-add">

                    <?php if ($track === 'pune') { ?>
                        <div>
                            <label>Track Type</label>
                            <select name="surface">
                                <option value="main">Main Track</option>
                                <option value="monsoon">Monsoon Track</option>
                            </select>
                        </div>
                    <?php } ?>

                    <div><label>Distance <small>(Metres)</small></label><input type="number" name="distance" min="100" max="9999" placeholder="e.g. 3200" required></div>
                    <div><label>Horse Name <small>(Indian Horses)</small></label><input type="text" name="horse_name" maxlength="150" placeholder="e.g. Horse Name" required></div>
                    <div><label>Weight <small>(Kgs)</small></label><input type="number" name="weight" step="0.1" min="0.1" max="99.9" placeholder="e.g. 55.0" required></div>
                    <div><label>Race Date</label><input type="date" name="race_date" required></div>
                    <div><label>Min</label><input type="number" name="time_min" min="0" max="9" value="0" required></div>
                    <div><label>Sec</label><input type="text" name="time_sec" maxlength="6" placeholder="00.000" required></div>
                    <div><label>Notes (Optional)</label><input type="text" name="notes" maxlength="255" placeholder="e.g. (Back Stretch)"></div>
                    <div class="rt-add-btn"><button type="submit" class="rt-btn"><i class="fas fa-plus"></i> Add Record</button></div>

                </div>
            </div>
        </form>

    <?php } ?>
</div>

<script>
(function () {
    // Eye icon: website pe dikhana / chhupana
    var eyes = document.querySelectorAll('.rt-eye');
    for (var i = 0; i < eyes.length; i++) {
        eyes[i].addEventListener('click', function () {
            var tr = this.closest('.rt-row');
            var vis = tr.querySelector('.rt-vis');
            var wasOn = (vis.value === '1');

            vis.value = wasOn ? '0' : '1';
            tr.classList.toggle('rt-off', wasOn);
            this.querySelector('i').className = wasOn ? 'fas fa-eye-slash' : 'fas fa-eye';
            this.title = wasOn ? 'Hidden on website (click to show)' : 'Visible on website (click to hide)';
        });
    }
})();
</script>
<?php } ?>
<?php
$design->closeDiv();
$design->writeLeftPanel();
$design->closeDiv();
$design->closeDiv();
$design->endPage();
$design = null;
?>