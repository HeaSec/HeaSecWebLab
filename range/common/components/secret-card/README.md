# HeaSec 密码验证卡片组件

## 概述

HeaSec 密码验证卡片是一个用于密码验证练习的公共组件，提供完整的用户输入验证、MD5哈希比对、实时反馈和恭喜弹窗功能。

**版本**: v1.0.0
**创建日期**: 2025-11-21
**团队**: 天积安全 (HeavenlySecret)

## 功能特性

- **表单验证** - 实时长度指示、格式校验、空值检查
- **MD5哈希** - 使用MD5哈希比对，避免直接暴露秘密
- **实时反馈** - 输入长度实时显示（0红色、1-19橙色、20蓝色）
- **恭喜弹窗** - 验证成功后显示恭喜消息
- **交互效果** - 卡片悬停发光、按钮加载状态、震动提示
- **键盘快捷键** - Ctrl+Enter提交、Escape重置

## 快速开始

### 1. 引入组件

```php
<?php
// 设置公共组件基础路径
$commonBasePath = '../../../common/';

// 引入密码验证卡片组件
require_once $commonBasePath . 'components/secret-card/includes/HeaSec_SecretCard.php';
?>
```

### 2. 生成秘密字符串

```php
<?php
// 使用会话管理组件生成秘密
require_once $commonBasePath . 'includes/session_manager.php';

define('HEASEC_RANGE_ACCESS', true);
HeaSec_InitRangeSession('my_range');
HeaSec_ValidateSession();

// 生成20位随机秘密
$secret = HeaSec_GetSecret(20);
?>
```

### 3. 渲染组件

```php
<?php
echo renderSecretCard([
    'cardTitle' => '秘密验证',
    'cardIcon' => 'fa fa-key',
    'secretValue' => $secret,
    'rangeCode' => 'my_range'
], $commonBasePath);
?>
```

## 配置参数

### 基础配置

| 参数 | 类型 | 默认值 | 说明 |
|------|------|--------|------|
| `cardTitle` | string | '秘密验证' | 卡片标题 |
| `cardIcon` | string | 'fa fa-key' | 标题图标 |
| `formId` | string | 'secretForm' | 表单ID前缀 |
| `inputId` | string | 'secret' | 输入框ID前缀 |

### 表单配置

| 参数 | 类型 | 默认值 | 说明 |
|------|------|--------|------|
| `inputLabel` | string | '输入你发现的秘密' | 输入框标签 |
| `inputPlaceholder` | string | '请输入20位的秘密字符串' | 占位符 |
| `maxLength` | int | 20 | 最大长度 |
| `helpText` | string | (见下方) | 帮助文本 |

默认帮助文本：
```
秘密格式：20位字母和数字组合（例如：AbCd1234EfGh5678IjKl）
```

### 按钮配置

| 参数 | 类型 | 默认值 | 说明 |
|------|------|--------|------|
| `submitText` | string | '验证秘密' | 提交按钮文本 |
| `submitIcon` | string | 'fa fa-sign-in' | 提交按钮图标 |
| `resetText` | string | '重置表单' | 重置按钮文本 |
| `resetIcon` | string | 'fa fa-refresh' | 重置按钮图标 |

### 验证配置

| 参数 | 类型 | 默认值 | 说明 |
|------|------|--------|------|
| `secretValue` | string | null | 秘密值（直接提供） |
| `secretCallback` | callable | null | 秘密值回调函数 |
| `validationPattern` | string | `/^[A-Za-z0-9]{20}$/` | 验证正则表达式 |

### 消息配置

| 参数 | 类型 | 默认值 | 说明 |
|------|------|--------|------|
| `successMessage` | string | '验证成功，恭喜你发现了秘密！' | 成功消息 |
| `successHint` | string | '' | 成功提示 |
| `errorMessage` | string | '验证失败，这不是我的秘密！' | 失败消息 |
| `emptyMessage` | string | '请输入秘密' | 空值消息 |
| `invalidLengthMessage` | string | '请输入20位的秘密字符串' | 长度错误消息 |
| `invalidFormatMessage` | string | '秘密格式不正确，请输入20位字母和数字组合' | 格式错误消息 |

