<?php
/**
 * HeaSec天积安全团队 - 密码重置流程绕过靶场 - 第二关重置密码接口
 * 版本: v1.0.0
 * 创建日期: 2026-02-04
 * 团队: 天积安全 (HeavenlySecret)
 *

 */

// 设置响应头
header('X-HeavenlySecret: HeaSec 密码重置流程绕过 Range v1.0.0');
header('Content-Type: application/json; charset=utf-8');

// 设置公共组件基础路径（从api/level2/目录到range/common/）
// 目录结构: range/logic/bypass_verify/iam/resetstepbp/api/level2/reset-password.php
// 需要到达: range/common/ （需要向上6级）
$commonBasePath = '../../../../../../common/';
// 定义访问常量
define('HEASEC_RANGE_ACCESS', true);

// 引入会话管理组件
require_once $commonBasePath . 'includes/session_manager.php';

// 初始化靶场会话
HeaSec_InitRangeSession('resetstepbp');

// 验证会话完整性
HeaSec_ValidateSession();

// 引入数据库组件
require_once $commonBasePath . 'includes/HeaSec_Database.php';

try {
    // 接收参数
    $input = json_decode(file_get_contents('php://input'), true);
    $username = isset($input['username']) ? trim($input['username']) : '';
    $password = isset($input['password']) ? trim($input['password']) : '';
    $confirmPassword = isset($input['confirm_password']) ? trim($input['confirm_password']) : '';

    // 校验密码一致性
    if ($password !== $confirmPassword) {
        echo json_encode([
            'success' => false,
            'message' => '两次密码不一致'
        ]);
        exit;
    }

    if (empty($username) || empty($password)) {
        echo json_encode([
            'success' => false,
            'message' => '请填写完整信息'
        ]);
        exit;
    }

    $pdo = HeaSec_Database::getConnection('heasec_logic');

    //仅校验账号是否存在，不校验用户是否完成验证流程
    $stmt = $pdo->prepare("SELECT id FROM heasec_resetstepbp_users WHERE level = 2 AND username = ?");
    $stmt->execute([$username]);

    if (!$stmt->fetch()) {
        echo json_encode([
            'success' => false,
            'message' => '账号不存在'
        ]);
        exit;
    }

    // 直接重置密码，没有任何验证流程检查
    $stmt = $pdo->prepare("UPDATE heasec_resetstepbp_users SET password = ? WHERE level = 2 AND username = ?");
    $stmt->execute([$password, $username]);

    echo json_encode([
        'success' => true,
        'message' => '密码重置成功'
    ]);
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => '重置失败，请稍后重试'
    ]);
}
