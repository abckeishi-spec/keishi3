/**
 * Grant AI Assistant - Frontend JavaScript
 * AIチャット機能のフロントエンド処理
 * 
 * @package Grant_AI_Assistant
 * @version 1.0.0
 */

class GAAChat {
    constructor(containerId) {
        this.container = document.getElementById(containerId);
        if (!this.container) {
            console.error('Grant AI Assistant: Container not found:', containerId);
            return;
        }

        this.containerId = containerId;
        this.chatHistory = this.container.querySelector(`#${containerId}-history`);
        this.messageInput = this.container.querySelector(`#${containerId}-input`);
        this.sendButton = this.container.querySelector(`#${containerId}-send`);
        this.quickButtons = this.container.querySelector(`#${containerId}-quick`);
        this.resultsArea = this.container.querySelector(`#${containerId}-results`);
        
        // 状態管理
        this.conversationHistory = [];
        this.isProcessing = false;
        this.isMinimized = false;
        
        // 設定
        this.config = {
            maxMessageLength: 500,
            typingDelay: 50,
            scrollDelay: 100,
            autoResizeDelay: 200
        };

        this.init();
    }
    
    init() {
        this.validateElements();
        this.bindEvents();
        this.setupAutoResize();
        this.setupAccessibility();
        this.loadStoredConversation();
        
        // 初期化完了ログ
        this.debugLog('Chat initialized successfully', { containerId: this.containerId });
    }

    /**
     * DOM要素の存在確認
     */
    validateElements() {
        const requiredElements = {
            chatHistory: this.chatHistory,
            messageInput: this.messageInput,
            sendButton: this.sendButton,
            resultsArea: this.resultsArea
        };

        for (const [name, element] of Object.entries(requiredElements)) {
            if (!element) {
                console.error(`Grant AI Assistant: Required element missing: ${name}`);
            }
        }
    }

    /**
     * イベントバインディング
     */
    bindEvents() {
        // 送信ボタンクリック
        this.sendButton?.addEventListener('click', (e) => {
            e.preventDefault();
            this.sendMessage();
        });
        
        // Enterキー送信（Shift+Enterは改行）
        this.messageInput?.addEventListener('keydown', (e) => {
            if (e.key === 'Enter' && !e.shiftKey) {
                e.preventDefault();
                this.sendMessage();
            }
        });

        // 入力文字数カウント
        this.messageInput?.addEventListener('input', () => {
            this.updateCharacterCount();
            this.autoResizeTextarea();
        });
        
        // クイックボタン
        this.quickButtons?.addEventListener('click', (e) => {
            if (e.target.classList.contains('gaa-quick-btn') || e.target.closest('.gaa-quick-btn')) {
                const button = e.target.classList.contains('gaa-quick-btn') ? e.target : e.target.closest('.gaa-quick-btn');
                const message = button.dataset.message;
                if (message) {
                    this.messageInput.value = message;
                    this.sendMessage();
                }
            }
        });
        
        // 最小化ボタン
        const minimizeBtn = this.container.querySelector('.gaa-minimize-btn');
        minimizeBtn?.addEventListener('click', () => {
            this.toggleMinimize();
        });

        // 結果エリアでのカードクリック追跡
        this.resultsArea?.addEventListener('click', (e) => {
            const card = e.target.closest('[data-post-id]');
            if (card) {
                const postId = card.dataset.postId;
                const score = card.dataset.score || '';
                this.trackCardClick(postId);
                // 計測APIへ非同期送信
                try {
                    const formData = new FormData();
                    formData.append('action', 'gaa_click');
                    formData.append('nonce', gaa_ajax.nonce);
                    formData.append('post_id', postId);
                    formData.append('score', score);
                    formData.append('query', this.conversationHistory?.slice(-1)[0]?.content || '');
                    fetch(gaa_ajax.ajax_url, { method: 'POST', body: formData }).catch(() => {});
                } catch (err) {}
            }
        });

        // 入力フォーカス時のスクロール（モバイル対応）
        this.messageInput?.addEventListener('focus', () => {
            setTimeout(() => {
                this.scrollToBottom();
            }, this.config.scrollDelay);
        });

        // ウィンドウリサイズ対応
        window.addEventListener('resize', () => {
            this.handleResize();
        });
    }

