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
    require_once __DIR__ . '/rwitc_website_api/config/config.php';

    use Aws\S3\S3Client;

    $pageTitle = 'Ratings of all horses';

    $ratingsContent = '';

    $latestFile = null;
    $latestDate = null;
    $latestSource = null;
    $latestLocalFile = null;
    $localRatingsPath = __DIR__ . '/staticpages/ratingschange/';

    if (is_dir($localRatingsPath)) {
        $localFiles = scandir($localRatingsPath);

        foreach ($localFiles as $localFile) {
            if ($localFile === '.' || $localFile === '..') {
                continue;
            }

            $extension = strtolower(pathinfo($localFile, PATHINFO_EXTENSION));

            // Only HTM / HTML files
            if (!in_array($extension, ['htm', 'html'])) {
                continue;
            }

            $localFullPath = $localRatingsPath . $localFile;

            if (!file_exists($localFullPath)) {
                continue;
            }

            if (preg_match('/_(\d{4}-\d{2}-\d{2})\.(htm|html)$/i', $localFile, $matches)) {
                $fileDate = $matches[1];
            } else {
                $fileDate = date('Y-m-d', filemtime($localFullPath));
            }

            if ($latestLocalFile === null || $fileDate > $latestLocalDate) {
                $latestLocalDate = $fileDate;
                $latestLocalFile = $localFullPath;
            }

            if ($latestDate === null || $fileDate > $latestDate) {
                $latestDate = $fileDate;
                $latestFile = $localFullPath;
                $latestSource = 'local';
            }
        }
    }


    try {
        $s3Client = new S3Client([
            'version' => 'latest',
            'region' => AWS_REGION,
            'credentials' => [
                'key' => AWS_ACCESS_KEY_ID,
                'secret' => AWS_SECRET_ACCESS_KEY,
            ],
        ]);

        $prefix = 'staticpages/ratingschange/';

        $result = $s3Client->listObjectsV2([
            'Bucket' => AWS_BUCKET,
            'Prefix' => $prefix,
        ]);

        if (!empty($result['Contents'])) {
            foreach ($result['Contents'] as $object) {
                $filename = basename($object['Key']);
                $extension = strtolower(pathinfo($filename, PATHINFO_EXTENSION));

                // Only HTM / HTML files
                if (!in_array($extension, ['htm', 'html'])) {
                    continue;
                }

                if (preg_match('/_(\d{4}-\d{2}-\d{2})\.(htm|html)$/i', $filename, $matches)) {
                    $fileDate = $matches[1];
                } else {
                    $fileDate = $object['LastModified']->format('Y-m-d');
                }

                if ($latestDate === null || $fileDate > $latestDate) {
                    $latestDate = $fileDate;
                    $latestFile = $prefix . $filename;
                    $latestSource = 's3';
                }
            }
        }

    } catch (Throwable $e) {

    }


    if ($latestSource === 's3') {
        try {
            $result = $s3Client->getObject([
                'Bucket' => AWS_BUCKET,
                'Key' => $latestFile,
            ]);

            $ratingsContent = (string) $result['Body'];
        } catch (Throwable $e) {
            if ($latestLocalFile !== null && file_exists($latestLocalFile)) {
                ob_start();
                include $latestLocalFile;
                $ratingsContent = ob_get_clean();
            } else {
                $ratingsContent = '<p>No ratings file found.</p>';
            }
        }
    } elseif ($latestSource === 'local') {
        ob_start();
        include $latestFile;
        $ratingsContent = ob_get_clean();
    } else {
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