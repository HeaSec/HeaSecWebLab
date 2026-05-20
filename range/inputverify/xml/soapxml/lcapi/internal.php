<?php
/**
 * HeaSec天积安全团队 - SOAP与XML靶场内部管理API
 * 版本: v1.0.0
 * 创建日期: 2026-04-11
 * 团队: 天积安全 (HeavenlySecret)
 * 说明: 模拟内部管理API，受IP白名单保护
 */

header('Content-Type: application/json; charset=utf-8');
header('X-HeavenlySecret: HeaSec SOAPXML Range v1.0.0');

require_once __DIR__ . '/../includes/functions.php';

// IP白名单验证：仅允许服务器本地访问
$clientIp = $_SERVER['REMOTE_ADDR'];
if ($clientIp !== '127.0.0.1' && $clientIp !== '::1') {
    http_response_code(403);
    echo json_encode(['error' => 'Access Denied'], JSON_UNESCAPED_UNICODE);
    exit;
}

// 从文件获取动态token（XXE请求不携带Session Cookie）
$token = getSsrfToken();

echo json_encode([
    'status' => 'ok',
    'service' => '天积云内部管理API',
    'token' => $token,
    'version' => '1.0.0',
    'data' => [
        'internal_id' => 'INT-2026-001',
        'system_status' => 'running'
    ]
], JSON_UNESCAPED_UNICODE);
