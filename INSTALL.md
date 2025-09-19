# Grant AI Assistant - インストール手順

## 📋 システム要件

- WordPress 5.0以上
- PHP 7.0以上  
- OpenAI APIキー（sk-で始まる文字列）

## 🚀 インストール方法

### 方法1: WordPressダッシュボード（推奨）

1. **ZIPファイルを準備**
   - `grant-ai-assistant` フォルダ全体をZIP圧縮
   
2. **WordPressにアップロード**
   - WordPress管理画面 → プラグイン → 新規追加
   - 「プラグインのアップロード」をクリック
   - ZIPファイルを選択してインストール
   - プラグインを有効化

### 方法2: FTP/SFTP

1. **ファイルアップロード**
   ```
   wp-content/plugins/grant-ai-assistant/
   ├── grant-ai-assistant.php
   ├── assets/
   │   ├── style.css
   │   └── script.js
   ├── README.txt
   └── INSTALL.md
   ```

2. **プラグイン有効化**
   - WordPress管理画面 → プラグイン
   - "Grant AI Assistant" を探して「有効化」

## ⚙️ 初期設定

### 1. APIキー設定

1. WordPress管理画面 → 設定 → Grant AI Assistant
2. OpenAI APIキーを入力（sk-xxxxx形式）
3. 「AIチャット機能」にチェック
4. 「変更を保存」

### 2. APIキー取得方法

1. [OpenAI Platform](https://platform.openai.com/) にアクセス
2. アカウント作成・ログイン
3. API Keys セクションで新しいキーを作成
4. sk-で始まるキーをコピー

## 📝 使用方法

### ショートコード

**基本形:**
```
[grant_ai_chat]
```

**カスタマイズ:**
```
[grant_ai_chat height="600px" title="AI助成金相談" width="800px"]
```

### 設置場所

- 投稿・固定ページのコンテンツ
- ウィジェットエリア（テキストウィジェット）
- テーマファイル: `<?php echo do_shortcode('[grant_ai_chat]'); ?>`

## 🔧 トラブルシューティング

### プラグインが有効化できない

1. **PHPバージョン確認**
   - サーバーのPHPが7.0以上かチェック

2. **ファイル権限確認**
   - プラグインディレクトリの権限を755に設定
   - PHPファイルの権限を644に設定

3. **他のプラグインと競合**
   - 他のプラグインを一時無効化してテスト

### チャット機能が動作しない

1. **API設定確認**
   - OpenAI APIキーが正しく入力されているか
   - 「AIチャット機能」が有効になっているか

2. **JavaScript エラー確認**
   - ブラウザの開発者ツールでエラーをチェック
   - jQueryが読み込まれているかチェック

### 助成金が表示されない

1. **投稿タイプ確認**
   - `grant` 投稿タイプが存在するかチェック
   - 公開済みの助成金投稿があるかチェック

## 📞 サポート

- **バグ報告**: GitHubのIssuesセクション
- **使用方法**: プラグイン設定画面の説明参照
- **機能要望**: 開発チームまでご連絡

---

**Grant AI Assistant v1.0.5**  
**Grant Insight Team**