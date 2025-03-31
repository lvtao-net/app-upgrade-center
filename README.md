PHP+SQLITE的，默认账号密码都是`admin`
管理地址在 你的域名/mc/
代码很简单，请自用修改即可～

## 检测更新
```
POST https://你的域名/app/check.php
{
  id:appid,
  p:platform,
  v:appVersion,
  i:其它信息
}

appid 应用ID 1
platform 平台 android、ios
version 版本号 2.0
```
## uni-app示例
> 特别说明，安卓需要添加俩个权限 `android.permission.INSTALL_PACKAGES` 和 `android.permission.REQUEST_INSTALL_PACKAGES`
使用方法，将下面的代码保存为js，然后在你的应用中引入
```
// #ifdef APP-PLUS || APP-PLUS-NVUE
  appUpdate(1); //你的后台应用对应的ID
// #endif
```
*app-update.js*
```
export default function(appid) {
	checkVersion(appid)
}

let maskLayer = null
let popupView = null

// 默认中文文本公共变量
let LANG_MAP = {
    newVersionFound: "发现新版本",
    upgradeNow: "立即升级",
    skipUpgrade: "暂不升级",
    invalidDownloadUrl: "下载地址无效",
    installingFile: "正在安装文件...",
    updateComplete: "应用资源更新完成！",
    installationFailed: "安装文件失败",
    downloadFailed: "文件下载失败...",
    preparingDownload: "准备下载...",
    startingDownload: "开始下载...",
    upgradeApp: "升级APP",
    updatingWait: "正在为您更新，请耐心等待",
    cancelDownload: "取消下载",
    downloadInBackground: "后台下载",
    downloadCancelled: "已取消下载",
	closed: "关闭"
};

// 方法：根据语言包更新 LANG_MAP
function updateLangMap(responseLang) {
    if (!responseLang || typeof responseLang !== "object") return;
    Object.keys(LANG_MAP).forEach(key => {
        if (responseLang[key]) {
            LANG_MAP[key] = responseLang[key]; // 如果返回中有对应字段，更新语言
        }
    });
}

function checkVersion(appid){
	uni.getSystemInfo({
		success: res => {
			uni.request({
				url: 'https://apps.sdxtsz.com/app/check.php',
				method: 'POST',
				header: {
					'Accept': 'application/json',
					'Content-Type': 'application/x-www-form-urlencoded', //自定义请求头信息
				},
			    data:{
					id:appid,
					p:res.platform,
					v:res.appVersion,
					i:JSON.stringify(res)
				},
				success: (response) => {
					let data = response.data;
					updateLangMap(data.lang); // 使用返回的语言包更新 LANG_MAP
					if(data.upgrade == true){
						showDialog(data.info)
					}
				}
			});
		}
	})
}

function showDialog(updateInfo) {
	updateInfo.platform = updateInfo.platform ? updateInfo.platform : 'android'
	updateInfo.mainColor = updateInfo.mainColor ? updateInfo.mainColor : 'FF5B78'
	if (updateInfo.platform != 'android'  && updateInfo.platform != 'ios') {
		return false
	} 
	if (maskLayer) {
		maskLayer.show()
		popupView.show()
		return false
	}
	maskLayer = new plus.nativeObj.View('maskLayer', {
		top: '0px',
		left: '0px',
		height: '100%',
		width: '100%',
		backgroundColor: 'rgba(0,0,0,0.5)'
	})
	let screenWidth = plus.screen.resolutionWidth
	let screenHeight = plus.screen.resolutionHeight
	const popupViewWidth = screenWidth * 0.7
	const viewContentPadding = 20
	const viewContentWidth = parseInt(popupViewWidth - (viewContentPadding * 2))
	const descriptionList = drawtext((updateInfo.updateContent || '发现新版本'), viewContentWidth)
	let popupViewHeight = 80 + 20 + 20 + 90 + 10
	let popupViewContentList = [{
			src: 'https://apps.sdxtsz.com/assets/img/up.png',
			id: "logo",
			tag: "img",
			position: {
				top: "0px",
				left: (popupViewWidth - 124) / 2 + "px",
				width: "124px",
				height: "80px",
			}
		},
		{
			tag: 'font',
			id: 'title',
			text: `${LANG_MAP.newVersionFound} ${updateInfo.version || ''}`,
			textStyles: {
				size: '18px',
				color: "#333",
				weight: "bold",
				whiteSpace: "normal"
			},
			position: {
				top: '90px',
				left: viewContentPadding + "px",
				width: viewContentWidth + "px",
				height: "30px",
			}
		}
	]
	const textHeight = 18
	let contentTop = 130
	descriptionList.forEach((item, index) => {
		if (index > 0) {
			popupViewHeight += textHeight;
			contentTop += textHeight;
		}
		popupViewContentList.push({
			tag: 'font',
			id: 'content' + index + 1,
			text: item.content,
			textStyles: {
				size: '14px',
				color: "#666",
				lineSpacing: "50%",
				align: "left"
			},
			position: {
				top: contentTop + "px",
				left: viewContentPadding + "px",
				width: viewContentWidth + "px",
				height: textHeight + "px",
			}
		});
		if (item.type == "break") {
			contentTop += 10;
			popupViewHeight += 10;
		}
	})
	if (updateInfo.force) {
		popupViewContentList.push({
			tag: 'rect', //绘制底边按钮
			rectStyles: {
				radius: "6px",
				color: updateInfo.mainColor
			},
			position: {
				bottom: viewContentPadding + 'px',
				left: viewContentPadding + "px",
				width: viewContentWidth + "px",
				height: "30px"
			}
		})
		popupViewContentList.push({
			tag: 'font',
			id: 'confirmText',
			text: LANG_MAP.upgradeNow,
			textStyles: {
				size: '14px',
				color: "#FFF",
				lineSpacing: "0%",
			},
			position: {
				bottom: viewContentPadding + 'px',
				left: viewContentPadding + "px",
				width: viewContentWidth + "px",
				height: "30px"
			}
		})
	} else {
		popupViewContentList.push({
			tag: 'rect',
			id: 'cancelBox',
			rectStyles: {
				radius: "3px",
				borderColor: "#f1f1f1",
				borderWidth: "1px",
			},
			position: {
				bottom: viewContentPadding + 'px',
				left: viewContentPadding + "px",
				width: (viewContentWidth - viewContentPadding) / 2 + "px",
				height: "30px",
			}
		})
		popupViewContentList.push({
			tag: 'rect',
			id: 'confirmBox',
			rectStyles: {
				radius: "3px",
				color: updateInfo.mainColor,
			},
			position: {
				bottom: viewContentPadding + 'px',
				left: ((viewContentWidth - viewContentPadding) / 2 + viewContentPadding * 2) + "px",
				width: (viewContentWidth - viewContentPadding) / 2 + "px",
				height: "30px",
			}
		})
		popupViewContentList.push({
			tag: 'font',
			id: 'cancelText',
			text: LANG_MAP.skipUpgrade,
			textStyles: {
				size: '14px',
				color: "#666",
				lineSpacing: "0%",
				whiteSpace: "normal"
			},
			position: {
				bottom: viewContentPadding + 'px',
				left: viewContentPadding + "px",
				width: (viewContentWidth - viewContentPadding) / 2 + "px",
				height: "30px",
			}
		})
		popupViewContentList.push({
			tag: 'font',
			id: 'confirmText',
			text: "立即升级",
			textStyles: {
				size: '14px',
				color: "#FFF",
				lineSpacing: "0%",
				whiteSpace: "normal"
			},
			position: {
				bottom: viewContentPadding + 'px',
				left: ((viewContentWidth - viewContentPadding) / 2 + viewContentPadding * 2) + "px",
				width: (viewContentWidth - viewContentPadding) / 2 + "px",
				height: "30px",
			}
		})
	}
	popupView = new plus.nativeObj.View("popupView", { //创建底部图标菜单
		tag: "rect",
		top: (screenHeight - popupViewHeight) / 2 + "px",
		left: '15%',
		height: popupViewHeight + "px",
		width: "70%"
	})
	popupView.drawRect({
		color: "#FFFFFF",
		radius: "8px"
	}, {
		top: "40px",
		height: popupViewHeight - 40 + "px",
	})
	popupView.draw(popupViewContentList)
	popupView.addEventListener("click", e => {
		let maxTop = popupViewHeight - viewContentPadding
		let maxLeft = popupViewWidth - viewContentPadding
		let buttonWidth = (viewContentWidth - viewContentPadding) / 2
		if (e.clientY > maxTop - 30 && e.clientY < maxTop) {
			if (updateInfo.force) {
				if (e.clientX > viewContentPadding && e.clientX < maxLeft) {
					maskLayer.hide()
					popupView.hide()
					let platform = updateInfo.platform || 'android'
					let downUrl = updateInfo.downUrl || ''
					download(updateInfo)
				}
			} else {
				let maxTop = popupViewHeight - viewContentPadding;
				let maxLeft = popupViewWidth - viewContentPadding;
				let buttonWidth = (viewContentWidth - viewContentPadding) / 2;
				if (e.clientY > maxTop - 30 && e.clientY < maxTop) {
					// 暂不升级
					if (e.clientX > viewContentPadding && e.clientX < maxLeft - buttonWidth -
						viewContentPadding) {
						maskLayer.hide()
						popupView.hide()
					}
					if (e.clientX > maxLeft - buttonWidth && e.clientX < maxLeft) {
						// 立即升级
						maskLayer.hide()
						popupView.hide()
						let platform = updateInfo.platform || 'android'
						let downUrl = updateInfo.downUrl || ''
						download(updateInfo)
					}
				}
			}
		}
	})
	// 点击遮罩层
	maskLayer.addEventListener("click", function() { //处理遮罩层点击
		// maskLayer.hide();
		// popupView.hide();
	})
	// 显示弹窗
	maskLayer.show()
	popupView.show()
}

// 下载流程
function download(updateInfo) {
	let platform = updateInfo.platform || 'android'
	if (updateInfo.downUrl) {
		if (platform == 'ios') {
			plus.runtime.openURL(updateInfo.downUrl)
		}
		if (platform == 'android') {
			getDownload(updateInfo)
		}
	} else {
		plus.nativeUI.alert(LANG_MAP.invalidDownloadUrl)
	}
}

// 从服务器下载应用资源包（wgt文件）
const getDownload = function(data) {
	let dtask
	let popupData = {
		progress: true,
		buttonNum: 2
	};
	if(data.force){
		popupData.buttonNum = 0
	}
	
	let lastProgressValue = 0
	let popupObj = downloadPopup(popupData, data.mainColor)
	
	dtask = plus.downloader.createDownload(data.downUrl, {
		filename: "_doc/update/"
	}, function(download, status) {
		if (status == 200) {
			popupObj.change({
				progressValue: 100,
				progressTip:LANG_MAP.installingFile,
				progress: true,
				buttonNum: 0
			})
			plus.runtime.install(download.filename, {}, function() {
				popupObj.change({
					contentText: LANG_MAP.updateComplete,
					buttonNum: 1,
					progress: false
				});
			}, function(e) {
				popupObj.cancel()
				plus.nativeUI.alert(`${LANG_MAP.installationFailed}[${e.code}]: ${e.message}`);
			});
		} else {
			popupObj.change({
				contentText: LANG_MAP.downloadFailed,
				buttonNum: 1,
				progress: false
			});
		}
	});
	dtask.start()
	dtask.addEventListener("statechanged", function(task, status) {
		switch (task.state) {
			case 1: // 开始
				popupObj.change({
					progressValue:0,
					progressTip:LANG_MAP.preparingDownload,
					progress: true
				});
				break;
			case 2: // 已连接到服务器  
				popupObj.change({
					progressValue:0,
					progressTip:LANG_MAP.startingDownload,
					progress: true
				});
				break;
			case 3:
				const progress = parseInt(task.downloadedSize / task.totalSize * 100);
				if(progress - lastProgressValue >= 2){
					lastProgressValue = progress;
					popupObj.change({
						progressValue:progress,
						progressTip: `${LANG_MAP.startingDownload} ${progress}%`,
						progress: true
					});
				}
				break;
		}
	})
	// 取消下载
	popupObj.cancelDownload = function(){
		dtask && dtask.abort()
		uni.showToast({
			title: LANG_MAP.downloadCancelled,
			icon:"none"
		});
	}
	// 重启APP
	popupObj.reboot = function(){
		plus.runtime.restart()
	}
}

// 文件下载的弹窗绘图
function downloadPopupDrawing(data, mainColor){
	// 以下为计算菜单的nview绘制布局，为固定算法，使用者无关关心
	const screenWidth = plus.screen.resolutionWidth;
	const screenHeight = plus.screen.resolutionHeight;
	//弹窗容器宽度
	const popupViewWidth = screenWidth * 0.7;
	
	// 弹窗容器的Padding
	const viewContentPadding = 20;
	// 弹窗容器的宽度
	const viewContentWidth = popupViewWidth - (viewContentPadding * 2);
	// 弹窗容器高度
	let popupViewHeight = viewContentPadding * 3 + 60;
	let progressTip = data.progressTip || LANG_MAP.preparingDownload;
	let contentText = data.contentText || LANG_MAP.updatingWait;
	
	let elementList = [
		{
			tag: 'rect', //背景色
			color: '#FFFFFF',
			rectStyles:{
				radius: "8px"
			}
		},
		{
			tag: 'font',
			id: 'title',
			text: LANG_MAP.upgradeApp,
			textStyles: {
				size: '16px',
				color: "#333",
				weight: "bold",
				verticalAlign: "middle",
				whiteSpace: "normal"
			},
			position: {
				top: viewContentPadding + 'px',
				height: "30px",
			}
		},
		{
			tag: 'font',
			id: 'content',
			text: contentText,
			textStyles: {
				size: '14px',
				color: "#333",
				verticalAlign: "middle",
				whiteSpace: "normal"
			},
			position: {
				top: viewContentPadding * 2 + 30 + 'px',
				height: "20px",
			}
		}
	];
	// 是否有进度条
	
	if(data.progress){
		popupViewHeight += viewContentPadding + 40
		elementList = elementList.concat([
			{
				tag: 'font',
				id: 'progressValue',
				text: progressTip,
				textStyles: {
					size: '14px',
					color: mainColor,
					whiteSpace: "normal"
				},
				position: {
					top: viewContentPadding * 4 + 20 + 'px',
					height: "30px"
				}
			},
			{
				tag: 'rect', //绘制进度条背景
				id: 'progressBg',
				rectStyles:{
					radius: "4px",
					borderColor: "#f1f1f1",
					borderWidth: "1px",
				},
				position:{
					top: viewContentPadding * 4 + 60 + 'px',
					left: viewContentPadding + "px",
					width: viewContentWidth + "px",
					height: "8px"
				}
			},
		])
	}
	
	if (data.buttonNum == 2) {
		popupViewHeight += viewContentPadding + 30;
		elementList = elementList.concat([
			{
				tag: 'rect', //绘制底边按钮
				rectStyles:{
					radius: "3px",
					borderColor: "#f1f1f1",
					borderWidth: "1px",
				},
				position:{
					bottom: viewContentPadding + 'px',
					left: viewContentPadding + "px",
					width: (viewContentWidth - viewContentPadding) / 2 + "px",
					height: "30px"
				}
			},
			{
				tag: 'rect', //绘制底边按钮
				rectStyles:{
					radius: "3px",
					color: mainColor
				},
				position:{
					bottom: viewContentPadding + 'px',
					left: ((viewContentWidth - viewContentPadding) / 2 + viewContentPadding * 2) + "px",
					width: (viewContentWidth - viewContentPadding) / 2 + "px",
					height: "30px"
				}
			},
			{
				tag: 'font',
				id: 'cancelText',
				text: LANG_MAP.cancelDownload,
				textStyles: {
					size: '14px',
					color: "#666",
					lineSpacing: "0%",
					whiteSpace: "normal"
				},
				position: {
					bottom: viewContentPadding + 'px',
					left: viewContentPadding + "px",
					width: (viewContentWidth - viewContentPadding) / 2 + "px",
					height: "30px",
				}
			},
			{
				tag: 'font',
				id: 'confirmText',
				text: LANG_MAP.downloadInBackground,
				textStyles: {
					size: '14px',
					color: "#FFF",
					lineSpacing: "0%",
					whiteSpace: "normal"
				},
				position: {
					bottom: viewContentPadding + 'px',
					left: ((viewContentWidth - viewContentPadding) / 2 + viewContentPadding * 2) + "px",
					width: (viewContentWidth - viewContentPadding) / 2 + "px",
					height: "30px",
				}
			}
		]);
	}
	
	if (data.buttonNum == 1) {
		popupViewHeight += viewContentPadding + 40;
		elementList = elementList.concat([
			{
				tag: 'rect', //绘制底边按钮
				rectStyles:{
					radius: "6px",
					color: $mainColor
				},
				position:{
					bottom: viewContentPadding + 'px',
					left: viewContentPadding + "px",
					width: viewContentWidth + "px",
					height: "40px"
				}
			},
			{
				tag: 'font',
				id: 'confirmText',
				text: LANG_MAP.closed,
				textStyles: {
					size: '14px',
					color: "#FFF",
					lineSpacing: "0%",
				},
				position: {
					bottom: viewContentPadding + 'px',
					left: viewContentPadding + "px",
					width: viewContentWidth + "px",
					height: "40px"
				}
			}
		]);
	}
	
	return {
		popupViewHeight:popupViewHeight,
		popupViewWidth:popupViewWidth,
		screenHeight:screenHeight,
		viewContentWidth:viewContentWidth,
		viewContentPadding:viewContentPadding,
		elementList: elementList
	};
}
// 文件下载的弹窗
function downloadPopup(data, mainColor) {
	
	// 弹窗遮罩层
	let maskLayer = new plus.nativeObj.View("maskLayer", { //先创建遮罩层
		top: '0px',
		left: '0px',
		height: '100%',
		width: '100%',
		backgroundColor: 'rgba(0,0,0,0.5)'
	});
	
	let popupViewData = downloadPopupDrawing(data, mainColor);
	
	// 弹窗内容
	let popupView = new plus.nativeObj.View("popupView", { //创建底部图标菜单
		tag: "rect",
		top: (popupViewData.screenHeight - popupViewData.popupViewHeight) / 2 + "px",
		left: '15%',
		height: popupViewData.popupViewHeight + "px",
		width: "70%",
	});
	let progressValue = 0;
	let progressTip = 0;
	let contentText = 0;
	let buttonNum = 2;
	if(data.buttonNum >= 0){
		buttonNum = data.buttonNum;
	}
	popupView.draw(popupViewData.elementList);
    let callbackData = {
		change: function(res) {
			let progressElement = [];
			if(res.progressValue){
				progressValue = res.progressValue;
				// 绘制进度条
				progressElement.push({
					tag: 'rect', //绘制进度条背景
					id: 'progressValueBg',
					rectStyles:{
						radius: "4px",
						color: mainColor
					},
					position:{
						top: popupViewData.viewContentPadding * 4 + 60 + 'px',
						left: popupViewData.viewContentPadding + "px",
						width: popupViewData.viewContentWidth * (res.progressValue / 100) + "px",
						height: "8px"
					}
				});
			}
			if(res.progressTip){
				progressTip = res.progressTip;
				progressElement.push({
					tag: 'font',
					id: 'progressValue',
					text: res.progressTip,
					textStyles: {
						size: '14px',
						color: mainColor,
						whiteSpace: "normal"
					},
					position: {
						top: popupViewData.viewContentPadding * 4 + 20 + 'px',
						height: "30px"
					}
				});
			}
			if(res.contentText){
				contentText = res.contentText;
				progressElement.push({
					tag: 'font',
					id: 'content',
					text: res.contentText,
					textStyles: {
						size: '16px',
						color: "#333",
						whiteSpace: "normal"
					},
					position: {
						top: popupViewData.viewContentPadding * 2 + 30 + 'px',
						height: "30px",
					}
				});
			}
			if(res.buttonNum >= 0 && buttonNum != res.buttonNum){
				buttonNum = res.buttonNum;
				popupView.reset();
				popupViewData = downloadPopupDrawing(Object.assign({
					progressValue:progressValue,
					progressTip:progressTip,
					contentText:contentText,
				},res));
				let newElement = [];
				popupViewData.elementList.map((item,index) => {
					let have = false;
					progressElement.forEach((childItem,childIndex) => {
						if(item.id == childItem.id){
							have = true;
						}
					});
					if(!have){
						newElement.push(item);
					}
				});
				progressElement = newElement.concat(progressElement);
				popupView.setStyle({
					tag: "rect",
					top: (popupViewData.screenHeight - popupViewData.popupViewHeight) / 2 + "px",
					left: '15%',
					height: popupViewData.popupViewHeight + "px",
					width: "70%",
				});
				popupView.draw(progressElement); 
			}else{
				popupView.draw(progressElement);
			}
		},
		cancel: function() { 
			maskLayer.hide();
			popupView.hide();
		}
	}
	popupView.addEventListener("click", function(e) {
		let maxTop = popupViewData.popupViewHeight - popupViewData.viewContentPadding;
		let maxLeft = popupViewData.popupViewWidth - popupViewData.viewContentPadding;
		if (e.clientY > maxTop - 40 && e.clientY < maxTop) {
			if(buttonNum == 1){
				// 单按钮
				if (e.clientX > popupViewData.viewContentPadding && e.clientX < maxLeft) {
					maskLayer.hide();
					popupView.hide();
                    callbackData.reboot();
				}
			}else if(buttonNum == 2){
				// 双按钮
				let buttonWidth = (popupViewData.viewContentWidth - popupViewData.viewContentPadding) / 2;
				if (e.clientX > popupViewData.viewContentPadding && e.clientX < maxLeft - buttonWidth - popupViewData.viewContentPadding) {
					maskLayer.hide();
					popupView.hide();
                    callbackData.cancelDownload();
				} else if (e.clientX > maxLeft - buttonWidth && e.clientX < maxLeft) {
					maskLayer.hide();
					popupView.hide();
				}
			}
		}
	});
	// 显示弹窗
	maskLayer.show();
	popupView.show();
	// 改变进度条
	return callbackData
}

// 文字换行
function drawtext(text, maxWidth) {
	let textArr = text.split("");
	let len = textArr.length;
	// 上个节点
	let previousNode = 0;
	// 记录节点宽度
	let nodeWidth = 0;
	// 文本换行数组
	let rowText = [];
	// 如果是字母，侧保存长度
	let letterWidth = 0;
	// 汉字宽度
	let chineseWidth = 14;
	// otherFont宽度
	let otherWidth = 7;
	for (let i = 0; i < len; i++) {
		if (/[\u4e00-\u9fa5]|[\uFE30-\uFFA0]/g.test(textArr[i])) {
			if (letterWidth > 0) {
				if (nodeWidth + chineseWidth + letterWidth * otherWidth > maxWidth) {
					rowText.push({
						type: "text",
						content: text.substring(previousNode, i)
					});
					previousNode = i
					nodeWidth = chineseWidth
					letterWidth = 0
				} else {
					nodeWidth += chineseWidth + letterWidth * otherWidth
					letterWidth = 0
				}
			} else {
				if (nodeWidth + chineseWidth > maxWidth) {
					rowText.push({
						type: "text",
						content: text.substring(previousNode, i)
					})
					previousNode = i
					nodeWidth = chineseWidth
				} else {
					nodeWidth += chineseWidth
				}
			}
		} else {
			if (/\n/g.test(textArr[i])) {
				rowText.push({
					type: "break",
					content: text.substring(previousNode, i)
				})
				previousNode = i + 1
				nodeWidth = 0
				letterWidth = 0
			} else if (textArr[i] == "\\" && textArr[i + 1] == "n") {
				rowText.push({
					type: "break",
					content: text.substring(previousNode, i)
				})
				previousNode = i + 2
				nodeWidth = 0
				letterWidth = 0
			} else if (/[a-zA-Z0-9]/g.test(textArr[i])) {
				letterWidth += 1;
				if (nodeWidth + letterWidth * otherWidth > maxWidth) {
					rowText.push({
						type: "text",
						content: text.substring(previousNode, i + 1 - letterWidth)
					})
					previousNode = i + 1 - letterWidth
					nodeWidth = letterWidth * otherWidth
					letterWidth = 0
				}
			} else {
				if (nodeWidth + otherWidth > maxWidth) {
					rowText.push({
						type: "text",
						content: text.substring(previousNode, i)
					});
					previousNode = i
					nodeWidth = otherWidth
				} else {
					nodeWidth += otherWidth
				}
			}
		}
	}
	if (previousNode < len) {
		rowText.push({
			type: "text",
			content: text.substring(previousNode, len)
		})
	}
	return rowText
}
```
