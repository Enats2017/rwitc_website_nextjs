<?php

error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once("../bootstrap.php");
require_once("../lib/dbTools.php");

session_start();

// Only Super Admin (Admin ID 19) can access this page
if (
    !isset($_SESSION['uid']) ||
    (int)$_SESSION['uid'] !== 19 ||
    !isset($_SESSION['role']) ||
    strtoupper((string)$_SESSION['role']) !== 'ADMIN'
) {
    header("Location: ../index.php");
    exit;
}

$db = new dbTool();

$action = isset($_GET['action']) ? $_GET['action'] : 'list';


/* ================================================================
   LOAD USER GROUPS
   ================================================================ */

$user_groups = $db->getMultiDimensionalArray(
    "SELECT user_group_id, name
     FROM user_group
     ORDER BY user_group_id ASC"
);


/* ================================================================
   DELETE SINGLE ADMIN
   ================================================================ */

if ($action == 'delete' && isset($_GET['id'])) {

    $id = (int)$_GET['id'];

    try {

        $db->query("DELETE FROM admin_group_modules WHERE admin_id = $id");
        $db->query("DELETE FROM admins WHERE id = $id");

        header("Location: users.php?msg=deleted");
        exit;
    } catch (Exception $e) {

        header("Location: users.php?msg=error&err=" . urlencode($e->getMessage()));
        exit;
    }
}


/* ================================================================
   BULK DELETE
   ================================================================ */

if (
    $_SERVER['REQUEST_METHOD'] == 'POST' &&
    isset($_POST['bulk_ids']) &&
    isset($_POST['bulk_delete'])
) {

    $ids = array_filter(array_map('intval', $_POST['bulk_ids']));

    if (!empty($ids)) {

        try {

            $db->query("DELETE FROM admin_group_modules WHERE admin_id IN (" . implode(',', $ids) . ")");
            $db->query("DELETE FROM admins WHERE id IN (" . implode(',', $ids) . ")");

            header("Location: users.php?msg=bulk_deleted");
            exit;
        } catch (Exception $e) {

            header("Location: users.php?msg=error&err=" . urlencode($e->getMessage()));
            exit;
        }
    } else {

        header("Location: users.php?msg=error&err=" . urlencode("No admins selected."));
        exit;
    }
}


/* ================================================================
   SAVE ADMIN - INSERT / UPDATE
   ================================================================ */

$form_error = null;

if (
    $_SERVER['REQUEST_METHOD'] == 'POST' &&
    isset($_POST['firstname']) &&
    !isset($_POST['bulk_delete'])
) {

    $username  = trim(isset($_POST['username']) ? $_POST['username'] : '');
    $firstname = trim(isset($_POST['firstname']) ? $_POST['firstname'] : '');
    $lastname  = trim(isset($_POST['lastname']) ? $_POST['lastname'] : '');
    $email     = trim(isset($_POST['email']) ? $_POST['email'] : '');
    $phoneno   = trim(isset($_POST['phoneno']) ? $_POST['phoneno'] : '');
    $role      = trim(isset($_POST['role']) ? $_POST['role'] : '');

    $gm = (isset($_POST['gm']) && is_array($_POST['gm'])) ? $_POST['gm'] : array();
    $group_id = 0;
    foreach ($gm as $gk => $gv) {
        if (is_array($gv) && count($gv) > 0) {
            $group_id = (int)$gk;
            break;
        }
    }

    $active = (isset($_POST['status']) && $_POST['status'] == 'Y') ? 'Y' : 'N';

    $password = isset($_POST['password']) ? $_POST['password'] : '';
    $confirm  = isset($_POST['confirm']) ? $_POST['confirm'] : '';
    $edit_id  = isset($_POST['id']) ? (int)$_POST['id'] : 0;


    /* ============================================================
       VALIDATION
       ============================================================ */

    if ($username === '') {

        $form_error = "Username is required.";
    } elseif ($firstname === '' || $lastname === '') {

        $form_error = "First name and last name are required.";
    } elseif ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {

        $form_error = "A valid email address is required.";
    } elseif (!$edit_id && $password === '') {

        $form_error = "Password is required for a new admin.";
    } elseif ($password !== '' && $password !== $confirm) {

        $form_error = "Password and confirm password do not match.";
    } elseif ($password !== '' && strlen($password) < 8) {

        $form_error = "Password must be at least 8 characters.";
    } else {

        try {

            /* ====================================================
               DUPLICATE USERNAME
               ==================================================== */

            $escaped_username = $db->escape($username);

            $usernameSql = "SELECT id FROM admins WHERE username = '$escaped_username'";

            if ($edit_id) {
                $usernameSql .= " AND id != $edit_id";
            }

            $duplicateUsername = $db->getSingleRowAssoc($usernameSql);


            if ($duplicateUsername) {

                $form_error = "This username is already used by another admin.";
            } else {


                /* =================================================
                   DUPLICATE EMAIL
                   ================================================= */

                $escaped_email = $db->escape($email);

                $emailSql = "SELECT id FROM admins WHERE email = '$escaped_email'";

                if ($edit_id) {
                    $emailSql .= " AND id != $edit_id";
                }

                $duplicateEmail = $db->getSingleRowAssoc($emailSql);


                if ($duplicateEmail) {

                    $form_error = "This email is already used by another admin.";
                } else {


                    /* =============================================
                       UPDATE ADMIN
                       ============================================= */

                    if ($edit_id) {

                        $sql =
                            "UPDATE admins SET
                                username='" . $db->escape($username) . "',
                                firstname='" . $db->escape($firstname) . "',
                                lastname='" . $db->escape($lastname) . "',
                                email='" . $db->escape($email) . "',
                                phoneno='" . $db->escape($phoneno) . "',
                                role='" . $db->escape($role) . "',
                                user_group_id=" . ($group_id ? $group_id : 'NULL') . ",
                                active='" . $active . "'";


                        /* -----------------------------------------
                           Password only changes when entered
                           ----------------------------------------- */

                        if ($password !== '') {

                            $hashedPassword = '*' . strtoupper(sha1(sha1($password, true)));

                            $sql .= ", password='" . $db->escape($hashedPassword) . "'";
                        }


                        $sql .= " WHERE id = $edit_id";

                        $db->update($sql);

                        saveAdminGroupModules($db, $edit_id, $gm);

                        header("Location: users.php?msg=updated");
                        exit;
                    } else {


                        /* =========================================
                           INSERT NEW ADMIN
                           ========================================= */

                        $hashedPassword = '*' . strtoupper(sha1(sha1($password, true)));

                        $sql =
                            "INSERT INTO admins
                            (
                                username,
                                password,
                                firstname,
                                lastname,
                                email,
                                phoneno,
                                role,
                                user_group_id,
                                created,
                                active
                            )
                            VALUES
                            (
                                '" . $db->escape($username) . "',
                                '" . $db->escape($hashedPassword) . "',
                                '" . $db->escape($firstname) . "',
                                '" . $db->escape($lastname) . "',
                                '" . $db->escape($email) . "',
                                '" . $db->escape($phoneno) . "',
                                '" . $db->escape($role) . "',
                                " . ($group_id ? $group_id : 'NULL') . ",
                                NOW(),
                                '" . $active . "'
                            )";

                        $db->insert($sql);

                        $newRow = $db->getSingleRowAssoc(
                            "SELECT id FROM admins WHERE username = '" . $db->escape($username) . "' LIMIT 1"
                        );
                        if ($newRow) {
                            saveAdminGroupModules($db, (int)$newRow['id'], $gm);
                        }

                        header("Location: users.php?msg=added");
                        exit;
                    }
                }
            }
        } catch (Exception $e) {

            $form_error = "Save failed: " . $e->getMessage();
        }
    }
}


