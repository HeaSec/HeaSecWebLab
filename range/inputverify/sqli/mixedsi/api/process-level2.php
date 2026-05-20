<?php
/**
 * HeaSec天积安全团队 - SQL注入综合实战靶场 - 第二关商品查询接口
 * 版本: v1.0.0
 * 功能: 商品详情查询处理（报错注入点）
 * 团队: 天积安全 (HeavenlySecret)
 */

$commonBasePath = '../../../../common/';
require_once $commonBasePath . 'includes/HeaSec_Database.php';
require_once __DIR__ . '/../includes/functions.php';

// 获取数据库连接
$pdo = HeaSec_Database::getConnection('heasec_sqli');

// 确保通关密码已在数据库表中生成
ensurePasswordExists(2);

$id = $_POST['id'] ?? '';

if ($id === '') {
    sendJsonResponse(false, '请输入商品ID');
}

// 安全过滤器
if (!filterLevel2_keywords($id)) {
    sendJsonResponse(false, '输入包含非法字符');
}
if (!filterLevel2_symbols($id)) {
    sendJsonResponse(false, '输入包含非法字符');
}

// SQL查询构造
$sql = "SELECT id, name, price, stock, description FROM heasec_mixedsi_products WHERE id = (" . $id . ")";

try {
    $stmt = $pdo->query($sql);
    $product = $stmt ? $stmt->fetch(PDO::FETCH_ASSOC) : null;
    if ($product) {
        // 对输出进行HTML转义
        $product['name'] = htmlspecialchars($product['name'], ENT_QUOTES, 'UTF-8');
        $product['description'] = htmlspecialchars($product['description'], ENT_QUOTES, 'UTF-8');
        sendJsonResponse(true, '查询成功', ['product' => $product]);
    } else {
        sendJsonResponse(false, '未找到该商品');
    }
} catch (PDOException $e) {
    // SQL错误信息直接输出（报错注入利用点）
    sendJsonResponse(false, '查询出错：' . $e->getMessage());
}
