<?php

use Aws\S3\S3Client;

if (!class_exists('Aws\S3\S3Client')) {
    require_once __DIR__ . "/../vendor/autoload.php";
}

function getLatestS3Html($conn, $type, $raceType = "post_race")
{
    try {

        $stmt = $conn->prepare(
            "SELECT `date`, `file_url`
             FROM run_race_details
             WHERE `type` = ? AND `race_type` = ?
               AND file_url IS NOT NULL AND file_url <> ''
             ORDER BY `date` DESC, id DESC
             LIMIT 1"
        );

        if ($stmt === false) {
            return null;
        }

        $stmt->bind_param("ss", $type, $raceType);

        if (!$stmt->execute()) {
            $stmt->close();
            return null;
        }

        $res = $stmt->get_result();
        $row = ($res && $res->num_rows > 0) ? $res->fetch_assoc() : null;
        $stmt->close();

        if ($row === null) {
            return null;
        }

        $path = parse_url($row["file_url"], PHP_URL_PATH);
        $key  = ltrim(rawurldecode((string) $path), "/");

        if (strpos($key, "run_races/") !== 0) {
            $key = "run_races/" . basename($key);
        }

        $s3 = new S3Client(array(
            "version"     => "latest",
            "region"      => AWS_REGION,
            "credentials" => array(
                "key"    => AWS_ACCESS_KEY_ID,
                "secret" => AWS_SECRET_ACCESS_KEY
            )
        ));

        $obj  = $s3->getObject(array("Bucket" => AWS_BUCKET, "Key" => $key));
        $html = (string) $obj["Body"];

        if (trim($html) === "") {
            return null;
        }

        return array("html" => $html, "date" => $row["date"], "key" => $key);
    } catch (Throwable $e) {

        error_log("getLatestS3Html(" . $type . ") failed: " . $e->getMessage());

        return null;
    }
}