    /**
     * テキストエリア自動リサイズ
     */
    autoResizeTextarea() {
        if (!this.messageInput) return;

        this.messageInput.style.height = 'auto';
        const scrollHeight = this.messageInput.scrollHeight;
        const maxHeight = 120; // 最大高さ（約4行）
        
        this.messageInput.style.height = Math.min(scrollHeight, maxHeight) + 'px';
        this.messageInput.style.overflowY = scrollHeight > maxHeight ? 'auto' : 'hidden';
    }

    /**
     * 文字数カウント更新
     */
    updateCharacterCount() {
        const counter = this.container.querySelector('.gaa-char-count');
        if (counter && this.messageInput) {
            const count = this.messageInput.value.length;
            counter.textContent = count;
            
            // 制限に近づいた時の警告
            counter.classList.toggle('gaa-warning', count > this.config.maxMessageLength * 0.9);
            counter.classList.toggle('gaa-danger', count > this.config.maxMessageLength);
        }
    }

    /**
     * メッセージ送信
     */
    async sendMessage() {
        const message = this.messageInput?.value.trim();
        
        if (!message || this.isProcessing) {
            return;
        }

        // 文字数制限チェック
        if (message.length > this.config.maxMessageLength) {
            this.showNotification(
                gaa_ajax.strings.char_limit || `メッセージは${this.config.maxMessageLength}文字以内で入力してください。`, 
                'warning'
            );
            return;
        }
        
        try {
            this.isProcessing = true;
            this.updateSendButton(true);
            
            // ユーザーメッセージを表示
            this.addUserMessage(message);
            this.messageInput.value = '';
            this.updateCharacterCount();
            this.autoResizeTextarea();
            
            // クイックボタンを非表示
            this.hideQuickButtons();
            
            // AI思考中表示
            const thinkingMessage = this.addAIThinkingMessage();
            
            // AI応答処理
            const response = await this.callAIAPI(message);
            
            // 思考中メッセージを削除
            this.removeThinkingMessage(thinkingMessage);
            
            // AI応答を処理
            await this.handleAIResponse(response);
            
        } catch (error) {
            this.debugLog('Send message error', { error: error.message, message });
            this.handleError(error);
        } finally {
            this.isProcessing = false;
            this.updateSendButton(false);
        }
    }

    /**
     * ユーザーメッセージ追加
     */
    addUserMessage(message) {
        const messageElement = this.createMessageElement('user', message);
        this.chatHistory.appendChild(messageElement);
        this.scrollToBottom();
        
        // 会話履歴に追加
        this.conversationHistory.push({
            role: 'user',
            content: message,
            timestamp: Date.now()
        });

        this.saveConversationHistory();
        this.animateMessageIn(messageElement);
    }

    /**
     * AIメッセージ追加
     */
    addAIMessage(message) {
        const messageElement = this.createMessageElement('ai', message);
        this.chatHistory.appendChild(messageElement);
        this.scrollToBottom();
        
        // 会話履歴に追加
        this.conversationHistory.push({
            role: 'assistant',
            content: message,
            timestamp: Date.now()
        });

        this.saveConversationHistory();
        this.animateMessageIn(messageElement);
        this.typewriterEffect(messageElement.querySelector('.gaa-message-content p'));
    }

    /**
     * AI思考中メッセージ
     */
    addAIThinkingMessage() {
        const messageElement = this.createMessageElement('ai', gaa_ajax.strings.thinking || 'AIが考えています...');
        messageElement.classList.add('gaa-thinking-message');
        
        // ローディングアニメーション追加
        const loadingDots = document.createElement('span');
        loadingDots.className = 'gaa-loading-dots';
        loadingDots.innerHTML = '<span></span><span></span><span></span>';
        messageElement.querySelector('.gaa-message-content').appendChild(loadingDots);
        
        this.chatHistory.appendChild(messageElement);
        this.scrollToBottom();
        
        return messageElement;
    }

    /**
     * 思考中メッセージ削除
     */
    removeThinkingMessage(messageElement) {
        if (messageElement && messageElement.parentNode) {
            messageElement.classList.add('gaa-fade-out');
            setTimeout(() => {
                messageElement.remove();
            }, 300);
        }
    }

    /**
     * メッセージ要素作成
     */
    createMessageElement(type, content) {
        const messageDiv = document.createElement('div');
        messageDiv.className = `gaa-message gaa-${type}-message`;
        
        const timestamp = new Date().toLocaleTimeString('ja-JP', { 
            hour: '2-digit', 
            minute: '2-digit' 
        });
        
        if (type === 'ai') {
            messageDiv.innerHTML = `
                <div class="gaa-avatar">🤖</div>
                <div class="gaa-message-content">
                    <p>${this.escapeHtml(content)}</p>
                </div>
                <div class="gaa-message-time">${timestamp}</div>
            `;
        } else {
            messageDiv.innerHTML = `
                <div class="gaa-message-content">
                    <p>${this.escapeHtml(content)}</p>
                </div>
                <div class="gaa-avatar">👤</div>
                <div class="gaa-message-time">${timestamp}</div>
            `;
        }
        
        return messageDiv;
    }

