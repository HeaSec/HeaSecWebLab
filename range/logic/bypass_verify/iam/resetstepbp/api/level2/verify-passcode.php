<?php
/**
 * HeaSec天积安全团队 - 密码重置流程绕过靶场 - 第二关通关验证接口
 * 版本: v1.0.0
 * 创建日期: 2026-02-04
 * 团队: 天积安全 (HeavenlySecret)
 */

// 设置响应头
header('X-HeavenlySecret: HeaSec 密码重置流程绕过 Range v1.0.0');
header('Content-Type: application/json; charset=utf-8');

// 设置公共组件基础路径
$commonBasePath = '../../../../../../common/';
// 定义访问常量
define('HEASEC_RANGE_ACCESS', true);

// 引入会话管理组件
require_once $commonBasePath . 'includes/session_manager.php';

// 初始化靶场会话
HeaSec_InitRangeSession('resetstepbp');

// 验证会话完整性
HeaSec_ValidateSession();

// 学习状态更新已移至第三关（最终关卡）的验证接口

try {
    // 接收参数
    $input = json_decode(file_get_contents('php://input'), true);
    $passcode = isset($input['passcode']) ? trim($input['passcode']) : '';

    // 获取正确的通关密码
    $correctPasscode = isset($_SESSION['passcode_level2']) ? $_SESSION['passcode_level2'] : '';

    if (empty($correctPasscode)) {
        echo json_encode([
            'passed' => false,
            'message' => '请先登录admin账号获取通关密码'
        ]);
        exit;
    }

    // 验证通关密码
    if ($passcode === $correctPasscode) {
        // 学习状态更新已移至第三关（最终关卡）

        echo json_encode([
            'passed' => true,
            'message' => '验证成功！进入下一关继续挑战。'
        ]);
    } else {
        echo json_encode([
            'passed' => false,
            'message' => '通关密码错误'
        ]);
    }
} catch (Exception $e) {
    echo json_encode([
        'passed' => false,
        'message' => '验证失败，请稍后重试'
    ]);
}
