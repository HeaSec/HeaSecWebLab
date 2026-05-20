# 文件Content-Type校验 靶场

## 靶场说明

学习仅依赖HTTP头部中的Content-Type进行文件上传校验存在的安全风险。

尝试抓包并修改Content-Type绕过校验机制，实现任意脚本文件上传，理解Content-Type校验的安全性缺陷。成功上传脚本后，需读取服务器上的 `images/secret.php` 文件获取通关密码完成验证。

---

*日积寸功，乐享安全*
