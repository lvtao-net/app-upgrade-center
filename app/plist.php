<?php
require_once '../includes/functions.php';

if (!isset($_GET['id'])) {
    http_response_code(400);
    die('未指定应用 ID。');
}

$app_id = intval($_GET['id']);
$app = getAppById($app_id);
$latestVersion = getLatestVersion($app_id);

if (!$app || !$latestVersion || empty($latestVersion['ipa_url'])) {
    http_response_code(404);
    die('无法生成 plist：应用或版本信息缺失。');
}

// 动态生成 plist
header('Content-Type: text/xml');
echo '<?xml version="1.0" encoding="UTF-8"?>';
?>
<plist version="1.0">
<dict>
    <key>items</key>
    <array>
        <dict>
            <key>assets</key>
            <array>
                <dict>
                    <key>kind</key>
                    <string>software-package</string>
                    <key>url</key>
                    <string><![CDATA[<?= htmlspecialchars($latestVersion['ipa_url']) ?>]]></string>
                </dict>
                <dict>
                    <key>kind</key>
                    <string>display-image</string>
                    <key>needs-shine</key>
                    <integer>0</integer>
                    <key>url</key>
                    <string><![CDATA[<?= htmlspecialchars($app['logo']) ?>]]></string>
                </dict>
                <dict>
                    <key>kind</key>
                    <string>full-size-image</string>
                    <key>needs-shine</key>
                    <true/>
                    <key>url</key>
                    <string><![CDATA[<?= htmlspecialchars($app['logo']) ?>]]></string>
                </dict>
            </array>
            <key>metadata</key>
            <dict>
                <key>bundle-identifier</key>
                <string><?= htmlspecialchars($app['package_name']) ?></string>
                <key>bundle-version</key>
                <string><![CDATA[<?= htmlspecialchars($latestVersion['version']) ?>]]></string>
                <key>kind</key>
                <string>software</string>
                <key>title</key>
                <string><![CDATA[<?= htmlspecialchars($app['name']) ?>]]></string>
                <key>subtitle</key>
                <string><![CDATA[<?= htmlspecialchars($app['name']) ?>]]></string>
            </dict>
        </dict>
    </array>
</dict>
</plist>