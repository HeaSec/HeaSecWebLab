# HeaSec Tech Blue UI 组件库

## 概述

HeaSec Tech Blue 是天积安全团队开发的科技蓝风格UI组件库，基于现代Web技术构建，提供丰富的UI组件和交互效果。

## 版本信息

- **版本**: v1.0.0
- **创建日期**: 2025-11-11
- **团队**: 天积安全 (HeavenlySecret)
- **兼容性**: PHP 5.6.9+, 现代浏览器 (Chrome, Firefox, Edge)

## 特性

- ✅ **完整的组件库**: 8个核心UI组件
- ✅ **科技蓝设计**: 现代化的科技风格设计
- ✅ **响应式设计**: 完美适配各种屏幕尺寸
- ✅ **动画系统**: 丰富的CSS动画和交互效果
- ✅ **主题系统**: 支持多主题切换
- ✅ **无障碍访问**: 完整的键盘导航和屏幕阅读器支持
- ✅ **性能优化**: GPU加速和硬件优化
- ✅ **模块化设计**: 按需加载和配置

## 快速开始

### 1. 引入组件库

```php
<?php
// 引入组件渲染器
require_once 'path/to/HeaSec_TechBlue.php';

// 渲染资源文件
echo HeaSec_TechBlue::renderAssets([
    'theme' => 'default',
    'animations' => true,
    'autoInit' => true
]);
?>
```

### 2. 基础使用

#### 渲染卡片
```php
echo HeaSec_TechBlue::renderCard([
    'title' => '卡片标题',
    'content' => '卡片内容',
    'variant' => 'default',
    'hover' => true
]);
```

#### 渲染按钮
```php
echo HeaSec_TechBlue::renderButton([
    'text' => '点击我',
    'variant' => 'primary',
    'size' => 'medium'
]);
```

#### 显示提示框
```php
echo HeaSec_TechBlue::renderAlert([
    'message' => '操作成功！',
    'type' => 'success',
    'title' => '成功'
]);
```

### 3. HTML直接使用

```html
<!DOCTYPE html>
<html>
<head>
    <!-- 引入样式 -->
    <link rel="stylesheet" href="css/heasec-tech-blue.css">
</head>
<body class="heasec-tech">
    <!-- 使用组件 -->
    <div class="heasec-tech-card">
        <div class="heasec-tech-card-header">
            <h3 class="heasec-tech-card-title">标题</h3>
        </div>
        <div class="heasec-tech-card-body">
            内容
        </div>
    </div>
    
    <!-- 引入脚本 -->
    <script src="js/heasec-tech-blue.js"></script>
    <script>
        HeaSec.TechBlue.init();
    </script>
</body>
</html>
```

## 组件文档

### 卡片组件 (Card)

```php
HeaSec_TechBlue::renderCard([
    'id' => 'card-1',              // 可选：卡片ID
    'title' => '卡片标题',          // 可选：标题
    'subtitle' => '副标题',         // 可选：副标题
    'content' => '卡片内容',        // 可选：内容HTML
    'footer' => '底部内容',         // 可选：底部HTML
    'variant' => 'default',        // 可选：default, elevated, flat, glass
    'size' => 'medium',           // 可选：small, medium, large
    'hover' => true,               // 可选：悬停效果
    'animation' => 'fadeIn',       // 可选：动画类型
    'class' => 'custom-class',     // 可选：自定义CSS类
    'attributes' => []              // 可选：HTML属性
]);
```

### 按钮组件 (Button)

```php
HeaSec_TechBlue::renderButton([
    'text' => '按钮文本',           // 必需：按钮文本
    'type' => 'button',            // 可选：button, submit, reset, link
    'variant' => 'primary',        // 可选：primary, secondary, success, warning, danger, info
    'size' => 'medium',           // 可选：small, medium, large
    'outline' => false,            // 可选：轮廓样式
    'rounded' => true,             // 可选：圆角样式
    'disabled' => false,           // 可选：禁用状态
    'loading' => false,            // 可选：加载状态
    'icon' => 'fa-star',           // 可选：图标类名
    'iconPosition' => 'left',      // 可选：left, right
    'href' => '',                  // 可选：链接地址
    'onClick' => '',               // 可选：点击事件
    'class' => '',                 // 可选：自定义CSS类
    'attributes' => []              // 可选：HTML属性
]);
```

