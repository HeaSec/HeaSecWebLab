<?php
/**
 * HeaSec天积安全团队 - 原始 multipart/form-data 请求体解析器
 * Raw multipart/form-data body parser
 * 版本: v1.0.0
 * 创建日期: 2026-07-25
 * 团队: 天积安全 (HeavenlySecret)
 *
 * 用途：
 *   在关闭了 enable_post_data_reading 的环境（如 Docker + mod_php）下，
 *   PHP 不会自动解析 POST 体，$_FILES 为空，但 php://input 可读取原始请求体。
 *   本类用于从原始请求体中提取上传文件字段，得到未被 PHP 截断的原始文件名
 *   （包含二进制空字节 \x00 等特殊字符），以支撑真实的空字节截断绕过检测。
 *
 * 说明：
 *   这是一个面向本靶场教学场景的极简实现，仅提取第一个文件字段，
 *   不追求覆盖 RFC 全部边界情况，保持简单易维护。
 */

class HeaSec_RawMultipartParser {

    /**
     * 从原始 multipart 请求体中解析出第一个文件字段
     *
     * @param string $rawBody     php://input 读取到的原始请求体（二进制安全）
     * @param string $contentType 请求头 Content-Type（用于提取 boundary）
     * @param string $fieldName   目标文件字段名，默认 upload_file
     * @return array|null 成功返回 ['name' => 原始文件名, 'content' => 文件内容, 'size' => 内容长度]，失败返回 null
     */
    public static function parseFirstFile($rawBody, $contentType, $fieldName = 'upload_file') {
        // 从 Content-Type 中提取 boundary（可能被引号包裹）
        if (!preg_match('/boundary=("?)([^";\r\n]+)\1/i', $contentType, $bm)) {
            return null;
        }
        $delimiter = '--' . $bm[2];

        // 按分隔符切分各部分（explode 二进制安全）
        $segments = explode($delimiter, $rawBody);

        foreach ($segments as $segment) {
            // 跳过前导空段与结束段（结束段形如 "--\r\n"）
            if ($segment === '' || trim($segment) === '' || trim($segment) === '--') {
                continue;
            }

            // 去掉每段开头的 CRLF
            $segment = ltrim($segment, "\r\n");

            // 分离该部分的头部与内容（以空行 \r\n\r\n 分界）
            $sepPos = strpos($segment, "\r\n\r\n");
            if ($sepPos === false) {
                continue;
            }
            $headers = substr($segment, 0, $sepPos);
            $content = substr($segment, $sepPos + 4);

            // 去掉内容尾部的 CRLF（分隔符前的换行属于协议，不属于内容）
            if (substr($content, -2) === "\r\n") {
                $content = substr($content, 0, -2);
            }

            // 仅处理目标文件字段
            if (stripos($headers, 'name="' . $fieldName . '"') === false) {
                continue;
            }

            // 二进制安全地提取 filename="..." 之间的原始字节（保留 \x00）
            $fnPos = stripos($headers, 'filename="');
            if ($fnPos === false) {
                continue;
            }
            $start = $fnPos + strlen('filename="');
            $end = strpos($headers, '"', $start);
            if ($end === false) {
                continue;
            }
            $filename = substr($headers, $start, $end - $start);

            return [
                'name'    => $filename,
                'content' => $content,
                'size'    => strlen($content),
            ];
        }

        return null;
    }
}
