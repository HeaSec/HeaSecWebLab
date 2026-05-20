<?php
/**
 * HeaSec天积安全团队 - 优惠滥用靶场 - 第三关数据获取接口
 * 版本: v1.0.0
 */

header('X-HeavenlySecret: HeaSec Discount Range v1.0.0');
header('Content-Type: application/json; charset=utf-8');

define('HEASEC_RANGE_ACCESS', true);

$commonBasePath = '../../../../../common/';
require_once $commonBasePath . 'includes/session_manager.php';
require_once $commonBasePath . 'includes/HeaSec_Database.php';

HeaSec_InitRangeSession('discount');

require_once '../../includes/functions.php';

$level = 3;
$pdo = HeaSec_Database::getConnection('heasec_logic');

// 获取商品列表
$products = getProducts($level, $pdo);

// 检查登录状态
$sessionUserId = isset($_SESSION['discount_user_id_level' . $level]) ? $_SESSION['discount_user_id_level' . $level] : null;

if (!$sessionUserId) {
    sendJsonResponse(true, '获取成功', [
        'loggedIn' => false,
        'products' => $products
    ]);
}

$user = getUserById($sessionUserId, $level, $pdo);
if (!$user) {
    unset($_SESSION['discount_user_id_level' . $level]);
    sendJsonResponse(true, '获取成功', [
        'loggedIn' => false,
        'products' => $products
    ]);
}

// 获取订单列表
$orders = getUserOrders($user['id'], $level, $pdo);

// 获取已购买数量
$paidCount = getPaidYuanbaoCount($user['id'], $level, $pdo);

// 初始化通关密码（首次登录时生成）
initUserPasscode($user['id'], $level, $pdo);

// 检查通关条件并返回通关密码
$passcode = null;
if (checkLevel3PassCondition($user['id'], $level, $pdo)) {
    $passcode = getUserPasscode($user['id'], $level, $pdo);
}

sendJsonResponse(true, '获取成功', [
    'loggedIn' => true,
    'user' => [
        'username' => $user['username'],
        'balance' => floatval($user['balance']),
        'firstPurchase' => $user['first_purchase'] == 1
    ],
    'products' => $products,
    'orders' => $orders,
    'paidCount' => $paidCount,
    'passcode' => $passcode
]);
