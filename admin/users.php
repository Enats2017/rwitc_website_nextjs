<?php

error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once("../bootstrap.php");
require_once("../lib/dbTools.php");

session_start();

$db = new dbTool();

$action = isset($_GET['action']) ? $_GET['action'] : 'list';

/* ---------------------------------------------------------------------
   Load groups (for dropdown + name lookups) - real data, not hardcoded
   --------------------------------------------------------------------- */
$user_groups = $db->getMultiDimensionalArray("SELECT user_group_id, name FROM user_group ORDER BY name ASC");

/* ---------------------------------------------------------------------
   DELETE (single row)
   --------------------------------------------------------------------- */
if ($action == 'delete' && isset($_GET['id'])) {
    $uid = (int)$_GET['id'];
    try {
        $db->query("DELETE FROM users WHERE id = $uid");
        header("Location: admin/users.php?msg=deleted");
        exit;
    } catch (Exception $e) {
        header("Location: admin/users.php?msg=error&err=" . urlencode($e->getMessage()));
        exit;
    }
}

/* ---------------------------------------------------------------------
   BULK DELETE (checkboxes in list view)
   --------------------------------------------------------------------- */
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['bulk_ids']) && isset($_POST['bulk_delete'])) {
    $ids = array_filter(array_map('intval', $_POST['bulk_ids']));
    if (!empty($ids)) {
        try {
            $db->query("DELETE FROM users WHERE id IN (" . implode(',', $ids) . ")");
            header("Location: admin/users.php?msg=bulk_deleted");
            exit;
        } catch (Exception $e) {
            header("Location: admin/users.php?msg=error&err=" . urlencode($e->getMessage()));
            exit;
        }
    } else {
        header("Location: admin/users.php?msg=error&err=" . urlencode("No users selected."));
        exit;
    }
}

/* ---------------------------------------------------------------------
   SAVE (INSERT / UPDATE)
   --------------------------------------------------------------------- */
$form_error = null;
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['firstname']) && !isset($_POST['bulk_delete'])) {
    $firstname = trim($_POST['firstname']);
    $lastname  = trim($_POST['lastname']);
    $email     = trim($_POST['email']);
    $phoneno   = trim($_POST['phoneno']);
    $role      = trim($_POST['role']);
    $group_id  = isset($_POST['user_group_id']) ? (int)$_POST['user_group_id'] : 0;
    $verified  = (isset($_POST['status']) && $_POST['status'] == 'Y') ? 'Y' : 'N';
    $password  = isset($_POST['password']) ? $_POST['password'] : '';
    $confirm   = isset($_POST['confirm']) ? $_POST['confirm'] : '';
    $edit_id   = isset($_POST['id']) ? (int)$_POST['id'] : 0;

    if ($firstname === '' || $lastname === '') {
        $form_error = "First name and last name are required.";
    } elseif ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $form_error = "A valid email address is required.";
    } elseif (!$edit_id && $password === '') {
        $form_error = "Password is required for a new user.";
    } elseif ($password !== '' && $password !== $confirm) {
        $form_error = "Password and confirm password do not match.";
    } elseif ($password !== '' && strlen($password) < 8) {
        $form_error = "Password must be at least 8 characters.";
    } else {
        try {
            // duplicate email check (excluding self when editing)
            $dupSql = "SELECT id FROM users WHERE email = '" . $db->escape($email) . "'";
            if ($edit_id) $dupSql .= " AND id != $edit_id";
            $dup = $db->getSingleRowAssoc($dupSql);
            if ($dup) {
                $form_error = "This email is already used by another account.";
            } else {
                if ($edit_id) {
                    $sql = "UPDATE users SET "
                        . "firstname='" . $db->escape($firstname) . "', "
                        . "lastname='" . $db->escape($lastname) . "', "
                        . "email='" . $db->escape($email) . "', "
                        . "phoneno='" . $db->escape($phoneno) . "', "
                        . "role='" . $db->escape($role) . "', "
                        . "user_group_id=" . ($group_id ?: 'NULL') . ", "
                        . "verified='" . $verified . "'";
                    if ($password !== '') {
                        $sql .= ", password='"
                            . $db->escape($db->getSingleValue("SELECT PASSWORD('" . $db->escape($password) . "')"))
                            . "'";
                    }
                    $sql .= " WHERE id = $edit_id";
                    $db->update($sql);
                    header("Location: users.php?msg=updated");
                    exit;
                } else {
                    $hashedPassword = '*' . strtoupper(sha1(sha1($password, true)));

                    $sql = "INSERT INTO users "
                        . "(firstname, lastname, email, phoneno, role, user_group_id, verified, password, created) VALUES ("
                        . "'" . $db->escape($firstname) . "', "
                        . "'" . $db->escape($lastname) . "', "
                        . "'" . $db->escape($email) . "', "
                        . "'" . $db->escape($phoneno) . "', "
                        . "'" . $db->escape($role) . "', "
                        . ($group_id ?: 'NULL') . ", "
                        . "'" . $verified . "', "
                        . "'" . $hashedPassword . "', "
                        . "NOW())";

                    $db->insert($sql);
                    // New user always lands on page 1, since the list is
                    // ordered by id DESC — no extra sorting needed here.
                    header("Location: users.php?msg=added");
                    exit;
                }
            }
        } catch (Exception $e) {
            $form_error = "Save failed: " . $e->getMessage();
        }
    }
}

