<?php

require_once __DIR__ . "/config/config.php";
require_once __DIR__ . "/../vendor/autoload.php";

use Aws\S3\S3Client;
use Aws\Exception\AwsException;

$key = isset($_GET['key']) ? $_GET['key'] : '';

if ($key === '') {
    http_response_code(400);
    exit("Missing key");
}

try {

    $s3Client = new S3Client([
        'version'     => 'latest',
        'region'      => AWS_REGION,
        'credentials' => [
            'key'    => AWS_ACCESS_KEY_ID,
            'secret' => AWS_SECRET_ACCESS_KEY,
        ],
    ]);

    $result = $s3Client->getObject([
        'Bucket' => AWS_BUCKET,
        'Key'    => $key,
    ]);

    header("Content-Type: " . $result['ContentType']);
    header("Cache-Control: public, max-age=86400");
    echo $result['Body'];

} catch (AwsException $e) {

    http_response_code(500);

    echo "<pre>";
    echo "AWS ERROR\n";
    echo "Message: " . $e->getMessage() . "\n";
    echo "AWS Error Code: " . $e->getAwsErrorCode() . "\n";
    echo "AWS Error Type: " . $e->getAwsErrorType() . "\n";
    echo "Bucket: " . AWS_BUCKET . "\n";
    echo "Region: " . AWS_REGION . "\n";
    echo "Key: " . $key . "\n";
    echo "</pre>";

    exit;
}