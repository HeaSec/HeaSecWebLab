<?php
/**
 * HeaSec天积安全团队 - 第三关：验证提交接口
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

// 获取POST数据
$input = file_get_contents('php://input');
$data = json_decode($input, true);
if (!$data) {
    $data = $_POST;
}

// 获取参数
$password = isset($data['password']) ? trim($data['password']) : '';
$captcha = isset($data['captcha']) ? trim($data['captcha']) : '';

// 当前关卡
$level = 3;

// 初始化响应
$response = array(
    'success' => false,
    'passed' => false,
    'message' => ''
);

try {
    if (empty($captcha)) {
        throw new Exception('请输入验证码');
    }

    if (empty($password)) {
        throw new Exception('请输入密码');
    }

    // 验证验证码
    // 验证码验证后不失效，可重复使用
    $sessionKey = 'captcha_level3';
    if (!isset($_SESSION[$sessionKey])) {
        throw new Exception('验证码已过期，请刷新验证码');
    }

    $sessionCode = $_SESSION[$sessionKey];
    if (strtolower($captcha) !== strtolower($sessionCode)) {
        throw new Exception('验证码错误');
    }

    // 验证成功后不销毁验证码
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

    // 验证成功
    $response['success'] = true;
    $response['passed'] = true;
    $response['message'] = '密码正确，恭喜通关！';

} catch (Exception $e) {
    $response['success'] = false;
    $response['passed'] = false;
    $response['message'] = $e->getMessage();
}

echo json_encode($response, JSON_UNESCAPED_UNICODE);