### 恭喜弹窗配置

恭喜弹窗使用扁平参数结构（直接传入 `renderSecretCard` 配置数组，而非嵌套在 `congratsConfig` 中）：

| 参数 | 类型 | 默认值 | 说明 |
|------|------|--------|------|
| `enableCongrats` | bool | `true` | 是否启用恭喜弹窗 |
| `autoLoadAssets` | bool | `true` | 自动引入恭喜消息资源 |
| `congratsTitle` | string | '恭喜你掌握了一个新技能' | 恭喜标题 |
| `congratsMessage` | string | '你完成了密码验证挑战' | 恭喜消息 |
| `congratsButtonText` | string | '继续学习' | 按钮文本 |
| `rangeCode` | string | '' | 靶场代码（用于下一靶场跳转） |
| `showParticles` | bool | `true` | 是否显示粒子效果 |
| `particleCount` | int | `8` | 粒子数量 |
| `animationDuration` | int | `2000` | 动画时长（毫秒） |

> **提示**：只需传入与默认值不同的参数。如果使用默认配置，无需传入 `congratsButtonText`、`showParticles`、`particleCount`、`animationDuration` 等参数。

## 完整示例

```php
<?php
// 设置公共组件基础路径
$commonBasePath = '../../../common/';

// 定义访问常量
define('HEASEC_RANGE_ACCESS', true);

// 引入会话管理组件
require_once $commonBasePath . 'includes/session_manager.php';

// 初始化靶场会话
HeaSec_InitRangeSession('my_range');
HeaSec_ValidateSession();

// 引入密码验证卡片组件
require_once $commonBasePath . 'components/secret-card/includes/HeaSec_SecretCard.php';

// 生成秘密字符串
$secret = HeaSec_GetSecret(20);

// 渲染组件（仅传入必要参数，其余使用默认值）
echo renderSecretCard([
    'cardTitle' => '秘密验证',
    'cardIcon' => 'fa fa-key',
    'secretValue' => $secret,
    'congratsTitle' => '恭喜你掌握了一个新技能',
    'congratsMessage' => '你成功完成了密码验证靶场的学习',
    'rangeCode' => 'my_range'
]);

// 引入公共底部
require_once $commonBasePath . 'includes/footer.php';
?>
```

> **注意**：无需传入 `congratsButtonText`（默认 '继续学习'）、`showParticles`（默认 true）、`particleCount`（默认 8）、`animationDuration`（默认 2000）等参数。

## JavaScript API

### 初始化组件

```javascript
// 组件会自动初始化，无需手动调用
```

### 全局函数

```javascript
// 重置密码验证卡片
resetSecretCard(componentId);
```

### 事件监听

组件会触发以下自定义事件：

- `heasec:secretCardInit` - 组件初始化完成时触发
- `heasec:secretCardSubmit` - 表单提交时触发
- `heasec:secretCardReset` - 重置时触发

## 样式说明

组件使用 `tech-card` 和 `tech-form` 样式类，需要引入对应的样式文件：

```php
<!-- 在引入 header.php 时会自动引入 -->
<link rel="stylesheet" href="<?php echo $commonBasePath; ?>css/heasec_range.css">
```

## 文件结构

```
components/secret-card/
├── includes/
│   └── HeaSec_SecretCard.php    # PHP组件逻辑
├── css/
│   └── secret-card.css           # 组件样式
├── js/
│   └── secret-card.js            # JavaScript交互
└── README.md                     # 使用文档（本文件）
```

## 依赖项

- **必需**: `session_manager.php` - 用于生成秘密字符串
- **可选**: `HeaSecCongratsModal` - 恭喜弹窗组件
- **样式**: `heasec_range.css`

## 浏览器兼容性

- Chrome 60+
- Firefox 55+
- Safari 12+
- Edge 79+

## 📝 更新日志

### v1.0.1 (2026-02-11)
- 📚 修正恭喜弹窗配置文档，改为扁平参数结构说明
- 🔧 精简示例代码，移除冗余默认参数
- 📖 增强默认值说明，添加使用提示

### v1.0.0 (2025-11-21)
- ✨ 首次发布

---

**天积安全 (HeaSec) - 日积寸功，乐享安全**
