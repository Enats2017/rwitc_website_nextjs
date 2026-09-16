<?php
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET");

include __DIR__ . '/config/config.php';

// ------------------ SINGLE ARTICLE CASE ------------------
if (isset($_GET['id'])) {

    $id = (int) $_GET['id'];

    $single_sql = "SELECT * FROM `articles` WHERE id = $id AND published='Y' LIMIT 1";
    $single_result = mysqli_query($conn, $single_sql);

    if ($single_result && $single_result->num_rows > 0) {
        $row = $single_result->fetch_assoc();

        echo json_encode([
            "success" => true,
            "data" => array(
                'articles_id'   => $row['id'],
                'article_title' => $row['title'],
                'date_added'    => $row['created'],
                'article_body'  => $row['body'],
            ),
        ]);
    } else {
        echo json_encode([
            "success" => false,
            "message" => "Article not found",
        ]);
    }

    exit;
}


// ------------------ LIST CASE (existing code same rahega) ------------------
if (!isset($_GET['page'])) {
    $page = 1;
} else {
    $page = (int) $_GET['page'];
}

$results_per_page = 20;
$page_first_result = ($page - 1) * $results_per_page;

$show_pagi_sql = "SELECT * FROM articles WHERE published='Y'";
$num_page_data = $conn->query($show_pagi_sql);

$number_of_result = $num_page_data->num_rows;
$number_of_page = ceil($number_of_result / $results_per_page);

$sql = "SELECT * FROM `articles` WHERE published='Y'";
$sql .= " ORDER BY created DESC LIMIT " . $page_first_result . ", " . $results_per_page . "";

$arti_details_datas = mysqli_query($conn, $sql);
$all_assessment = array();

if ($arti_details_datas->num_rows > 0) {
    while ($arti_data = $arti_details_datas->fetch_assoc()) {
        $all_assessment[] = array(
            'articles_id'   => $arti_data['id'],
            'article_title' => $arti_data['title'],
            'date_added'    => $arti_data['created'],
        );
    }
}

echo json_encode([
    "success"      => true,
    "data"         => $all_assessment,
    "current_page" => (int) $page,
    "total_pages"  => (int) $number_of_page,
]);

exit;