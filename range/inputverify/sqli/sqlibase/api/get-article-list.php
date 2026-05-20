<?php
/**
 * HeaSec天积安全团队 - SQL注入基础靶场获取资讯列表接口
 * 版本: v1.0.0
 * 团队: 天积安全 (HeavenlySecret)
 */

define('HEASEC_RANGE_ACCESS', true);
$commonBasePath = '../../../../common/';
require_once __DIR__ . '/../includes/bootstrap.php';

sqlibase_handle_api(function () {
    $pdo = sqlibase_get_pdo();
    $articles = sqlibase_get_article_list($pdo);
    $categories = sqlibase_get_categories($pdo);
    sqlibase_json_success('', ['articles' => $articles, 'categories' => $categories]);
});
