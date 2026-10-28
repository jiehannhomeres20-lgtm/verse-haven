<?php
// Translates verse text.
// Tagalog uses the free MyMemory service.
// Waray-Waray uses Google Translate, because MyMemory does not support Waray-Waray.
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

// Optional: put your email here to raise MyMemory's free daily limit (Tagalog only)
const MYMEMORY_EMAIL = '';
const VERIFY_SSL = true;

function out(int $code, array $body): void {
    http_response_code($code);
    echo json_encode($body, JSON_UNESCAPED_UNICODE);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') out(405, ['error' => 'Method not allowed']);

$in = json_decode(file_get_contents('php://input'), true);
$texts = isset($in['texts']) ? $in['texts'] : null;
$from = isset($in['from']) ? $in['from'] : 'en';
$to = isset($in['to']) ? $in['to'] : 'tl';
if (!is_array($texts) || count($texts) < 1 || count($texts) > 10 || !in_array($from, ['en', 'pt', 'ro', 'la'], true) || !in_array($to, ['tl', 'war'], true)) {
    out(400, ['error' => 'Bad request']);
}
foreach ($texts as $t) {
    if (!is_string($t) || mb_strlen($t) > 2000) out(400, ['error' => 'Bad request']);
}
set_time_limit(60);

function split_text(string $text, int $max = 450): array {
    if (mb_strlen($text) <= $max) return [$text];
    $parts = preg_split('/(?<=[.!?;:])\s+/u', $text);
    $chunks = [];
    $cur = '';
    foreach ($parts as $p) {
        if ($cur !== '' && mb_strlen($cur) + mb_strlen($p) + 1 > $max) {
            $chunks[] = $cur;
            $cur = $p;
        } else {
            $cur = $cur === '' ? $p : $cur . ' ' . $p;
        }
    }
    if ($cur !== '') $chunks[] = $cur;
    return array_map(fn($c) => mb_substr($c, 0, $max), $chunks);
}

function http_get(string $url): string {
    if (function_exists('curl_init')) {
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 15,
            CURLOPT_SSL_VERIFYPEER => VERIFY_SSL,
            CURLOPT_SSL_VERIFYHOST => VERIFY_SSL ? 2 : 0,
            CURLOPT_USERAGENT => 'Mozilla/5.0 (compatible; VerseHaven)',
        ]);
        $body = curl_exec($ch);
        if ($body === false) {
            $msg = curl_error($ch);
            curl_close($ch);
            throw new RuntimeException('Network error: ' . $msg);
        }
        curl_close($ch);
        return $body;
    }
    $ctx = stream_context_create(['http' => [
        'timeout' => 15,
        'ignore_errors' => true,
        'header' => "User-Agent: Mozilla/5.0 (compatible; VerseHaven)\r\n",
    ]]);
    $body = @file_get_contents($url, false, $ctx);
    if ($body === false) throw new RuntimeException('Network error');
    return $body;
}

function mymemory_chunk(string $q, string $from, string $to): string {
    $url = 'https://api.mymemory.translated.net/get?q=' . rawurlencode($q)
         . '&langpair=' . $from . '%7C' . $to
         . (MYMEMORY_EMAIL !== '' ? '&de=' . rawurlencode(MYMEMORY_EMAIL) : '');
    $j = json_decode(http_get($url), true);
    $text = isset($j['responseData']['translatedText']) ? $j['responseData']['translatedText'] : '';
    $status = isset($j['responseStatus']) ? $j['responseStatus'] : 0;
    if (stripos($text, 'MYMEMORY WARNING') !== false || $status == 429) {
        throw new RuntimeException('The free translation limit for today has been reached. Try again tomorrow.');
    }
    if ($status != 200) {
        throw new RuntimeException('The free translation service could not translate into this language.');
    }
    return html_entity_decode($text, ENT_QUOTES | ENT_HTML5, 'UTF-8');
}

function google_chunk(string $q, string $from, string $to): string {
    $url = 'https://translate.googleapis.com/translate_a/single?client=gtx&sl=' . $from
         . '&tl=' . $to . '&dt=t&q=' . rawurlencode($q);
    $j = json_decode(http_get($url), true);
    if (!is_array($j) || !isset($j[0]) || !is_array($j[0])) {
        throw new RuntimeException('The Waray-Waray translator did not answer. Try again in a moment.');
    }
    $result = '';
    foreach ($j[0] as $seg) {
        if (isset($seg[0]) && is_string($seg[0])) $result .= $seg[0];
    }
    if ($result === '') {
        throw new RuntimeException('The Waray-Waray translator returned nothing. Try again in a moment.');
    }
    return $result;
}

function translate_chunk(string $q, string $from, string $to): string {
    return $to === 'war' ? google_chunk($q, $from, $to) : mymemory_chunk($q, $from, $to);
}

try {
    $result = [];
    foreach ($texts as $t) {
        $t = trim((string) $t);
        if ($t === '') { $result[] = ''; continue; }
        $result[] = implode(' ', array_map(fn($c) => translate_chunk($c, $from, $to), split_text($t)));
    }
    out(200, ['translations' => $result]);
} catch (RuntimeException $ex) {
    out(502, ['error' => $ex->getMessage()]);
}