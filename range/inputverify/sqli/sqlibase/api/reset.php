<?php
/**
 * HeaSec天积安全团队 - SQL注入基础靶场重置接口
 * 版本: v1.0.0
 * 团队: 天积安全 (HeavenlySecret)
 */

define('HEASEC_RANGE_ACCESS', true);
$commonBasePath = '../../../../common/';
require_once __DIR__ . '/../includes/bootstrap.php';

sqlibase_handle_api(function () {
    sqlibase_require_method('POST');
    sqlibase_execute_reset();
    sqlibase_json_success('靶场已重置');
});
