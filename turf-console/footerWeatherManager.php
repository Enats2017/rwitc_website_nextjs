<?php
include_once('../bootstrap.php');
require_once('../lib/users.class.php');
require_once('../lib/userchecks.php');
require_once('../lib/permissions.php');

session_start();
$userObj = new Users($db);

$pageTitle  = 'Footer Weather City';
$moduleKey  = 'footer_weather';
$pageFile   = 'footerWeatherManager.php';

$cities = array(
    'PUNE'   => array('title' => 'Pune',   'icon' => 'fas fa-location-dot'),
    'MUMBAI' => array('title' => 'Mumbai', 'icon' => 'fas fa-location-dot')
);
$defaultCity = 'PUNE';

$secmsg = '';
if (!isAdminlogin()) {
    $secmsg = 'You do not have access to this page.';
} elseif (!hasModuleAccess($moduleKey)) {
    $secmsg = 'You do not have access to this module.';
}

if (empty($_SESSION['fweather_csrf'])) {
    $_SESSION['fweather_csrf'] = bin2hex(random_bytes(16));
}
$csrf = $_SESSION['fweather_csrf'];
$flash = isset($_SESSION['fweather_flash']['text']) ? $_SESSION['fweather_flash']['text'] : '';
$flashType = isset($_SESSION['fweather_flash']['type']) ? $_SESSION['fweather_flash']['type'] : '';
unset($_SESSION['fweather_flash']);

if (empty($secmsg) && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $error = '';
    $city = '';

    if (!isset($_POST['csrf']) || !hash_equals($csrf, (string)$_POST['csrf'])) {
        $error = 'Invalid request. Please try again.';
    } else {
        $city = isset($_POST['city']) && is_string($_POST['city']) ? strtoupper(trim($_POST['city'])) : '';
        if (!isset($cities[$city])) {
            $error = 'Please select a valid city.';
        }
    }

    if ($error === '') {
        try {
            $db->query(
                "INSERT INTO footer_weather_city (id, city_key, `created`, `updated`)
                 VALUES (1, '" . $db->escape($city) . "', NOW(), NOW())
                 ON DUPLICATE KEY UPDATE
                    city_key = VALUES(city_key),
                    `updated` = NOW()"
            );
            $_SESSION['fweather_flash'] = array('text' => 'Weather city updated successfully. The website now shows ' . $cities[$city]['title'] . '.', 'type' => 'success');
        } catch (Exception $e) {
            error_log('Footer weather city save error: ' . $e->getMessage());
            $_SESSION['fweather_flash'] = array('text' => 'Database error. Could not save.', 'type' => 'error');
        }
    } else {
        $_SESSION['fweather_flash'] = array('text' => $error, 'type' => 'error');
    }

    header('Location: ' . $pageFile);
    exit;
}

