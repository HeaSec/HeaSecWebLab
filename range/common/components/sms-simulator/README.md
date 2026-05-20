# 短信模拟器组件 (SMS Simulator Component)

## 📱 组件简介

短信模拟器组件是天积安全（HeavenlySecret）团队开发的一个完整的手机短信模拟系统，专门为网络安全靶场设计。该组件可以模拟真实的短信发送和接收功能，方便在靶场中进行短信验证码、通知等场景的测试和学习。

**版本**: v1.0.0
**创建日期**: 2026-01-06
**团队**: 天积安全 (HeavenlySecret)
**许可证**: MIT

---

## ✨ 功能特点

### 核心功能
- ✅ **短信发送**：支持模拟发送短信到虚拟手机号
- ✅ **收件箱管理**：完整的短信收件箱系统，支持查看、删除、标记已读
- ✅ **验证码识别**：智能识别短信中的验证码，支持一键复制
- ✅ **发送日志**：记录所有短信发送尝试，包括成功和失败的记录
- ✅ **多号码管理**：支持添加多个虚拟手机号，可设置默认号码
- ✅ **易于集成**：提供简单的PHP集成接口，方便在靶场中快速集成

### 技术特点
- 🎯 **纯后端调用**：使用PHP后端API，不依赖前端JavaScript
- 🔧 **自动路径处理**：智能计算相对路径，在任何层级目录下都能正常工作
- 🔒 **安全设计**：完整的SQL注入防护和参数验证
- 📊 **详细日志**：记录IP地址、User-Agent等详细信息
- 🎨 **现代化UI**：采用科技蓝主题，响应式设计
- 🤖 **智能识别**：自动识别验证码并提供一键复制功能

### 验证码识别功能 🎯

组件内置智能验证码识别系统，可以自动识别短信中的验证码并提供一键复制功能。

#### 支持的验证码格式

| 格式类型 | 示例 | 说明 |
|---------|------|------|
| 中文格式（冒号） | `验证码：123456` | 支持中文冒号和英文冒号 |
| 中文格式（是） | `验证码是 123456` | 关键词后跟验证码 |
| 英文格式 | `verification code: 123456` | 不区分大小写 |
| 简短格式 | `code: 123456` | 简化的英文表达 |

#### 验证码规则

- **长度**：4-8位字符
- **字符类型**：数字（0-9）和字母（a-z，A-Z）
- **识别方式**：自动检测，无需手动配置
- **不区分大小写**：自动识别各种大小写组合

#### 使用说明

1. **自动识别**：当接收到的短信包含符合格式的验证码时，系统会自动识别
2. **显示按钮**：在短信气泡右侧自动显示"📋 复制验证码"按钮
3. **一键复制**：点击按钮即可将验证码复制到剪贴板
4. **视觉反馈**：
   - 点击后按钮文字变为"✓ 已复制"
   - 显示通知消息："验证码 XXX 已复制到剪贴板"
   - 2秒后按钮自动恢复原始状态

#### 示例

```
短信内容：
"您的验证码：123456，5分钟内有效。请勿泄露给他人。"

↓ 自动识别 ↓

识别结果：123456

↓ 显示按钮 ↓

[短信气泡内容...] [📋 复制验证码]

↓ 点击复制 ↓

验证码已复制到剪贴板！
```

#### 浏览器兼容性

- **现代浏览器**：使用 `navigator.clipboard` API
- **旧版浏览器**：自动降级到 `document.execCommand('copy')` 方法
- **全平台支持**：PC端和移动端均正常工作

---

## 📁 目录结构

```
sms-simulator/
├── api/                          # API接口目录
│   ├── send-sms.php              # 发送短信接口
│   ├── phone-list.php            # 获取手机号列表
│   ├── phone-add.php             # 添加手机号
│   ├── phone-edit.php            # 编辑手机号
│   ├── phone-delete.php          # 删除手机号
│   ├── phone-set-default.php     # 设置默认手机号
│   ├── phone-clear-sms.php       # 清空手机号短信
│   ├── phone-batch-add.php       # 批量添加手机号
│   ├── sms-list.php              # 获取短信列表
│   ├── sms-delete.php            # 删除短信
│   ├── sms-mark-read.php         # 标记短信为已读
│   ├── sms-mark-all-read.php     # 标记所有短信为已读
│   ├── sms-log-list.php          # 获取发送日志
│   ├── sms-log-batch-delete.php  # 批量删除日志
│   ├── sms-log-clear.php         # 清空日志
│   ├── init-database.php         # 初始化数据库
│   ├── check-database.php        # 检查数据库状态
│   └── check-status.php          # 检查组件状态
├── includes/                     # 核心库文件
│   ├── HeaSec_SmsSimulator.php   # 短信模拟器主类
│   └── HeaSec_SmsSender.php      # 短信发送器集成库
├── templates/                    # 模板文件
│   ├── tab-phones.php            # 手机号管理模板
│   ├── tab-sms.php               # 短信收件箱模板
│   └── tab-logs.php              # 发送日志模板
├── assets/                       # 静态资源
│   ├── css/                      # 样式文件
│   │   └── sms-simulator.css     # 组件样式
│   └── js/                       # JavaScript文件
│       └── sms-simulator.js      # 组件脚本
├── database/                     # 数据库文件
│   └── init_database.sql         # 数据库初始化脚本
├── manage.php                    # 管理界面入口
└── README.md                     # 本文件
```

