<?php
$botsAdminID    = '218751234';   // Put your Telegram ID here.
$notifierBotKey = '447753866:AAESiIUaz1287IsyGjcILT1fubTokjgRYIA'; // Put your notifier bot API Key here.

$botsList = [
    'botOne' => '447753866:AAESiIUaz1287IsyGjcILT1fubTokjgRYIA', // Name (to show in messages) and API KEY for first bot.
];

$botsDown = [];
foreach ($botsList as $botUsername => $apiKey) {
    $botErrorMessage = sprintf('🆘 @%s: Bot status inaccessible', $botUsername);

    $chWI = curl_init('https://api.telegram.org/bot' . $apiKey . '/getWebhookInfo');
    curl_setopt($chWI, CURLOPT_RETURNTRANSFER, true);
    $response = curl_exec($chWI);
    curl_close($chWI);

    if ($status = json_decode($response, true)) {
        if (isset($status['ok']) && $status['ok']) {
            $result = $status['result'];

            // If there are less than 5 pending updates, bot counts as up and active.
            if (isset($result['pending_update_count']) &&
                $result['pending_update_count'] < 5
            ) {
                continue;
            }

            $botErrorMessage = sprintf(
                '🆘 @%s: %d pending updates;' . PHP_EOL . '%s: %s',
                $botUsername,
                $result['pending_update_count'],
                date('Y-m-d H:i:s', $result['last_error_date']),
                $result['last_error_message']
            );
        } else {
            $botErrorMessage = sprintf(
                '🆘 @%s: (%d) %s',
                $botUsername,
                $status['error_code'],
                $status['description']
            );
        }
    }

    $botsDown[$botUsername] = $botErrorMessage;
}

if (empty($botsDown)) {
    exit;
}

// Send message to notifier chat.
$chSM = curl_init('https://api.telegram.org/bot' . $notifierBotKey . '/sendMessage');
curl_setopt($chSM, CURLOPT_RETURNTRANSFER, true);
curl_setopt($chSM, CURLOPT_POST, 1);
curl_setopt($chSM, CURLOPT_POSTFIELDS, http_build_query([
    'chat_id' => $botsAdminID,
    'text'    => implode(PHP_EOL . PHP_EOL, $botsDown),
]));

curl_exec($chSM);
curl_close($chSM);