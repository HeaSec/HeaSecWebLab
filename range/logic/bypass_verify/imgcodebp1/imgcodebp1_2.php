<?php
/**
 * HeaSec天积安全团队 - 图片验证码绕过1靶场 - 第二关
 * 版本: v1.0.0
 * 创建日期: 2026-01-20
 * 团队: 天积安全 (HeavenlySecret)
 *
 */

// 设置响应头
header('X-HeavenlySecret: HeaSec 图片验证码绕过1 Range v1.0.0');
header('Content-Type: text/html; charset=utf-8');

// 设置页面变量
$pageTitle = '图片验证码绕过1靶场';
$rangeName = '图片验证码绕过1';
$showVersion = false;
$showResetButton = true;
$version = 'v1.0.0';

// 设置公共组件的基础路径
$commonBasePath = '../../../common/';

// 设置重置功能相关变量
$initSqlFile = 'database/init_database.sql';
$databaseName = 'heasec_logic';
$useDatabase = true;

// 当前关卡
$currentLevel = 2;
$levelTitle = '请输入正确的密码';
$levelHint = '看来你已经掌握了绕过图片验证码的技巧，那么试试找到正确的密码吧';
$passwordRule = '密码为4位，第一位是字母（大小写均可），后三位是数字';
$nextPage = 'imgcodebp1_3.php';
$nextBtnText = '下一关';

// 检查并生成密码（如果不存在）
define('HEASEC_RANGE_ACCESS', true);
require_once $commonBasePath . 'includes/HeaSec_Database.php';

try {
    $pdo = HeaSec_Database::getConnection('heasec_logic');
    $stmt = $pdo->prepare("SELECT password FROM heasec_imgcodebp1_passwords WHERE level = ?");
    $stmt->execute(array($currentLevel));
    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$row) {
        // 生成新密码
        $letters = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $firstChar = $letters[mt_rand(0, 51)];
        $numbers = sprintf('%03d', mt_rand(0, 999));
        $password = $firstChar . $numbers;

        // 存入数据库
        $stmt = $pdo->prepare("INSERT INTO heasec_imgcodebp1_passwords (level, password) VALUES (?, ?)");
        $stmt->execute(array($currentLevel, $password));
    }
} catch (Exception $e) {
    // 数据库错误会在header中显示初始化提示
}

// 引入会话管理组件
require_once $commonBasePath . 'includes/session_manager.php';
HeaSec_InitRangeSession('imgcodebp1');

// 引入公共头部
require_once $commonBasePath . 'includes/header.php';
?>

<!-- 引入统一样式文件 -->
<link rel="stylesheet" href="<?php echo $commonBasePath; ?>css/heasec_range.css">
<!-- 引入站点特定样式文件 -->
<link rel="stylesheet" href="css/style.css">

<!-- 靶场主要内容 -->
<div class="tech-container">
    <div class="tech-card">
        <div class="tech-card-header">
            <h3>
                <i class="fa fa-lock"></i>
                <?php echo htmlspecialchars($levelTitle); ?>
            </h3>
        </div>
        <div class="tech-card-body">
            <!-- 提示信息 -->
            <div class="alert-info">
                <i class="fa fa-info-circle"></i>
                <span><?php echo htmlspecialchars($levelHint); ?></span>
            </div>

            <!-- 密码规则提示 -->
            <div class="password-rule">
                <i class="fa fa-key"></i>
                <span><?php echo htmlspecialchars($passwordRule); ?></span>
            </div>

            <!-- 登录表单 -->
            <form id="loginForm" class="tech-form">
                <div class="form-group">
                    <label for="username" class="form-label">
                        <i class="fa fa-user"></i>
                        用户名
                    </label>
                    <input type="text" id="username" name="username" class="tech-input" value="admin" readonly>
                </div>

                <div class="form-group">
                    <label for="password" class="form-label">
                        <i class="fa fa-lock"></i>
                        密码
                    </label>
                    <input type="password" id="password" name="password" class="tech-input" placeholder="请输入密码"
                        autocomplete="off" maxlength="4">
                </div>

                <div class="form-group">
                    <label for="captcha" class="form-label">
                        <i class="fa fa-shield"></i>
                        验证码
                    </label>
                    <div class="captcha-group">
                        <div class="captcha-input-wrapper">
                            <input type="text" id="captcha" name="captcha" class="tech-input" placeholder="请输入验证码"
                                autocomplete="off" maxlength="4">
                            <input type="hidden" id="captchaVerified" name="captcha_verified" value="0">
                        </div>
                        <div class="captcha-image-wrapper">
                            <img id="captchaImage" class="captcha-image" src="" alt="验证码" title="点击刷新">
                        </div>
                    </div>
                </div>

                <div class="form-actions">
                    <button type="submit" class="tech-btn tech-btn-primary">
                        <i class="fa fa-sign-in"></i>
                        提交
                    </button>
                    <a href="<?php echo htmlspecialchars($nextPage); ?>" id="nextLevelBtn"
                        class="tech-btn tech-btn-success" style="display: none;">
                        <i class="fa fa-arrow-right"></i>
                        <?php echo htmlspecialchars($nextBtnText); ?>
                    </a>
                </div>
            </form>

            <!-- 结果显示区域 -->
            <div id="resultArea" class="detection-result" style="display: none;"></div>
        </div>
    </div>
</div>

<!-- 引入交互脚本 -->
<script src="js/imgcodebp1.js?v=<?php echo $version; ?>"></script>
<script>
    // 初始化第二关
    document.addEventListener('DOMContentLoaded', function () {
        initImgCodeBp1(<?php echo $currentLevel; ?>);
    });
</script>

<?php
// 引入公共底部
require_once $commonBasePath . 'includes/footer.php';
?>