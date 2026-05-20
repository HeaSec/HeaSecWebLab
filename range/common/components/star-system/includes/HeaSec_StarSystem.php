<?php
/**
 * HeaSec天积安全团队 - 华丽星星成就系统公共组件
 * Luxury Star Achievement System - Common Component
 * 版本: v2.0.0
 * 创建日期: 2025-11-08
 * 迁移日期: 2025-11-08
 * 团队: 天积安全 (HeavenlySecret)
 *
 * 从/test/star/迁移到/range/common/components/star-system/
 * 专为靶场项目设计的公共星星系统组件
 */

class HeaSec_StarSystem
{

    /**
     * 组件版本
     */
    const VERSION = '2.0.1';

    /**
     * 基础路径（相对于公共组件目录）
     */
    const BASE_PATH = 'components/star-system/';

    /**
     * 默认配置
     */
    private static $defaultConfig = [
        'starCount' => 3,
        'size' => 48,
        'gap' => 12,
        'animated' => false,
        'interactive' => false,
        'particles' => true,
        'theme' => 'luxury',
        'containerClass' => 'heasec-star-system',
        'autoInit' => true,
        'showCongrats' => false,
        'congratsModal' => false,
        'basePath' => 'components/star-system/'
    ];

    /**
     * 预设配置
     */
    private static $presets = [
        'compact' => [
            'starCount' => 3,
            'size' => 60,
            'gap' => 15,
            'particles' => false,
            'congratsModal' => false
        ],
        'full' => [
            'starCount' => 5,
            'size' => 100,
            'gap' => 25,
            'particles' => true,
            'congratsModal' => true
        ],
        'mini' => [
            'starCount' => 3,
            'size' => 40,
            'gap' => 10,
            'animated' => false,
            'particles' => false,
            'congratsModal' => false
        ]
    ];

    /**
     * 渲染完整的星星系统
     *
     * @param array $config 配置选项
     * @param array $starData 星星数据
     * @return string HTML代码
     */
    public static function renderStarSystem($config = [], $starData = [])
    {
        $config = array_merge(self::$defaultConfig, $config);
        $containerId = uniqid('heasec-star-system-');

        // 计算已解锁的星星数量（从 starData 中统计）
        if (empty($starData)) {
            $starData = self::generateDefaultStarData($config['starCount']);
        }
        $unlockedCount = 0;
        foreach ($starData as $star) {
            if (!empty($star['unlocked'])) {
                $unlockedCount++;
            }
        }

        // 精简配置：仅输出与默认值不同的字段到 data-heasec-star
        // JS 侧已定义了相同的默认值，会自动合并
        $slimConfig = [];
        foreach ($config as $key => $value) {
            if (!isset(self::$defaultConfig[$key]) || self::$defaultConfig[$key] !== $value) {
                $slimConfig[$key] = $value;
            }
        }
        // 将解锁数量并入配置（替代冗余的 data-heasec-star-data 数组）
        if ($unlockedCount > 0) {
            $slimConfig['unlockedCount'] = $unlockedCount;
        }

        // 生成容器HTML — 精简的单个 data 属性
        $html = sprintf(
            '<div id="%s" class="%s" data-heasec-star=\'%s\' data-version="%s"></div>',
            $containerId,
            $config['containerClass'],
            json_encode($slimConfig, JSON_UNESCAPED_UNICODE),
            self::VERSION
        );

        return $html;
    }

    /**
     * 使用预设配置渲染星星系统
     *
     * @param string $preset 预设名称
     * @param array $config 额外配置
     * @return string HTML代码
     */
    public static function renderPresetStarSystem($preset = 'compact', $config = [])
    {
        $presetConfig = isset(self::$presets[$preset]) ? self::$presets[$preset] : [];
        $config = array_merge($presetConfig, $config);

        return self::renderStarSystem($config);
    }

