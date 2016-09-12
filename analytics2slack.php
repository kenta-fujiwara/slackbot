<?php

include_once __DIR__.'/SlackBot.php';
include_once __DIR__.'/SlackBotInfo.php';

// ライブラリの読み込み
require_once 'google-api-php-client/src/Google/autoload.php';

// サービスアカウントのメールアドレス
$service_account_email = 'analytics-bot@analytics-bot-143204.iam.gserviceaccount.com';

// 秘密キーファイルの読み込み
$key = file_get_contents('analytics-bot-e105379956cc.p12');

// プロファイル(ビュー)ID
$profile = '129347988';

// Googleクライアントのインスタンスを作成
$client = new Google_Client();
$analytics = new Google_Service_Analytics($client);

// クレデンシャルの作成
$cred = new Google_Auth_AssertionCredentials(
    $service_account_email,
    array(Google_Service_Analytics::ANALYTICS_READONLY),
    $key
);
$client->setAssertionCredentials($cred);
if($client->getAuth()->isAccessTokenExpired()) {
  $client->getAuth()->refreshTokenWithAssertion($cred);
}

function getWeeklyReport($analytics, $profile){
  // 7日前から昨日までのセッション数・PV・平均閲覧ページ数・平均セッション時間・直帰率を取得
  $results_this_week = $analytics->data_ga->get(
    'ga:' . $profile,
    '7daysAgo',
    'yesterday',
    'ga:sessions,ga:pageviews,ga:pageviewsPerSession,ga:avgSessionDuration,ga:bounceRate'
  );

  // 14日前から8日前までのセッション数・PV・平均閲覧ページ数・平均セッション時間・直帰率を取得
  $results_last_week = $analytics->data_ga->get(
    'ga:' . $profile,
    '14daysAgo',
    '8daysAgo',
    'ga:sessions,ga:pageviews,ga:pageviewsPerSession,ga:avgSessionDuration,ga:bounceRate'
  );

  // 取得したデータから必要な部分を抽出
  $this_week_data = $results_this_week->rows;
  $last_week_data = $results_last_week->rows;

  // 7日前と昨日の日付を取得
  $start = date('n/d', strtotime('-1 week'));
  $end   = date('n/d', strtotime('-1 day'));

  // 先週と今週のレポートを比較して増減を計算する関数
  function calcReport($this, $last){
    $result = round( $this - $last , 1);
    if($result > 0){
      return ' (+' . $result . ') ';
    } else {
      return ' (' . $result . ') ';
    }
  }

  // データを整形
  $report = $start . '〜' . $end . 'のレポート' . "\n";
  $report .= '訪問数 : ' . $this_week_data[0][0] . calcReport( $this_week_data[0][0], $last_week_data[0][0] ) . "\n";
  $report .= '合計PV : ' . $this_week_data[0][1] . calcReport( $this_week_data[0][1], $last_week_data[0][1] ) . "\n";
  $report .= '平均閲覧ページ数 : ' . round( $this_week_data[0][2], 2 ) . calcReport( $this_week_data[0][2], $last_week_data[0][2] ) . "\n";
  $report .= '平均滞在時間 : ' . ceil( $this_week_data[0][3] ) . '秒' . calcReport( $this_week_data[0][3], $last_week_data[0][3] ) . "\n";
  $report .= '直帰率 : ' . round( $this_week_data[0][4], 1 ) . '%' . calcReport( $this_week_data[0][4], $last_week_data[0][4] ) .  "\n";

  return $report;
}

$report = getWeeklyReport($analytics, $profile);

function getWeeklyRanking($analytics, $profile){
  $results = $analytics->data_ga->get(
    'ga:' . $profile,
    '7daysAgo',
    'yesterday',
    'ga:pageviews',
    array(
      'dimensions'  => 'ga:pageTitle',  // データの区切り
      'sort'        => '-ga:pageviews', // ページビューでソート
      'max-results' => '10',            // 取得件数
    )
  );

  // 取得したデータから必要な部分を抽出
  $data = $results->rows;

  // 7日前と昨日の日付を取得
  $start = date('n/d', strtotime('-1 week'));
  $end   = date('n/d', strtotime('-1 day'));

  // 配列で取得したデータをループで回してランキングに
  $ranking = $start . '〜' . $end . 'の記事ランキング' . "\n";
  foreach ($data as $key => $row) {
    $ranking .= ($key + 1) . '.' . $row[0] . ' ' . $row[1] . 'PV' . "\n";
  }

  return $ranking;
}

$ranking = getWeeklyRanking($analytics, $profile);

$message = $report . "\n" . $ranking;

// ポスト先ルームのURL
$url = 'https://hooks.slack.com/services/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';
// メッセージをポスト
$bot = new SlackBot();
print_r($bot->post_message(new SlackBotInfo($url, $message)));
