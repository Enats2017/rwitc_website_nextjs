<?php
/* =====================================================================
   userGroup.php — FINAL, DB-wired version (dummy data hataya, ab dbTool
   se real fetch/insert/update/delete ho raha hai).

   Schema assumed:
     user_group  (user_group_id INT PK AUTO_INCREMENT, name VARCHAR, permission TEXT)

   "permission" column mein serialize() karke PHP array store hota hai:
     array('access' => array('dashboard','race',...), 'modify' => array('race',...))

   IMPORTANT: Change the require_once path below to match where your
   dbTool class file actually lives (I've assumed "dbTool.php" in the
   same folder — adjust if yours is e.g. "inc/dbTool.php").
   ===================================================================== */
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once("../bootstrap.php");
require_once("../lib/dbTools.php");

session_start();

$db = new dbTool();

// ---- Modules jinke access/modify permission set kiye jaate hain ----
$modules = array(
    'articles'                => 'Articles Manager',
    'csr_articles'            => 'CSR Articles Manager',
    'race_history'            => 'Race History Manager',
    'send_mailer'              => 'Send Mailers',
    'rating_change'            => 'Ratings Change Manager',
    'gallery'                 => 'Gallery Manager',
    'video'                   => 'Videos Manager',
    'dividends'               => 'Dividends Manager',
    'stewards_report'         => 'Stewards Report Manager',
    'race_day_report'         => 'Race Day Reports Manager',
    'calendar'                => 'Calendar Manager',
    'availability_calendar'   => 'Racecourse Availability Calendar Manager',
    'prakash_gosavi'           => 'Prakash Gosavi Articles Manager',
    'shiven_surendranath'      => 'Shiven Surendranath Articles Manager',
    'polls'                   => 'Manage Polls',
    'adminusers'              => 'Manage Admins',
    'workingManager'          => 'Working Group Upload',
    'bannerManager'           => 'Banner Manager',
    'tickerManager'           => 'Ticker Manager',
    'sponsorManager'          => 'Sponsor Manager',
    'sponsorofthedayManager'  => 'Sponsor Of the Day Manager',
    'horseweightManager'      => 'Reset Horse Weight Manager',
    'racedataManager'         => 'Reset Race Data Manager',
    'configManager'           => 'Config Manager',
    'mailManager'             => 'Draft Mail Manager',
    'homepopup'               => 'Home Popup',
    'erp_prerace'             => 'Pre Race Date',
    'erp_postrace'            => 'Post Race Date',
    'trackworkManager'        => 'Trackwork Manager',
    'suggestion_feedback'     => 'Suggestion Feedback',
    'youtube_upload'          => 'YouTube Upload',
    'chairman_email'          => 'Chairman Email List',
    'image_upload'            => 'Image Upload',
);

$action = isset($_GET['action']) ? $_GET['action'] : 'list';

/* ---------------------------------------------------------------------
   DELETE
   --------------------------------------------------------------------- */
if ($action == 'delete' && isset($_GET['user_group_id'])) {
    $gid = (int)$_GET['user_group_id'];
    try {
        // Protect the Administrator system group from deletion
        $chk = $db->getSingleRowAssoc("SELECT name FROM user_group WHERE user_group_id=$gid");
        if ($chk && $chk['name'] === 'Administrator') {
            header("Location: userGroup.php?msg=locked");
            exit;
        }
        $db->query("DELETE FROM user_group WHERE user_group_id = $gid");
        header("Location: userGroup.php?msg=deleted");
        exit;
    } catch (Exception $e) {
        header("Location: userGroup.php?msg=error&err=" . urlencode($e->getMessage()));
        exit;
    }
}

/* ---------------------------------------------------------------------
   SAVE (INSERT / UPDATE) — form POSTs here with action=form
   --------------------------------------------------------------------- */
