<?php
/**
 * HeaSec天积安全团队 - PHP Cookie和Session处理示例
 * 版本: v1.0.0
 * 创建日期: 2025-12-30
 * 说明: 演示PHP的Session管理和Cookie使用，包括用户登录、登出、"记住我"功能
 */

// ==================== 数据库配置 ====================
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', 'root');
define('DB_NAME', 'heasec_base');
define('DB_CHARSET', 'utf8mb4');

// ==================== 数据库连接 ====================
$pdo = null;  // 初始化PDO对象为null
try {
    // 构建数据源名称（DSN）
    $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
    // 创建PDO实例，配置错误处理、获取模式和预处理模拟
    $pdo = new PDO($dsn, DB_USER, DB_PASS, array(
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false
    ));
} catch (PDOException $e) {
    die("数据库连接失败: " . $e->getMessage());
}

// ==================== 创建数据表（如果不存在） ====================
$createTableSQL = "
    CREATE TABLE IF NOT EXISTS heasec_server_user (
        id INT AUTO_INCREMENT PRIMARY KEY COMMENT '用户ID，自增主键',
        username VARCHAR(50) NOT NULL UNIQUE COMMENT '用户名，唯一',
        password VARCHAR(255) NOT NULL COMMENT '密码（实际应用应存储哈希值）',
        email VARCHAR(100) COMMENT '邮箱地址',
        status TINYINT DEFAULT 1 COMMENT '状态：1启用 0禁用',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间'
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='PHP服务端语言基础用户表'
";

try {
    $pdo->exec($createTableSQL);
} catch (PDOException $e) {
    die("创建表失败: " . $e->getMessage());
}

// ==================== 初始化测试数据（如果表为空） ====================
try {
    // 查询表中的记录数
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM heasec_server_user");
    $result = $stmt->fetch();

    // 如果表为空，插入测试数据
    if ($result['count'] == 0) {
        // 定义测试用户数组（用户名、密码、邮箱）
        $testUsers = array(
            array('admin', 'admin123', 'admin@heasec.com'),
            array('user', 'user123', 'user@heasec.com'),
            array('test', 'test123', 'test@heasec.com')
        );

        // 准备插入语句，使用问号(?)作为占位符（位置占位符）
        $stmt = $pdo->prepare("INSERT INTO heasec_server_user (username, password, email) VALUES (?, ?, ?)");
        // 循环插入每个测试用户
        foreach ($testUsers as $user) {
            $stmt->execute($user);
        }
    }
} catch (PDOException $e) {
    // 忽略初始化错误（避免重复数据导致的问题）
}

// ==================== 配置会话隔离 ====================
// 原理：通过设置不同的会话Cookie路径，防止会话数据被其他靶场站点访问,此为靶场站点的特殊配置，正常站点无需专门配置，直接从后面的启动Session代码开始即可，会话会默认全局生效。
// 设置会话Cookie的路径为当前靶场目录，确保会话仅在当前靶场下生效
$sessionPath = '/heasecdev/range/base/server/php_sql/';
// 设置会话名称，使用独特的名称避免与其他靶场站点冲突
session_name('PHPSESSID_PHP_SQL');
// 设置会话Cookie的路径参数
session_set_cookie_params(0, $sessionPath);

// ==================== 启动Session ====================
// session_start(): 启动新会话或恢复现有会话
// 必须在任何输出之前调用（包括HTML和空格）
session_start();

// ==================== 处理表单提交 ====================
$message = '';      // 操作结果消息
$messageType = '';  // 消息类型（success/error/info）

// ==================== 登录处理 ====================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $action = $_POST['action'];

    if ($action === 'login') {
        // 获取并清理表单数据
        $username = trim($_POST['username'] ?? '');
        $password = $_POST['password'] ?? '';
        $remember = isset($_POST['remember']);  // 检查是否勾选"记住我"

        // 验证必填字段
        if (empty($username) || empty($password)) {
            $message = "用户名和密码不能为空";
            $messageType = 'error';
        } else {
            // 查询用户信息（仅查询状态为启用的用户）
            $stmt = $pdo->prepare("SELECT id, username, password, email, status FROM heasec_server_user WHERE username = ? AND status = 1 LIMIT 1");
            $stmt->execute(array($username));
            $user = $stmt->fetch();

            // 验证用户名和密码（实际应用中应使用password_verify()验证哈希密码）
            if ($user && $user['password'] === $password) {
                // 登录成功：设置Session变量
                // $_SESSION: 存储会话数据的超全局数组
                $_SESSION['logged_in'] = true;      // 登录状态标记
                $_SESSION['user_id'] = $user['id'];  // 用户ID
                $_SESSION['username'] = $username;   // 用户名
                $_SESSION['email'] = $user['email']; // 邮箱
                $_SESSION['login_time'] = time();    // 登录时间戳

                // 处理"记住我"功能（使用Cookie）
                if ($remember) {
                    // 计算过期时间（7天后）
                    $expiry = time() + (7 * 24 * 60 * 60);
                    // setcookie(): 设置Cookie
                    // 参数1: Cookie名称
                    // 参数2: Cookie值
                    // 参数3: 过期时间（Unix时间戳）
                    // 参数4: 服务器路径（设置为当前靶场路径，确保Cookie仅在当前靶场下生效）
                    setcookie('remember_username', $username, $expiry, $sessionPath);
                    $message = "登录成功！已记住用户名7天";
                } else {
                    $message = "登录成功！";
                }
                $messageType = 'success';

                // 重定向到当前页面，防止表单重复提交
                header('Location: ' . $_SERVER['PHP_SELF']);
                exit;  // 终止脚本执行
            } else {
                $message = "用户名或密码错误";
                $messageType = 'error';
            }
        }
    }

    // ==================== 退出登录 ====================
    elseif ($action === 'logout') {
        // 清空Session数组
        $_SESSION = array();

        // 删除包含Session ID的Cookie
        if (isset($_COOKIE[session_name()])) {
            // session_name(): 获取/设置当前Session名称
            // 通过设置过期时间为过去的时间来删除Cookie
            // 注意：必须使用与设置Cookie时相同的路径参数
            setcookie(session_name(), '', time() - 3600, $sessionPath);
        }

        // 销毁Session所有数据
        session_destroy();

        $message = "已退出登录";
        $messageType = 'info';

        // 重定向到当前页面
        header('Location: ' . $_SERVER['PHP_SELF']);
        exit;
    }
}

