<?php
/**
 * HeaSec天积安全团队 - 文件包含进阶靶场
 * 版本: v1.0.0
 * 创建日期: 2026-04-17
 * 团队: 天积安全 (HeavenlySecret)
 */

// 设置响应头
header('X-HeavenlySecret: HeaSec 文件包含进阶 Range v1.0.0');
header('Content-Type: text/html; charset=utf-8');

// 设置页面变量
$pageTitle = '文件包含进阶靶场';
$rangeName = '文件包含进阶';
$showVersion = false;
$showResetButton = true;
$version = 'v1.0.0';

// 设置公共组件的基础路径
$commonBasePath = '../../../common/';

// 设置重置功能相关变量
$initSqlFile = 'database/init_database.sql';
$databaseName = 'heasec_inputverify';
$useDatabase = true;

// 引入公共头部（header.php 会处理数据库检测和初始化）
require_once $commonBasePath . 'includes/header.php';

// 引入数据库组件和靶场函数（在 header.php 之后，确保数据库已初始化）
require_once $commonBasePath . 'includes/HeaSec_Database.php';
require_once __DIR__ . '/includes/functions.php';

// 初始化业务数据
$targetPreview = '';
$phpConfig = checkPhpConfig();
$starCount = 0;
$records = [];
$progressHint = '';

try {
    $pdo = HeaSec_Database::getConnection('heasec_inputverify');
    $targetString = getOrCreateTargetString($pdo);
    $targetPreview = substr($targetString, 0, 8) . '********';

    // 查询当前成就状态
    $achievementData = getAchievementStatus($pdo);
    $starCount = $achievementData['achieved_count'];
    $records = $achievementData['records'];
    $progressHint = generateProgressHint($starCount);
} catch (Exception $e) {
    $targetPreview = 'I_love_h********';
    $progressHint = '';
}
?>

<!-- 引入统一样式文件 -->
<link rel="stylesheet" href="<?php echo $commonBasePath; ?>css/heasec_range.css">
<!-- 引入站点特定样式文件 -->
<link rel="stylesheet" href="css/style.css">

