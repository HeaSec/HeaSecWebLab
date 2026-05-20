<?php
/**
 * HeaSec天积安全团队 - JWT签名算法绕过靶场 - 通关密码验证接口
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

// 获取POST数据
$input = file_get_contents('php://input');
$data = json_decode($input, true);

if (!$data) {
    $data = $_POST;
}

// 获取参数
$passcode = isset($data['passcode']) ? trim($data['passcode']) : '';
$level = isset($data['level']) ? intval($data['level']) : 1;

// 验证参数
if (empty($passcode)) {
    echo json_encode([
        'success' => false,
        'message' => '请输入通关密码'
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

    // 验证通关密码
    $stmt = $pdo->prepare("SELECT passcode FROM heasec_jwtalg_users WHERE level = ? AND username = 'admin'");
    $stmt->execute([$level]);
    $adminUser = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$adminUser || !$adminUser['passcode']) {
        echo json_encode([
            'success' => false,
            'message' => '通关密码验证失败'
        ]);
        exit;
    }

    if ($adminUser['passcode'] === $passcode) {
        echo json_encode([
            'success' => true,
            'message' => '验证通过'
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => '通关密码错误'
        ]);
    }

} catch (Exception $e) {
    error_log('[HeaSec] Verify passcode error: ' . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => '服务器错误，请稍后重试'
    ]);
}
