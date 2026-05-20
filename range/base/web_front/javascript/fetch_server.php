<?php
/**
 * HeaSec天积安全团队 - Fetch API操作服务端脚本
 * 版本: v1.0.0
 * 创建日期: 2025-12-16
 * 团队: 天积安全 (HeavenlySecret)
 *
 * 功能：处理来自JavaScript靶场的Fetch API请求
 */

// 设置响应头，允许跨域请求
header('Content-Type: text/plain; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// 设置安全响应头
header('X-HeavenlySecret: HeaSec Fetch API Server v1.0.0');

// 获取请求方法
$method = $_SERVER['REQUEST_METHOD'];

// 处理OPTIONS预检请求
if ($method === 'OPTIONS') {
    exit(0);
}

// 获取参数并进行安全过滤
$name = isset($_REQUEST['name']) ? trim($_REQUEST['name']) : '访客';
$gender = isset($_REQUEST['gender']) ? $_REQUEST['gender'] : 'unknown';

// 对输入参数进行安全处理
$name = htmlspecialchars($name, ENT_QUOTES, 'UTF-8');
$gender = in_array($gender, ['male', 'female']) ? $gender : 'unknown';

// 生成性别称呼
$genderTitle = '先生/女士';
if ($gender === 'male') {
    $genderTitle = '先生';
} elseif ($gender === 'female') {
    $genderTitle = '女士';
}

// 获取当前时间
$currentTime = date('Y-m-d H:i:s');

// 生成响应消息
$response = $name . ' ' . $genderTitle . '，你好！' . "\n";
$response .= '你于' . $currentTime . '使用' . $method . '方法提交了一个Fetch请求' . "\n";
$response .= '请求处理成功';

// 记录请求日志（可选）
$logMessage = sprintf(
    "[%s] %s请求: name=%s, gender=%s",
    $currentTime,
    $method,
    $name,
    $gender
);
// 在生产环境中，可以将日志写入文件
// error_log($logMessage . "\n", 3, 'fetch_requests.log');

// 输出响应
echo $response;
?>