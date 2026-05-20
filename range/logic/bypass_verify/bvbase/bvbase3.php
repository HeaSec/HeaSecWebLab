<?php
/**
 * HeaSec天积安全团队 - 基础流程绕过靶场 - 第三关
 * 版本: v1.0.0
 * 创建日期: 2026-01-17
 * 团队: 天积安全 (HeavenlySecret)
 *
 */

// 设置响应头
header('X-HeavenlySecret: HeaSec 基础流程绕过 Range v1.0.0');
header('Content-Type: text/html; charset=utf-8');

// 设置页面变量
$pageTitle = '基础流程绕过靶场';
$rangeName = '基础流程绕过';
$showVersion = false;
$showResetButton = true;
$showSmsSimulator = true;  // 启用短信模拟器按钮
$version = 'v1.0.0';

// 设置公共组件的基础路径
$commonBasePath = '../../../common/';

// 设置重置功能相关变量
$initSqlFile = 'database/init_database.sql';
$databaseName = 'heasec_logic';
$useDatabase = true;

// 当前关卡
$currentLevel = 3;
$levelTitle = '第三关：请输入正确通关密码';
$nextPage = 'index.php';
$nextBtnText = '返回第一关';

// 引入会话管理组件
require_once $commonBasePath . 'includes/session_manager.php';
HeaSec_InitRangeSession('bvbase');

// 引入公共头部
require_once $commonBasePath . 'includes/header.php';

// 引入星星系统组件（用于恭喜弹窗）
require_once $commonBasePath . 'components/star-system/includes/HeaSec_StarSystem.php';
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
                <i class="fa fa-gift"></i>
                <?php echo htmlspecialchars($levelTitle); ?>
            </h3>
        </div>
        <div class="tech-card-body">
            <!-- 提示信息 -->
            <div class="alert-info">
                <i class="fa fa-info-circle"></i>
                <span>请使用13866668888获取领奖验证码，验证码即为通关密码</span>
            </div>

            <!-- 领奖申请表单 -->
            <div class="form-section-title">
                <i class="fa fa-mobile"></i> 领奖申请
            </div>
            <form id="applyForm" class="tech-form">
                <div class="form-group">
                    <label for="phone" class="form-label">
                        <i class="fa fa-phone"></i>
                        手机号
                    </label>
                    <input type="text" id="phone" name="phone" class="tech-input" placeholder="请输入手机号" maxlength="11"
                        autocomplete="off">
                    <input type="hidden" id="phoneCheck" name="phone_check" value="">
                    <!-- 校验状态显示 -->
                    <div id="phoneStatus" class="phone-status" style="display: none;"></div>
                </div>
                <div class="form-actions">
                    <button type="submit" class="tech-btn tech-btn-primary">
                        <i class="fa fa-paper-plane"></i>
                        申请领奖
                    </button>
                </div>
                <!-- 申请领奖结果显示区域 -->
                <div id="applyResultArea" class="detection-result" style="display: none;"></div>
            </form>

            <!-- 分隔线 -->
            <hr class="form-divider">

            <!-- 礼品兑换表单 -->
            <div class="form-section-title">
                <i class="fa fa-key"></i> 礼品兑换
            </div>
            <form id="verifyForm" class="tech-form">
                <div class="form-group">
                    <label for="code" class="form-label">
                        <i class="fa fa-lock"></i>
                        通关密码
                    </label>
                    <input type="text" id="code" name="code" class="tech-input" placeholder="请输入验证码" autocomplete="off">
                </div>
                <div class="form-actions">
                    <button type="submit" class="tech-btn tech-btn-primary">
                        <i class="fa fa-check"></i>
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
            <div id="verifyResultArea" class="detection-result" style="display: none;"></div>
        </div>
    </div>
</div>

<!-- 引入星星系统组件资源（包含恭喜弹窗） -->
<?php
echo HeaSec_StarSystem::renderAssets($commonBasePath, ['congrats' => true]);
?>

<!-- 引入交互脚本 -->
<script src="js/bvbase.js?v=<?php echo $version; ?>"></script>
<script>
    // 初始化第三关
    document.addEventListener('DOMContentLoaded', function () {
        initBvbase(<?php echo $currentLevel; ?>, '<?php echo $commonBasePath; ?>');
    });
</script>

<?php
// 引入公共底部
require_once $commonBasePath . 'includes/footer.php';
?>