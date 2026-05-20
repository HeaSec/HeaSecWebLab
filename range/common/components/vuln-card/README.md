# HeaSec 漏洞挖掘卡片公共组件

![版本](https://img.shields.io/badge/版本-v1.2.0-blue.svg)
![团队](https://img.shields.io/badge/团队-天积安全-green.svg)
![PHP](https://img.shields.io/badge/PHP-5.6+-blue.svg)

漏洞挖掘卡片公共组件是为网络安全靶场设计的漏洞提交和验证组件，支持基于分数的星星解锁系统、漏洞提交表单、记录列表展示和恭喜消息功能。

支持两种工作模式：
- **localStorage 模式**（默认）：漏洞记录存储在浏览器 localStorage 中
- **数据库模式**（推荐）：通过 `HeaSec_VulnManager` 将漏洞记录持久化到数据库中

## ✨ 特性

- 🔍 **漏洞提交验证** - 支持提交漏洞URL、参数和类型进行验证
- ⭐ **星星解锁系统** - 基于分数的星星解锁机制，达到分数线自动解锁
- 📊 **分数进度展示** - 实时显示当前得分和满分
- 📝 **漏洞记录列表** - 展示已成功提交的漏洞记录
- 🎉 **恭喜消息** - 星星解锁时自动触发恭喜弹窗
- 🎨 **统一样式** - 与靶场tech-card风格保持一致
- 📱 **响应式设计** - 完美适配PC和移动设备
- 🌙 **暗色主题** - 支持系统暗色主题偏好

## 📁 文件结构

```
/range/common/components/vuln-card/
├── includes/
│   ├── HeaSec_VulnCard.php        # PHP核心渲染组件
│   └── HeaSec_VulnManager.php     # 漏洞管理器（数据库模式核心）
├── css/
│   └── vuln-card.css              # 漏洞卡片样式
├── js/
│   └── vuln-card.js               # JavaScript交互逻辑
└── README.md                      # 使用文档（本文件）
```

## 🚀 快速开始

### 1. 引入组件

```php
<?php
$commonBasePath = '../../../common/';
require_once $commonBasePath . 'components/vuln-card/includes/HeaSec_VulnCard.php';
?>
```

### 2. 基础使用（localStorage 模式）

```php
<?php
echo renderVulnCard([
    'rangeCode' => 'sqli_basic',
    'vulnTypes' => ['SQL注入', 'XSS跨站脚本', '任意文件读取'],
    'totalScore' => 0,
    'submittedRecords' => []
], $commonBasePath);
?>
```

### 3. 数据库模式使用（推荐）

数据库模式通过 `HeaSec_VulnManager` 将漏洞记录持久化到 MySQL 数据库中，需要以下步骤：

**步骤1：引入组件并创建 VulnManager 实例**

```php
<?php
$commonBasePath = '../../../common/';
require_once $commonBasePath . 'components/vuln-card/includes/HeaSec_VulnCard.php';
require_once $commonBasePath . 'components/vuln-card/includes/HeaSec_VulnManager.php';

// 创建漏洞管理器实例
$sessionId = HeaSec_VulnManager::generateSessionRecordKey();
$vulnManager = new HeaSec_VulnManager([
    'pdo'              => $pdo,                                    // PDO 连接
    'vulnConfigPath'   => __DIR__ . '/config/vuln_config.php',     // 漏洞定义文件
    'vulnRecordsTable' => 'heasec_myrange_vuln_records',           // 漏洞记录表
    'starStatusTable'  => 'heasec_myrange_star_status',            // 星星状态表
    'scoreThresholds'  => [30, 60, 100],                           // 星星解锁阈值
    'sessionId'        => $sessionId,                              // 当前会话ID
    'rangeCode'        => 'myrange',                               // 靶场编码
]);
?>
```

**步骤2：渲染漏洞卡片**

```php
<?php
echo renderVulnCard([
    'rangeCode' => 'myrange',
    'vulnTypes' => ['SQL注入', 'XSS跨站脚本'],
    'submittedRecords' => $vulnManager->getSubmittedRecords(),
    'totalScore' => $vulnManager->getTotalScore(),
    'maxScore' => 100,
    'vulnConfig' => [
        'validateApiUrl' => 'api/validate-vuln.php',
        'submitMethod' => 'POST',
    ],
], $commonBasePath);
?>
```

**步骤3：创建 API 文件**

每个靶场需要创建漏洞验证 API 文件（或在后台创建靶场时选择「漏洞挖掘模板」自动生成）：

```php
<?php
// api/validate-vuln.php - 漏洞验证接口
$vulnManager = createVulnManager(); // 你的初始化函数
$requestData = json_decode(file_get_contents('php://input'), true);
$result = $vulnManager->handleValidateRequest($requestData);
HeaSec_VulnManager::sendJsonResponse($result);
```

**步骤4：创建数据库表**

可使用 VulnManager 自动生成 SQL：

```php
<?php
// 生成建表语句
echo HeaSec_VulnManager::generateInitSQL('heasec_myrange_');
```

会生成 `heasec_myrange_vuln_records` 和 `heasec_myrange_star_status` 两张表。

## ⚙️ 配置选项

### 配置选项表格

| 参数 | 类型 | 默认值 | 说明 |
|------|------|--------|------|
| `title` | string | '漏洞挖掘' | 卡片标题 |
| `rangeCode` | string | '' | 靶场代码（必填，用于localStorage区分） |
| `starCount` | int | 3 | 星星数量 |
| `scoreThresholds` | array | [30, 60, 100] | 解锁每颗星需要的分数线 |
| `starTitles` | array | ['初级挖掘者', '中级挖掘者', '高级挖掘者'] | 每颗星星对应的标题 |
| `starSize` | int | 48 | 星星图标相对大小参数 |
| `starGap` | int | 12 | 星星之间的间距 |
| `showParticles` | bool | true | 是否在解锁时显示粒子特效 |
| `theme` | string | 'luxury' | 卡片星星主题样式 |
| `vulnTypes` | array | [] | 漏洞类型列表（必填，用于表单下拉选项） |
| `vulnConfig` | array | `['validateApiUrl' => '', 'submitMethod' => 'POST']` | 漏洞验证相关API和提交方式配置 |
| `submittedRecords` | array | [] | 已提交的漏洞记录 |
| `totalScore` | int | 0 | 当前总分 |
| `maxScore` | int | 100 | 满分（所有漏洞得分之和） |
| `storageKey` | string | 'vuln_card_star_count' | 本地存储使用的键名后缀 |
| `containerClass` | string | 'heasec-vuln-card' | 卡片容器的额外CSS类名 |
| `congratsConfig` | array | [] | 恭喜功能配置 |

### 分数线说明

- `scoreThresholds` 数组定义了解锁每颗星星所需的最低分数
- 例如 `[30, 60, 100]` 表示：
  - 得分 ≥ 30 分：解锁第1颗星星，获得"初级挖掘者"称号
  - 得分 ≥ 60 分：解锁第2颗星星，获得"中级挖掘者"称号
  - 得分 ≥ 100 分：解锁第3颗星星，获得"高级挖掘者"称号
- 满分应等于或大于最后一个分数阈值，以便用户能够解锁全部星星

### 漏洞类型配置

漏洞类型支持两种格式：

**简化格式（推荐）**
```php
'vulnTypes' => ['SQL注入', 'XSS跨站脚本', '任意文件读取']
```

**完整格式（兼容旧版）**
```php
'vulnTypes' => [
    ['value' => 'SQL注入', 'label' => 'SQL注入'],
    ['value' => 'XSS跨站脚本', 'label' => 'XSS跨站脚本'],
]
```

### 漏洞记录格式

支持两种格式：

**新格式（推荐，支持多参数）**
```php
$submittedRecords = [
    [
        'type' => 'SQL注入',        // 漏洞类型（直接使用中文）
        'url' => '/api/user.php',   // 漏洞URL
        'params' => [               // 参数列表（支持多个参数，对于无参漏洞可为空数组）
            ['name' => 'id', 'location' => 'GET'],
            ['name' => 'cookie:type', 'location' => 'HEAD']
        ],
        'score' => 30,              // 得分
        'time' => '2026-03-07 10:30' // 提交时间
    ],
];
```

**旧格式（兼容，单参数）**
```php
$submittedRecords = [
    [
        'type' => 'SQL注入',
        'url' => '/api/user.php',
        'param' => 'id',            // 单参数名
        'score' => 30,
        'time' => '2026-03-07 10:30'
    ],
];
```

### 漏洞配置文件格式

后端漏洞配置文件使用简洁的表格格式，支持多参数和参数位置：

```php
<?php
return [
    // 漏洞列表 - 表格格式
    // [URL路径, [[参数名, 位置], ...], 漏洞类型, 得分]（如果无参数则空数组[]）
    // 位置可选值: GET, POST, HEAD
    'vulns' => [
        // 单参数示例
        ['/api/user.php', [['id', 'GET']], 'SQL注入', 30],
        ['/api/search.php', [['keyword', 'POST']], 'SQL注入', 25],
        ['/api/comment.php', [['content', 'POST']], 'XSS跨站脚本', 20],
        ['/download.php', [['file', 'GET']], '任意文件读取', 25],
        // 无参数示例
        ['/api/get-user-list.php', [], '垂直越权', 100],

        // 多参数示例
        // ['/api/admin.php', [['id', 'GET'], ['role', 'POST']], '越权访问', 30],
        // ['/api/proxy.php', [['url', 'POST'], ['X-Forwarded-For', 'HEAD']], 'SSRF', 35],
    ],

    // 满分（可选，不填则自动计算）
    'maxScore' => 100,
];
```

**参数位置说明：**
- `GET` - URL查询参数 (如: ?id=1)
- `POST` - POST请求体参数
- `HEAD` - HTTP请求头 (如: X-Forwarded-For) 或其他请求数据 (如 cookie)

### 恭喜功能配置

```php
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
```

## 📡 后端API接口

### 验证API

组件需要后端提供漏洞验证API，接口规范如下：

**请求**
```
POST /api/validate-vuln.php
Content-Type: application/json

{
    "vuln_url": "/api/user.php",
    "vuln_type": "SQL注入",
    "params": [
        {"name": "id", "location": "GET"},
        {"name": "token", "location": "HEAD"}
    ],
    "range_code": "sqli_basic"
}
```

**参数列表格式**
- `params`: 验证时提交的参数数组，`location` 选项支持 `GET`, `POST`, `HEAD`。无参数时支持空数组 `[]`。

**成功响应**
```json
{
    "success": true,
    "data": {
        "valid": true,
        "score": 30,
        "message": "SQL注入漏洞验证成功！",
        "totalScore": 30,
        "unlockedStars": 1,
        "congratsShownStars": 0,
        "vulnInfo": {
            "type": "SQL注入",
            "url": "/api/user.php",
            "params": [{"name": "id", "location": "GET"}]
        }
    }
}
```

**重复提交响应**
```json
{
    "success": false,
    "data": { "already_submitted": true },
    "message": "该漏洞已提交过"
}
```

**验证失败响应**
该组件现已支持展示来自后端的具体失败原因提示 (`data.hint`)：
```json
{
    "success": true,
    "data": {
        "valid": false,
        "hint": "靶场标识不匹配"
    }
}
```
**注意**：组件目前会将成功提示固定为"漏洞审核通过"，将失败默认提示固定为"漏洞审核失败"，如果后端在 `data.hint` 中返回了更详细的原因，则会附加显示，如："漏洞审核失败：靶场标识不匹配"。

## 📖 使用示例

### 完整配置示例

```php
<?php
// 从数据库获取已提交记录
$records = getSubmittedVulnRecords($userId, 'sqli_basic');
$totalScore = calculateTotalScore($records);

echo renderVulnCard([
    'title' => 'SQL注入漏洞挖掘',
    'rangeCode' => 'sqli_basic',
    'starCount' => 3,
    'scoreThresholds' => [20, 35, 50],
    'starTitles' => ['入门黑客', '渗透测试员', '安全专家'],
    'vulnTypes' => ['时间盲注', '联合查询注入', '报错注入', 'WAF绕过'],
    'vulnConfig' => [
        'validateApiUrl' => $commonBasePath . 'api/validate-vuln.php',
    ],
    'submittedRecords' => $records,
    'totalScore' => $totalScore,
    'maxScore' => 50,
    'congratsConfig' => [
        'messages' => [
            'partial_title' => '🎯 太棒了！你解锁了一颗新星星！',
            'complete_title' => '🏆 恭喜！你已经解锁了全部星星！',
        ]
    ]
], $commonBasePath);
?>
```

## 🔄 事件系统

组件会触发以下自定义事件：

### `heasec:rangeReset`
（监听）当触发该事件时，组件会清除对应靶场的漏洞挖掘进度（本地存储的星星数量），主要用于靶场重置功能。

```javascript
// 触发重置（在其他地方）
document.dispatchEvent(new CustomEvent('heasec:rangeReset'));
```

### `heasec:vulnSubmitted`
漏洞提交成功时触发。

```javascript
document.addEventListener('heasec:vulnSubmitted', function(e) {
    console.log('漏洞提交成功:', e.detail);
    // { rangeCode, vulnType, score }
});
```

### `heasec:starUnlocked`
星星解锁时触发。

```javascript
document.addEventListener('heasec:starUnlocked', function(e) {
    console.log('星星解锁:', e.detail);
    // { starCount, previousCount, rangeCode }
});
```

### `heasec:vulnAllFound`
全部漏洞被发现（全部星星解锁）时触发。

```javascript
document.addEventListener('heasec:vulnAllFound', function(e) {
    console.log('全部完成:', e.detail);
    // { rangeCode, totalScore }
});
```

## 🎨 样式定制

组件使用CSS变量，可以轻松定制：

```css
.heasec-vuln-card {
    --vuln-primary: #e74c3c;
    --vuln-secondary: #c0392b;
    --vuln-success: #27ae60;
    --vuln-warning: #f39c12;
    --vuln-info: #3498db;
    --vuln-border-radius: 8px;
}
```

## 📱 响应式支持

组件已内置响应式设计，支持以下断点：

- **768px** - 平板
- **480px** - 手机

## 🌙 主题支持

- **暗色主题** - 自动适配系统暗色主题偏好
- **高对比度模式** - 支持`prefers-contrast: high`
- **减少动画模式** - 支持`prefers-reduced-motion: reduce`

## 🔧 路径处理

组件会根据传入的 `$commonBasePath` 自动处理资源路径：

| 靶场位置 | $commonBasePath 示例 |
|----------|---------------------|
| `/range/sqli/basic/` | `'../../../common/'` |
| `/range/base/http/httpxff/` | `'../../../common/'` |

## 📝 更新日志

### v1.2.0 (2026-03-17)
- 🗑️ 移除废弃的星星状态 API 和恭喜弹窗更新 API（改用 localStorage 管理）
- 🗑️ 清理废弃的 `fetchStarStatusFromApi`、`checkStarUnlock` 方法
- 🗑️ 清理废弃的 `updateCongratsShownStars`、`getCongratsShownStars`、`handleUpdateCongratsRequest` 方法
- 📝 简化文档，移除废弃 API 相关说明

### v1.1.0 (2026-03-11)
- 🆕 新增 `HeaSec_VulnManager` 漏洞管理器类，支持数据库持久化模式
- 🆕 内置 API 处理器（漏洞验证、星星状态、恭喜弹窗更新）
- 🆕 SQL 建表语句自动生成（`generateInitSQL`）
- 🆕 后台「漏洞挖掘模板」一键生成靶场骨架
- ⚡ 静态工具方法（URL/参数规范化、唯一键生成、会话ID管理）

### v1.0.0 (2026-03-07)
- ✨ 首次发布
- 🔍 支持漏洞提交和验证
- ⭐ 基于分数的星星解锁系统
- 📝 漏洞记录列表展示
- 🎉 恭喜消息功能

## 👥 团队信息

**天积安全团队 (HeaSec)**
- **团队名称**: 天积安全 (HeavenlySecret)
- **团队口号**: 日积寸功，乐享安全

---

⭐ 如果这个组件对你有帮助，请给我们一个 Star！
