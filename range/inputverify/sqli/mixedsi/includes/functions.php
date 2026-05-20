<?php
/**
 * HeaSec天积安全团队 - SQL注入综合实战靶场 - 公共函数库
 * 版本: v1.0.0
 * 团队: 天积安全 (HeavenlySecret)
 */

if (!defined('HEASEC_RANGE_ACCESS')) {
    define('HEASEC_RANGE_ACCESS', true);
}

/**
 * 发送JSON响应
 * @param bool $success 是否成功
 * @param string $message 消息
 * @param array $data 附加数据
 */
function sendJsonResponse($success, $message, $data = []) {
    header('Content-Type: application/json; charset=utf-8');
    header('X-HeavenlySecret: HeaSec MixedSI Range v1.0.0');
    $response = ['success' => $success, 'message' => $message];
    if (!empty($data)) {
        $response['data'] = $data;
    }
    echo json_encode($response, JSON_UNESCAPED_UNICODE);
    exit;
}

// ===== 第一关安全过滤器 =====

/**
 * 第一关安全过滤器 — 空格过滤
 * 拦截所有空白字符
 */
function filterLevel1_spaces($input) {
    if (preg_match('/\s/', $input)) {
        return false;
    }
    return true;
}

/**
 * 第一关安全过滤器 — 注释符过滤
 * 拦截：--、#
 */
function filterLevel1_comments($input) {
    if (strpos($input, '--') !== false || strpos($input, '#') !== false) {
        return false;
    }
    return true;
}

/**
 * 第一关安全过滤器 — 关键字过滤
 * 拦截：INFORMATION_SCHEMA（大小写不敏感）
 */
function filterLevel1_keywords($input) {
    if (preg_match('/INFORMATION_SCHEMA/i', $input)) {
        return false;
    }
    return true;
}

// ===== 第二关安全过滤器 =====

/**
 * 第二关安全过滤器 — 关键字+函数名过滤
 * 拦截关键字（带空格上下文匹配），使用内联注释可绕过
 * EXTRACTVALUE/UPDATEXML函数名始终拦截（MySQL函数名不可拆分）
 */
function filterLevel2_keywords($input) {
    // 关键字后跟空格（使用/**/替代空格可绕过）
    if (preg_match('/\bSELECT\s|\bUNION\s|\bWHERE\s|\bLIMIT\s/i', $input)) {
        return false;
    }
    // 关键字前后有空格或跟括号（使用/**/替代空格可绕过）
    if (preg_match('/\sFROM\s|\sFROM\(|\sAND\s|\sAND\(|\sOR\s|\sOR\(/i', $input)) {
        return false;
    }
    // INFORMATION_SCHEMA后跟.或空格（使用/**/替代.或空格可绕过）
    if (preg_match('/INFORMATION_SCHEMA[\s.]/i', $input)) {
        return false;
    }
    // EXTRACTVALUE/UPDATEXML函数名始终拦截
    if (preg_match('/\bEXTRACTVALUE\b|\bUPDATEXML\b/i', $input)) {
        return false;
    }
    return true;
}

/**
 * 第二关安全过滤器 — 符号过滤
 * 拦截：=、'、"
 */
function filterLevel2_symbols($input) {
    if (strpos($input, '=') !== false ||
        strpos($input, "'") !== false ||
        strpos($input, '"') !== false) {
        return false;
    }
    return true;
}

// ===== 第三关安全过滤器 =====

/**
 * 第三关安全过滤器 — 逗号过滤
 */
function filterLevel3_comma($input) {
    if (strpos($input, ',') !== false) {
        return false;
    }
    return true;
}

/**
 * 第三关安全过滤器 — 比较符号过滤
 * 拦截：=、>、<、LIKE、REGEXP、RLIKE、IN、BETWEEN
 */
function filterLevel3_comparison($input) {
    $pattern = '/=|>|<|\bLIKE\b|\bREGEXP\b|\bRLIKE\b|\bIN\b|\bBETWEEN\b/i';
    if (preg_match($pattern, $input)) {
        return false;
    }
    return true;
}

/**
 * 第三关安全过滤器 — 条件判断函数过滤
 * 拦截：IF(、CASE、WHEN
 */
function filterLevel3_conditional($input) {
    $pattern = '/IF\(|CASE|WHEN/i';
    if (preg_match($pattern, $input)) {
        return false;
    }
    return true;
}

/**
 * 第三关安全过滤器 — 逻辑运算符过滤
 * 拦截：AND、OR（使用词边界）
 */
function filterLevel3_logicops($input) {
    $pattern = '/\bAND\b|\bOR\b/i';
    if (preg_match($pattern, $input)) {
        return false;
    }
    return true;
}

/**
 * 第三关安全过滤器 — 注释符过滤
 * 拦截：--、#、多行注释符
 */
function filterLevel3_comments($input) {
    if (strpos($input, '--') !== false ||
        strpos($input, '#') !== false ||
        strpos($input, '/*') !== false ||
        strpos($input, '*/') !== false) {
        return false;
    }
    return true;
}

