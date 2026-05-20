<?php
/**
 * HeaSec天积安全团队 - 无回显命令注入靶场 - 第一关处理接口
 * 版本: v1.0.0
 * 创建日期: 2026-04-21
 * 团队: 天积安全 (HeavenlySecret)
 */

header('Content-Type: application/json; charset=utf-8');
header('X-HeavenlySecret: HeaSec BlindRce Range v1.0.0');

require_once dirname(__DIR__) . '/includes/functions.php';

// 将工作目录切换到靶场根目录
chdir(dirname(__DIR__));

// 接收用户输入的IP地址
$ip = $_POST['ip'] ?? '';

if ($ip === '') {
    sendJsonResponse(false, '请输入IP地址或域名');
}

// 根据操作系统构造ping命令
$isWindows = strtoupper(substr(PHP_OS, 0, 3)) === 'WIN';
$pingCmd = $isWindows ? "ping -n 2 " : "ping -c 2 ";

// 执行网络连通性检测命令
$command = $pingCmd . $ip;
exec($command, $output, $returnCode);

// 根据返回码判断目标是否可达
$reachable = ($returnCode === 0);

sendJsonResponse(true, $reachable ? '目标可达' : '目标不可达', [
    'reachable' => $reachable,
    'ip' => $ip
]);