---

## 🚀 安装部署

### 1. 数据库初始化

组件使用独立的数据库 `heasec_common`，包含以下数据表：

```sql
-- 手机号表
heasec_sms_simulator
-- 短信表
heasec_sms_message
-- 发送日志表
heasec_sms_log
```

**初始化方法1：通过管理界面**
访问 `manage.php`，首次访问时会自动检测并提示初始化数据库。

**初始化方法2：手动执行SQL**
```bash
mysql -u root -p < database/init_database.sql
```

### 2. 权限配置

确保PHP有权限访问数据库配置文件（通常位于 `range/common/database/`）。

### 3. 访问管理界面

在浏览器中访问：
```
http://localhost/heasecdev/range/common/components/sms-simulator/manage.php
```

---

## 📖 使用方法

### 方法1：在靶场中集成发送功能

#### 步骤1：引入集成库

在靶场页面中引入短信发送器：

```php
<?php
// 引入短信发送器集成库
require_once 'path/to/HeaSec_SmsSender.php';
?>
```

#### 步骤2：发送短信

```php
<?php
// 完整发送（返回详细结果）
$result = HeaSec_SmsSender::send('13800138000', '验证码123456', 'my_range');

if ($result['success']) {
    echo '发送成功！';
    if ($result['data']['sent']) {
        echo '短信已保存到收件箱';
    } else {
        echo '手机号未注册，短信未保存';
    }
} else {
    echo '发送失败：' . $result['message'];
}
?>
```

#### 快速发送（仅返回成功/失败）

```php
<?php
if (HeaSec_SmsSender::sendQuick('13800138000', '验证码123456', 'my_range')) {
    echo '发送成功！';
}
?>
```

### 方法2：显示短信模拟器按钮

在靶场页面顶部显示短信模拟器按钮：

```php
<?php
// 在引入 header.php 之前设置变量
$showSmsSimulator = true;
$commonBasePath = '../../../common/'; // 根据实际路径调整

// 引入公共头部
require_once $commonBasePath . 'includes/header.php';
?>
```

点击按钮会在新窗口打开短信模拟器管理界面。

### 方法3：直接调用API接口

使用POST请求调用发送接口：

```php
<?php
$data = array(
    'phone' => '13800138000',
    'message' => '验证码123456',
    'range_code' => 'my_range'
);

$ch = curl_init('http://localhost/heasecdev/range/common/components/sms-simulator/api/send-sms.php');
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json'));

$response = curl_exec($ch);
$result = json_decode($response, true);
curl_close($ch);
?>
```

---

## 🔌 API 接口文档

### 发送短信接口

**URL**: `api/send-sms.php`
**方法**: `POST`
**Content-Type**: `application/json`

#### 请求参数

| 参数名 | 类型 | 必填 | 说明 |
|--------|------|------|------|
| phone | string | 是 | 手机号（1开头，11位数字） |
| message | string | 是 | 短信内容（不超过500个字符） |
| range_code | string | 否 | 靶场代码，用于标识发送者 |

#### 响应格式

```json
{
    "success": true,
    "message": "短信发送成功",
    "data": {
        "phone": "13800138000",
        "sent": true,
        "log_id": "1"
    },
    "timestamp": 1736227200
}
```

#### 响应字段说明

| 字段名 | 类型 | 说明 |
|--------|------|------|
| success | boolean | 是否成功 |
| message | string | 响应消息 |
| data.sent | boolean | 短信是否实际保存到收件箱 |
| data.log_id | string | 日志ID |
| timestamp | int | 时间戳 |

### 其他接口

详细的API文档请参考各个接口文件中的注释说明。

---

## ⚙️ 配置说明

### 数据库配置

数据库配置文件位于 `range/common/database/config.php`，确保以下配置正确：

```php
'database' => 'heasec_common',
'host' => 'localhost',
'port' => 3306,
'username' => 'root',
'password' => 'your_password',
'charset' => 'utf8mb4'
```

### 路径配置

短信发送器会自动计算API接口URL，无需手动配置路径。

---

## 🔍 集成示例

### 示例1：验证码发送

```php
<?php
require_once '../../../common/components/sms-simulator/includes/HeaSec_SmsSender.php';

// 生成6位随机验证码
$code = str_pad(rand(0, 999999), 6, '0', STR_PAD_LEFT);

// 发送验证码
$result = HeaSec_SmsSender::send($_POST['phone'], "您的验证码是：{$code}，5分钟内有效。", 'verify_demo');

if ($result['success']) {
    // 将验证码保存到Session
    $_SESSION['verify_code'] = $code;
    $_SESSION['verify_time'] = time();

    echo json_encode(['success' => true, 'message' => '验证码已发送']);
} else {
    echo json_encode(['success' => false, 'message' => $result['message']]);
}
?>
```

