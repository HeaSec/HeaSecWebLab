<?php
/**
 * HeaSec天积安全团队 - 越权访问综合实战认证辅助
 * 版本: v1.0.0
 * 团队: 天积安全 (HeavenlySecret)
 */

/**
 * 获取登录会话键名。
 *
 * @return string
 */
function privesc_get_auth_session_key()
{
    $config = privesc_get_config();
    return $config['range_code'] . '_auth_user_id';
}

/**
 * 获取当前登录用户ID。
 *
 * @return int
 */
function privesc_get_current_user_id()
{
    $key = privesc_get_auth_session_key();
    return isset($_SESSION[$key]) ? (int) $_SESSION[$key] : 0;
}

/**
 * 设置登录用户。
 *
 * @param array $user 用户信息
 */
function privesc_login_user(array $user)
{
    $_SESSION[privesc_get_auth_session_key()] = (int) $user['id'];
    privesc_sync_type_cookie($user);
}

/**
 * 清理登录状态。
 */
function privesc_logout_user()
{
    unset($_SESSION[privesc_get_auth_session_key()]);
    privesc_set_type_cookie(null);
}

/**
 * 同步类型 Cookie。
 *
 * @param array $user 用户信息
 */
function privesc_sync_type_cookie(array $user)
{
    if ((int) $user['role'] === 2) {
        privesc_set_type_cookie('2');
    } else {
        privesc_set_type_cookie(null);
    }
}

/**
 * 获取当前登录用户。
 *
 * @param PDO $pdo 数据库连接
 * @return array|null
 */
function privesc_get_current_user(PDO $pdo)
{
    $userId = privesc_get_current_user_id();
    if ($userId <= 0) {
        return null;
    }

    $user = privesc_fetch_user_by_id($pdo, $userId);
    if (!$user || (int) $user['status'] !== 1) {
        privesc_logout_user();
        return null;
    }

    return $user;
}

/**
 * 检查是否为管理员。
 *
 * @param array|null $user 用户信息
 * @return bool
 */
function privesc_is_admin_user($user)
{
    return is_array($user) && isset($user['role']) && (int) $user['role'] === 2;
}

/**
 * 导出认证用户数据。
 *
 * @param array $user 用户信息
 * @return array
 */
function privesc_export_auth_user(array $user)
{
    return [
        'user_id' => (int) $user['id'],
        'username' => $user['username'],
        'name' => $user['name'],
        'role' => (int) $user['role'],
        'role_name' => privesc_get_role_name($user['role']),
        'user_hash' => hash('sha256', $user['username']),
    ];
}

/**
 * 导出当前会话状态。
 *
 * @param PDO $pdo 数据库连接
 * @param array|null $user 当前用户
 * @param bool $includeUser 是否包含完整用户信息（true=含详情+地址+用户列表，false=仅基础字段）
 * @return array
 */
function privesc_build_session_state(PDO $pdo, $user = null, $includeUser = true)
{
    if (!is_array($user)) {
        $user = privesc_get_current_user($pdo);
    }

    if (!$user) {
        return [
            'logged_in' => false,
            'user' => null,
            'users' => [],
        ];
    }

    $state = [
        'logged_in' => true,
        'user' => $includeUser ? privesc_build_public_user_info($pdo, $user, true) : [
            'user_id' => (int) $user['id'],
            'username' => $user['username'],
            'role' => (int) $user['role'],
            'role_name' => privesc_get_role_name($user['role']),
        ],
        'users' => [],
    ];

    // 仅在包含完整用户信息时加载用户列表，避免功能API返回冗余数据
    if ($includeUser && privesc_is_admin_user($user)) {
        $state['users'] = privesc_get_user_list($pdo);
    }

    return $state;
}

/**
 * 要求用户已登录。
 *
 * @param PDO $pdo 数据库连接
 * @return array
 */
function privesc_require_login(PDO $pdo)
{
    $user = privesc_get_current_user($pdo);
    if (!$user) {
        privesc_json_error('请先登录', 401);
    }

    return $user;
}

/**
 * 要求用户具备管理员权限。
 *
 * @param PDO $pdo 数据库连接
 * @return array
 */
function privesc_require_admin(PDO $pdo)
{
    $user = privesc_require_login($pdo);
    if (!privesc_is_admin_user($user)) {
        privesc_json_error('权限不足，仅管理员可操作', 403);
    }

    return $user;
}
