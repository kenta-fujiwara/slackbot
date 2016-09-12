<?php

include_once __DIR__.'/SlackBot.php';
include_once __DIR__.'/SlackBotInfo.php';

// 引数チェック
if ($argc < 2) {
    exit('引数にポストしたいメッセージを指定してください');
}
$message = $argv[1];

// ポスト先ルームのURL
$url = 'https://hooks.slack.com/services/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';
// メッセージをポスト
$bot = new SlackBot();
print_r($bot->post_message(new SlackBotInfo($url, $message)));