### 提示框组件 (Alert)

```php
HeaSec_TechBlue::renderAlert([
    'message' => '提示信息',       // 必需：提示消息
    'type' => 'info',              // 可选：success, warning, danger, info
    'title' => '标题',             // 可选：提示标题
    'dismissible' => true,         // 可选：可关闭
    'icon' => true,                // 可选：显示图标
    'autoClose' => 0,              // 可选：自动关闭时间(毫秒)
    'position' => 'top-right',     // 可选：位置
    'class' => '',                 // 可选：自定义CSS类
    'attributes' => []              // 可选：HTML属性
]);
```

### 模态框组件 (Modal)

```php
HeaSec_TechBlue::renderModal([
    'id' => 'modal-1',             // 可选：模态框ID
    'title' => '模态框标题',        // 必需：标题
    'content' => '模态框内容',      // 必需：内容HTML
    'footer' => '底部内容',         // 可选：底部HTML
    'size' => 'medium',           // 可选：small, medium, large, fullscreen
    'closeOnEscape' => true,       // 可选：ESC键关闭
    'closeOnOverlay' => true,      // 可选：点击遮罩关闭
    'showCloseButton' => true,     // 可选：显示关闭按钮
    'centered' => true,            // 可选：居中显示
    'backdrop' => true,            // 可选：背景遮罩
    'animation' => 'fadeIn',       // 可选：动画类型
    'class' => '',                 // 可选：自定义CSS类
    'attributes' => []              // 可选：HTML属性
]);
```

### 表单组件 (Form)

```php
HeaSec_TechBlue::renderForm([
    'fields' => [
        [
            'type' => 'text',
            'name' => 'username',
            'label' => '用户名',
            'placeholder' => '请输入用户名',
            'required' => true
        ],
        [
            'type' => 'email',
            'name' => 'email',
            'label' => '邮箱',
            'placeholder' => 'example@email.com'
        ]
    ],
    'method' => 'post',
    'action' => '',
    'class' => '',
    'attributes' => []
]);
```

## JavaScript API

### 初始化组件库

```javascript
// 基础初始化
HeaSec.TechBlue.init();

// 带配置初始化
HeaSec.TechBlue.init({
    animations: {
        enabled: true,
        pageLoad: true,
        hoverEffects: true,
        transitions: true
    },
    performance: {
        gpuAcceleration: true,
        reduceMotion: false
    },
    accessibility: {
        keyboardNavigation: true,
        focusManagement: true
    }
});
```

### 显示提示框

```javascript
// 基础提示框
HeaSec.TechBlue.showAlert('消息内容', 'success', '标题', 5000);

// 自动关闭提示框
const alertId = HeaSec.TechBlue.showAlert('5秒后自动关闭', 'info', '信息', 5000);

// 关闭提示框
HeaSec.TechBlue.closeAlert(alertId);
```

### 创建模态框

```javascript
// 创建模态框
const modalId = HeaSec.TechBlue.createModal(
    '模态框标题',
    '模态框内容',
    {
        size: 'medium',
        footer: '<button class="heasec-tech-btn heasec-tech-btn-primary">确认</button>'
    }
);

// 关闭模态框
HeaSec.TechBlue.closeModal(modalId);
```

### 主题切换

```javascript
// 应用主题
HeaSecTechBlueConfig.applyTheme('default');

// 可用主题
// 'default' - 默认科技蓝
// 'darkTech' - 深空科技
// 'minimal' - 极简科技
// 'performance' - 性能优化

// 自定义主题
HeaSecTechBlueConfig.applyTheme('default', {
    customColor: '#0066cc'
});
```

## 主题系统

