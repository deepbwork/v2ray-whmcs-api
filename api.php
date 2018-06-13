<?php
error_reporting(E_ALL^E_NOTICE);
//require(dirname(dirname(__FILE__)).'/init.php');
require('lib/DB.php');
require('lib/WHMCS.php');
require('config.php');
//use WHMCS\Database\Capsule;
header("Access-Control-Allow-Origin: *");
function echoJson($code, $data = '', $msg = '') {
    $arr = [
        'ret' => $code,
        'data' => $data,
        'msg' => $msg
    ];
    exit(json_encode($arr));
}


function verifyToken() {
    global $Db;
    $token = !empty($_GET['token'])?$_GET['token']:null;
    if (empty($token)) {
        return echoJson(0, '', '令牌为空');
    }
    $user = $Db->where('uuid', $token)->getOne('tblclients');
    //$user = Capsule::table('tblclients')->where('securityqans', $token)->first();
    if (empty($user)) {
        return echoJson(0, '', '令牌过期，请尝试重新登录');
    }
    return $user;
}

function recordLog(){
    global $config;
    file_put_contents('/tmp/'.$config['cc_encryption_hash'].'.log', date('[Y-m-d H:i:s]').urldecode($_POST['log'])."\r\n", FILE_APPEND);
}

function getNodes($str, $serverId) {
    global $Db;
    if (empty($str)) {
        $str = $Db->where('sid', $serverId)->getOne('v2ray_setting')['node'];
    }
    $nodes = [];
    $tempA = explode(PHP_EOL, $str);
    for($i=0;$i<count($tempA);$i++) {
        $tempB = explode('|', $tempA[$i]);
        $nodes[$i] = [
            'id' => $i,
            'name' => $tempB[0],
            'server' => $tempB[1],
            'port' => $tempB[2],
            'sec' => $tempB[3],
            'remark' => $tempB[4]
        ];
    }
    return $nodes;
}


function auth(){
    global $Db;
    $username = !empty($_POST['username'])?$_POST['username']:null;
    $password = !empty($_POST['password'])?$_POST['password']:null;
    if (empty($username)) {
        return echoJson(0, '', '用户名密码错误');
    }
    $user = $Db->where('email', $username)->getOne('tblclients');
    if (!password_verify($password, $user['password'])) {
        return echoJson(0, '', '用户名密码错误');
    }
    return echoJson(1, $user['uuid'], '欢迎回来');
}

function getHosting(){
    global $Db;
    $user = verifyToken();
    $hosting = $Db->where('userid', $user['id'])
        ->where('domainstatus', 'Active')
        ->get('tblhosting');
    $product = $Db->get('tblproducts');
    $arr = [];
    // $hosting = Capsule::table('tblhosting')->where('userid', $user->id)->get();
    // $product = Capsule::table('tblproducts')->get();
    if ($product && $hosting) {
        for($i=0;$i<count($hosting);$i++) {
            for($n=0;$n<=count($product);$n++) {
                if ($hosting[$i]['packageid'] == $product[$n]['id']) {
                    if($product[$n]['servertype'] == 'v2ray') {
                        array_push($arr, [
                          'packageId' => $hosting[$i]['id'],
                          'name' => $product[$n]['name'],
                          'serverId' => $hosting[$i]['server'],
                          'expireDate' => $hosting[$i]['nextduedate'],
                          'node' => getNodes($product[$n]['configoption7'], $hosting[$i]['server'])
                        ]);
                    }
                }
            }
        }
    }
    return echoJson(1, $arr, '');
}

function getConfig(){
    global $Db,$WHMCS;
    verifyToken();
    // $_GET['node'] = 'eyJpZCI6MCwibmFtZSI6ImFhYSIsInNlcnZlciI6ImFhYS5jb20iLCJwb3J0IjoiYWFhIiwic2VjIjoibm9uZSIsInJlbWFyayI6bnVsbH0=';
    $nodeData = !empty($_GET['node'])?$_GET['node']:null;
    $node = json_decode(urldecode(base64_decode($nodeData)));
    $serverId = !empty($_GET['serverId'])?$_GET['serverId']:null;
    $packageId = !empty($_GET['packageId'])?$_GET['packageId']:null;
    //get server
    $server = $Db->where('id', $serverId)->getOne('tblservers');
    // $server = Capsule::table('tblservers')->where('id', $serverId)->first();
  $tempdb = new MysqliDb($server['ipaddress'], $server['username'], $WHMCS->decrypt($server['password']), $server['name'], 3306);
  //组装数据
  $v2ray = $tempdb->where('pid', $packageId)->getOne('user');
  $jsonData = file_get_contents('./client.json');
  $jsonData = json_decode($jsonData, true);
  //socks
  $jsonData['inbound']['port'] = 31211;
  //http
  $jsonData['inboundDetour'][0]['port'] = 31210;
  //other
  $jsonData['outbound']['settings']['vnext'][0]['address'] = (string)$node->server;
  $jsonData['outbound']['settings']['vnext'][0]['port'] = (int)$node->port;
  $jsonData['outbound']['settings']['vnext'][0]['users'][0]['id'] = (string)$v2ray['v2ray_uuid'];
  $jsonData['outbound']['settings']['vnext'][0]['users'][0]['alterId'] = (int)$v2ray['v2ray_alter_id'];
  $jsonData['outbound']['settings']['vnext'][0]['remark'] = (string)$node->name;
  echo json_encode($jsonData);
}

function getUserInfo(){
    global $Db,$WHMCS;
    verifyToken();
    $serverId = !empty($_GET['serverId'])?$_GET['serverId']:null;
    $packageId = !empty($_GET['packageId'])?$_GET['packageId']:null;
    //get server
    // $server = Capsule::table('tblservers')->where('id', $serverId)->first();
    $server = $Db->where('id', $serverId)->getOne('tblservers');
  $tempdb = new MysqliDb($server['ipaddress'], $server['username'], $WHMCS->decrypt($server['password']), $server['name'], 3306);
  //组装数据
  $v2ray = $tempdb->where('pid', $packageId)->getOne('user');
  return echoJson(1, $v2ray, '');
}

function getInit(){
    global $config;
    $config = [
        //背景色
        'background' => $config['app_background'],
        'title' => $config['app_title'],
        'website' => $config['app_website']
    ];
    return echoJson(1, $config, '');
}

$service = !empty($_GET['s'])?$_GET['s']:null;

if(isset($service)){
	$Db = new MysqliDb($config['db_hostname'], $config['db_username'], $config['db_password'], $config['db_database'], $config['db_port']);
	//$server = Capsule::table('tblservers')->where('name', $databaseName)->first();
	$WHMCS = new WHMCS($config['cc_encryption_hash']);
	switch($service) {
	    case 'user.auth': return auth();
	    break;
	    case 'whmcs.hosting': return getHosting();
	    break;
	    case 'v2ray.config': return getConfig();
	    break;
	    case 'v2ray.userInfo' : return getUserInfo();
	    break;
      case 'app.init' : return getInit();
      break;
      case 'app.log' : return recordLog();
      break;
	}

}else{
	return echoJson(0, '', 'service error');
}