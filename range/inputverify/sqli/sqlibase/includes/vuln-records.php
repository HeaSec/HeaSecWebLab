<?php
/**
 * HeaSec天积安全团队 - SQL注入基础靶场漏洞记录辅助
 * 版本: v1.0.0 - 全局共享漏洞记录和成就
 * 团队: 天积安全 (HeavenlySecret)
 */

/**
 * 获取或创建 VulnManager 实例（单例）。
 *
 * @param PDO|null $pdo 数据库连接（首次调用时必须提供）
 * @return HeaSec_VulnManager
 */
function sqlibase_get_vuln_manager($pdo = null)
{
    static $manager = null;

    if ($manager !== null) {
        return $manager;
    }

    if ($pdo === null) {
        $pdo = sqlibase_get_pdo();
    }

    $config = sqlibase_get_config();

    $manager = new HeaSec_VulnManager([
        'pdo' => $pdo,
        'vulnConfigPath' => __DIR__ . '/../config/vuln_config.php',
        'vulnRecordsTable' => $config['tables']['vuln_records'],
        'starStatusTable' => $config['tables']['star_status'],
        'scoreThresholds' => $config['ui']['score_thresholds'],
        'rangeCode' => $config['range_code'],
        'showParamError' => true,
        'showTypeError' => true,
    ]);

    return $manager;
}

/**
 * 获取已提交的漏洞记录（全局）。
 *
 * @return array
 */
function sqlibase_get_submitted_records()
{
    return sqlibase_get_vuln_manager()->getSubmittedRecords();
}

/**
 * 获取全局漏洞总分。
 *
 * @return int
 */
function sqlibase_get_total_score()
{
    return sqlibase_get_vuln_manager()->getTotalScore();
}

/**
 * 获取全局星星状态。
 *
 * @return array
 */
function sqlibase_get_star_status()
{
    return sqlibase_get_vuln_manager()->getStarStatus();
}
