<?php
/**
 * HeaSec天积安全团队 - SQL注入基础靶场保存偏好设置接口
 * 版本: v1.0.0
 * 团队: 天积安全 (HeavenlySecret)
 */

define('HEASEC_RANGE_ACCESS', true);
$commonBasePath = '../../../../common/';
require_once __DIR__ . '/../includes/bootstrap.php';

sqlibase_handle_api(function () {
    $json = file_get_contents('php://input');
    $data = json_decode($json, true);

    $perPage = isset($data['per_page']) ? (int) $data['per_page'] : 10;
    $theme   = isset($data['theme']) ? trim($data['theme']) : 'blue';

    if (!in_array($perPage, [5, 10, 20], true)) {
        sqlibase_json_error('无效的每页条数');
    }

    if (!in_array($theme, ['blue', 'green', 'dark'], true)) {
        sqlibase_json_error('无效的主题');
    }

    $pdo = sqlibase_get_pdo();
    $table = sqlibase_table('preferences');

    $stmt = $pdo->prepare("UPDATE {$table} SET per_page = ?, theme = ? WHERE pref_key = 'default'");
    $stmt->execute([$perPage, $theme]);

    sqlibase_json_success('偏好已保存');
});
