<?php
/**
 * HeaSec天积安全团队 - 命令执行实战靶场 - 成就检测接口
 * 版本: v1.0.0
 * 创建日期: 2026-04-23
 * 团队: 天积安全 (HeavenlySecret)
 */

define('HEASEC_RANGE_ACCESS', true);

$commonBasePath = '../../../../common/';
require_once $commonBasePath . 'includes/HeaSec_Database.php';
require_once __DIR__ . '/../includes/functions.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    sendJsonResponse(false, '仅支持POST请求');
}

$type = $_POST['type'] ?? '';
$isWindows = (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN');
$result = false;
$detail = '';

switch ($type) {
    case 'reverse_shell':
        $result = checkReverseShell($_POST, $detail);
        break;
    case 'create_user':
        $result = checkCreateUser($isWindows, $detail);
        break;
    case 'open_port':
        $result = checkOpenPort($detail);
        break;
    default:
        sendJsonResponse(false, '未知的成就类型');
}

if ($result) {
    try {
        $pdo = HeaSec_Database::getConnection('heasec_inputverify');
        recordAchievement($pdo, $type, $detail);
        sendJsonResponse(true, '验证通过，成就已解锁！', ['achievement_type' => $type]);
    } catch (Exception $e) {
        sendJsonResponse(false, '数据库错误');
    }
} else {
    sendJsonResponse(false, '验证未通过：' . $detail);
}
