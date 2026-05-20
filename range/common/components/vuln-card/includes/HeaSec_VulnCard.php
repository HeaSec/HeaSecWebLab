<?php
/**
 * HeaSec天积安全团队 - 漏洞挖掘卡片公共组件
 * Vulnerability Card Common Component
 * 版本: v1.0.0
 * 创建日期: 2026-03-07
 * 团队: 天积安全 (HeavenlySecret)
 *
 * 功能说明：
 * - 基于星星系统的漏洞挖掘进度展示
 * - 漏洞提交表单和验证
 * - 已提交漏洞记录列表
 * - 恭喜消息功能（星星解锁时触发）
 */

/**
 * 漏洞挖掘卡片组件版本
 */
define('HEASEC_VULN_CARD_VERSION', '1.2.0');

/**
 * 渲染完整的漏洞挖掘卡片
 *
 * @param array $config 配置选项
 * @param string $commonBasePath 公共组件基础路径
 * @return string HTML代码
 */
function renderVulnCard($config = [], $commonBasePath = '../../../common/')
{
    // 内联默认配置
    $defaultConfig = [
        // 卡片基础配置
        'title' => '漏洞挖掘',
        'rangeCode' => '',                      // 靶场代码（必填，用于localStorage区分）

        // 星星系统配置
        'starCount' => 3,
        'scoreThresholds' => [30, 60, 100],     // 解锁每颗星需要的分数线
        'starTitles' => ['初级挖掘者', '中级挖掘者', '高级挖掘者'],
        'starSize' => 48,
        'starGap' => 12,
        'showParticles' => true,
        'theme' => 'luxury',

        // 漏洞类型配置（仅用于表单下拉选项）
        'vulnTypes' => [],

        // 漏洞验证配置
        'vulnConfig' => [
            'validateApiUrl' => '',              // 后端验证API地址
            'submitMethod' => 'POST',            // 提交方式
        ],

        // 已提交记录（从后端获取）
        'submittedRecords' => [],

        // 当前总分
        'totalScore' => 0,

        // 满分（所有漏洞得分之和）
        'maxScore' => 100,

        // 存储键
        'storageKey' => 'vuln_card_star_count',
        'containerClass' => 'heasec-vuln-card',

        // 恭喜功能配置
        'congratsConfig' => [
            'enableCongrats' => true,
            'enableNextRangeButton' => true,
            'updateLearningStatus' => true,
            'particleCount' => 8,
            'animationDuration' => 2000,
            'messages' => [
                'partial_title' => '🎉 恭喜你解锁了一颗新星星！',
                'complete_title' => '🏆 恭喜你解锁了全部星星！',
                'partial' => '你已解锁 %d/%d 颗星星，继续挖掘漏洞提升等级！',
                'complete' => '太棒了！你已解锁全部 %d 颗星星，成为真正的漏洞挖掘专家！',
                'buttonText' => '继续学习'
            ]
        ]
    ];

    // 合并配置
    $config = array_merge($defaultConfig, $config);

    // 合并子配置
    if (isset($config['vulnConfig'])) {
        $config['vulnConfig'] = array_merge($defaultConfig['vulnConfig'], $config['vulnConfig']);
    }
    if (isset($config['congratsConfig'])) {
        $config['congratsConfig'] = array_merge($defaultConfig['congratsConfig'], $config['congratsConfig']);
    }

    // 配置验证
    if (empty($config['rangeCode'])) {
        return '<div class="heasec-vuln-card-error"><i class="fa fa-exclamation-triangle"></i> 配置参数无效：rangeCode 必填</div>';
    }
    if (empty($config['vulnTypes']) || !is_array($config['vulnTypes'])) {
        return '<div class="heasec-vuln-card-error"><i class="fa fa-exclamation-triangle"></i> 配置参数无效：vulnTypes 必填且为数组</div>';
    }

    // 生成唯一容器ID和存储键
    $containerId = 'heasec-vuln-card-' . uniqid();
    $storageKey = $config['storageKey'];
    if (!empty($config['rangeCode'])) {
        $storageKey = $config['rangeCode'] . '_' . $storageKey;
    }

    // 根据分数计算已解锁的星星数量
    $unlockedStars = 0;
    foreach ($config['scoreThresholds'] as $threshold) {
        if ($config['totalScore'] >= $threshold) {
            $unlockedStars++;
        } else {
            break;
        }
    }

    // 构建 JS 配置
    $jsConfig = [
        'rangeCode' => $config['rangeCode'],
        'totalScore' => $config['totalScore'],
        'maxScore' => $config['maxScore'],
        'scoreThresholds' => $config['scoreThresholds'],
        'unlockedStars' => $unlockedStars,
        'totalStars' => count($config['scoreThresholds']),
        'commonBasePath' => $commonBasePath,
        'validateApiUrl' => $config['vulnConfig']['validateApiUrl'],
        'submitMethod' => $config['vulnConfig']['submitMethod'],
    ];

    // 精简 congratsConfig
    $congratsDefaults = [
        'enableCongrats' => true,
        'enableNextRangeButton' => true,
        'updateLearningStatus' => true,
        'particleCount' => 8,
        'animationDuration' => 2000,
    ];
    $slimCongrats = [];
    foreach ($config['congratsConfig'] as $key => $value) {
        if ($key === 'messages') {
            $messagesDefaults = [
                'partial_title' => '🎉 恭喜你解锁了一颗新星星！',
                'complete_title' => '🏆 恭喜你解锁了全部星星！',
                'buttonText' => '继续学习',
            ];
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
        style="max-width: 700px; width: 100%;" data-config='<?php echo json_encode($jsConfig, JSON_UNESCAPED_UNICODE); ?>'
        data-storage-key="<?php echo htmlspecialchars($storageKey); ?>">

        <div class="tech-card-header">
            <h3>
                <i class="fa fa-bug"></i>
                <?php echo htmlspecialchars($config['title']); ?>
            </h3>
        </div>

        <div class="tech-card-body" style="padding: 15px;">
            <!-- 成就进度区域 -->
            <div class="tech-info-panel">
                <h4>挖掘进度：</h4>
                <div class="vuln-score-display">
                    <span class="score-current"><?php echo $config['totalScore']; ?></span>
                    <span class="score-divider">/</span>
                    <span class="score-max"><?php echo $config['maxScore']; ?></span>
                    <span class="score-label">分</span>
                </div>
                <?php
                // 内联星星系统渲染逻辑
                $starSystemPath = $commonBasePath . 'components/star-system/includes/HeaSec_StarSystem.php';
                if (file_exists($starSystemPath)) {
                    require_once $starSystemPath;
                    if (class_exists('HeaSec_StarSystem') && method_exists('HeaSec_StarSystem', 'renderAchievementStars')) {
                        $starConfig = [
                            'particles' => $config['showParticles'],
                            'theme' => $config['theme'],
                        ];
                        // 传入正确的阈值：[1, 2, 3, ...]，确保只有达到对应星星数才解锁
                        // 例如：unlockedStars=1 只解锁第1颗，unlockedStars=2 解锁前2颗
                        $starThresholds = range(1, count($config['scoreThresholds']));
                        echo HeaSec_StarSystem::renderAchievementStars(
                            $unlockedStars,
                            $starThresholds,
                            $config['starTitles'],
                            $starConfig
                        );
                    }
                }
                ?>
            </div>

            <!-- 漏洞记录列表区域 -->
            <div class="tech-info-panel">
                <h4>
                    <i class="fa fa-list"></i>
                    已提交漏洞
                </h4>
                <div class="vuln-records-list" id="<?php echo $containerId; ?>-records">
                    <?php if (empty($config['submittedRecords'])): ?>
                        <div class="vuln-record-empty">
                            <i class="fa fa-inbox"></i>
                            <span>暂无成功提交的漏洞记录</span>
                        </div>
                    <?php else: ?>
                        <?php foreach ($config['submittedRecords'] as $record): ?>
                            <div class="vuln-record-item">
                                <div class="vuln-record-info">
                                    <span class="vuln-record-type">
                                        <i class="fa fa-tag"></i>
                                        <?php echo htmlspecialchars($record['type']); ?>
                                    </span>
                                    <span class="vuln-record-url"><?php echo htmlspecialchars($record['url']); ?></span>
                                    <?php if (!empty($record['params']) && is_array($record['params'])): ?>
                                        <div class="vuln-record-params">
                                            <?php foreach ($record['params'] as $param): ?>
                                                <span class="vuln-param-tag <?php echo strtolower($param['location']); ?>">
                                                    <span class="param-location"><?php echo htmlspecialchars($param['location']); ?></span>
                                                    <span class="param-name"><?php echo htmlspecialchars($param['name']); ?></span>
                                                </span>
                                            <?php endforeach; ?>
                                        </div>
                                    <?php elseif (!empty($record['param'])): ?>
                                        <span class="vuln-record-param">参数: <?php echo htmlspecialchars($record['param']); ?></span>
                                    <?php endif; ?>
                                </div>
                                <div class="vuln-record-meta">
                                    <span class="vuln-record-score">+<?php echo $record['score']; ?>分</span>
                                    <span class="vuln-record-time"><?php echo htmlspecialchars($record['time'] ?? ''); ?></span>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>

            <!-- 漏洞提交表单区域 -->
            <div class="tech-info-panel vuln-submit-panel">
                <div class="vuln-submit-header">
                    <h4>
                        <i class="fa fa-paper-plane"></i>
                        提交漏洞
                    </h4>
                    <button type="button" class="tech-btn tech-btn-secondary vuln-help-btn" title="查看填写说明">
                        <i class="fa fa-question-circle"></i>
                        填写说明
                    </button>
                </div>
                <form class="vuln-submit-form" id="<?php echo $containerId; ?>-form">
                    <div class="form-group">
                        <label for="<?php echo $containerId; ?>-url">
                            <i class="fa fa-link"></i> 漏洞URL <span class="required">*</span>
                        </label>
                        <input type="text" id="<?php echo $containerId; ?>-url" name="vuln_url"
                            class="form-control vuln-input" placeholder="例如: /api/user.php" required>
                    </div>

                    <!-- 参数列表区域 -->
                    <div class="form-group">
                        <label>
                            <i class="fa fa-code"></i> 参数列表
                        </label>
                        <div class="vuln-params-container" id="<?php echo $containerId; ?>-params">
                            <div class="vuln-param-item" data-index="0">
                                <select name="params[0][location]" class="form-control vuln-select vuln-param-location">
                                    <option value="GET">GET</option>
                                    <option value="POST">POST</option>
                                    <option value="HEAD">HEAD</option>
                                </select>
                                <input type="text" name="params[0][name]" class="form-control vuln-input vuln-param-name"
                                    placeholder="参数名（可选）">
                                <button type="button" class="tech-btn tech-btn-danger vuln-param-remove"
                                    style="padding: 0 10px; height: 38px; width: auto;" title="删除参数">
                                    <i class="fa fa-times"></i>
                                </button>
                                <button type="button" class="tech-btn tech-btn-info vuln-param-add"
                                    style="padding: 0 10px; height: 38px; width: auto;"
                                    data-container="<?php echo $containerId; ?>" title="添加参数">
                                    <i class="fa fa-plus"></i>
                                </button>
                            </div>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="<?php echo $containerId; ?>-type">
                            <i class="fa fa-bug"></i> 漏洞类型 <span class="required">*</span>
                        </label>
                        <select id="<?php echo $containerId; ?>-type" name="vuln_type" class="form-control vuln-select"
                            required>
                            <option value="">-- 请选择漏洞类型 --</option>
                            <?php foreach ($config['vulnTypes'] as $type): ?>
                                <?php
                                // 支持两种格式：字符串或数组
                                if (is_string($type)) {
                                    $typeValue = $type;
                                    $typeLabel = $type;
                                } else {
                                    $typeValue = isset($type['value']) ? $type['value'] : $type['label'];
                                    $typeLabel = isset($type['label']) ? $type['label'] : $type['value'];
                                }
                                ?>
                                <option value="<?php echo htmlspecialchars($typeValue); ?>">
                                    <?php echo htmlspecialchars($typeLabel); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-actions">
                        <button type="submit" class="tech-btn tech-btn-success vuln-submit-btn"
                            style="width: 100%; border-radius: 25px; font-size: 16px; padding: 12px;">
                            <i class="fa fa-check"></i>
                            <span class="btn-text">提交验证</span>
                            <span class="btn-loading" style="display:none;">
                                <i class="fa fa-spinner fa-spin"></i> 验证中...
                            </span>
                        </button>
                    </div>
                    <div class="vuln-submit-message" id="<?php echo $containerId; ?>-message"></div>
                </form>
            </div>
        </div>
    </div>

    <?php
    // 引入组件样式
    $vulnCardCssPath = $commonBasePath . 'components/vuln-card/css/vuln-card.css';
    ?>
    <link rel="stylesheet" href="<?php echo $vulnCardCssPath; ?>?v=<?php echo HEASEC_VULN_CARD_VERSION; ?>">

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

    // 引入外部JavaScript文件
    $vulnCardJsPath = $commonBasePath . 'components/vuln-card/js/vuln-card.js';
    ?>
    <script src="<?php echo $vulnCardJsPath; ?>?v=<?php echo HEASEC_VULN_CARD_VERSION; ?>"></script>

    <?php
    return ob_get_clean();
}

/**
 * 获取组件信息
 *
 * @return array 组件信息
 */
function getVulnCardComponentInfo()
{
    return [
        'name' => 'HeaSec Vulnerability Card',
        'version' => HEASEC_VULN_CARD_VERSION,
        'team' => '天积安全 (HeavenlySecret)',
        'created' => '2026-03-07',
        'description' => '漏洞挖掘卡片公共组件 - 支持漏洞提交、验证和星星进度展示',
        'features' => [
            '基于分数的星星解锁系统',
            '漏洞提交表单',
            '已提交漏洞记录列表',
            '恭喜消息功能',
            '自定义漏洞类型配置',
        ],
        'usage' => [
            'basic' => 'renderVulnCard($config, $commonBasePath)',
        ]
    ];
}
?>