    /**
     * API呼び出し
     */
    async callAIAPI(message) {
        const formData = new FormData();
        formData.append('action', 'gaa_chat_message');
        formData.append('nonce', gaa_ajax.nonce);
        formData.append('message', message);
        formData.append('history', JSON.stringify(this.conversationHistory));
        
        const response = await fetch(gaa_ajax.ajax_url, {
            method: 'POST',
            body: formData
        });
        
        if (!response.ok) {
            throw new Error(`HTTP ${response.status}: ${response.statusText}`);
        }
        
        const data = await response.json();
        
        if (!data.success) {
            throw new Error(data.data || 'Unknown server error');
        }
        
        return data.data;
    }

    /**
     * AI応答処理
     */
    async handleAIResponse(response) {
        // AIメッセージを表示
        this.addAIMessage(response.message);
        
        // 助成金カードを表示
        if (response.grants && response.grants.length > 0) {
            await this.displayGrants(response.grants);
        } else {
            this.showNoResultsMessage();
        }
        
        // フォローアップ質問を表示
        if (response.suggestions && response.suggestions.length > 0) {
            this.showFollowUpQuestions(response.suggestions);
        }

        // 検索情報をログ
        this.debugLog('AI Response processed', {
            grantsCount: response.grants?.length || 0,
            suggestionsCount: response.suggestions?.length || 0,
            searchInfo: response.search_info
        });

        // レコメンドを保存（検索結果を関連として連携）
        try {
            const ids = (response.grants || []).map(g => g.id);
            const keywords = (response.intent?.search_keywords || []);
            if (ids.length > 0) {
                const formData = new FormData();
                formData.append('action', 'gaa_save_recs');
                formData.append('nonce', gaa_ajax.nonce);
                formData.append('ids', ids.join(','));
                formData.append('keywords', keywords.join(','));
                fetch(gaa_ajax.ajax_url, { method: 'POST', body: formData }).catch(() => {});
            }
        } catch (err) {}
    }

    /**
     * 助成金カード表示
     */
    async displayGrants(grants) {
        this.resultsArea.innerHTML = '';
        
        if (grants.length === 0) {
            this.showNoResultsMessage();
            return;
        }

        // 結果タイトル
        const resultsTitle = document.createElement('h3');
        resultsTitle.className = 'gaa-results-title';
        resultsTitle.innerHTML = `💡 ${grants.length}${gaa_ajax.strings.search_results || '件の助成金が見つかりました！'}`;
        this.resultsArea.appendChild(resultsTitle);
        
        // グリッドコンテナ
        const grantsGrid = document.createElement('div');
        grantsGrid.className = 'gaa-grants-grid';
        
        // カード追加（アニメーション付き）
        for (let i = 0; i < grants.length; i++) {
            const grant = grants[i];
            if (grant.html) {
                const cardWrapper = document.createElement('div');
                cardWrapper.className = 'gaa-grant-card-wrapper';
                cardWrapper.innerHTML = grant.html;
                
                // データ属性追加
                cardWrapper.dataset.postId = grant.id;
                cardWrapper.dataset.score = grant.score;
                
                // アニメーション遅延
                cardWrapper.style.animationDelay = `${i * 0.1}s`;
                cardWrapper.classList.add('gaa-card-animate-in');
                
                grantsGrid.appendChild(cardWrapper);
                
                // 段階的に表示
                await this.delay(100);
            }
        }
        
        this.resultsArea.appendChild(grantsGrid);
        
        // 結果エリアまでスムーススクロール
        setTimeout(() => {
            this.resultsArea.scrollIntoView({ 
                behavior: 'smooth', 
                block: 'start' 
            });
        }, 500);
    }

    /**
     * 結果なしメッセージ
     */
    showNoResultsMessage() {
        this.resultsArea.innerHTML = `
            <div class="gaa-no-results">
                <div class="gaa-no-results-icon">😅</div>
                <h3>${gaa_ajax.strings.no_results || '該当する助成金が見つかりませんでした'}</h3>
                <p>条件を変えて再度お試しいただくか、より詳細な情報をお教えください。</p>
                <button type="button" class="gaa-retry-search-btn" onclick="this.closest('.gaa-chat-container').querySelector('.gaa-message-input').focus()">
                    🔍 再検索する
                </button>
            </div>
        `;
    }

