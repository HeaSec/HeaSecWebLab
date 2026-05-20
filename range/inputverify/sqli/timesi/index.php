<?php
/**
 * HeaSec天积安全团队 - 时间盲注靶场
 * 版本: v2.0.0
 * 创建日期: 2026-04-20
 * 更新日期: 2026-04-26 - 双维度成就系统升级
 * 团队: 天积安全 (HeavenlySecret)
 */

// 设置响应头
header('X-HeavenlySecret: HeaSec Timesi Range v1.0.0');
header('Content-Type: text/html; charset=utf-8');

// 设置页面变量
$pageTitle = '时间盲注靶场';
$rangeName = '时间盲注';
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
$delayRecords = [];
$stringRecords = [];
$delayHint = '';
$stringHint = '';
$services = [];

try {
    $pdo = HeaSec_Database::getConnection('heasec_sqli');

    // 查询双维度成就状态（全局共享，无需会话）
    $achievementData = getAchievementStatus($pdo);
    $starCount = $achievementData['star_count'];
    $delayRecords = $achievementData['delay_records'];
    $stringRecords = $achievementData['string_records'];
    $delayHint = $achievementData['delay_hint'];
    $stringHint = $achievementData['string_hint'];

    // 获取服务列表数据（展示在页面上）
    $services = getServiceList($pdo);
} catch (Exception $e) {
    // 静默处理
}
?>

<!-- 引入统一样式文件 -->
<link rel="stylesheet" href="<?php echo $commonBasePath; ?>css/heasec_range.css">
<!-- 引入站点特定样式文件 -->
<link rel="stylesheet" href="css/style.css">

<!-- 靶场主要内容 -->
<div class="range-container">
    <!-- 左侧：实验操作区域 -->
    <div class="timesi-left-col">
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
                        <small>天积服务资产查询系统，输入服务编号查询该编号是否已在资产库中登记。系统当前处于生产模式。</small>
                    </span>
                </div>

                <!-- 过滤说明 -->
                <div class="alert alert-secondary">
                    <div>
                        <i class="fa fa-shield"></i>
                        <strong>安全过滤</strong>
                    </div>
                    <span class="alert-hint">
                        <small>系统已启用安全过滤：输入中不允许包含空白字符，部分SQL关键字被禁止。</small>
                    </span>
                </div>

                <!-- 任务引导 -->
                <div id="taskHint" class="alert alert-warning">
                    <div>
                        <i class="fa fa-exclamation-triangle"></i>
                        <strong>任务目标</strong>
                    </div>
                    <span class="alert-hint">
                        <small>利用时间盲注技术判断当前数据库名称是否为 heasec_sqli。</small>
                    </span>
                    <div style="margin-top: 8px; font-size: 12px; color: #856404;">
                        <p><strong>【注意事项】</strong><br>1. 生产模式下SQL错误信息不显示，正常查询仅返回"已登记/未找到"<br>2. 页面会显示每次查询的响应时间，请关注响应时间的差异<br>3. 系统对输入进行了安全过滤，请尝试绕过<br>4. 目标：使用延迟技术配合字符串处理函数判断数据库名<br>5. <strong>成就系统：双维度解锁</strong>——需要同时满足延迟技术和字符串函数两个维度的条件才可解锁星星：<br>&nbsp;&nbsp;&nbsp;&nbsp;⭐1星：1种延迟技术 + 1种字符串函数<br>&nbsp;&nbsp;&nbsp;&nbsp;⭐⭐2星：2种延迟技术 + 3种字符串函数<br>&nbsp;&nbsp;&nbsp;&nbsp;⭐⭐⭐3星：3种延迟技术 + 5种字符串函数<br></p>
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
    <div class="timesi-right-col">
        <?php
        // 双维度成就记录分组
        $recordGroups = [
            [
                'title'   => '已掌握的延迟技术',
                'icon'    => 'fa fa-clock-o',
                'records' => $delayRecords,
                'hint'    => $delayHint
            ],
            [
                'title'   => '已使用的字符串函数',
                'icon'    => 'fa fa-font',
                'records' => $stringRecords,
                'hint'    => $stringHint
            ]
        ];

        // 初始进度引导提示
        $progressHint = '';
        if ($starCount == 0) {
            $progressHint = '使用延迟技术配合字符串函数猜解数据库名首字符，两个维度各自达标即可获得星星';
        }

        require_once $commonBasePath . 'components/achievement-card/includes/HeaSec_AchievementCard.php';
        echo renderAchievementCard([
            'title'              => '时间盲注成就',
            'achievedCount'      => $starCount,
            'thresholds'         => [1, 2, 3],
            'titles'             => ['盲注新手', '盲注探索者', '盲注大师'],
            'recordGroups'       => $recordGroups,
            'recordLabel'        => '技术',
            'progressHint'       => $progressHint,
            'rangeCode'          => 'timesi',
            'congratsConfig'     => [
                'messages' => [
                    'partial_title'  => '成就解锁！',
                    'complete_title' => '恭喜你成为盲注大师！',
                    'partial'        => '太棒了！你已经掌握了 %d/3 颗星的技能！继续探索更多延迟技术和字符串函数来解锁全部成就吧！',
                    'complete'       => '太棒了！你已经掌握了所有 3 颗星的技能——熟练运用多种延迟技术和字符串函数，成为盲注大师！',
                    'buttonText'     => '继续学习'
                ]
            ]
        ], $commonBasePath);
        ?>
    </div>
</div>

<!-- 引入交互脚本 -->
<script src="js/timesi.js?v=1.0.0"></script>

<?php
// 引入公共底部
require_once $commonBasePath . 'includes/footer.php';
?>
