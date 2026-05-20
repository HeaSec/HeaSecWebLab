<?php
/**
 * HeaSec天积安全团队 - 报错注入靶场
 * 版本: v1.0.0
 * 创建日期: 2026-04-18
 * 团队: 天积安全 (HeavenlySecret)
 */

// 设置响应头
header('X-HeavenlySecret: HeaSec 报错注入 Range v1.0.0');
header('Content-Type: text/html; charset=utf-8');

// 设置页面变量
$pageTitle = '报错注入靶场';
$rangeName = '报错注入';
$showResetButton = true;

// 设置公共组件的基础路径
$commonBasePath = '../../../common/';

// 设置重置功能相关变量
$initSqlFile = 'database/init_database.sql';
$databaseName = 'heasec_sqli';
$useDatabase = true;

// 引入公共头部（header.php 会处理数据库检测和初始化）
require_once $commonBasePath . 'includes/header.php';

// 引入数据库组件和靶场函数
require_once $commonBasePath . 'includes/HeaSec_Database.php';
require_once __DIR__ . '/includes/functions.php';

// 初始化业务数据
$starCount = 0;
$records = [];
$progressHint = '';
$services = [];

try {
    $pdo = HeaSec_Database::getConnection('heasec_sqli');

    // 查询当前成就状态
    $achievementData = getAchievementStatus($pdo);
    $starCount = $achievementData['achieved_count'];
    $records = $achievementData['records'];
    $progressHint = generateProgressHint($starCount);

    // 获取服务列表数据
    $services = getServiceList($pdo);
} catch (Exception $e) {
    $progressHint = '';
}
?>

<!-- 引入统一样式文件 -->
<link rel="stylesheet" href="<?php echo $commonBasePath; ?>css/heasec_range.css">
<!-- 引入站点特定样式文件 -->
<link rel="stylesheet" href="css/style.css">

<!-- 靶场主要内容 -->
<div class="range-container">
    <!-- 左侧：实验操作区域 -->
    <div class="errsi-left-col">
        <!-- 卡片一：天积服务资产查询系统 -->
        <div class="tech-card">
            <div class="tech-card-header">
                <h3>
                    <i class="fa fa-server"></i>
                    天积服务资产查询系统
                </h3>
            </div>
            <div class="tech-card-body">
                <!-- 功能说明 -->
                <div class="alert alert-info">
                    <div>
                        <i class="fa fa-info-circle"></i>
                        <strong>天积服务资产查询系统</strong>
                    </div>
                    <span class="alert-hint">
                        <small>天积服务资产查询系统，输入服务编号查询该编号是否已在资产库中登记。系统当前处于调试模式。</small>
                    </span>
                </div>

                <!-- 任务引导 -->
                <div id="taskHint" class="alert alert-warning">
                    <div>
                        <i class="fa fa-exclamation-triangle"></i>
                        <strong>任务目标</strong>
                    </div>
                    <span class="alert-hint">
                        <small>利用报错注入技术获取当前数据库名称。</small>
                    </span>
                    <div style="margin-top: 8px; font-size: 12px; color: #856404;">
                        <p><strong>【注意事项】</strong><br>1. 系统处于调试模式，SQL错误信息直接显示<br>2. 正常查询仅返回"已登记/未找到"<br>3. 目标：构造参数触发报错，从错误信息中提取数据库名<br>4. 尝试使用不同的报错函数解锁不同成就</p>
                    </div>
                </div>

                <!-- 服务列表 -->
                <div id="serviceList">
                    <h4 style="font-size: 14px; color: #495057; margin-bottom: 10px;">
                        <i class="fa fa-list"></i> 已登记服务列表
                    </h4>
                    <table class="service-list-table">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>服务名称</th>
                                <th>状态</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($services as $svc): ?>
                            <tr>
                                <td><?php echo (int)$svc['id']; ?></td>
                                <td><?php echo htmlspecialchars($svc['service_name']); ?></td>
                                <td>
                                    <?php if ($svc['status'] == 1): ?>
                                    <span class="badge badge-success">正常</span>
                                    <?php else: ?>
                                    <span class="badge badge-warning">异常</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <!-- 查询输入区 -->
                <div class="submit-section">
                    <input type="text" id="queryInput" placeholder="输入服务ID进行查询..." autocomplete="off">
                    <button type="button" id="queryBtn" class="tech-btn tech-btn-primary">
                        <i class="fa fa-search"></i> 查询
                    </button>
                </div>

                <!-- 查询结果展示区 -->
                <div id="resultArea" class="doc-content-wrapper"></div>

                <!-- 查询历史（可折叠） -->
                <div class="query-history">
                    <div class="query-history-header" id="historyToggle">
                        <i class="fa fa-history"></i>
                        <span>查询历史</span>
                        <i class="fa fa-chevron-down history-arrow"></i>
                    </div>
                    <div class="query-history-body" id="queryHistory" style="display: none;">
                        <p class="history-empty">暂无查询记录</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- 右侧：成就系统 -->
    <div class="errsi-right-col">
        <?php
        $recordGroups = [[
            'title'   => '已掌握的注入函数',
            'icon'    => 'fa fa-code',
            'records' => $records,
            'hint'    => $progressHint
        ]];

        require_once $commonBasePath . 'components/achievement-card/includes/HeaSec_AchievementCard.php';
        echo renderAchievementCard([
            'title'              => '函数成就',
            'achievedCount'      => $starCount,
            'thresholds'         => [1, 3, 5],
            'titles'             => ['报请新手', '报错探索者', '报错大师'],
            'recordGroups'       => $recordGroups,
            'recordLabel'        => '函数',
            'rangeCode'          => 'errsi',
            'congratsConfig'     => [
                'messages' => [
                    'partial_title'  => '成就解锁！',
                    'complete_title' => '恭喜你成为报错大师！',
                    'partial'        => '太棒了！你已经掌握了 %d/5 种报错注入函数！继续探索更多函数来解锁全部成就吧！',
                    'complete'       => '太棒了！你已经掌握了足够多的报错注入函数，成为报错大师！本靶场共有 8 种报错函数可供探索，继续发现更多利用方式吧！',
                    'buttonText'     => '继续学习'
                ]
            ]
        ], $commonBasePath);
        ?>
    </div>
</div>

<!-- 引入交互脚本 -->
<script src="js/errsi.js?v=1.0.0"></script>

<?php
// 引入公共底部
require_once $commonBasePath . 'includes/footer.php';
?>
