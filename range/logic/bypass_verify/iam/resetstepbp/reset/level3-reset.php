<?php
/**
 * HeaSec天积安全团队 - 密码重置流程绕过靶场 - 第三关密码重置确认页面
 * 版本: v1.1.0
 * 创建日期: 2026-04-01
 * 团队: 天积安全 (HeavenlySecret)
 *
 * 该页面通过短信中的重置链接在浏览器中独立打开
 * 用于正常流程中的密码重置确认
 */

// 设置响应头
header('X-HeavenlySecret: HeaSec 密码重置流程绕过 Range v1.1.0');
header('Content-Type: text/html; charset=utf-8');

// 设置公共组件基础路径（从reset/目录到range/common/）
$commonBasePath = '../../../../../common/';

// 定义访问常量
define('HEASEC_RANGE_ACCESS', true);

// 设置页面变量
$pageTitle = '重置密码';
$rangeName = '密码重置流程绕过';
$showVersion = false;
$showResetButton = false;
$showSmsSimulator = false;

// 设置重置功能相关变量
$initSqlFile = '../database/init_database.sql';
$databaseName = 'heasec_logic';
$useDatabase = true;

// 引入会话管理组件
require_once $commonBasePath . 'includes/session_manager.php';

// 初始化靶场会话
HeaSec_InitRangeSession('resetstepbp');

// 验证会话完整性
HeaSec_ValidateSession();

// 引入公共头部
require_once $commonBasePath . 'includes/header.php';

// 引入数据库组件
require_once $commonBasePath . 'includes/HeaSec_Database.php';

// 获取token参数
$token = isset($_GET['token']) ? trim($_GET['token']) : '';
$tokenValid = false;
$tokenError = '';
$username = '';

// 检查token是否有效
if (!empty($token)) {
    $pdo = HeaSec_Database::getConnection('heasec_logic');
    $stmt = $pdo->prepare("SELECT * FROM heasec_resetstepbp_reset_tokens WHERE token = ? AND used = 0 AND expires_at > NOW()");
    $stmt->execute([$token]);
    $tokenRecord = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($tokenRecord) {
        $tokenValid = true;
        $username = $tokenRecord['username'];
    } else {
        $tokenError = '链接无效或已过期';
    }
} else {
    $tokenError = '缺少重置令牌';
}

// 处理密码重置提交
$resetSuccess = false;
$resetError = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $tokenValid) {
    $newPassword = isset($_POST['password']) ? trim($_POST['password']) : '';
    $confirmPassword = isset($_POST['confirm_password']) ? trim($_POST['confirm_password']) : '';

    if (empty($newPassword) || empty($confirmPassword)) {
        $resetError = '请填写完整信息';
    } elseif ($newPassword !== $confirmPassword) {
        $resetError = '两次密码不一致';
    } else {
        $pdo = HeaSec_Database::getConnection('heasec_logic');

        // 更新用户密码
        $stmt = $pdo->prepare("UPDATE heasec_resetstepbp_users SET password = ? WHERE level = 3 AND username = ?");
        $stmt->execute([$newPassword, $username]);

        // 将token标记为已使用
        $stmt = $pdo->prepare("UPDATE heasec_resetstepbp_reset_tokens SET used = 1 WHERE token = ?");
        $stmt->execute([$token]);

        $resetSuccess = true;
    }
}
?>

<!-- 引入统一样式文件 -->
<link rel="stylesheet" href="<?php echo $commonBasePath; ?>css/heasec_range.css">
<link rel="stylesheet" href="../css/style.css">

<!-- 重置密码页面内容 -->
<div class="tech-container">
    <div class="tech-card">
        <div class="tech-card-header">
            <h3>
                <i class="fa fa-key"></i> 重置密码
            </h3>
        </div>
        <div class="tech-card-body">
            <?php if ($resetSuccess): ?>
                <!-- 重置成功提示 -->
                <div class="alert-success">
                    <i class="fa fa-check-circle"></i>
                    <span>密码重置成功！</span>
                </div>
                <div class="form-actions">
                    <a href="../resetstepbp3.php" class="tech-btn tech-btn-primary">
                        <i class="fa fa-arrow-left"></i> 返回登录
                    </a>
                </div>
            <?php elseif (!empty($tokenError)): ?>
                <!-- token无效提示 -->
                <div class="alert-error">
                    <i class="fa fa-exclamation-triangle"></i>
                    <span><?php echo htmlspecialchars($tokenError); ?></span>
                </div>
            <?php else: ?>
                <!-- 重置密码表单 -->
                <form method="POST" class="tech-form">
                    <?php if (!empty($resetError)): ?>
                        <div class="alert-error" style="margin-bottom: 15px;">
                            <i class="fa fa-exclamation-triangle"></i>
                            <span><?php echo htmlspecialchars($resetError); ?></span>
                        </div>
                    <?php endif; ?>
                    <div class="form-group">
                        <label for="password" class="form-label">
                            <i class="fa fa-lock"></i> 新密码
                        </label>
                        <input type="password" id="password" name="password" class="tech-input" placeholder="请输入新密码"
                            autocomplete="off" required>
                    </div>
                    <div class="form-group">
                        <label for="confirm_password" class="form-label">
                            <i class="fa fa-lock"></i> 确认密码
                        </label>
                        <input type="password" id="confirm_password" name="confirm_password" class="tech-input"
                            placeholder="请再次输入新密码" autocomplete="off" required>
                    </div>
                    <div class="form-actions">
                        <button type="submit" class="tech-btn tech-btn-primary">
                            <i class="fa fa-check"></i> 提交
                        </button>
                    </div>
                </form>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php
// 引入公共底部
require_once $commonBasePath . 'includes/footer.php';
?>
