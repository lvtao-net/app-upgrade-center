<?php
require_once '../includes/functions.php';

if (!isset($_SERVER['PATH_INFO'])) {
    http_response_code(404);
    die('未指定应用 ID。');
}

$app_id = intval(trim($_SERVER['PATH_INFO'], '/'));
$app = getAppById($app_id);
$latestVersion = getLatestVersion($app_id);

if (!$app) {
    http_response_code(404);
    die('未找到对应的应用。');
}

// 判断设备系统，自动选择下载链接
$userAgent = strtolower($_SERVER['HTTP_USER_AGENT']);
$isAndroid = strpos($userAgent, 'android') !== false;
$isIOS = strpos($userAgent, 'iphone') !== false || strpos($userAgent, 'ipad') !== false;

// 如果 IPA 不为空，动态生成下载的 plist 链接
$ipaPlistLink = '';
if ($latestVersion && !empty($latestVersion['ipa_url'])) {
    $ipaPlistLink = "/app/plist.php?id={$app_id}";
}

// 默认值
$appRating = $app['rating'] ?? '4.9';
$appCategory = $app['category'] ?? '工具';
$appAge = $app['age'] ?? '4+';

// 应用截图
$screenshots = $app['screenshots'] ? explode(',', $app['screenshots']) : [];
?>
<!DOCTYPE html>
<html lang="zh">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no">
    <title><?= htmlspecialchars($app['name']) ?>应用下载</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 0;
            background-color: #f9f9f9;
            color: #333;
        }
        .container {
            padding: 20px;
            max-width: 800px;
            margin: 0 auto;
        }
        .header {
            display: flex;
            align-items: flex-start;
            margin-bottom: 20px;
        }
        .header img {
            width: 80px;
            height: 80px;
            border-radius: 10px;
            margin-right: 15px;
        }
        .header h1 {
            font-size: 20px;
            margin: 0;
        }
        .header p {
            margin: 5px 0 10px;
            font-size: 14px;
            color: #666;
        }
        .download-buttons a {
            display: inline-block;
            padding: 5px 12px;
            margin: 5px 5px 5px 0;
            font-size: 12px;line-height: 12px;
            color: #fff;
            min-width: 40px;text-align: center;
            text-decoration: none;
            border-radius: 12px;
        }
        .btn-android {
            background-color: #4CAF50;
        }
        .btn-ios {
            background-color: #007BFF;
        }
        .btn-secondary {
            background-color: #6C757D;
        }
        .info {
            display: flex;
            justify-content: space-between;
            align-items: center; /* 确保内容垂直居中 */
            margin: 20px 0;
            padding: 15px;
            border-top: 1px #eee solid;
            border-bottom: 1px #eee solid;
            text-align: center; /* 确保内容居中对齐 */
        }
        .info div {
            display: flex;
            flex-direction: column; /* 垂直排列标题和内容 */
            align-items: center; /* 内容水平居中 */
            justify-content: center; /* 内容垂直居中 */
            flex: 1; /* 每个块平均分布宽度 */
        }
        .info div p {
            margin: 0;
            font-size: 14px;
            color: #666;
            font-weight: normal;
        }
        .info div span {
            display: block;
            font-size: 16px;
            font-weight: 500;
            color: #5d5d5d;
            margin-top: 5px; /* 与标题保持适当间距 */
        }
        .description {
            margin-top: 20px;
            font-size: 14px;
            color: #555;
            line-height: 1.6; border-bottom:1px #eee solid;
        }
        .version{display:flex;align-items:center;justify-content:space-between}
        .screenshots-container {
            margin-top: 30px;
            overflow: hidden;
            position: relative;
        }
        .screenshots {
            display: flex;
            gap: 10px;
            transition: transform 0.3s ease-in-out;
            user-select: none; /* 禁止选中文字 */
        }
        .screenshots img {
            width: 65%; /* 每张图片占据容器的 65% */
            height: 100%; /* 图片高度自适应父容器 */
            object-fit: contain; /* 图片适应容器但不裁剪 */
            flex-shrink: 0;
            pointer-events: none; /* 禁止直接与图片交互 */
        }
        .store {
            display: flex;
            flex-wrap: wrap;
            margin-bottom: -6px; /* 抵消最后一行边距 */
        }
        
        .store a.btn-secondary {
            display: inline-flex;
            align-items: center;
            min-height: 24px; 
            padding: 2px 8px 2px 26px;
            margin-right: 6px;
            margin-bottom: 6px;
            background-color: #f8f9fa;
            border: 1px solid #dee2e6;
            border-radius: 6px;
            color: #2d3436;
            font-size: 12px;
            font-weight: bold;color:#666;
            position: relative;
            background-size: 18px;
            background-position: 4px center;
            background-repeat: no-repeat;
            transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
            text-decoration: none;
            line-height: 1.4;
        }
        
        /* 悬停效果 */
        .store a.btn-secondary:hover {
            background-color: #fff;
            transform: translateY(-1px);
            box-shadow: 0 3px 8px rgba(0,0,0,0.08);
            border-color: #ced4da;
        }
        
        /* 图标配置 */
        .store a.btn-secondary.google { background-image: url('./img/google.png'); }
        .store a.btn-secondary.huawei { background-image: url('./img/huawei.png'); }
        .store a.btn-secondary.honor { background-image: url('./img/honor.png'); }
        .store a.btn-secondary.mi { background-image: url('./img/mi.png'); }
        .store a.btn-secondary.qq { background-image: url('./img/qq.png'); }
        .store a.btn-secondary.vivo { background-image: url('./img/vivo.png'); }
        .store a.btn-secondary.oppo { background-image: url('./img/oppo.png'); }
    </style>