/* ---------------------------------------------------------------------
   FETCH - list view (search + status filter + pagination)
   --------------------------------------------------------------------- */
$users          = array();
$search         = '';
$status_filter  = '';
$page           = 1;
$per_page       = 20;
$total_pages    = 1;
$filtered_count = 0;
$total_count    = 0;
$active_count   = 0;
$inactive_count = 0;

if ($action == 'list') {

    $search        = isset($_GET['q']) ? trim($_GET['q']) : '';
    $status_filter = isset($_GET['status_filter']) ? $_GET['status_filter'] : '';
    if ($status_filter !== 'Y' && $status_filter !== 'N') $status_filter = '';
    $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
    if ($page < 1) $page = 1;

    // Stat strip always reflects ALL users, regardless of search/filter.
    $all_verified = $db->getMultiDimensionalArray("SELECT verified FROM users");
    $total_count    = count($all_verified);
    $active_count   = count(array_filter($all_verified, function ($u) {
        return $u['verified'] == 'Y';
    }));
    $inactive_count = $total_count - $active_count;

    // Build WHERE clause for search + status filter
    $where = array();
    if ($search !== '') {
        $s = $db->escape($search);
        $where[] = "(u.firstname LIKE '%$s%' OR u.lastname LIKE '%$s%' "
            . "OR u.email LIKE '%$s%' OR CONCAT(u.firstname,' ',u.lastname) LIKE '%$s%')";
    }
    if ($status_filter !== '') {
        $where[] = "u.verified = '" . $status_filter . "'";
    }
    $whereSql = !empty($where) ? (' WHERE ' . implode(' AND ', $where)) : '';

    // Count matching rows (for pagination)
    $countRow       = $db->getSingleRowAssoc("SELECT COUNT(*) AS cnt FROM users u" . $whereSql);
    $filtered_count = $countRow ? (int)$countRow['cnt'] : 0;
    $total_pages    = max(1, (int)ceil($filtered_count / $per_page));
    if ($page > $total_pages) $page = $total_pages;
    $offset = ($page - 1) * $per_page;

    // Newest user first (ORDER BY id DESC) so a freshly added user is
    // always at the top of page 1.
    $sql = "SELECT u.*, ug.name AS group_name
            FROM users u
            LEFT JOIN user_group ug ON u.user_group_id = ug.user_group_id
            $whereSql
            ORDER BY u.id DESC
            LIMIT $per_page OFFSET $offset";
    $users = $db->getMultiDimensionalArray($sql);
}

/* ---------------------------------------------------------------------
   FETCH - single user for edit form
   --------------------------------------------------------------------- */
$edit_user = null;
if ($action == 'form' && $_SERVER['REQUEST_METHOD'] !== 'POST' && isset($_GET['id'])) {
    $edit_user = $db->getSingleRowAssoc("SELECT * FROM users WHERE id = " . (int)$_GET['id']);
}
// re-populate form after a validation error
if ($form_error && $_SERVER['REQUEST_METHOD'] == 'POST') {
    $edit_user = array(
        'id'            => isset($_POST['id']) ? $_POST['id'] : null,
        'firstname'     => $_POST['firstname'],
        'lastname'      => $_POST['lastname'],
        'email'         => $_POST['email'],
        'phoneno'       => $_POST['phoneno'],
        'role'          => $_POST['role'],
        'user_group_id' => $_POST['user_group_id'],
        'verified'      => (isset($_POST['status']) && $_POST['status'] == 'Y') ? 'Y' : 'N',
    );
}

