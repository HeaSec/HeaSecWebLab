<?php
/**
 * HeaSec天积安全团队 - 越权访问综合实战删除头像接口
 * 版本: v1.0.0
 * 团队: 天积安全 (HeavenlySecret)
 */

define('HEASEC_RANGE_ACCESS', true);
$commonBasePath = '../../../../common/';
require_once __DIR__ . '/../includes/bootstrap.php';

privesc_handle_api(function () {
    privesc_require_method('POST');

    $pdo = privesc_get_pdo();
    privesc_ensure_seed_data($pdo);

    $currentUser = privesc_require_login($pdo);
    $request = privesc_get_request_data();
    $filename = privesc_get_string($request, 'filename');

    if (!privesc_is_valid_avatar_filename($filename)) {
        privesc_json_error('头像文件名格式错误');
    }

    if (!privesc_delete_avatar_file($filename)) {
        privesc_json_error('头像文件不存在或删除失败', 404);
    }

    privesc_clear_avatar_references($pdo, $filename);

    privesc_json_success('头像删除成功');
});