### 示例2：通知发送

```php
<?php
require_once '../../../common/components/sms-simulator/includes/HeaSec_SmsSender.php';

$phone = '13800138000';
$title = '系统通知';
$content = '您的账户已于' . date('Y-m-d H:i:s') . '登录';

$result = HeaSec_SmsSender::send($phone, "[{$title}] {$content}", 'system_notify');
?>
```

### 示例3：批量发送

```php
<?php
require_once '../../../common/components/sms-simulator/includes/HeaSec_SmsSender.php';

$phones = ['13800138000', '13900139000', '13700137000'];
$message = '系统维护通知：系统将于今晚22:00进行维护，预计耗时2小时';

$successCount = 0;
foreach ($phones as $phone) {
    if (HeaSec_SmsSender::sendQuick($phone, $message, 'batch_notify')) {
        $successCount++;
    }
}

echo "发送完成：成功 {$successCount} / " . count($phones);
?>
```

---

## 🛠️ 常见问题

### Q1: 短信发送成功但收件箱中没有收到？

**A**: 这种情况通常是因为手机号未在短信模拟器中注册。请先在管理界面的"手机号管理"标签中添加手机号。

### Q2: 如何重置数据库？

**A**: 在管理界面点击"帮助说明"标签，然后点击"重置数据库"按钮。或手动执行 `database/init_database.sql` 文件。

### Q3: 集成后无法调用API？

**A**: 检查以下几点：
1. 确保引入的路径正确
2. 检查服务器是否支持cURL或file_get_contents
3. 查看PHP错误日志

### Q4: 手机号格式要求是什么？

**A**: 必须是中国大陆手机号格式：1开头，第二位是3-9，总共11位数字。正则表达式：`/^1[3-9]\d{9}$/`

### Q5: 短信内容长度限制？

**A**: 短信内容最多500个字符（中文字符按1个计算）。超过限制会被API拒绝。

### Q6: 如何在不同层级的靶场中集成？

**A**: 短信发送器会自动处理路径问题，无论在哪个层级目录下调用都能正确工作。只需确保引入路径正确即可。

### Q7: 验证码识别支持哪些格式？

**A**: 支持以下格式：
- 中文：`验证码：123456` 或 `验证码: 123456` 或 `验证码是 123456`
- 英文：`verification code: 123456` 或 `code: 123456`
- 验证码长度：4-8位数字或字母
- 不区分大小写

### Q8: 为什么没有显示复制验证码按钮？

**A**: 请检查以下几点：
1. 短信内容是否包含符合格式的验证码
2. 验证码长度是否在4-8位之间
3. 是否使用了支持的关键词（验证码、code等）
4. 浏览器是否支持JavaScript（需要启用）

### Q9: 复制验证码功能在所有浏览器都可用吗？

**A**: 是的，组件提供了双重保障：
- 现代浏览器使用 `navigator.clipboard` API
- 旧版浏览器自动降级到 `document.execCommand('copy')` 方法
- 支持PC端和移动端

### Q10: 可以自定义验证码识别规则吗？

**A**: 当前版本使用内置的识别规则，如需自定义可以修改 `js/heasec-sms-simulator.js` 文件中的 `extractVerificationCode` 方法。

---

## 📋 版本历史

### v1.0.0 (2026-01-07)
- ✨ 初始版本发布
- 🎯 实现短信发送功能
- 📱 实现收件箱管理
- 📊 实现发送日志
- 🔧 提供集成库
- 🎨 完善管理界面
- 🤖 **新增验证码识别功能**：智能识别短信中的验证码并提供一键复制
- 📋 支持多种验证码格式（中文/英文，4-8位字符）
- 📋 一键复制到剪贴板，支持现代浏览器和旧版浏览器

---

## 👥 团队信息

**中文名**: 天积安全
**英文名**: HeavenlySecret
**英文缩写**: HeaSec
**口号**: 日积寸功，乐享安全

---

## 📮 联系方式

如有问题或建议，欢迎通过以下方式联系：

- 📧 邮箱: support@heavenlysecret.com
- 🌐 网站: https://www.heavenlysecret.com
- 💬 Issue: 在项目仓库提交Issue

---

## 📄 许可证

MIT License

Copyright (c) 2026 HeavenlySecret Team

Permission is hereby granted, free of charge, to any person obtaining a copy of this software and associated documentation files (the "Software"), to deal in the Software without restriction, including without limitation the rights to use, copy, modify, merge, publish, distribute, sublicense, and/or sell copies of the Software, and to permit persons to whom the Software is furnished to do so, subject to the following conditions:

The above copyright notice and this permission notice shall be included in all copies or substantial portions of the Software.

THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM, OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE SOFTWARE.

---

## 🙏 致谢

感谢所有为这个项目做出贡献的开发者和用户！

---

**最后更新**: 2026-01-07
**文档版本**: v1.0.0
