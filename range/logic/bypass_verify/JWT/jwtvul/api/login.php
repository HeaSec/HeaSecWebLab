<?php
/**
 * HeaSec天积安全团队 - JWT基础漏洞靶场 - 登录接口
 * 版本: v1.0.0
 * 创建日期: 2026-03-03
 * 团队: 天积安全 (HeavenlySecret)
 */

// 设置响应头
header('X-HeavenlySecret: HeaSec JWT基础漏洞 Range v1.0.0');
header('Content-Type: application/json; charset=utf-8');

// 定义访问常量
define('HEASEC_RANGE_ACCESS', true);

// 引入公共组件
require_once '../../../../../common/includes/HeaSec_Database.php';

// 获取POST数据
$input = file_get_contents('php://input');
$data = json_decode($input, true);

if (!$data) {
    $data = $_POST;
}

// 获取参数
$username = isset($data['username']) ? trim($data['username']) : '';
$password = isset($data['password']) ? trim($data['password']) : '';
$level = isset($data['level']) ? intval($data['level']) : 1;

// 验证参数
if (empty($username) || empty($password)) {
    echo json_encode([
        'success' => false,
        'message' => '请输入账号和密码'
    ]);
    exit;
}

// 验证关卡
if (!in_array($level, [1, 2, 3])) {
    echo json_encode([
        'success' => false,
        'message' => '无效的关卡'
    ]);
    exit;
}

try {
    // 获取数据库连接
    $pdo = HeaSec_Database::getConnection('heasec_logic');

    // 查询用户
    $stmt = $pdo->prepare("SELECT * FROM heasec_jwtvul_users WHERE level = ? AND username = ?");
    $stmt->execute([$level, $username]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user || $user['password'] !== $password) {
        echo json_encode([
            'success' => false,
            'message' => '账号或密码错误'
        ]);
        exit;
    }

    // 引入对应关卡的JWT类
    require_once '../includes/jwt_level' . $level . '.php';
    $jwtClass = 'JWT_Level' . $level;

    // 生成JWT Token
    if ($level === 1) {
        // 获取当前关卡的通关密码
        $stmt = $pdo->prepare("SELECT passcode FROM heasec_jwtvul_users WHERE level = ? AND username = 'admin'");
        $stmt->execute([$level]);
        $adminData = $stmt->fetch(PDO::FETCH_ASSOC);
        $passcode = $adminData ? $adminData['passcode'] : null;
        $token = $jwtClass::encode($username, $user['role'], $passcode);
    } else {
        $token = $jwtClass::encode($username, $user['role']);
    }

    echo json_encode([
        'success' => true,
        'token' => $token,
        'message' => '登录成功'
    ]);

} catch (Exception $e) {
    error_log('[HeaSec] Login error: ' . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => '服务器错误，请稍后重试'
    ]);
}
