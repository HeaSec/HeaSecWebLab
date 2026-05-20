# 在线代码编辑器公共组件

## 概述

HeaSec天积安全团队的在线代码编辑器公共组件，提供HTML、CSS、JavaScript代码的在线编辑和实时预览功能。

## 功能特性

- **多语言支持**：支持HTML、CSS、JavaScript代码编辑
- **语法高亮**：基础语法高亮显示，提升代码可读性
- **行号显示**：带行号的代码编辑器，方便调试
- **实时预览**：点击运行代码按钮即可在右侧预览效果
- **响应式设计**：自适应不同屏幕尺寸，移动端友好
- **代码管理**：支持清空、重置、保存代码等操作
- **主题支持**：支持明暗主题切换
- **安全执行**：代码在iframe中运行，不影响主页面

## 文件结构

```
code-editor/
├── css/
│   └── heasec-code-editor.css          # 组件样式
├── js/
│   └── heasec-code-editor.js           # JavaScript交互
├── includes/
│   └── HeaSec_CodeEditor.php           # PHP组件逻辑
└── README.md                           # 组件说明
```

## 使用方法

### 基本用法

```php
<?php
// 引入公共组件
require_once '../../../common/components/code-editor/includes/HeaSec_CodeEditor.php';

// 渲染代码编辑器
echo renderCodeEditor([
    'cardTitle' => 'HTML练习编辑器',
    'height' => '500px',
    'defaultLanguage' => 'html'
]);
?>
```

### 高级配置

```php
<?php
echo renderCodeEditor([
    'cardTitle' => 'CSS样式练习',
    'cardIcon' => 'fa fa-css3',
    'height' => '600px',
    'fontSize' => '16px',
    'theme' => 'light',
    'lineNumbers' => true,
    'syntaxHighlighting' => true,
    'languages' => ['html', 'css', 'javascript'],
    'defaultLanguage' => 'css',
    'defaultCode' => [
        'html' => '<div class="box">Hello World</div>',
        'css' => '.box {\n    width: 200px;\n    height: 200px;\n    background: #007bff;\n    color: white;\n    display: flex;\n    align-items: center;\n    justify-content: center;\n    border-radius: 10px;\n}',
        'javascript' => 'console.log("Ready!");'
    ],
    'runButtonText' => '运行代码',
    'clearButtonText' => '清空',
    'resetButtonText' => '重置',
    'layout' => 'horizontal',
    'autoLoadAssets' => true
]);
?>
```

## 配置参数

### 基础配置

| 参数 | 类型 | 默认值 | 说明 |
|------|------|--------|------|
| `cardTitle` | string | '在线代码编辑器' | 组件标题 |
| `cardIcon` | string | 'fa fa-code' | 标题图标类名 |
| `editorId` | string | 'codeEditor' | 编辑器ID前缀 |
| `previewId` | string | 'codePreview' | 预览区ID前缀 |

### 编辑器配置

| 参数 | 类型 | 默认值 | 说明 |
|------|------|--------|------|
| `height` | string | '400px' | 编辑器高度 |
| `fontSize` | string | '14px' | 字体大小 |
| `theme` | string | 'light' | 主题（light/dark） |
| `lineNumbers` | boolean | true | 是否显示行号 |
| `syntaxHighlighting` | boolean | true | 是否启用语法高亮 |

### 语言配置

| 参数 | 类型 | 默认值 | 说明 |
|------|------|--------|------|
| `languages` | array | ['html', 'css', 'javascript'] | 支持的编程语言 |
| `defaultLanguage` | string | 'html' | 默认选中的语言 |
| `defaultCode` | array | 见下方 | 各语言的默认代码 |

### 默认代码示例

```php
'defaultCode' => [
    'html' => '<!DOCTYPE html>\n<html>\n<head>\n    <title>示例页面</title>\n</head>\n<body>\n    <h1>Hello, World!</h1>\n</body>\n</html>',
    'css' => 'body {\n    font-family: Arial, sans-serif;\n    margin: 20px;\n}',
    'javascript' => 'console.log("Hello, World!");'
]
```

### 按钮配置

| 参数 | 类型 | 默认值 | 说明 |
|------|------|--------|------|
| `runButtonText` | string | '运行代码' | 运行按钮文本 |
| `runButtonIcon` | string | 'fa fa-play' | 运行按钮图标 |
| `clearButtonText` | string | '清空代码' | 清空按钮文本 |
| `clearButtonIcon` | string | 'fa fa-trash' | 清空按钮图标 |
| `resetButtonText` | string | '重置代码' | 重置按钮文本 |
| `resetButtonIcon` | string | 'fa fa-refresh' | 重置按钮图标 |

### 布局配置

| 参数 | 类型 | 默认值 | 说明 |
|------|------|--------|------|
| `layout` | string | 'horizontal' | 布局方式（horizontal/vertical） |
| `splitRatio` | string | '50:50' | 左右分割比例 |
| `autoLoadAssets` | boolean | true | 是否自动引入资源文件 |

## JavaScript API

### 全局方法

