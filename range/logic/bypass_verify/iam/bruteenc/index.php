<?php
/**
 * HeaSec天积安全团队 - 暴力破解前端加密靶场（第一关）
 * 版本: v1.0.0
 * 创建日期: 2026-02-25
 * 团队: 天积安全 (HeavenlySecret)
 */

// 设置响应头
header('X-HeavenlySecret: HeaSec BruteEnc Range v1.0.0 - Level 1');
header('Content-Type: text/html; charset=utf-8');

// 设置页面变量
$pageTitle = '暴力破解前端加密靶场 - 第一关';
$rangeName = '暴力破解前端加密';
$showVersion = false;
$showResetButton = true;  // 第一关显示重置按钮
$version = 'v1.0.0';

// 设置公共组件的基础路径
$commonBasePath = '../../../../common/';

// 设置重置功能相关变量
$initSqlFile = 'database/init_database.sql';
$databaseName = 'heasec_logic';
$useDatabase = true;

// 引入公共头部
require_once $commonBasePath . 'includes/header.php';

// 引入数据库组件
require_once $commonBasePath . 'includes/HeaSec_Database.php';

// 引入星星系统组件（用于恭喜弹窗）
require_once $commonBasePath . 'components/star-system/includes/HeaSec_StarSystem.php';

// 定义访问常量
define('HEASEC_RANGE_ACCESS', true);

/**
 * 生成四位随机密码（第一位为字母，后三位为数字）
 *
 * @param int $length 密码长度（默认为4）
 * @return string 生成的密码
 */
function generatePassword($length = 4)
{
    // 第一位：字母（大小写字母）
    $letters = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
    $password = $letters[mt_rand(0, strlen($letters) - 1)];

    // 后三位：数字
    $digits = '0123456789';
    for ($i = 1; $i < $length; $i++) {
        $password .= $digits[mt_rand(0, strlen($digits) - 1)];
    }

    return $password;
}

/**
 * 检查并创建admin用户（第一关）
 *
 * @param PDO $pdo 数据库连接
 * @return array 用户信息数组
 */
function ensureAdminUser($pdo)
{
    // 检查admin用户是否存在（第一关）
    $stmt = $pdo->prepare("SELECT username, password FROM heasec_bruteenc_users WHERE username = 'admin' AND level = 1 LIMIT 1");
    $stmt->execute();
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user) {
        return [
            'username' => $user['username'],
            'password_hash' => $user['password']
        ];
    }

    // 不存在则创建
    $password = generatePassword(4);
    $passwordHash = hash('sha256', $password);  // 使用SHA256哈希存储

    $stmt = $pdo->prepare("INSERT INTO heasec_bruteenc_users (username, password, level, created_at) VALUES (?, ?, 1, NOW())");
    $stmt->execute(['admin', $passwordHash]);

    return [
        'username' => 'admin',
        'password' => $password,
        'password_hash' => $passwordHash
    ];
}

// 获取数据库连接并初始化用户
try {
    $pdo = HeaSec_Database::getConnection('heasec_logic');
    $adminUser = ensureAdminUser($pdo);
} catch (Exception $e) {
    error_log('[HeaSec BruteEnc] Database error: ' . $e->getMessage());
}

// 引入星星系统组件资源
echo HeaSec_StarSystem::renderAssets($commonBasePath, [
    'css' => true,
    'js' => true,
    'congrats' => true
]);
?>

<!-- 引入统一样式文件 -->
<link rel="stylesheet" href="<?php echo $commonBasePath; ?>css/heasec_range.css">
<!-- 引入站点特定样式文件 -->
<link rel="stylesheet" href="css/style.css">

<!-- 引入CryptoJS加密库 -->
<script src="js/lib/crypto-js.min.js"></script>
<!-- 引入第一关加密脚本 -->
<script src="js/level1.js?v=<?php echo $version; ?>"></script>

<!-- 靶场主要内容 -->
<div class="tech-container">
    <!-- 登录表单区域 -->
    <div class="tech-card">
        <div class="tech-card-header">
            <h3>
                <i class="fa fa-sign-in"></i>
                第一关 请尝试登录admin账号
            </h3>
        </div>
        <div class="tech-card-body">
            <!-- 登录表单 -->
            <form id="loginForm" class="tech-form">
                <!-- 用户名输入 -->
                <div class="form-group">
                    <label for="username" class="form-label">
                        <i class="fa fa-user"></i>
                        用户名
                    </label>
                    <input type="text" id="username" name="username" class="tech-input" placeholder="请输入用户名"
                        aria-label="用户名" value="admin">
                </div>

                <!-- 密码输入 -->
                <div class="form-group">
                    <label for="password" class="form-label">
                        <i class="fa fa-lock"></i>
                        密码
                    </label>
                    <input type="password" id="password" name="password" class="tech-input" placeholder="请输入密码"
                        autocomplete="off" aria-label="密码">
                </div>

                <!-- 提交按钮 -->
                <div class="form-actions">
                    <button type="submit" class="tech-btn tech-btn-primary" id="submitBtn">
                        <i class="fa fa-sign-in"></i>
                        登录
                    </button>
                    <a href="level2.php" class="tech-btn tech-btn-success" id="nextBtn" style="display: none;">
                        <i class="fa fa-arrow-right"></i>
                        下一关
                    </a>
                </div>
            </form>

            <!-- 登录结果消息 -->
            <div id="loginResult" class="detection-result" style="display: none; margin-top: 20px;">
                <div class="alert" id="resultAlert">
                    <div>
                        <i id="resultIcon" class="fa"></i>
                        <strong id="resultMessage"></strong>
                    </div>
                    <p class="alert-hint" id="resultHint" style="display: none;">
                        <small>密码是4位字符串（第一位为字母，后三位为数字）</small>
                    </p>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// 页面配置
window.HeaSecPageConfig = {
    level: 1,
    loginApiUrl: 'api/login.php'
};
</script>

<?php
// 引入公共底部
require_once $commonBasePath . 'includes/footer.php';
?>
