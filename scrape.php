<?php

$localhost = 'localhost';
$username = 'root';
$password = '';
$database = 'encircle_marketing';

// database connection
$conn = mysqli_connect($localhost, $username, $password, $database);

// Check if the database connection failed and stop execution with an error message
if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}

// Set your target URL here
$url = "https://www.oponeo.co.uk/tyre-finder/t=1/car/r=1/205-55-r16";

$maxRecords = 20;
$count = 0;
$tyreData = [];

// Fetch and parse the target page
$ch = curl_init($url);
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_FOLLOWLOCATION => true,
    CURLOPT_USERAGENT => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64)',
]);
$response = curl_exec($ch);

// If the cURL request fails, stop the script and display the error message
if ($response === false) {
    die("Failed to fetch URL: " . curl_error($ch));
}

curl_close($ch);

// Ethical delay: wait 1–3 seconds after each request
sleep(rand(1, 3));

// Suppress HTML parsing warnings and load the HTML response into DOM for XPath querying
libxml_use_internal_errors(true);
$dom = new DOMDocument();
@$dom->loadHTML($response);
$xpath = new DOMXPath($dom);

// Select all product elements from the HTML using XPath (divs with class containing 'product')
$productNodes = $xpath->query('//div[contains(@class, "product")]');

foreach ($productNodes as $node) {
    if ($count >= $maxRecords) break;

    // Extract the raw JSON string from the 'data-layer' attribute of the product node
    $jsonRaw = $node->getAttribute('data-layer');

    // Decode the JSON string (after converting HTML entities) into a PHP associative array
    $data = json_decode(html_entity_decode($jsonRaw), true);

    // skip if JSON is invalid
    if (!$data) continue;

    $brand = $data['item_brand'] ?? 'N/A';
    $description = $data['item_name'] ?? 'N/A';
    $price = isset($data['price']) ? floatval($data['price']) : 'N/A';
    $rating = 'N/A';

    // Strip brand from description and split into pattern + size
    if ($brand !== 'N/A' && $description !== 'N/A') {
        if (stripos($description, $brand) === 0) {
            $titleWithoutBrand = trim(substr($description, strlen($brand)));
        } else {
            $titleWithoutBrand = $description;
        }

        if (preg_match('/^(.*?)\s+(\d{3}\/\d{2}\s*R\d{2}.*)$/i', $titleWithoutBrand, $matches)) {
            $tyrePattern = $matches[1];
            $tyreSize = $matches[2];
        } else {
            $tyrePattern = 'N/A';
            $tyreSize = 'N/A';
        }
    } else {
        $tyrePattern = 'N/A';
        $tyreSize = 'N/A';
    }

    // Grab rating from DOM
    $ratingNode = $xpath->query('.//div[contains(@class, "note")]', $node)->item(0);
    if ($ratingNode) {
        $rating = trim($ratingNode->nodeValue);
    }

    // Convert rating to float if available, or leave as 'N/A'
    $rating = $rating !== 'N/A' ? floatval($rating) : 'N/A';

    // Skip the current product if any key value is missing or marked as 'N/A'
    if (in_array('N/A', [$brand, $tyrePattern, $tyreSize, $price, $rating])) {
        continue;
    }

    $exists = 0;

    // Check if the tyre already exists in the database to prevent duplicate entries
    $checkQuery = "SELECT COUNT(*) FROM tyres WHERE brand = ? AND pattern = ? AND size = ?";
    $checkStmt = mysqli_prepare($conn, $checkQuery);
    mysqli_stmt_bind_param($checkStmt, "sss", $brand, $tyrePattern, $tyreSize);
    mysqli_stmt_execute($checkStmt);
    mysqli_stmt_bind_result($checkStmt, $exists);
    mysqli_stmt_fetch($checkStmt);
    mysqli_stmt_close($checkStmt);

    $website = 'www.oponeo.co.uk';

    // If the tyre does not exist, insert it into the database
    if ($exists == 0) {
        $sql = "INSERT INTO tyres (website,brand, pattern, size, price, rating) VALUES (?,?, ?, ?, ?, ?)";
        $stmt = mysqli_prepare($conn, $sql);
        if (!$stmt) {
            die("Prepare failed: " . mysqli_error($conn));
        }
        mysqli_stmt_bind_param($stmt, "ssssdd",$website ,$brand, $tyrePattern, $tyreSize, $price, $rating);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);
    }

    $tyreData[] = [
        'website' => 'www.oponeo.co.uk',
        'brand'   => $brand,
        'pattern' => $tyrePattern,
        'size'    => $tyreSize,
        'price'   => $price,
        'rating'  => $rating,
    ];

    $count++;
}

// Export CSV
$filename = 'tyres_export.csv';
$fp = fopen($filename, 'w');

if ($fp) {
    fputcsv($fp, ['Website','Brand', 'Tyre Pattern', 'Tyre Size', 'Price (£)', 'Rating']);
    foreach ($tyreData as $row) {
        fputcsv($fp, [$row['website'],$row['brand'], $row['pattern'], $row['size'], $row['price'], $row['rating']]);
    }
    fclose($fp);
    echo "<p>CSV file exported as <strong>$filename</strong></p>";
} else {
    echo "<p>Failed to create CSV file.</p>";
}

mysqli_close($conn);