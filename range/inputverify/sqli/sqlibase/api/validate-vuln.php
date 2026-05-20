<?php
/**
 * HeaSec天积安全团队 - SQL注入基础靶场漏洞验证提交接口
 * 版本: v1.0.0
 * 团队: 天积安全 (HeavenlySecret)
 */

define('HEASEC_RANGE_ACCESS', true);
$commonBasePath = '../../../../common/';
require_once __DIR__ . '/../includes/bootstrap.php';

sqlibase_handle_api(function () {
    sqlibase_require_method('POST');

    $pdo = sqlibase_get_pdo();
    $manager = sqlibase_get_vuln_manager($pdo);

    $request = sqlibase_get_request_data();
    $result = $manager->handleValidateRequest($request, sqlibase_get_config()['range_code']);
    HeaSec_VulnManager::sendJsonResponse($result);
});
