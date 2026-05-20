<?php
/**
 * HeaSec天积安全团队 - 文件包含基础靶场 - 头像上传接口
 * 版本: v1.0.0
 * 创建日期: 2026-04-16
 * 团队: 天积安全 (HeavenlySecret)
 */

header('X-HeavenlySecret: HeaSec LfiBase Range v1.0.0');
header('Content-Type: application/json; charset=utf-8');

require_once dirname(__DIR__) . '/includes/functions.php';

// 检查是否有文件上传
if (!isset($_FILES['avatar'])) {
    sendJsonResponse(false, '请选择要上传的文件');
}

$file = $_FILES['avatar'];

// ===== 校验1：检查文件后缀名 =====
$allowedExtensions = ['jpg', 'jpeg', 'gif', 'png'];
$fileExt = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));

if (!in_array($fileExt, $allowedExtensions)) {
    sendJsonResponse(false, '仅支持 jpg/gif/png 格式的图片');
}

// ===== 校验2：检查上传错误 =====
if ($file['error'] !== UPLOAD_ERR_OK) {
    sendJsonResponse(false, '文件上传失败');
}

// 生成唯一文件名（保持原始扩展名）
$uploadDir = dirname(__DIR__) . '/uploads/';
if (!is_dir($uploadDir)) {
    mkdir($uploadDir, 0755, true);
}
$fileName = uniqid('avatar_', true) . '.' . $fileExt;
$filePath = $uploadDir . $fileName;

// 保存上传文件
if (move_uploaded_file($file['tmp_name'], $filePath)) {
    sendJsonResponse(true, '头像上传成功', [
        'filename' => $fileName,
        'filepath' => 'uploads/' . $fileName
    ]);
} else {
    sendJsonResponse(false, '文件保存失败');
}