function initials($first, $last)
{
    $first = $first ?: '';
    $last  = $last ?: '';
    $i = strtoupper(mb_substr($first, 0, 1) . mb_substr($last, 0, 1));
    return $i !== '' ? $i : '?';
}
function fmtDate($val)
{
    if (!$val || $val === '0000-00-00 00:00:00') return '-';
    return date('d M Y', strtotime($val));
}
// Builds a admin/users.php?... link that keeps the current search/filter/page state
function buildPageUrl($p, $search, $status_filter)
{
    $params = array('action' => 'list');
    if ($search !== '') $params['q'] = $search;
    if ($status_filter !== '') $params['status_filter'] = $status_filter;
    if ($p > 1) $params['page'] = $p;
    return 'admin/users.php?' . http_build_query($params);
}

$msg = isset($_GET['msg']) ? $_GET['msg'] : null;
?>
<?php
$design = new Design();

$design->css = '
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Newsreader:ital,opsz,wght@0,6..72,500;0,6..72,600;1,6..72,500&family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css">
<style>
        :root {
            --green-900: #0d3b28;
            --green-700: #14532d;
            --green-600: #1a6b3c;
            --gold-500: #c9a227;
            --gold-400: #d9b84a;
            --ink-900: #1a211d;
            --ink-600: #5b655f;
            --ink-400: #8a938d;
            --line: #e4e8e4;
            --bg: #f6f7f5;
            --surface: #ffffff;
            --danger: #c0392b;
            --danger-bg: #fbeae8;
            --success-bg: #e9f3ec;
            --radius: 10px;
            --shadow: 0 1px 2px rgba(20, 30, 24, 0.04), 0 4px 16px rgba(20, 30, 24, 0.05);
        }

        * {
            box-sizing: border-box;
        }

        html,
        body {
            margin: 0;
            padding: 0;
        }

        body {
            background: var(--bg);
            color: var(--ink-900);
            font-family: \'Inter\', -apple-system, sans-serif;
            font-size: 14.5px;
            -webkit-font-smoothing: antialiased;
            line-height: 1.5;
        }

        a {
            text-decoration: none;
            color: inherit;
        }

        button {
            font-family: inherit;
            cursor: pointer;
        }

        .page-wrap {
            max-width: 1180px;
            margin: 0 auto;
            padding: 32px 28px 60px;
        }

        .eyebrow {
            font-size: 11.5px;
            font-weight: 600;
            letter-spacing: 0.1em;
            text-transform: uppercase;
            color: var(--gold-500);
            margin: 0 0 6px;
        }

        .page-head {
            display: flex;
            align-items: flex-end;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: 16px;
            margin-bottom: 22px;
            padding-bottom: 20px;
            border-bottom: 1px solid var(--line);
        }

        .page-head h1 {
            font-family: \'Newsreader\', serif;
            font-weight: 600;
            font-size: 30px;
            margin: 0;
            color: var(--green-900);
        }

        .page-head p {
            margin: 4px 0 0;
            color: var(--ink-600);
            font-size: 13.5px;
        }

        .btn {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 10px 18px;
            border-radius: 8px;
            border: 1px solid transparent;
            font-size: 13.5px;
            font-weight: 600;
            transition: all .15s ease;
        }

        .btn-primary {
            background: var(--green-700);
            color: #fff;
        }

        .btn-primary:hover {
            background: var(--green-900);
        }

        .btn-ghost {
            background: #fff;
            color: var(--ink-900);
            border-color: var(--line);
        }

        .btn-ghost:hover {
            border-color: var(--ink-400);
        }

        .btn-danger-ghost {
            background: #fff;
            color: var(--danger);
            border-color: var(--line);
        }

        .btn-danger-ghost:hover {
            background: var(--danger-bg);
            border-color: var(--danger);
        }

        .btn:disabled {
            opacity: .45;
            cursor: not-allowed;
        }

        .alert {
            padding: 12px 18px;
            border-radius: 8px;
            font-size: 13.5px;
            font-weight: 500;
            margin-bottom: 18px;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .alert-success {
            background: var(--success-bg);
            color: var(--green-700);
            border: 1px solid #cfe6d6;
        }

        .alert-danger {
            background: var(--danger-bg);
            color: var(--danger);
            border: 1px solid #f1c9c3;
        }

        .stat-strip {
            display: flex;
            gap: 0;
            background: var(--surface);
            border: 1px solid var(--line);
            border-radius: var(--radius);
            box-shadow: var(--shadow);
            margin-bottom: 24px;
            overflow: hidden;
        }

        .stat {
            flex: 1;
            padding: 16px 22px;
            border-right: 1px solid var(--line);
        }

        .stat:last-child {
            border-right: 0;
        }

        .stat .num {
            font-family: \'Newsreader\', serif;
            font-size: 25px;
            font-weight: 600;
            color: var(--green-900);
        }

        .stat .lbl {
            font-size: 12px;
            color: var(--ink-600);
            margin-top: 2px;
        }

        .stat.active .num {
            color: var(--green-600);
        }

        .stat.inactive .num {
            color: var(--danger);
        }

        .toolbar {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 14px;
            flex-wrap: wrap;
            margin-bottom: 14px;
        }

        .search-box {
            position: relative;
            flex: 1;
            max-width: 320px;
            min-width: 220px;
        }

        .search-box i {
            position: absolute;
            left: 13px;
            top: 50%;
            transform: translateY(-50%);
            color: var(--ink-400);
            font-size: 13px;
        }

        .search-box input {
            width: 100%;
            padding: 9px 12px 9px 34px;
            border: 1px solid var(--line);
            border-radius: 8px;
            font-size: 13.5px;
            font-family: inherit;
            background: #fff;
            outline: none;
            transition: border-color .15s;
        }

        .search-box input:focus {
            border-color: var(--green-600);
        }

        .toolbar-actions {
            display: flex;
            gap: 10px;
            align-items: center;
        }

        .status-filter {
            padding: 9px 12px;
            border: 1px solid var(--line);
            border-radius: 8px;
            font-size: 13.5px;
            font-family: inherit;
            background: #fff;
            color: var(--ink-900);
            outline: none;
            cursor: pointer;
        }

        .status-filter:focus {
            border-color: var(--green-600);
        }

        .results-note {
            font-size: 12.5px;
            color: var(--ink-600);
            margin-bottom: 10px;
        }

        .card {
            background: var(--surface);
            border: 1px solid var(--line);
            border-radius: var(--radius);
            box-shadow: var(--shadow);
            overflow: hidden;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        thead th {
            text-align: left;
            font-size: 11.5px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            color: var(--ink-600);
            padding: 13px 18px;
            background: #fafbfa;
            border-bottom: 1px solid var(--line);
        }

        tbody td {
            padding: 13px 18px;
            border-bottom: 1px solid var(--line);
            font-size: 13.5px;
            vertical-align: middle;
        }

        tbody tr:last-child td {
            border-bottom: 0;
        }

        tbody tr:hover {
            background: #fafcfa;
        }

        .user-cell {
            display: flex;
            align-items: center;
            gap: 11px;
        }

        .avatar-ring {
            width: 34px;
            height: 34px;
            border-radius: 50%;
            background: var(--green-700);
            color: #fff;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 12px;
            font-weight: 700;
            border: 2px solid var(--gold-400);
            flex-shrink: 0;
        }

        .user-name {
            font-weight: 600;
            color: var(--ink-900);
        }

        .user-sub {
            font-size: 12px;
            color: var(--ink-400);
        }

        .pill-group {
            display: inline-block;
            padding: 3px 10px;
            border-radius: 20px;
            font-size: 11.5px;
            font-weight: 600;
            background: #f1f0e8;
            color: var(--green-900);
            border: 1px solid #e3e1d2;
        }

        .pill-none {
            color: var(--ink-400);
            font-size: 12.5px;
        }

        .status {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            font-size: 12.5px;
            font-weight: 600;
        }

        .status .dot {
            width: 7px;
            height: 7px;
            border-radius: 50%;
        }

        .status.enabled {
            color: var(--green-600);
        }

        .status.enabled .dot {
            background: var(--green-600);
        }

        .status.disabled {
            color: var(--danger);
        }

        .status.disabled .dot {
            background: var(--danger);
        }

        .row-actions {
            display: flex;
            gap: 6px;
            justify-content: flex-end;
        }

        .icon-btn {
            width: 30px;
            height: 30px;
            border-radius: 7px;
            display: flex;
            align-items: center;
            justify-content: center;
            border: 1px solid var(--line);
            background: #fff;
            color: var(--ink-600);
            font-size: 12.5px;
            transition: all .15s;
        }

        .icon-btn:hover {
            border-color: var(--green-600);
            color: var(--green-700);
            background: #f4f8f5;
        }

        .icon-btn.danger:hover {
            border-color: var(--danger);
            color: var(--danger);
            background: var(--danger-bg);
        }

        .checkbox {
            width: 16px;
            height: 16px;
            accent-color: var(--green-700);
            cursor: pointer;
        }

        .empty {
            padding: 50px 20px;
            text-align: center;
            color: var(--ink-400);
        }

        .empty i {
            font-size: 28px;
            margin-bottom: 10px;
            display: block;
            color: var(--line);
        }

        .pagination {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 6px;
            flex-wrap: wrap;
            margin-top: 18px;
        }

        .page-link {
            min-width: 34px;
            height: 34px;
            padding: 0 10px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border: 1px solid var(--line);
            border-radius: 7px;
            background: #fff;
            color: var(--ink-700, var(--ink-900));
            font-size: 13px;
            font-weight: 600;
        }

        .page-link:hover {
            border-color: var(--green-600);
            color: var(--green-700);
        }

        .page-link.active {
            background: var(--green-700);
            border-color: var(--green-700);
            color: #fff;
        }

        .page-link.disabled {
            opacity: .4;
            pointer-events: none;
        }

        .form-card {
            background: var(--surface);
            border: 1px solid var(--line);
            border-radius: var(--radius);
            box-shadow: var(--shadow);
            overflow: hidden;
        }

        .form-section {
            padding: 26px 30px;
            border-bottom: 1px solid var(--line);
        }

        .form-section:last-child {
            border-bottom: 0;
        }

        .section-title {
            font-family: \'Newsreader\', serif;
            font-size: 16px;
            font-weight: 600;
            color: var(--green-900);
            margin: 0 0 3px;
        }

        .section-desc {
            font-size: 12.5px;
            color: var(--ink-600);
            margin: 0 0 18px;
        }

        .grid-2 {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 18px;
        }

        .field {
            margin-bottom: 16px;
        }

        .field:last-child {
            margin-bottom: 0;
        }

        .field label {
            display: block;
            font-size: 12.5px;
            font-weight: 600;
            color: var(--ink-900);
            margin-bottom: 6px;
        }

        .field label .req {
            color: var(--danger);
        }

        .field input,
        .field select {
            width: 100%;
            padding: 10px 13px;
            border: 1px solid var(--line);
            border-radius: 8px;
            font-size: 13.5px;
            font-family: inherit;
            background: #fff;
            color: var(--ink-900);
            outline: none;
            transition: border-color .15s, box-shadow .15s;
        }

        .field input:focus,
        .field select:focus {
            border-color: var(--green-600);
            box-shadow: 0 0 0 3px rgba(26, 107, 60, 0.1);
        }

        .field .hint {
            font-size: 11.5px;
            color: var(--ink-400);
            margin-top: 5px;
        }

        .field .hint i {
            margin-right: 4px;
        }

        .form-footer {
            display: flex;
            justify-content: flex-end;
            gap: 10px;
            padding: 18px 30px;
            background: #fafbfa;
        }

        @media (max-width: 860px) {
            .grid-2 {
                grid-template-columns: 1fr;
            }

            .stat-strip {
                flex-wrap: wrap;
            }

            .stat {
                flex: 1 1 50%;
                border-right: 1px solid var(--line);
            }

            .stat:nth-child(2n) {
                border-right: 0;
            }
        }

        @media (max-width: 720px) {
            thead {
                display: none;
            }

            table,
            tbody,
            tr,
            td {
                display: block;
                width: 100%;
            }

            tbody tr {
                border-bottom: 1px solid var(--line);
                padding: 14px 16px;
                position: relative;
            }

            tbody td {
                border: 0;
                padding: 6px 0;
                display: flex;
                justify-content: space-between;
                align-items: center;
                gap: 10px;
            }

            tbody td:before {
                content: attr(data-label);
                font-size: 11px;
                font-weight: 600;
                color: var(--ink-400);
                text-transform: uppercase;
                letter-spacing: 0.04em;
                flex-shrink: 0;
            }

            tbody td.user-td:before {
                display: none;
            }

            tbody td.user-td {
                padding-bottom: 10px;
            }

            .row-actions {
                justify-content: flex-start;
            }

            .page-head {
                flex-direction: column;
                align-items: flex-start;
            }

            .page-wrap {
                padding: 22px 16px 40px;
            }

            .form-section {
                padding: 20px;
            }

            .form-footer {
                padding: 16px 20px;
                flex-direction: column-reverse;
            }

            .form-footer .btn {
                width: 100%;
                justify-content: center;
            }

            .toolbar {
                flex-direction: column;
                align-items: stretch;
            }

            .search-box {
                max-width: none;
            }
        }
    </style>
';
$design->jqueryJs = "";
$design->js = "";
$design->startPage("RWITC | Users");
$design->writeLogoTickerMenu();
?>

<div class="page-wrap">

    <?php if ($msg == 'added'): ?>
        <div class="alert alert-success"><i class="fa fa-check-circle"></i> User added successfully.</div>
    <?php elseif ($msg == 'updated'): ?>
        <div class="alert alert-success"><i class="fa fa-check-circle"></i> User updated successfully.</div>
    <?php elseif ($msg == 'deleted'): ?>
        <div class="alert alert-success"><i class="fa fa-check-circle"></i> User deleted.</div>
    <?php elseif ($msg == 'bulk_deleted'): ?>
        <div class="alert alert-success"><i class="fa fa-check-circle"></i> Selected users deleted.</div>
    <?php elseif ($msg == 'error'): ?>
        <div class="alert alert-danger"><i class="fa fa-exclamation-circle"></i> Something went wrong<?php echo isset($_GET['err']) ? ': ' . htmlspecialchars($_GET['err']) : '.'; ?></div>
    <?php endif; ?>

    <?php if ($form_error): ?>
        <div class="alert alert-danger"><i class="fa fa-exclamation-circle"></i> <?php echo htmlspecialchars($form_error); ?></div>
    <?php endif; ?>

    <?php if ($action == 'list'): ?>

        <div class="page-head">
            <div>
                <p class="eyebrow">User Management</p>
                <h1>System Users</h1>
                <p>Manage who can access the RWITC admin portal and what they can do.</p>
            </div>
            <a href="admin/users.php?action=form" class="btn btn-primary"><i class="fa fa-plus"></i> Add User</a>
        </div>

        <div class="stat-strip">
            <div class="stat">
                <div class="num"><?php echo $total_count; ?></div>
                <div class="lbl">Total Users</div>
            </div>
            <div class="stat active">
                <div class="num"><?php echo $active_count; ?></div>
                <div class="lbl">Verified</div>
            </div>
            <div class="stat inactive">
                <div class="num"><?php echo $inactive_count; ?></div>
                <div class="lbl">Not Verified</div>
            </div>
            <div class="stat">
                <div class="num"><?php echo count($user_groups); ?></div>
                <div class="lbl">User Groups</div>
            </div>
        </div>

        <!-- Search + status filter: plain GET form, separate from the
             bulk-delete form below (forms can't be nested in HTML) -->
        <form method="get" action="admin/users.php" id="filter-form" class="toolbar">
            <input type="hidden" name="action" value="list">
            <div class="search-box">
                <i class="fa fa-search"></i>
                <input type="text" name="q" id="searchInput" placeholder="Search by name or email..."
                    value="<?php echo htmlspecialchars($search); ?>" oninput="debouncedSubmit()">
            </div>
            <div class="toolbar-actions">
                <select name="status_filter" class="status-filter" onchange="document.getElementById('filter-form').submit()">
                    <option value="" <?php echo $status_filter === ''  ? 'selected' : ''; ?>>All Status</option>
                    <option value="Y" <?php echo $status_filter === 'Y' ? 'selected' : ''; ?>>Active</option>
                    <option value="N" <?php echo $status_filter === 'N' ? 'selected' : ''; ?>>Disabled</option>
                </select>
                <button type="submit" class="btn btn-ghost"><i class="fa fa-search"></i> Search</button>
            </div>
        </form>

        <?php if ($filtered_count > 0): ?>
            <div class="results-note">
                Showing <?php echo (($page - 1) * $per_page) + 1; ?>–<?php echo min($page * $per_page, $filtered_count); ?>
                of <?php echo $filtered_count; ?> user<?php echo $filtered_count == 1 ? '' : 's'; ?>
                <?php if ($search !== '' || $status_filter !== ''): ?>(filtered)<?php endif; ?>
            </div>
        <?php endif; ?>

        <form method="post" action="admin/users.php" id="bulk-form">
            <div class="toolbar-actions" style="justify-content:flex-end;margin-bottom:12px;">
                <button type="submit" name="bulk_delete" value="1" class="btn btn-danger-ghost"
                    onclick="return confirm('Delete all selected users? This cannot be undone.');">
                    <i class="fa fa-trash-o"></i> Delete Selected
                </button>
            </div>

            <div class="card">
                <table id="usersTable">
                    <thead>
                        <tr>
                            <th style="width:1px;"><input type="checkbox" class="checkbox" onclick="document.querySelectorAll('.row-check').forEach(c=>c.checked=this.checked)"></th>
                            <th>User</th>
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
                                <td colspan="7">
                                    <div class="empty">
                                        <i class="fa fa-user"></i>
                                        <?php echo ($search !== '' || $status_filter !== '') ? 'No users match your search / filter.' : 'No users yet. Click "Add User" to create one.'; ?>
                                    </div>
                                </td>
                            </tr>
                        <?php endif; ?>
                        <?php foreach ($users as $u): ?>
                            <tr class="user-row">
                                <td data-label=""><input type="checkbox" class="checkbox row-check" name="bulk_ids[]" value="<?php echo (int)$u['id']; ?>"></td>
                                <td class="user-td" data-label="User">
                                    <div class="user-cell">
                                        <div class="avatar-ring"><?php echo initials($u['firstname'], $u['lastname']); ?></div>
                                        <div>
                                            <div class="user-name"><?php echo htmlspecialchars(trim($u['firstname'] . ' ' . $u['lastname'])) ?: '-'; ?></div>
                                            <div class="user-sub"><?php echo htmlspecialchars($u['email']); ?></div>
                                        </div>
                                    </div>
                                </td>
                                <td data-label="Group">
                                    <?php if (!empty($u['group_name'])): ?>
                                        <span class="pill-group"><?php echo htmlspecialchars($u['group_name']); ?></span>
                                    <?php else: ?>
                                        <span class="pill-none">No group</span>
                                    <?php endif; ?>
                                </td>
                                <td data-label="Status">
                                    <?php if ($u['verified'] == 'Y'): ?>
                                        <span class="status enabled"><span class="dot"></span> Active</span>
                                    <?php else: ?>
                                        <span class="status disabled"><span class="dot"></span> Disabled</span>
                                    <?php endif; ?>
                                </td>
                                <td data-label="Added"><?php echo fmtDate($u['created']); ?></td>
                                <td data-label="Last Login"><?php echo fmtDate($u['last_login']); ?></td>
                                <td data-label="">
                                    <div class="row-actions">
                                        <a href="admin/users.php?action=form&id=<?php echo (int)$u['id']; ?>" class="icon-btn" title="Edit"><i class="fa fa-pencil"></i></a>
                                        <a href="admin/users.php?action=delete&id=<?php echo (int)$u['id']; ?>" class="icon-btn danger" title="Delete"
                                            onclick="return confirm('Delete this user? This cannot be undone.');"><i class="fa fa-trash-o"></i></a>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </form>

        <?php if ($total_pages > 1): ?>
            <div class="pagination">
                <a href="<?php echo htmlspecialchars(buildPageUrl(max(1, $page - 1), $search, $status_filter)); ?>"
                    class="page-link <?php echo $page <= 1 ? 'disabled' : ''; ?>">« Prev</a>

                <?php
                // Compact windowed page list: 1 ... (page-1) page (page+1) ... total_pages
                // Always show first page, last page, and one page on each side of
                // the current page. Anything skipped in between becomes a single
                // "..." (non-clickable) separator — this keeps it to one row even
                // when there are hundreds of pages.
                $window = 1; // how many neighbours to show on each side of current page

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
                        echo '<span class="page-link disabled">&hellip;</span>';
                    }
                    echo '<a href="' . htmlspecialchars(buildPageUrl($p, $search, $status_filter)) . '" class="page-link ' . ($p == $page ? 'active' : '') . '">' . $p . '</a>';
                    $prevShown = $p;
                }
                ?>

                <a href="<?php echo htmlspecialchars(buildPageUrl(min($total_pages, $page + 1), $search, $status_filter)); ?>"
                    class="page-link <?php echo $page >= $total_pages ? 'disabled' : ''; ?>">Next »</a>
            </div>
        <?php endif; ?>

        <script>
            // Auto-submit the search box a moment after typing stops, so the
            // list refreshes (server-side) without needing to press Enter.
            var searchTimer;

            function debouncedSubmit() {
                clearTimeout(searchTimer);
                searchTimer = setTimeout(function() {
                    document.getElementById('filter-form').submit();
                }, 500);
            }
        </script>

    <?php else: ?>

        <div class="page-head">
            <div>
                <p class="eyebrow"><?php echo $edit_user ? 'Edit User' : 'New User'; ?></p>
                <h1><?php echo $edit_user && !empty($edit_user['firstname']) ? htmlspecialchars($edit_user['firstname'] . ' ' . $edit_user['lastname']) : 'Add User'; ?></h1>
                <p><?php echo $edit_user ? 'Update account details and permissions.' : 'Create a new account to access the admin portal.'; ?></p>
            </div>
            <a href="admin/users.php" class="btn btn-ghost"><i class="fa fa-arrow-left"></i> Back to list</a>
        </div>

        <form method="post" action="admin/users.php?action=form" id="form-user">
            <?php if ($edit_user && !empty($edit_user['id'])): ?>
                <input type="hidden" name="id" value="<?php echo (int)$edit_user['id']; ?>">
            <?php endif; ?>
            <div class="form-card">

                <div class="form-section">
                    <h3 class="section-title">Basic Details</h3>
                    <p class="section-desc">Name and login identity for this account.</p>
                    <div class="grid-2">
                        <div class="field">
                            <label>First Name <span class="req">*</span></label>
                            <input type="text" name="firstname" placeholder="e.g. Rohan" value="<?php echo htmlspecialchars($edit_user['firstname'] ?? ''); ?>">
                        </div>
                        <div class="field">
                            <label>Last Name <span class="req">*</span></label>
                            <input type="text" name="lastname" placeholder="e.g. Sharma" value="<?php echo htmlspecialchars($edit_user['lastname'] ?? ''); ?>">
                        </div>
                    </div>
                    <div class="grid-2">
                        <div class="field">
                            <label>Email Address <span class="req">*</span></label>
                            <input type="text" name="email" placeholder="name@rwitc.com" value="<?php echo htmlspecialchars($edit_user['email'] ?? ''); ?>">
                        </div>
                        <div class="field">
                            <label>Phone Number</label>
                            <input type="text" name="phoneno" placeholder="e.g. 9876543210" value="<?php echo htmlspecialchars($edit_user['phoneno'] ?? ''); ?>">
                        </div>
                    </div>
                </div>

                <div class="form-section">
                    <h3 class="section-title">Access &amp; Permissions</h3>
                    <p class="section-desc">Controls what this user can see and edit.</p>
                    <div class="grid-2">
                        <div class="field">
                            <label>User Group</label>
                            <select name="user_group_id">
                                <option value="">- No group -</option>
                                <?php foreach ($user_groups as $ug): ?>
                                    <option value="<?php echo $ug['user_group_id']; ?>"
                                        <?php echo (isset($edit_user['user_group_id']) && $edit_user['user_group_id'] == $ug['user_group_id']) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($ug['name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="field">
                            <label>Role (free text)</label>
                            <input type="text" name="role" placeholder="e.g. editor" value="<?php echo htmlspecialchars($edit_user['role'] ?? ''); ?>">
                        </div>
                    </div>
                    <div class="field">
                        <label>Status</label>
                        <select name="status">
                            <option value="Y" <?php echo (isset($edit_user['verified']) && $edit_user['verified'] == 'Y') ? 'selected' : ''; ?>>Active (Verified)</option>
                            <option value="N" <?php echo (!isset($edit_user['verified']) || $edit_user['verified'] == 'N') ? 'selected' : ''; ?>>Disabled (Not Verified)</option>
                        </select>
                    </div>
                </div>

                <div class="form-section">
                    <h3 class="section-title">Security</h3>
                    <p class="section-desc"><?php echo $edit_user ? 'Leave blank to keep the current password.' : 'Set a password for this account.'; ?></p>
                    <div class="grid-2">
                        <div class="field">
                            <label>Password <?php if (!$edit_user): ?><span class="req">*</span><?php endif; ?></label>
                            <input type="password" name="password" placeholder="********" autocomplete="new-password">
                        </div>
                        <div class="field">
                            <label>Confirm Password <?php if (!$edit_user): ?><span class="req">*</span><?php endif; ?></label>
                            <input type="password" name="confirm" placeholder="********" autocomplete="new-password">
                        </div>
                    </div>
                    <div class="hint"><i class="fa fa-info-circle"></i> Minimum 8 characters.</div>
                </div>

                <div class="form-footer">
                    <a href="admin/users.php" class="btn btn-ghost">Cancel</a>
                    <button type="submit" class="btn btn-primary"><i class="fa fa-save"></i> Save User</button>
                </div>
            </div>
        </form>

    <?php endif; ?>

</div>
<?php
$design->endPage();
$design = NULL;
?>