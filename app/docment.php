<?php
require_once '../includes/functions.php';
$html  = '';
$type  = $_GET['t'] ?? '';
$appId = $_GET['id'] ?? 0;
if(empty($type) || !$appId || !in_array($type, ['p', 's'])) {
    http_response_code(404);
    die('404 not found');
}
$app = getAppById($appId);
if (!$app) {
    http_response_code(404);
    die('未找到对应的应用。');
}
$title = $type == 'p' ? '隐私政策' : '服务协议';
$html = file_get_contents('../db/' . ($type == 'p' ? 'privacy' : 'service') . '.md');
$html = str_replace('{{.email}}', $app['email'], $html);
$html = str_replace('{{.product}}', $app['name'], $html);
$html = str_replace('{{.company}}', $app['company'], $html);
$html = str_replace('{{.com}}', $app['com'], $html);
$html = str_replace('{{.desc}}', $app['description'], $html);
$html = str_replace('{{.server}}', $app['summary'], $html);
$html = str_replace('{{.ctime}}', date('Y年m月d日', strtotime($app['created_at'])), $html);
?>
<html lang="zh">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no">
    <title><?php echo htmlspecialchars($app['name']) . $title?></title>
<style>
/* 设置全局样式 */
body {
    font-family: Arial, Helvetica, sans-serif; /* 全局字体 */
    font-size: 14px; /* 全局基础字号 */
    line-height: 1.6; /* 全局行高 */
    margin: 0;
    padding: 10px;
    background-color: #f9f9f9; /* 背景色 */
    color: #333; /* 文字颜色 */
}

/* 统一标题样式 h1 - h6 */
h1, h2, h3, h4, h5, h6 {
    font-weight: bold;
    margin-top: 1.2em;
    margin-bottom: 0.6em;
    color: #222;
}

/* 分别调整标题大小 */
h1 {
    font-size: 2.5em; /* 最大标题 */
    line-height: 1.2;
}
h2 {
    font-size: 2em;
}
h3 {
    font-size: 1.75em;
}
h4 {
    font-size: 1.5em;
}
h5 {
    font-size: 1.25em;
}
h6 {
    font-size: 1em; /* 最小标题 */
    color: #555; /* 次要颜色 */
}

/* 段落样式 */
p {
    margin: 0; /* 段后空隙 */
    color: #444;
    line-height: 1.5em; /* 更舒适的阅读体验 */
}

/* 列表样式 */
ul, ol {
    margin: 0 0 1em 1.5em; /* 列表的左缩进 */
    padding: 0;
    line-height: 1.6;
}
ul li, ol li {
    margin-bottom: 0.5em; /* 每项之间增加间距 */
}

/* 块引用样式 */
blockquote {
    margin: 1.5em 0;
    padding: 0.8em 1.2em;
    border-left: 4px solid #ccc;
    background: #f1f1f1;
    font-style: italic;
    color: #555;
}

/* 行内代码样式 */
code {
    font-family: Consolas, Monaco, monospace;
    background: #eee;
    color: #c7254e;
    padding: 0.2em 0.4em;
    border-radius: 4px;
}

/* 标题间距的适配 */
h1 + p, h2 + p, h3 + p, h4 + p, h5 + p, h6 + p {
    margin-top: 0.5em; /* 减小标题和段落的间距 */
}

/* 超链接样式 */
a {
    color: #007bff; /* 蓝色链接 */
    text-decoration: none; /* 去掉默认下划线 */
}
a:hover {
    text-decoration: underline; /* 悬停时增加下划线 */
}

/* 表格样式 */
table {
    width: 100%; /* 默认全宽 */
    border-collapse: collapse; /* 合并边框 */
    margin-bottom: 1em;
    background-color: #fff;
}
th, td {
    border: 1px solid #ddd;
    padding: 0.75em;
    text-align: left;
}
th {
    background-color: #f8f8f8;
    font-weight: bold;
}

/* 自适应字体大小调整 */
@media (max-width: 768px) {
    body {
        font-size: 14px; /* 小屏幕调整字体大小 */
    }
    h1 {
        font-size: 2em;
    }
    h2 {
        font-size: 1.75em;
    }
    h3 {
        font-size: 1.5em;
    }
}
</style>
</head>
<body>
    <?php echo parseMarkdown($html);?>
</body>
</html>