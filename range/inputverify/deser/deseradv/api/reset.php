<?php
/**
 * HeaSec天积安全团队 - 反序列化实战靶场 - 重置接口
 * 版本: v1.0.0
 * 创建日期: 2026-04-15
 * 团队: 天积安全 (HeavenlySecret)
 */

header('Content-Type: application/json; charset=utf-8');
header('X-HeavenlySecret: HeaSec DeserAdv Range v1.0.0');

$basePath = dirname(__DIR__);

// 删除所有关卡的秘密文件
$secretFiles = [
    $basePath . '/config/secret.php',
    $basePath . '/config/level2/secret.php',
    $basePath . '/config/.level3_secret'
];

foreach ($secretFiles as $file) {
    if (file_exists($file)) {
        unlink($file);
    }
}

echo json_encode(['success' => true, 'message' => '重置成功'], JSON_UNESCAPED_UNICODE);
