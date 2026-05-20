<?php
/**
 * HeaSec天积安全团队 - 越权访问综合实战响应辅助
 * 版本: v1.0.0
 * 团队: 天积安全 (HeavenlySecret)
 */

/**
 * 输出 JSON 响应并结束脚本。
 *
 * @param bool $success 是否成功
 * @param string $message 消息内容
 * @param array $data 附加数据
 * @param int $statusCode HTTP 状态码
 */
function privesc_json_response($success, $message = '', array $data = [], $statusCode = 200)
{
    http_response_code((int) $statusCode);
    header('Content-Type: application/json; charset=utf-8');
    header('HeavenlySecret: HeaSec');
    header('X-HeavenlySecret: HeaSec API v1.0.0');

    echo json_encode([
        'success' => (bool) $success,
        'message' => $message,
        'data' => $data,
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

/**
 * 输出成功响应。
 *
 * @param string $message 消息内容
 * @param array $data 附加数据
 * @param int $statusCode HTTP 状态码
 */
function privesc_json_success($message = '', array $data = [], $statusCode = 200)
{
    privesc_json_response(true, $message, $data, $statusCode);
}

/**
 * 输出失败响应。
 *
 * @param string $message 消息内容
 * @param int $statusCode HTTP 状态码
 * @param array $data 附加数据
 */
function privesc_json_error($message, $statusCode = 400, array $data = [])
{
    privesc_json_response(false, $message, $data, $statusCode);
}

/**
 * 校验请求方式。
 *
 * @param string $method 允许的请求方式
 */
function privesc_require_method($method)
{
    $currentMethod = isset($_SERVER['REQUEST_METHOD']) ? strtoupper($_SERVER['REQUEST_METHOD']) : 'GET';
    if ($currentMethod !== strtoupper((string) $method)) {
        privesc_json_error('请求方式错误', 405);
    }
}

/**
 * 统一执行 API 逻辑。
 *
 * @param callable $handler 处理函数
 */
function privesc_handle_api($handler)
{
    try {
        call_user_func($handler);
    } catch (Exception $exception) {
        error_log('[HeaSec][privesc] ' . $exception->getMessage());
        privesc_json_error('服务器处理失败', 500);
    }
}
