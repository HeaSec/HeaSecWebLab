<?php
/**
 * HeaSec天积安全团队 - 成就卡片公共组件
 * Achievement Card Common Component
 * 版本: v2.1.0
 * 创建日期: 2025-11-19
 * 重构日期: 2026-02-11
 * 团队: 天积安全 (HeavenlySecret)
 *
 * 重构说明：
 * - PHP负责HTML渲染和配置输出（通过data-config属性）
 * - JavaScript逻辑提取到外部文件 js/achievement-card.js
 * - JS通过DOM属性自动读取配置并初始化
 * - 保持100%向后兼容性
 */

/**
 * 成就卡片组件版本
 */
define('HEASEC_ACHIEVEMENT_CARD_VERSION', '2.1.0');

/**
 * 渲染完整的成就卡片（重构版 - 单函数架构）
 *
 * @param array $config 配置选项
 * @param string $commonBasePath 公共组件基础路径
 * @return string HTML代码
 */
function renderAchievementCard($config = [], $commonBasePath = '../../../common/')
{
    // 内联默认配置
    $defaultConfig = [
        'title' => '成就系统',
        'achievedCount' => 0,
        'thresholds' => [1, 2, 3],
        'titles' => ['初学者', '探索者', '大师'],
        'starSize' => 48,
        'starGap' => 12,
        'showParticles' => true,
        'theme' => 'luxury',
        'customRecords' => [],
        'showRecords' => true,
        'recordsTitle' => '成功记录',
        'storageKey' => 'achievement_previous_count',
        'rangeCode' => '',
        'containerClass' => 'heasec-achievement-card',
        'showCongratsButton' => false,
        'progressHint' => '',
        'recordGroups' => [],
        'recordLabel' => '对象',

        'congratsConfig' => [
            'enableCongrats' => true,
            'enableNextRangeButton' => true,
            'updateLearningStatus' => true,
            'particleCount' => 8,
            'animationDuration' => 2000,
            'messages' => [
                'partial_title' => '🎉 恭喜你掌握了一个新技能',
                'complete_title' => '🏆 恭喜你获得了全部成就！',
                'partial' => '你已经掌握了 %d/%d 种技能！继续努力，获得更多的成就！',
                'complete' => '太棒了！你已经掌握了所有%d种技能，成为了真正的安全大师！',
                'buttonText' => '继续学习'
            ]
        ]
    ];

    // 合并配置
    $config = array_merge($defaultConfig, $config);

    // 合并恭喜配置
    if (isset($config['congratsConfig'])) {
        $config['congratsConfig'] = array_merge($defaultConfig['congratsConfig'], $config['congratsConfig']);
    }

    // 内联配置验证
    if (!isset($config['achievedCount']) || !is_numeric($config['achievedCount'])) {
        return '<div class="heasec-achievement-card-error"><i class="fa fa-exclamation-triangle"></i> 配置参数无效</div>';
    }
    if (!isset($config['thresholds']) || !is_array($config['thresholds'])) {
        return '<div class="heasec-achievement-card-error"><i class="fa fa-exclamation-triangle"></i> 配置参数无效</div>';
    }
    if (!isset($config['titles']) || !is_array($config['titles'])) {
        return '<div class="heasec-achievement-card-error"><i class="fa fa-exclamation-triangle"></i> 配置参数无效</div>';
    }
    if (!isset($config['customRecords']) || !is_array($config['customRecords'])) {
        $config['customRecords'] = [];
    }

    // 生成唯一容器ID和存储键
    $containerId = 'heasec-achievement-card-' . uniqid();
    $storageKey = $config['storageKey'];
    if (!empty($config['rangeCode'])) {
        $storageKey = $config['rangeCode'] . '_' . $storageKey;
    }

    // 构建精简的 JS 配置（仅包含恭喜功能所需的动态字段）
    // PHP-only 的渲染字段（title, recordGroups, progressHint 等）不输出到 HTML
    $jsConfig = [
        'achievedCount' => $config['achievedCount'],
        'rangeCode' => $config['rangeCode'],
        'thresholds' => $config['thresholds'],
        'commonBasePath' => $commonBasePath,
    ];

    // congratsConfig 中也只输出非默认值
    $congratsDefaults = [
        'enableCongrats' => true,
        'enableNextRangeButton' => true,
        'updateLearningStatus' => true,
        'particleCount' => 8,
        'animationDuration' => 2000,
    ];
    // messages 子对象的默认值（与 JS 侧保持一致）
    $messagesDefaults = [
        'partial_title' => '🎉 恭喜你掌握了一个新技能',
        'complete_title' => '🏆 恭喜你获得了全部成就！',
        'buttonText' => '继续学习',
    ];
    $slimCongrats = [];
    foreach ($config['congratsConfig'] as $key => $value) {
        if ($key === 'messages' && is_array($value)) {
            // 对 messages 子对象过滤默认值
            $slimMessages = [];
            foreach ($value as $msgKey => $msgValue) {
                if (!isset($messagesDefaults[$msgKey]) || $messagesDefaults[$msgKey] !== $msgValue) {
                    $slimMessages[$msgKey] = $msgValue;
                }
            }
            if (!empty($slimMessages)) {
                $slimCongrats['messages'] = $slimMessages;
            }
        } elseif (!isset($congratsDefaults[$key]) || $congratsDefaults[$key] !== $value) {
            $slimCongrats[$key] = $value;
        }
    }
    if (!empty($slimCongrats)) {
        $jsConfig['congratsConfig'] = $slimCongrats;
    }

    // 开始输出缓冲
    ob_start();
    ?>
    <div id="<?php echo $containerId; ?>" class="tech-card <?php echo $config['containerClass']; ?>"
        style="max-width: 600px; width: 100%;" data-config='<?php echo json_encode($jsConfig, JSON_UNESCAPED_UNICODE); ?>'
        data-storage-key="<?php echo htmlspecialchars($storageKey); ?>">

        <div class="tech-card-header">
            <h3>
                <i class="fa fa-trophy"></i>
                <?php echo htmlspecialchars($config['title']); ?>
            </h3>
        </div>

        <div class="tech-card-body" style="padding: 15px;">
            <!-- 成就进度区域 -->
            <div class="tech-info-panel">
                <h4>成就进度：</h4>
                <?php
                // 内联星星系统渲染逻辑
                $starSystemPath = $commonBasePath . 'components/star-system/includes/HeaSec_StarSystem.php';
                if (file_exists($starSystemPath)) {
                    require_once $starSystemPath;
                    if (class_exists('HeaSec_StarSystem') && method_exists('HeaSec_StarSystem', 'renderAchievementStars')) {
                        $starConfig = [
                            'particles' => $config['showParticles'],
                            'theme' => $config['theme'],
                            // basePath 由 JS 从父元素 data-config.commonBasePath 自动推导
                        ];
                        echo HeaSec_StarSystem::renderAchievementStars(
                            $config['achievedCount'],
                            $config['thresholds'],
                            $config['titles'],
                            $starConfig
                        );
                    }
                }
                ?>
            </div>

            <?php if ($config['showCongratsButton']): ?>
                <div class="tech-info-panel">
                    <div class="achievement-actions">
                        <button class="btn btn-success" id="triggerCongrats">
                            <i class="fa fa-gift"></i>
                            测试恭喜消息
                        </button>
                    </div>
                </div>
            <?php endif; ?>

            <!-- 进度提示区域 -->
            <?php if (!empty($config['progressHint'])): ?>
                <div class="tech-info-panel">
                    <div class="alert alert-info mb-0"
                        style="background-color: rgba(0, 123, 255, 0.1); border-color: rgba(0, 123, 255, 0.2); color: #0056b3;">
                        <i class="fa fa-info-circle"></i>
                        <?php echo htmlspecialchars($config['progressHint']); ?>
                    </div>
                </div>
            <?php endif; ?>

            <!-- 记录列表区域 -->
            <?php if ($config['showRecords']): ?>
                <?php if (!empty($config['recordGroups'])): ?>
                    <?php
                    // Auto-detect grid layout if multiple groups exist
                    $isGrid = (isset($config['layout']) && $config['layout'] === 'grid') || count($config['recordGroups']) > 1;
                    ?>

                    <?php if ($isGrid): ?>
                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 8px;">
                        <?php endif; ?>

                        <?php foreach ($config['recordGroups'] as $group): ?>
                            <div class="tech-info-panel" style="<?php echo $isGrid ? 'margin-bottom: 0; padding: 12px;' : ''; ?>">
                                <h4>
                                    <?php if (!empty($group['icon'])): ?>
                                        <i class="fa <?php echo htmlspecialchars($group['icon']); ?>" style="font-size: 13px;"></i>
                                    <?php endif; ?>
                                    <?php echo htmlspecialchars(isset($group['title']) ? $group['title'] : '记录'); ?>
                                </h4>

                                <!-- Group Hint -->
                                <?php if (!empty($group['hint'])): ?>
                                    <div class="alert alert-info mb-2"
                                        style="font-size: 11px; padding: 6px 10px; margin-bottom: 8px;  border: 1px solid rgba(23, 162, 184, 0.2); color: #17a2b8; line-height: 1.4;">
                                        <span><?php echo htmlspecialchars($group['hint']); ?></span>
                                    </div>
                                <?php endif; ?>

                                <div class="info-grid" style="gap: 8px;">
                                    <!-- Header Row -->
                                    <div class="info-item"
                                        style="background: rgba(0,0,0,0.03); border: none; padding: 6px 12px; min-height: auto;">
                                        <span class="info-label" style="font-size: 12px; font-weight: bold; color: #666;"><?php echo htmlspecialchars($config['recordLabel']); ?></span>
                                        <span class="info-label" style="font-size: 12px; font-weight: bold; color: #666;">次数</span>
                                    </div>
                                    <?php
                                    if (empty($group['records'])) {
                                        echo '<div class="info-item"><span class="info-label">暂无记录</span><span class="info-value"></span></div>';
                                    } else {
                                        foreach ($group['records'] as $record) {
                                            $name = isset($record['name']) ? $record['name'] : '未知';
                                            $desc = isset($record['desc']) ? $record['desc'] : '';
                                            $count = isset($record['count']) ? $record['count'] : 0;
                                            echo '<div class="info-item">';
                                            if (!empty($desc)) {
                                                // 有描述时：名称为badge + 描述在下方 + 次数靠右
                                                echo '<div style="display: flex; align-items: flex-start; justify-content: space-between; width: 100%; gap: 8px;">';
                                                echo '<div style="flex: 1; min-width: 0;">';
                                                echo '<span class="record-name-badge">' . htmlspecialchars($name) . '</span>';
                                                if (!empty($desc)) {
                                                    echo '<div class="record-desc">' . htmlspecialchars($desc) . '</div>';
                                                }
                                                echo '</div>';
                                                echo '<span class="info-value" style="flex-shrink: 0;"><span class="badge badge-success" style="font-size: 11px;">' . $count . '</span></span>';
                                                echo '</div>';
                                            } else {
                                                // 无描述时：保持原有格式
                                                echo '<span class="info-label" style="font-size: 13px;">' . htmlspecialchars($name) . '：</span>';
                                                echo '<span class="info-value"><span class="badge badge-success" style="font-size: 11px;">' . $count . '</span></span>';
                                            }
                                            echo '</div>';
                                        }
                                    }
                                    ?>
                                </div>
                            </div>
                        <?php endforeach; ?>

                        <?php if ($isGrid): ?>
                        </div>
                    <?php endif; ?>
                <?php else: ?>
                    <div class="tech-info-panel">
                        <h4>
                            <i class="fa fa-list"></i>
                            <?php echo htmlspecialchars($config['recordsTitle']); ?>
                        </h4>
                        <div class="info-grid">
                            <?php
                            if (empty($config['customRecords'])) {
                                echo '<div class="info-item"><span class="info-label">暂无记录</span><span class="info-value"></span></div>';
                            } else {
                                foreach ($config['customRecords'] as $record) {
                                    $name = isset($record['name']) ? $record['name'] : '未知';
                                    $count = isset($record['count']) ? $record['count'] : 0;
                                    echo '<div class="info-item">';
                                    echo '<span class="info-label">' . htmlspecialchars($name) . '：</span>';
                                    echo '<span class="info-value"><span class="badge badge-success">请求成功 ' . $count . ' 次</span></span>';
                                    echo '</div>';
                                }
                            }
                            ?>
                        </div>
                    </div>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    </div>

    <?php
    // 引入恭喜弹窗资源（如果需要）
    if ($config['congratsConfig']['enableCongrats']) {
        $starSystemPath = $commonBasePath . 'components/star-system/includes/HeaSec_StarSystem.php';
        if (file_exists($starSystemPath)) {
            require_once $starSystemPath;
            if (class_exists('HeaSec_StarSystem')) {
                echo HeaSec_StarSystem::renderAssets($commonBasePath, [
                    'css' => true,
                    'js' => true,
                    'congrats' => true
                ]);
            }
        }
    }

    // 引入外部JavaScript文件（恭喜消息处理模块）
    if ($config['congratsConfig']['enableCongrats']) {
        $achievementCardJsPath = $commonBasePath . 'components/achievement-card/js/achievement-card.js';
        ?>
        <script src="<?php echo $achievementCardJsPath; ?>?v=<?php echo HEASEC_ACHIEVEMENT_CARD_VERSION; ?>"></script>
        <?php
    }

    return ob_get_clean();
}

/**
 * 获取组件信息（保持向后兼容）
 *
 * @return array 组件信息
 */
function getAchievementCardComponentInfo()
{
    return [
        'name' => 'HeaSec Achievement Card',
        'version' => HEASEC_ACHIEVEMENT_CARD_VERSION,
        'team' => '天积安全 (HeavenlySecret)',
        'created' => '2025-11-19',
        'refactored' => '2025-11-26',
        'description' => '成就卡片公共组件 - PHP渲染 + 外部JS架构',
        'features' => [
            '基于星星系统的成就展示',
            '自定义记录列表',
            '路径自适应',
            '恭喜消息功能（外部JS自动初始化）',
            'PHP渲染 + data-config属性传参',
            '简化的配置处理'
        ],
        'usage' => [
            'basic' => 'renderAchievementCard($config, $commonBasePath)',
            'with_congrats' => '在config中设置congratsConfig.enableCongrats => true'
        ]
    ];
}
?>