<?php
/**
 * HeaSec天积安全团队 - 用户枚举靶场（第一关）
 * 关卡主题：登录安全分析
 * 版本: v1.0.0
 * 创建日期: 2026-02-27
 * 团队: 天积安全 (HeavenlySecret)
 */

// 设置响应头
header('X-HeavenlySecret: HeaSec UserEnum Range v1.0.0 - Level 1');
header('Content-Type: text/html; charset=utf-8');

// 设置页面变量
$pageTitle = '用户枚举靶场 - 第一关';
$rangeName = '用户枚举';
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
 * 确保测试账号和目标账号存在（第一关）
 *
 * @param PDO $pdo 数据库连接
 * @return void
 */
function ensureUsers($pdo)
{
    // 检查测试账号是否存在
    $stmt = $pdo->prepare("SELECT id FROM heasec_userenum_users WHERE username = '13866668888' AND level = 1 LIMIT 1");
    $stmt->execute();
    if (!$stmt->fetch()) {
        // 创建测试账号
        $stmt = $pdo->prepare("INSERT INTO heasec_userenum_users (username, password, level, created_at) VALUES (?, ?, 1, NOW())");
        $stmt->execute(['13866668888', '123456']);
    }

    // 检查目标账号是否存在（1100591XXXX）
    $stmt = $pdo->prepare("SELECT username FROM heasec_userenum_users WHERE username LIKE '1100591%' AND level = 1 LIMIT 1");
    $stmt->execute();
    if (!$stmt->fetch()) {
        // 生成目标账号
        $randomSuffix = str_pad(mt_rand(0, 9999), 4, '0', STR_PAD_LEFT);
        $targetUsername = '1100591' . $randomSuffix;
        $targetPassword = generatePassword(4);

        $stmt = $pdo->prepare("INSERT INTO heasec_userenum_users (username, password, level, created_at) VALUES (?, ?, 1, NOW())");
        $stmt->execute([$targetUsername, $targetPassword]);
    }
}

// 获取数据库连接并初始化用户
try {
    $pdo = HeaSec_Database::getConnection('heasec_logic');
    ensureUsers($pdo);
} catch (Exception $e) {
    error_log('[HeaSec UserEnum] Database error: ' . $e->getMessage());
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

<!-- 引入登录脚本 -->
<script src="js/login.js?v=<?php echo $version; ?>"></script>

<!-- 靶场主要内容 -->
<div class="tech-container">
    <!-- 登录表单区域 -->
    <div class="tech-card">
        <div class="tech-card-header">
            <h3>
                <i class="fa fa-sign-in"></i>
                第一关 请尝试登录除了测试账号以外的账号
            </h3>
        </div>
        <div class="tech-card-body">
            <!-- 登录表单 -->
            <form id="loginForm" class="tech-form">
                <!-- 用户名输入 -->
                <div class="form-group">
                    <label for="username" class="form-label">
                        <i class="fa fa-user"></i>
                        手机号
                    </label>
                    <input type="text" id="username" name="username" class="tech-input" placeholder="请输入手机号"
                        aria-label="手机号">
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
                        <small>目标账号格式：1100591XXXX，密码是4位字符串（第一位为字母，后三位为数字）</small>
                    </p>
                </div>
            </div>

            <!-- 测试账号提示 -->
            <div class="test-account-hint">
                <p>测试账号：<strong>13866668888</strong> / <strong>123456</strong></p>
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
