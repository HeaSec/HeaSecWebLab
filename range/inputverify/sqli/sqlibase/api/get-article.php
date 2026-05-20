<?php
/**
 * HeaSec天积安全团队 - SQL注入基础靶场获取资讯详情接口
 * 版本: v1.0.0
 * 团队: 天积安全 (HeavenlySecret)
 */

define('HEASEC_RANGE_ACCESS', true);
$commonBasePath = '../../../../common/';
require_once __DIR__ . '/../includes/bootstrap.php';

sqlibase_handle_api(function () {
    $id = isset($_GET['id']) ? $_GET['id'] : '';

    if ($id === '') {
        sqlibase_json_error('缺少资讯ID');
    }

    $pdo = sqlibase_get_pdo();

    $sql = "SELECT a.id, a.title, a.content, a.publish_date, a.view_count,
                   c.name AS category_name, u.name AS author_name
            FROM heasec_sqlibase_articles a
            LEFT JOIN heasec_sqlibase_categories c ON a.category_id = c.id
            LEFT JOIN heasec_sqlibase_users u ON a.author_id = u.id
            WHERE a.id = $id AND a.status = 1";

    try {
        $stmt = $pdo->query($sql);
        if ($stmt && $row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            sqlibase_json_success('', $row);
        } else {
            sqlibase_json_error('未找到该资讯');
        }
    } catch (PDOException $e) {
        sqlibase_json_error('未找到该资讯');
    }
});
