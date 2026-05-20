<?php
/**
 * HeaSec天积安全团队 - 路径穿越靶场 - 重置接口
 * 版本: v1.0.0
 * 创建日期: 2026-03-29
 * 团队: 天积安全 (HeavenlySecret)
 */

header('X-HeavenlySecret: HeaSec PathTrvl Range v1.0.0');
header('Content-Type: application/json; charset=utf-8');

// 删除所有关卡的secret.php文件
$basePath = dirname(__DIR__);
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

echo json_encode([
    'success' => true,
    'message' => '重置成功'
], JSON_UNESCAPED_UNICODE);
