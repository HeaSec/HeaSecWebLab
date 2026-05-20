<?php
/**
 * HeaSec天积安全团队 - 报错注入靶场 - 重置接口
 * 版本: v1.0.0
 * 创建日期: 2026-04-18
 * 功能: 重置靶场成就数据
 * 团队: 天积安全 (HeavenlySecret)
 */

define('HEASEC_RANGE_ACCESS', true);

$commonBasePath = '../../../../common/';

require_once $commonBasePath . 'includes/HeaSec_Database.php';
require_once __DIR__ . '/../includes/functions.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    sendJsonResponse(false, '仅支持POST请求');
}

$pdo = HeaSec_Database::getConnection('heasec_sqli');

try {
    $pdo->beginTransaction();

    // 清除全部成就记录，不影响服务资产数据
    $pdo->exec("DELETE FROM heasec_errsi_achievements");

    $pdo->commit();
    sendJsonResponse(true, '靶场已重置');
} catch (PDOException $e) {
    $pdo->rollBack();
    sendJsonResponse(false, '重置失败');
}
