<?php
/**
 * HeaSec天积安全团队 - 文件包含基础靶场 - 重置接口
 * 版本: v1.0.0
 * 创建日期: 2026-04-16
 * 团队: 天积安全 (HeavenlySecret)
 */

header('Content-Type: application/json; charset=utf-8');
header('X-HeavenlySecret: HeaSec LfiBase Range v1.0.0');

$basePath = dirname(__DIR__);

// 删除第一关秘密文件
$secretFile1 = $basePath . '/config/secret.txt';
if (file_exists($secretFile1)) {
    unlink($secretFile1);
}

// 删除第二关秘密文件及目录
$level2Dir = $basePath . '/config/level2';
if (is_dir($level2Dir)) {
    $secretFile2 = $level2Dir . '/secret.php';
    if (file_exists($secretFile2)) {
        unlink($secretFile2);
    }
}

// 删除第三关秘密文件及目录
$level3Dir = $basePath . '/config/level3';
if (is_dir($level3Dir)) {
    $secretFile3 = $level3Dir . '/secret.php';
    if (file_exists($secretFile3)) {
        unlink($secretFile3);
    }
}

// 清空上传目录
$uploadDir = $basePath . '/uploads';
if (is_dir($uploadDir)) {
    $files = glob($uploadDir . '/*');
    if ($files) {
        foreach ($files as $file) {
            if (is_file($file)) {
                unlink($file);
            }
        }
    }
}

echo json_encode(['success' => true, 'message' => '重置成功'], JSON_UNESCAPED_UNICODE);
