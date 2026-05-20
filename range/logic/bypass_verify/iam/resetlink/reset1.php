<?php
/**
 * HeaSec天积安全团队 - 密码重置凭证可猜测靶场 - 第一关密码重置页面
 * 版本: v1.0.0
 * 创建日期: 2026-01-22
 * 团队: 天积安全 (HeavenlySecret)
 */

header('X-HeavenlySecret: HeaSec 密码重置凭证可猜测 Range v1.0.0');
header('Content-Type: text/html; charset=utf-8');

$commonBasePath = '../../../../common/';
require_once $commonBasePath . 'includes/header.php';
require_once $commonBasePath . 'includes/HeaSec_Database.php';

$uid = $_GET['uid'] ?? '';
$valid = false;
$targetUser = null;

if ($uid) {
    $pdo = HeaSec_Database::getConnection('heasec_logic');
    $stmt = $pdo->prepare("SELECT * FROM heasec_resetlink_users WHERE level = 1 AND user_id = ?");
    $stmt->execute([$uid]);
    $targetUser = $stmt->fetch(PDO::FETCH_ASSOC);
    $valid = ($targetUser !== false);
}

// 处理密码重置请求
$resetSuccess = false;
$resetError = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'reset_password' && $valid) {
    $newPassword = isset($_POST['new_password']) ? trim($_POST['new_password']) : '';
    $confirmPassword = isset($_POST['confirm_password']) ? trim($_POST['confirm_password']) : '';

    if (empty($newPassword)) {
        $resetError = '新密码不能为空';
    } elseif ($newPassword !== $confirmPassword) {
        $resetError = '两次输入的密码不一致，请重新输入';
    } else {
        $pdo = HeaSec_Database::getConnection('heasec_logic');
        $stmt = $pdo->prepare("UPDATE heasec_resetlink_users SET password = ? WHERE level = 1 AND user_id = ?");
        $stmt->execute([$newPassword, $uid]);
        $resetSuccess = true;
    }
}

$pageTitle = '重置密码';
$rangeName = '密码重置凭证可猜测';
?>
<link rel="stylesheet" href="<?php echo $commonBasePath; ?>css/heasec_range.css">
<link rel="stylesheet" href="css/style.css">

<div class="tech-container">
    <div class="tech-card">
        <div class="tech-card-header">
            <h3><i class="fa fa-key"></i> 重置密码</h3>
        </div>
        <div class="tech-card-body">
            <?php if ($valid && $targetUser): ?>
                <?php if ($resetSuccess): ?>
                <div class="alert-success">
                    <i class="fa fa-check-circle"></i>
                    <span>密码重置成功！请使用新密码登录。</span>
                </div>
                <div class="form-actions">
                    <a href="index.php" class="tech-btn tech-btn-primary">
                        <i class="fa fa-arrow-left"></i> 返回登录
                    </a>
                </div>
                <?php else: ?>
                <form method="POST" class="tech-form">
                    <input type="hidden" name="action" value="reset_password">
                    <input type="hidden" name="uid" value="<?php echo htmlspecialchars($uid); ?>">
                    <?php if ($resetError): ?>
                    <div class="alert-error" style="margin-bottom: 15px;">
                        <i class="fa fa-exclamation-triangle"></i>
                        <span><?php echo htmlspecialchars($resetError); ?></span>
                    </div>
                    <?php endif; ?>
                    <div class="form-group">
                        <label>账号</label>
                        <input type="text" class="tech-input" value="<?php echo htmlspecialchars($targetUser['username']); ?>" readonly>
                    </div>
                    <div class="form-group">
                        <label for="new_password">新密码</label>
                        <input type="password" id="new_password" name="new_password" class="tech-input" required>
                    </div>
                    <div class="form-group">
                        <label for="confirm_password">确认密码</label>
                        <input type="password" id="confirm_password" name="confirm_password" class="tech-input" required>
                    </div>
                    <button type="submit" class="tech-btn tech-btn-primary">确认重置</button>
                </form>
                <?php endif; ?>
            <?php else: ?>
            <div class="alert-error">
                <i class="fa fa-exclamation-triangle"></i>
                <span>无效的重置链接</span>
            </div>
            <div class="form-actions">
                <a href="index.php" class="tech-btn tech-btn-primary">
                    <i class="fa fa-arrow-left"></i> 返回登录
                </a>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<script src="js/reset-password.js?v=<?php echo $version; ?>"></script>

<?php require_once $commonBasePath . 'includes/footer.php'; ?>
