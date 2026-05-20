<?php
/**
 * HeaSec天积安全团队 - 时间盲注靶场 - 成就状态接口
 * 版本: v2.0.0
 * 创建日期: 2026-04-20
 * 更新日期: 2026-04-26 - 双维度成就数据返回
 * 功能: 获取当前双维度成就状态（延迟技术 + 字符串函数）
 * 团队: 天积安全 (HeavenlySecret)
 */

define('HEASEC_RANGE_ACCESS', true);

$commonBasePath = '../../../../common/';

require_once $commonBasePath . 'includes/HeaSec_Database.php';
require_once __DIR__ . '/../includes/functions.php';

try {
    $pdo = HeaSec_Database::getConnection('heasec_sqli');

    $achievementData = getAchievementStatus($pdo);

    sendJsonResponse(true, '获取成功', [
        'star_count'      => $achievementData['star_count'],
        'delay_count'     => $achievementData['delay_count'],
        'string_count'    => $achievementData['string_count'],
        'delay_records'   => $achievementData['delay_records'],
        'string_records'  => $achievementData['string_records'],
        'delay_hint'      => $achievementData['delay_hint'],
        'string_hint'     => $achievementData['string_hint'],
        'achieved_count'  => $achievementData['star_count']  // 向后兼容
    ]);
} catch (Exception $e) {
    sendJsonResponse(false, '获取成就状态失败');
}
