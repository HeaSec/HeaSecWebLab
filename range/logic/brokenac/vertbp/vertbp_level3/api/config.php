<?php
/**
 * HeaSec天积安全团队 - 垂直越权基础靶场 - 第三关配置接口
 * 版本: v1.0.0
 * 创建日期: 2026-03-05
 * 团队: 天积安全 (HeavenlySecret)
 * 第三关配置接口
 */

header('Content-Type: application/json; charset=utf-8');
header('X-HeavenlySecret: HeaSec API v1.0.0');

define('HEASEC_RANGE_ACCESS', true);
$commonBasePath = '../../../../../common/';
require_once $commonBasePath . 'includes/session_manager.php';
require_once $commonBasePath . 'includes/HeaSec_Database.php';
require_once __DIR__ . '/../../includes/user-init.php';

// 初始化靶场会话
HeaSec_InitRangeSession('vertbp');

$response = ['success' => false, 'message' => ''];

try {
    // 检查登录状态（仅检查是否登录）
    if (!isset($_SESSION['vertbp_level3_logged_in']) || $_SESSION['vertbp_level3_logged_in'] !== true) {
        throw new Exception('请先登录');
    }

    // 根据请求来源地址判断是否允许访问配置数据
    $referer = isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : '';
    $parsedReferer = parse_url($referer);

    if ($parsedReferer === false || !isset($parsedReferer['scheme'], $parsedReferer['host'], $parsedReferer['path'])) {
        throw new Exception('非法请求来源');
    }

    $requestScheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $requestHost = isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : '';

    // 根据 edit.php 的实际文件位置推导其在 Web 根目录下的相对路径，
    // 避免硬编码部署路径，使靶场可自适应不同部署环境（子目录、Docker 内网等）。
    // edit.php 位于当前接口所在 api/ 目录的上一级（vertbp_level3/）目录下。
    $editPhpFsPath = dirname(__DIR__) . DIRECTORY_SEPARATOR . 'edit.php';
    $docRoot = isset($_SERVER['DOCUMENT_ROOT']) ? rtrim($_SERVER['DOCUMENT_ROOT'], '/\\') : '';
    $expectedPath = '';
    if ($docRoot !== '' && is_file($editPhpFsPath)) {
        // 统一为正斜杠比较以兼容 Windows；按文档根长度截取得到相对 Web 路径
        $normalizedFsPath = str_replace('\\', '/', $editPhpFsPath);
        $normalizedDocRoot = str_replace('\\', '/', $docRoot);
        if (stripos($normalizedFsPath, $normalizedDocRoot) === 0) {
            $expectedPath = '/' . ltrim(substr($normalizedFsPath, strlen($normalizedDocRoot)), '/');
        }
    }

    $refererHost = $parsedReferer['host'];
    if (isset($parsedReferer['port'])) {
        $refererHost .= ':' . $parsedReferer['port'];
    }

    if ($parsedReferer['scheme'] !== $requestScheme || $refererHost !== $requestHost || $parsedReferer['path'] !== $expectedPath) {
        throw new Exception('非法请求来源');
    }

    // 获取数据库连接
    $pdo = HeaSec_Database::getConnection('heasec_logic');

    // 初始化用户数据
    initVertbpLevelUsers(3, $pdo);

    // 获取admin用户的通关密码
    $stmt = $pdo->prepare("SELECT passcode FROM heasec_vertbp_users WHERE level = 3 AND role = 'admin'");
    $stmt->execute();
    $admin = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$admin || empty($admin['passcode'])) {
        throw new Exception('系统错误，请重置靶场');
    }

    // 生成模拟运行时间
    $uptime_days = rand(1, 30);
    $uptime_hours = rand(0, 23);
    $uptime_minutes = rand(0, 59);

    // 返回当前关卡配置数据
    $response['success'] = true;
    $response['data'] = [
        'device_name' => 'HeaSec-TJRouter-X1000',
        'firmware_version' => 'v2.3.1',
        'mac_address' => '00:1A:2B:3C:4D:5E',
        'uptime' => "{$uptime_days}天 {$uptime_hours}小时 {$uptime_minutes}分钟",
        'online_devices' => rand(3, 8),
        'wan_status' => '已连接',
        'lan_status' => '已连接',
        'passcode' => $admin['passcode']
    ];

} catch (Exception $e) {
    $response['message'] = $e->getMessage();
}

echo json_encode($response, JSON_UNESCAPED_UNICODE);
