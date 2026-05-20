<?php
// 从HTML示例代码.md文件中提取的五个子章节代码
$chapterCodes = [
    'html_structure' => [
        'title' => 'HTML文档结构',
        'html' => '<!DOCTYPE html> <!-- 文档类型声明，告诉浏览器这是HTML5文档 -->
<html lang="zh-CN"> <!-- html根元素，lang属性指定页面语言 -->
<head>
    <meta charset="UTF-8"> <!-- 字符编码声明，支持中文显示 -->
    <meta name="viewport" content="width=device-width, initial-scale=1.0"> <!-- 移动端适配 -->
    <title>HTML基础示例</title> <!-- 浏览器标签页标题 -->
    <meta name="description" content="这是一个HTML基础学习示例"> <!-- 页面描述 -->
</head>
<body>
    <!-- 页面主要内容区域 -->
    <h1>欢迎学习HTML</h1>
    <p>这是一个完整的HTML文档结构示例</p>
</body>
</html>',
        'css' => '',
        'javascript' => ''
    ],

    'text_tags' => [
        'title' => '文本标签',
        'html' => '<h1>一级标题 - 最重要</h1>
<h2>二级标题 - 章节标题</h2>
<h3>三级标题 - 小节标题</h3>

<p>这是一个段落标签，用于包含连续的文本内容。
段落会自动换行，并且段落之间有适当的间距。</p>

<p>这是另一个段落。<br>使用br标签可以<br>强制换行。</p>

<hr> <!-- 水平分割线，用于分隔内容区域 -->

<p>段落前后使用hr标签分割线可以让页面结构更清晰</p>
<hr> <!-- 水平分割线，用于分隔内容区域 -->
<p>
    <strong>粗体文本</strong> - 重要内容强调<br>
    <b>粗体文本</b> - 视觉上的粗体<br>
    <em>斜体文本</em> - 语调强调<br>
    <i>斜体文本</i> - 视觉上的斜体<br>
    <u>下划线文本</u> - 链接或重点标记<br>
    <del>删除线文本</del> - 表示删除的内容<br>
    <ins>插入线文本</ins> - 表示新增的内容
</p>

<p>
    H<sub>2</sub>O 是水的化学式<br>
    X<sup>2</sup> 表示X的平方<br>
    <small>小号字体</small> 用于版权信息和注释
</p>

<p>
    <mark>高亮文本</mark> 用于标记重要内容<br>
    <code>代码文本</code> 用于显示代码片段<br>
    <kbd>键盘输入</kbd> 表示键盘按键
</p>',
        'css' => '',
        'javascript' => ''
    ],

    'links_images' => [
        'title' => '链接和图片',
        'html' => '<!-- 基本超链接 -->
<a href="https://www.example.com">访问示例网站（当前窗口）</a><br>
<a href="https://www.example.com" target="_blank">访问示例网站（新窗口打开）</a><br>
<hr>
<!-- 基本图片显示 -->
<img src="https://via.placeholder.com/300x200" alt="示例图片" width="300" height="200">
<!-- 带标题的图片 -->
<img src="https://via.placeholder.com/200x150" alt="学习图片" title="鼠标悬停显示的提示文字">

<!-- 图片加载失败时的替代显示 -->
<img src="不存在的图片.jpg" alt="图片加载失败"
     onerror="this.src=\'https://via.placeholder.com/200x150?text=图片加载失败\'">

<hr>
<!-- 图片链接 -->
<a href="https://www.example.com" target="_blank">
    <img src="https://via.placeholder.com/150x100" alt="点击访问网站" border="0">
</a>',
        'css' => '',
        'javascript' => ''
    ],

    'tables' => [
        'title' => '表格标签',
        'html' => '<!-- 基本表格结构 -->
<table border="1" cellpadding="8" cellspacing="0" width="100%">
    <caption>学生成绩表</caption>

    <!-- 表头区域 -->
    <thead>
        <tr style="background-color: #f0f0f0;">
            <th>姓名</th>
            <th>语文</th>
            <th>数学</th>
            <th>英语</th>
            <th>总分</th>
        </tr>
    </thead>

    <!-- 表格主体 -->
    <tbody>
        <tr>
            <td>张三</td>
            <td>85</td>
            <td>92</td>
            <td>78</td>
            <td>255</td>
        </tr>
        <tr>
            <td>李四</td>
            <td>90</td>
            <td>88</td>
            <td>95</td>
            <td>273</td>
        </tr>
        <tr>
            <td>王五</td>
            <td>76</td>
            <td>85</td>
            <td>82</td>
            <td>243</td>
        </tr>
    </tbody>

    <!-- 表格底部 -->
    <tfoot>
        <tr style="background-color: #f9f9f9; font-weight: bold;">
            <td>平均分</td>
            <td>83.7</td>
            <td>88.3</td>
            <td>85.0</td>
            <td>257.0</td>
        </tr>
    </tfoot>
</table>',
        'css' => '',
        'javascript' => ''
    ],

    'forms' => [
        'title' => '表单基础',
        'html' => '<!-- 基本表单结构 -->
<form action="/submit" method="post" enctype="multipart/form-data">

    <!-- 文本输入框 -->
    <label for="username">用户名：</label>
    <input type="text" id="username" name="username"
           placeholder="请输入用户名" required maxlength="20">
    <br><br>

    <!-- 密码输入框 -->
    <label for="password">密码：</label>
    <input type="password" id="password" name="password"
           placeholder="请输入密码" required minlength="6">
    <br><br>

    <!-- 数字输入框 -->
    <label for="age">年龄：</label>
    <input type="number" id="age" name="age"
           min="18" max="100" value="25">
    <br><br>

    <!-- 邮箱输入框 -->
    <label for="email">邮箱：</label>
    <input type="email" id="email" name="email"
           placeholder="example@email.com" required>
    <br><br>

    <!-- 日期选择器 -->
    <label for="birthday">生日：</label>
    <input type="date" id="birthday" name="birthday">
    <br><br>

    <!-- 文件上传 -->
    <label for="avatar">头像：</label>
    <input type="file" id="avatar" name="avatar" accept="image/*">
    <br><br>

    <!-- 隐藏字段 -->
    <input type="hidden" name="form_id" value="user_registration">

    <fieldset>
        <legend>性别选择</legend>
        <input type="radio" id="male" name="gender" value="male" checked>
        <label for="male">男</label>

        <input type="radio" id="female" name="gender" value="female">
        <label for="female">女</label>

        <input type="radio" id="other" name="gender" value="other">
        <label for="other">其他</label>
    </fieldset>
    <br>

    <!-- 复选框 -->
    <fieldset>
        <legend>兴趣爱好</legend>
        <input type="checkbox" id="reading" name="hobbies[]" value="reading">
        <label for="reading">阅读</label>

        <input type="checkbox" id="music" name="hobbies[]" value="music">
        <label for="music">音乐</label>

        <input type="checkbox" id="sports" name="hobbies[]" value="sports">
        <label for="sports">运动</label>

        <input type="checkbox" id="coding" name="hobbies[]" value="coding" checked>
        <label for="coding">编程</label>
    </fieldset>
    <br>

    <!-- 下拉选择框 -->
    <label for="city">所在城市：</label>
    <select id="city" name="city">
        <option value="">请选择城市</option>
        <optgroup label="一线城市">
            <option value="beijing">北京</option>
            <option value="shanghai">上海</option>
            <option value="guangzhou">广州</option>
            <option value="shenzhen">深圳</option>
        </optgroup>
        <optgroup label="二线城市">
            <option value="hangzhou">杭州</option>
            <option value="nanjing">南京</option>
            <option value="chengdu">成都</option>
        </optgroup>
    </select>
    <br><br>

    <!-- 多行文本框 -->
    <label for="message">留言内容：</label><br>
    <textarea id="message" name="message" rows="5" cols="40"
              placeholder="请输入您的留言内容...">这里是默认文本</textarea>
    <br><br>

    <!-- 按钮类型 -->
    <button type="submit">提交表单</button>
    <button type="reset">重置表单</button>
    <button type="button" onclick="alert(\'普通按钮\')">普通按钮</button>

    <!-- 提交按钮 -->
    <input type="submit" value="注册">
    <input type="reset" value="重置">

</form>',
        'css' => '',
        'javascript' => ''
    ],

    'css_html_combined' => [
        'title' => 'CSS与HTML结合',
        'html' => '<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CSS与HTML结合示例</title>

    <!-- 内部样式表：在head标签中使用style标签定义CSS -->
    <style>
        /* 全局样式 */
        body {
            font-family: \'Microsoft YaHei\', Arial, sans-serif;
            background-color: #f5f5f5;
            margin: 0;
            padding: 20px;
            box-sizing: border-box;
        }

        /* 通用盒子模型设置 */
        * {
            box-sizing: border-box;
        }

        /* 类选择器样式 */
        .container {
            max-width: 800px;
            margin: 0 auto;
            background-color: #ffffff;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            overflow: hidden;
        }

        /* ID选择器样式 */
        #main-title {
            color: #333;
            text-align: center;
            border-bottom: 2px solid #4CAF50;
            padding-bottom: 10px;
        }

        /* 标签选择器样式 */
        p {
            line-height: 1.8;
            color: #666;
        }

        /* 伪类选择器：鼠标悬停效果 */
        .btn-primary:hover {
            background-color: #45a049;
            transform: scale(1.05);
        }

        /* 组合选择器 */
        .highlight-box .box-title {
            font-weight: bold;
            color: #4CAF50;
        }

        /* 过渡动画 */
        .btn-primary {
            transition: all 0.3s ease;
        }

        /* 响应式表单样式 */
        .form-group {
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            margin-bottom: 15px;
            gap: 10px;
        }

        .form-group label {
            font-weight: bold;
            min-width: 80px;
            flex-shrink: 0;
        }

        .form-group input,
        .form-group select {
            flex: 1;
            min-width: 0;
            padding: 8px 12px;
            border: 1px solid #ddd;
            border-radius: 4px;
        }

        .form-buttons {
            display: flex;
            flex-wrap: wrap;
            justify-content: center;
            gap: 10px;
            margin-top: 20px;
        }

        .form-buttons button {
            flex: 1;
            min-width: 100px;
            max-width: 150px;
        }

        /* 响应式盒子模型 */
        .box-model-demo {
            max-width: 100%;
            word-wrap: break-word;
            overflow-wrap: break-word;
        }
    </style>
</head>
<body>

    <!-- 使用内联样式：直接在HTML标签中使用style属性 -->
    <div class="container" style="border-left: 5px solid #4CAF50;">
        <h1 id="main-title">CSS与HTML结合样式示例</h1>

        <p>这是一个展示CSS如何美化HTML元素的示例页面。</p>

        <!-- 使用类选择器样式 -->
        <div class="highlight-box" style="background-color: #f9f9f9; padding: 15px; margin: 20px 0; border-left: 3px solid #2196F3;">
            <p class="box-title">使用内部样式表</p>
            <p>通过在head标签中定义的style标签，可以为页面中的元素统一设置样式。</p>
        </div>

        <hr style="border: none; border-top: 1px dashed #ddd; margin: 30px 0;">

        <!-- 表单样式示例 -->
        <div style="background-color: #fff9e6; padding: 20px; border-radius: 5px;">
            <h3 style="color: #ff9800; margin-top: 0;">样式化表单</h3>

            <form>
                <div class="form-group">
                    <label for="styled-input">用户名：</label>
                    <input type="text" id="styled-input" placeholder="请输入用户名">
                </div>

                <div class="form-group">
                    <label for="styled-select">选择城市：</label>
                    <select id="styled-select">
                        <option value="">请选择城市</option>
                        <option value="beijing">北京</option>
                        <option value="shanghai">上海</option>
                        <option value="guangzhou">广州</option>
                    </select>
                </div>

                <div class="form-buttons">
                    <!-- 使用类选择器定义的按钮样式 -->
                    <button type="submit" class="btn-primary"
                            style="background-color: #4CAF50; color: white; padding: 10px 30px; border: none; border-radius: 5px; cursor: pointer; font-size: 16px;">
                        提交表单
                    </button>
                    <button type="reset"
                            style="background-color: #f44336; color: white; padding: 10px 30px; border: none; border-radius: 5px; cursor: pointer; font-size: 16px;">
                        重置
                    </button>
                </div>
            </form>
        </div>

        <!-- 文本样式示例 -->
        <div style="margin-top: 30px;">
            <h3 style="color: #9c27b0;">文本样式效果</h3>
            <p>
                <span style="color: #e91e63; font-weight: bold;">红色粗体文字</span> |
                <span style="color: #2196F3; font-style: italic;">蓝色斜体文字</span> |
                <span style="color: #4CAF50; text-decoration: underline;">绿色下划线文字</span>
            </p>
            <p style="text-align: center; background-color: #e3f2fd; padding: 10px; border-radius: 5px;">
                这段文字使用了居中对齐和背景色样式
            </p>
        </div>

        <!-- 盒子模型示例 -->
        <div style="margin-top: 30px;">
            <h3 style="color: #ff5722;">CSS盒子模型</h3>
            <div class="box-model-demo" style="
                background-color: #bbdefb;
                padding: 20px;
                margin: 10px 0;
                border: 2px solid #1976d2;
                border-radius: 8px;
                max-width: 100%;
            ">
                这个div展示了padding（内边距）、margin（外边距）、border（边框）和background-color（背景色）
            </div>
        </div>

        <!-- 定位和布局示例 -->
        <div style="margin-top: 30px;">
            <h3 style="color: #607d8b;">定位与布局</h3>
            <div style="position: relative; height: 100px; background-color: #eceff1; border-radius: 5px; overflow: hidden;">
                <div style="position: absolute; top: 10px; left: 10px; background-color: #f44336; color: white; padding: 5px 15px; border-radius: 3px; white-space: nowrap;">
                    绝对定位1
                </div>
                <div style="position: absolute; top: 10px; right: 10px; background-color: #4CAF50; color: white; padding: 5px 15px; border-radius: 3px; white-space: nowrap;">
                    绝对定位2
                </div>
                <div style="position: absolute; bottom: 10px; left: 50%; transform: translateX(-50%); background-color: #2196F3; color: white; padding: 5px 15px; border-radius: 3px; white-space: nowrap;">
                    居中定位
                </div>
            </div>
        </div>

    </div>

</body>
</html>',
        'css' => '',
        'javascript' => ''
    ]
];
?>