<?php
/**
 * HeaSec天积安全团队 - JWT密钥注入靶场 - 文件上传接口
 * 版本: v1.0.0
 * 创建日期: 2026-03-03
 * 团队: 天积安全 (HeavenlySecret)
 */

// 设置响应头
header('X-HeavenlySecret: HeaSec JWT密钥注入 Range v1.0.0');
header('Content-Type: application/json; charset=utf-8');

// 定义访问常量
define('HEASEC_RANGE_ACCESS', true);

// 设置公共组件的基础路径（从api目录到common目录的相对路径）
$commonBasePath = '../../../../../common/';

// 引入公共组件
require_once $commonBasePath . 'includes/database.php';

// 引入靶场配置和功能文件
require_once dirname(__DIR__) . '/includes/config.php';
require_once dirname(__DIR__) . '/includes/jwt.php';

// 获取Authorization头
$authHeader = '';
if (function_exists('getallheaders')) {
    $headers = getallheaders();
    if (isset($headers['Authorization'])) {
        $authHeader = $headers['Authorization'];
    }
}
if (empty($authHeader) && isset($_SERVER['HTTP_AUTHORIZATION'])) {
    $authHeader = $_SERVER['HTTP_AUTHORIZATION'];
}
if (empty($authHeader) && isset($_SERVER['REDIRECT_HTTP_AUTHORIZATION'])) {
    $authHeader = $_SERVER['REDIRECT_HTTP_AUTHORIZATION'];
}
if (empty($authHeader) && function_exists('apache_request_headers')) {
    $apacheHeaders = apache_request_headers();
    if (isset($apacheHeaders['Authorization'])) {
        $authHeader = $apacheHeaders['Authorization'];
    }
}

// 验证Token
if (!preg_match('/Bearer\s+(.*)$/i', $authHeader, $matches)) {
    echo json_encode([
        'success' => false,
        'message' => '未授权访问'
    ]);
    exit;
}

$token = $matches[1];

try {
    // 验证Token
    $payload = JWT_KeyInjection::decode($token);

    if (!$payload) {
        echo json_encode([
            'success' => false,
            'message' => 'Token验证失败'
        ]);
        exit;
    }
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Token验证失败'
    ]);
    exit;
}

// 检查是否有文件上传
if (!isset($_FILES['file']) || $_FILES['file']['error'] !== UPLOAD_ERR_OK) {
    echo json_encode([
        'success' => false,
        'message' => '请选择要上传的文件'
    ]);
    exit;
}

$file = $_FILES['file'];

// 获取文件信息
$fileName = $file['name'];
$fileSize = $file['size'];
$fileTmpName = $file['tmp_name'];

// 获取文件扩展名
$ext = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));

// 允许的文件类型（仅图片和文档，避免文件上传漏洞）
$allowedExtensions = ['jpg', 'jpeg', 'png', 'gif', 'bmp', 'webp', 'txt', 'pdf', 'doc', 'docx'];

// 验证文件类型
if (!in_array($ext, $allowedExtensions)) {
    echo json_encode([
        'success' => false,
        'message' => '只允许上传图片和文档文件（.jpg、.png、.gif、.txt、.pdf等）'
    ]);
    exit;
}

// 验证文件大小（最大10KB）
$maxSize = 10 * 1024; // 10KB
if ($fileSize > $maxSize) {
    echo json_encode([
        'success' => false,
        'message' => '文件大小不能超过10KB'
    ]);
    exit;
}

// 验证文件内容是否为文本
$fileContent = file_get_contents($fileTmpName);
if (preg_match('/[\x00-\x08\x0B\x0C\x0E-\x1F]/', $fileContent)) {
    echo json_encode([
        'success' => false,
        'message' => '文件内容必须是纯文本'
    ]);
    exit;
}

// 生成UUID文件名
function generateUUID() {
    return sprintf('%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
        random_int(0, 0xffff), random_int(0, 0xffff),
        random_int(0, 0xffff),
        random_int(0, 0x0fff) | 0x4000,
        random_int(0, 0x3fff) | 0x8000,
        random_int(0, 0xffff), random_int(0, 0xffff), random_int(0, 0xffff)
    );
}

// 确保上传目录存在
$uploadDir = dirname(__DIR__) . '/uploads/';
if (!is_dir($uploadDir)) {
    mkdir($uploadDir, 0755, true);
}

// 生成新文件名
$newFileName = generateUUID() . '.' . $ext;
$uploadPath = $uploadDir . $newFileName;

// 移动文件
if (move_uploaded_file($fileTmpName, $uploadPath)) {
    echo json_encode([
        'success' => true,
        'data' => [
            'filename' => $newFileName,
            'path' => 'uploads/' . $newFileName
        ],
        'message' => '文件上传成功'
    ]);
} else {
    echo json_encode([
        'success' => false,
        'message' => '文件上传失败'
    ]);
}
