<?php
/**
 * HeaSec天积安全团队 - 异常数据处理靶场 - 第三关二维码接口
 * 版本: v1.0.0
 * 创建日期: 2026-03-18
 * 团队: 天积安全 (HeavenlySecret)
 */

// 设置响应头
header('X-HeavenlySecret: HeaSec 异常数据 Range v1.0.0');

// 定义访问常量
define('HEASEC_RANGE_ACCESS', true);

// 引入公共组件
$commonBasePath = '../../../../../common/';
require_once $commonBasePath . 'includes/session_manager.php';
require_once $commonBasePath . 'includes/HeaSec_Database.php';

// 初始化靶场会话
HeaSec_InitRangeSession('anomdata');

// 引入公共函数
require_once '../../includes/functions.php';

// 引入PHP QR Code库
require_once '../../includes/phpqrcode/phpqrcode.php';

$level = 3;

try {
    // 检查是否已登录
    $sessionUserId = isset($_SESSION['anomdata_user_id_level' . $level]) ? $_SESSION['anomdata_user_id_level' . $level] : null;
    if (!$sessionUserId) {
        header('HTTP/1.1 401 Unauthorized');
        exit;
    }

    // 获取订单ID
    $orderId = isset($_GET['orderId']) ? intval($_GET['orderId']) : 0;
    if ($orderId <= 0) {
        header('HTTP/1.1 400 Bad Request');
        exit;
    }

    // 获取数据库连接
    $pdo = HeaSec_Database::getConnection('heasec_logic');

    // 获取订单信息
    $stmt = $pdo->prepare("SELECT o.*, p.name as product_name FROM heasec_anomdata_orders o LEFT JOIN heasec_anomdata_products p ON o.product_id = p.id WHERE o.id = ? AND o.user_id = ? AND o.level = ?");
    $stmt->execute([$orderId, $sessionUserId, $level]);
    $order = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$order) {
        header('HTTP/1.1 404 Not Found');
        exit;
    }

    if (empty($order['passcode'])) {
        header('HTTP/1.1 404 Not Found');
        exit;
    }

    // 生成二维码内容
    $qrContent = $order['passcode'];

    // 输出二维码图片
    header('Content-Type: image/png');
    QRcode::png($qrContent, false, QR_ECLEVEL_M, 6, 2);

} catch (Exception $e) {
    error_log('[HeaSec] QRCode error: ' . $e->getMessage());
    header('HTTP/1.1 500 Internal Server Error');
    exit;
}
