<?php
// Simple IP logger / viewer demo
// Usage:
//   - Save IP:   ip-demo.php?k=some-key
//   - Show IP:   ip-demo.php?mostrar=1&k=some-key

$file_with_ips = __DIR__ . '/ip.json';

// Ensure the file exists with an empty JSON object
if (!file_exists($file_with_ips)) {
    file_put_contents($file_with_ips, json_encode(new stdClass(), JSON_PRETTY_PRINT));
}

$raw = file_get_contents($file_with_ips);
$contenido = json_decode($raw, true);
if (!is_array($contenido)) {
    $contenido = [];
}

$key = $_GET['k'] ?? null;
if ($key === null || $key === '') {
    http_response_code(400);
    echo '<p>Please supply a key with the <code>k</code> query string (e.g. <code>?k=example</code>).</p>';
    exit;
}

if (isset($_GET['mostrar'])) {
    $ip = $contenido[$key] ?? null;
    if ($ip === null) {
        echo "<p>No IP stored for key <b>" . htmlspecialchars($key, ENT_QUOTES, 'UTF-8') . "</b>.</p>";
    } else {
        echo "<p>IP for <b>" . htmlspecialchars($key, ENT_QUOTES, 'UTF-8') . "</b>: <b>" . htmlspecialchars($ip, ENT_QUOTES, 'UTF-8') . "</b></p>";
    }
} else {
    $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    $contenido[$key] = $ip;
    file_put_contents($file_with_ips, json_encode($contenido, JSON_PRETTY_PRINT));

    echo "<p>Stored IP <b>" . htmlspecialchars($ip, ENT_QUOTES, 'UTF-8') . "</b> for key <b>" . htmlspecialchars($key, ENT_QUOTES, 'UTF-8') . "</b>.</p>";
    echo '<p>View it later with <code>?mostrar=1&amp;k=' . htmlspecialchars(urlencode($key), ENT_QUOTES, 'UTF-8') . '</code>.</p>';
}
?>