// ==================== 检查登录状态 ====================
// 检查Session中的登录状态标记
$isLoggedIn = isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true;
// 获取当前登录用户名，未登录时为null
$currentUser = $_SESSION['username'] ?? null;

// ==================== 更新Session活动时间 ====================
if ($isLoggedIn) {
    // 更新最后活动时间，可用于实现Session超时功能
    $_SESSION['last_activity'] = time();
}
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>PHP Cookie和Session示例</title>
    <link rel="stylesheet" href="css/session.css">
</head>
<body>
    <?php if ($message): ?>
    <div class="message <?php echo $messageType; ?>">
        <?php echo htmlspecialchars($message); ?>
    </div>
    <?php endif; ?>

    <!-- 登录表单 -->
    <?php if (!$isLoggedIn): ?>
    <!-- 未登录时显示登录表单 -->
    <div class="container">
        <h2>🔐 用户登录</h2>
        <form method="post">
            <input type="hidden" name="action" value="login">
            <div class="form-group">
                <label>用户名：</label>
                <!-- $_COOKIE: PHP预定义的超全局变量，包含从客户端发送的Cookie数据 -->
                <!-- 如果Cookie中保存了用户名，自动填充到输入框 -->
                <input type="text" name="username" placeholder="请输入用户名" required
                       value="<?php echo htmlspecialchars($_COOKIE['remember_username'] ?? ''); ?>">
            </div>
            <div class="form-group">
                <label>密码：</label>
                <input type="password" name="password" placeholder="请输入密码" required>
            </div>
            <div class="form-group">
                <div class="checkbox-group">
                    <!-- 如果Cookie中存在remember_username，默认勾选"记住我" -->
                    <input type="checkbox" name="remember" id="remember"
                           <?php echo isset($_COOKIE['remember_username']) ? 'checked' : ''; ?>>
                    <label for="remember" style="margin: 0; font-weight: normal;">记住我（7天）</label>
                </div>
            </div>
            <button type="submit">登录</button>
        </form>
        <div class="info-box">
            <h4>📖 测试账号</h4>
            <ul class="info-list">
                <li>admin / admin123</li>
                <li>user / user123</li>
                <li>test / test123</li>
            </ul>
        </div>
    </div>
    <?php endif; ?>

    <!-- 用户信息展示 -->
    <?php if ($isLoggedIn): ?>
    <!-- 已登录时显示用户信息和Session数据 -->
    <div class="container">
        <h2>👋 欢迎您，<?php echo htmlspecialchars($currentUser); ?>！</h2>
        <div class="info-box">
            <h4>📊 Session信息</h4>
            <ul class="info-list">
                <li>用户ID：<code><?php echo $_SESSION['user_id'] ?? 'N/A'; ?></code></li>
                <li>用户名：<code><?php echo htmlspecialchars($currentUser); ?></code></li>
                <li>邮箱：<code><?php echo htmlspecialchars($_SESSION['email'] ?? 'N/A'); ?></code></li>
                <!-- date(): 格式化Unix时间戳为可读日期 -->
                <li>登录时间：<code><?php echo date('Y-m-d H:i:s', $_SESSION['login_time']); ?></code></li>
                <!-- session_id(): 获取当前Session ID -->
                <li>Session ID：<code><?php echo session_id(); ?></code></li>
            </ul>
        </div>

        <form method="post" style="margin-top: 20px;">
            <input type="hidden" name="action" value="logout">
            <button type="submit" class="btn-logout">退出登录</button>
        </form>
    </div>
    <?php endif; ?>
</body>
</html>
