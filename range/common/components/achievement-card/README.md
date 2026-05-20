# HeaSec 成就卡片公共组件

![版本](https://img.shields.io/badge/版本-v2.1.0-blue.svg)
![团队](https://img.shields.io/badge/团队-天积安全-green.svg)
![PHP](https://img.shields.io/badge/PHP-5.6+-blue.svg)

HeaSec成就卡片公共组件是从httpxff靶场提取的通用成就展示组件，专注于前端展示和交互，不包含业务逻辑。组件具有良好的路径处理能力，能适配不同层级的目录结构。

## ✨ 特性

- 🎯 **纯展示组件** - 不包含业务逻辑，成就触发由各靶场自行处理
- 🔧 **路径自适应** - 通过 `$commonBasePath` 参数适配不同目录层级
- ⭐ **星星系统集成** - 复用现有的star-system组件
- 📝 **自定义记录** - 支持显示自定义的成就记录列表
- 🎉 **恭喜功能** - 可选的恭喜消息功能，支持智能检测新成就
- 📱 **响应式设计** - 完美适配PC和移动设备
- 🎨 **样式一致** - 与靶场卡片样式保持完全一致
- 🌙 **暗色主题** - 支持系统暗色主题
- ♿ **无障碍支持** - 支持高对比度模式和减少动画模式

## 📁 文件结构

```
/range/common/components/achievement-card/
├── includes/
│   └── HeaSec_AchievementCard.php      # PHP核心组件函数
├── css/
│   └── achievement-card.css             # 成就卡片样式
├── js/
│   └── achievement-card.js              # JavaScript交互逻辑
├── examples/
│   └── basic-usage.php                  # 基础使用示例
└── README.md                            # 使用文档（本文件）
```

## 🚀 快速开始

### 1. 引入组件

```php
<?php
// 设置公共组件基础路径
$commonBasePath = '../../../common/';

// 引入成就卡片组件
require_once $commonBasePath . 'components/achievement-card/includes/HeaSec_AchievementCard.php';
?>
```

### 2. 渲染成就卡片

```php
<?php
// 基础使用（默认 title='成就系统', thresholds=[1,2,3], titles=['初学者','探索者','大师']）
echo renderAchievementCard([
    'achievedCount' => 2,  // 已达成2个成就
    'rangeCode' => 'myrange'  // 靶场代码（必填）
], $commonBasePath);
?>
```

### 3. 高级使用（带记录列表）

```php
<?php
// 带记录列表的完整示例（自定义非默认参数）
$records = [
    ['name' => 'X-Forwarded-For', 'count' => 5, 'time' => '2025-11-19 10:30'],
    ['name' => 'X-Real-IP', 'count' => 3, 'time' => '2025-11-19 09:15'],
];

echo renderAchievementCard([
    'achievedCount' => 2,
    'customRecords' => $records,
    'recordsTitle' => '成功使用过的请求头',
    'rangeCode' => 'httpxff'
], $commonBasePath);
?>
```

## ⚙️ 配置选项

### 基础配置

| 参数 | 类型 | 默认值 | 说明 |
|------|------|--------|------|
| `title` | string | '成就系统' | 成就卡片标题 |
| `showTitle` | boolean | true | 是否显示标题 |
| `achievedCount` | int | 0 | 已达成的成就数量 |
| `thresholds` | array | [1, 2, 3] | 星星解锁阈值 |
| `titles` | array | ['初学者', '探索者', '大师'] | 星星标题 |
| `rangeCode` | string | '' | 靶场代码（必填，用于localStorage区分） |

### 星星系统配置

| 参数 | 类型 | 默认值 | 说明 |
|------|------|--------|------|
| `starSize` | int | 48 | 星星尺寸（像素） |
| `starGap` | int | 12 | 星星间距（像素） |
| `showParticles` | boolean | true | 是否显示粒子效果 |
| `theme` | string | 'luxury' | 星星主题（luxury, blue, green等） |

### 记录列表配置

| 参数 | 类型 | 默认值 | 说明 |
|------|------|--------|------|
| `customRecords` | array | [] | 自定义记录列表 |
| `showRecords` | boolean | true | 是否显示记录列表 |
| `recordsTitle` | string | '成功记录' | 记录列表标题 |

### 恭喜功能配置

> **注意**：以下配置的默认值已在 v2.1.0 中调整，`enableCongrats`、`enableNextRangeButton`、`updateLearningStatus` 现在默认为 `true`，大多数情况下无需显式传入。

```php
'congratsConfig' => [
    'enableCongrats' => true,            // 默认开启恭喜功能
    'congratsMode' => 'auto',            // auto | manual
    'particleCount' => 8,                // 粒子数量
    'animationDuration' => 2000,         // 动画时长
    'enableNextRangeButton' => true,     // 默认开启下一靶场按钮
    'updateLearningStatus' => true,      // 默认自动更新学习状态
    'messages' => [
        'partial_title' => '🎉 恭喜你掌握了一个新技能',
        'complete_title' => '🏆 恭喜你获得了全部成就！',
        'partial' => '你已经掌握了 %d/%d 种技能！继续努力！',
        'complete' => '太棒了！你已经掌握了所有%d种技能！',
        'buttonText' => '继续学习'
    ]
]
```

> **提示**：只需传入需要覆盖默认值的参数。如果使用默认的恭喜配置，甚至不需要传入 `congratsConfig`。

## 📝 记录列表格式

自定义记录列表的格式：

```php
$records = [
    [
        'name' => '记录名称',      // 必填，记录的名称
        'count' => 5,            // 必填，成功次数
        'time' => '2025-11-19'   // 可选，时间戳
    ],
    // 更多记录...
];
```

## 🎉 恭喜功能

### 启用恭喜功能

恭喜功能在 v2.1.0 中默认开启，通常无需额外配置：

```php
echo renderAchievementCard([
    'achievedCount' => 1,
    'rangeCode' => 'httpxff'
], $commonBasePath);
```

如需禁用恭喜功能，可以传入：

```php
echo renderAchievementCard([
    'achievedCount' => 1,
    'rangeCode' => 'httpxff',
    'congratsConfig' => [
        'enableCongrats' => false
    ]
], $commonBasePath);
```

### 恭喜消息逻辑

恭喜功能会在以下情况下触发：
1. 页面加载时检测成就数量变化
2. 成就数量增加时自动显示恭喜
3. 恭喜消息仅在**新成就**时显示（通过localStorage比较）

### 自定义恭喜消息

```php
'congratsConfig' => [
    'enableCongrats' => true,
    'messages' => [
        'partial_title' => '🎯 太棒了！你找到了一个有用的请求头！',
        'complete_title' => '🏆 恭喜！你已经掌握了所有类型的请求头！',
        'partial' => '你已经成功使用了 %d 种不同的请求头！继续探索更多！',
        'complete' => '完美！你已经掌握了所有 %d 种请求头，成为真正的HTTP安全专家！',
        'buttonText' => '继续挑战'
    ]
]
```

## 🔧 路径处理

组件会根据传入的 `$commonBasePath` 自动处理资源路径：

| 靶场位置 | $commonBasePath 示例 | 说明 |
|----------|---------------------|------|
| `/range/base/http/httpxff/` | `'../../../common/'` | 当前靶场到/range的相对路径 |
| `/range/base/https/sslbasic/` | `'../../../common/'` | 同样适用于二级分类 |
| `/range/advanced/ctf/web/` | `'../../../../common/'` | 三级分类需要更多层级 |

## 🎨 样式定制

### 修改星星大小

```php
'starSize' => 60,  // 增大星星
'starGap' => 15    // 相应调整间距
```

### 修改主题

```php
'theme' => 'blue'  // 使用蓝色主题
```

支持的主题：
- `luxury` - 华丽金色（默认）
- `blue` - 科技蓝色
- `green` - 生命绿色
- `purple` - 神秘紫色

### 自定义样式

组件使用CSS变量，可以轻松定制：

```css
.heasec-achievement-card {
    --achievement-primary: #007BFF;
    --achievement-secondary: #0056b3;
    --achievement-border-radius: 15px;
}
```

## 📱 响应式支持

组件已内置响应式设计，支持以下断点：

- **1200px** - 大桌面
- **768px** - 平板
- **480px** - 手机

## 🌙 主题支持

- **暗色主题** - 自动适配系统暗色主题偏好
- **高对比度模式** - 支持`prefers-contrast: high`
- **减少动画模式** - 支持`prefers-reduced-motion: reduce`

## 🔄 事件系统

组件会触发以下自定义事件：

### `heasec:achievementCardInit`
组件初始化完成时触发。

```javascript
document.addEventListener('heasec:achievementCardInit', function(e) {
    console.log('成就卡片初始化:', e.detail);
});
```

### `heasec:achievementUnlocked`
成就解锁时触发。

```javascript
document.addEventListener('heasec:achievementUnlocked', function(e) {
    const { currentCount, previousCount, isComplete } = e.detail;
    console.log('成就解锁:', currentCount);
});
```

### `heasec:achievementCongratsShow`
恭喜弹窗显示时触发。

```javascript
document.addEventListener('heasec:achievementCongratsShow', function(e) {
    console.log('恭喜弹窗显示:', e.detail);
});
```

## 🔄 更新成就数量

可以通过JavaScript动态更新成就数量：

```javascript
// 触发全局成就更新事件
document.dispatchEvent(new CustomEvent('heasec:updateAchievement', {
    detail: {
        rangeCode: 'httpxff',  // 靶场代码
        count: 3               // 新的成就数量
    }
}));

// 或直接调用组件实例方法
const card = document.querySelector('.heasec-achievement-card')._heasecAchievementCard;
card.updateAchievementCount(3);
```

## 📖 实际使用示例

### 示例1：httpxff靶场

```php
<?php
// 获取数据库中的成就数据
$db = getHttpXFFDatabase();
$stmt = $db->query("SELECT COUNT(*) as count FROM heasec_httpxff_records");
$result = $stmt->fetch(PDO::FETCH_ASSOC);
$starCount = intval($result['count']);

// 获取记录列表
$stmt = $db->query("SELECT header_name, success_count, last_success_at FROM heasec_httpxff_records ORDER BY last_success_at DESC");
$records = $stmt->fetchAll(PDO::FETCH_ASSOC);

$formattedRecords = [];
foreach ($records as $record) {
    $formattedRecords[] = [
        'name' => $record['header_name'],
        'count' => $record['success_count'],
        'time' => $record['last_success_at']
    ];
}

// 渲染成就卡片（使用默认 title/thresholds/titles）
echo renderAchievementCard([
    'achievedCount' => $starCount,
    'customRecords' => $formattedRecords,
    'recordsTitle' => '成功使用过的请求头',
    'rangeCode' => 'httpxff'
], $commonBasePath);
?>
```

### 示例2：自定义成就阈值和恭喜消息

```php
<?php
// 自定义阈值和标题（覆盖默认的3颗星配置）
echo renderAchievementCard([
    'achievedCount' => 2,
    'thresholds' => [1, 2, 3, 5],
    'titles' => ['入门', '初级', '中级', '高级'],
    'customRecords' => [
        ['name' => '完成初级任务', 'count' => 10, 'time' => '2025-11-19'],
        ['name' => '完成中级任务', 'count' => 5, 'time' => '2025-11-18']
    ],
    'rangeCode' => 'myrange',
    // 自定义恭喜消息（覆盖默认值）
    'congratsConfig' => [
        'messages' => [
            'partial' => '你已经完成了 %d/%d 个任务！继续加油！',
            'complete' => '完美！你已经完成了所有 %d 个任务！'
        ]
    ]
], $commonBasePath);
?>
```

## ⚠️ 注意事项

1. **业务逻辑分离** - 组件仅负责展示，不处理成就计算逻辑
2. **路径配置** - 确保 `$commonBasePath` 正确配置
3. **rangeCode必填** - 用于区分不同靶场的localStorage，避免冲突
4. **依赖星星系统** - 组件依赖 `star-system` 组件渲染星星
5. **兼容性好** - 如果星星系统不可用，会自动降级到简单星星显示

## 🐛 故障排除

### 星星不显示
- 检查 `star-system` 组件是否正确引入
- 检查 `$commonBasePath` 是否正确
- 查看浏览器控制台是否有错误信息

### 恭喜消息不显示
- 检查 `congratsConfig.enableCongrats` 是否设置为 `true`
- 检查 `HeaSecCongratsModal` 组件是否已加载
- 确认成就数量确实增加了（localStorage比较）

### 样式异常
- 确认 `achievement-card.css` 已正确引入
- 检查是否有CSS冲突
- 查看浏览器开发者工具的Network面板确认资源加载成功

## 📝 更新日志

### v2.1.0 (2026-02-11)
- 🔧 重构组件架构，提取 CSS/JS 到外部文件
- ⚙️ 调整默认配置：`enableCongrats`/`enableNextRangeButton`/`updateLearningStatus` 默认改为 `true`
- 📦 精简 `data-config` 属性输出，仅输出非默认值
- 📚 更新文档和示例代码

### v1.0.0 (2025-11-19)
- ✨ 首次发布
- 🎯 从 httpxff 靶场提取为通用组件

## 📄 许可证

天积安全 (HeavenlySecret) 团队内部使用

## 👥 团队

**天积安全 (HeavenlySecret)**  
口号：日积寸功，乐享安全

---