/* ================================================================
   FETCH ADMIN LIST
   ================================================================ */

$users = array();
$search = '';
$status_filter = '';
$page = 1;
$per_page = 20;
$total_pages = 1;
$filtered_count = 0;
$total_count = 0;
$active_count = 0;
$inactive_count = 0;


if ($action == 'list') {

    $search = isset($_GET['q']) ? trim($_GET['q']) : '';

    $status_filter = isset($_GET['status_filter']) ? $_GET['status_filter'] : '';

    if ($status_filter !== 'Y' && $status_filter !== 'N') {
        $status_filter = '';
    }

    $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;

    if ($page < 1) {
        $page = 1;
    }


    /* ============================================================
       ADMIN STATISTICS
       ============================================================ */

    $all_admins = $db->getMultiDimensionalArray("SELECT active FROM admins");

    $total_count = count($all_admins);

    $active_count = count(
        array_filter(
            $all_admins,
            function ($admin) {
                return (isset($admin['active']) && $admin['active'] == 'Y');
            }
        )
    );

    $inactive_count = $total_count - $active_count;


    /* ============================================================
       SEARCH
       ============================================================ */

    $where = array();

    if ($search !== '') {

        $s = $db->escape($search);

        $where[] =
            "(a.username LIKE '%$s%'
              OR a.firstname LIKE '%$s%'
              OR a.lastname LIKE '%$s%'
              OR a.email LIKE '%$s%'
              OR a.phoneno LIKE '%$s%'
              OR a.role LIKE '%$s%'
              OR CONCAT(a.firstname, ' ', a.lastname) LIKE '%$s%')";
    }


    /* ============================================================
       STATUS FILTER
       ============================================================ */

    if ($status_filter !== '') {
        $where[] = "a.active = '" . $db->escape($status_filter) . "'";
    }

    $whereSql = !empty($where) ? " WHERE " . implode(" AND ", $where) : "";


    /* ============================================================
       COUNT
       ============================================================ */

    $countRow = $db->getSingleRowAssoc(
        "SELECT COUNT(*) AS cnt FROM admins a $whereSql"
    );

    $filtered_count = $countRow ? (int)$countRow['cnt'] : 0;

    $total_pages = max(1, (int)ceil($filtered_count / $per_page));

    if ($page > $total_pages) {
        $page = $total_pages;
    }

    $offset = ($page - 1) * $per_page;


    /* ============================================================
       FETCH ADMINS + GROUP
       ============================================================ */

    $sql =
        "SELECT
            a.*,
            ug.name AS group_name
         FROM admins a
         LEFT JOIN user_group ug
            ON a.user_group_id = ug.user_group_id
         $whereSql
         ORDER BY a.id DESC
         LIMIT $per_page
         OFFSET $offset";

    $users = $db->getMultiDimensionalArray($sql);
}


/* ================================================================
   FETCH SINGLE ADMIN FOR EDIT
   ================================================================ */

$edit_user = null;

if (
    $action == 'form' &&
    $_SERVER['REQUEST_METHOD'] !== 'POST' &&
    isset($_GET['id'])
) {

    $edit_id = (int)$_GET['id'];

    $edit_user = $db->getSingleRowAssoc(
        "SELECT * FROM admins WHERE id = $edit_id"
    );
}


/* ================================================================
   REPOPULATE AFTER ERROR
   ================================================================ */

if ($form_error && $_SERVER['REQUEST_METHOD'] == 'POST') {

    $edit_user = array(
        'id'            => isset($_POST['id']) ? $_POST['id'] : null,
        'username'      => isset($_POST['username']) ? $_POST['username'] : '',
        'firstname'     => $_POST['firstname'],
        'lastname'      => $_POST['lastname'],
        'email'         => $_POST['email'],
        'phoneno'       => $_POST['phoneno'],
        'role'          => $_POST['role'],
        'user_group_id' => isset($_POST['user_group_id']) ? $_POST['user_group_id'] : null,
        'active'        => (isset($_POST['status']) && $_POST['status'] == 'Y') ? 'Y' : 'N'
    );
}

/* ================================================================
   GROUPS + MODULES FOR FORM
   ================================================================ */

$catalogMods = Design::moduleCatalog();
$groupsForForm = array();
$grows = $db->getMultiDimensionalArray(
    "SELECT user_group_id, name, permission FROM user_group ORDER BY name ASC"
);
if (is_array($grows)) {
    foreach ($grows as $gr) {
        $p = @unserialize($gr['permission']);
        $acc = (is_array($p) && !empty($p['access']) && is_array($p['access'])) ? $p['access'] : array();
        $mods = array();
        foreach ($acc as $k) {
            if (isset($catalogMods[$k])) {
                $mods[$k] = $catalogMods[$k][0];
            }
        }
        if (!empty($mods)) {
            $groupsForForm[(int)$gr['user_group_id']] = array('name' => $gr['name'], 'mods' => $mods);
        }
    }
}

