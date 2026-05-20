# HeaSec 公共组件库

**天积安全 (HeavenlySecret)** | *日积寸功，乐享安全*

## 概述

这是天积安全靶场平台的公共组件库，为所有靶场站点提供统一的UI组件、功能模块和基础服务。

**版本**: v1.0.0
**兼容性**: PHP 7.3+, 现代浏览器

---

## 目录结构

```
range/common/
├── components/              # UI组件
│   ├── achievement-card/   # 成就卡片组件
│   ├── code-editor/        # 代码编辑器组件
│   ├── secret-card/        # 密码验证卡片组件
│   ├── sms-simulator/      # 短信模拟器组件
│   ├── star-system/        # 星星成就系统组件
│   └── tech-blue/          # 科技蓝UI组件库
├── includes/               # 公共包含文件
│   ├── header.php          # 公共头部
│   ├── footer.php          # 公共底部
│   ├── database.php        # 数据库连接
│   ├── session_manager.php # 会话管理
│   ├── HeaSec_Database.php
│   └── HeaSec_LearningStatusUpdater.php
├── classes/                # 公共类库
│   ├── HeaSecPath.php
│   ├── HeaSec_Captcha.php
│   └── HeaSec_NextRangeDetector.php
├── api/                     # 公共API接口
│   ├── captcha.php
│   ├── next-range.php
│   └── update-learning-status.php
├── css/                     # 公共样式
├── js/                      # 公共脚本
└── assets/                  # 静态资源
```

---

## UI 组件

### 成就卡片组件 (achievement-card)

用于展示靶场学习进度的成就卡片，支持星星显示、记录列表和恭喜功能。

**文档**: [components/achievement-card/README.md](components/achievement-card/README.md)

```php
<?php
require_once $commonBasePath . 'components/achievement-card/includes/HeaSec_AchievementCard.php';
echo renderAchievementCard([
    'title' => '成就系统',
    'achievedCount' => 2,
    'thresholds' => [1, 2, 3],
    'titles' => ['初学者', '探索者', '大师'],
    'rangeCode' => 'my_range'
], $commonBasePath);
?>
```

### 代码编辑器组件 (code-editor)

提供HTML/CSS/JavaScript在线编辑和实时预览功能。

**文档**: [components/code-editor/README.md](components/code-editor/README.md)

```php
<?php
require_once $commonBasePath . 'components/code-editor/includes/HeaSec_CodeEditor.php';
echo renderCodeEditor([
    'cardTitle' => 'HTML练习编辑器',
    'height' => '500px',
    'defaultLanguage' => 'html'
]);
?>
```

### 密码验证卡片组件 (secret-card)

用于密码验证练习的卡片组件，支持MD5哈希验证和实时反馈。

**文档**: [components/secret-card/README.md](components/secret-card/README.md)

```php
<?php
require_once $commonBasePath . 'components/secret-card/includes/HeaSec_SecretCard.php';
$secret = HeaSec_GetSecret(20);
echo renderSecretCard([
    'cardTitle' => '秘密验证',
    'secretValue' => $secret,
    'rangeCode' => 'my_range'
], $commonBasePath);
?>
```

### 短信模拟器组件 (sms-simulator)

完整的手机短信模拟系统，支持短信发送、收件箱管理和验证码识别。

**文档**: [components/sms-simulator/README.md](components/sms-simulator/README.md)

```php
<?php
// 发送短信
require_once $commonBasePath . 'components/smsimulator/includes/HeaSec_SmsSender.php';
$result = HeaSec_SmsSender::send('13800138000', '验证码123456', 'my_range');
?>
```

### 星星成就系统组件 (star-system)

提供华丽的金属质感五角星展示和恭喜弹窗功能。

**文档**: [components/star-system/README.md](components/star-system/README.md)

```php
<?php
require_once $commonBasePath . 'components/star-system/includes/HeaSec_StarSystem.php';
echo HeaSec_StarSystem::renderPresetStarSystem('compact');
?>
```

### 科技蓝UI组件库 (tech-blue)

提供完整的科技风格UI组件库，包括卡片、按钮、表单、模态框等。

**文档**: [components/tech-blue/README.md](components/tech-blue/README.md)

---

## 公共服务

### 会话管理 (session_manager.php)

基于路径隔离的靶场会话管理，每个靶场的会话独立互不干扰。

```php
<?php
define('HEASEC_RANGE_ACCESS', true);
require_once $commonBasePath . 'includes/session_manager.php';
HeaSec_InitRangeSession('my_range');
HeaSec_ValidateSession();

// 生成秘密字符串
$secret = HeaSec_GetSecret(20);
?>
```

### 数据库连接 (database.php)

支持多数据库配置和连接管理的数据库组件。

**文档**: [README_Database_v2.md](README_Database_v2.md)

```php
<?php
require_once $commonBasePath . 'includes/database.php';
$pdo = heasec_db('my_range');
?>
```

### 学习状态更新

自动更新前台网站靶场学习状态的功能。

**文档**: [README_AutoLearningStatusUpdate.md](README_AutoLearningStatusUpdate.md)

```php
<?php
require_once $commonBasePath . 'includes/HeaSec_LearningStatusUpdater.php';
HeaSec_UpdateLearningStatusIfNeeded('my_range');
?>
```

### 验证码组件

轻量级的图片验证码生成和验证组件。

**文档**: [README_Captcha.md](README_Captcha.md)

### 智能下一靶场按钮

根据学习进度智能显示下一靶场或返回首页的导航功能。

**文档**: [README_NextRangeButton.md](README_NextRangeButton.md)

---

## 快速开始

### 1. 设置基础路径

```php
<?php
// 根据靶场在range目录下的层级设置路径
// 一级分类: range/category/link/ -> $commonBasePath = '../../common/';
// 二级分类: range/category/subcategory/link/ -> $commonBasePath = '../../../common/';
// 三级分类: range/category/subcategory/third/link/ -> $commonBasePath = '../../../../common/';

$commonBasePath = '../../../common/';
?>
```

### 2. 引入公共头部

```php
<?php
$pageTitle = '靶场标题';
$rangeName = '靶场名称';
$showVersion = false;
$showResetButton = true;

require_once $commonBasePath . 'includes/header.php';
?>
```

### 3. 使用组件

```php
<!-- 使用密码验证卡片 -->
<?php
require_once $commonBasePath . 'components/secret-card/includes/HeaSec_SecretCard.php';
echo renderSecretCard([...], $commonBasePath);
?>
```

### 4. 引入公共底部

```php
<?php
require_once $commonBasePath . 'includes/footer.php';
?>
```

---

## 命名规范

- **类名**: `HeaSec_` 前缀，如 `HeaSec_Captcha`
- **函数名**: `HeaSec_` 前缀，如 `HeaSec_InitRangeSession`
- **CSS类名**: `heasec-` 前缀，如 `heasec-card`
- **常量**: `HEASEC_` 前缀，如 `HEASEC_RANGE_ACCESS`

---

## 版本信息

**当前版本**: v1.0.0
**创建日期**: 2026-01-23
**维护团队**: 天积安全 (HeavenlySecret)

---

**日积寸功，乐享安全** 🚀
