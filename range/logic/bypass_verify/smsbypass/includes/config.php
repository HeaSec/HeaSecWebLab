<?php
/**
 * HeaSec天积安全团队 - 短信验证码绕过靶场配置文件
 * SMS Bypass Range Configuration
 * 版本: v1.0.0
 * 创建日期: 2026-01-21
 * 团队: 天积安全 (HeavenlySecret)
 */


// 定义常量
define('ORIGINAL_PHONE', '11066668888');  // 原始手机号（admin账号绑定的手机号）
define('TARGET_PHONE', '13866668888');    // 目标手机号（攻击者控制的手机号）
define('CODE_EXPIRE_TIME', 300);          // 验证码有效期（5分钟）
define('PHONE_SEPARATOR_PATTERN', '/[,;\n\r\t\f|]+/');  // 手机号分隔符正则表达式

/**
 * 获取篡改方式名称
 *
 * @param string $type 篡改类型
 * @return string 篡改方式名称
 */
function getBypassTypeName($type) {
    $names = [
        'direct_replace' => '直接篡改参数',
        'array_injection' => '多值注入',
        'parameter_pollution' => '参数污染/特殊分隔符注入'
    ];
    return isset($names[$type]) ? $names[$type] : $type;
}

/**
 * 识别篡改方式
 *
 * @param array $params 原始请求参数
 * @return string|null 篡改方式类型或null
 */
function identifyBypassType($params) {
    if (!isset($params['phone'])) {
        return null;
    }

    $phone = $params['phone'];

    // 检查是否是数组（多值注入）
    if (is_array($phone)) {
        // 检查第一个手机号是否为admin的手机号
        if (empty($phone) || $phone[0] !== ORIGINAL_PHONE) {
            return null;
        }
        // 检查是否同时包含原手机号和目标手机号
        if (in_array(ORIGINAL_PHONE, $phone) && in_array(TARGET_PHONE, $phone)) {
            return 'array_injection';
        }
        // 数组中只有目标手机号或其他情况，不符合多值注入的条件
        return null;
    }

    // 检查是否是字符串
    if (is_string($phone)) {
        // 直接篡改：手机号被直接替换为目标手机号
        if ($phone === TARGET_PHONE) {
            return 'direct_replace';
        }

        // 参数污染：包含分隔符
        if (preg_match(PHONE_SEPARATOR_PATTERN, $phone)) {
            $phones = preg_split(PHONE_SEPARATOR_PATTERN, $phone);
            $phones = array_map('trim', $phones);
            // 过滤空值
            $phones = array_filter($phones, function($p) { return $p !== ''; });
            $phones = array_values($phones);

            // 检查第一个手机号是否为admin的手机号
            if (empty($phones) || $phones[0] !== ORIGINAL_PHONE) {
                return null;
            }

            if (in_array(ORIGINAL_PHONE, $phones) && in_array(TARGET_PHONE, $phones)) {
                return 'parameter_pollution';
            }
        }
    }

    return null;
}

/**
 * 检查成就是否已存在
 *
 * @param string $bypassType 篡改类型
 * @return bool 成就是否已存在
 */
function isAchievementExists($bypassType) {
    try {
        $db = heasec_db('heasec_logic');
        $stmt = $db->prepare("SELECT id FROM heasec_smsbypass_records WHERE bypass_type = ?");
        $stmt->execute([$bypassType]);
        return $stmt->fetch() !== false;
    } catch (Exception $e) {
        error_log('[HeaSec] isAchievementExists error: ' . $e->getMessage());
        return false;
    }
}

/**
 * 记录成就
 *
 * @param string $bypassType 篡改类型
 * @return bool 是否成功
 */
