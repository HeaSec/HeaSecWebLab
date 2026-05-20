<?php
/**
 * HeaSec天积安全团队 - 文件包含进阶靶场文件上传接口
 * 版本: v1.0.0
 * 创建日期: 2026-04-17
 * 团队: 天积安全 (HeavenlySecret)
 */

define('HEASEC_RANGE_ACCESS', true);

$commonBasePath = '../../../../common/';

require_once $commonBasePath . 'includes/HeaSec_Database.php';
require_once __DIR__ . '/../includes/functions.php';

// 检查请求方法
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    sendJsonResponse(false, '仅支持POST请求');
}

// 检查文件是否上传
if (!isset($_FILES['userfile']) || $_FILES['userfile']['error'] === UPLOAD_ERR_NO_FILE) {
    sendJsonResponse(false, '请选择要上传的文件');
}

$file = $_FILES['userfile'];

// 检查上传错误
if ($file['error'] !== UPLOAD_ERR_OK) {
    sendJsonResponse(false, '文件上传失败');
}

// 允许的后缀名
$allowedExtensions = ['jpg', 'jpeg', 'gif', 'png', 'txt', 'zip', 'rar', 'tar', 'gz'];

// 获取文件后缀（处理 tar.gz 双扩展名的情况）
$fileName = $file['name'];
$fileExt = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));

// 特殊处理 .tar.gz 后缀
if (preg_match('/\.tar\.gz$/i', $fileName)) {
    $fileExt = 'tar.gz';
    // tar.gz 在允许列表中用 gz 代替即可，或者单独判断
    $fileExt = 'gz';
}

if (!in_array($fileExt, $allowedExtensions)) {
    sendJsonResponse(false, '不支持的文件格式，允许的格式：jpg/jpeg/gif/png/txt/zip/rar/tar/gz');
}

// 仅检查后缀名，不检查文件内容和 MIME 类型
$uploadDir = dirname(__DIR__) . '/uploads/';
if (!is_dir($uploadDir)) {
    mkdir($uploadDir, 0755, true);
}

// 生成唯一文件名
$uniqueName = uniqid('file_', true) . '.' . $fileExt;
$filePath = $uploadDir . $uniqueName;

if (move_uploaded_file($file['tmp_name'], $filePath)) {
    sendJsonResponse(true, '文件上传成功', [
        'filename' => $uniqueName,
        'filepath' => 'uploads/' . $uniqueName
    ]);
} else {
    sendJsonResponse(false, '文件保存失败');
}
