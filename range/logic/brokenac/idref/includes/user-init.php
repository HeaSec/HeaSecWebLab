<?php
/**
 * HeaSec天积安全团队 - 水平越权基础靶场 - 用户数据初始化
 * 版本: v1.0.0
 * 创建日期: 2026-03-05
 * 团队: 天积安全 (HeavenlySecret)
 */

/**
 * 生成随机密码
 * @param int $length 密码长度
 * @return string 随机密码
 */
function generateRandomPassword($length = 10) {
    $chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
    $password = '';
    for ($i = 0; $i < $length; $i++) {
        $password .= $chars[mt_rand(0, strlen($chars) - 1)];
    }
    return $password;
}

/**
 * 生成随机用户ID（10位字符串）
 * @return string 用户ID
 */
function generateUserId() {
    $chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
    $userId = '';
    for ($i = 0; $i < 10; $i++) {
        $userId .= $chars[mt_rand(0, strlen($chars) - 1)];
    }
    return $userId;
}

/**
 * 生成唯一的数字ID数组
 * @param int $count 数量
 * @param int $min 最小值
 * @param int $max 最大值
 * @return array 数字ID数组
 */
function generateUniqueNumIds($count, $min, $max) {
    $ids = [];
    while (count($ids) < $count) {
        $id = mt_rand($min, $max);
        if (!in_array($id, $ids)) {
            $ids[] = $id;
        }
    }
    return $ids;
}

/**
 * 生成唯一的用户ID数组
 * @param int $count 数量
 * @return array 用户ID数组
 */
function generateUniqueUserIds($count) {
    $ids = [];
    while (count($ids) < $count) {
        $id = generateUserId();
        if (!in_array($id, $ids)) {
            $ids[] = $id;
        }
    }
    return $ids;
}

/**
 * 初始化关卡用户数据
 * @param int $level 关卡编号
 * @param PDO $pdo 数据库连接
 */
function initLevelUsers($level, $pdo) {
    // 检查是否已初始化
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM heasec_idref_users WHERE level = ?");
    $stmt->execute([$level]);
    if ($stmt->fetchColumn() > 0) {
        return; // 已初始化，跳过
    }

    // 生成随机数字ID（1000-9999）
    $numIds = generateUniqueNumIds(10, 1000, 9999);

    // 生成随机用户ID
    $userIds = generateUniqueUserIds(10);

    // 干扰用户数据
    $interferenceNames = ['张三', '李四', '王五', '赵六', '钱七', '孙八', '周九', '吴十'];
    $interferencePhones = ['13805913333', '15966664444', '15966665555', '15966666666',
                          '15966667777', '15966668888', '15966669999', '15966660000'];
    $interferenceIdcards = ['350105203003033333', '350105203004044444', '350105203005055555',
                           '350105203006066666', '350105203007077777', '350105203008088888',
                           '350105203009099999', '350105203010100000'];

    // 生成guanliyuan的通关密码（20位随机字符串）
    $guanliyuanPasscode = '';
    $chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
    for ($i = 0; $i < 20; $i++) {
        $guanliyuanPasscode .= $chars[mt_rand(0, strlen($chars) - 1)];
    }

    // 生成guanliyuan的密码（10位随机字符串）
    $guanliyuanPassword = generateRandomPassword(10);

    // 根据关卡设置不同的管理员手机号
    if ($level == 2) {
        // 第二关：管理员手机号在1380591范围内随机（13805910000-13805919999）
        $guanliyuanPhone = '1380591' . str_pad(mt_rand(0, 9999), 4, '0', STR_PAD_LEFT);
    } else {
        // 第一关和第三关：使用固定手机号
        $guanliyuanPhone = '13900001234';
    }

    // 插入测试用户（test）
    $testPhone = '13805918866';
    $testIdcard = '350105199001011111';
    $stmt = $pdo->prepare("INSERT INTO heasec_idref_users
        (level, account, password, name, phone, idcard, num_id, user_id, passcode)
        VALUES (?, 'test', '123456', '测试用户', ?, ?, ?, ?, NULL)");
    $stmt->execute([$level, $testPhone, $testIdcard, $numIds[0], $userIds[0]]);

    // 插入目标用户（guanliyuan）
    $guanliyuanIdcard = '350105198501012222';
    $stmt = $pdo->prepare("INSERT INTO heasec_idref_users
        (level, account, password, name, phone, idcard, num_id, user_id, passcode)
        VALUES (?, 'guanliyuan', ?, '管理员', ?, ?, ?, ?, ?)");
    $stmt->execute([$level, $guanliyuanPassword, $guanliyuanPhone, $guanliyuanIdcard,
                   $numIds[1], $userIds[1], $guanliyuanPasscode]);

    // 插入8个干扰用户
    for ($i = 0; $i < 8; $i++) {
        $stmt = $pdo->prepare("INSERT INTO heasec_idref_users
            (level, account, password, name, phone, idcard, num_id, user_id, passcode)
            VALUES (?, NULL, NULL, ?, ?, ?, ?, ?, NULL)");
        $stmt->execute([$level, $interferenceNames[$i], $interferencePhones[$i],
                       $interferenceIdcards[$i], $numIds[$i + 2], $userIds[$i + 2]]);
    }
}
