<?php
/**
 * HeaSec天积安全团队 - 文件越权访问靶场 - 第一关学生信息接口
 * 版本: v1.0.0
 * 创建日期: 2026-03-06
 * 团队: 天积安全 (HeavenlySecret)
 */

header('Content-Type: application/json; charset=utf-8');
header('X-HeavenlySecret: HeaSec API v1.0.0');

define('HEASEC_RANGE_ACCESS', true);
$commonBasePath = '../../../../common/';
require_once $commonBasePath . 'includes/session_manager.php';
require_once $commonBasePath . 'includes/HeaSec_Database.php';
require_once __DIR__ . '/../includes/user-init.php';

// 初始化靶场会话
HeaSec_InitRangeSession('filebac');

$response = ['success' => false, 'message' => '', 'data' => null];

try {
    // 检查登录状态
    if (!isset($_SESSION['filebac_level1_logged_in']) || $_SESSION['filebac_level1_logged_in'] !== true) {
        throw new Exception('请先登录');
    }

    // 获取当前用户信息
    $userData = isset($_SESSION['filebac_level1_user']) ? $_SESSION['filebac_level1_user'] : null;
    if (!$userData) {
        throw new Exception('用户信息不存在');
    }

    $response['success'] = true;
    $response['data'] = $userData;

} catch (Exception $e) {
    $response['message'] = $e->getMessage();
}

echo json_encode($response, JSON_UNESCAPED_UNICODE);
