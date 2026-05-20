<?php
/**
 * HeaSec天积安全团队 - 代码注入靶场 - 第二关
 * 版本: v1.0.0
 * 创建日期: 2026-04-22
 * 团队: 天积安全 (HeavenlySecret)
 */

// 设置响应头
header('X-HeavenlySecret: HeaSec CodeInj Range v1.0.0');
header('Content-Type: text/html; charset=utf-8');

// 设置页面变量
$pageTitle = '代码注入 - 第二关';
$rangeName = '代码注入';
$showVersion = false;
$showResetButton = true;
$version = 'v1.0.0';

// 设置公共组件的基础路径
$commonBasePath = '../../../common/';

// 当前关卡配置
$currentLevel = 2;
$nextPage = 'level3.php';
$nextBtnText = '下一关';

// 第二关需要数据库支持
$useDatabase = true;
$databaseName = 'heasec_inputverify';
$initSqlFile = __DIR__ . '/database/init_database.sql';

// 引入公共头部
require_once $commonBasePath . 'includes/header.php';

// 引入星星系统组件（恭喜弹窗）
require_once $commonBasePath . 'components/star-system/includes/HeaSec_StarSystem.php';
echo HeaSec_StarSystem::renderAssets($commonBasePath, ['congrats' => true]);

// 初始化靶场会话
require_once $commonBasePath . 'includes/session_manager.php';
HeaSec_InitRangeSession('code_inj');

// 引入公共函数
require_once 'includes/functions.php';

// 确保当前关卡的secret.php存在
$secretPath = getSecretFilePath($currentLevel);
generateSecretFile($secretPath);

// 确保 backups 目录存在
$backupDir = __DIR__ . '/backups/';
if (!is_dir($backupDir)) {
    mkdir($backupDir, 0755, true);
}
?>

<!-- 引入统一样式文件 -->
<link rel="stylesheet" href="<?php echo $commonBasePath; ?>css/heasec_range.css">
<!-- 引入站点特定样式文件 -->
<link rel="stylesheet" href="css/style.css">

<!-- 靶场主要内容 -->
<div class="tech-container">
    <!-- 用户信息管理卡片 -->
    <div class="tech-card">
        <div class="tech-card-header">
            <h3>
                <i class="fa fa-user"></i>
                <span>用户信息管理</span>
            </h3>
        </div>
        <div class="tech-card-body">
            <!-- 安全防护提示 -->
            <div class="alert-info">
                <i class="fa fa-shield"></i>
                <span>当前系统提供用户信息管理功能，您可以查看和编辑个人信息。同时系统提供数据备份功能，可以将数据库中的数据导出为SQL格式的备份文件</span>
            </div>

            <!-- 任务提示 -->
            <div class="alert-warning">
                <div>
                    <strong>任务目标：</strong>
                    <small>综合利用个人信息编辑和数据备份功能获取通关密码，秘密文件位于 config/level2 目录下</small>
                </div>
            </div>

            <!-- 当前用户信息展示 -->
            <div id="userInfoPanel" class="user-info-panel">
                <div class="user-info-item"><strong>用户名:</strong> <span id="displayUsername">加载中...</span></div>
                <div class="user-info-item"><strong>邮箱:</strong> <span id="displayEmail">加载中...</span></div>
                <div class="user-info-item"><strong>个人简介:</strong> <span id="displayBio">加载中...</span></div>
            </div>

            <hr class="section-divider">

            <!-- 编辑个人简介表单 -->
            <div class="form-group">
                <label for="bioInput" class="form-label">
                    <i class="fa fa-edit"></i> 编辑个人简介
                </label>
                <textarea id="bioInput" class="tech-input" placeholder="请输入个人简介" rows="4"></textarea>
            </div>
            <div class="form-actions">
                <button type="button" id="saveBioBtn" class="tech-btn tech-btn-primary">
                    <i class="fa fa-save"></i> 保存简介
                </button>
            </div>
            <div id="profileResultArea" style="margin-top: 15px;"></div>
        </div>
    </div>

    <br>

    <!-- 数据备份中心卡片 -->
    <div class="tech-card">
        <div class="tech-card-header">
            <h3>
                <i class="fa fa-database"></i>
                <span>数据备份中心</span>
            </h3>
        </div>
        <div class="tech-card-body">
            <!-- 备份配置 -->
            <div class="backup-config">
                <div class="form-group">
                    <label for="backupTable" class="form-label">
                        <i class="fa fa-table"></i> 备份表名
                    </label>
                    <select id="backupTable" class="tech-input">
                        <option value="heasec_code_inj_user">heasec_code_inj_user</option>
                    </select>
                </div>
                <div class="form-group">
                    <label for="backupFilename" class="form-label">
                        <i class="fa fa-file-archive-o"></i> 备份文件名
                    </label>
                    <input type="text" id="backupFilename" class="tech-input" value="backup.sql" placeholder="请输入备份文件名" autocomplete="off">
                </div>
                <div class="form-actions">
                    <button type="button" id="executeBackupBtn" class="tech-btn tech-btn-primary">
                        <i class="fa fa-download"></i> 执行备份
                    </button>
                </div>
            </div>
            <div id="backupResultArea" style="margin-top: 15px;"></div>

            <hr class="section-divider">

            <!-- 已有备份文件列表 -->
            <div class="backup-list">
                <h4><i class="fa fa-list"></i> 已有备份文件</h4>
                <div id="backupListArea">
                    <p style="color: #6c757d; font-size: 13px;">加载中...</p>
                </div>
            </div>
        </div>
    </div>

    <br>

    <!-- 通关验证卡片 -->
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

<!-- 引入交互脚本 -->
<script src="js/code_inj.js?v=<?php echo $version; ?>"></script>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        initCodeInj(<?php echo $currentLevel; ?>, '<?php echo $commonBasePath; ?>');
    });
</script>

<?php
// 引入公共底部
require_once $commonBasePath . 'includes/footer.php';
?>
