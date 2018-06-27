<?php
// require(dirname(dirname(dirname(dirname(__FILE__)))).'/init.php');
require('lib/DB.php');
require('lib/WHMCS.php');
require('config.php');
// use WHMCS\Database\Capsule;

function getUsers(){
    global $Db;
    $users = $Db->get('user');
    $data = [];
    foreach ($users as $user){
        $user['id'] = $user['pid'];
        $user['v2ray_user'] = [
            "uuid" => $user['v2ray_uuid'],
            "email" => sprintf("%s@v2ray.user", $user['v2ray_uuid']),
            "alter_id" => $user['v2ray_alter_id'],
            "level" => $user['v2ray_level'],
        ];
        array_push($data, $user);
    }
    $res = [
        'msg' => 'ok',
        'data' => $data,
    ];

    echo json_encode($res);
}

function addTraffic(){
    global $Db;
    $rate = $_GET['rate'];
    $input = file_get_contents("php://input");
    //file_put_contents('111.txt', json_encode($input));
    $datas = json_decode($input, true);
    foreach ($datas as $data) {
        $user = $Db->where('pid', $data['user_id'])->getOne('user');
        $fetchData = [
            't' => time(),
            'u' => $user['u'] + ($data['u'] * $rate),
            'd' => $user['d'] + ($data['d'] * $rate),
            'enable' => $user['u'] + $user['d'] <= $user['transfer_enable']?1:0
        ];
        $result = $Db->where('pid', $data['user_id'])->update('user', $fetchData);
    }
    
    $res = [
        "ret" => 1,
        "msg" => "ok",
    ];
    
    echo json_encode($res);
}


function getConfig(){
  $jsonData = file_get_contents('./server.json');
  $jsonData = json_decode($jsonData);
  $jsonData->inbound->port = 443;
  if ($_GET['tls']) {
      $jsonData->inbound->streamSettings->security = "tls";
      $jsonData->inbound->streamSettings->tlsSettings->certificates[0]->certificateFile = "/home/v2ray.crt";
      $jsonData->inbound->streamSettings->tlsSettings->certificates[0]->keyFile = "/home/v2ray.key";
  }
  echo json_encode($jsonData, JSON_UNESCAPED_UNICODE);
}

$databaseName = !empty($_GET['databaseName'])?$_GET['databaseName']:null;
$token = !empty($_GET['token'])?$_GET['token']:null;
$method = !empty($_GET['method'])?$_GET['method']:null;

if(isset($databaseName) && isset($token)){
	$WHMCSdb = new MysqliDb($config['db_hostname'], $config['db_username'], $config['db_password'], $config['db_database'], $config['db_port']);
	$WHMCS = new WHMCS($config['cc_encryption_hash']);
	$server = $WHMCSdb->where('name', $databaseName)->getOne('tblservers');
    if($token !== $server['accesshash']) {
        die('TOKEN ERROR!!');
    }
	$dbhost = $server['ipaddress'] ? $server['ipaddress'] : 'localhost';
	$dbuser = $server['username'];
	$dbpass = $WHMCS->decrypt($server['password']);
	$Db = new MysqliDb($dbhost, $dbuser, $dbpass, $databaseName, 3306);
	switch($_GET['method']) {
	    case 'getUsers': return getUsers();
	    break;
	    case 'addTraffic': return addTraffic();
        break;
        case 'getConfig': return getConfig();
        break;
	}

}else{
	die('Invaild');
}