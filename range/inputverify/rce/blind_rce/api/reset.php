<?php
/**
 * HeaSec天积安全团队 - 无回显命令注入靶场 - 重置接口
 * 版本: v1.0.0
 * 创建日期: 2026-04-21
 * 团队: 天积安全 (HeavenlySecret)
 */

header('Content-Type: application/json; charset=utf-8');
header('X-HeavenlySecret: HeaSec BlindRce Range v1.0.0');

require_once dirname(__DIR__) . '/includes/functions.php';

$basePath = dirname(__DIR__);

// 删除第一关配置文件
$configFiles = [
    $basePath . '/config/database.php',
    $basePath . '/config/level2/target.txt',
    $basePath . '/config/level2/passcode.php',
    $basePath . '/config/level3/passcode.php'
];

foreach ($configFiles as $file) {
    if (file_exists($file)) {
        unlink($file);
    }
}

// 清理用户可能写入的文件
$userFiles = [
    $basePath . '/execution/attack.txt',
    $basePath . '/webshell.php',
    $basePath . '/tmp.b64'
];

foreach ($userFiles as $file) {
    if (file_exists($file)) {
        unlink($file);
    }
}

// 清理 execution 目录及其内容
$execDir = $basePath . '/execution';
if (is_dir($execDir)) {
    $files = glob($execDir . '/*');
    foreach ($files as $f) {
        is_file($f) && unlink($f);
    }
    rmdir($execDir);
}

sendJsonResponse(true, '重置成功');
