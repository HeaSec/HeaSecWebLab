<?php
/**
 * HeaSec天积安全团队 - 命令执行实战靶场 - 成就状态查询接口
 * 版本: v1.0.0
 * 创建日期: 2026-04-23
 * 团队: 天积安全 (HeavenlySecret)
 */

define('HEASEC_RANGE_ACCESS', true);

$commonBasePath = '../../../../common/';
require_once $commonBasePath . 'includes/HeaSec_Database.php';
require_once __DIR__ . '/../includes/functions.php';

try {
    $pdo = HeaSec_Database::getConnection('heasec_inputverify');
    $achievementData = getAchievementStatus($pdo);

    sendJsonResponse(true, '', [
        'achieved_count' => $achievementData['achieved_count'],
        'records'        => $achievementData['records'],
        'progress_hint'  => generateProgressHint($achievementData['achieved_count']),
        'reverse_shell'  => $achievementData['reverse_shell'],
        'create_user'    => $achievementData['create_user'],
        'open_port'      => $achievementData['open_port']
    ]);
} catch (Exception $e) {
    sendJsonResponse(false, '数据库错误');
}
