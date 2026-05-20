<?php
/**
 * HeaSec天积安全团队 - JWT密钥注入靶场 - JWKS端点
 * 版本: v1.0.0
 * 创建日期: 2026-03-03
 * 团队: 天积安全 (HeavenlySecret)
 */

// 设置响应头
header('X-HeavenlySecret: HeaSec JWT密钥注入 Range v1.0.0');
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');

// 定义访问常量
define('HEASEC_RANGE_ACCESS', true);

// 引入JWT类
require_once dirname(__DIR__) . '/includes/jwt.php';

try {
    // 获取JWKS
    $jwks = JWT_KeyInjection::getJWKS();

    if (empty($jwks)) {
        echo json_encode([
            'error' => 'unable_to_load_keys',
            'message' => '无法加载密钥'
        ]);
        exit;
    }

    echo json_encode($jwks);

} catch (Exception $e) {
    error_log('[HeaSec] JWKS error: ' . $e->getMessage());
    echo json_encode([
        'error' => 'server_error',
        'message' => '服务器错误'
    ]);
}
