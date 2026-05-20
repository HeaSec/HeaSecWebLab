<?php
/**
 * HeaSec天积安全团队 - SQL盲注靶场 - 第一关处理接口
 * 版本: v1.0.0
 * 功能: 商品信息查询（报错注入）
 * 团队: 天积安全 (HeavenlySecret)
 */

$commonBasePath = '../../../../common/';

require_once $commonBasePath . 'includes/HeaSec_Database.php';
require_once __DIR__ . '/../includes/functions.php';

// 接收用户提交的商品ID
$id = $_POST['id'] ?? '';

if ($id === '') {
    sendJsonResponse(false, '请输入商品ID');
}

// 获取数据库连接
$pdo = HeaSec_Database::getConnection('heasec_sqli');

// 数字型SQL查询 — 直接拼接用户输入
$sql = "SELECT id, name, price FROM heasec_blindinj_products WHERE id = " . $id;

try {
    $stmt = $pdo->query($sql);
    $row = $stmt ? $stmt->fetch(PDO::FETCH_ASSOC) : null;
    if ($row) {
        sendJsonResponse(true, '查询成功：商品存在', ['found' => true]);
    } else {
        sendJsonResponse(true, '查询完成：商品不存在', ['found' => false]);
    }
} catch (PDOException $e) {
    // 将SQL错误信息直接输出到页面
    sendJsonResponse(false, '查询出错：' . $e->getMessage());
}