    /**
     * 渲染单个星星
     *
     * @param int $index 星星索引
     * @param boolean $isUnlocked 是否解锁
     * @param array $config 配置
     * @return string HTML代码
     */
    public static function renderSingleStar($index, $isUnlocked = false, $config = [])
    {
        $config = array_merge(self::$defaultConfig, $config);
        $state = $isUnlocked ? 'gold' : 'gray';
        $starId = uniqid('heasec-star-');
        $basePath = isset($config['basePath']) ? $config['basePath'] : self::BASE_PATH;
        $svgPath = $basePath . 'assets/svg/star-' . $state . '.svg';

        $html = sprintf(
            '<div id="%s" class="heasec-star heasec-star-%s" ' .
            'data-star-index="%d" data-title="成就星星 %d" aria-label="成就星星 %d, %s">',
            $starId,
            $state,
            $index,
            $index + 1,
            $index + 1,
            $isUnlocked ? '已解锁' : '未解锁'
        );

        $html .= sprintf(
            '<img src="%s" class="star-svg" alt="Star" style="width: 100%%; height: 100%%;">',
            $svgPath
        );
        $html .= '</div>';

        return $html;
    }

    /**
     * 渲染基于数据库的成就星星
     *
     * @param int $achievedCount 已达成数量
     * @param array $thresholds 阈值数组
     * @param array $titles 标题数组
     * @param array $config 额外配置
     * @return string HTML代码
     */
    public static function renderAchievementStars($achievedCount, $thresholds = [1, 2, 3], $titles = [], $config = [])
    {
        $unlockedStars = 0;
        $totalStars = count($thresholds);

        // 计算解锁的星星数量
        foreach ($thresholds as $threshold) {
            if ($achievedCount >= $threshold) {
                $unlockedStars++;
            } else {
                break;
            }
        }

        // 生成标题
        if (empty($titles)) {
            $titles = ['初学者', '探索者', '大师', '传奇', '神话'];
        }

        // 生成星星配置
        $starConfig = self::generateAchievementConfig($totalStars, $unlockedStars, $titles);

        // 渲染星星系统
        $html = self::renderStarSystem($config, $starConfig);

        // 如果全部解锁且开启恭喜弹窗
        if ($unlockedStars === $totalStars && $config['congratsModal']) {
            $html .= self::renderCongratsModal();
        }

        return $html;
    }

    /**
     * 渲染恭喜弹窗
     *
     * @return string HTML代码
     */
    public static function renderCongratsModal()
    {
        return self::loadTemplate('congrats-modal');
    }

    /**
     * 生成CSS和JS引入代码
     *
     * @param string $commonBasePath 公共组件基础路径
     * @param array $options 引入选项
     * @return string HTML代码
     */
    public static function renderAssets($commonBasePath = null, $options = [])
    {
        if ($commonBasePath === null) {
            $basePath = self::BASE_PATH;
        } else {
            $basePath = $commonBasePath . self::BASE_PATH;
        }

        $defaultOptions = [
            'css' => true,
            'js' => true,
            'congrats' => false,
            'version' => self::VERSION
        ];

        $options = array_merge($defaultOptions, $options);
        $html = '';

        // CSS引入
        if ($options['css']) {
            $html .= sprintf(
                '<link rel="stylesheet" href="%scss/heasec-star-system.css?v=%s">' . "\n",
                $basePath,
                $options['version']
            );
        }

        // 恭喜弹窗CSS
        if ($options['congrats']) {
            $html .= sprintf(
                '<link rel="stylesheet" href="%scss/heasec-congrats-modal.css?v=%s">' . "\n",
                $basePath,
                $options['version']
            );
        }

        // JavaScript引入
        if ($options['js']) {
            $html .= sprintf(
                '<script src="%sjs/heasec-star-system.js?v=%s"></script>' . "\n",
                $basePath,
                $options['version']
            );
        }

        // 恭喜弹窗JavaScript
        if ($options['congrats']) {
            $html .= sprintf(
                '<script src="%sjs/heasec-congrats-modal.js?v=%s"></script>' . "\n",
                $basePath,
                $options['version']
            );
        }

        return $html;
    }

    /**


    /**
     * 生成默认星星数据
     */
    private static function generateDefaultStarData($starCount)
    {
        $starData = [];
        for ($i = 0; $i < $starCount; $i++) {
            $starData[] = [
                'index' => $i,
                'state' => 'gray',
                'title' => "成就星星 " . ($i + 1),
                'unlocked' => false
            ];
        }
        return $starData;
    }

