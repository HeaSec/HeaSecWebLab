<?php
/**
 * HeaSec天积安全团队 - 会话安全靶场公共函数
 * 版本: v1.0.0
 * 创建日期: 2026-04-01
 * 团队: 天积安全 (HeavenlySecret)
 */

/**
 * 获取当前靶场的Cookie路径
 * 从当前请求URL中动态提取到session/目录的路径
 *
 * @return string Cookie路径
 */
function getRangeCookiePath() {
    $requestPath = $_SERVER['SCRIPT_NAME'];
    $searchPattern = '/session/';
    $pos = strpos($requestPath, $searchPattern);
    if ($pos !== false) {
        return substr($requestPath, 0, $pos + strlen($searchPattern));
    }
    return dirname($requestPath) . '/';
}

/**
 * 自定义会话初始化
 * 根据关卡设置不同的Cookie安全属性
 *
 * @param int $level 关卡编号（1/2/3）
 * @return bool 初始化是否成功
 */
function initRangeSession($level = 1) {
    $sessionName = 'HEASEC_RANGE_SESSION_SESSION';
    $lifetime = 3600;
    $httpOnly = ($level !== 3);

    $cookiePath = getRangeCookiePath();

    // 如果已有活跃会话且名称匹配，直接返回
    if (session_status() === PHP_SESSION_ACTIVE) {
        if (session_name() === $sessionName) {
            if ($level === 3) {
                setcookie($sessionName, session_id(), time() + $lifetime, $cookiePath, '', false, false);
            }
            return true;
        }
        session_write_close();
    }

    // 设置会话名称
    session_name($sessionName);

    // 设置Cookie参数
    session_set_cookie_params([
        'lifetime' => $lifetime,
        'path' => $cookiePath,
        'domain' => '',
        'secure' => false,
        'httponly' => $httpOnly,
        'samesite' => 'Lax'
    ]);

    // 启动会话
    if (!session_start()) {
        return false;
    }

    // 第三关：覆盖可能已存在的cookie属性
    if ($level === 3) {
        setcookie($sessionName, session_id(), time() + $lifetime, $cookiePath, '', false, false);
    }

    return true;
}

/**
 * 生成随机通关密码字符串
 * @return string 20位随机字符串
 */
function generateRandomPasscode() {
    $chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
    $passcode = '';
    for ($i = 0; $i < 20; $i++) {
        $passcode .= $chars[mt_rand(0, strlen($chars) - 1)];
    }
    return $passcode;
}

/**
 * 生成通关密码（仅在首次访问或重置时调用）
 *
 * @param int $level 关卡编号
 * @param PDO $pdo 数据库连接
 * @return string 生成的通关密码
 */
function generatePasscode($level, $pdo) {
    $passcode = generateRandomPasscode();
    $stmt = $pdo->prepare("INSERT INTO heasec_session_passcodes (level, passcode, session_id) VALUES (:level, :passcode, '')");
    $stmt->execute([':level' => $level, ':passcode' => $passcode]);
    return $passcode;
}

/**
 * 获取关卡通关密码（如不存在则自动生成）
 *
 * @param int $level 关卡编号
 * @param PDO $pdo 数据库连接
 * @return string|null 通关密码
 */
