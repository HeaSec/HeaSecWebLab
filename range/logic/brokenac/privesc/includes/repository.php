<?php
/**
 * HeaSec天积安全团队 - 越权访问综合实战数据库与业务辅助
 * 版本: v1.0.0
 * 团队: 天积安全 (HeavenlySecret)
 */

/**
 * 获取数据库连接。
 *
 * @return PDO
 */
function privesc_get_pdo()
{
    $config = privesc_get_config();
    return HeaSec_Database::getConnection($config['database']);
}

/**
 * 获取用户表中全部用户数量。
 *
 * @param PDO $pdo 数据库连接
 * @return int
 */
function privesc_get_user_count(PDO $pdo)
{
    $table = privesc_table('users');
    $stmt = $pdo->query("SELECT COUNT(*) FROM {$table}");
    return (int) $stmt->fetchColumn();
}

/**
 * 通过 ID 获取用户。
 *
 * @param PDO $pdo 数据库连接
 * @param int $userId 用户ID
 * @return array|null
 */
function privesc_fetch_user_by_id(PDO $pdo, $userId)
{
    $table = privesc_table('users');
    $stmt = $pdo->prepare("SELECT * FROM {$table} WHERE id = ? LIMIT 1");
    $stmt->execute([(int) $userId]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    return $user ?: null;
}

/**
 * 通过用户名获取用户。
 *
 * @param PDO $pdo 数据库连接
 * @param string $username 用户名
 * @return array|null
 */
function privesc_fetch_user_by_username(PDO $pdo, $username)
{
    $table = privesc_table('users');
    $stmt = $pdo->prepare("SELECT * FROM {$table} WHERE username = ? LIMIT 1");
    $stmt->execute([$username]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    return $user ?: null;
}

/**
 * 通过手机号获取用户。
 *
 * @param PDO $pdo 数据库连接
 * @param string $phone 手机号
 * @return array|null
 */
function privesc_fetch_user_by_phone(PDO $pdo, $phone)
{
    $table = privesc_table('users');
    $stmt = $pdo->prepare("SELECT * FROM {$table} WHERE phone = ? LIMIT 1");
    $stmt->execute([$phone]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    return $user ?: null;
}

/**
 * 通过用户哈希获取用户。
 *
 * @param PDO $pdo 数据库连接
 * @param string $userHash 用户哈希
 * @return array|null
 */
function privesc_fetch_user_by_hash(PDO $pdo, $userHash)
{
    $table = privesc_table('users');
    $stmt = $pdo->prepare("SELECT * FROM {$table}");
    $stmt->execute();
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($users as $user) {
        if (hash('sha256', $user['username']) === $userHash) {
            return $user;
        }
    }

    return null;
}

/**
 * 获取地址记录。
 *
 * @param PDO $pdo 数据库连接
 * @param int $userId 用户ID
 * @return array|null
 */
function privesc_fetch_address_by_user_id(PDO $pdo, $userId)
{
    $table = privesc_table('addresses');
    $stmt = $pdo->prepare("SELECT * FROM {$table} WHERE user_id = ? LIMIT 1");
    $stmt->execute([(int) $userId]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    return $row ?: null;
}

/**
 * 根据地址标识查询地址。
 *
 * @param PDO $pdo 数据库连接
 * @param string $addressId 地址标识
 * @return array|null
 */
function privesc_fetch_address_by_address_id(PDO $pdo, $addressId)
{
    $table = privesc_table('addresses');
    $stmt = $pdo->prepare("SELECT * FROM {$table} WHERE address_id = ? LIMIT 1");
    $stmt->execute([$addressId]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    return $row ?: null;
}

/**
 * 组装用户展示信息。
 *
 * @param PDO $pdo 数据库连接
 * @param array $user 用户信息
 * @param bool $includeUserHash 是否包含当前用户哈希
 * @return array
 */
function privesc_build_public_user_info(PDO $pdo, array $user, $includeUserHash = false)
{
    $address = privesc_fetch_address_by_user_id($pdo, $user['id']);

    $data = [
        'user_id' => (int) $user['id'],
        'username' => $user['username'],
        'name' => $user['name'],
        'phone' => $user['phone'],
        'role' => (int) $user['role'],
        'role_name' => privesc_get_role_name($user['role']),
        'status' => (int) $user['status'] === 1 ? 1 : 0,
        'status_name' => (int) $user['status'] === 1 ? '正常' : '停用',
        'avatar' => $user['avatar'],
        'avatar_url' => privesc_get_avatar_url($user['avatar']),
        'address_id' => $address ? $address['address_id'] : '',
        'address' => $address ? $address['address'] : '',
    ];

    if ($includeUserHash) {
        $data['user_hash'] = hash('sha256', $user['username']);
    }

    return $data;
}

/**
 * 生成下一个地址编号。
 *
 * @param PDO $pdo 数据库连接
 * @return string
 */
function privesc_generate_next_address_id(PDO $pdo)
{
    $table = privesc_table('addresses');
    $stmt = $pdo->query("SELECT address_id FROM {$table} ORDER BY id DESC LIMIT 1");
    $lastAddressId = $stmt->fetchColumn();

    $number = 1;
    if ($lastAddressId && preg_match('/^ADDR_(\d{4})$/', $lastAddressId, $matches)) {
        $number = (int) $matches[1] + 1;
    }

    if ($number > 9999) {
        $number = 9999;
    }

    return 'ADDR_' . str_pad((string) $number, 4, '0', STR_PAD_LEFT);
}

/**
 * 创建地址记录。
 *
 * @param PDO $pdo 数据库连接
 * @param int $userId 用户ID
 * @param string $address 地址内容
 * @return string
 */
function privesc_create_address(PDO $pdo, $userId, $address)
{
    $table = privesc_table('addresses');
    $addressId = privesc_generate_next_address_id($pdo);
    $stmt = $pdo->prepare("INSERT INTO {$table} (address_id, user_id, address) VALUES (?, ?, ?)");
    $stmt->execute([$addressId, (int) $userId, $address]);
    return $addressId;
}

/**
 * 创建用户。
 *
 * @param PDO $pdo 数据库连接
 * @param array $data 用户数据
 * @return int
 */
function privesc_create_user(PDO $pdo, array $data)
{
    $table = privesc_table('users');
    $stmt = $pdo->prepare("INSERT INTO {$table} (username, password, name, phone, role, status, avatar) VALUES (?, ?, ?, ?, ?, ?, ?)");
    $stmt->execute([
        $data['username'],
        $data['password'],
        $data['name'],
        $data['phone'],
        (int) $data['role'],
        isset($data['status']) ? (int) $data['status'] : 1,
        isset($data['avatar']) ? $data['avatar'] : null,
    ]);

    return (int) $pdo->lastInsertId();
}

/**
 * 更新当前用户资料。
 *
 * @param PDO $pdo 数据库连接
 * @param int $userId 用户ID
 * @param string $name 姓名
 * @param string $phone 手机号
 * @param int|null $role 角色
 */
function privesc_update_profile(PDO $pdo, $userId, $name, $phone, $role = null)
{
    $table = privesc_table('users');

    if ($role === null) {
        $stmt = $pdo->prepare("UPDATE {$table} SET name = ?, phone = ? WHERE id = ?");
        $stmt->execute([$name, $phone, (int) $userId]);
        return;
    }

    $stmt = $pdo->prepare("UPDATE {$table} SET name = ?, phone = ?, role = ? WHERE id = ?");
    $stmt->execute([$name, $phone, (int) $role, (int) $userId]);
}

/**
 * 更新密码。
 *
 * @param PDO $pdo 数据库连接
 * @param int $userId 用户ID
 * @param string $password 新密码
 */
function privesc_update_password(PDO $pdo, $userId, $password)
{
    $table = privesc_table('users');
    $stmt = $pdo->prepare("UPDATE {$table} SET password = ? WHERE id = ?");
    $stmt->execute([$password, (int) $userId]);
}

/**
 * 更新地址。
 *
 * @param PDO $pdo 数据库连接
 * @param string $addressId 地址标识
 * @param string $address 地址内容
 */
function privesc_update_address(PDO $pdo, $addressId, $address)
{
    $table = privesc_table('addresses');
    $stmt = $pdo->prepare("UPDATE {$table} SET address = ? WHERE address_id = ?");
    $stmt->execute([$address, $addressId]);
}

/**
 * 更新头像文件名。
 *
 * @param PDO $pdo 数据库连接
 * @param int $userId 用户ID
 * @param string|null $filename 文件名
 */
function privesc_update_avatar(PDO $pdo, $userId, $filename)
{
    $table = privesc_table('users');
    $stmt = $pdo->prepare("UPDATE {$table} SET avatar = ? WHERE id = ?");
    $stmt->execute([$filename, (int) $userId]);
}

/**
 * 按文件名批量清空头像引用。
 *
 * @param PDO $pdo 数据库连接
 * @param string $filename 文件名
 */
function privesc_clear_avatar_references(PDO $pdo, $filename)
{
    $table = privesc_table('users');
    $stmt = $pdo->prepare("UPDATE {$table} SET avatar = NULL WHERE avatar = ?");
    $stmt->execute([$filename]);
}

/**
 * 获取管理员用户列表。
 *
 * @param PDO $pdo 数据库连接
 * @return array
 */
function privesc_get_user_list(PDO $pdo)
{
    $table = privesc_table('users');
    $stmt = $pdo->query("SELECT * FROM {$table} ORDER BY id ASC");
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $result = [];

    foreach ($users as $user) {
        $result[] = privesc_build_public_user_info($pdo, $user);
    }

    return $result;
}

/**
 * 更新用户角色。
 *
 * @param PDO $pdo 数据库连接
 * @param int $userId 用户ID
 * @param int $role 新角色
 */
function privesc_update_user_role(PDO $pdo, $userId, $role)
{
    $table = privesc_table('users');
    $stmt = $pdo->prepare("UPDATE {$table} SET role = ? WHERE id = ?");
    $stmt->execute([(int) $role, (int) $userId]);
}

/**
 * 切换用户状态。
 *
 * @param PDO $pdo 数据库连接
 * @param int $userId 用户ID
 * @return int
 */
function privesc_toggle_user_status(PDO $pdo, $userId)
{
    $user = privesc_fetch_user_by_id($pdo, $userId);
    if (!$user) {
        return -1;
    }

    $newStatus = (int) $user['status'] === 1 ? 0 : 1;
    $table = privesc_table('users');
    $stmt = $pdo->prepare("UPDATE {$table} SET status = ? WHERE id = ?");
    $stmt->execute([$newStatus, (int) $userId]);

    return $newStatus;
}

/**
 * 删除用户。
 *
 * @param PDO $pdo 数据库连接
 * @param int $userId 用户ID
 * @return array
 */
function privesc_delete_user(PDO $pdo, $userId)
{
    $user = privesc_fetch_user_by_id($pdo, $userId);
    if (!$user) {
        return ['deleted' => false, 'avatar' => ''];
    }

    $addressTable = privesc_table('addresses');
    $userTable = privesc_table('users');

    $stmt = $pdo->prepare("DELETE FROM {$addressTable} WHERE user_id = ?");
    $stmt->execute([(int) $userId]);

    $stmt = $pdo->prepare("DELETE FROM {$userTable} WHERE id = ?");
    $stmt->execute([(int) $userId]);

    return [
        'deleted' => true,
        'avatar' => (string) $user['avatar'],
    ];
}

/**
 * 删除头像文件。
 *
 * @param string $filename 文件名
 * @return bool
 */
function privesc_delete_avatar_file($filename)
{
    if (!privesc_is_valid_avatar_filename($filename)) {
        return false;
    }

    $path = privesc_get_avatar_directory() . basename($filename);
    if (!is_file($path)) {
        return false;
    }

    return unlink($path);
}
