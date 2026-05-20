<?php
/**
 * HeaSec天积安全团队 - 越权访问综合实战漏洞验证接口
 * 版本: v1.1.0 - 使用 VulnManager 统一处理
 * 团队: 天积安全 (HeavenlySecret)
 */

define('HEASEC_RANGE_ACCESS', true);
$commonBasePath = '../../../../common/';
require_once __DIR__ . '/../includes/bootstrap.php';

privesc_handle_api(function () {
    privesc_require_method('POST');

    $pdo = privesc_get_pdo();
    privesc_ensure_seed_data($pdo);

    $request = privesc_get_request_data();
    $manager = privesc_get_vuln_manager($pdo);

    $result = $manager->handleValidateRequest($request, privesc_get_config()['range_code']);
    HeaSec_VulnManager::sendJsonResponse($result);
});
