<?php
chdir(__DIR__);
require_once 'UnityBundle.php';
require_once 'UnityAsset.php';
if (!file_exists('last_version')) {
  $last_version = array('TruthVersion'=>0,'hash'=>'');
} else {
  $last_version = json_decode(file_get_contents('last_version'), true);
}
$logFile = fopen('redive.log', 'a');
function _log($s) {
  global $logFile;
  fwrite($logFile, date('[m/d H:i] ').$s."\n");
  //echo $s."\n";
}
function execQuery($db, $query) {
  $returnVal = [];
  /*if ($stmt = $db->prepare($query)) {
    $result = $stmt->execute();
    if ($result->numColumns()) {
      $returnVal = $result->fetchArray(SQLITE3_ASSOC);
    }
  }*/
  $result = $db->query($query);
  $returnVal = $result->fetchAll(PDO::FETCH_ASSOC);
  return $returnVal;
}

function encodeValue($value) {
  $arr = [];
  foreach ($value as $key=>$val) {
    $arr[] = '/*'.$key.'*/' . (is_numeric($val) ? $val : ('"'.str_replace('"','\\"',$val).'"'));
  }
  return implode(", ", $arr);
}

function main() {

global $last_version;
chdir(__DIR__);

//check app ver at 00:00
$appver = file_exists('appver') ? file_get_contents('appver') : '1.1.4';
$itunesid = 1134429300;
$curl = curl_init();
curl_setopt_array($curl, array(
  CURLOPT_URL=>'https://itunes.apple.com/lookup?id='.$itunesid.'&lang=ja_jp&country=jp',
  CURLOPT_USERAGENT=>'Mozilla/5.0 (Windows NT 6.1; Win64; x64; rv:51.0) Gecko/20100101 Firefox/59.0',
  CURLOPT_HEADER=>0,
  CURLOPT_RETURNTRANSFER=>1,
  CURLOPT_SSL_VERIFYPEER=>false
));
$appinfo = curl_exec($curl);
curl_close($curl);
if ($appinfo !== false) {
  $appinfo = json_decode($appinfo, true);
  if (!empty($appinfo['results'][0]['version'])) {
    $prevappver = $appver;
    $appver = $appinfo['results'][0]['version'];

    if (version_compare($prevappver,$appver, '<')) {
      file_put_contents('appver', $appver);
      _log('new game version: '. $appver);
      $data = json_encode(array(
        'game'=>'redive',
        'ver'=>$appver,
        'link'=>'https://itunes.apple.com/jp/app/id'.$itunesid
      ));
      $header = [
        'X-GITHUB-EVENT: app_update',
        'X-HUB-SIGNATURE: sha1='.hash_hmac('sha1', $data, 'sec', false)
      ];
      $curl = curl_init();
      curl_setopt_array($curl, array(
        CURLOPT_URL=>'https://redive.estertion.win/masterdb_subscription/webhook.php',
        CURLOPT_HEADER=>0,
        CURLOPT_RETURNTRANSFER=>1,
        CURLOPT_SSL_VERIFYPEER=>false,
        CURLOPT_HTTPHEADER=>$header,
        CURLOPT_POST=>1,
        CURLOPT_POSTFIELDS=>$data
      ));
      curl_exec($curl);
      curl_close($curl);
    }
  }
}

//check TruthVersion
$game_start_header = [
  'Host: app.priconne-redive.jp',
  'User-Agent: princessconnectredive/12 CFNetwork/758.4.3 Darwin/15.5.0',
  'PARAM: 7db6304d4d0be697a1e37b14883a9bc7848c5665',
  'REGION_CODE: ',
  'BATTLE_LOGIC_VERSION: 1',
  'PLATFORM_OS_VERSION: iPhone OS 9.3.2',
  'Proxy-Connection: keep-alive',
  'DEVICE_ID: 06D993AF-FE6D-4778-8553-085FAF6707CE',
  'KEYCHAIN: 577247511',
  'GRAPHICS_DEVICE_NAME: Apple A9 GPU',
  'SHORT_UDID: 000961@277B487>163;656<178>844@861C276>212744265538282151286561554573842',
  'DEVICE_NAME: iPhone8,4',
  'BUNDLE_VER: ',
  'LOCALE: Jpn',
  'IP_ADDRESS: 192.168.1.112',
  'SID: 956c729eb8eb60d80239ea6c662ebd9b',
  'Content-Length: 176',
  'X-Unity-Version: 2017.1.2p2',
  'PLATFORM: 1',
  'Connection: keep-alive',
  'Accept-Language: en-us',
  'APP_VER: '.$appver,
  'RES_VER: 10000000',
  'Accept: */*',
  'Content-Type: application/x-www-form-urlencoded',
  'Accept-Encoding: gzip, deflate',
  'DEVICE: 1'
];
global $curl;
$curl = curl_init();
curl_setopt_array($curl, array(
  CURLOPT_URL => 'https://app.priconne-redive.jp/check/game_start',
  CURLOPT_HTTPHEADER=>$game_start_header,
  CURLOPT_HEADER=>0,
  CURLOPT_RETURNTRANSFER=>1,
  CURLOPT_SSL_VERIFYPEER=>false,
  CURLOPT_POST=>true,
  CURLOPT_POSTFIELDS=>base64_decode('Fh28009IuC3baOl9zp5LX7v/MuF7Ye2SI7fPSKlU84ru+bTFQPoEEoUnHBbZn4tf/gD6bCI+GD6opqtyeuAKq5Yile53RJYRU5ERCk6UpHWDmts6K8Z+vt5+3yb9sCU9EedYA2xnOoltbNjffQ1bTVBCErKRlDoo3agsFuCWF2AZEn3plfN7UpR7udogIePAWkRFeVpUWXlaakV5TldGbVltVXlOR1poWXpka05HUXg='),
));
$response = curl_exec($curl);
curl_close($curl);
if ($response === false) {
  _log('error fetching TruthVersion');
  return;
}
//$response = 'RerDjP3EYxdZtDcDm24Ni5WJLz/mnmKHltcuvXd8wUPHpVgkz7h8eNxSs25yL+xckTnO5EwR/YdCFu/jQ0tspFDhep7GI1hw1zPxX5AIzQnxS1uayolDzl9nfHZtJR28uj043NMdQ9Noqr0TNbbe0MUu66gYUFTzjTvboGf5l7nt/QwXR6hY3tzo67aMTpETbf0ZCi3urhOnEQlJlBMhjU2gtl6Ws2J7+wkTlNswpN2fn+d99xFuIdNln0J0jNRa/Ku/f2ix18wMiKA34ATWXUj5WBHcg6rZjbDrr7xp2QUbU4W3t62nRt7xR0klFxblxD5u4vmTZv5eYXHKlCgbMTM0YWVkYmQ3NzBkOTcyZDZiOTVhZTA0OGE5MjYyZGY2';
$response = base64_decode($response);
$key = substr($response, -32, 32);
$udid = 'b94fb285-fdda-4136-b66c-e694f141cdab';
$udid = '662dcf1f-cf2e-4560-a325-f550a1c01954';
$iv = substr(str_replace('-','',$udid),0,16);
$response = msgpack_unpack(decrypt(substr($response, 0, -32), $key, $iv));

//print_r($response);
//exit;
if (!isset($response['data_headers']['required_res_ver'])) {
  _log('invalid response: '. json_encode($response));
  return;
}
$TruthVersion = $response['data_headers']['required_res_ver'];

if ($TruthVersion == $last_version['TruthVersion']) {
  _log('no update found');
  return;
}
$last_version['TruthVersion'] = $TruthVersion;
_log("TruthVersion: ${TruthVersion}");
file_put_contents('data/!TruthVersion.txt', $TruthVersion."\n");

//$TruthVersion = '10000000';
$curl = curl_init();
curl_setopt_array($curl, array(
  CURLOPT_URL=>'http://priconne-redive.akamaized.net/dl/Resources/'.$TruthVersion.'/Jpn/AssetBundles/iOS/manifest/masterdata_assetmanifest',
  CURLOPT_RETURNTRANSFER=>true,
  CURLOPT_HEADER=>0,
  CURLOPT_SSL_VERIFYPEER=>false
));
//$manifest = file_get_contents('history/'.$TruthVersion);
$manifest = curl_exec($curl);
$manifest = explode(',', $manifest);
$bundleHash = $manifest[1];
$bundleSize = $manifest[3]|0;
if ($last_version['hash'] == $bundleHash) {
  _log("Same hash as last version ${bundleHash}");
  file_put_contents('last_version', json_encode($last_version));
  chdir('data');
  exec('git add !TruthVersion.txt');
  exec('git commit -m '.$TruthVersion);
  exec('git push origin master');
  return;
}
$last_version['hash'] = $bundleHash;
//download bundle
_log("downloading bundle for TruthVersion ${TruthVersion}, hash: ${bundleHash}, size: ${bundleSize}");
$bundleFileName = "master_${TruthVersion}.unity3d";
curl_setopt_array($curl, array(
  CURLOPT_URL=>'http://priconne-redive.akamaized.net/dl/pool/AssetBundles/'.substr($bundleHash,0,2).'/'.$bundleHash,
  CURLOPT_RETURNTRANSFER=>true
));
$bundle = curl_exec($curl);
//curl_close($curl);
$downloadedSize = strlen($bundle);
$downloadedHash = md5($bundle);
if ($downloadedSize != $bundleSize || $downloadedHash != $bundleHash) {
  _log("download failed, received hash: ${downloadedHash}, received size: ${downloadedSize}");
  return;
}

//extract db
_log('extracting bundle');
$bundle = new MemoryStream($bundle);
$assetsList = extractBundle($bundle);
unset($bundle);

$assetsFile = new FileStream($assetsList[0]);
$tableSize = $assetsFile->readInt32();
$dataEnd = $assetsFile->readInt32();
$fileGen = $assetsFile->readInt32();
$dataOffset = $assetsFile->readInt32();

$assetsFile->seek($dataOffset + 16);
file_put_contents('redive.db', $assetsFile->readData($dataEnd - $dataOffset - 16));
unset($assetsFile);
unlink($assetsList[0]);

//dump sql
_log('dumping sql');
$db = new PDO('sqlite:redive.db');

$tables = execQuery($db, 'SELECT * FROM sqlite_master');

foreach (glob('data/*.sql') as $file) {unlink($file);}

foreach ($tables as $entry) {
  if ($entry['type'] == 'table') {
    $tblName = $entry['name'];
    $f = fopen("data/${tblName}.sql", 'w');
    fwrite($f, $entry['sql'].";\n");
    $values = execQuery($db, "SELECT * FROM ${tblName}");
    foreach($values as $value) {
      fwrite($f, "INSERT INTO `${tblName}` VALUES (".encodeValue($value).");\n");
    }
    fclose($f);
  } else if ($entry['type'] == 'index') {
    file_put_contents("data/${tblName}.sql", $entry['sql'], FILE_APPEND);
  }
}
$name = [];
foreach(execQuery($db, 'SELECT unit_id,unit_name FROM unit_data WHERE unit_id > 100000 AND unit_id < 200000') as $row) {
  $name[$row['unit_id']+30] = $row['unit_name'];
}
file_put_contents(RESOURCE_PATH_PREFIX.'card/full/index.json', json_encode($name, JSON_UNESCAPED_SLASHES+JSON_UNESCAPED_UNICODE));
unset($name);
unset($db);
file_put_contents('last_version', json_encode($last_version));

chdir('data');
exec('git add *.sql !TruthVersion.txt');
exec('git commit -m '.$TruthVersion);
exec('git push origin master');


checkAndUpdateResource($TruthVersion);

}

/*foreach(glob('history/100*') as $ver) {
  main(substr($ver, 8));
}*/
main();