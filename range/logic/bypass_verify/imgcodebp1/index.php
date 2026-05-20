<?php
/**
 * HeaSec天积安全团队 - 图片验证码绕过1靶场 - 第一关
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
$currentLevel = 1;
$levelTitle = '请输入正确的验证码';
$levelHint = '听说真正的高手不用看图就能知道验证码是什么';
$nextPage = 'imgcodebp1_2.php';
$nextBtnText = '下一关';

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
                    <input type="password" id="password" name="password" class="tech-input" value="admin123" readonly>
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
    // 初始化第一关
    document.addEventListener('DOMContentLoaded', function () {
        initImgCodeBp1(<?php echo $currentLevel; ?>);
    });
</script>

<?php
// 引入公共底部
require_once $commonBasePath . 'includes/footer.php';
?>