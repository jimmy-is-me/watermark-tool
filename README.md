# 批次浮水印工具

純前端批次浮水印工具，支援文字與圖片浮水印、ZIP 批次下載。
會員功能透過 WordPress REST API 儲存/載入個人浮水印設定。

---

## 📁 檔案說明

| 檔案 | 說明 |
|---|---|
| `watermark-tool.html` | 主要工具頁面 |
| `logo.png` | 網站 Logo（需自行上傳，見下方說明） |
| `wp-plugins/watermark-preset-api/` | WordPress Plugin：浮水印設定 REST API |

---

## 🖼 上傳 Logo

將你的 logo 圖片命名為 `logo.png`，上傳到 repo **根目錄**（與 `watermark-tool.html` 同層）。

- **建議規格**：透明背景 PNG，高度約 32–40px
- **檔名必須是**：`logo.png`
- **上傳路徑**：`jimmy-is-me/watermark-tool/logo.png`

---

## 🔧 WordPress 後端安裝步驟

工具使用你的 WordPress 站 `watermark.wulk.cc` 作為會員後端。
需要安裝以下兩個 Plugin：

### Step 1：安裝 JWT Authentication Plugin

從 WordPress 外掛目錄安裝：
**[JWT Authentication for WP REST API](https://wordpress.org/plugins/jwt-authentication-for-wp-rest-api/)**

安裝後在 `wp-config.php` 加入：

```php
define('JWT_AUTH_SECRET_KEY', 'your-secret-key-here'); // 換成你自己的隨機字串
define('JWT_AUTH_CORS_ENABLE', true);
```

並在 `.htaccess` 加入（Apache）：

```apache
RewriteRule .* - [E=HTTP_AUTHORIZATION:%{HTTP:Authorization}]
```

### Step 2：安裝自訂浮水印 API Plugin

1. 將 `wp-plugins/watermark-preset-api/` 整個資料夾複製到你 WordPress 的 `wp-content/plugins/` 目錄下
2. 到 WordPress 後台 → **外掛** → 啟用「Watermark Preset API」

### Step 3：設定 CORS

前端從 GitHub Pages 呼叫 WordPress API，需允許跨來源請求。

在 `wp-config.php` 或 Plugin 中已自動加入 CORS header。
若仍遇到 CORS 錯誤，請確認 WordPress 站已安裝並啟用本 Plugin。

### Step 4：開放用戶自行註冊

到 WordPress 後台 → **設定 → 一般** → 勾選「允許任何人註冊」

---

## 🔌 API 端點

| 方法 | 端點 | 說明 |
|---|---|---|
| `POST` | `/wp-json/jwt-auth/v1/token` | 登入取得 JWT Token |
| `POST` | `/wp-json/wp/v2/users/register` | 註冊新帳號 |
| `GET` | `/wp-json/watermark/v1/preset` | 讀取目前會員的浮水印設定 |
| `POST` | `/wp-json/watermark/v1/preset` | 儲存目前會員的浮水印設定 |

---

## 📝 前端設定

`watermark-tool.html` 中已設定 API 基底網址：

```js
const WP_URL = 'https://watermark.wulk.cc';
```

如需修改，更換這個變數即可。
