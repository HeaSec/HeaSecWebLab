<?php
/**
 * HeaSec天积安全团队 - 报错注入靶场 - 成就状态接口
 * 版本: v1.0.0
 * 创建日期: 2026-04-18
 * 功能: 获取当前成就状态和服务列表
 * 团队: 天积安全 (HeavenlySecret)
 */

define('HEASEC_RANGE_ACCESS', true);

$commonBasePath = '../../../../common/';

require_once $commonBasePath . 'includes/HeaSec_Database.php';
require_once __DIR__ . '/../includes/functions.php';

$pdo = HeaSec_Database::getConnection('heasec_sqli');

// 查询成就状态
$achievementData = getAchievementStatus($pdo);

sendJsonResponse(true, '获取成功', [
    'achieved_count' => $achievementData['achieved_count'],
    'records'        => $achievementData['records'],
    'progress_hint'  => generateProgressHint($achievementData['achieved_count'])
]);