</head>
<body>
    <div class="container">
        <!-- 头部信息 -->
        <div class="header">
            <img src="<?= htmlspecialchars($app['logo']) ?>" alt="应用 Logo" width="68" height="68" style="width:68px;height:68px">
            <div>
                <h1><?= htmlspecialchars($app['name']) ?></h1>
                <div class="download-buttons">
                    <?php if ($isAndroid && !empty($latestVersion['apk_url'])): ?>
                        <a href="<?= htmlspecialchars($latestVersion['apk_url']) ?>" class="btn-android">立即安装</a>
                    <?php elseif ($isIOS && !empty($app['ios_store_url'])): ?>
                        <a href="<?= htmlspecialchars($app['ios_store_url']) ?>" class="btn-ios">App Store</a>
                    <?php elseif($isIOS && !empty($ipaPlistLink)): ?>
                        <a href="itms-services://?action=download-manifest&url=<?= htmlspecialchars($ipaPlistLink) ?>" class="btn-secondary">直接安装</a>
                    <?php else:?>
                        <span style="font-size:12px;color:#ccc">当前系统暂无可用安装包</span>
                    <?php endif; ?>
                    <?php if($isAndroid):?>
                    <div class="store">
                        <?php if(!empty($app['s_google'])):?>
                            <a href="<?= htmlspecialchars($app['s_google']) ?>" class="btn-secondary google">Google商店</a>
                        <?php endif?>
                        <?php if(!empty($app['s_huawei'])):?>
                            <a href="<?= htmlspecialchars($app['s_huawei']) ?>" class="btn-secondary huawei">华为商店</a>
                        <?php endif?>
                        <?php if(!empty($app['s_honor'])):?>
                            <a href="<?= htmlspecialchars($app['s_honor']) ?>" class="btn-secondary honor">荣耀e商店</a>
                        <?php endif?>
                        <?php if(!empty($app['s_mi'])):?>
                            <a href="<?= htmlspecialchars($app['s_mi']) ?>" class="btn-secondary mi">小米商店</a>
                        <?php endif?>
                        <?php if(!empty($app['s_qq'])):?>
                            <a href="<?= htmlspecialchars($app['s_qq']) ?>" class="btn-secondary qq">应用宝</a>
                        <?php endif?>
                        <?php if(!empty($app['s_vivo'])):?>
                            <a href="<?= htmlspecialchars($app['s_vivo']) ?>" class="btn-secondary vivo">vivo商店</a>
                        <?php endif?>
                        <?php if(!empty($app['s_oppo'])):?>
                            <a href="<?= htmlspecialchars($app['s_oppo']) ?>" class="btn-secondary oppo">OPPO商店</a>
                        <?php endif?>
                    </div>
                    <?php endif?>
                </div>
            </div>
        </div>
        <div class="description" style="border-bottom:none"><?= htmlspecialchars($app['description']) ?></div>
        <!-- 中间信息 -->
        <div class="info">
            <?php if(!$isAndroid && !$isIOS):?>
            <div>
                <p>扫码下载</p>
                <img src='https://tool.lvtao.net/qr?t=<?=getScheme() . $_SERVER['HTTP_HOST'] . '/app/' . $app['id']?>' style="width:128px;height:128px"/>
                </div>
            <?php endif; ?>
            <div>
                <p>评分</p>
                <span><?= htmlspecialchars($appRating) ?></span>
            </div>
            <div>
                <p>分类</p>
                <span><?= htmlspecialchars($appCategory) ?></span>
            </div>
            <div>
                <p>年龄</p>
                <span><?= htmlspecialchars($appAge) ?></span>
            </div>
        </div>
        
        <!-- 简介 -->
        <div class="description">
            <h2>新功能</h2>
            <div class="version">
                <span>版本 <?= $latestVersion['version']?></span>
                <span><?= timeAgo($latestVersion['created_at']) ?></span>
            </div>
            <p><?= nl2br(htmlspecialchars($latestVersion['changelog'])) ?></p>
            <div class="version">
                <?=$app['package_name']?>
                <a href="/app/docment.php?t=p&id=<?= $app['id']?>">隐私政策</a>
            </div>
        </div>
        <?php if ($screenshots): ?>
        <!-- 应用截图 -->
        <div class="screenshots-container">
            <h2>预览</h2>
            <div id="sliderContainer">
                <div class="screenshots" id="screenshotSlider">
                    <?php foreach ($screenshots as $screenshot): ?>
                        <img src="<?= htmlspecialchars(trim($screenshot)) ?>" alt="应用截图">
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
        <?php endif; ?>
    </div>
   <script>
   document.addEventListener("DOMContentLoaded", function () {
   // 判断是否微信浏览器
            function isWeChatBrowser() {
                var ua = navigator.userAgent.toLowerCase();
                return ua.indexOf("micromessenger") !== -1;
            }

            // 显示提示图片
            function showWeChatTip() {
                document.body.style.overflow = "hidden";
                var tipDiv = document.createElement("div");
                tipDiv.id = "wechat-tip";
                tipDiv.style.position = "fixed";
                tipDiv.style.top = "0";
                tipDiv.style.left = "0";
                tipDiv.style.width = "100%";
                tipDiv.style.height = "100%";
                tipDiv.style.backgroundColor = "rgba(255, 255, 255, 0.96)";
                tipDiv.style.zIndex = "10000";
                tipDiv.style.display = "flex";
                tipDiv.style.justifyContent = "center";
                tipDiv.style.alignItems = "top";
            
                // 使用背景图片替代 <img> 标签
                var tipContent = document.createElement("div");
                tipContent.style.width = "80%";
                tipContent.style.height = "80%";
                tipContent.style.marginTop = "10px";
                tipContent.style.backgroundImage = "url('/assets/img/open_in_browser_tip_bg.png')";
                tipContent.style.backgroundSize = "contain";
                tipContent.style.backgroundRepeat = "no-repeat";
                tipContent.style.backgroundPosition = "top center";
                tipContent.style.cursor = "pointer";
            
                tipDiv.appendChild(tipContent);
                document.body.appendChild(tipDiv);
            }

            if (isWeChatBrowser()) {
                showWeChatTip();
            }
        });
        
    // 禁止所有文本的选择
    document.body.style.userSelect = "none"; // 全局禁用选择
    document.body.style.webkitUserSelect = "none"; // Safari 兼容
    document.body.style.msUserSelect = "none"; // IE 兼容
    document.body.style.mozUserSelect = "none"; // Firefox 兼容

    // 禁止长按保存图片，仅针对图片
    document.querySelectorAll("img").forEach(function (img) {
        img.style.pointerEvents = "none"; // 禁止鼠标事件
        img.setAttribute("draggable", "false"); // 禁止拖拽
        img.addEventListener("contextmenu", function (e) {
            e.preventDefault(); // 禁止右键菜单
        });
        img.addEventListener("touchstart", function (e) {
            e.preventDefault(); // 禁止长按触摸行为
        });
    });

    // 禁止页面的右键菜单（不影响按钮点击）
    document.body.addEventListener("contextmenu", function (e) {
        if (e.target.tagName !== "BUTTON" && e.target.tagName !== "A") {
            e.preventDefault(); // 仅非按钮和链接时禁用右键
        }
    });

    // 禁止页面其他元素的文本选择
    document.body.addEventListener("selectstart", function (e) {
        if (e.target.tagName !== "BUTTON" && e.target.tagName !== "A") {
            e.preventDefault(); // 仅非按钮和链接时禁用选择
        }
    });

    // 禁止双击选择文本
    document.body.addEventListener("selectstart", function (e) {
        e.preventDefault(); // 禁止所有选择操作
    });
   
    const sliderContainer = document.getElementById('sliderContainer');
    const slider = document.getElementById('screenshotSlider');
    const images = Array.from(slider.children);
    const imageWidth = images[0].getBoundingClientRect().width + 10; // 包括间距
    const visiblePart = imageWidth * 0.5; // 显示下一张的一部分

    let isDragging = false;
    let isHorizontalDrag = false;
    let startX = 0;
    let startY = 0;
    let currentTranslate = 0;
    let prevTranslate = 0;
    let currentIndex = 0;
    const dragThreshold = 30; // 拖动的阈值（像素），超过这个值才会触发左右滑动

    // 事件监听
    sliderContainer.addEventListener('mousedown', startDrag);
    sliderContainer.addEventListener('mousemove', onDrag);
    sliderContainer.addEventListener('mouseup', endDrag);
    sliderContainer.addEventListener('mouseleave', endDrag);
    sliderContainer.addEventListener('touchstart', startDrag);
    sliderContainer.addEventListener('touchmove', onDrag);
    sliderContainer.addEventListener('touchend', endDrag);

    function startDrag(event) {
        isDragging = true;
        isHorizontalDrag = false;
        startX = getPositionX(event);
        startY = getPositionY(event);
        prevTranslate = currentTranslate;
        slider.style.transition = 'none'; // 禁止滑动动画
        sliderContainer.style.cursor = 'grabbing';
    }

    function onDrag(event) {
        if (!isDragging) return;

        const currentX = getPositionX(event);
        const currentY = getPositionY(event);
        const movedX = currentX - startX;
        const movedY = currentY - startY;

        // 如果垂直滑动超过水平滑动，优先触发上下滑动
        if (!isHorizontalDrag && Math.abs(movedY) > Math.abs(movedX)) {
            return;
        }

        // 只有当水平滑动距离超过阈值时，才进入左右滑动模式
        if (Math.abs(movedX) > dragThreshold) {
            isHorizontalDrag = true;
        }

        if (isHorizontalDrag) {
            currentTranslate = prevTranslate + movedX;
            slider.style.transform = `translateX(${currentTranslate}px)`;
        }
    }

    function endDrag() {
        if (!isDragging) return;
        isDragging = false;
        sliderContainer.style.cursor = 'grab';

        // 如果没有触发左右滑动模式，直接返回
        if (!isHorizontalDrag) {
            return;
        }

        // 滑动完成后根据距离自动对齐
        const movedBy = currentTranslate - prevTranslate;
        if (movedBy < -50 && currentIndex < images.length - 1) {
            currentIndex++;
        } else if (movedBy > 50 && currentIndex > 0) {
            currentIndex--;
        }

        // 对齐当前索引
        if (currentIndex === images.length - 1) {
            currentTranslate = -(currentIndex * imageWidth - visiblePart); // 最后一张靠右
        } else {
            currentTranslate = -currentIndex * imageWidth;
        }

        slider.style.transition = 'transform 0.3s ease'; // 平滑过渡
        slider.style.transform = `translateX(${currentTranslate}px)`;
    }

    function getPositionX(event) {
        return event.type.includes('mouse') ? event.pageX : event.touches[0].clientX;
    }

    function getPositionY(event) {
        return event.type.includes('mouse') ? event.pageY : event.touches[0].clientY;
    }
</script>
</body>
</html>