$form_error = null;
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['name'])) {
    $name   = trim($_POST['name']);
    $access = isset($_POST['access']) && is_array($_POST['access']) ? $_POST['access'] : array();
    $modify = isset($_POST['modify']) && is_array($_POST['modify']) ? $_POST['modify'] : array();

    // whitelist against known module keys (never trust raw POST keys)
    $access = array_values(array_intersect($access, array_keys($modules)));
    $modify = array_values(array_intersect($modify, array_keys($modules)));

    if ($name === '') {
        $form_error = "Group name is required.";
    } else {
        $permission_serialized = serialize(array('access' => $access, 'modify' => $modify));
        try {
            if (!empty($_POST['user_group_id'])) {
                $gid = (int)$_POST['user_group_id'];
                $db->update(
                    "UPDATE user_group SET name='" . $db->escape($name) . "', "
                    . "permission='" . $db->escape($permission_serialized) . "' "
                    . "WHERE user_group_id = $gid"
                );
                header("Location: userGroup.php?msg=updated");
                exit;
            } else {
                $db->insert(
                    "INSERT INTO user_group (name, permission) VALUES ("
                    . "'" . $db->escape($name) . "', "
                    . "'" . $db->escape($permission_serialized) . "')"
                );
                header("Location: userGroup.php?msg=added");
                exit;
            }
        } catch (Exception $e) {
            $form_error = "Save failed: " . $e->getMessage();
        }
    }
}

/* ---------------------------------------------------------------------
   FETCH — list view
   --------------------------------------------------------------------- */
$user_groups = array();
if ($action == 'list') {
    $rows = $db->getMultiDimensionalArray("SELECT * FROM user_group ORDER BY user_group_id ASC");
    foreach ($rows as $r) {
        $perm = @unserialize($r['permission']);
        if (!is_array($perm)) $perm = array('access' => array(), 'modify' => array());
        $r['permission'] = $perm;
        $user_groups[] = $r;
    }
}

/* ---------------------------------------------------------------------
   FETCH — single group for edit form
   --------------------------------------------------------------------- */
$edit_group = null;
if ($action == 'form' && $_SERVER['REQUEST_METHOD'] !== 'POST' && isset($_GET['user_group_id'])) {
    $row = $db->getSingleRowAssoc("SELECT * FROM user_group WHERE user_group_id = " . (int)$_GET['user_group_id']);
    if ($row) {
        $perm = @unserialize($row['permission']);
        if (!is_array($perm)) $perm = array('access' => array(), 'modify' => array());
        $row['permission'] = $perm;
        $edit_group = $row;
    }
}
// if form re-shown after a validation error, re-populate what user typed
if ($form_error && $_SERVER['REQUEST_METHOD'] == 'POST') {
    $edit_group = array(
        'user_group_id' => isset($_POST['user_group_id']) ? $_POST['user_group_id'] : null,
        'name'          => $_POST['name'],
        'permission'    => array(
            'access' => isset($_POST['access']) ? $_POST['access'] : array(),
            'modify' => isset($_POST['modify']) ? $_POST['modify'] : array(),
        ),
    );
}

$total_groups = count($user_groups);

function hasAccess($group, $key) {
    return $group && isset($group['permission']['access']) && in_array($key, $group['permission']['access']);
}
function hasModify($group, $key) {
    return $group && isset($group['permission']['modify']) && in_array($key, $group['permission']['modify']);
}

$msg = isset($_GET['msg']) ? $_GET['msg'] : null;
?>
<?php
$pageTitle = "RWITC | User Groups";

$design = new Design();

$design->css = <<<'USERGROUPCSS'
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Newsreader:ital,opsz,wght@0,6..72,500;0,6..72,600;1,6..72,500&family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css">
<style>
:root{
    --green-900:#0d3b28;
    --green-700:#14532d;
    --green-600:#1a6b3c;
    --gold-500:#c9a227;
    --gold-400:#d9b84a;
    --ink-900:#1a211d;
    --ink-600:#5b655f;
    --ink-400:#8a938d;
    --line:#e4e8e4;
    --bg:#f6f7f5;
    --surface:#ffffff;
    --danger:#c0392b;
    --danger-bg:#fbeae8;
    --success-bg:#e9f3ec;
    --radius:10px;
    --shadow: 0 1px 2px rgba(20,30,24,0.04), 0 4px 16px rgba(20,30,24,0.05);
}
*{box-sizing:border-box;}
html,body{margin:0;padding:0;}
body{
    background:var(--bg);
    color:var(--ink-900);
    font-family:'Inter',-apple-system,sans-serif;
    font-size:14.5px;
    -webkit-font-smoothing:antialiased;
    line-height:1.5;
}
a{text-decoration:none;color:inherit;}
button{font-family:inherit;cursor:pointer;}

