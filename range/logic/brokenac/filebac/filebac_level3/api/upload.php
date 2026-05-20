<?php
/**
 * HeaSec天积安全团队 - 文件越权访问靶场 - 第三关上传接口
 * 版本: v1.0.0
 * 创建日期: 2026-03-06
 * 团队: 天积安全 (HeavenlySecret)
 */

header('Content-Type: application/json; charset=utf-8');
header('X-HeavenlySecret: HeaSec API v1.0.0');

define('HEASEC_RANGE_ACCESS', true);
$commonBasePath = '../../../../../common/';
require_once $commonBasePath . 'includes/session_manager.php';
require_once $commonBasePath . 'includes/HeaSec_Database.php';
require_once dirname(__DIR__) . '/../includes/user-init.php';

// 初始化靶场会话
HeaSec_InitRangeSession('filebac');

$response = ['success' => false, 'message' => ''];

try {
    // 检查登录状态
    if (!isset($_SESSION['filebac_level3_logged_in']) || $_SESSION['filebac_level3_logged_in'] !== true) {
        throw new Exception('请先登录');
    }

    // 获取当前用户信息
    $userData = isset($_SESSION['filebac_level3_user']) ? $_SESSION['filebac_level3_user'] : null;
    if (!$userData) {
        throw new Exception('用户信息不存在');
    }

    // 检查是否有文件上传
    if (!isset($_FILES['idcard_image']) || $_FILES['idcard_image']['error'] !== UPLOAD_ERR_OK) {
        throw new Exception('请选择要上传的图片');
    }

    $file = $_FILES['idcard_image'];

    // 验证文件类型
    $allowedTypes = ['image/png', 'image/jpeg', 'image/jpg'];
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mimeType = finfo_file($finfo, $file['tmp_name']);
    finfo_close($finfo);

    if (!in_array($mimeType, $allowedTypes)) {
        throw new Exception('仅允许上传PNG、JPG、JPEG格式的图片');
    }

    // 验证文件大小（最大2MB）
    $maxSize = 2 * 1024 * 1024;
    if ($file['size'] > $maxSize) {
        throw new Exception('文件大小不能超过2MB');
    }

    // 计算手机号MD5作为文件名
    $phoneMd5 = md5($userData['phone']);

    // 确保目录存在
    $uploadDir = dirname(__DIR__) . '/idcard/';
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }

    // 保存文件（覆盖之前的文件）
    $uploadPath = $uploadDir . $phoneMd5 . '.png';

    // 如果是PNG直接移动，否则转换
    if ($mimeType === 'image/png') {
        if (!move_uploaded_file($file['tmp_name'], $uploadPath)) {
            throw new Exception('文件保存失败');
        }
    } else {
        // 转换为PNG格式
        if ($mimeType === 'image/jpeg' || $mimeType === 'image/jpg') {
            $sourceImage = imagecreatefromjpeg($file['tmp_name']);
        } else {
            throw new Exception('不支持的图片格式');
        }

        if (!$sourceImage) {
            throw new Exception('图片处理失败');
        }

        $result = imagepng($sourceImage, $uploadPath);
        imagedestroy($sourceImage);

        if (!$result) {
            throw new Exception('图片保存失败');
        }
    }

    $response['success'] = true;
    $response['message'] = '上传成功';

} catch (Exception $e) {
    $response['message'] = $e->getMessage();
}

echo json_encode($response, JSON_UNESCAPED_UNICODE);
