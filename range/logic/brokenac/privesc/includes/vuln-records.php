<?php
/**
 * HeaSec天积安全团队 - 越权访问综合实战漏洞记录辅助
 * 版本: v1.2.0 - 全局共享漏洞记录和成就
 * 团队: 天积安全 (HeavenlySecret)
 *
 * 本文件保留 privesc_ 前缀的函数签名，内部委托给 HeaSec_VulnManager 实例
 */

/**
 * 获取或创建 VulnManager 实例（单例）
 *
 * @param PDO|null $pdo 数据库连接（首次调用时必须提供）
 * @return HeaSec_VulnManager
 */
function privesc_get_vuln_manager($pdo = null)
{
    static $manager = null;

    if ($manager !== null) {
        return $manager;
    }

    if ($pdo === null) {
        $pdo = privesc_get_pdo();
    }

    $config = privesc_get_config();

    $commonBasePath = isset($GLOBALS['privescCommonBasePath']) ? $GLOBALS['privescCommonBasePath'] : '../../../../common/';
    require_once $commonBasePath . 'components/vuln-card/includes/HeaSec_VulnManager.php';

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
 * 获取漏洞定义。
 *
 * @return array
 */
function privesc_get_vuln_definitions()
{
    return privesc_get_vuln_manager()->getVulnDefinitions();
}

/**
 * 匹配漏洞提交。
 *
 * @param string $url 提交的URL
 * @param string $type 提交的类型
 * @param array $params 提交的参数
 * @return array|null
 */
function privesc_match_vulnerability($url, $type, array $params)
{
    return privesc_get_vuln_manager()->matchVulnerability($url, $type, $params);
}

/**
 * 检查漏洞记录是否已存在。
 *
 * @param PDO $pdo 数据库连接（保留签名兼容，内部忽略）
 * @param string $sessionId 会话标识（已废弃，保留签名兼容）
 * @param string $vulnId 漏洞标识
 * @return bool
 */
function privesc_has_vuln_record(PDO $pdo, $sessionId, $vulnId)
{
    return privesc_get_vuln_manager()->hasVulnRecord($vulnId);
}

/**
 * 新增漏洞记录。
 *
 * @param PDO $pdo 数据库连接（保留签名兼容，内部忽略）
 * @param string $sessionId 会话标识（已废弃，保留签名兼容）
 * @param string $vulnId 漏洞标识
 * @param int $score 分数
 */
function privesc_add_vuln_record(PDO $pdo, $sessionId, $vulnId, $score)
{
    privesc_get_vuln_manager()->addVulnRecord($vulnId, $score);
}

/**
 * 获取全局漏洞总分。
 *
 * @param PDO $pdo 数据库连接（保留签名兼容，内部忽略）
 * @param string $sessionId 会话标识（已废弃，保留签名兼容）
 * @return int
 */
function privesc_get_total_score(PDO $pdo, $sessionId)
{
    return privesc_get_vuln_manager()->getTotalScore();
}

/**
 * 获取已提交的漏洞记录（全局）。
 *
 * @param PDO $pdo 数据库连接（保留签名兼容，内部忽略）
 * @param string $sessionId 会话标识（已废弃，保留签名兼容）
 * @return array
 */
function privesc_get_submitted_records(PDO $pdo, $sessionId)
{
    return privesc_get_vuln_manager()->getSubmittedRecords();
}

/**
 * 获取全局星星状态。
 *
 * @param PDO $pdo 数据库连接（保留签名兼容，内部忽略）
 * @param string $sessionId 会话标识（已废弃，保留签名兼容）
 * @return array
 */
function privesc_get_star_status(PDO $pdo, $sessionId)
{
    return privesc_get_vuln_manager()->getStarStatus();
}

/**
 * 更新全局星星状态。
 *
 * @param PDO $pdo 数据库连接（保留签名兼容，内部忽略）
 * @param string $sessionId 会话标识（已废弃，保留签名兼容）
 * @param int $unlockedStars 已解锁的星星数量
 * @param int|null $congratsShownStars 已废弃
 */
function privesc_update_star_status(PDO $pdo, $sessionId, $unlockedStars, $congratsShownStars = null)
{
    privesc_get_vuln_manager()->updateStarStatus($unlockedStars, $congratsShownStars);
}


/**
 * 根据分数计算已解锁的星星数量。
 *
 * @param int $totalScore 当前总分
 * @param array $scoreThresholds 星星解锁的分数阈值
 * @return int
 */
function privesc_calculate_unlocked_stars($totalScore, array $scoreThresholds)
{
    return privesc_get_vuln_manager()->calculateUnlockedStars($totalScore);
}

