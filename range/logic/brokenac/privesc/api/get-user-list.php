<?php
/**
 * HeaSec天积安全团队 - 越权访问综合实战获取用户列表接口
 * 版本: v1.0.0
 * 团队: 天积安全 (HeavenlySecret)
 */

define('HEASEC_RANGE_ACCESS', true);
$commonBasePath = '../../../../common/';
require_once __DIR__ . '/../includes/bootstrap.php';

privesc_handle_api(function () {
    privesc_require_method('GET');

    $pdo = privesc_get_pdo();
    privesc_ensure_seed_data($pdo);

    privesc_require_login($pdo);

    privesc_json_success('', [
        'users' => privesc_get_user_list($pdo),
    ]);
});
