<?php
/**
 * HeaSec天积安全团队 - JWT密钥注入靶场 - 个人中心接口
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
require_once dirname(__DIR__) . '/includes/init_users.php';
require_once dirname(__DIR__) . '/includes/jwt.php';

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
    // 先解码获取Header（用于识别攻击类型）
    $decodedToken = JWT_KeyInjection::decodeWithoutVerification($token);
    $header = $decodedToken ? $decodedToken['header'] : [];

    // 验证并解码Token
    $payload = JWT_KeyInjection::decode($token);

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

    // 成就相关
    $achievement = null;

    // 如果是admin角色，识别攻击类型并记录成就
    if ($role === 'admin' && $username === 'admin') {
        $attackType = identifyAttackType($header);

        if ($attackType !== null) {
            // 检查是否是新成就
            $isNewAchievement = !isAchievementExists($attackType);

            // 记录成就
            recordAchievement($attackType);

            $achievement = [
                'unlocked' => true,
                'type' => $attackType,
                'name' => getAttackTypeName($attackType),
                'message' => $isNewAchievement ? '检测到' . getAttackTypeName($attackType) . '攻击' : '再次使用' . getAttackTypeName($attackType) . '攻击'
            ];
        }
    }

    echo json_encode([
        'success' => true,
        'data' => $responseData,
        'achievement' => $achievement
    ]);

} catch (Exception $e) {
    error_log('[HeaSec] Profile error: ' . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Token验证失败'
    ]);
}