function recordAchievement($bypassType) {
    try {
        $db = heasec_db('heasec_logic');
        $sql = "INSERT INTO heasec_smsbypass_records (bypass_type, success_count, last_success_at)
                VALUES (?, 1, NOW())
                ON DUPLICATE KEY UPDATE
                success_count = success_count + 1,
                last_success_at = NOW()";
        $stmt = $db->prepare($sql);
        $stmt->execute([$bypassType]);
        return true;
    } catch (Exception $e) {
        error_log('[HeaSec] recordAchievement error: ' . $e->getMessage());
        return false;
    }
}

/**
 * 解析手机号列表
 * 支持：单个手机号、数组、特殊分隔符分隔的字符串
 *
 * @param mixed $phoneParam 手机号参数
 * @return array 有效手机号列表
 */
function parsePhoneList($phoneParam) {
    $phoneList = [];

    if (is_array($phoneParam)) {
        // 多值注入：手机号是数组
        $phoneList = $phoneParam;
    } elseif (is_string($phoneParam)) {
        // 检查是否包含分隔符
        if (preg_match(PHONE_SEPARATOR_PATTERN, $phoneParam)) {
            // 参数污染：使用分隔符分隔
            $phoneList = preg_split(PHONE_SEPARATOR_PATTERN, $phoneParam);
        } else {
            // 普通字符串
            $phoneList = [$phoneParam];
        }
    }

    // 清理和验证手机号
    $phoneList = array_map('trim', $phoneList);
    $phoneList = array_filter($phoneList, function($p) {
        return preg_match('/^1\d{10}$/', $p);
    });

    return array_values(array_unique($phoneList));
}

/**
 * 保存验证码到数据库
 * 同时将之前的有效验证码设为失效
 *
 * @param string $code 验证码
 * @param array $sentPhones 发送的手机号列表
 * @param array $requestParams 原始请求参数
 * @return bool 是否成功
 */
function saveVerificationCode($code, $sentPhones, $requestParams) {
    try {
        $db = heasec_db('heasec_logic');

        // 将之前的有效验证码设为失效
        $db->exec("UPDATE heasec_smsbypass_codes SET status = 0 WHERE status = 1");

        // 插入新验证码
        $sql = "INSERT INTO heasec_smsbypass_codes (code, sent_phones, request_params, status, created_at)
                VALUES (?, ?, ?, 1, NOW())";
        $stmt = $db->prepare($sql);
        $stmt->execute([
            $code,
            json_encode($sentPhones, JSON_UNESCAPED_UNICODE),
            json_encode($requestParams, JSON_UNESCAPED_UNICODE)
        ]);
        return true;
    } catch (Exception $e) {
        error_log('[HeaSec] saveVerificationCode error: ' . $e->getMessage());
        return false;
    }
}

/**
 * 获取有效的验证码记录
 *
 * @return array|null 验证码记录或null
 */
function getValidVerificationCode() {
    try {
        $db = heasec_db('heasec_logic');
        $sql = "SELECT * FROM heasec_smsbypass_codes
                WHERE status = 1
                AND created_at > DATE_SUB(NOW(), INTERVAL ? SECOND)
                ORDER BY id DESC LIMIT 1";
        $stmt = $db->prepare($sql);
        $stmt->execute([CODE_EXPIRE_TIME]);
        $record = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($record) {
            // 解析JSON字段
            $record['sent_phones'] = json_decode($record['sent_phones'], true) ?: [];
            $record['request_params'] = json_decode($record['request_params'], true) ?: [];
        }

        return $record ?: null;
    } catch (Exception $e) {
        error_log('[HeaSec] getValidVerificationCode error: ' . $e->getMessage());
        return null;
    }
}

/**
 * 使验证码失效
 *
 * @param int $id 验证码记录ID
 * @return bool 是否成功
 */
function invalidateVerificationCode($id) {
    try {
        $db = heasec_db('heasec_logic');
        $stmt = $db->prepare("UPDATE heasec_smsbypass_codes SET status = 0 WHERE id = ?");
        $stmt->execute([$id]);
        return true;
    } catch (Exception $e) {
        error_log('[HeaSec] invalidateVerificationCode error: ' . $e->getMessage());
        return false;
    }
}
?>