    /**
     * フォローアップ質問表示
     */
    showFollowUpQuestions(questions) {
        if (!questions || questions.length === 0) return;

        const followUpContainer = document.createElement('div');
        followUpContainer.className = 'gaa-follow-up';
        
        const title = document.createElement('p');
        title.className = 'gaa-follow-up-title';
        title.textContent = 'さらに詳しく検索するには：';
        followUpContainer.appendChild(title);
        
        const buttonsContainer = document.createElement('div');
        buttonsContainer.className = 'gaa-follow-up-buttons';
        
        questions.forEach((question, index) => {
            const button = document.createElement('button');
            button.className = 'gaa-follow-up-btn';
            button.textContent = question;
            button.style.animationDelay = `${index * 0.1}s`;
            button.onclick = () => {
                this.messageInput.value = question;
                this.sendMessage();
            };
            buttonsContainer.appendChild(button);
        });
        
        followUpContainer.appendChild(buttonsContainer);
        this.resultsArea.appendChild(followUpContainer);
    }

    /**
     * クイックボタン非表示
     */
    hideQuickButtons() {
        if (this.quickButtons) {
            this.quickButtons.style.opacity = '0';
            setTimeout(() => {
                this.quickButtons.style.display = 'none';
            }, 300);
        }
    }

    /**
     * 送信ボタン状態更新
     */
    updateSendButton(isProcessing) {
        if (!this.sendButton) return;

        const sendText = this.sendButton.querySelector('.gaa-send-text');
        const loadingIcon = this.sendButton.querySelector('.gaa-loading');
        
        if (isProcessing) {
            sendText?.classList.add('hidden');
            loadingIcon?.classList.remove('hidden');
            this.sendButton.disabled = true;
            this.sendButton.classList.add('gaa-processing');
        } else {
            sendText?.classList.remove('hidden');
            loadingIcon?.classList.add('hidden');
            this.sendButton.disabled = false;
            this.sendButton.classList.remove('gaa-processing');
        }
    }

    /**
     * エラー処理
     */
    handleError(error) {
        this.debugLog('Error occurred', { error: error.message });
        
        const errorMessage = error.message.includes('API') 
            ? 'AI サービスとの通信でエラーが発生しました。しばらく経ってから再度お試しください。'
            : (gaa_ajax.strings.error || 'エラーが発生しました。もう一度お試しください。');
            
        this.addAIMessage(errorMessage);
        
        // エラー通知表示
        this.showNotification(errorMessage, 'error');
    }

    /**
     * 最小化切り替え
     */
    toggleMinimize() {
        this.isMinimized = !this.isMinimized;
        this.container.classList.toggle('gaa-minimized', this.isMinimized);
        
        const minimizeIcon = this.container.querySelector('.gaa-minimize-icon');
        if (minimizeIcon) {
            minimizeIcon.textContent = this.isMinimized ? '□' : '−';
        }
    }

    /**
     * 通知表示
     */
    showNotification(message, type = 'info') {
        const notification = document.createElement('div');
        notification.className = `gaa-notification gaa-notification-${type}`;
        notification.innerHTML = `
            <span class="gaa-notification-text">${this.escapeHtml(message)}</span>
            <button type="button" class="gaa-notification-close" onclick="this.parentNode.remove()">×</button>
        `;
        
        this.container.appendChild(notification);
        
        // 自動削除
        setTimeout(() => {
            if (notification.parentNode) {
                notification.classList.add('gaa-fade-out');
                setTimeout(() => notification.remove(), 300);
            }
        }, 5000);
    }

    /**
     * スムーススクロール
     */
    scrollToBottom() {
        if (!this.chatHistory) return;

        setTimeout(() => {
            this.chatHistory.scrollTo({
                top: this.chatHistory.scrollHeight,
                behavior: 'smooth'
            });
        }, this.config.scrollDelay);
    }

    /**
     * メッセージアニメーション
     */
    animateMessageIn(messageElement) {
        messageElement.style.opacity = '0';
        messageElement.style.transform = 'translateY(20px)';
        
        setTimeout(() => {
            messageElement.style.transition = 'all 0.3s ease-out';
            messageElement.style.opacity = '1';
            messageElement.style.transform = 'translateY(0)';
        }, 50);
    }