$currentCity = $defaultCity;
$lastUpdated = '';
if (empty($secmsg)) {
    $row = $db->getSingleRowAssoc("SELECT city_key, `updated` FROM footer_weather_city WHERE id = 1");
    if ($row) {
        $saved = strtoupper(trim((string)$row['city_key']));
        if (isset($cities[$saved])) {
            $currentCity = $saved;
        }
        $lastUpdated = '';
        if (!empty($row['updated'])) {
            $dt = new DateTime($row['updated'], new DateTimeZone('UTC'));
            $dt->setTimezone(new DateTimeZone('Asia/Kolkata'));
            $lastUpdated = $dt->format('d M Y, h:i A');
        }
    }
}

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
.ar-header{margin-bottom:20px}.ar-title{font-size:24px;font-weight:700;color:#2b332f;margin:0}.ar-subtitle{font-size:14px;color:#7a8c84;margin:6px 0 0}.ar-card{background:#fff;border:1px solid #e2e6e4;border-radius:12px;padding:24px;box-shadow:0 1px 2px rgba(0,0,0,.03);max-width:100%;margin-bottom:26px}.ar-card-title{font-size:17px;font-weight:600;color:#2b332f;margin:0 0 20px;padding-bottom:14px;border-bottom:1px solid #eef1ef}.ar-updated{font-size:13px;color:#7a8c84;margin-bottom:14px}.ar-hint{font-size:12px;color:#7a8c84;margin:14px 0 0;line-height:1.7}.ar-badge{display:inline-block;padding:3px 10px;border-radius:20px;font-size:12px;font-weight:600;background:#e6f4ec;color:#0f5c33}
.fw-options{display:flex;gap:14px;flex-wrap:wrap}.fw-option{flex:1 1 220px;position:relative}.fw-option input{position:absolute;opacity:0;pointer-events:none}.fw-option label{display:flex;align-items:center;gap:12px;padding:18px 20px;border:1px solid #d3d9d6;border-radius:10px;cursor:pointer;font-size:16px;font-weight:600;color:#2b332f;background:#fff;transition:.2s ease}.fw-option label:hover{border-color:#1a7a45;background:#f7fbf9}.fw-option label i{font-size:20px;color:#0f5c33}.fw-option .fw-check{margin-left:auto;display:none}.fw-option input:checked+label{border-color:#0f5c33;background:#e6f4ec;color:#0f5c33;box-shadow:0 0 0 3px rgba(26,122,69,.12)}.fw-option input:checked+label .fw-check{display:inline}.fw-option input:focus-visible+label{outline:2px solid #1a7a45;outline-offset:2px}
.ar-actions{display:flex;gap:10px;margin-top:18px;padding-top:18px;border-top:1px solid #eef1ef;flex-wrap:wrap}.ar-btn{display:inline-flex;align-items:center;justify-content:center;gap:8px;padding:11px 22px;font-size:15px;font-weight:600;border-radius:8px;border:1px solid transparent;cursor:pointer;text-decoration:none}.ar-btn-primary{background:#0f5c33;color:#fff}.ar-btn-primary:hover{background:#1a7a45;color:#fff}html,body{scrollbar-width:none;-ms-overflow-style:none}html::-webkit-scrollbar,body::-webkit-scrollbar{display:none}
@media(min-width:1920px){.ar-title{font-size:30px}.ar-card{padding:30px}}@media(max-width:700px){.ar-title{font-size:20px}.ar-card{padding:16px;border-radius:10px}}@media(max-width:560px){.ar-actions{flex-direction:column}.ar-btn{width:100%;box-sizing:border-box}.fw-option{flex:1 1 100%}}
</style>
<?php if ($secmsg !== '') { ?><div class="message"><?php echo htmlspecialchars($secmsg); ?></div><?php } ?>
<?php if ($secmsg === '') { ?>
<div class="main-content">
    <div class="ar-header">
        <h1 class="ar-title">FOOTER WEATHER CITY</h1>
        <p class="ar-subtitle">Choose which city's weather is shown in the website footer. Only the selected city is displayed.</p>
    </div>
    <?php if ($flash !== '') { ?><div class="message <?php echo htmlspecialchars($flashType); ?>"><?php echo htmlspecialchars($flash); ?></div><?php } ?>

    <div class="ar-card">
        <div class="ar-card-title">Weather City</div>
        <div class="ar-updated">
            Currently shown on website: <span class="ar-badge"><?php echo htmlspecialchars($cities[$currentCity]['title']); ?></span>
            <?php if ($lastUpdated !== '') { ?>&nbsp;|&nbsp; <i class="fas fa-clock"></i> Last updated: <?php echo htmlspecialchars($lastUpdated); ?><?php } ?>
        </div>
        <form id="fwForm" method="post" action="turf-console/<?php echo htmlspecialchars($pageFile); ?>">
            <input type="hidden" name="csrf" value="<?php echo htmlspecialchars($csrf); ?>">

            <div class="fw-options">
                <?php foreach ($cities as $key => $info) { ?>
                    <div class="fw-option">
                        <input type="radio" name="city" id="city_<?php echo htmlspecialchars($key); ?>" value="<?php echo htmlspecialchars($key); ?>"<?php echo ($key === $currentCity) ? ' checked' : ''; ?>>
                        <label for="city_<?php echo htmlspecialchars($key); ?>">
                            <i class="<?php echo htmlspecialchars($info['icon']); ?>"></i>
                            <?php echo htmlspecialchars($info['title']); ?>
                            <i class="fas fa-circle-check fw-check"></i>
                        </label>
                    </div>
                <?php } ?>
            </div>

            <p class="ar-hint">The change appears on the website within about a minute.</p>

            <div class="ar-actions">
                <button type="submit" class="ar-btn ar-btn-primary"><i class="fas fa-save"></i> Save City</button>
            </div>
        </form>
    </div>
</div>
<?php } ?>
<?php
$design->closeDiv();
$design->writeLeftPanel();
$design->closeDiv();
$design->closeDiv();
$design->endPage();
$design = null;
?>