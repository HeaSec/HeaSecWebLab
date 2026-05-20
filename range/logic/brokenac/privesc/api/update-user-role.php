<?php
/**
 * HeaSec天积安全团队 - 越权访问综合实战更新用户角色接口
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
    $role = privesc_get_int($request, 'role', -1);

    if ($userId <= 0) {
        privesc_json_error('用户编号无效');
    }

    if (!privesc_is_valid_role($role)) {
        privesc_json_error('角色类型无效');
    }

    $targetUser = privesc_fetch_user_by_id($pdo, $userId);
    if (!$targetUser) {
        privesc_json_error('用户不存在', 404);
    }

    privesc_update_user_role($pdo, $userId, $role);

    if ((int) $currentUser['id'] === $userId) {
        $freshCurrentUser = privesc_fetch_user_by_id($pdo, $userId);
        if ($freshCurrentUser) {
            privesc_sync_type_cookie($freshCurrentUser);
        }
    }

    privesc_json_success('角色更新成功');
});