$selectedGM = array();
if ($form_error && $_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['gm']) && is_array($_POST['gm'])) {
    foreach ($_POST['gm'] as $gk => $gv) {
        $selectedGM[(int)$gk] = is_array($gv) ? $gv : array();
    }
} elseif ($edit_user && !empty($edit_user['id'])) {
    $eid = (int)$edit_user['id'];
    $srows = $db->getMultiDimensionalArray(
        "SELECT user_group_id, module_key FROM admin_group_modules WHERE admin_id = $eid"
    );
    if (is_array($srows) && count($srows) > 0) {
        foreach ($srows as $sr) {
            $selectedGM[(int)$sr['user_group_id']][] = $sr['module_key'];
        }
    } elseif (!empty($edit_user['user_group_id']) && isset($groupsForForm[(int)$edit_user['user_group_id']])) {
        $selectedGM[(int)$edit_user['user_group_id']] = array_keys($groupsForForm[(int)$edit_user['user_group_id']]['mods']);
    }
}


/* ================================================================
   HELPERS
   ================================================================ */

function saveAdminGroupModules($db, $adminId, $gm)
{
    $adminId = (int)$adminId;
    if ($adminId <= 0) {
        return;
    }

    $catalog = Design::moduleCatalog();
    $db->query("DELETE FROM admin_group_modules WHERE admin_id = $adminId");

    foreach ($gm as $gid => $mods) {
        $gid = (int)$gid;
        if ($gid <= 0 || !is_array($mods)) {
            continue;
        }

        $g = $db->getSingleRowAssoc("SELECT permission FROM user_group WHERE user_group_id = $gid");
        if (!$g) {
            continue;
        }
        $p = @unserialize($g['permission']);
        $allowed = (is_array($p) && !empty($p['access']) && is_array($p['access'])) ? $p['access'] : array();

        foreach (array_unique($mods) as $m) {
            if (in_array($m, $allowed, true) && isset($catalog[$m])) {
                $db->query(
                    "INSERT IGNORE INTO admin_group_modules (admin_id, user_group_id, module_key)
                     VALUES ($adminId, $gid, '" . $db->escape($m) . "')"
                );
            }
        }
    }
}

function initials($first, $last)
{
    $first = $first ?: '';
    $last = $last ?: '';

    $i = strtoupper(mb_substr($first, 0, 1) . mb_substr($last, 0, 1));

    return $i !== '' ? $i : '?';
}


function fmtDate($val)
{
    if (!$val || $val === '0000-00-00 00:00:00') {
        return '-';
    }

    return date('d M Y', strtotime($val));
}


function buildPageUrl($p, $search, $status_filter)
{
    $params = array('action' => 'list');

    if ($search !== '') {
        $params['q'] = $search;
    }

    if ($status_filter !== '') {
        $params['status_filter'] = $status_filter;
    }

    if ($p > 1) {
        $params['page'] = $p;
    }

    return 'turf-console/users.php?' . http_build_query($params);
}


$msg = isset($_GET['msg']) ? $_GET['msg'] : null;


/* ================================================================
   PAGE DESIGN
   ================================================================ */

$design = new Design();

$design->css = '
<link rel="stylesheet" href="https://ajax.googleapis.com/ajax/libs/jqueryui/1.8/themes/base/jquery-ui.css" type="text/css"/>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
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
    --shadow:0 1px 2px rgba(20,30,24,.04),
             0 4px 16px rgba(20,30,24,.05);
}

*{ box-sizing:border-box; }

html,
body{ margin:0; padding:0; }

body{
    background:var(--bg);
    color:var(--ink-900);
    font-family:Inter,-apple-system,sans-serif;
    font-size:14.5px;
    line-height:1.5;
}

a{ text-decoration:none; color:inherit; }

button{ font-family:inherit; cursor:pointer; }

.page-wrap{
    max-width:1180px;
    margin:0 auto;
    padding:32px 28px 60px;
}
.rw-page-flex{
    display:flex;
    flex-direction:row-reverse;
    align-items:flex-start;
    max-width:1500px;
    margin:0 auto;
}
.rw-page-flex .page-wrap{
    flex:1 1 auto;
    min-width:0;
    max-width:none;
    margin:0;
}

.eyebrow{
    font-size:11.5px;
    font-weight:600;
    letter-spacing:.1em;
    text-transform:uppercase;
    color:var(--gold-500);
    margin:0 0 6px;
}

.page-head{
    display:flex;
    align-items:flex-end;
    justify-content:space-between;
    flex-wrap:wrap;
    gap:16px;
    margin-bottom:22px;
    padding-bottom:20px;
    border-bottom:1px solid var(--line);
}

.page-head h1{
    font-family:Georgia,serif;
    font-weight:600;
    font-size:30px;
    margin:0;
    color:var(--green-900);
}

.page-head p{
    margin:4px 0 0;
    color:var(--ink-600);
    font-size:13.5px;
}

.btn{
    display:inline-flex;
    align-items:center;
    gap:8px;
    padding:10px 18px;
    border-radius:8px;
    border:1px solid transparent;
    font-size:13.5px;
    font-weight:600;
}

