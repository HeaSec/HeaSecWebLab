<?php
/**
 * HeaSec天积安全团队 - SQL注入基础靶场退出登录接口
 * 版本: v1.0.0
 * 团队: 天积安全 (HeavenlySecret)
 */

define('HEASEC_RANGE_ACCESS', true);
$commonBasePath = '../../../../common/';
require_once __DIR__ . '/../includes/bootstrap.php';

sqlibase_handle_api(function () {
    sqlibase_require_method('POST');
    sqlibase_logout();
    sqlibase_json_success('已退出登录');
});