### 内置主题

1. **default** - 默认科技蓝主题
2. **darkTech** - 深空科技主题
3. **minimal** - 极简科技主题
4. **performance** - 性能优化主题

### 自定义主题

```javascript
// 创建自定义主题
HeaSecTechBlueConfig.saveComponentPreset('card', 'custom', {
    hover: true,
    shadow: 'large',
    animation: 'scaleIn'
});

// 导出配置
const config = HeaSecTechBlueConfig.exportConfig();

// 导入配置
HeaSecTechBlueConfig.importConfig(configJson);
```

## CSS变量系统

### 主要颜色变量

```css
--heasec-tech-primary: #007BFF;
--heasec-tech-success: #28A745;
--heasec-tech-warning: #FFC107;
--heasec-tech-danger: #DC3545;
--heasec-tech-info: #17A2B8;
```

### 间距变量

```css
--heasec-tech-space-1: 4px;
--heasec-tech-space-2: 8px;
--heasec-tech-space-3: 12px;
--heasec-tech-space-4: 16px;
--heasec-tech-space-5: 20px;
--heasec-tech-space-6: 24px;
```

### 圆角变量

```css
--heasec-tech-radius-sm: 6px;
--heasec-tech-radius: 10px;
--heasec-tech-radius-lg: 16px;
--heasec-tech-radius-xl: 24px;
```

## 响应式设计

### 断点系统

```css
/* 移动设备 */
@media (max-width: 480px) { }

/* 平板设备 */
@media (max-width: 768px) { }

/* 桌面设备 */
@media (min-width: 992px) { }

/* 大屏幕 */
@media (min-width: 1200px) { }
```

### 响应式工具类

```html
<div class="heasec-tech-d-flex heasec-tech-flex-column heasec-tech-md-flex-row">
    <!-- 在小屏幕垂直排列，中等屏幕水平排列 -->
</div>
```

## 动画系统

### 入场动画

- `heasec-tech-animate-fadeIn` - 淡入
- `heasec-tech-animate-fadeInUp` - 向上淡入
- `heasec-tech-animate-fadeInDown` - 向下淡入
- `heasec-tech-animate-scaleIn` - 缩放淡入
- `heasec-tech-animate-rotateIn` - 旋转淡入

### 悬停效果

- `heasec-tech-hover-lift` - 悬停浮起
- `heasec-tech-hover-glow` - 悬停发光
- `heasec-tech-hover-scale` - 悬停缩放

## 无障碍访问

### 键盘导航

- `Tab` - 导航元素
- `Enter`/`Space` - 激活按钮
- `Escape` - 关闭模态框

### 快捷键

- `Ctrl/Cmd + M` - 关闭所有模态框
- `Ctrl/Cmd + A` - 关闭所有提示框

### ARIA支持

所有组件都支持ARIA属性，确保屏幕阅读器兼容性。

## 性能优化

### GPU加速

```css
.heasec-tech-gpu-accelerated {
    transform: translateZ(0);
    backface-visibility: hidden;
    perspective: 1000px;
}
```

### 动画性能

```javascript
// 检测动画偏好
if (window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
    HeaSec.TechBlue.config.animations.enabled = false;
}
```

## 浏览器兼容性

- Chrome 60+
- Firefox 55+
- Safari 12+
- Edge 79+

## 示例

查看完整示例：

- `examples/demo.php` - PHP示例站点
- `examples/demo.html` - HTML示例站点

## 更新日志

### v1.0.0 (2025-11-11)

- 🎉 首次发布
- ✨ 完整的UI组件库
- 🎨 科技蓝设计风格
- 📱 响应式设计
- ⚡ 性能优化
- ♿ 无障碍访问支持

## 贡献指南

1. Fork 项目
2. 创建功能分支
3. 提交更改
4. 创建 Pull Request

## 许可证

MIT License - 天积安全团队

## 支持

如有问题或建议，请联系天积安全团队。

---

**天积安全团队 | 日积寸功，乐享安全**