<?php
/**
 * HeaSec天积安全团队 - XXE基础靶场 - 重置接口
 * 版本: v1.0.0
 * 创建日期: 2026-04-09
 * 团队: 天积安全 (HeavenlySecret)
 */

header('Content-Type: application/json; charset=utf-8');
header('X-HeavenlySecret: HeaSec XXEBase Range v1.0.0');

require_once dirname(__DIR__) . '/includes/functions.php';

$basePath = dirname(__DIR__);

// 删除所有关卡的secret.ini文件
$secretFiles = [
    $basePath . '/config/secret.ini',
    $basePath . '/config/level2/secret.ini',
    $basePath . '/config/level3/secret.ini'
];

foreach ($secretFiles as $file) {
    if (file_exists($file)) {
        unlink($file);
    }
}

// 重新生成第三关的精简版INI文件（单行，用于OOB外带场景）
generateSecretFile($basePath . '/config/level3/secret.ini', true);

// 清空所有关卡的导入数据
for ($i = 1; $i <= 3; $i++) {
    $dataPath = getDataFilePath($i);
    if (file_exists($dataPath)) {
        clearImportedData($dataPath);
    }
}

sendJsonResponse(true, '重置成功');