function getOrCreatePasscode($level, $pdo) {
    $stmt = $pdo->prepare("SELECT passcode FROM heasec_session_passcodes WHERE level = :level LIMIT 1");
    $stmt->execute([':level' => $level]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($result) {
        return $result['passcode'];
    }
    return generatePasscode($level, $pdo);
}

/**
 * 获取关卡通关密码（不自动生成）
 *
 * @param int $level 关卡编号
 * @param PDO $pdo 数据库连接
 * @return string|null 通关密码或null
 */
function getPasscode($level, $pdo) {
    $stmt = $pdo->prepare("SELECT passcode FROM heasec_session_passcodes WHERE level = :level LIMIT 1");
    $stmt->execute([':level' => $level]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    return $result ? $result['passcode'] : null;
}

/**
 * 获取用户信息（按用户名和关卡）
 *
 * @param int $level 关卡编号
 * @param string $username 用户名
 * @param PDO $pdo 数据库连接
 * @return array|null 用户信息或null
 */
function getUser($level, $username, $pdo) {
    $stmt = $pdo->prepare("SELECT * FROM heasec_session_users WHERE level = ? AND username = ?");
    $stmt->execute([$level, $username]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

/**
 * 获取用户信息（按ID和关卡）
 *
 * @param int $userId 用户ID
 * @param int $level 关卡编号
 * @param PDO $pdo 数据库连接
 * @return array|null 用户信息或null
 */
function getUserById($userId, $level, $pdo) {
    $stmt = $pdo->prepare("SELECT * FROM heasec_session_users WHERE id = ? AND level = ?");
    $stmt->execute([$userId, $level]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

/**
 * 验证通关密码
 *
 * @param string $passcode 待验证的密码
 * @param int $level 关卡编号
 * @param PDO $pdo 数据库连接
 * @return bool 是否验证通过
 */
function verifyPasscode($passcode, $level, $pdo) {
    $storedPasscode = getPasscode($level, $pdo);
    return $storedPasscode !== null && $storedPasscode === $passcode;
}

/**
 * 记录活跃会话（第二关使用）
 *
 * @param int $level 关卡编号
 * @param string $sessionId 会话ID
 * @param string $username 用户名
 * @param string $role 角色
 * @param PDO $pdo 数据库连接
 */
function recordActiveSession($level, $sessionId, $username, $role, $pdo) {
    $stmt = $pdo->prepare("INSERT INTO heasec_session_active_sessions (level, session_id, username, role) VALUES (?, ?, ?, ?)");
    $stmt->execute([$level, $sessionId, $username, $role]);
}

/**
 * 删除活跃会话记录
 *
 * @param int $level 关卡编号
 * @param string $sessionId 会话ID
 * @param PDO $pdo 数据库连接
 */
function removeActiveSession($level, $sessionId, $pdo) {
    $stmt = $pdo->prepare("DELETE FROM heasec_session_active_sessions WHERE level = ? AND session_id = ?");
    $stmt->execute([$level, $sessionId]);
}

/**
 * 记录请求参数（第三关使用）
 *
 * @param int $level 关卡编号
 * @param string $paramType 参数类型（username/sid/url）
 * @param string $paramValue 参数值
 * @param string $sessionId 目标会话ID
 * @param PDO $pdo 数据库连接
 */
function logRequestParam($level, $paramType, $paramValue, $sessionId, $pdo) {
    $stmt = $pdo->prepare("INSERT INTO heasec_session_param_logs (level, param_type, param_value, session_id) VALUES (?, ?, ?, ?)");
    $stmt->execute([$level, $paramType, $paramValue, $sessionId]);
}

/**
 * 检查会话ID是否在参数记录中（第三关使用）
 *
 * @param string $sessionId 会话ID
 * @param int $level 关卡编号
 * @param PDO $pdo 数据库连接
 * @return bool 是否存在记录
 */
function isSessionIdInParamLogs($sessionId, $level, $pdo) {
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM heasec_session_param_logs WHERE session_id = ? AND level = ?");
    $stmt->execute([$sessionId, $level]);
    return $stmt->fetchColumn() > 0;
}

/**
 * 生成随机管理员密码
 *
 * @param int $length 密码长度
 * @return string 随机密码
 */
function generateAdminPassword($length = 10) {
    $chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
    $password = '';
    for ($i = 0; $i < $length; $i++) {
        $password .= $chars[mt_rand(0, strlen($chars) - 1)];
    }
    return $password;
}

/**
 * 生成随机会话ID（32位十六进制字符串）
 *
 * @return string 随机会话ID
 */
function generateRandomSessionId() {
    if (function_exists('random_bytes')) {
        return bin2hex(random_bytes(16));
    }
    return bin2hex(openssl_random_pseudo_bytes(16));
}

/**
 * 发送JSON响应
 *
 * @param bool $success 是否成功
 * @param string $message 消息
 * @param array $data 额外数据
 */
function sendJsonResponse($success, $message, $data = []) {
    header('Content-Type: application/json; charset=utf-8');
    header('X-HeavenlySecret: HeaSec Session Range v1.0.0');

    $response = [
        'success' => $success,
        'message' => $message
    ];

    if (!empty($data)) {
        $response['data'] = $data;
    }

    echo json_encode($response, JSON_UNESCAPED_UNICODE);
    exit;
}

/**
 * 获取请求输入数据（支持JSON和表单）
 * @return array 请求数据
 */
function getRequestData() {
    $input = file_get_contents('php://input');
    $data = json_decode($input, true);
    if (!$data) {
        $data = $_POST;
    }
    return $data ?: [];
}

/**
 * 确保第二关管理员活跃会话存在
 *
 * 检查数据库中是否已有admin的活跃会话记录，如果没有则自动创建一个。
 * 创建过程包含重试机制和写后读回验证，确保session文件和数据库记录一致。
 * 此函数可被reset.php（重置时创建）和debug-sessions.php（兜底自动修复）复用。
 *
 * @param PDO $pdo 数据库连接
 * @param string $cookiePath Cookie路径
 * @return string 管理员会话ID
 */
function ensureAdminSession($pdo, $cookiePath) {
    $level = 2;
    $sessionName = 'HEASEC_RANGE_SESSION_SESSION';
    $maxRetries = 3;

    // 关键：保存当前会话状态，操作完admin会话后必须完全恢复，
    // 否则后续session_start()会错误地打开admin会话而非用户自己的会话
    $originalUseCookies = ini_get('session.use_cookies');
    $originalSessionId = session_id();  // 保存用户原始session ID
    session_write_close();
    ini_set('session.use_cookies', '0');

    try {
        // 先检查数据库中是否已存在admin活跃会话
        $stmt = $pdo->prepare("SELECT session_id FROM heasec_session_active_sessions WHERE level = ? AND role = 'admin' LIMIT 1");
        $stmt->execute([$level]);
        $existingSession = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($existingSession) {
            // 验证已存在的session文件是否仍然有效
            $existingSessionId = $existingSession['session_id'];
            session_write_close();
            ini_set('session.gc_maxlifetime', 3600);
            session_id($existingSessionId);
            session_name($sessionName);
            session_set_cookie_params([
                'lifetime' => 3600,
                'path' => $cookiePath,
                'domain' => '',
                'secure' => false,
                'httponly' => true,
                'samesite' => 'Lax'
            ]);

            if (session_start()
                && isset($_SESSION['session_user_id_level2'])
                && isset($_SESSION['session_logged_in_level2'])
                && $_SESSION['session_logged_in_level2'] === true
                && $_SESSION['session_role_level2'] === 'admin') {
                session_write_close();
                error_log('[HeaSec Session] 管理员会话已存在且有效, sessionId=' . substr($existingSessionId, 0, 8) . '...');
                return $existingSessionId;
            }
            session_write_close();

            // 已有DB记录但session文件无效，删除无效记录
            error_log('[HeaSec Session] 检测到无效的管理员会话记录，将重新创建');
            $stmt = $pdo->prepare("DELETE FROM heasec_session_active_sessions WHERE level = ? AND session_id = ?");
            $stmt->execute([$level, $existingSessionId]);
        }

        // 从数据库获取管理员用户ID
        $stmt = $pdo->prepare("SELECT id FROM heasec_session_users WHERE level = ? AND username = 'admin' LIMIT 1");
        $stmt->execute([$level]);
        $adminUser = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$adminUser) {
            error_log('[HeaSec Session] 致命错误: 管理员用户不存在于数据库中');
            return '';
        }
        $adminUserId = (int)$adminUser['id'];

        // 创建新的管理员会话（带重试和写后验证）
        for ($retry = 0; $retry < $maxRetries; $retry++) {
            $adminSessionId = generateRandomSessionId();

            try {
                session_write_close();
                ini_set('session.gc_maxlifetime', 3600);

                session_id($adminSessionId);
                session_name($sessionName);
                session_set_cookie_params([
                    'lifetime' => 3600,
                    'path' => $cookiePath,
                    'domain' => '',
                    'secure' => false,
                    'httponly' => true,
                    'samesite' => 'Lax'
                ]);

                if (!session_start()) {
                    error_log('[HeaSec Session] session_start() 失败 (重试 ' . ($retry + 1) . "/$maxRetries)");
                    continue;
                }

                $_SESSION['session_user_id_level2'] = $adminUserId;
                $_SESSION['session_username_level2'] = 'admin';
                $_SESSION['session_realname_level2'] = '关莉媛';
                $_SESSION['session_role_level2'] = 'admin';
                $_SESSION['session_logged_in_level2'] = true;

                session_write_close();

                // 写后读回验证
                session_id($adminSessionId);
                session_name($sessionName);
                session_set_cookie_params([
                    'lifetime' => 3600,
                    'path' => $cookiePath,
                    'domain' => '',
                    'secure' => false,
                    'httponly' => true,
                    'samesite' => 'Lax'
                ]);

                if (!session_start()) {
                    error_log('[HeaSec Session] 验证阶段 session_start() 失败 (重试 ' . ($retry + 1) . "/$maxRetries)");
                    continue;
                }

                $validationPassed = isset($_SESSION['session_user_id_level2'])
                    && $_SESSION['session_user_id_level2'] === $adminUserId
                    && isset($_SESSION['session_logged_in_level2'])
                    && $_SESSION['session_logged_in_level2'] === true
                    && isset($_SESSION['session_role_level2'])
                    && $_SESSION['session_role_level2'] === 'admin';

                session_write_close();

                if ($validationPassed) {
                    // 记录到活跃会话数据库
                    $stmt = $pdo->prepare("INSERT INTO heasec_session_active_sessions (level, session_id, username, role) VALUES (?, ?, ?, ?)");
                    $stmt->execute([$level, $adminSessionId, 'admin', 'admin']);

                    error_log('[HeaSec Session] 管理员会话创建成功, sessionId=' . substr($adminSessionId, 0, 8) . '...');
                    return $adminSessionId;
                } else {
                    error_log('[HeaSec Session] 会话数据验证失败 (重试 ' . ($retry + 1) . "/$maxRetries)");
                }
            } catch (Exception $e) {
                error_log('[HeaSec Session] 创建管理员会话异常 (重试 ' . ($retry + 1) . "/$maxRetries): " . $e->getMessage());
                if (session_status() === PHP_SESSION_ACTIVE) {
                    session_write_close();
                }
            }
        }

        error_log('[HeaSec Session] 致命错误: 经过 ' . $maxRetries . ' 次尝试仍无法创建管理员会话');
        return '';
    } finally {
        // 恢复原始会话状态：先关闭当前可能打开的admin会话，再恢复cookie和session_id
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_write_close();
        }
        session_id($originalSessionId);  // 恢复用户原始session ID
        ini_set('session.use_cookies', $originalUseCookies);  // 恢复Cookie发送
    }
}
