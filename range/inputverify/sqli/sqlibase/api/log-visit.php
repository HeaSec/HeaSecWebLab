<?php
/**
 * HeaSec天积安全团队 - SQL注入基础靶场访问日志接口
 * 版本: v1.0.0
 * 团队: 天积安全 (HeavenlySecret)
 */

define('HEASEC_RANGE_ACCESS', true);
$commonBasePath = '../../../../common/';
require_once __DIR__ . '/../includes/bootstrap.php';

sqlibase_handle_api(function () {
    $ua = isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : 'Unknown';

    $pdo = sqlibase_get_pdo();

    $check_sql = "SELECT id, visit_count FROM heasec_sqlibase_visit_logs WHERE user_agent LIKE '%$ua%'";

    $matched_ua = null;
    try {
        $stmt = $pdo->query($check_sql);
        if ($stmt && $row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $matched_ua = $row;
            $update_sql = "UPDATE heasec_sqlibase_visit_logs SET visit_count = visit_count + 1, last_visit = NOW() WHERE id = ?";
            $update_stmt = $pdo->prepare($update_sql);
            $update_stmt->execute([$row['id']]);
        } else {
            $insert_sql = "INSERT INTO heasec_sqlibase_visit_logs (user_agent, visit_count, first_visit, last_visit) VALUES (?, 1, NOW(), NOW())";
            $insert_stmt = $pdo->prepare($insert_sql);
            $insert_stmt->execute([$ua]);
        }
    } catch (PDOException $e) {
        // 报错注入：将SQL错误信息返回给前端
        sqlibase_json_success('', [
            'total_visits' => 0,
            'total_hits' => 0,
            'today_visits' => 0,
            'error' => $e->getMessage()
        ]);
    }

    $stats_sql = "SELECT COUNT(*) as total_visits,
                  SUM(visit_count) as total_hits,
                  (SELECT COUNT(*) FROM heasec_sqlibase_visit_logs WHERE DATE(last_visit) = CURDATE()) as today_visits
                  FROM heasec_sqlibase_visit_logs";
    $stats_stmt = $pdo->query($stats_sql);
    $stats = $stats_stmt ? $stats_stmt->fetch(PDO::FETCH_ASSOC) : ['total_visits' => 0, 'total_hits' => 0, 'today_visits' => 0];

    $stats['matched_ua'] = $matched_ua;

    sqlibase_json_success('', $stats);
});
