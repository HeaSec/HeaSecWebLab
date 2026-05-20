<?php
// 从JavaScript示例代码.md文件中提取的五个子章节代码
$chapterCodes = [
    'dom_modification' => [
        'title' => 'DOM修改不同标签和位置',
        'html' => '<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <title>DOM修改示例 - JavaScript靶场</title>
</head>
<body>
    <div>
        <h1>DOM修改不同标签示例</h1>
        <p>点击按钮查看如何修改不同类型的HTML元素：</p>

        <!-- 按钮组 -->
        <div>
            <h3>1. 修改div元素</h3>
                <div id="demo-div">
                    这是一个div元素
                </div>
            <button onclick="modifyDivContent()">修改div内容</button>

            <h3>2. 修改p元素</h3>
            <p id="demo-p">这是一个段落元素</p>            
            <button onclick="modifyPContent()">修改p内容</button>

            <h3>3. 修改img元素</h3>
            <img id="demo-img" src="./images/light.svg"  width="200" height="200">
            <br>
            <button onclick="modifyImgSrcLightOn()">修改图片src实现开灯</button>
            <button onclick="modifyImgSrcLightOff()">修改图片src实现关灯</button>

            <h3>4. 修改input元素</h3>
            <input type="text" id="demo-input" value="原始文本">
            <br>
            <button onclick="modifyInputValue()">修改输入框值</button>
 
            <h3>5. 添加和删除元素</h3>
            <!-- 演示区域 -->
            <div id="demo-container">
            </div>
                
            <button onclick="addNewElement()">增加新元素</button>
            <button onclick="removeElement()">删除新元素</button>
        </div>
    </div>

    <script>
        // 1. 修改div元素 - innerHTML可以修改HTML内容，包含HTML标签
        function modifyDivContent() {
            var div = document.getElementById("demo-div");
            div.innerHTML = "<strong>✅ div内容已修改</strong> - 使用innerHTML添加HTML标签";
            console.log("[HeaSec] div内容修改成功");
        }

        // 2. 修改p元素 - textContent只修改纯文本内容，不会解析HTML标签
        function modifyPContent() {
            var p = document.getElementById("demo-p");
            p.textContent = "✅ p元素文本内容已修改 - 使用textContent设置纯文本";
            console.log("[HeaSec] p元素内容修改成功");
        }

        // 3. 修改img元素 - src修改图片源地址
        function modifyImgSrcLightOn() {
            var img = document.getElementById("demo-img");
            img.src = "./images/light.svg";
            console.log("[HeaSec] 图片src修改成功");
        }

        function modifyImgSrcLightOff() {
            var img = document.getElementById("demo-img");
            img.src = "./images/dark.svg";
            console.log("[HeaSec] 图片src修改成功");
        }

        // 4. 修改input元素 - value修改输入框的文本值
        function modifyInputValue() {
            var input = document.getElementById("demo-input");
            input.value = "改成了新的输入文本";
            console.log("[HeaSec] 输入框值修改成功");
        }


        // 5. 添加和删除元素 - createElement()创建新元素，appendChild()添加到父元素，remove()删除元素
        function addNewElement() {
            var container = document.getElementById("demo-container");
            var newElement = document.createElement("div");
            newElement.id = "new-element";
            newElement.innerHTML = "✅ 新添加的元素 - 使用createElement和appendChild添加";
            container.appendChild(newElement);
            console.log("[HeaSec] 新元素添加成功");
        }

        function removeElement() {
            var element = document.getElementById("new-element");
            if (element) {
                element.remove();
                console.log("[HeaSec] 元素删除成功");
            } else {
                console.log("[HeaSec] 没有找到要删除的元素");
            }
        }

        console.log("[HeaSec] DOM修改示例已加载");
    </script>
</body>
</html>',
        'css' => '',
        'javascript' => ''
    ],

    'dom_selectors' => [
        'title' => 'DOM选择器使用',
        'html' => '<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <title>DOM选择器示例 - JavaScript靶场</title>
    <style>
        /* 演示元素的基础样式 */
        .demo-item {
            padding: 10px;
            margin: 5px 0;
            border: 2px solid #ddd;
            border-radius: 4px;
            background-color: #f9f9f9;
            transition: all 0.3s ease;
        }
    </style>
</head>
<body>
    <div>
        <h1>DOM选择器示例</h1>
        <p>点击按钮查看不同的DOM选择器使用方法，同时修改不同的样式属性：</p>

        <!-- 演示区域 -->
        <div>
            <div id="item1" class="demo-item">元素1 (ID: item1)</div>
            <div class="demo-item demo-special">元素2 (类: demo-special)</div>
            <div class="demo-item">元素3</div>
            <div class="demo-item demo-special">元素4 (类: demo-special)</div>
            <p class="demo-item">段落元素</p>
        </div>

        <!-- 按钮组 -->
        <div>
            <h3>1. 通过ID选择 - 修改背景色</h3>
            <button onclick="selectById()">getElementById</button>

            <h3>2. 通过类名选择 - 修改文字颜色</h3>
            <button onclick="selectByClassName()">getElementsByClassName</button>

            <h3>3. 通过标签名选择 - 修改边框样式</h3>
            <button onclick="selectByTagName()">getElementsByTagName</button>

            <h3>4. 使用querySelector选择 - 修改字体大小</h3>
            <button onclick="selectByQuerySelector()">querySelector</button>

            <h3>5. 使用querySelectorAll选择 - 修改内边距</h3>
            <button onclick="selectByQuerySelectorAll()">querySelectorAll</button>

            <h3>6. 重置所有样式</h3>
            <button onclick="resetAllStyles()">重置</button>
        </div>
    </div>

    <script>
        // 1. 通过ID选择 - getElementById，通过元素的ID属性选择，返回单个元素
        // 同时修改：内容和背景色样式
        function selectById() {
            var element = document.getElementById("item1");
            element.innerHTML = "✅ 通过getElementById选择 - 修改了背景色";
            element.style.backgroundColor = "#4CAF50"; // 修改背景色为绿色
            element.style.color = "#fff"; // 文字改为白色以保持对比度
            console.log("[HeaSec] getElementById选择成功 - 修改了backgroundColor样式");
        }

        // 2. 通过类名选择 - getElementsByClassName，通过元素的类名选择，返回HTMLCollection（类似数组）
        // 同时修改：内容和文字颜色样式
        function selectByClassName() {
            var elements = document.getElementsByClassName("demo-special");
            for (var i = 0; i < elements.length; i++) {
                elements[i].innerHTML = "✅ 通过getElementsByClassName选择 (" + (i + 1) + ") - 修改了文字颜色";
                elements[i].style.color = "#FF5722"; // 修改文字颜色为橙红色
            }
            console.log("[HeaSec] getElementsByClassName选择成功，找到 " + elements.length + " 个元素 - 修改了color样式");
        }

        // 3. 通过标签名选择 - getElementsByTagName，通过标签名选择，返回HTMLCollection
        // 同时修改：内容和边框样式
        function selectByTagName() {
            var elements = document.getElementsByTagName("div");
            for (var i = 0; i < elements.length; i++) {
                if (elements[i].className.includes("demo-item")) {
                    elements[i].innerHTML = "✅ 通过getElementsByTagName选择div (" + (i + 1) + ") - 修改了边框";
                    elements[i].style.border = "3px dashed #9C27B0"; // 修改边框为紫色虚线
                    elements[i].style.borderRadius = "8px"; // 添加圆角
                }
            }
            console.log("[HeaSec] getElementsByTagName选择成功，找到 " + elements.length + " 个div元素 - 修改了border样式");
        }

        // 4. 使用querySelector选择 - 选择第一个匹配元素，使用CSS选择器语法，返回第一个匹配的元素
        // 同时修改：内容和字体大小样式
        function selectByQuerySelector() {
            var element = document.querySelector(".demo-special");
            if (element) {
                element.innerHTML = "✅ 通过querySelector选择第一个.demo-special元素 - 修改了字体大小";
                element.style.fontSize = "20px"; // 修改字体大小
                element.style.fontWeight = "bold"; // 加粗
            }
            console.log("[HeaSec] querySelector选择成功 - 修改了fontSize和fontWeight样式");
        }

        // 5. 使用querySelectorAll选择 - 选择所有匹配元素，使用CSS选择器语法，返回NodeList（类似数组）
        // 同时修改：内容和内边距样式
        function selectByQuerySelectorAll() {
            var elements = document.querySelectorAll(".demo-item");
            elements.forEach(function(element, index) {
                element.innerHTML = "✅ 通过querySelectorAll选择 (" + (index + 1) + ") - 修改了内边距";
                element.style.padding = "20px"; // 修改内边距
                element.style.margin = "10px 0"; // 修改外边距
            });
            console.log("[HeaSec] querySelectorAll选择成功，找到 " + elements.length + " 个元素 - 修改了padding和margin样式");
        }

        // 6. 重置所有样式
        function resetAllStyles() {
            var elements = document.querySelectorAll(".demo-item");
            elements.forEach(function(element) {
                // 重置内容
                if (element.id === "item1") {
                    element.innerHTML = "元素1 (ID: item1)";
                } else if (element.classList.contains("demo-special")) {
                    element.innerHTML = element.previousElementSibling && element.previousElementSibling.classList.contains("demo-special")
                        ? "元素4 (类: demo-special)"
                        : "元素2 (类: demo-special)";
                } else if (element.tagName === "P") {
                    element.innerHTML = "段落元素";
                } else {
                    element.innerHTML = "元素3";
                }

                // 重置所有样式
                element.style.backgroundColor = "#f9f9f9";
                element.style.color = "";
                element.style.border = "2px solid #ddd";
                element.style.borderRadius = "4px";
                element.style.fontSize = "";
                element.style.fontWeight = "";
                element.style.padding = "10px";
                element.style.margin = "5px 0";
            });
            console.log("[HeaSec] 所有样式已重置");
        }

        console.log("[HeaSec] DOM选择器示例已加载 - 支持内容+样式修改");
    </script>
</body>
</html>',
        'css' => '',
        'javascript' => ''
    ],

    'events_popups' => [
        'title' => '事件与弹窗',
        'html' => '<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <title>事件与弹窗示例 - JavaScript靶场</title>
</head>
<body>
    <div>
        <h1>事件与弹窗示例</h1>
        <p>点击按钮查看不同类型的弹窗和事件处理：</p>

        <!-- 常见事件 -->
        <div>
            <h3>1. 常见事件处理</h3>
            <h4>加载事件</h4>
            <p>图片1：<img src="./images/light.svg" onload="alert(\'图片1加载成功\')" /></p>
            <p>图片2：<img src="不存在的图片.jpg" onerror="confirm(\'图片2加载失败，是否继续？\')"></p>

            <h4>鼠标/点击事件：</h4>
            <p id="event-message1">事件消息将显示在这里</p>
            <button onclick="handleClick()">点击事件</button>
            <button onmouseover="handleMouseOver()" onmouseout="handleMouseOut()">鼠标悬停/移出事件</button>
            <button oncontextmenu="handleContextMenu(event)">右键菜单事件</button>
            <br><br>

            <h4>输入事件：</h4>
            <p id="event-message2">事件消息将显示在这里</p>
            <input type="text" id="focus-input" onfocus="handleFocus()" onblur="handleBlur()" placeholder="点击获取焦点，离开失去焦点">
            <br><br>
            <input type="text" id="keyboard-input" onkeydown="handleKeyDown(event)" placeholder="按下任意键触发键盘事件">
            <br><br>
            <select id="select-input" onchange="handleChange()">
                <option value="">选择选项触发change事件</option>
                <option value="option1">选项一</option>
                <option value="option2">选项二</option>
                <option value="option3">选项三</option>
            </select>
        </div>

        <br><hr>

        <!-- 不同类型弹窗 -->
        <div>
            <h3>2. 不同类型弹窗：</h3>
            <p id="event-message3">事件消息将显示在这里</p>
            <button onclick="alert(\'这是一个alert弹窗\\n用于显示简单的消息提示\')">alert弹窗</button>
            <button onclick="confirm(\'这是一个confirm弹窗\\n点击确定返回true，取消返回false\')">confirm弹窗</button>
            <button onclick="prompt(\'这是一个prompt弹窗\\n请输入您的名字：\')">prompt弹窗</button>
            <br><br>
            <h4>使用函数调用弹窗</h4>
            <button onclick="showAlert()">alert弹窗</button>
            <button onclick="showConfirm()">confirm弹窗</button>
            <button onclick="showPrompt()">prompt弹窗</button>
        </div>
    </div>

    <script>
        // 不同类型弹窗函数
        function showAlert() {
            alert("这是一个alert弹窗\\n用于显示简单的消息提示");
            document.getElementById("event-message3").textContent = "alert弹窗已显示";
        }

        function showConfirm() {
            var result = confirm("这是一个confirm弹窗\\n点击确定返回true，取消返回false");
            if (result) {
                document.getElementById("event-message3").textContent = "用户点击了确定";
            } else {
                document.getElementById("event-message3").textContent = "用户点击了取消";
            }
        }

        function showPrompt() {
            var name = prompt("这是一个prompt弹窗\\n请输入您的名字：", "访客");
            if (name) {
                document.getElementById("event-message3").textContent = "您好，" + name + "！";
            } else {
                document.getElementById("event-message3").textContent = "您没有输入名字";
            }
        }

        // 常见事件处理函数
        function handleClick() {
            document.getElementById("event-message1").textContent = "按钮被点击了";
        }

        function handleMouseOver() {
            document.getElementById("event-message1").textContent = "鼠标悬停在按钮上";
        }

        function handleMouseOut() {
            document.getElementById("event-message1").textContent = "鼠标移出按钮";
        }

        function handleKeyDown(event) {
            var message = "按键被按下：键码=" + event.keyCode + "，按键=" + event.key;
            document.getElementById("event-message2").textContent = message;
        }

        function handleContextMenu(event) {
            event.preventDefault(); // 阻止默认右键菜单
            document.getElementById("event-message1").textContent = "右键点击事件被触发";
        }

        function handleFocus() {
            document.getElementById("event-message2").textContent = "输入框获得焦点";
        }

        function handleBlur() {
            document.getElementById("event-message2").textContent = "输入框失去焦点";
        }

        function handleChange() {
            var select = document.getElementById("select-input");
            var value = select.value;
            if (value) {
                document.getElementById("event-message2").textContent = "选择了选项：" + value;
            } else {
                document.getElementById("event-message2").textContent = "请选择一个选项";
            }
        }

        console.log("[HeaSec] 事件与弹窗示例已加载");
    </script>
</body>
</html>',
        'css' => '',
        'javascript' => ''
    ],

    'bom_operations' => [
        'title' => 'BOM操作',
        'html' => '<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <title>BOM操作示例 - JavaScript靶场</title>
</head>
<body>
    <div>
        <h1>BOM操作示例</h1>
        <p>点击按钮查看不同的浏览器对象操作：</p>

        <!-- 窗口操作 -->
        <div>
            <h3>1. 窗口操作 (Window)</h3>
            <button onclick="getWindowSize()">获取窗口大小</button>
            <button onclick="openNewWindow()">打开新窗口</button>
            <button onclick="closeNewWindow()">关闭新窗口</button>
            <button onclick="resizeWindow()">调整窗口大小</button>
            <button onclick="moveWindow()">移动窗口位置</button>
            <p id="window-info">窗口信息将显示在这里</p>
        </div>

        <!-- 地址栏操作 -->
        <div>
            <h3>2. 地址栏操作 (Location)</h3>
            <button onclick="getLocationInfo()">获取当前URL信息</button>
            <button onclick="goToUrl()">跳转到新页面</button>
            <p id="location-info">地址栏信息将显示在这里</p>
        </div>



    </div>

    <script>
        var newWindow = null; // 用于存储新窗口的引用

        // 窗口操作 (Window)
        function getWindowSize() {
            var width = window.innerWidth || document.documentElement.clientWidth || document.body.clientWidth;
            var height = window.innerHeight || document.documentElement.clientHeight || document.body.clientHeight;
            document.getElementById("window-info").textContent = "窗口大小：" + width + " x " + height + " 像素";
        }

        function openNewWindow() {
            if (!newWindow || newWindow.closed) {
                newWindow = window.open("", "_blank", "width=400,height=300");
                newWindow.document.write("<html><body><h2>新窗口</h2><p>这是通过JavaScript打开的新窗口</p></body></html>");
                document.getElementById("window-info").textContent = "新窗口已打开";
            } else {
                document.getElementById("window-info").textContent = "新窗口已经存在";
            }
        }

        function closeNewWindow() {
            if (newWindow && !newWindow.closed) {
                newWindow.close();
                newWindow = null;
                document.getElementById("window-info").textContent = "新窗口已关闭";
            } else {
                document.getElementById("window-info").textContent = "没有可关闭的新窗口";
            }
        }

        function resizeWindow() {
            if (newWindow && !newWindow.closed) {
                // 先将新窗口置于最上层，然后调整大小
                newWindow.focus();
                newWindow.resizeTo(600, 400);
                document.getElementById("window-info").textContent = "新窗口大小已调整为 600x400";
            } else {
                // 不调整主窗口大小，避免影响用户体验
                document.getElementById("window-info").textContent = "没有可调整大小的新窗口";
            }
        }

        function moveWindow() {
            if (newWindow && !newWindow.closed) {
                // 先将新窗口置于最上层，然后移动位置
                newWindow.focus();
                newWindow.moveTo(100, 100);
                document.getElementById("window-info").textContent = "新窗口已移动到位置 (100, 100)";
            } else {
                document.getElementById("window-info").textContent = "没有可移动的新窗口";
            }
        }

        // 地址栏操作 (Location)
        function getLocationInfo() {
            var location = window.location;
            var info = "URL: " + location.href + "\\n";
            info += "协议: " + location.protocol + "\\n";
            info += "主机名: " + location.hostname + "\\n";
            info += "端口号: " + location.port + "\\n";
            info += "路径: " + location.pathname;
            document.getElementById("location-info").textContent = info;
        }

        function goToUrl() {
            // 警告用户即将离开页面
            if (confirm("这将跳转到外部网站，确定要继续吗？")) {
                window.location.href = "https://www.baidu.com";
            }
        }




        console.log("[HeaSec] BOM操作示例已加载");
    </script>
</body>
</html>',
        'css' => '',
        'javascript' => ''
    ],

    'ajax_operations' => [
        'title' => 'Fetch API操作',
        'html' => '<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <title>Fetch API操作示例 - JavaScript靶场</title>
</head>
<body>
    <div>
        <h1>Fetch API操作示例</h1>
        <p><strong>说明：</strong>靶场已有一个服务端脚本来处理Fetch请求（fetch_server.php）。服务端脚本功能：接收姓名和性别参数，返回个性化的问候消息。</p>

        <!-- 表单区域 -->
        <div>
            <h3>请输入信息：</h3>
            <label for="name">姓名：</label>
            <input type="text" id="name" placeholder="请输入您的姓名" value="张三">

            <br><br>
            <label>性别：</label>
            <input type="radio" name="gender" value="male" id="male" checked> <label for="male">男</label>
            <input type="radio" name="gender" value="female" id="female"> <label for="female">女</label>

            <br><br>
            <button onclick="sendGetRequest()">发送GET请求</button>
            <button onclick="sendPostRequest()">发送POST请求</button>
        </div>

        <!-- 响应显示区域 -->
        <div>
            <h3>服务器响应：</h3>
            <p id="response-area">等待发送请求...</p>
        </div>

        <!-- 加载状态 -->
        <div id="loading" style="display: none;">
            <p>正在发送请求，请稍候...</p>
        </div>


    </div>

    <script>
        // 发送GET请求 - 使用Fetch API
        function sendGetRequest() {
            // 获取表单数据
            var name = document.getElementById("name").value || "访客";
            var gender = document.querySelector("input[name=\"gender\"]:checked").value;

            // 显示加载状态
            showLoading();

            // 构建请求URL（注意：这里使用事先创建的服务端脚本fetch_server.php）
            var url = "fetch_server.php?name=" + encodeURIComponent(name) + "&gender=" + gender;

            // 使用Fetch API发送GET请求
            // fetch()返回一个Promise，可以使用.then()和.catch()处理响应
            fetch(url)
                .then(function(response) {
                    // 检查响应状态
                    if (response.ok) {
                        // response.text()也返回一个Promise
                        return response.text();
                    }
                    throw new Error("网络响应不正常");
                })
                .then(function(data) {
                    // 隐藏加载状态
                    hideLoading();
                    // 显示服务器响应
                    document.getElementById("response-area").textContent = data;
                })
                .catch(function(error) {
                    // 隐藏加载状态
                    hideLoading();
                    // 显示错误信息
                    document.getElementById("response-area").textContent = "请求失败: " + error.message;
                });
        }

        // 发送POST请求 - 使用Fetch API
        function sendPostRequest() {
            // 获取表单数据
            var name = document.getElementById("name").value || "访客";
            var gender = document.querySelector("input[name=\"gender\"]:checked").value;

            // 显示加载状态
            showLoading();

            // 准备发送的数据
            var data = "name=" + encodeURIComponent(name) + "&gender=" + gender;

            // 使用Fetch API发送POST请求
            // fetch()第二个参数用于配置请求选项
            fetch("fetch_server.php", {
                method: "POST",                          // 请求方法
                headers: {                                // 请求头
                    "Content-type": "application/x-www-form-urlencoded"
                },
                body: data                                // 请求体数据
            })
            .then(function(response) {
                // 检查响应状态
                if (response.ok) {
                    return response.text();
                }
                throw new Error("网络响应不正常");
            })
            .then(function(data) {
                // 隐藏加载状态
                hideLoading();
                // 显示服务器响应
                document.getElementById("response-area").textContent = data;
            })
            .catch(function(error) {
                // 隐藏加载状态
                hideLoading();
                // 显示错误信息
                document.getElementById("response-area").textContent = "请求失败: " + error.message;
            });
        }

        // 显示加载状态
        function showLoading() {
            document.getElementById("loading").style.display = "block";
            document.getElementById("response-area").textContent = "正在发送请求...";
        }

        // 隐藏加载状态
        function hideLoading() {
            document.getElementById("loading").style.display = "none";
        }
    </script>
</body>
</html>',
        'css' => '',
        'javascript' => ''
    ]
];
?>