.topbar{background:var(--green-900);color:#fff;padding:0 28px;}
.topbar-inner{max-width:1180px;margin:0 auto;display:flex;align-items:center;justify-content:space-between;height:58px;}
.brand{display:flex;align-items:center;gap:10px;font-family:'Newsreader',serif;font-size:19px;font-weight:600;letter-spacing:0.2px;}
.brand .mark{width:28px;height:28px;border-radius:50%;border:1.5px solid var(--gold-400);display:flex;align-items:center;justify-content:center;font-family:'Inter',sans-serif;font-weight:700;font-size:12px;color:var(--gold-400);}
.brand small{font-family:'Inter',sans-serif;font-weight:500;font-size:11.5px;color:rgba(255,255,255,0.55);letter-spacing:0.08em;text-transform:uppercase;margin-left:2px;}
.topbar-right{display:flex;align-items:center;gap:14px;font-size:13px;color:rgba(255,255,255,0.75);}
.topbar-right .avatar{width:30px;height:30px;border-radius:50%;background:var(--gold-500);color:var(--green-900);font-weight:700;font-size:12px;display:flex;align-items:center;justify-content:center;}
.topbar-nav{display:flex;gap:2px;max-width:1180px;margin:0 auto;padding:0 28px;}
.topbar-nav a{padding:11px 14px;font-size:13px;color:rgba(255,255,255,0.65);font-weight:500;border-bottom:2px solid transparent;}
.topbar-nav a:hover{color:#fff;}
.topbar-nav a.active{color:#fff;border-bottom-color:var(--gold-500);}
.subnav{background:#123f2c;}

.page-wrap{max-width:1180px;margin:0 auto;padding:32px 28px 60px;}
.eyebrow{font-size:11.5px;font-weight:600;letter-spacing:0.1em;text-transform:uppercase;color:var(--gold-500);margin:0 0 6px;}
.page-head{display:flex;align-items:flex-end;justify-content:space-between;flex-wrap:wrap;gap:16px;margin-bottom:22px;padding-bottom:20px;border-bottom:1px solid var(--line);}
.page-head h1{font-family:'Newsreader',serif;font-weight:600;font-size:30px;margin:0;color:var(--green-900);}
.page-head p{margin:4px 0 0;color:var(--ink-600);font-size:13.5px;}

.btn{display:inline-flex;align-items:center;gap:8px;padding:10px 18px;border-radius:8px;border:1px solid transparent;font-size:13.5px;font-weight:600;transition:all .15s ease;}
.btn-primary{background:var(--green-700);color:#fff;}
.btn-primary:hover{background:var(--green-900);}
.btn-ghost{background:#fff;color:var(--ink-900);border-color:var(--line);}
.btn-ghost:hover{border-color:var(--ink-400);}
.btn-danger-ghost{background:#fff;color:var(--danger);border-color:var(--line);}
.btn-danger-ghost:hover{background:var(--danger-bg);border-color:var(--danger);}

.alert{padding:12px 18px;border-radius:8px;font-size:13.5px;font-weight:500;margin-bottom:18px;display:flex;align-items:center;gap:8px;}
.alert-success{background:var(--success-bg);color:var(--green-700);border:1px solid #cfe6d6;}
.alert-danger{background:var(--danger-bg);color:var(--danger);border:1px solid #f1c9c3;}
.alert-warn{background:#fbf6e8;color:#8a6d0f;border:1px solid #f0e2b5;}

.stat-strip{display:flex;background:var(--surface);border:1px solid var(--line);border-radius:var(--radius);box-shadow:var(--shadow);margin-bottom:24px;overflow:hidden;}
.stat{flex:1;padding:16px 22px;border-right:1px solid var(--line);}
.stat:last-child{border-right:0;}
.stat .num{font-family:'Newsreader',serif;font-size:25px;font-weight:600;color:var(--green-900);}
.stat .lbl{font-size:12px;color:var(--ink-600);margin-top:2px;}

.toolbar{display:flex;align-items:center;justify-content:space-between;gap:14px;flex-wrap:wrap;margin-bottom:14px;}
.search-box{position:relative;flex:1;max-width:320px;min-width:220px;}
.search-box i{position:absolute;left:13px;top:50%;transform:translateY(-50%);color:var(--ink-400);font-size:13px;}
.search-box input{width:100%;padding:9px 12px 9px 34px;border:1px solid var(--line);border-radius:8px;font-size:13.5px;font-family:inherit;background:#fff;outline:none;transition:border-color .15s;}
.search-box input:focus{border-color:var(--green-600);}

.card{background:var(--surface);border:1px solid var(--line);border-radius:var(--radius);box-shadow:var(--shadow);overflow:hidden;}
table{width:100%;border-collapse:collapse;}
thead th{text-align:left;font-size:11.5px;font-weight:600;text-transform:uppercase;letter-spacing:0.05em;color:var(--ink-600);padding:13px 18px;background:#fafbfa;border-bottom:1px solid var(--line);}
tbody td{padding:13px 18px;border-bottom:1px solid var(--line);font-size:13.5px;vertical-align:middle;}
tbody tr:last-child td{border-bottom:0;}
tbody tr:hover{background:#fafcfa;}

.group-cell{display:flex;align-items:center;gap:11px;}
.group-icon{width:34px;height:34px;border-radius:9px;background:var(--green-700);color:#fff;display:flex;align-items:center;justify-content:center;font-size:14px;border:2px solid var(--gold-400);flex-shrink:0;}
.group-name{font-weight:600;color:var(--ink-900);}
.group-sub{font-size:12px;color:var(--ink-400);}

.perm-bar-wrap{display:flex;align-items:center;gap:9px;min-width:140px;}
.perm-bar{flex:1;height:6px;border-radius:4px;background:#eef1ee;overflow:hidden;}
.perm-bar-fill{height:100%;background:linear-gradient(90deg,var(--green-600),var(--gold-500));border-radius:4px;}
.perm-bar-txt{font-size:12px;color:var(--ink-600);font-weight:600;white-space:nowrap;}

.row-actions{display:flex;gap:6px;justify-content:flex-end;}
.icon-btn{width:30px;height:30px;border-radius:7px;display:flex;align-items:center;justify-content:center;border:1px solid var(--line);background:#fff;color:var(--ink-600);font-size:12.5px;transition:all .15s;}
.icon-btn:hover{border-color:var(--green-600);color:var(--green-700);background:#f4f8f5;}
.icon-btn.danger:hover{border-color:var(--danger);color:var(--danger);background:var(--danger-bg);}
.icon-btn.locked{opacity:.35;cursor:not-allowed;}
.checkbox{width:16px;height:16px;accent-color:var(--green-700);cursor:pointer;}

.badge-system{display:inline-flex;align-items:center;gap:5px;font-size:11px;font-weight:600;color:var(--gold-500);background:#fbf6e8;border:1px solid #f0e2b5;padding:2px 8px;border-radius:20px;margin-left:8px;}

.form-card{background:var(--surface);border:1px solid var(--line);border-radius:var(--radius);box-shadow:var(--shadow);overflow:hidden;}
.form-section{padding:26px 30px;border-bottom:1px solid var(--line);}
.form-section:last-child{border-bottom:0;}
.section-title{font-family:'Newsreader',serif;font-size:16px;font-weight:600;color:var(--green-900);margin:0 0 3px;}
.section-desc{font-size:12.5px;color:var(--ink-600);margin:0 0 18px;}
.field{margin-bottom:0;max-width:420px;}
.field label{display:block;font-size:12.5px;font-weight:600;color:var(--ink-900);margin-bottom:6px;}
.field label .req{color:var(--danger);}
.field input{width:100%;padding:10px 13px;border:1px solid var(--line);border-radius:8px;font-size:13.5px;font-family:inherit;background:#fff;color:var(--ink-900);outline:none;transition:border-color .15s, box-shadow .15s;}
.field input:focus{border-color:var(--green-600);box-shadow:0 0 0 3px rgba(26,107,60,0.1);}

.perm-toolbar{display:flex;gap:10px;margin-bottom:14px;}
.perm-toolbar button{font-size:12px;font-weight:600;color:var(--green-700);background:#f4f8f5;border:1px solid #dcebe0;padding:6px 12px;border-radius:6px;}
.perm-toolbar button:hover{background:#e9f3ec;}
.perm-table{width:100%;border-collapse:collapse;border:1px solid var(--line);border-radius:8px;overflow:hidden;}
.perm-table thead th{background:#fafbfa;padding:10px 16px;font-size:11.5px;text-transform:uppercase;letter-spacing:0.05em;color:var(--ink-600);border-bottom:1px solid var(--line);text-align:left;}
.perm-table thead th.center{text-align:center;width:110px;}
.perm-table tbody td{padding:10px 16px;border-bottom:1px solid var(--line);font-size:13.5px;}
.perm-table tbody tr:last-child td{border-bottom:0;}
.perm-table tbody tr:hover{background:#fafcfa;}
.perm-table td.center{text-align:center;}
.module-name{font-weight:500;color:var(--ink-900);}
.perm-check{width:17px;height:17px;accent-color:var(--green-700);cursor:pointer;}

.form-footer{display:flex;justify-content:flex-end;gap:10px;padding:18px 30px;background:#fafbfa;}

.empty{padding:50px 20px;text-align:center;color:var(--ink-400);}
.empty i{font-size:28px;margin-bottom:10px;display:block;color:var(--line);}

@media (max-width: 860px){
    .stat-strip{flex-wrap:wrap;}
    .stat{flex:1 1 50%;border-right:1px solid var(--line);}
    .stat:nth-child(2n){border-right:0;}
}
@media (max-width: 720px){
    thead{display:none;}
    table:not(.perm-table),tbody,tr,td{display:block;width:100%;}
    #groupsTable tbody tr{border-bottom:1px solid var(--line);padding:14px 16px;}
    #groupsTable tbody td{border:0;padding:6px 0;display:flex;justify-content:space-between;align-items:center;gap:10px;}
    #groupsTable tbody td:before{content:attr(data-label);font-size:11px;font-weight:600;color:var(--ink-400);text-transform:uppercase;letter-spacing:0.04em;flex-shrink:0;}
    #groupsTable tbody td.group-td:before{display:none;}
    #groupsTable tbody td.group-td{padding-bottom:10px;}
    .row-actions{justify-content:flex-start;}
    .page-head{flex-direction:column;align-items:flex-start;}
    .page-wrap{padding:22px 16px 40px;}
    .form-section{padding:20px;}
    .form-footer{padding:16px 20px;flex-direction:column-reverse;}
    .form-footer .btn{width:100%;justify-content:center;}
    .perm-table{font-size:12.5px;}
    .perm-table thead th.center{width:70px;}
    .perm-table tbody td{padding:8px 10px;}
}
</style>
USERGROUPCSS;

$design->startPage($pageTitle);
$design->writeLogoTickerMenu();

$design->openDiv("contentWrapper");
$design->openDiv("infoWrapper");
$design->openDiv("leftArea");
?>

<div class="page-wrap">


    <?php if ($msg == 'added'): ?>
        <div class="alert alert-success"><i class="fa fa-check-circle"></i> Group added successfully.</div>
    <?php elseif ($msg == 'updated'): ?>
        <div class="alert alert-success"><i class="fa fa-check-circle"></i> Group updated successfully.</div>
    <?php elseif ($msg == 'deleted'): ?>
        <div class="alert alert-success"><i class="fa fa-check-circle"></i> Group deleted.</div>
    <?php elseif ($msg == 'locked'): ?>
        <div class="alert alert-warn"><i class="fa fa-lock"></i> Administrator is a system group and can't be deleted.</div>
    <?php elseif ($msg == 'error'): ?>
        <div class="alert alert-danger"><i class="fa fa-exclamation-circle"></i> Something went wrong<?php echo isset($_GET['err']) ? ': ' . htmlspecialchars($_GET['err']) : '.'; ?></div>
    <?php endif; ?>

    <?php if ($form_error): ?>
        <div class="alert alert-danger"><i class="fa fa-exclamation-circle"></i> <?php echo htmlspecialchars($form_error); ?></div>
    <?php endif; ?>

    <?php if ($action == 'list'): ?>

        <div class="page-head">
            <div>
                <p class="eyebrow">Access Control</p>
                <h1>User Groups</h1>
                <p>Define roles and control which modules each role can view or edit.</p>
            </div>
            <a href="admin/userGroup.php?action=form" class="btn btn-primary"><i class="fa fa-plus"></i> Add User Group</a>
        </div>

        <div class="stat-strip">
            <div class="stat">
                <div class="num"><?php echo $total_groups; ?></div>
                <div class="lbl">Total Groups</div>
            </div>
            <div class="stat">
                <div class="num"><?php echo count($modules); ?></div>
                <div class="lbl">Modules</div>
            </div>
        </div>

        <div class="toolbar">
            <div class="search-box">
                <i class="fa fa-search"></i>
                <input type="text" id="searchInput" placeholder="Search groups..." onkeyup="filterGroups()">
            </div>
        </div>

        <div class="card">
            <table id="groupsTable">
                <thead>
                    <tr>
                        <th>Group</th>
                        <th>Module Access</th>
                        <th style="text-align:right;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($user_groups)): ?>
                    <tr><td colspan="3">
                        <div class="empty"><i class="fa fa-shield"></i>No user groups yet. Click "Add User Group" to create one.</div>
                    </td></tr>
                    <?php endif; ?>
                    <?php foreach ($user_groups as $g):
                        $access_ct = count($g['permission']['access']);
                        $mod_ct = count($modules);
                        $pct = $mod_ct ? round(($access_ct / $mod_ct) * 100) : 0;
                        $is_admin_group = ($g['name'] === 'Administrator');
                    ?>
                    <tr class="group-row" data-search="<?php echo strtolower(htmlspecialchars($g['name'])); ?>">
                        <td class="group-td" data-label="Group">
                            <div class="group-cell">
                                <div class="group-icon"><i class="fa fa-shield"></i></div>
                                <div>
                                    <div class="group-name">
                                        <?php echo htmlspecialchars($g['name']); ?>
                                        <?php if ($is_admin_group): ?><span class="badge-system"><i class="fa fa-lock"></i> System</span><?php endif; ?>
                                    </div>
                                    <div class="group-sub">Group ID #<?php echo (int)$g['user_group_id']; ?></div>
                                </div>
                            </div>
                        </td>
                        <td data-label="Access">
                            <div class="perm-bar-wrap">
                                <div class="perm-bar"><div class="perm-bar-fill" style="width:<?php echo $pct; ?>%;"></div></div>
                                <div class="perm-bar-txt"><?php echo $access_ct; ?>/<?php echo $mod_ct; ?></div>
                            </div>
                        </td>
                        <td data-label="">
                            <div class="row-actions">
                                <a href="admin/userGroup.php?action=form&user_group_id=<?php echo (int)$g['user_group_id']; ?>" class="icon-btn" title="Edit"><i class="fa fa-pencil"></i></a>
                                <?php if ($is_admin_group): ?>
                                    <span class="icon-btn locked" title="System group can't be deleted"><i class="fa fa-trash-o"></i></span>
                                <?php else: ?>
                                    <a href="admin/userGroup.php?action=delete&user_group_id=<?php echo (int)$g['user_group_id']; ?>"
                                       class="icon-btn danger" title="Delete"
                                       onclick="return confirm('Delete this group? This cannot be undone.');">
                                        <i class="fa fa-trash-o"></i>
                                    </a>
                                <?php endif; ?>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <div class="empty" id="emptyState" style="display:none;">
                <i class="fa fa-search"></i>
                No groups match your search.
            </div>
        </div>

        <script>
        function filterGroups(){
            var q = document.getElementById('searchInput').value.toLowerCase();
            var rows = document.querySelectorAll('.group-row');
            var visible = 0;
            rows.forEach(function(r){
                var match = r.getAttribute('data-search').indexOf(q) !== -1;
                r.style.display = match ? '' : 'none';
                if (match) visible++;
            });
            document.getElementById('emptyState').style.display = (visible === 0 && rows.length > 0) ? 'block' : 'none';
        }
        </script>

    <?php else: ?>

        <div class="page-head">
            <div>
                <p class="eyebrow"><?php echo $edit_group ? 'Edit Group' : 'New Group'; ?></p>
                <h1><?php echo $edit_group && !empty($edit_group['name']) ? htmlspecialchars($edit_group['name']) : 'Add User Group'; ?></h1>
                <p>Set the group name and choose module-level access &amp; edit rights.</p>
            </div>
            <a href="admin/userGroup.php" class="btn btn-ghost"><i class="fa fa-arrow-left"></i> Back to list</a>
        </div>

        <form method="post" action="admin/userGroup.php?action=form" id="form-group">
        <?php if ($edit_group && !empty($edit_group['user_group_id'])): ?>
            <input type="hidden" name="user_group_id" value="<?php echo (int)$edit_group['user_group_id']; ?>">
        <?php endif; ?>
        <div class="form-card">

            <div class="form-section">
                <h3 class="section-title">Group Name</h3>
                <p class="section-desc">A short, recognisable name for this role.</p>
                <div class="field">
                    <label>Group Name <span class="req">*</span></label>
                    <input type="text" name="name" placeholder="e.g. Manager" value="<?php echo htmlspecialchars($edit_group['name'] ?? ''); ?>">
                </div>
            </div>

            <div class="form-section">
                <h3 class="section-title">Module Permissions</h3>
                <p class="section-desc">Access lets a user view the module. Modify lets them add, edit or delete records within it.</p>

                <div class="perm-toolbar">
                    <button type="button" onclick="toggleAll('access', true)">Select all Access</button>
                    <button type="button" onclick="toggleAll('modify', true)">Select all Modify</button>
                    <button type="button" onclick="clearAll()">Clear all</button>
                </div>

                <table class="perm-table">
                    <thead>
                        <tr>
                            <th>Module</th>
                            <th class="center">Access</th>
                            <th class="center">Modify</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($modules as $key => $label): ?>
                        <tr>
                            <td class="module-name"><?php echo htmlspecialchars($label); ?></td>
                            <td class="center">
                                <input type="checkbox" class="perm-check perm-access" name="access[]" value="<?php echo $key; ?>"
                                    <?php echo hasAccess($edit_group, $key) ? 'checked' : ''; ?>>
                            </td>
                            <td class="center">
                                <input type="checkbox" class="perm-check perm-modify" name="modify[]" value="<?php echo $key; ?>"
                                    <?php echo hasModify($edit_group, $key) ? 'checked' : ''; ?>>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <div class="form-footer">
                <a href="admin/userGroup.php" class="btn btn-ghost">Cancel</a>
                <button type="submit" class="btn btn-primary"><i class="fa fa-save"></i> Save Group</button>
            </div>
        </div>
        </form>

        <script>
        function toggleAll(type, state){
            document.querySelectorAll('.perm-' + type).forEach(function(c){ c.checked = state; });
        }
        function clearAll(){
            document.querySelectorAll('.perm-check').forEach(function(c){ c.checked = false; });
        }
        </script>

    <?php endif; ?>

</div>

<?php
$design->closeDiv();
$design->closeDiv();
$design->closeDiv();
$design->endPage();
$design = NULL;
?>
