<?php
/**
 * HeaSec天积安全团队 - JWT签名算法绕过靶场 - 个人中心接口
 * 版本: v1.0.0
 * 创建日期: 2026-03-02
 * 团队: 天积安全 (HeavenlySecret)
 */

// 设置响应头
header('X-HeavenlySecret: HeaSec JWT签名算法绕过 Range v1.0.0');
header('Content-Type: application/json; charset=utf-8');

// 定义访问常量
define('HEASEC_RANGE_ACCESS', true);

// 引入公共组件
require_once dirname(__DIR__) . '/../../../../common/includes/HeaSec_Database.php';

// 获取Authorization头（兼容多种服务器环境）
$authHeader = '';

// 方式1: 使用getallheaders函数
if (function_exists('getallheaders')) {
    $headers = getallheaders();
    if (isset($headers['Authorization'])) {
        $authHeader = $headers['Authorization'];
    }
}

// 方式2: 从$_SERVER中获取（Apache+CGI环境）
if (empty($authHeader) && isset($_SERVER['HTTP_AUTHORIZATION'])) {
    $authHeader = $_SERVER['HTTP_AUTHORIZATION'];
}

// 方式3: 从redirect变量获取（某些Apache配置）
if (empty($authHeader) && isset($_SERVER['REDIRECT_HTTP_AUTHORIZATION'])) {
    $authHeader = $_SERVER['REDIRECT_HTTP_AUTHORIZATION'];
}

// 方式4: 使用apache_request_headers函数
if (empty($authHeader) && function_exists('apache_request_headers')) {
    $apacheHeaders = apache_request_headers();
    if (isset($apacheHeaders['Authorization'])) {
        $authHeader = $apacheHeaders['Authorization'];
    }
}

// 从GET参数获取level
$level = isset($_GET['level']) ? intval($_GET['level']) : 1;

// 验证关卡
if (!in_array($level, [1, 2, 3])) {
    echo json_encode([
        'success' => false,
        'message' => '无效的关卡'
    ]);
    exit;
}

// 解析Token
if (!preg_match('/Bearer\s+(.*)$/i', $authHeader, $matches)) {
    echo json_encode([
        'success' => false,
        'message' => 'Token验证失败'
    ]);
    exit;
}

$token = $matches[1];

try {
    // 引入对应关卡的JWT类
    require_once dirname(__DIR__) . '/includes/jwt_level' . $level . '.php';
    $jwtClass = 'JWT_Level' . $level;

    // 验证并解码Token
    $payload = $jwtClass::decode($token);

    if (!$payload) {
        echo json_encode([
            'success' => false,
            'message' => 'Token验证失败'
        ]);
        exit;
    }

    // 获取用户信息
    $username = isset($payload['sub']) ? $payload['sub'] : '';
    $role = isset($payload['role']) ? $payload['role'] : 'user';

    // 构建响应数据
    $responseData = [
        'username' => $username,
        'role' => $role
    ];

    // 如果是admin角色，返回通关密码
    if ($role === 'admin') {
        // 从数据库获取通关密码
        $pdo = HeaSec_Database::getConnection('heasec_logic');
        $stmt = $pdo->prepare("SELECT passcode FROM heasec_jwtalg_users WHERE level = ? AND username = 'admin'");
        $stmt->execute([$level]);
        $adminUser = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($adminUser && $adminUser['passcode']) {
            $responseData['passcode'] = $adminUser['passcode'];
        }
    }

    echo json_encode([
        'success' => true,
        'data' => $responseData
    ]);

} catch (Exception $e) {
    error_log('[HeaSec] Profile error: ' . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Token验证失败'
    ]);
}
