<?php
/**
 * HeaSec天积安全团队 - SQL盲注进阶靶场 - 第二关
 * 版本: v1.0.0
 * 功能: 逗号绕过 + 布尔盲注 — 成员验证
 * 团队: 天积安全 (HeavenlySecret)
 */

header('X-HeavenlySecret: HeaSec BSIAdv Range v1.0.0');
header('Content-Type: text/html; charset=utf-8');

// 页面变量
$pageTitle = 'SQL盲注进阶靶场 - 第二关';
$rangeName = 'SQL盲注进阶';
$showVersion = false;
$showResetButton = true;
$version = 'v1.0.0';

// 数据库配置
$initSqlFile = __DIR__ . '/database/init_database.sql';
$databaseName = 'heasec_sqli';

// 公共组件路径
$commonBasePath = '../../../common/';

// 当前关卡配置
$currentLevel = 2;
$nextPage = 'level3.php';
$nextBtnText = '下一关';

// 引入公共头部
require_once $commonBasePath . 'includes/header.php';

// 会话管理
require_once $commonBasePath . 'includes/session_manager.php';
HeaSec_InitRangeSession('bsiadv');

// 星星系统
require_once $commonBasePath . 'components/star-system/includes/HeaSec_StarSystem.php';
echo HeaSec_StarSystem::renderAssets($commonBasePath, ['congrats' => true]);

// 引入靶场函数
require_once 'includes/functions.php';

// 确保当前关卡的密码已生成
ensurePasswordExists($currentLevel);
?>

<link rel="stylesheet" href="<?php echo $commonBasePath; ?>css/heasec_range.css">
<link rel="stylesheet" href="css/style.css">

<div class="tech-container">
    <!-- 卡片一：成员验证 -->
    <div class="tech-card">
        <div class="tech-card-header">
            <h3>
                <i class="fa fa-user"></i>
                <span>成员验证</span>
            </h3>
        </div>
        <div class="tech-card-body">
            <!-- 系统功能说明 -->
            <div class="alert-info">
                <i class="fa fa-shield"></i>
                <span>天积安全审计系统 — 成员验证功能。输入成员ID验证成员身份，系统将返回验证结果。</span>
            </div>


            <!-- 任务提示 -->
            <div class="alert-warning">
                <div>
                    <strong>任务目标：</strong>
                    <small>已知存在成员M-001，尝试通过注入获取通关密码。提示：WAF新增逗号拦截规则，通关密码存储在 heasec_bsiadv_members 表的 member_key 字段中</small>
                </div>
            </div>

            <!-- 验证表单 -->
            <div class="submit-section">
                <form id="verifyMemberForm" class="query-form">
                    <div class="form-group">
                        <label for="memberId" class="form-label">
                            <i class="fa fa-id-card"></i> 成员ID
                        </label>
                        <input type="text" id="memberId" name="member_id" class="tech-input" placeholder="请输入成员ID" autocomplete="off">
                    </div>
                    <div class="form-actions">
                        <button type="submit" id="verifyBtn" class="tech-btn tech-btn-primary">
                            <i class="fa fa-check-circle"></i> 验证成员
                        </button>
                    </div>
                </form>
            </div>

            <!-- 验证结果区域 -->
            <div id="verifyMemberArea" style="display: none;"></div>
        </div>
    </div>

    <br>

    <!-- 卡片二：通关验证 -->
    <div class="tech-card">
        <div class="tech-card-header">
            <h3>
                <i class="fa fa-trophy"></i>
                <span>通关验证</span>
            </h3>
        </div>
        <div class="tech-card-body">
            <form id="verifyForm" class="tech-form">
                <div class="form-group">
                    <label for="passcode" class="form-label">
                        <i class="fa fa-key"></i> 通关密码
                    </label>
                    <input type="text" id="passcode" name="passcode" class="tech-input" placeholder="请输入通关密码" autocomplete="off">
                </div>
                <div class="form-actions">
                    <button type="submit" class="tech-btn tech-btn-primary">
                        <i class="fa fa-check"></i> 提交
                    </button>
                    <a href="<?php echo htmlspecialchars($nextPage); ?>" id="nextLevelBtn" class="tech-btn tech-btn-success" style="display: none;">
                        <i class="fa fa-arrow-right"></i> <?php echo htmlspecialchars($nextBtnText); ?>
                    </a>
                </div>
                <div id="verifyResultArea" class="detection-result" style="display: none;"></div>
            </form>
        </div>
    </div>
</div>

<script src="js/bsiadv.js?v=<?php echo $version; ?>"></script>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        initBSIAdv(<?php echo $currentLevel; ?>, '<?php echo $commonBasePath; ?>');
    });
</script>

<?php
require_once $commonBasePath . 'includes/footer.php';
?>
