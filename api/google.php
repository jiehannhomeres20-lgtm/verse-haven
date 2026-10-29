<?php
// Checks with Google that a sign-in is real, and returns the verified Google email and name.
require __DIR__ . '/config.php';
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

function fail(int $code, string $msg): void {
    http_response_code($code);
    echo json_encode(['error' => $msg]);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') fail(405, 'Method not allowed.');
if (strpos(GOOGLE_CLIENT_ID, 'PASTE') === 0) fail(500, 'The Google Client ID is not set yet. Open api/config.php and paste it.');

$in = json_decode(file_get_contents('php://input'), true);
$cred = (is_array($in) && isset($in['credential']) && is_string($in['credential'])) ? $in['credential'] : '';
if ($cred === '' || strlen($cred) > 4000) fail(400, 'The Google sign-in is missing. Try again.');

$url = 'https://oauth2.googleapis.com/tokeninfo?id_token=' . rawurlencode($cred);
$body = false;
if (function_exists('curl_init')) {
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 10,
        CURLOPT_SSL_VERIFYPEER => VERIFY_SSL,
        CURLOPT_SSL_VERIFYHOST => VERIFY_SSL ? 2 : 0,
    ]);
    $body = curl_exec($ch);
    curl_close($ch);
}
if ($body === false && ini_get('allow_url_fopen')) {
    $ctx = stream_context_create(['http' => ['timeout' => 10, 'ignore_errors' => true]]);
    $body = @file_get_contents($url, false, $ctx);
}
if ($body === false || $body === '') fail(502, 'Could not reach Google to check your sign-in. Try again.');

$g = json_decode($body, true);
if (!is_array($g) || empty($g['aud'])) fail(401, 'Google did not accept this sign-in. Try again.');
if ($g['aud'] !== GOOGLE_CLIENT_ID) fail(401, 'This sign-in was made for a different app.');
if (!in_array(isset($g['iss']) ? $g['iss'] : '', ['accounts.google.com', 'https://accounts.google.com'], true)) fail(401, 'This sign-in did not come from Google.');
if ((isset($g['exp']) ? (int) $g['exp'] : 0) < time()) fail(401, 'This sign-in expired. Try again.');
if (!in_array(isset($g['email_verified']) ? $g['email_verified'] : '', ['true', true], true)) fail(401, 'Your Google email is not verified.');

$email = strtolower(isset($g['email']) ? $g['email'] : '');
if ($email === '') fail(401, 'Google did not share your email.');

echo json_encode([
    'ok' => true,
    'email' => $email,
    'name' => isset($g['name']) ? $g['name'] : '',
    'sub' => isset($g['sub']) ? $g['sub'] : '',
]);
