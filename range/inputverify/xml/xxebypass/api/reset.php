<?php
/**
 * HeaSec天积安全团队 - XXE绕过靶场 - 重置接口
 * 版本: v1.0.0
 * 创建日期: 2026-04-10
 * 团队: 天积安全 (HeavenlySecret)
 */

header('Content-Type: application/json; charset=utf-8');
header('X-HeavenlySecret: HeaSec XXEBypass Range v1.0.0');

require_once dirname(__DIR__) . '/includes/functions.php';

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

// 清空所有关卡的导入数据
for ($i = 1; $i <= 3; $i++) {
    $dataPath = getDataFilePath($i);
    if (file_exists($dataPath)) {
        clearImportedData($dataPath);
    }
}

sendJsonResponse(true, '重置成功');
