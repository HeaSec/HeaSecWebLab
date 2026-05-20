<?php
/**
 * HeaSec天积安全团队 - 越权访问综合实战重置接口
 * 版本: v1.0.0
 * 团队: 天积安全 (HeavenlySecret)
 */

define('HEASEC_RANGE_ACCESS', true);
$commonBasePath = '../../../../common/';
require_once __DIR__ . '/../includes/bootstrap.php';

privesc_handle_api(function () {
    privesc_require_method('POST');
    privesc_execute_reset();

    privesc_json_success('重置成功');
});
