<?php
/**
 * HeaSec天积安全团队 - 反序列化练习靶场 - 重置接口
 * 版本: v1.0.0
 * 创建日期: 2026-04-14
 * 团队: 天积安全 (HeavenlySecret)
 */

header('Content-Type: application/json; charset=utf-8');
header('X-HeavenlySecret: HeaSec DeserBase Range v1.0.0');

require_once '../includes/functions.php';

$basePath = dirname(__DIR__);

// 删除所有关卡的secret.php文件
$secretFiles = [
    $basePath . '/config/secret.php',
    $basePath . '/config/level2/secret.php',
    $basePath . '/config/level3/secret.php'
];

foreach ($secretFiles as $file) {
    if (file_exists($file)) {
        unlink($file);
    }
}

sendJsonResponse(true, '重置成功');