.btn-primary{ background:var(--green-700); color:#fff; }

.btn-ghost{ background:#fff; color:var(--ink-900); border-color:var(--line); }

.btn-danger-ghost{ background:#fff; color:var(--danger); border-color:var(--line); }

.alert{
    padding:12px 18px;
    border-radius:8px;
    font-size:13.5px;
    font-weight:500;
    margin-bottom:18px;
}

.alert-success{
    background:var(--success-bg);
    color:var(--green-700);
    border:1px solid #cfe6d6;
}

.alert-danger{
    background:var(--danger-bg);
    color:var(--danger);
    border:1px solid #f1c9c3;
}

.stat-strip{
    display:flex;
    background:#fff;
    border:1px solid var(--line);
    border-radius:var(--radius);
    box-shadow:var(--shadow);
    margin-bottom:24px;
    overflow:hidden;
}

.stat{
    flex:1;
    padding:16px 22px;
    border-right:1px solid var(--line);
}

.stat:last-child{ border-right:0; }

.stat .num{
    font-family:Georgia,serif;
    font-size:25px;
    font-weight:600;
    color:var(--green-900);
}

.stat .lbl{ font-size:12px; color:var(--ink-600); }

.toolbar{
    display:flex;
    align-items:center;
    justify-content:space-between;
    gap:14px;
    flex-wrap:wrap;
    margin-bottom:14px;
}

.search-box{
    position:relative;
    flex:1;
    max-width:320px;
    min-width:220px;
}

.search-box input{
    width:100%;
    padding:9px 12px;
    border:1px solid var(--line);
    border-radius:8px;
    font-size:13.5px;
    background:#fff;
}

.toolbar-actions{ display:flex; gap:10px; align-items:center; }

.status-filter{
    padding:9px 12px;
    border:1px solid var(--line);
    border-radius:8px;
    font-size:13.5px;
    background:#fff;
}

.card,
.form-card{
    background:#fff;
    border:1px solid var(--line);
    border-radius:var(--radius);
    box-shadow:var(--shadow);
    overflow:hidden;
}

table{ width:100%; border-collapse:collapse; }

thead th{
    text-align:left;
    font-size:11.5px;
    font-weight:600;
    text-transform:uppercase;
    letter-spacing:.05em;
    color:var(--ink-600);
    padding:13px 18px;
    background:#fafbfa;
    border-bottom:1px solid var(--line);
}

tbody td{
    padding:13px 18px;
    border-bottom:1px solid var(--line);
    font-size:13.5px;
    vertical-align:middle;
}

.user-cell{ display:flex; align-items:center; gap:11px; }

.avatar-ring{
    width:34px;
    height:34px;
    border-radius:50%;
    background:var(--green-700);
    color:#fff;
    display:flex;
    align-items:center;
    justify-content:center;
    font-size:12px;
    font-weight:700;
    border:2px solid var(--gold-400);
    flex-shrink:0;
}

.user-name{ font-weight:600; }

.user-sub{ font-size:12px; color:var(--ink-400); }

.pill-group{
    display:inline-block;
    padding:3px 10px;
    border-radius:20px;
    font-size:11.5px;
    font-weight:600;
    background:#f1f0e8;
    color:var(--green-900);
    border:1px solid #e3e1d2;
}

.pill-none{ color:var(--ink-400); }

.status{
    display:inline-flex;
    align-items:center;
    gap:6px;
    font-size:12.5px;
    font-weight:600;
}

.status .dot{ width:7px; height:7px; border-radius:50%; }

.status.enabled{ color:var(--green-600); }
.status.enabled .dot{ background:var(--green-600); }

.status.disabled{ color:var(--danger); }
.status.disabled .dot{ background:var(--danger); }

.row-actions{ display:flex; gap:6px; justify-content:flex-end; }

.icon-btn{
    width:30px;
    height:30px;
    border-radius:7px;
    display:flex;
    align-items:center;
    justify-content:center;
    border:1px solid var(--line);
    background:#fff;
    color:var(--ink-600);
}

.checkbox{ width:16px; height:16px; }

.empty{
    padding:50px 20px;
    text-align:center;
    color:var(--ink-400);
}

.results-note{
    font-size:12.5px;
    color:var(--ink-600);
    margin-bottom:10px;
}

.pagination{
    display:flex;
    align-items:center;
    justify-content:center;
    gap:6px;
    flex-wrap:wrap;
    margin-top:18px;
}

.page-link{
    min-width:34px;
    height:34px;
    padding:0 10px;
    display:inline-flex;
    align-items:center;
    justify-content:center;
    border:1px solid var(--line);
    border-radius:7px;
    background:#fff;
    font-size:13px;
    font-weight:600;
}

.page-link.active{
    background:var(--green-700);
    border-color:var(--green-700);
    color:#fff;
}

.page-link.disabled{ opacity:.4; pointer-events:none; }

.form-section{
    padding:26px 30px;
    border-bottom:1px solid var(--line);
}

.section-title{
    font-family:Georgia,serif;
    font-size:16px;
    font-weight:600;
    color:var(--green-900);
    margin:0 0 3px;
}

.section-desc{
    font-size:12.5px;
    color:var(--ink-600);
    margin:0 0 18px;
}

.grid-2{
    display:grid;
    grid-template-columns:1fr 1fr;
    gap:18px;
}


html, body { scrollbar-width: none; -ms-overflow-style: none; }
html::-webkit-scrollbar, body::-webkit-scrollbar { display: none; }

.field{ margin-bottom:16px; }

.field label{
    display:block;
    font-size:12.5px;
    font-weight:600;
    margin-bottom:6px;
}

.field input,
.field select{
    width:100%;
    padding:10px 13px;
    border:1px solid var(--line);
    border-radius:8px;
    font-size:13.5px;
    background:#fff;
}

/* ---------- Password show / hide eye ---------- */
.pw-wrap{ position:relative; }

.pw-wrap input{ padding-right:44px; }

.pw-toggle{
    position:absolute;
    top:50%;
    right:6px;
    transform:translateY(-50%);
    width:34px;
    height:34px;
    border:0;
    background:transparent;
    color:var(--ink-600);
    font-size:15px;
    border-radius:6px;
    display:flex;
    align-items:center;
    justify-content:center;
}

.pw-toggle:hover{ color:var(--green-700); background:#f1f3f1; }

/* ---------- Selected modules summary ---------- */
.gm-summary{
    border:1px solid var(--line);
    border-radius:8px;
    background:#fafbfa;
    padding:14px 16px;
    margin-bottom:18px;
}

.gm-summary-title{
    font-size:12.5px;
    font-weight:700;
    text-transform:uppercase;
    letter-spacing:.05em;
    color:var(--green-900);
    margin-bottom:10px;
}

.gm-summary-empty{
    font-size:13px;
    color:var(--ink-400);
}

.gm-sum-row{
    display:flex;
    align-items:flex-start;
    gap:12px;
    padding:9px 0;
    border-top:1px dashed var(--line);
}

.gm-sum-row:first-of-type{ border-top:0; }

.gm-sum-group{
    flex:0 0 190px;
    font-weight:700;
    font-size:13px;
    color:var(--green-900);
    cursor:pointer;
}

.gm-sum-group:hover{ text-decoration:underline; }

.gm-sum-group .cnt{
    font-weight:500;
    color:var(--ink-400);
    font-size:12px;
}

.gm-sum-chips{ display:flex; flex-wrap:wrap; gap:6px; }

.gm-chip{
    display:inline-flex;
    align-items:center;
    gap:6px;
    padding:3px 6px 3px 10px;
    border-radius:20px;
    background:#e9f3ec;
    border:1px solid #cfe6d6;
    color:var(--green-700);
    font-size:12px;
    font-weight:600;
}

.gm-chip button{
    border:0;
    background:transparent;
    color:var(--green-700);
    font-size:14px;
    line-height:1;
    padding:0 3px;
    border-radius:50%;
}

.gm-chip button:hover{ color:var(--danger); }

@media(max-width:720px){

    thead{ display:none; }

    table,
    tbody,
    tr,
    td{
        display:block;
        width:100%;
    }

    tbody tr{
        border-bottom:1px solid var(--line);
        padding:14px 16px;
    }

    tbody td{
        border:0;
        padding:6px 0;
        display:flex;
        justify-content:space-between;
        gap:10px;
    }

    .grid-2{ grid-template-columns:1fr; }

    .page-head{
        flex-direction:column;
        align-items:flex-start;
    }

    .page-wrap{ padding:22px 16px 40px; }

    .form-section{ padding:20px; }

    .gm-sum-row{ flex-direction:column; gap:6px; }
    .gm-sum-group{ flex:none; }

    .form-footer{
        padding:16px 20px;
        flex-direction:column-reverse;
    }

    .form-footer .btn{
        width:100%;
        justify-content:center;
    }
}

.form-footer{
    display:flex;
    justify-content:flex-end;
    gap:10px;
    padding:18px 30px;
    background:#fafbfa;
}

</style>
';


$design->startPage("RWITC | Admins");
$design->writeLogoTickerMenu();

?>

<div class="rw-page-flex">
<div class="page-wrap">

    <?php if ($msg == 'added'): ?>

        <div class="alert alert-success">
            <i class="fa fa-check-circle"></i>
            Admin added successfully.
        </div>

    <?php elseif ($msg == 'updated'): ?>

        <div class="alert alert-success">
            <i class="fa fa-check-circle"></i>
            Admin updated successfully.
        </div>

    <?php elseif ($msg == 'deleted'): ?>

        <div class="alert alert-success">
            <i class="fa fa-check-circle"></i>
            Admin deleted.
        </div>

    <?php elseif ($msg == 'bulk_deleted'): ?>

        <div class="alert alert-success">
            <i class="fa fa-check-circle"></i>
            Selected admins deleted.
        </div>

    <?php elseif ($msg == 'error'): ?>

        <div class="alert alert-danger">
            <i class="fa fa-exclamation-circle"></i>
            Something went wrong
            <?php
            echo isset($_GET['err'])
                ? ': ' . htmlspecialchars($_GET['err'])
                : '.';
            ?>
        </div>

    <?php endif; ?>


    <?php if ($form_error): ?>

        <div class="alert alert-danger">
            <i class="fa fa-exclamation-circle"></i>
            <?php echo htmlspecialchars($form_error); ?>
        </div>

    <?php endif; ?>


    <?php if ($action == 'list'): ?>


        <!-- =========================================================
         PAGE HEADER
         ========================================================= -->

        <div class="page-head">

            <div>

                <p class="eyebrow">Admin Management</p>

                <h1>System Admins</h1>

                <p>Manage administrators and their user group access.</p>

            </div>

            <a href="turf-console/users.php?action=form" class="btn btn-primary">
                <i class="fa fa-plus"></i>
                Add Admin
            </a>

        </div>


        <!-- =========================================================
         STATISTICS
         ========================================================= -->

        <div class="stat-strip">

            <div class="stat">
                <div class="num"><?php echo $total_count; ?></div>
                <div class="lbl">Total Admins</div>
            </div>

            <div class="stat">
                <div class="num"><?php echo $active_count; ?></div>
                <div class="lbl">Active</div>
            </div>

            <div class="stat">
                <div class="num"><?php echo $inactive_count; ?></div>
                <div class="lbl">Disabled</div>
            </div>

            <div class="stat">
                <div class="num"><?php echo count($user_groups); ?></div>
                <div class="lbl">User Groups</div>
            </div>

        </div>


        <!-- =========================================================
         SEARCH / FILTER
         ========================================================= -->

        <form
            method="get"
            action="turf-console/users.php"
            class="toolbar"
            id="filter-form">

            <input type="hidden" name="action" value="list">

            <div class="search-box">

                <input
                    type="text"
                    name="q"
                    id="searchInput"
                    placeholder="Search username, name or email..."
                    value="<?php echo htmlspecialchars($search); ?>"
                    oninput="debouncedSubmit()">

            </div>

            <div class="toolbar-actions">

                <select
                    name="status_filter"
                    class="status-filter"
                    onchange="document.getElementById('filter-form').submit()">

                    <option value="" <?php echo $status_filter === '' ? 'selected' : ''; ?>>
                        All Status
                    </option>

                    <option value="Y" <?php echo $status_filter === 'Y' ? 'selected' : ''; ?>>
                        Active
                    </option>

                    <option value="N" <?php echo $status_filter === 'N' ? 'selected' : ''; ?>>
                        Disabled
                    </option>

                </select>

            </div>

        </form>


        <?php if ($filtered_count > 0): ?>

            <div class="results-note">

                Showing
                <?php echo (($page - 1) * $per_page) + 1; ?>
                –
                <?php echo min($page * $per_page, $filtered_count); ?>

                of
                <?php echo $filtered_count; ?>

                admin<?php echo $filtered_count == 1 ? '' : 's'; ?>

            </div>

        <?php endif; ?>


        <!-- =========================================================
         BULK DELETE
         ========================================================= -->

        <form method="post" action="turf-console/users.php" id="bulk-form">

            <div
                class="toolbar-actions"
                style="justify-content:flex-end;margin-bottom:12px;">

                <button
                    type="submit"
                    name="bulk_delete"
                    value="1"
                    class="btn btn-danger-ghost"
                    onclick="return confirm('Delete all selected admins? This cannot be undone.');">

                    <i class="fa fa-trash-o"></i>

                    Delete Selected

                </button>

            </div>


            <!-- =====================================================
             ADMIN TABLE
             ===================================================== -->

            <div class="card">

                <table id="usersTable">

                    <thead>

                        <tr>

                            <th style="width:1px;">

                                <input
                                    type="checkbox"
                                    class="checkbox"
                                    onclick="document.querySelectorAll('.row-check').forEach(c => c.checked = this.checked)">

                            </th>

                            <th>Admin</th>
                            <th>Username</th>
                            <th>Group</th>
                            <th>Status</th>
                            <th>Added</th>
                            <th>Last Login</th>
                            <th style="text-align:right;">Actions</th>

                        </tr>

                    </thead>


                    <tbody>

                        <?php if (empty($users)): ?>

                            <tr>
                                <td colspan="8">
                                    <div class="empty">
                                        <i class="fa fa-user"></i>
                                        No admins found.
                                    </div>
                                </td>
                            </tr>

                        <?php endif; ?>


                        <?php foreach ($users as $u): ?>

                            <tr>

                                <td>
                                    <input
                                        type="checkbox"
                                        class="checkbox row-check"
                                        name="bulk_ids[]"
                                        value="<?php echo (int)$u['id']; ?>">
                                </td>


                                <!-- ADMIN NAME -->

                                <td>

                                    <div class="user-cell">

                                        <div class="avatar-ring">
                                            <?php echo initials($u['firstname'], $u['lastname']); ?>
                                        </div>

                                        <div>

                                            <div class="user-name">
                                                <?php
                                                echo htmlspecialchars(trim($u['firstname'] . ' ' . $u['lastname'])) ?: '-';
                                                ?>
                                            </div>

                                            <div class="user-sub">
                                                <?php echo htmlspecialchars($u['email']); ?>
                                            </div>

                                        </div>

                                    </div>

                                </td>


                                <!-- USERNAME -->

                                <td><?php echo htmlspecialchars($u['username']); ?></td>


                                <!-- GROUP -->

                                <td>

                                    <?php if (!empty($u['group_name'])): ?>

                                        <span class="pill-group">

                                            <?php echo htmlspecialchars($u['group_name']); ?>

                                            <?php if ((int)$u['id'] === 1): ?>
                                                <i class="fa fa-star" title="Super Admin"></i>
                                            <?php endif; ?>

                                        </span>

                                    <?php else: ?>

                                        <span class="pill-none">No group</span>

                                    <?php endif; ?>

                                </td>


                                <!-- STATUS -->

                                <td>

                                    <?php if ($u['active'] == 'Y'): ?>

                                        <span class="status enabled">
                                            <span class="dot"></span>
                                            Active
                                        </span>

                                    <?php else: ?>

                                        <span class="status disabled">
                                            <span class="dot"></span>
                                            Disabled
                                        </span>

                                    <?php endif; ?>

                                </td>


                                <!-- CREATED -->

                                <td><?php echo fmtDate($u['created']); ?></td>


                                <!-- LAST LOGIN -->

                                <td><?php echo fmtDate($u['last_login']); ?></td>


                                <!-- ACTIONS -->

                                <td>

                                    <div class="row-actions">

                                        <a
                                            href="turf-console/users.php?action=form&id=<?php echo (int)$u['id']; ?>"
                                            class="icon-btn"
                                            title="Edit">
                                            <i class="fa fa-pencil"></i>
                                        </a>

                                        <a
                                            href="turf-console/users.php?action=delete&id=<?php echo (int)$u['id']; ?>"
                                            class="icon-btn"
                                            title="Delete"
                                            onclick="return confirm('Delete this admin? This cannot be undone.');">
                                            <i class="fa fa-trash-o"></i>
                                        </a>

                                    </div>

                                </td>

                            </tr>

                        <?php endforeach; ?>

                    </tbody>

                </table>

            </div>

        </form>


        <!-- =========================================================
         PAGINATION
         ========================================================= -->

        <?php if ($total_pages > 1): ?>

            <div class="pagination">

                <a
                    href="<?php echo htmlspecialchars(buildPageUrl(max(1, $page - 1), $search, $status_filter)); ?>"
                    class="page-link <?php echo $page <= 1 ? 'disabled' : ''; ?>">
                    « Prev
                </a>

                <?php

                $window = 1;

                $pagesToShow = array(1, $total_pages);

                for ($p = $page - $window; $p <= $page + $window; $p++) {
                    if ($p >= 1 && $p <= $total_pages) {
                        $pagesToShow[] = $p;
                    }
                }

                $pagesToShow = array_unique($pagesToShow);
                sort($pagesToShow);

                $prevShown = 0;

                foreach ($pagesToShow as $p) {

                    if ($prevShown && $p - $prevShown > 1) {
                        echo '<span class="page-link disabled">...</span>';
                    }

                    echo '<a href="' .
                        htmlspecialchars(buildPageUrl($p, $search, $status_filter)) .
                        '" class="page-link ' . ($p == $page ? 'active' : '') . '">' .
                        $p .
                        '</a>';

                    $prevShown = $p;
                }

                ?>

                <a
                    href="<?php echo htmlspecialchars(buildPageUrl(min($total_pages, $page + 1), $search, $status_filter)); ?>"
                    class="page-link <?php echo $page >= $total_pages ? 'disabled' : ''; ?>">
                    Next »
                </a>

            </div>

        <?php endif; ?>


        <script>
            var searchTimer;

            function debouncedSubmit() {

                clearTimeout(searchTimer);

                searchTimer = setTimeout(function() {
                    document.getElementById('filter-form').submit();
                }, 500);
            }
        </script>


    <?php else: ?>


        <!-- =========================================================
         ADD / EDIT ADMIN
         ========================================================= -->

        <div class="page-head">

            <div>

                <p class="eyebrow">
                    <?php echo $edit_user ? 'Edit Admin' : 'New Admin'; ?>
                </p>

                <h1>
                    <?php
                    echo ($edit_user && !empty($edit_user['firstname']))
                        ? htmlspecialchars($edit_user['firstname'] . ' ' . $edit_user['lastname'])
                        : 'Add Admin';
                    ?>
                </h1>

                <p>Manage administrator details and user group access.</p>

            </div>

            <a href="turf-console/users.php" class="btn btn-ghost">
                <i class="fa fa-arrow-left"></i>
                Back to list
            </a>

        </div>


        <form
            method="post"
            action="turf-console/users.php?action=form"
            id="form-user">

            <?php if ($edit_user && !empty($edit_user['id'])): ?>

                <input
                    type="hidden"
                    name="id"
                    value="<?php echo (int)$edit_user['id']; ?>">

            <?php endif; ?>


            <div class="form-card">


                <!-- =================================================
                 1. BASIC DETAILS
                 ================================================= -->

                <div class="form-section">

                    <h3 class="section-title">Basic Details</h3>

                    <p class="section-desc">Login identity and administrator details.</p>

                    <div class="grid-2">

                        <div class="field">
                            <label>Username <span class="req">*</span></label>
                            <input
                                type="text"
                                name="username"
                                placeholder="e.g. test"
                                value="<?php echo htmlspecialchars($edit_user['username'] ?? ''); ?>">
                        </div>

                        <div class="field">
                            <label>Email Address <span class="req">*</span></label>
                            <input
                                type="text"
                                name="email"
                                placeholder="name@rwitc.com"
                                value="<?php echo htmlspecialchars($edit_user['email'] ?? ''); ?>">
                        </div>

                    </div>

                    <div class="grid-2">

                        <div class="field">
                            <label>First Name <span class="req">*</span></label>
                            <input
                                type="text"
                                name="firstname"
                                placeholder="e.g. Rohan"
                                value="<?php echo htmlspecialchars($edit_user['firstname'] ?? ''); ?>">
                        </div>

                        <div class="field">
                            <label>Last Name <span class="req">*</span></label>
                            <input
                                type="text"
                                name="lastname"
                                placeholder="e.g. Sharma"
                                value="<?php echo htmlspecialchars($edit_user['lastname'] ?? ''); ?>">
                        </div>

                    </div>

                    <div class="grid-2">

                        <div class="field">
                            <label>Phone Number</label>
                            <input
                                type="text"
                                name="phoneno"
                                placeholder="e.g. 9999999999"
                                value="<?php echo htmlspecialchars($edit_user['phoneno'] ?? ''); ?>">
                        </div>

                        <div class="field">
                            <label>Role</label>
                            <input
                                type="text"
                                name="role"
                                placeholder="e.g. ADMIN"
                                value="<?php echo htmlspecialchars($edit_user['role'] ?? ''); ?>">
                        </div>

                    </div>

                </div>


                <!-- =================================================
                 2. SECURITY (moved up)
                 ================================================= -->

                <div class="form-section">

                    <h3 class="section-title">Security</h3>

                    <p class="section-desc">
                        <?php
                        echo $edit_user
                            ? 'Leave password blank to keep the current password.'
                            : 'Set a password for this administrator.';
                        ?>
                    </p>

                    <div class="grid-2">

                        <div class="field">

                            <label>
                                Password
                                <?php if (!$edit_user): ?>
                                    <span class="req">*</span>
                                <?php endif; ?>
                            </label>

                            <div class="pw-wrap">
                                <input
                                    type="password"
                                    name="password"
                                    id="pwField"
                                    placeholder="********"
                                    autocomplete="new-password">
                                <button type="button" class="pw-toggle" data-target="pwField" title="Show / hide password">
                                    <i class="fa fa-eye"></i>
                                </button>
                            </div>

                        </div>

                        <div class="field">

                            <label>
                                Confirm Password
                                <?php if (!$edit_user): ?>
                                    <span class="req">*</span>
                                <?php endif; ?>
                            </label>

                            <div class="pw-wrap">
                                <input
                                    type="password"
                                    name="confirm"
                                    id="pwConfirm"
                                    placeholder="********"
                                    autocomplete="new-password">
                                <button type="button" class="pw-toggle" data-target="pwConfirm" title="Show / hide password">
                                    <i class="fa fa-eye"></i>
                                </button>
                            </div>

                        </div>

                    </div>

                    <div class="section-desc" style="margin-bottom:0;">
                        <i class="fa fa-info-circle"></i>
                        Minimum 8 characters.
                    </div>

                </div>


                <!-- =================================================
                 3. ACCESS & PERMISSIONS (status)
                 ================================================= -->

                <div class="form-section">

                    <h3 class="section-title">Access & Permissions</h3>

                    <p class="section-desc">
                        Module access is handled by group selection below.
                        This admin can be assigned permissions from one or more groups.
                    </p>

                    <div class="grid-2">

                        <div class="field">

                            <label>Status</label>

                            <select name="status">

                                <option
                                    value="Y"
                                    <?php
                                    echo (isset($edit_user['active']) && $edit_user['active'] == 'Y') ? 'selected' : '';
                                    ?>>
                                    Active
                                </option>

                                <option
                                    value="N"
                                    <?php
                                    echo (!isset($edit_user['active']) || $edit_user['active'] == 'N') ? 'selected' : '';
                                    ?>>
                                    Disabled
                                </option>

                            </select>

                        </div>

                    </div>

                </div>


                <!-- =================================================
                 4. GROUP MODULES (dropdown + summary, at the bottom)
                 ================================================= -->

                <div class="form-section">

                    <h3 class="section-title">Group Modules</h3>
                    <p class="section-desc">
                        Select a group, then tick the modules this admin should get from that group.
                        You can switch between groups; selections made in every group are saved.
                    </p>

                    <?php if (empty($groupsForForm)): ?>

                        <p class="section-desc">No user groups with modules found.</p>

                    <?php else: ?>

                        <div class="field" style="max-width:420px;">
                            <label for="groupSelect">User Modules</label>
                            <select id="groupSelect">
                                <option value="">-- Select Group --</option>
                                <?php foreach ($groupsForForm as $gId => $g): ?>
                                    <option value="<?php echo (int)$gId; ?>"
                                        data-name="<?php echo htmlspecialchars($g['name']); ?>">
                                        <?php echo htmlspecialchars($g['name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <?php foreach ($groupsForForm as $gId => $g): ?>
                            <div class="gm-panel"
                                id="gmPanel_<?php echo (int)$gId; ?>"
                                style="display:none;border:1px solid var(--line);border-radius:8px;padding:14px 16px;margin-top:6px;">

                                <div style="font-weight:600;margin-bottom:10px;">
                                    <i class="fa fa-folder-open"></i>
                                    <?php echo htmlspecialchars($g['name']); ?>
                                    <span style="font-weight:400;color:var(--ink-400);font-size:12.5px;">
                                        &mdash; modules
                                    </span>
                                </div>

                                <?php foreach ($g['mods'] as $mKey => $mLabel): ?>
                                    <label style="display:inline-flex;align-items:center;gap:6px;margin:0 18px 8px 0;font-weight:500;">
                                        <input type="checkbox" class="checkbox gm-check"
                                            data-group="<?php echo (int)$gId; ?>"
                                            data-label="<?php echo htmlspecialchars($mLabel); ?>"
                                            name="gm[<?php echo (int)$gId; ?>][]"
                                            value="<?php echo htmlspecialchars($mKey); ?>"
                                            <?php echo (isset($selectedGM[$gId]) && in_array($mKey, $selectedGM[$gId], true)) ? 'checked' : ''; ?>>
                                        <?php echo htmlspecialchars($mLabel); ?>
                                    </label>
                                <?php endforeach; ?>

                            </div>
                        <?php endforeach; ?>

                        <!-- Selected modules summary (at the very end) -->
                        <div class="gm-summary" style="margin-top:18px;margin-bottom:0;">
                            <div class="gm-summary-title">
                                <i class="fa fa-list-check"></i>
                                Selected Modules
                            </div>
                            <div id="gmSummaryBody"></div>
                        </div>


                        <script>
                            (function() {
                                var select = document.getElementById('groupSelect');
                                var summary = document.getElementById('gmSummaryBody');
                                if (!select || !summary) {
                                    return;
                                }

                                // Show only the panel of the selected group
                                function showPanel() {
                                    var panels = document.querySelectorAll('.gm-panel');
                                    for (var i = 0; i < panels.length; i++) {
                                        panels[i].style.display = 'none';
                                    }
                                    if (select.value !== '') {
                                        var active = document.getElementById('gmPanel_' + select.value);
                                        if (active) {
                                            active.style.display = 'block';
                                        }
                                    }
                                }

                                // Show how many modules are ticked in each group, inside the dropdown
                                function updateCounts() {
                                    for (var i = 0; i < select.options.length; i++) {
                                        var opt = select.options[i];
                                        if (opt.value === '') {
                                            continue;
                                        }
                                        var count = document.querySelectorAll(
                                            '.gm-check[data-group="' + opt.value + '"]:checked'
                                        ).length;
                                        var name = opt.getAttribute('data-name');
                                        opt.text = count > 0 ? name + ' (' + count + ' selected)' : name;
                                    }
                                }

                                // Build the summary: every group that has ticked modules + its module chips
                                function renderSummary() {
                                    summary.innerHTML = '';
                                    var any = false;

                                    for (var i = 0; i < select.options.length; i++) {
                                        var opt = select.options[i];
                                        if (opt.value === '') {
                                            continue;
                                        }

                                        var checked = document.querySelectorAll(
                                            '.gm-check[data-group="' + opt.value + '"]:checked'
                                        );
                                        if (checked.length === 0) {
                                            continue;
                                        }
                                        any = true;

                                        var row = document.createElement('div');
                                        row.className = 'gm-sum-row';

                                        var gname = document.createElement('div');
                                        gname.className = 'gm-sum-group';
                                        gname.setAttribute('data-group', opt.value);
                                        gname.title = 'Click to open this group';
                                        gname.appendChild(document.createTextNode(opt.getAttribute('data-name') + ' '));
                                        var cnt = document.createElement('span');
                                        cnt.className = 'cnt';
                                        cnt.textContent = '(' + checked.length + ')';
                                        gname.appendChild(cnt);
                                        gname.addEventListener('click', function() {
                                            select.value = this.getAttribute('data-group');
                                            showPanel();
                                        });

                                        var chips = document.createElement('div');
                                        chips.className = 'gm-sum-chips';

                                        for (var j = 0; j < checked.length; j++) {
                                            (function(cb) {
                                                var chip = document.createElement('span');
                                                chip.className = 'gm-chip';
                                                chip.appendChild(document.createTextNode(cb.getAttribute('data-label')));

                                                var x = document.createElement('button');
                                                x.type = 'button';
                                                x.title = 'Remove';
                                                x.innerHTML = '&times;';
                                                x.addEventListener('click', function() {
                                                    cb.checked = false;
                                                    refresh();
                                                });
                                                chip.appendChild(x);
                                                chips.appendChild(chip);
                                            })(checked[j]);
                                        }

                                        row.appendChild(gname);
                                        row.appendChild(chips);
                                        summary.appendChild(row);
                                    }

                                    if (!any) {
                                        var empty = document.createElement('div');
                                        empty.className = 'gm-summary-empty';
                                        empty.textContent = 'No modules selected yet.';
                                        summary.appendChild(empty);
                                    }
                                }

                                function refresh() {
                                    updateCounts();
                                    renderSummary();
                                }

                                select.addEventListener('change', showPanel);

                                var checks = document.querySelectorAll('.gm-check');
                                for (var j = 0; j < checks.length; j++) {
                                    checks[j].addEventListener('change', refresh);
                                }

                                // On load (edit mode): auto-select the first group that already has ticked modules
                                var firstChecked = document.querySelector('.gm-check:checked');
                                if (firstChecked) {
                                    select.value = firstChecked.getAttribute('data-group');
                                }

                                refresh();
                                showPanel();
                            })();
                        </script>

                    <?php endif; ?>

                </div>


                <!-- =================================================
                 FOOTER
                 ================================================= -->

                <div class="form-footer">

                    <a href="turf-console/users.php" class="btn btn-ghost">Cancel</a>

                    <button type="submit" class="btn btn-primary">
                        <i class="fa fa-save"></i>
                        Save Admin
                    </button>

                </div>


            </div>

        </form>


        <script>
            // Password show / hide (eye icon)
            (function() {
                var btns = document.querySelectorAll('.pw-toggle');
                for (var i = 0; i < btns.length; i++) {
                    btns[i].addEventListener('click', function() {
                        var input = document.getElementById(this.getAttribute('data-target'));
                        var icon = this.querySelector('i');
                        if (!input) {
                            return;
                        }
                        if (input.type === 'password') {
                            input.type = 'text';
                            icon.className = 'fa fa-eye-slash';
                        } else {
                            input.type = 'password';
                            icon.className = 'fa fa-eye';
                        }
                    });
                }
            })();
        </script>


    <?php endif; ?>

</div>
<?php $design->writeLeftPanel(); ?>
</div>


<?php

$design->endPage();

$design = NULL;

?>