/**
 * 第三关安全过滤器 — 空白字符过滤
 * 拦截：空格、制表符、换行、回车、垂直制表符
 * 不拦截：换页符(0x0c)
 */
function filterLevel3_spaces($input) {
    if (preg_match('/[ \t\n\r\x0b]/', $input)) {
        return false;
    }
    return true;
}

// ===== 密码管理函数 =====

/**
 * 确保指定关卡的通关密码已生成
 *
 * L1：生成 config/secret.php + 更新 admin 密码
 * L2：更新数据库表 heasec_mixedsi_secret
 * L3：生成 config/secret3.php
 *
 * @param int $level 关卡编号（1/2/3）
 */
function ensurePasswordExists($level) {
    if ($level === 2) {
        try {
            require_once __DIR__ . '/../../../../common/includes/HeaSec_Database.php';
            $pdo = HeaSec_Database::getConnection('heasec_sqli');
            $stmt = $pdo->query("SELECT secret_value FROM heasec_mixedsi_secret WHERE secret_key = 'level2_passcode' LIMIT 1");
            $row = $stmt ? $stmt->fetch(PDO::FETCH_ASSOC) : null;
            if ($row && $row['secret_value'] !== '__PLACEHOLDER__' && $row['secret_value'] !== '') {
                return;
            }
            $password = generateRandomString(20);
            $stmt = $pdo->prepare("UPDATE heasec_mixedsi_secret SET secret_value = ? WHERE secret_key = 'level2_passcode'");
            $stmt->execute([$password]);
        } catch (Exception $e) {
            // 静默处理
        }
        return;
    }

    if ($level === 1) {
        // L1：生成通关密码文件
        $secretFile = __DIR__ . '/../config/secret.php';
        $config = file_exists($secretFile) ? include($secretFile) : [];
        if (empty($config['level1_pass'])) {
            $password = generateRandomString(20);
            $content = "<?php\nreturn [\n    'level1_pass' => '" . addslashes($password) . "',\n];\n";
            file_put_contents($secretFile, $content, LOCK_EX);
        }

        // 生成admin用户密码
        try {
            require_once __DIR__ . '/../../../../common/includes/HeaSec_Database.php';
            $pdo = HeaSec_Database::getConnection('heasec_sqli');
            $stmt = $pdo->query("SELECT password FROM heasec_mixedsi_users WHERE username = 'admin' LIMIT 1");
            $row = $stmt ? $stmt->fetch(PDO::FETCH_ASSOC) : null;
            if ($row && ($row['password'] === '__PLACEHOLDER__' || $row['password'] === '')) {
                $adminPass = generateRandomString(10);
                $stmt = $pdo->prepare("UPDATE heasec_mixedsi_users SET password = ? WHERE username = 'admin'");
                $stmt->execute([$adminPass]);
            }
        } catch (Exception $e) {
            // 静默处理
        }
        return;
    }

    // L3：文件存储
    if ($level === 3) {
        $secretFile = __DIR__ . '/../config/secret3.php';
        $config = file_exists($secretFile) ? include($secretFile) : [];
        if (!empty($config['level3_pass'])) {
            return;
        }
        $password = generateRandomString(10);
        $content = "<?php\nreturn [\n    'level3_pass' => '" . addslashes($password) . "',\n];\n";
        file_put_contents($secretFile, $content, LOCK_EX);
    }
}

/**
 * 生成指定长度的随机字符串
 * @param int $length 字符串长度
 * @return string 随机字符串
 */
function generateRandomString($length = 20) {
    $chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
    $str = '';
    for ($i = 0; $i < $length; $i++) {
        $str .= $chars[mt_rand(0, strlen($chars) - 1)];
    }
    return $str;
}

/**
 * 获取指定关卡的通关密码
 *
 * @param int $level 关卡编号
 * @return string|false 密码字符串或失败时返回false
 */
function getPasscode($level) {
    if ($level === 2) {
        try {
            require_once __DIR__ . '/../../../../common/includes/HeaSec_Database.php';
            $pdo = HeaSec_Database::getConnection('heasec_sqli');
            $stmt = $pdo->query("SELECT secret_value FROM heasec_mixedsi_secret WHERE secret_key = 'level2_passcode' LIMIT 1");
            $row = $stmt ? $stmt->fetch(PDO::FETCH_ASSOC) : null;
            if ($row && $row['secret_value'] !== '__PLACEHOLDER__') {
                return $row['secret_value'];
            }
        } catch (Exception $e) {
            return false;
        }
        return false;
    }

    $fileNameMap = [1 => 'secret.php', 3 => 'secret3.php'];
    $varNameMap = [1 => 'level1_pass', 3 => 'level3_pass'];

    $secretFile = __DIR__ . '/../config/' . ($fileNameMap[$level] ?? '');
    if (!$secretFile) return false;
    $config = file_exists($secretFile) ? include($secretFile) : [];
    $configKey = $varNameMap[$level] ?? '';

    return (!empty($config[$configKey])) ? $config[$configKey] : false;
}
