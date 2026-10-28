<?php
// Gets Bible text from bible-api.com and gives it to our pages.
// Example: bible.php?ref=John+3:16&t=web
header("Content-Type: application/json; charset=utf-8");
header("Cache-Control: public, max-age=86400");

$ref = isset($_GET["ref"]) ? trim($_GET["ref"]) : "";
$t   = isset($_GET["t"]) ? $_GET["t"] : "web";

// Only normal Bible references are allowed (like "Psalm 23" or "John 3:16-18")
if ($ref === "" || strlen($ref) > 60 || !preg_match('/^[A-Za-z0-9 :,\-]+$/', $ref)) {
    http_response_code(400);
    echo json_encode(["error" => "Bad reference"]);
    exit;
}
$allowed = ["web", "kjv", "webbe", "bbe", "oeb-us", "almeida", "clementine"];
if (!in_array($t, $allowed, true)) { $t = "web"; }

$url = "https://bible-api.com/" . rawurlencode($ref) . "?translation=" . $t;
$body = false;

if (function_exists("curl_init")) {
    $c = curl_init($url);
    curl_setopt($c, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($c, CURLOPT_TIMEOUT, 8);
    curl_setopt($c, CURLOPT_FOLLOWLOCATION, true);
    $body = curl_exec($c);
    curl_close($c);
}
if ($body === false && ini_get("allow_url_fopen")) {
    $ctx = stream_context_create(["http" => ["timeout" => 8, "ignore_errors" => true]]);
    $body = @file_get_contents($url, false, $ctx);
}

if ($body === false || $body === "" || json_decode($body) === null) {
    http_response_code(502);
    echo json_encode(["error" => "Could not reach the Bible site"]);
    exit;
}
echo $body;
