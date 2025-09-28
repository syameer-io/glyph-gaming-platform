<?php
// Get your real Telegram chat ID
$token = '7813820750:AAHPgGBsiLL2994KqYy0ovdpkrdapiN8feU';

// Get recent updates
$url = "https://api.telegram.org/bot{$token}/getUpdates?limit=10";
$response = file_get_contents($url);
$data = json_decode($response, true);

echo "Recent Telegram Updates:\n";
echo "========================\n\n";

if (isset($data['result']) && count($data['result']) > 0) {
    foreach ($data['result'] as $update) {
        if (isset($update['message'])) {
            $message = $update['message'];
            echo "Chat ID: " . $message['chat']['id'] . "\n";
            echo "From: " . $message['from']['first_name'] . "\n";
            echo "Text: " . ($message['text'] ?? 'N/A') . "\n";
            echo "Date: " . date('Y-m-d H:i:s', $message['date']) . "\n";
            echo "---\n";
        }
    }
} else {
    echo "No recent messages found.\n";
    echo "Send a message to your bot first, then run this script again.\n";
}