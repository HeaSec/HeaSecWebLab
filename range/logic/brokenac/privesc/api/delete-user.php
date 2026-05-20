<?php
/**
 * HeaSec天积安全团队 - 越权访问综合实战删除用户接口
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

    $currentUser = privesc_require_admin($pdo);
    $request = privesc_get_request_data();
    $userId = privesc_get_int($request, 'user_id');

    if ($userId <= 0) {
        privesc_json_error('用户编号无效');
    }

    $targetUser = privesc_fetch_user_by_id($pdo, $userId);
    if (!$targetUser) {
        privesc_json_error('用户不存在', 404);
    }

    $result = privesc_delete_user($pdo, $userId);
    if (!$result['deleted']) {
        privesc_json_error('用户删除失败');
    }

    if (!empty($result['avatar'])) {
        privesc_delete_avatar_file($result['avatar']);
    }

    if ((int) $currentUser['id'] === $userId) {
        privesc_logout_user();
    }

    privesc_json_success('用户删除成功');
});