```javascript
// 切换编程语言
HeaSecCodeEditor.switchLanguage(componentId, 'css');

// 运行代码
HeaSecCodeEditor.runCode(componentId);

// 清空代码
HeaSecCodeEditor.clearCode(componentId);

// 重置代码
HeaSecCodeEditor.resetCode(componentId);

// 刷新预览
HeaSecCodeEditor.refreshPreview(componentId);

// 获取代码
var code = HeaSecCodeEditor.getCode(componentId, 'html');

// 设置代码
HeaSecCodeEditor.setCode(componentId, '<div>Hello</div>', 'html');
```

### 事件监听

```javascript
// 监听初始化完成事件
document.getElementById('componentId').addEventListener('heasecCodeEditor:initialized', function(e) {
    console.log('编辑器初始化完成', e.detail);
});

// 监听语言切换事件
document.getElementById('componentId').addEventListener('heasecCodeEditor:languageChanged', function(e) {
    console.log('语言已切换到:', e.detail.language);
});

// 监听代码运行事件
document.getElementById('componentId').addEventListener('heasecCodeEditor:codeRun', function(e) {
    console.log('代码已运行', e.detail);
});
```

## CSS类名

### 主要类名

- `.heasec-code-editor` - 主容器
- `.heasec-code-editor-header` - 头部区域
- `.heasec-code-editor-tabs` - 标签页区域
- `.heasec-tab-button` - 标签按钮
- `.heasec-code-editor-main` - 主内容区域
- `.heasec-editor-pane` - 编辑器面板
- `.heasec-preview-pane` - 预览面板
- `.heasec-code-editor-actions` - 操作按钮区域

### 语法高亮类名

- `.tag` - HTML标签
- `.attr-name` - HTML属性名
- `.attr-value` - HTML属性值
- `.selector` - CSS选择器
- `.property` - CSS属性
- `.value` - CSS属性值
- `.keyword` - JavaScript关键字
- `.string` - JavaScript字符串
- `.comment` - 注释
- `.function` - 函数名
- `.number` - 数字

## 响应式断点

- **桌面端**：> 768px - 左右分栏布局
- **平板端**：≤ 768px - 上下分栏布局
- **手机端**：≤ 480px - 单列布局，按钮堆叠

## 浏览器兼容性

- Chrome 60+
- Firefox 55+
- Safari 12+
- Edge 79+

## 安全特性

- 代码在iframe的沙箱环境中运行
- 防止代码访问主页面DOM
- 支持CSP（内容安全策略）
- 自动转义HTML特殊字符

## 依赖项

### 必需依赖

- Font Awesome图标库（用于按钮图标）
- 现代浏览器（支持ES6语法）

### 可选依赖

- 无

## 版本信息

- **当前版本**：v1.0.0
- **创建日期**：2025-11-30
- **开发团队**：天积安全 (HeavenlySecret)

## 更新日志

### v1.0.0 (2025-11-30)

- 🎉 初始版本发布
- ✨ 支持HTML、CSS、JavaScript代码编辑
- ✨ 基础语法高亮功能
- ✨ 行号显示和交替行背景
- ✨ 响应式设计支持
- ✨ 实时代码预览功能
- ✨ 多主题支持
- ✨ 完整的JavaScript API

## 示例代码

### 简单HTML编辑器

```php
<?php
require_once '../../../common/components/code-editor/includes/HeaSec_CodeEditor.php';

echo renderCodeEditor([
    'cardTitle' => 'HTML基础练习',
    'height' => '450px',
    'defaultLanguage' => 'html',
    'defaultCode' => [
        'html' => '<!DOCTYPE html>\n<html>\n<head>\n    <title>我的第一个网页</title>\n    <style>\n        body {\n            font-family: Arial, sans-serif;\n            text-align: center;\n            margin-top: 50px;\n        }\n        h1 {\n            color: #007bff;\n        }\n    </style>\n</head>\n<body>\n    <h1>欢迎来到我的网页</h1>\n    <p>这是一个用HTML创建的简单页面。</p>\n</body>\n</html>'
    ]
]);
?>
```

### CSS样式练习器

```php
<?php
echo renderCodeEditor([
    'cardTitle' => 'CSS样式练习',
    'cardIcon' => 'fa fa-paint-brush',
    'height' => '500px',
    'defaultLanguage' => 'css',
    'defaultCode' => [
        'html' => '<div class="container">\n    <div class="card">\n        <h2>卡片标题</h2>\n        <p>这是一个卡片组件。</p>\n        <button class="btn">点击我</button>\n    </div>\n</div>',
        'css' => '.container {\n    padding: 20px;\n    background: #f8f9fa;\n}\n\n.card {\n    max-width: 400px;\n    margin: 0 auto;\n    background: white;\n    border-radius: 10px;\n    padding: 30px;\n    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);\n    text-align: center;\n}\n\n.btn {\n    background: #007bff;\n    color: white;\n    border: none;\n    padding: 12px 24px;\n    border-radius: 6px;\n    cursor: pointer;\n    transition: background 0.3s;\n}\n\n.btn:hover {\n    background: #0056b3;\n}'
    ]
]);
?>
```

## 联系方式

如有问题或建议，请联系天积安全团队。

**团队口号：日积寸功，乐享安全**