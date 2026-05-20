<?php
/**
 * HeaSec天积安全团队 - 第二关：验证提交接口
 * 版本: v1.0.0
 * 创建日期: 2026-01-20
 * 团队: 天积安全 (HeavenlySecret)

 */

// 设置响应头
header('Content-Type: application/json; charset=utf-8');
header('X-HeavenlySecret: HeaSec API v1.0.0');

// 只允许POST请求
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(array(
        'success' => false,
        'passed' => false,
        'message' => '只允许POST请求'
    ), JSON_UNESCAPED_UNICODE);
    exit;
}



// 定义访问常量
define('HEASEC_RANGE_ACCESS', true);

// 设置公共组件的基础路径
$commonBasePath = '../../../../../common/';

// 引入会话管理组件
require_once $commonBasePath . 'includes/session_manager.php';
HeaSec_InitRangeSession('imgcodebp1');

// 引入数据库组件
require_once $commonBasePath . 'includes/HeaSec_Database.php';

// 引入学习状态更新组件
require_once $commonBasePath . 'includes/HeaSec_LearningStatusUpdater.php';

// 获取POST数据
$input = file_get_contents('php://input');
$data = json_decode($input, true);
if (!$data) {
    $data = $_POST;
}

// 获取参数
$password = isset($data['password']) ? trim($data['password']) : '';
$captchaVerified = isset($data['captcha_verified']) ? $data['captcha_verified'] : '0';

// 当前关卡
$level = 2;

// 初始化响应
$response = array(
    'success' => false,
    'passed' => false,
    'message' => ''
);

try {
    // 仅检查前端传递的captcha_verified字段
    if ($captchaVerified !== '1') {
        throw new Exception('验证码校验未通过，请先正确输入验证码');
    }

    if (empty($password)) {
        throw new Exception('请输入密码');
    }

    // 获取数据库连接
    $pdo = HeaSec_Database::getConnection('heasec_logic');

    // 查询正确密码
    $stmt = $pdo->prepare("SELECT password FROM heasec_imgcodebp1_passwords WHERE level = ?");
    $stmt->execute(array($level));
    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$row) {
        throw new Exception('系统错误：密码未初始化');
    }

    // 验证密码
    if ($password !== $row['password']) {
        throw new Exception('密码错误');
    }

    // 验证成功，更新学习状态：从"待学习"更新为"学习中"
    HeaSec_UpdateLearningStatusIfNeeded('imgcodebp1');

    $response['success'] = true;
    $response['passed'] = true;
    $response['message'] = '密码正确，恭喜通关！';

} catch (Exception $e) {
    $response['success'] = false;
    $response['passed'] = false;
    $response['message'] = $e->getMessage();
}

echo json_encode($response, JSON_UNESCAPED_UNICODE);
