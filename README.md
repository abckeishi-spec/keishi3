# Grant AI Assistant Plugin

## 📋 プロジェクト概要

**Grant AI Assistant**は、既存の「Grant Insight Perfect」WordPressテーマと完全統合するAI対話型助成金検索プラグインです。

### 🎯 主な機能

- **AIチャット式助成金検索**: ユーザーが自然言語で質問し、AIが最適な助成金を推薦
- **リアルタイム会話形式**: 質問を絞り込みながら、最適な助成金を見つけられる
- **既存システム完全統合**: 既存のカード表示機能、AJAX処理、ACFフィールドとシームレスに連携
- **ショートコード対応**: `[grant_ai_chat]`で任意の場所に配置可能

### 🏗️ ファイル構成

```
grant-ai-assistant/
├── grant-ai-assistant.php        # メインプラグインファイル
├── includes/
│   ├── ai-chat-section.php      # チャットセクション表示クラス
│   └── ai-engine.php            # AI処理エンジン・助成金検索
└── assets/
    ├── ai-chat.js               # フロントエンドJavaScript
    └── ai-chat.css              # スタイルシート
```

## 🔌 技術仕様

### AI API連携
- **OpenAI GPT-4**: 高精度な意図解析とユーザー対話
- **カスタムプロンプト**: 助成金専門コンサルタントとしてのAI設定

### 既存システム統合
- **gi_render_card()**: カード表示の統一
- **gi_safe_get_meta()**: データ取得の統一
- **gi_get_acf_field_safely()**: ACFフィールドとの完全連携
- **既存AJAX処理**: 検索・フィルタリング機能との相乗り

### データベース検索
- **投稿タイプ**: `grant`
- **メタフィールド**: `application_status`, `grant_target`, `max_amount_numeric` など
- **タクソノミー**: `grant_category`, `grant_prefecture`
- **スコアリング**: マッチング精度によるランキング

## 🚀 インストール・設定

### 1. 前提条件
- WordPress 5.8以上
- PHP 7.4以上
- Grant Insight Perfectテーマが有効
- OpenAI APIキー

### 2. インストール手順

1. **プラグインアップロード**
   ```bash
   wp-content/plugins/ に grant-ai-assistant フォルダをアップロード
   ```

2. **プラグイン有効化**
   - WordPress管理画面 > プラグイン > Grant AI Assistant を有効化

3. **API設定**
   - 設定 > Grant AI Assistant
   - OpenAI APIキーを入力
   - AIチャット機能を有効化

### 3. 使用方法

#### 基本的なショートコード
```shortcode
[grant_ai_chat]
```

#### カスタマイズオプション
```shortcode
[grant_ai_chat height="600px" title="AI助成金相談" theme="dark"]
```

#### 利用可能な属性
| 属性 | 説明 | デフォルト | 例 |
|------|------|-----------|-----|
| `title` | チャットのタイトル | AI助成金コンシェルジュ | `title="助成金相談AI"` |
| `height` | チャットエリアの高さ | 500px | `height="600px"` |
| `width` | チャットエリアの幅 | 100% | `width="800px"` |
| `style` | 表示スタイル | default | `style="minimal"` |
| `theme` | カラーテーマ | light | `theme="dark"` |

## 💡 使用例

### 1. 投稿・固定ページへの配置
```html
<h2>助成金を探してみましょう</h2>
[grant_ai_chat title="AI助成金検索" height="700px"]
```

### 2. ウィジェットエリアでの使用
- 外観 > ウィジェット
- ショートコードウィジェットに `[grant_ai_chat]` を入力

### 3. テンプレートファイルでの直接呼び出し
```php
<?php echo do_shortcode('[grant_ai_chat height="600px"]'); ?>
```

## 🎨 カスタマイズ

### CSSカスタマイズ
```css
/* チャットコンテナのカスタマイズ */
.gaa-chat-container {
    max-width: 900px;
    box-shadow: 0 10px 40px rgba(0,0,0,0.2);
}

/* メッセージの色変更 */
.gaa-ai-message .gaa-message-content {
    background: #f0f8ff;
}
```

### JavaScript連携
```javascript
// チャット初期化時のイベント
document.addEventListener('gaa_chat_initialized', function(e) {
    console.log('AI Chat initialized:', e.detail.chatInstance);
});
```

## 🔧 開発者向け情報

### フィルターフック
```php
// AI応答メッセージのカスタマイズ
add_filter('gaa_ai_response_message', function($message, $intent) {
    return "カスタム: " . $message;
}, 10, 2);

// 検索結果の後処理
add_filter('gaa_search_results', function($grants, $intent) {
    // カスタム並び替えなど
    return $grants;
}, 10, 2);
```

### アクションフック
```php
// チャットメッセージ送信後
add_action('gaa_after_chat_message', function($message, $response) {
    // ログ記録、分析など
}, 10, 2);
```

## 📊 システム情報

### 依存関係チェック
プラグインは以下の要素の存在を自動チェックします：

- ✅ Grant Insight Perfectテーマ
- ✅ `gi_render_card()` 関数
- ✅ `gi_safe_get_meta()` 関数
- ✅ `grant` 投稿タイプ
- ✅ 必要なACFフィールド

### パフォーマンス
- **検索応答時間**: 平均2-3秒
- **最大表示件数**: 設定可能（デフォルト6件）
- **会話履歴**: ブラウザローカルストレージに24時間保存

## 🐛 トラブルシューティング

### よくある問題

1. **「AIチャット機能が設定されていません」**
   - 設定画面でOpenAI APIキーを入力
   - APIキーの形式確認（sk-で始まる文字列）

2. **「助成金カードが表示されない」**
   - gi_render_card関数の存在確認
   - 助成金投稿の公開状態確認

3. **「AI応答が遅い」**
   - OpenAI APIの応答状況確認
   - デバッグモードでレスポンス時間チェック

### ログ確認
```php
// wp-config.php に追加
define('WP_DEBUG', true);
define('WP_DEBUG_LOG', true);

// ログ確認
tail -f /path/to/wordpress/wp-content/debug.log | grep "Grant AI Assistant"
```

## 📈 今後の拡張予定

- **多言語対応**: 英語・中国語対応
- **音声入力**: ブラウザ音声認識API連携  
- **詳細フィルタ**: 地域・業種の詳細絞り込み
- **お気に入り連携**: 既存のお気に入り機能と統合
- **レポート機能**: 検索履歴・人気助成金の分析

## 📞 サポート

### システム要件
- **WordPress**: 5.8以上
- **PHP**: 7.4以上  
- **Grant Insightテーマ**: v6.0以上推奨
- **ブラウザ**: Chrome, Firefox, Safari, Edge（最新版）

### お問い合わせ
- **バグレポート**: GitHubのIssuesより報告
- **機能要望**: GitHubのDiscussionsより相談
- **技術サポート**: 開発チームまで

## 📄 ライセンス

このプラグインは Grant Insight Perfect テーマ専用として開発されています。  
商用利用可能、改変・再配布は開発チームの許可が必要です。

---

**Grant AI Assistant v1.0.0**  
Powered by OpenAI GPT-4 & Grant Insight Team