<?php
error_reporting(0);
require_once '../includes/functions.php';

header('Content-Type: application/json');
// 获取 GET 请求的参数
$appId = isset($_REQUEST['id']) ? $_REQUEST['id'] : null;
$platform = isset($_REQUEST['p']) ? $_REQUEST['p'] : null;
$version = isset($_REQUEST['v']) ? $_REQUEST['v'] : null;

$log = [];
$log['appid'] = $appId;
$log['platform'] = $platform;
$log['version'] = $version;
$log['header'] = json_encode($_SERVER);
$log['created_at'] = time();

$log['ip_address'] = $_SERVER['REMOTE_ADDR']; // 用户 IP 地址
$log['user_agent'] = $_SERVER['HTTP_USER_AGENT']; // 用户代理信息
$log['request_uri'] = $_SERVER['REQUEST_URI']; // 请求 URI
$log['host'] = $_SERVER['HTTP_HOST']; // 请求主机
$log['content_type'] = $_SERVER['CONTENT_TYPE']; // 请求内容类型
$log['server_protocol'] = $_SERVER['SERVER_PROTOCOL']; // 服务器协议
$log['https'] = isset($_SERVER['HTTPS']) ? 'on' : 'off'; // 是否 HTTPS

$info = isset($_REQUEST['i']) ? $_REQUEST['i'] : '';
if (!empty($info)) {
    $requestData = json_decode($info, true); // 将 i 转为数组
    $log['info'] = $info;
    $log['device_brand'] = $requestData['deviceBrand'] ?? ''; // 设备品牌
    $log['device_model'] = $requestData['deviceModel'] ?? ''; // 设备型号
    $log['os_version'] = $requestData['osVersion'] ?? ''; // 系统版本
    $log['app_language'] = $requestData['appLanguage'] ?? ''; // 应用语言
    $log['screen_width'] = $requestData['screenWidth'] ?? ''; // 屏幕宽度
    $log['screen_height'] = $requestData['screenHeight'] ?? ''; // 屏幕高度
    $log['status_bar_height'] = $requestData['statusBarHeight'] ?? ''; // 状态栏高度
    $log['safe_area'] = json_encode($requestData['safeArea'] ?? []); // 安全区信息
    $log['uni_version'] = $requestData['uniRuntimeVersion'] ?? ''; // uni-app 运行时版本
}
addLogs($log);

$json = ['upgrade' => false, 'info' => []];
// 验证参数完整性
if (!$appId || !$platform || !$version) {
    exit(json_encode($json));
}
//获取应用最后的版本
$latestVersion = getLatestVersion($appId);
if(!$latestVersion) exit(json_encode($json));

$info = ['platform' => $platform, 'updateContent' => $latestVersion['changelog'], 'force' => !!$latestVersion['force'], 'mainColor' => 'FF5B78'];

if($platform == 'android'){
    $json['upgrade'] = version_compare($version, $latestVersion['version'], '<');
    if($json['upgrade']){
        $info['downUrl'] = $latestVersion['apk_url'];
        $info['version'] = $latestVersion['version'];
    }
}
if($platform == 'ios'){
    $json['upgrade'] = version_compare($version, $latestVersion['ios_version'], '<');
    if($json['upgrade']){
        $info['version'] = $latestVersion['ios_version'];
        if(!empty($latestVersion['ipa_url'])){
           $info['downUrl'] = getScheme() . $_SERVER['HTTP_HOST'] . '/app/' . $appId;
        } elseif(!empty($latestVersion['ios_store_url'])){
           $info['downUrl'] = $latestVersion['ios_store_url'];
        } else {
            $json['upgrade'] = false;
        }
    }
}
$json['info'] = $info;
exit(json_encode($json));