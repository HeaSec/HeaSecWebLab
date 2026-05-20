# HeaSec 华丽星星成就系统 - 公共组件

![HeaSec Logo](https://img.shields.io/badge/HeaSec-天积安全-blue.svg)
![Version](https://img.shields.io/badge/version-2.0.2-green.svg)
![License](https://img.shields.io/badge/license-MIT-orange.svg)

> **日积寸功，乐享安全** - 专为网络安全靶场设计的华丽金属质感五角星成就系统公共组件

## 📋 概述

这是 HeaSec 天积安全团队开发的华丽星星成就系统公共组件，从 `/test/star/` 迁移到 `/range/common/components/star-system/`。该组件为靶场项目提供统一的成就展示系统，具有华丽的金属质感和丰富的交互效果，支持多种预设配置、主题切换和完整的恭喜弹窗功能。

## ✨ 特性

- 🌟 **华丽金属质感** - 多层渐变、光泽效果、立体阴影
- 🎨 **极致视觉效果** - 金色发光、灰色金属、高光反射
- 🚀 **丰富动画效果** - 解锁动画、悬停特效、粒子系统
- 🎯 **完全可定制** - 尺寸、颜色、动画、交互全部可配置
- ⚡ **高性能优化** - GPU硬件加速、内存自动管理
- 📱 **响应式设计** - 完美适配各种屏幕尺寸
- 🔧 **易于集成** - 简单的API设计，一行代码即可使用
- 🎪 **预设配置** - 紧凑型、完整型、迷你型三种预设
- 🏆 **成就系统** - 支持基于数据库的成就计算
- 🎉 **恭喜弹窗** - 内置完整的恭喜弹窗系统
- 🔄 **智能导航** - 支持下一靶场自动跳转
- ♿ **无障碍支持** - 键盘导航、屏幕阅读器兼容

## 📁 文件结构

```
range/common/components/star-system/
├── README.md                              # 使用文档（本文件）
├── includes/
│   └── HeaSec_StarSystem.php             # 核心PHP渲染器类
├── css/
│   ├── heasec-star-system.css             # 主样式文件
│   └── heasec-congrats-modal.css          # 恭喜弹窗样式
├── js/
│   ├── heasec-star-system.js              # 主脚本文件
│   └── heasec-congrats-modal.js           # 恭喜弹窗脚本
├── assets/
│   └── svg/
│       ├── star-gold.svg                  # 金色星星SVG
│       └── star-gray.svg                  # 灰色星星SVG
└── templates/
    └── congrats-modal.php                 # 恭喜弹窗模板
```

## 🚀 快速开始

### 1. 基础集成

```php
<?php
// 设置公共组件基础路径（相对于靶场页面的路径）
$commonBasePath = '../../../common/';

// 引入星星系统组件
require_once $commonBasePath . 'components/star-system/includes/HeaSec_StarSystem.php';

// 引入CSS和JS资源
echo HeaSec_StarSystem::renderAssets($commonBasePath);

// 渲染星星系统
echo HeaSec_StarSystem::renderStarSystem();
?>
```

### 2. 使用预设配置

```php
<?php
// 紧凑型（3颗星，60px，无粒子效果）
echo HeaSec_StarSystem::renderPresetStarSystem('compact');

// 完整型（5颗星，100px，全功能）
echo HeaSec_StarSystem::renderPresetStarSystem('full');

// 迷你型（3颗星，40px，无动画）
echo HeaSec_StarSystem::renderPresetStarSystem('mini');
?>
```

### 3. 成就系统集成

```php
<?php
// 基于用户成就数据渲染星星
$achievedCount = 2; // 从数据库获取
$thresholds = [1, 3, 5, 10]; // 解锁阈值
$titles = ['新手', '熟练', '专家', '大师']; // 星星标题

echo HeaSec_StarSystem::renderAchievementStars(
    $achievedCount,
    $thresholds,
    $titles,
    ['animated' => true, 'interactive' => true]
);
?>
```

## 🔧 集成方式

### 在靶场页面中的完整集成示例

```php
<?php
// 1. 设置公共组件基础路径
$commonBasePath = '../../../common/';

// 2. 引入公共头部（如果需要）
// require_once $commonBasePath . 'includes/header.php';

// 3. 引入星星系统组件
require_once $commonBasePath . 'components/star-system/includes/HeaSec_StarSystem.php';

// 4. 在<head>中引入资源文件（默认引入CSS和JS，congrats默认关闭）
echo HeaSec_StarSystem::renderAssets($commonBasePath, ['congrats' => true]);

// 5. 在页面中渲染星星系统
echo HeaSec_StarSystem::renderPresetStarSystem('full', [
    'showCongrats' => true,
    'congratsModal' => true
]);
?>
```

### 通过其他组件间接使用

星星系统也被其他组件（如 achievement-card）内部使用：

```php
<?php
// 成就卡片组件会自动引入和渲染星星系统
// 默认配置包含：title='成就系统'、thresholds=[1,2,3]、titles=['初学者','探索者','大师']
echo renderAchievementCard([
    'achievedCount' => 3,
    'rangeCode' => 'html'
], $commonBasePath);
?>
```

## 📖 API 参考

### HeaSec_StarSystem 类方法

#### `renderAssets($commonBasePath, $options = [])`
生成CSS和JS引入代码。

**参数:**
- `$commonBasePath` (string): 公共组件基础路径
- `$options` (array): 引入选项
  - `css` (bool): 是否引入CSS，**默认 `true`**
  - `js` (bool): 是否引入JavaScript，**默认 `true`**
  - `congrats` (bool): 是否引入恭喜弹窗资源，**默认 `false`**
  - `version` (string): 版本号（用于缓存控制），默认为组件版本

> **提示**：只需传入与默认值不同的选项即可。例如，需要恭喜弹窗时只需传 `['congrats' => true]`，无需再传 `'css' => true` 和 `'js' => true`。仅需CSS时传 `['js' => false]`。

**返回:** string HTML引入代码

#### `renderStarSystem($config = [], $starData = [])`
渲染完整的星星系统。

**参数:**
- `$config` (array): 配置选项
  - `starCount` (int): 星星数量，默认 3
  - `size` (int): 星星尺寸（像素），默认 80
  - `gap` (int): 星星间距（像素），默认 20
  - `animated` (bool): 是否启用动画，默认 true
  - `interactive` (bool): 是否可交互，默认 true
  - `particles` (bool): 是否启用粒子效果，默认 true
  - `theme` (string): 主题风格，默认 'luxury'
  - `containerClass` (string): 容器CSS类名，默认 'heasec-star-system'
  - `autoInit` (bool): 是否自动初始化，默认 true
  - `showCongrats` (bool): 是否显示恭喜弹窗，默认 true
  - `congratsModal` (bool): 是否使用恭喜弹窗，默认 true
- `$starData` (array): 星星数据（可选）

**返回:** string HTML代码

#### `renderPresetStarSystem($preset = 'compact', $config = [])`
使用预设配置渲染星星系统。

**参数:**
- `$preset` (string): 预设名称（'compact'/'full'/'mini'）
- `$config` (array): 额外配置

**返回:** string HTML代码

#### `renderAchievementStars($achievedCount, $thresholds = [1, 2, 3], $titles = [], $config = [])`
渲染基于数据库的成就星星。

**参数:**
- `$achievedCount` (int): 已达成数量
- `$thresholds` (array): 阈值数组
- `$titles` (array): 星星标题数组
- `$config` (array): 额外配置

**返回:** string HTML代码

### 预设配置

#### Compact (紧凑型)
- 星星数量: 3
- 尺寸: 60px
- 间距: 15px
- 粒子效果: 关闭
- 恭喜弹窗: 关闭

#### Full (完整型)
- 星星数量: 5
- 尺寸: 100px
- 间距: 25px
- 粒子效果: 开启
- 恭喜弹窗: 开启

#### Mini (迷你型)
- 星星数量: 3
- 尺寸: 40px
- 间距: 10px
- 动画: 关闭
- 粒子效果: 关闭
- 恭喜弹窗: 关闭

### JavaScript API

#### HeaSecStarSystem 类

```javascript
// 初始化星星系统
const starSystem = new HeaSecStarSystem(container, config);

// 获取已存在的实例（关键！）
// 组件初始化后会将实例挂载到 DOM 容器的 _heasecStarInstance 属性上
const existingInstance = document.querySelector('.heasec-star-system')._heasecStarInstance;

// 解锁星星
starSystem.unlockStar(index);              // 解锁指定索引的星星
starSystem.unlockMultipleStars(count);     // 批量解锁多颗星星（自动处理样式、动画和图标更新）

// 重置和管理
starSystem.resetStars();                   // 重置所有星星
starSystem.getUnlockedCount();             // 获取已解锁数量
starSystem.getTotalStars();                // 获取总星星数
starSystem.isAllUnlocked();                // 检查是否全部解锁
```

### 🧩 异步集成示例（无刷新更新）

在 AJAX 请求完成后，无需手动操作 DOM，直接调用组件实例方法即可优雅地更新星星状态：

```javascript
// 假设这是你的 AJAX 回调函数
function handleAchievementUpdate(data) {
    // 1. 获取星星容器
    var starContainer = document.querySelector('.heasec-star-system');
    
    // 2. 检查实例是否存在
    if (starContainer && starContainer._heasecStarInstance) {
        // 3. 调用 API 更新状态
        // unlockMultipleStars 会自动处理：
        // - 样式切换 (gray -> gold)
        // - 图标源更新 (src 替换)
        // - 解锁动画播放
        // - 触发恭喜弹窗 (如果全部解锁)
        starContainer._heasecStarInstance.unlockMultipleStars(data.star_count, true);
        
        console.log('星星状态已异步更新');
    }
}
```

## 🎯 使用示例

### 基础使用

```html
<!DOCTYPE html>
<html>
<head>
    <?php
    $commonBasePath = '../../../common/';
    require_once $commonBasePath . 'components/star-system/includes/HeaSec_StarSystem.php';
    echo HeaSec_StarSystem::renderAssets($commonBasePath);
    ?>
</head>
<body>
    <h2>学习进度</h2>
    <?php echo HeaSec_StarSystem::renderPresetStarSystem('compact'); ?>
</body>
</html>
```

### 完整功能示例

```php
<?php
// 设置完整配置
$config = [
    'starCount' => 5,
    'size' => 100,
    'theme' => 'luxury',
    'particles' => true,
    'showCongrats' => true,
    'congratsModal' => true
];

// 渲染星星系统
echo HeaSec_StarSystem::renderStarSystem($config);
?>
```

### 成就系统集成示例

```php
<?php
// 从数据库获取用户成就
$userChallenges = getUserCompletedChallenges($userId);

// 渲染成就星星
echo HeaSec_StarSystem::renderAchievementStars(
    $userChallenges,
    [1, 3, 5, 10, 20],
    ['新手入门', '初级掌握', '中级熟练', '高级精通', '专家大师'],
    [
        'animated' => true,
        'interactive' => true,
        'size' => 80,
        'gap' => 20
    ]
);
?>
```

## 🎉 恭喜弹窗功能

星星系统集成了完整的恭喜弹窗功能，支持智能导航和学习状态更新。

### 基本使用

```javascript
// 显示简单的恭喜消息
HeaSecCongratsModal.show({
    title: '🎉 恭喜你掌握了一个新技能',
    message: '你已完成本章节的学习内容',
    buttonText: '太棒了！'
});
```

### 完整配置示例

```javascript
HeaSecCongratsModal.show({
    title: '🎉 恭喜你掌握了一个新技能',
    message: '你理解了XXX的作用和原理...',
    buttonText: '继续学习',
    showParticles: true,
    particleCount: 10,
    animationDuration: 2500,

    // 启用下一靶场功能
    enableNextRangeButton: true,
    rangeCode: 'html',              // 当前靶场代码
    nextRangeApiUrl: heasecConfig.commonBasePath + 'api/next-range.php',

    // 自动更新学习状态
    updateLearningStatus: true,
    updateStatusApiUrl: heasecConfig.commonBasePath + 'api/update-learning-status.php',
    learningStatus: '已掌握',     // '已掌握' 或 '学习中'

    // 回调函数
    onClose: function() {
        console.log('恭喜消息弹窗已关闭');
    },
    onContinue: function() {
        console.log('用户选择继续学习');
    }
});
```

### 在靶场中的实际应用

```html
<button type="button" class="heasec-mastery-btn" onclick="showMasteryCongrats()">
    <i class="fa fa-check-circle"></i>
    我已掌握
</button>

<script>
function showMasteryCongrats() {
    if (typeof HeaSecCongratsModal !== 'undefined') {
        HeaSecCongratsModal.show({
            title: '🎉 恭喜你掌握了一个新技能',
            message: '你理解了HTML的基本语法，包括文档结构、文本标签、链接图片、表格和表单的使用...',
            buttonText: '继续学习',
            enableNextRangeButton: true,
            rangeCode: 'html',
            updateLearningStatus: true,
            updateStatusApiUrl: heasecConfig.commonBasePath + 'api/update-learning-status.php',
            nextRangeApiUrl: heasecConfig.commonBasePath + 'api/next-range.php'
        });
    }
}
</script>
```

## 🎮 事件系统

星星系统提供了丰富的事件接口，方便进行交互处理。

### 监听星星事件

```javascript
// 监听星星点击事件
document.addEventListener('heasec:starClick', function(e) {
    console.log('点击了第', e.detail.star + 1, '颗星星');
    console.log('星星数据:', e.detail.starData);
});

// 监听星星解锁事件
document.addEventListener('heasec:starUnlocked', function(e) {
    console.log('解锁进度:', e.detail.totalUnlocked + '/' + e.detail.totalStars);

    // 全部解锁完成时的处理
    if (e.detail.totalUnlocked === e.detail.totalStars) {
        showCongratulations();
    }
});

// 监听重置事件
document.addEventListener('heasec:starsReset', function(e) {
    console.log('星星系统已重置');
});

// 监听全部解锁完成事件
document.addEventListener('allStarsUnlocked', function(e) {
    console.log('所有星星已解锁！', e.detail);
});
```

### 事件数据结构

```javascript
// 星星点击事件
{
    star: 0,                  // 星星索引
    starData: {               // 星星数据
        index: 0,
        state: 'gold',
        title: '成就星星 1',
        unlocked: true
    }
}

// 星星解锁事件
{
    star: 0,                  // 解锁的星星索引
    totalUnlocked: 1,         // 已解锁总数
    totalStars: 5,            // 星星总数
    progress: 20              // 完成百分比
}
```

## 🎨 自定义主题

系统使用CSS变量实现主题定制，可以轻松修改外观。

### CSS变量列表

```css
:root {
    /* 基础尺寸 */
    --heasec-star-size: 80px;
    --heasec-star-gap: 20px;

    /* 金属质感颜色 - 金色主题 */
    --heasec-gold-primary: #FFD700;
    --heasec-gold-secondary: #FFA500;
    --heasec-gold-tertiary: #FF8C00;
    --heasec-gold-shadow: #B8860B;

    /* 金属质感颜色 - 灰色主题 */
    --heasec-gray-primary: #9E9E9E;
    --heasec-gray-secondary: #757575;
    --heasec-gray-tertiary: #616161;
    --heasec-gray-shadow: #424242;

    /* 效果强度 */
    --heasec-shadow-intensity: 0.7;
    --heasec-glow-intensity: 0.8;
    --heasec-highlight-intensity: 0.6;

    /* 动画效果 */
    --heasec-star-transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    --heasec-animation-duration: 0.6s;
    --heasec-particle-duration: 1s;
}
```

### 自定义主题示例

```css
/* 蓝色主题 */
.heasec-star-theme-blue {
    --heasec-gold-primary: #2196F3;
    --heasec-gold-secondary: #1976D2;
    --heasec-gold-tertiary: #1565C0;
    --heasec-gold-shadow: #0D47A1;
}

/* 绿色主题 */
.heasec-star-theme-green {
    --heasec-gold-primary: #4CAF50;
    --heasec-gold-secondary: #388E3C;
    --heasec-gold-tertiary: #2E7D32;
    --heasec-gold-shadow: #1B5E20;
}

/* 紫色主题 */
.heasec-star-theme-purple {
    --heasec-gold-primary: #9C27B0;
    --heasec-gold-secondary: #7B1FA2;
    --heasec-gold-tertiary: #6A1B9A;
    --heasec-gold-shadow: #4A148C;
}
```

## ❓ 常见问题

### 1. 星星不显示

**可能原因:**
- CSS或JS文件路径错误
- PHP类未正确引入
- 容器元素未找到

**解决方案:**
```php
<?php
// 确保路径正确
$commonBasePath = '../../../common/';
require_once $commonBasePath . 'components/star-system/includes/HeaSec_StarSystem.php';

// 确保引入资源
echo HeaSec_StarSystem::renderAssets($commonBasePath);

// 确保PHP版本兼容（需要5.6.9+）
?>
```

### 2. 动画效果不流畅

**可能原因:**
- 浏览器不支持CSS3动画
- GPU硬件加速未启用
- 同时动画元素过多

**解决方案:**
```css
/* 启用GPU硬件加速 */
.heasec-star {
    transform: translateZ(0);
    will-change: transform, opacity;
}

/* 减少动画数量 */
.particle {
    pointer-events: none;
}
```

### 3. 点击事件不响应

**可能原因:**
- `interactive`选项未启用
- 其他元素遮挡
- 事件绑定失败

**解决方案:**
```javascript
// 确保启用交互
const config = {
    interactive: true,
    autoInit: true
};

// 检查元素层级
document.querySelector('.heasec-star').style.zIndex = '10';
```

### 4. 恭喜弹窗不显示

**可能原因:**
- 恭喜弹窗资源未引入
- HeaSecCongratsModal类未加载
- 配置参数错误

**解决方案:**
```php
<?php
// 确保引入恭喜弹窗资源
echo HeaSec_StarSystem::renderAssets($commonBasePath, [
    'congrats' => true
]);
?>
```

```javascript
// 检查类是否加载
if (typeof HeaSecCongratsModal !== 'undefined') {
    HeaSecCongratsModal.show({...});
} else {
    console.error('恭喜弹窗组件未加载');
}
```

## 🌊 浏览器兼容性

| 浏览器 | 版本要求 | 支持状态 | 备注 |
|--------|----------|----------|------|
| Chrome | 60+ | ✅ 完全支持 | 推荐使用 |
| Firefox | 55+ | ✅ 完全支持 | |
| Safari | 12+ | ✅ 完全支持 | |
| Edge | 79+ | ✅ 完全支持 | Chromium版本 |
| IE | 11 | ⚠️ 部分支持 | 动画效果受限 |

## 📝 更新日志

### v2.0.2 (2026-02-11)
- 🔧 精简 `renderAssets` API 调用，仅需传入非默认选项
- 📚 更新示例代码，移除冗余默认参数
- 📖 增强 API 文档，明确标注各选项默认值

### v2.0.1 (2025-12-06)
- 📚 完全重写README文档，修正API说明
- 🔧 修正所有示例代码，确保可运行
- ✨ 添加恭喜弹窗完整使用说明
- 🎯 补充智能下一靶场功能说明
- 🎮 添加事件系统详细文档
- 🎨 完善主题定制指南
- ❓ 新增常见问题解决方案

### v2.0.0 (2025-11-08)
- ✨ 从 `/test/star/` 迁移到公共组件系统
- 🏗️ 重构为模块化架构，便于维护和扩展
- 🔧 新增预设配置系统（compact/full/mini）
- 🎨 新增多种主题变体（蓝色/绿色/紫色）
- 📱 优化响应式设计和移动端体验
- 🔗 集成到现有的 `ui-components.php` 系统
- ⚡ 性能优化和代码清理
- 📚 完善的文档和使用示例

### v1.0.1 (原始版本)
- ✨ 首次发布
- 🌟 华丽金属质感设计
- 🎨 完整的动画效果
- ⚡ 性能优化
- 📱 响应式设计
- 🔧 PHP集成支持

## 👥 团队信息

**天积安全团队 (HeaSec)**
- **团队名称**: 天积安全 (HeavenlySecret)
- **英文名称**: HeaSec (HeavenlySecret)
- **团队口号**: 日积寸功，乐享安全
- **项目主页**: [HeaSec天积安全](https://heasec.example.com)

## 📄 许可证

本项目采用 [MIT 许可证](LICENSE)。

## 🤝 贡献

欢迎提交 Issue 和 Pull Request 来改进这个项目。

---

⭐ 如果这个组件对你有帮助，请给我们一个 Star！