    /**
     * タイプライター効果
     */
    typewriterEffect(element) {
        if (!element || !element.textContent) return;

        const text = element.textContent;
        element.textContent = '';
        
        let i = 0;
        const timer = setInterval(() => {
            element.textContent += text[i];
            i++;
            
            if (i >= text.length) {
                clearInterval(timer);
            }
        }, this.config.typingDelay);
    }

    /**
     * 会話履歴保存
     */
    saveConversationHistory() {
        try {
            const key = `gaa_conversation_${this.containerId}`;
            const data = {
                history: this.conversationHistory,
                timestamp: Date.now()
            };
            localStorage.setItem(key, JSON.stringify(data));
        } catch (error) {
            this.debugLog('Failed to save conversation history', { error: error.message });
        }
    }

    /**
     * 会話履歴読み込み
     */
    loadStoredConversation() {
        try {
            const key = `gaa_conversation_${this.containerId}`;
            const stored = localStorage.getItem(key);
            
            if (stored) {
                const data = JSON.parse(stored);
                const ageHours = (Date.now() - data.timestamp) / (1000 * 60 * 60);
                
                // 24時間以内の履歴のみ復元
                if (ageHours < 24 && data.history) {
                    this.conversationHistory = data.history;
                    this.debugLog('Conversation history restored', { 
                        messages: data.history.length,
                        ageHours: ageHours.toFixed(1)
                    });
                }
            }
        } catch (error) {
            this.debugLog('Failed to load conversation history', { error: error.message });
        }
    }

    /**
     * カードクリック追跡
     */
    trackCardClick(postId) {
        this.debugLog('Grant card clicked', { postId });
        
        // Google Analytics がある場合はイベント送信
        if (typeof gtag !== 'undefined') {
            gtag('event', 'grant_card_click', {
                'grant_id': postId,
                'source': 'ai_chat'
            });
        }
    }

    /**
     * アクセシビリティ設定
     */
    setupAccessibility() {
        // ARIA属性設定
        if (this.messageInput) {
            this.messageInput.setAttribute('aria-label', 'メッセージ入力');
            this.messageInput.setAttribute('aria-describedby', 'gaa-input-hint');
        }

        if (this.sendButton) {
            this.sendButton.setAttribute('aria-label', 'メッセージを送信');
        }

        if (this.chatHistory) {
            this.chatHistory.setAttribute('role', 'log');
            this.chatHistory.setAttribute('aria-live', 'polite');
        }
    }

    /**
     * リサイズ処理
     */
    handleResize() {
        // モバイルでのビューポート変更に対応
        setTimeout(() => {
            this.scrollToBottom();
        }, this.config.autoResizeDelay);
    }

    /**
     * 自動リサイズ設定
     */
    setupAutoResize() {
        if (!this.chatHistory) return;

        const resizeObserver = new ResizeObserver(() => {
            this.scrollToBottom();
        });
        
        resizeObserver.observe(this.chatHistory);
    }

    /**
     * HTMLエスケープ
     */
    escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }

    /**
     * 遅延ユーティリティ
     */
    delay(ms) {
        return new Promise(resolve => setTimeout(resolve, ms));
    }

    /**
     * デバッグログ
     */
    debugLog(message, data = {}) {
        if (console && typeof console.log === 'function') {
            console.log(`[Grant AI Assistant] ${message}`, data);
        }
    }

    /**
     * 会話履歴クリア
     */
    clearHistory() {
        this.conversationHistory = [];
        this.saveConversationHistory();
        
        // UI上の履歴もクリア（初期メッセージ以外）
        const messages = this.chatHistory.querySelectorAll('.gaa-message:not(.gaa-initial-message)');
        messages.forEach(message => message.remove());
        
        // 結果エリアクリア
        if (this.resultsArea) {
            this.resultsArea.innerHTML = '';
        }
        
        // クイックボタンを再表示
        if (this.quickButtons) {
            this.quickButtons.style.display = 'block';
            this.quickButtons.style.opacity = '1';
        }
    }
}

// グローバル関数として公開（既存テーマとの互換性のため）
window.GAAChat = GAAChat;

// jQuery 連携（既存テーマとの互換性）
if (typeof jQuery !== 'undefined') {
    jQuery(document).ready(function($) {
        // 既存テーマのAJAX処理との連携
        $(document).on('gaa_chat_initialized', function(e, chatInstance) {
            console.log('Grant AI Assistant chat initialized via jQuery event');
        });
    });
}