    /**
     * 生成成就星星配置
     */
    private static function generateAchievementConfig($totalStars, $unlockedStars, $titles = [])
    {
        $config = [];

        for ($i = 0; $i < $totalStars; $i++) {
            $isUnlocked = $i < $unlockedStars;
            $config[] = [
                'index' => $i,
                'state' => $isUnlocked ? 'gold' : 'gray',
                'title' => isset($titles[$i]) ? $titles[$i] : "成就星星 " . ($i + 1),
                'unlocked' => $isUnlocked,
                'description' => $isUnlocked ? '已解锁的成就' : '未解锁的成就'
            ];
        }

        return $config;
    }

    /**
     * 生成初始化脚本
     */
    private static function generateInitScript($containerId, $config, $starData)
    {
        $configJson = json_encode($config);
        $starDataJson = json_encode($starData);

        return sprintf(
            '<script>' .
            'document.addEventListener("DOMContentLoaded", function() {' .
            'var container = document.getElementById("%s");' .
            'if (container && typeof HeaSecStarSystem !== "undefined") {' .
            'var starSystem = new HeaSecStarSystem(container, %s);' .
            'var starData = %s;' .
            'starData.forEach(function(data, index) {' .
            'if (data.unlocked) {' .
            'setTimeout(function() { starSystem.unlockStar(index); }, index * 200);' .
            '}' .
            '});' .
            '}' .
            '});' .
            '</script>',
            $containerId,
            $configJson,
            $starDataJson
        );
    }

    /**
     * 加载模板文件
     */
    private static function loadTemplate($templateName)
    {
        $templatePath = __DIR__ . '/../templates/' . $templateName . '.php';
        if (file_exists($templatePath)) {
            ob_start();
            include $templatePath;
            return ob_get_clean();
        }
        return '';
    }

    /**
     * 获取星星状态统计
     */
    public static function getStarStats($starData)
    {
        $total = count($starData);
        $unlocked = 0;

        foreach ($starData as $star) {
            if ($star['unlocked'] || $star['state'] === 'gold') {
                $unlocked++;
            }
        }

        return [
            'total' => $total,
            'unlocked' => $unlocked,
            'locked' => $total - $unlocked,
            'progress' => $total > 0 ? round(($unlocked / $total) * 100, 1) : 0
        ];
    }

    /**
     * 验证配置参数
     */
    public static function validateConfig($config)
    {
        $required = ['starCount', 'size', 'gap'];

        foreach ($required as $key) {
            if (!isset($config[$key]) || !is_numeric($config[$key]) || $config[$key] < 0) {
                return false;
            }
        }

        return true;
    }

    /**
     * 获取可用预设
     */
    public static function getAvailablePresets()
    {
        return array_keys(self::$presets);
    }

    /**
     * 获取组件信息
     */
    public static function getComponentInfo()
    {
        return [
            'name' => 'HeaSec Star System',
            'version' => self::VERSION,
            'team' => '天积安全 (HeavenlySecret)',
            'created' => '2025-11-08',
            'migrated' => '2025-11-08',
            'description' => '华丽金属风格星星成就系统公共组件',
            'base_path' => self::BASE_PATH,
            'path_note' => '使用$commonBasePath参数进行路径管理，相对于公共组件目录'
        ];
    }
}

// 使用示例（取消注释以使用）
/*
// 在靶场页面中使用，通过$commonBasePath进行路径管理
$commonBasePath = '../../../common/';

// 基础使用
echo HeaSec_StarSystem::renderAssets($commonBasePath);
echo HeaSec_StarSystem::renderStarSystem();

// 使用预设配置
echo HeaSec_StarSystem::renderPresetStarSystem('compact');

// 高级使用
$config = [
    'starCount' => 5,
    'size' => 100,
    'gap' => 25,
    'animated' => true,
    'interactive' => true,
    'congratsModal' => true
];
echo HeaSec_StarSystem::renderStarSystem($config);

// 成就系统
$achievedCount = 2;
$thresholds = [1, 3, 5, 10, 20];
$titles = ['新手入门', '初级掌握', '中级熟练', '高级精通', '专家大师'];
echo HeaSec_StarSystem::renderAchievementStars($achievedCount, $thresholds, $titles);

// 包含恭喜弹窗的资源引入
echo HeaSec_StarSystem::renderAssets($commonBasePath, ['congrats' => true]);
*/

?>