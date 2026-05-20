<?php
/**
 * HeaSec天积安全团队 - SQL注入基础靶场获取偏好设置接口
 * 版本: v1.0.0
 * 团队: 天积安全 (HeavenlySecret)
 */

define('HEASEC_RANGE_ACCESS', true);
$commonBasePath = '../../../../common/';
require_once __DIR__ . '/../includes/bootstrap.php';

sqlibase_handle_api(function () {
    $config = sqlibase_get_config();
    $token = isset($_COOKIE[$config['cookie']['name']]) ? $_COOKIE[$config['cookie']['name']] : $config['cookie']['default'];

    $pdo = sqlibase_get_pdo();

    $sql = "SELECT * FROM heasec_sqlibase_preferences WHERE (pref_key) = ('$token')";

    try {
        $stmt = $pdo->query($sql);
        if ($stmt && $row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            sqlibase_json_success('', $row);
        } else {
            sqlibase_json_error('未找到偏好设置');
        }
    } catch (PDOException $e) {
        sqlibase_json_error('SQL错误: ' . $e->getMessage());
    }
});
