<?php
    header('Access-Control-Allow-Origin: http://localhost:3000');
    header('Access-Control-Allow-Methods: GET, OPTIONS');
    header('Access-Control-Allow-Headers: Content-Type');

    if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
        http_response_code(200);
        exit;
    }

    include_once('bootstrap.php');
    require_once __DIR__ . '/vendor/autoload.php';
    require_once __DIR__ . '/rwitc_website_api/config/config.php'; // provides $conn (mysqli) + AWS_* constants

    use Aws\S3\S3Client;

    $pageTitle = 'Ratings of all horses';

    $ratingsContent   = '';
    $prefix           = 'staticpages/ratingschange/';
    $localRatingsPath = __DIR__ . '/staticpages/ratingschange/';

    // ------------------------------------------------------------
    // 1) DB is the source of truth: get the latest row's filename.
    //    Delete a row in `ratings_change` -> next latest takes over.
    // ------------------------------------------------------------
    $latestFilename = null;

    $dbResult = $conn->query(
        "SELECT filename FROM ratings_change ORDER BY racedate DESC, id DESC LIMIT 1"
    );

    if ($dbResult && $row = $dbResult->fetch_assoc()) {
        $latestFilename = $row['filename'];
    }

    if ($latestFilename) {

        // 2) Try S3 first for this exact filename.
        try {
            $s3Client = new S3Client([
                'version' => 'latest',
                'region' => AWS_REGION,
                'credentials' => [
                    'key' => AWS_ACCESS_KEY_ID,
                    'secret' => AWS_SECRET_ACCESS_KEY,
                ],
            ]);

            $s3Result = $s3Client->getObject([
                'Bucket' => AWS_BUCKET,
                'Key' => $prefix . $latestFilename,
            ]);

            $ratingsContent = (string) $s3Result['Body'];

        } catch (Throwable $e) {
            $ratingsContent = '';
        }

        // 3) If not on S3 (deleted / not uploaded there), fall back to local folder.
        if ($ratingsContent === '') {
            $localFullPath = $localRatingsPath . $latestFilename;

            if (file_exists($localFullPath)) {
                ob_start();
                include $localFullPath;
                $ratingsContent = ob_get_clean();
            }
        }
    }

    if ($ratingsContent === '') {
        $ratingsContent = '<p>No ratings file found.</p>';
    }

    if (isset($_GET['content']) && $_GET['content'] === '1') {
        header('Content-Type: text/html; charset=UTF-8');
        echo $ratingsContent;
        exit;
    }

    $design = new Design();

    $design->js = '';

    $design->css = '
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">

        <style type="text/css">

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

        .horseratings-title {
            font-size: 24px;
            color: #2b332f;
            margin: 0 0 20px 0;
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .horseratings-title i {
            color: #0f5c33;
        }

        html,
        body {
            scrollbar-width: none;
            -ms-overflow-style: none;
        }

        html::-webkit-scrollbar,
        body::-webkit-scrollbar {
            display: none;
        }

        .horseratings-card {
            background: #fff;
            border: 1px solid #e2e6e4;
            border-radius: 12px;
            padding: 24px;
            box-shadow: 0 1px 2px rgba(0,0,0,0.03);
            overflow-x: auto;
        }

        @media (max-width: 700px) {

            #leftArea.col-lg-9 {
                padding: 0 16px;
            }

            .horseratings-card {
                padding: 16px;
            }
        }

        </style>
    ';


    $design->startPage("$pageTitle");

    $design->writeLogoTickerMenu();

    $design->openDiv("contentWrapper");

    $design->openDiv("infoWrapper", "col-lg-12");

    $design->openDiv("leftArea", 'col-lg-9');

?>

<div class="horseratings-card">
    <?php echo $ratingsContent; ?>
</div>

<?php
    $design->closeDiv();
    $design->writeLeftPanel();
    $design->closeDiv();
    $design->endPage();
    $design = NULL;
?>