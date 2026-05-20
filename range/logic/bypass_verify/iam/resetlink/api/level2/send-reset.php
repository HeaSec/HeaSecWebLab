<?php
/**
 * HeaSec天积安全团队 - 密码重置凭证可猜测靶场 - 第二关发送重置链接API
 * 版本: v1.0.0
 * 创建日期: 2026-01-22
 * 团队: 天积安全 (HeavenlySecret)

 */

header('Content-Type: application/json; charset=utf-8');
header('X-HeavenlySecret: HeaSec API v1.0.0');

define('HEASEC_RANGE_ACCESS', true);
$commonBasePath = '../../../../../../common/';
require_once $commonBasePath . 'includes/HeaSec_Database.php';
require_once $commonBasePath . 'components/sms-simulator/includes/HeaSec_SmsSender.php';

$input = file_get_contents('php://input');
$data = json_decode($input, true);
$username = isset($data['username']) ? trim($data['username']) : '';

$response = ['success' => false, 'message' => ''];

try {
    $pdo = HeaSec_Database::getConnection('heasec_logic');
    $stmt = $pdo->prepare("SELECT * FROM heasec_resetlink_users WHERE level = 2 AND username = ?");
    $stmt->execute([$username]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        throw new Exception('账号不存在');
    }


    $token = md5($user['username']);
    // 动态获取网站基础URL
    $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'];
    // 计算靶场根目录的相对URL路径
    $rangeDir = dirname(dirname(dirname($_SERVER['SCRIPT_NAME']))); // 从 /api/level2/ 返回到靶场根目录
    $baseUrl = $protocol . '://' . $host . $rangeDir;
    $resetLink = $baseUrl . '/reset2.php?token=' . $token;
    $message = '您的密码重置链接为：' . $resetLink;

    $result = HeaSec_SmsSender::send($user['phone'], $message, 'resetlink_level2');

    if ($result['success']) {
        $response['success'] = true;
        $response['message'] = '重置链接已发送到手机号：' . $user['phone'];
    } else {
        throw new Exception($result['message'] ?? '发送失败');
    }
} catch (Exception $e) {
    $response['message'] = $e->getMessage();
}

echo json_encode($response, JSON_UNESCAPED_UNICODE);