<!-- 靶场主要内容 -->
<!-- 左右布局：左侧实验操作区 + 右侧成就系统 -->
<div class="range-container">
    <!-- 左侧：实验操作区域 -->
    <div class="fiadv-left-col">

        <!-- 卡片一：天积资源管理器 -->
        <div class="tech-card">
            <div class="tech-card-header">
                <h3>
                    <i class="fa fa-folder-open"></i>
                    天积资源管理器
                </h3>
            </div>
            <div class="tech-card-body">
                <!-- PHP 配置检测 -->
                <?php if ($phpConfig['severity'] === 'warning'): ?>
                <div class="alert alert-danger">
                    <div>
                        <i class="fa fa-exclamation-circle"></i>
                        <strong>PHP 配置检测</strong>
                    </div>
                    <span class="alert-hint">
                        <small><?php echo htmlspecialchars($phpConfig['message']); ?></small>
                    </span>
                    <?php if (isset($phpConfig['config_file'])): ?>
                    <span class="alert-hint">
                        <small>php.ini 路径：<?php echo htmlspecialchars($phpConfig['config_file']); ?></small>
                    </span>
                    <?php endif; ?>
                </div>
                <?php else: ?>
                <div class="alert alert-success">
                    <div>
                        <i class="fa fa-check-circle"></i>
                        <strong><?php echo htmlspecialchars($phpConfig['message']); ?></strong>
                    </div>
                </div>
                <?php endif; ?>

                <!-- 功能说明 -->
                <div class="alert alert-info">
                    <div>
                        <i class="fa fa-info-circle"></i>
                        <strong>天积资源管理器</strong>
                    </div>
                    <span class="alert-hint">
                        <small>天积资源管理器的动态模板引擎允许您通过参数选择要加载的模板文件，系统将加载并展示对应的模板内容。系统提供了文件上传功能，但只允许上传图片、文本和压缩包文件。</small>
                    </span>
                </div>

                <!-- 任务引导 -->
                <div id="taskHint" class="alert alert-warning">
                    <div>
                        <i class="fa fa-exclamation-triangle"></i>
                        <strong>任务目标</strong>
                    </div>
                    <span class="alert-hint">
                        <small>利用文件包含漏洞执行 PHP 代码输出指定字符串即可触发成就，输出格式要求：<code>{目标字符串}:{验证令牌}</code>，请尝试使用不同方式完成上述操作。</small>
                    </span>
                    <div style="margin-top: 8px; font-size: 12px; color: #856404;">
                        <p><strong>【注意事项】</strong><br>1. 目标字符串<strong><?php echo htmlspecialchars($targetPreview); ?></strong>共34位，请先读取靶场的templates\config\target_def.php文件获取完整字符串。<br>2. 验证令牌可通过 PHP 全局变量 <code>$GLOBALS['heasec_rce_token']</code> 获取。<br>3. 参考代码：<code>echo 'I_love_heasec_XXX' . ':' . $GLOBALS['heasec_rce_token'];</code>。</p>
                    </div>
                </div>

                <!-- 模板选择导航 -->
                <div class="doc-nav">
                    <a href="#" class="doc-nav-link" data-page="templates/default.php"><i class="fa fa-home"></i> 默认模板</a>
                    <a href="#" class="doc-nav-link" data-page="templates/about.php"><i class="fa fa-info-circle"></i> 关于我们</a>
                    <a href="#" class="doc-nav-link" data-page="templates/contact.php"><i class="fa fa-envelope"></i> 联系方式</a>
                </div>

                <!-- 自定义路径输入 -->
                <div class="submit-section">
                    <input type="text" id="templateInput" placeholder="输入模板路径或URI..." autocomplete="off">
                    <button type="button" id="loadBtn" class="tech-btn tech-btn-primary">
                        <i class="fa fa-play"></i> 加载
                    </button>
                </div>

                <!-- 模板内容展示区域 -->
                <div id="contentArea" class="doc-content-wrapper" style="display: none;"></div>

                <!-- 拖拽上传区域 -->
                <div class="upload-section">
                    <div class="upload-drop-zone" id="dropZone">
                        <i class="fa fa-cloud-upload"></i>
                        <p>将文件拖拽到此处，或 <span class="upload-click-trigger">点击选择文件</span></p>
                        <small>支持格式：图片(jpg/jpeg/gif/png)、文档(txt)、压缩包(zip/rar/tar/gz)</small>
                        <input type="file" id="fileInput" style="display:none">
                    </div>
                    <!-- 上传结果区域 -->
                    <div id="uploadResultArea"></div>
                    <!-- 已上传文件列表 -->
                    <div id="uploadedFilesList" class="uploaded-files-list"></div>
                </div>
            </div>
        </div>

    </div>

    <!-- 右侧：成就系统 -->
    <div class="fiadv-right-col">
        <?php
        // 构建记录分组（参考 urlredirect 的 recordGroups 模式，hint 嵌入记录面板）
        $recordGroups = [[
            'title' => '已掌握的利用协议',
            'icon' => 'fa fa-list',
            'records' => $records,
            'hint' => $progressHint
        ]];
        require_once $commonBasePath . 'components/achievement-card/includes/HeaSec_AchievementCard.php';
        echo renderAchievementCard([
            'title' => '协议成就',
            'achievedCount' => $starCount,
            'thresholds' => [1, 3, 5],
            'titles' => ['新手成就', '探索者成就', '大师成就'],
            'recordGroups' => $recordGroups,
            'recordLabel' => '协议',
            'rangeCode' => 'fiadv',
            'congratsConfig' => [
                'messages' => [
                    'partial_title' => '成就解锁！',
                    'complete_title' => '恭喜你掌握了新技能！',
                    'partial' => '太棒了！你已经掌握了 %d种文件包含协议利用方式！继续探索更多协议来解锁全部成就吧！',
                    'complete' => '太棒了！你已经掌握了 5 种文件包含协议利用方式！解锁全部成就！！',
                    'buttonText' => '继续学习'
                ]
            ]
        ], $commonBasePath);
        ?>
    </div>
</div>

<!-- 引入交互脚本 -->
<script src="js/fiadv.js?v=<?php echo $version; ?>"></script>

<?php
// 引入公共底部
require_once $commonBasePath . 'includes/footer.php';
?>
