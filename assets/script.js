/**
 * Grant AI Assistant Pro - 高性能JavaScript (ES2022+)
 * Version: 2.0.0
 * 
 * 次世代対話体験エンジン
 * - モダンES2022+ JavaScript
 * - 高度なマイクロインタラクション
 * - リアルタイムストリーミング対応
 * - インテリジェントUXパターン
 * - パフォーマンス最適化
 * - アクセシビリティ完全準拠
 */

'use strict';

/**
 * 全体設定とユーティリティ
 */
class GAAPUtils {
  /**
   * デバウンス関数
   */
  static debounce(func, wait, immediate = false) {
    let timeout;
    return function executedFunction(...args) {
      const later = () => {
        timeout = null;
        if (!immediate) func.apply(this, args);
      };
      const callNow = immediate && !timeout;
      clearTimeout(timeout);
      timeout = setTimeout(later, wait);
      if (callNow) func.apply(this, args);
    };
  }

  /**
   * スロットル関数
   */
  static throttle(func, limit) {
    let inThrottle;
    return function(...args) {
      if (!inThrottle) {
        func.apply(this, args);
        inThrottle = true;
        setTimeout(() => inThrottle = false, limit);
      }
    };
  }

  /**
   * HTMLエスケープ
   */
  static escapeHtml(text) {
    const map = {
      '&': '&amp;',
      '<': '&lt;',
      '>': '&gt;',
      '"': '&quot;',
      "'": '&#039;'
    };
    return text.replace(/[&<>"']/g, m => map[m]);
  }

  /**
   * ランダムID生成
   */
  static generateId(prefix = 'gaap', length = 8) {
    const chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789';
    let result = prefix + '-';
    for (let i = 0; i < length; i++) {
      result += chars.charAt(Math.floor(Math.random() * chars.length));
    }
    return result;
  }

  /**
   * 要素の可視性チェック
   */
  static isElementVisible(element) {
    const rect = element.getBoundingClientRect();
    return rect.top >= 0 && rect.left >= 0 && 
           rect.bottom <= (window.innerHeight || document.documentElement.clientHeight) &&
           rect.right <= (window.innerWidth || document.documentElement.clientWidth);
  }

  /**
   * スムーススクロール
   */
  static smoothScrollTo(element, offset = 0) {
    const elementTop = element.offsetTop + offset;
    window.scrollTo({
      top: elementTop,
      behavior: 'smooth'
    });
  }

  /**
   * ローカルストレージ操作（エラーハンドリング付き）
   */
  static storage = {
    set(key, value) {
      try {
        localStorage.setItem(key, JSON.stringify(value));
        return true;
      } catch (e) {
        console.warn('LocalStorage set failed:', e);
        return false;
      }
    },

    get(key, defaultValue = null) {
      try {
        const item = localStorage.getItem(key);
        return item ? JSON.parse(item) : defaultValue;
      } catch (e) {
        console.warn('LocalStorage get failed:', e);
        return defaultValue;
      }
    },

    remove(key) {
      try {
        localStorage.removeItem(key);
        return true;
      } catch (e) {
        console.warn('LocalStorage remove failed:', e);
        return false;
      }
    }
  };

  /**
   * 日付フォーマット
   */
  static formatDate(date, options = {}) {
    const defaultOptions = {
      year: 'numeric',
      month: 'short',
      day: 'numeric',
      hour: '2-digit',
      minute: '2-digit'
    };
    return new Intl.DateTimeFormat('ja-JP', { ...defaultOptions, ...options }).format(date);
  }
}

/**
 * アニメーション管理クラス
 */
class GAAPAnimations {
  /**
   * タイピングアニメーション
   */
  static async typeWriter(element, text, speed = 30) {
    element.textContent = '';
    element.style.opacity = '1';
    
    for (let i = 0; i < text.length; i++) {
      element.textContent += text.charAt(i);
      await new Promise(resolve => setTimeout(resolve, speed));
    }
  }

  /**
   * フェードイン
   */
  static fadeIn(element, duration = 300) {
    element.style.opacity = '0';
    element.style.transition = `opacity ${duration}ms ease-in-out`;
    
    requestAnimationFrame(() => {
      element.style.opacity = '1';
    });
  }

  /**
   * スライドアップ
   */
  static slideUp(element, duration = 300) {
    element.style.transform = 'translateY(20px)';
    element.style.opacity = '0';
    element.style.transition = `all ${duration}ms cubic-bezier(0.4, 0, 0.2, 1)`;
    
    requestAnimationFrame(() => {
      element.style.transform = 'translateY(0)';
      element.style.opacity = '1';
    });
  }

  /**
   * バウンス効果
   */
  static bounce(element) {
    element.style.animation = 'none';
    requestAnimationFrame(() => {
      element.style.animation = 'gaap-bounce 0.6s ease-out';
    });
  }

  /**
   * シェイク効果
   */
  static shake(element) {
    element.style.animation = 'none';
    requestAnimationFrame(() => {
      element.style.animation = 'gaap-shake 0.5s ease-in-out';
    });
  }

  /**
   * パルス効果
   */
  static pulse(element, duration = 2000) {
    element.style.animation = `gaap-pulse ${duration}ms infinite`;
  }
}

/**
 * 音声認識管理クラス
 */
class GAAPVoiceRecognition {
  constructor() {
    this.recognition = null;
    this.isListening = false;
    this.isSupported = 'webkitSpeechRecognition' in window || 'SpeechRecognition' in window;
    
    if (this.isSupported) {
      const SpeechRecognition = window.SpeechRecognition || window.webkitSpeechRecognition;
      this.recognition = new SpeechRecognition();
      this.setupRecognition();
    }
  }

  setupRecognition() {
    this.recognition.continuous = false;
    this.recognition.interimResults = false;
    this.recognition.lang = 'ja-JP';
    this.recognition.maxAlternatives = 1;
  }

  async startListening() {
    if (!this.isSupported || this.isListening) return false;

    return new Promise((resolve, reject) => {
      this.recognition.onstart = () => {
        this.isListening = true;
        resolve(true);
      };

      this.recognition.onresult = (event) => {
        const result = event.results[0][0].transcript;
        this.onResult?.(result);
      };

      this.recognition.onerror = (event) => {
        this.isListening = false;
        this.onError?.(event.error);
        reject(event.error);
      };

      this.recognition.onend = () => {
        this.isListening = false;
        this.onEnd?.();
      };

      try {
        this.recognition.start();
      } catch (error) {
        reject(error);
      }
    });
  }

  stopListening() {
    if (this.recognition && this.isListening) {
      this.recognition.stop();
    }
  }

  // イベントハンドラー（外部から設定）
  onResult = null;
  onError = null;
  onEnd = null;
}

/**
 * キャッシュ管理クラス
 */
class GAAPCache {
  constructor(maxSize = 100, ttl = 1800000) { // 30分
    this.cache = new Map();
    this.maxSize = maxSize;
    this.ttl = ttl;
  }

  set(key, value) {
    const item = {
      value,
      timestamp: Date.now()
    };

    // サイズ制限チェック
    if (this.cache.size >= this.maxSize) {
      const firstKey = this.cache.keys().next().value;
      this.cache.delete(firstKey);
    }

    this.cache.set(key, item);
  }

  get(key) {
    const item = this.cache.get(key);
    
    if (!item) return null;
    
    // TTLチェック
    if (Date.now() - item.timestamp > this.ttl) {
      this.cache.delete(key);
      return null;
    }

    return item.value;
  }

  clear() {
    this.cache.clear();
  }

  size() {
    return this.cache.size;
  }
}

/**
 * イベントエミッタークラス
 */
class GAAPEventEmitter {
  constructor() {
    this.events = {};
  }

  on(event, listener) {
    if (!this.events[event]) {
      this.events[event] = [];
    }
    this.events[event].push(listener);
  }

  off(event, listenerToRemove) {
    if (!this.events[event]) return;
    
    this.events[event] = this.events[event].filter(
      listener => listener !== listenerToRemove
    );
  }

  emit(event, ...args) {
    if (!this.events[event]) return;
    
    this.events[event].forEach(listener => {
      try {
        listener(...args);
      } catch (error) {
        console.error(`Error in event listener for ${event}:`, error);
      }
    });
  }

  once(event, listener) {
    const onceListener = (...args) => {
      listener(...args);
      this.off(event, onceListener);
    };
    this.on(event, onceListener);
  }
}

/**
 * メイン GAAPProChat クラス
 */
class GAAPProChat extends GAAPEventEmitter {
  constructor(containerId, options = {}) {
    super();
    
    this.containerId = containerId;
    this.container = document.getElementById(containerId);
    
    if (!this.container) {
      throw new Error(`Container with ID "${containerId}" not found`);
    }

    // デフォルト設定
    this.config = {
      maxMessageLength: 1000,
      typingSpeed: 30,
      retryAttempts: 3,
      retryDelay: 1000,
      debounceDelay: 300,
      cacheEnabled: true,
      voiceEnabled: false,
      analyticsEnabled: true,
      autoScrollEnabled: true,
      persistConversation: true,
      ...options
    };

    // 内部状態
    this.state = {
      isConnected: true,
      isTyping: false,
      currentConversationId: null,
      messageCount: 0,
      retryCount: 0,
      lastMessageTime: 0
    };

    // コンポーネント
    this.cache = new GAAPCache();
    this.voiceRecognition = new GAAPVoiceRecognition();
    
    // DOM 要素
    this.elements = {};
    
    // イベント初期化
    this.init();
  }

  /**
   * 初期化
   */
  async init() {
    try {
      this.setupElements();
      this.setupEventListeners();
      this.setupVoiceRecognition();
      this.loadConversationHistory();
      this.showWelcomeMessage();
      
      this.emit('ready');
      console.log('GAAP Pro Chat initialized successfully');
      
    } catch (error) {
      console.error('Failed to initialize GAAP Pro Chat:', error);
      this.showErrorMessage('チャットの初期化に失敗しました。');
    }
  }

  /**
   * DOM 要素セットアップ
   */
  setupElements() {
    // メッセージコンテナ
    this.elements.messagesContainer = this.container.querySelector('.gaap-chat-messages');
    if (!this.elements.messagesContainer) {
      throw new Error('Messages container not found');
    }

    // 入力要素
    this.elements.inputField = this.container.querySelector('.gaap-chat-input input[type="text"]');
    this.elements.sendButton = this.container.querySelector('.gaap-send-button') || 
                             this.container.querySelector('.gaap-chat-input button');
    
    if (!this.elements.inputField || !this.elements.sendButton) {
      throw new Error('Input elements not found');
    }

    // 音声ボタン（オプション）
    this.elements.voiceButton = this.container.querySelector('.gaap-voice-button');
    
    // ヘッダー要素
    this.elements.header = this.container.querySelector('.gaap-chat-header');
    this.elements.statusIndicator = this.container.querySelector('.gaap-status-indicator');

    // アクセシビリティ属性設定
    this.setupAccessibility();
  }

  /**
   * アクセシビリティ設定
   */
  setupAccessibility() {
    // ARIA ラベル
    this.elements.inputField.setAttribute('aria-label', '助成金について質問を入力してください');
    this.elements.sendButton.setAttribute('aria-label', 'メッセージを送信');
    this.elements.messagesContainer.setAttribute('aria-live', 'polite');
    this.elements.messagesContainer.setAttribute('aria-label', '会話履歴');

    if (this.elements.voiceButton) {
      this.elements.voiceButton.setAttribute('aria-label', '音声入力');
    }

    // ロール設定
    this.container.setAttribute('role', 'application');
    this.elements.messagesContainer.setAttribute('role', 'log');
  }

  /**
   * イベントリスナー設定
   */
  setupEventListeners() {
    // 送信ボタン
    this.elements.sendButton.addEventListener('click', () => this.handleSendMessage());

    // Enter キー
    this.elements.inputField.addEventListener('keypress', (e) => {
      if (e.key === 'Enter' && !e.shiftKey) {
        e.preventDefault();
        this.handleSendMessage();
      }
    });

    // 入力フィールドフォーカス管理
    this.elements.inputField.addEventListener('focus', () => {
      this.container.classList.add('gaap-focused');
    });

    this.elements.inputField.addEventListener('blur', () => {
      this.container.classList.remove('gaap-focused');
    });

    // リアルタイム入力検証
    const debouncedValidation = GAAPUtils.debounce(() => {
      this.validateInput();
    }, this.config.debounceDelay);

    this.elements.inputField.addEventListener('input', debouncedValidation);

    // 音声認識ボタン
    if (this.elements.voiceButton && this.voiceRecognition.isSupported) {
      this.elements.voiceButton.addEventListener('click', () => this.toggleVoiceRecognition());
    } else if (this.elements.voiceButton) {
      this.elements.voiceButton.style.display = 'none';
    }

    // ウィンドウイベント
    window.addEventListener('beforeunload', () => this.saveConversationHistory());
    window.addEventListener('online', () => this.handleConnectionChange(true));
    window.addEventListener('offline', () => this.handleConnectionChange(false));

    // 可視性変更
    document.addEventListener('visibilitychange', () => {
      if (document.visibilityState === 'visible') {
        this.handleTabVisible();
      }
    });

    // コンテナサイズ変更監視
    if ('ResizeObserver' in window) {
      const resizeObserver = new ResizeObserver(() => {
        this.handleContainerResize();
      });
      resizeObserver.observe(this.container);
    }
  }

  /**
   * 音声認識設定
   */
  setupVoiceRecognition() {
    if (!this.voiceRecognition.isSupported) return;

    this.voiceRecognition.onResult = (text) => {
      this.elements.inputField.value = text;
      this.validateInput();
      GAAPAnimations.bounce(this.elements.inputField);
    };

    this.voiceRecognition.onError = (error) => {
      console.error('Voice recognition error:', error);
      this.showErrorMessage('音声認識エラーが発生しました。');
      this.updateVoiceButtonState(false);
    };

    this.voiceRecognition.onEnd = () => {
      this.updateVoiceButtonState(false);
    };
  }

  /**
   * 音声認識トグル
   */
  async toggleVoiceRecognition() {
    if (this.voiceRecognition.isListening) {
      this.voiceRecognition.stopListening();
      this.updateVoiceButtonState(false);
    } else {
      try {
        this.updateVoiceButtonState(true);
        await this.voiceRecognition.startListening();
      } catch (error) {
        this.updateVoiceButtonState(false);
        this.showErrorMessage('音声認識を開始できませんでした。');
      }
    }
  }

  /**
   * 音声ボタン状態更新
   */
  updateVoiceButtonState(isActive) {
    if (!this.elements.voiceButton) return;

    if (isActive) {
      this.elements.voiceButton.classList.add('active');
      this.elements.voiceButton.setAttribute('aria-pressed', 'true');
    } else {
      this.elements.voiceButton.classList.remove('active');
      this.elements.voiceButton.setAttribute('aria-pressed', 'false');
    }
  }

  /**
   * 入力検証
   */
  validateInput() {
    const message = this.elements.inputField.value.trim();
    const isValid = message.length > 0 && message.length <= this.config.maxMessageLength;
    
    this.elements.sendButton.disabled = !isValid;
    
    // 文字数インジケーター更新
    this.updateCharacterCount(message.length);
    
    return isValid;
  }

  /**
   * 文字数表示更新
   */
  updateCharacterCount(count) {
    let indicator = this.container.querySelector('.gaap-char-count');
    
    if (!indicator) {
      indicator = document.createElement('div');
      indicator.className = 'gaap-char-count';
      this.elements.inputField.parentNode.appendChild(indicator);
    }

    indicator.textContent = `${count}/${this.config.maxMessageLength}`;
    
    if (count > this.config.maxMessageLength * 0.9) {
      indicator.classList.add('gaap-char-count-warning');
    } else {
      indicator.classList.remove('gaap-char-count-warning');
    }
  }

  /**
   * メッセージ送信処理
   */
  async handleSendMessage() {
    const message = this.elements.inputField.value.trim();
    
    if (!this.validateInput() || this.state.isTyping) {
      return;
    }

    try {
      // ユーザーメッセージを即座に表示
      this.displayUserMessage(message);
      
      // 入力フィールドクリア
      this.elements.inputField.value = '';
      this.updateCharacterCount(0);
      this.elements.sendButton.disabled = true;

      // タイピング状態表示
      this.showTypingIndicator();

      // API リクエスト
      const response = await this.sendMessageToAPI(message);
      
      // タイピング状態解除
      this.hideTypingIndicator();

      // AI レスポンス表示
      await this.displayAIMessage(response);

      // 状態更新
      this.state.messageCount++;
      this.state.lastMessageTime = Date.now();
      this.state.retryCount = 0;

      // イベント発火
      this.emit('messageProcessed', { message, response });

      // 会話保存
      if (this.config.persistConversation) {
        this.saveConversationHistory();
      }

      // 分析データ送信
      if (this.config.analyticsEnabled) {
        this.trackInteraction(message, response);
      }

    } catch (error) {
      console.error('Message send error:', error);
      this.hideTypingIndicator();
      
      // リトライ処理
      if (this.state.retryCount < this.config.retryAttempts) {
        await this.retryMessage(message);
      } else {
        this.showErrorMessage('メッセージの送信に失敗しました。しばらく時間をおいて再度お試しください。');
      }
    }
  }

  /**
   * API リクエスト送信
   */
  async sendMessageToAPI(message) {
    const cacheKey = this.generateCacheKey(message);
    
    // キャッシュチェック
    if (this.config.cacheEnabled) {
      const cachedResponse = this.cache.get(cacheKey);
      if (cachedResponse) {
        console.log('Using cached response');
        return cachedResponse;
      }
    }

    const requestData = {
      action: 'gaap_chat',
      message: message,
      conversation_id: this.state.currentConversationId,
      nonce: gaapConfig.nonce,
      timestamp: Date.now()
    };

    const response = await fetch(gaapConfig.ajaxUrl, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/x-www-form-urlencoded',
      },
      body: new URLSearchParams(requestData)
    });

    if (!response.ok) {
      throw new Error(`HTTP error! status: ${response.status}`);
    }

    const data = await response.json();

    if (!data.success) {
      throw new Error(data.data || 'Unknown error occurred');
    }

    // レスポンスキャッシュ
    if (this.config.cacheEnabled) {
      this.cache.set(cacheKey, data.data);
    }

    // 会話IDを設定
    if (data.data.conversation_id) {
      this.state.currentConversationId = data.data.conversation_id;
    }

    return data.data;
  }

  /**
   * キャッシュキー生成
   */
  generateCacheKey(message) {
    return `msg_${btoa(message).slice(0, 20)}_${this.state.currentConversationId || 'new'}`;
  }

  /**
   * リトライ処理
   */
  async retryMessage(message) {
    this.state.retryCount++;
    
    console.log(`Retrying message (attempt ${this.state.retryCount})`);
    
    // 指数バックオフ
    const delay = this.config.retryDelay * Math.pow(2, this.state.retryCount - 1);
    
    await new Promise(resolve => setTimeout(resolve, delay));
    
    // リトライメッセージ表示
    this.showRetryMessage(this.state.retryCount);
    
    try {
      await this.handleSendMessage();
    } catch (error) {
      console.error(`Retry attempt ${this.state.retryCount} failed:`, error);
      throw error;
    }
  }

  /**
   * ユーザーメッセージ表示
   */
  displayUserMessage(message) {
    const messageElement = this.createMessageElement('user', message);
    this.appendMessage(messageElement);
    
    // アニメーション
    GAAPAnimations.slideUp(messageElement);
    
    this.emit('userMessage', message);
  }

  /**
   * AI メッセージ表示
   */
  async displayAIMessage(response) {
    const messageElement = this.createMessageElement('ai', '');
    this.appendMessage(messageElement);

    const contentElement = messageElement.querySelector('.gaap-message-content p');
    
    // タイピングアニメーション
    if (response.message) {
      await GAAPAnimations.typeWriter(contentElement, response.message, this.config.typingSpeed);
    }

    // 助成金情報表示
    if (response.grants) {
      const grantsElement = document.createElement('div');
      grantsElement.innerHTML = response.grants;
      messageElement.querySelector('.gaap-message-content').appendChild(grantsElement);
      
      // 助成金カードアニメーション
      const grantCards = grantsElement.querySelectorAll('.gaap-grant-card');
      grantCards.forEach((card, index) => {
        setTimeout(() => {
          GAAPAnimations.slideUp(card);
        }, index * 100);
      });
    }

    // 提案ボタン表示
    if (response.suggestions && response.suggestions.length > 0) {
      this.displaySuggestions(response.suggestions);
    }

    this.emit('aiMessage', response);
  }

  /**
   * メッセージ要素作成
   */
  createMessageElement(type, content) {
    const messageElement = document.createElement('div');
    messageElement.className = `gaap-message gaap-${type}-message`;

    if (type === 'ai') {
      messageElement.innerHTML = `
        <div class="gaap-ai-avatar">AI</div>
        <div class="gaap-message-content">
          <p>${GAAPUtils.escapeHtml(content)}</p>
        </div>
      `;
    } else {
      messageElement.innerHTML = `
        <div class="gaap-message-content">
          <p>${GAAPUtils.escapeHtml(content)}</p>
        </div>
      `;
    }

    // タイムスタンプ追加
    const timestamp = document.createElement('div');
    timestamp.className = 'gaap-message-timestamp';
    timestamp.textContent = GAAPUtils.formatDate(new Date(), { 
      hour: '2-digit', 
      minute: '2-digit' 
    });
    messageElement.appendChild(timestamp);

    return messageElement;
  }

  /**
   * メッセージ追加
   */
  appendMessage(messageElement) {
    this.elements.messagesContainer.appendChild(messageElement);
    
    if (this.config.autoScrollEnabled) {
      this.scrollToBottom();
    }
  }

  /**
   * 提案ボタン表示
   */
  displaySuggestions(suggestions) {
    // 既存の提案を削除
    const existingSuggestions = this.container.querySelector('.gaap-suggestions');
    if (existingSuggestions) {
      existingSuggestions.remove();
    }

    const suggestionsContainer = document.createElement('div');
    suggestionsContainer.className = 'gaap-suggestions';

    suggestions.forEach((suggestion, index) => {
      const button = document.createElement('button');
      button.className = 'gaap-suggestion-button';
      button.textContent = suggestion;
      button.setAttribute('data-suggestion', suggestion);
      
      button.addEventListener('click', () => {
        this.elements.inputField.value = suggestion;
        this.validateInput();
        this.elements.inputField.focus();
        suggestionsContainer.remove();
      });

      // スタッガードアニメーション
      setTimeout(() => {
        GAAPAnimations.slideUp(button);
      }, index * 50);

      suggestionsContainer.appendChild(button);
    });

    this.container.appendChild(suggestionsContainer);
  }

  /**
   * タイピングインジケーター表示
   */
  showTypingIndicator() {
    this.state.isTyping = true;
    this.elements.sendButton.disabled = true;

    const typingElement = document.createElement('div');
    typingElement.className = 'gaap-message gaap-loading-message';
    typingElement.innerHTML = `
      <div class="gaap-ai-avatar">AI</div>
      <div class="gaap-message-content">
        <p>考え中<span class="gaap-loading-dots"><span></span><span></span><span></span></span></p>
      </div>
    `;

    this.elements.messagesContainer.appendChild(typingElement);
    this.scrollToBottom();

    // ステータス更新
    this.updateStatus('thinking');
  }

  /**
   * タイピングインジケーター非表示
   */
  hideTypingIndicator() {
    this.state.isTyping = false;
    
    const loadingMessage = this.elements.messagesContainer.querySelector('.gaap-loading-message');
    if (loadingMessage) {
      loadingMessage.remove();
    }

    // ステータス更新
    this.updateStatus('ready');
  }

  /**
   * ステータス更新
   */
  updateStatus(status) {
    if (!this.elements.statusIndicator) return;

    const statusClasses = {
      ready: 'gaap-status-ready',
      thinking: 'gaap-status-thinking',
      error: 'gaap-status-error',
      offline: 'gaap-status-offline'
    };

    // 既存のステータスクラスを削除
    Object.values(statusClasses).forEach(cls => {
      this.elements.statusIndicator.classList.remove(cls);
    });

    // 新しいステータスクラスを追加
    if (statusClasses[status]) {
      this.elements.statusIndicator.classList.add(statusClasses[status]);
    }
  }

  /**
   * エラーメッセージ表示
   */
  showErrorMessage(message) {
    const errorElement = document.createElement('div');
    errorElement.className = 'gaap-message gaap-error-message';
    errorElement.innerHTML = `
      <div class="gaap-message-content">
        <p>❌ ${GAAPUtils.escapeHtml(message)}</p>
      </div>
    `;

    this.elements.messagesContainer.appendChild(errorElement);
    GAAPAnimations.slideUp(errorElement);
    this.scrollToBottom();

    // ステータス更新
    this.updateStatus('error');

    this.emit('error', message);
  }

  /**
   * リトライメッセージ表示
   */
  showRetryMessage(retryCount) {
    const retryElement = document.createElement('div');
    retryElement.className = 'gaap-message gaap-system-message';
    retryElement.innerHTML = `
      <div class="gaap-message-content">
        <p>🔄 再試行中... (${retryCount}/${this.config.retryAttempts})</p>
      </div>
    `;

    this.elements.messagesContainer.appendChild(retryElement);
    this.scrollToBottom();

    // 3秒後に削除
    setTimeout(() => {
      if (retryElement.parentNode) {
        retryElement.remove();
      }
    }, 3000);
  }

  /**
   * ウェルカムメッセージ表示
   */
  showWelcomeMessage() {
    const welcomeMessage = 'こんにちは！🤖\n\n助成金選びのお手伝いをします。どのような用途で助成金をお探しですか？';
    
    const messageElement = this.createMessageElement('ai', '');
    this.appendMessage(messageElement);

    const contentElement = messageElement.querySelector('.gaap-message-content p');
    
    // ウェルカムメッセージをタイピング
    setTimeout(() => {
      GAAPAnimations.typeWriter(contentElement, welcomeMessage, 20);
    }, 500);

    // 初期提案を表示
    setTimeout(() => {
      this.displaySuggestions([
        'スタートアップ向けの助成金を教えて',
        '設備投資に使える助成金は？',
        '研究開発の助成金について知りたい',
        'IT・デジタル化の支援制度は？'
      ]);
    }, 2000);
  }

  /**
   * 底部へスクロール
   */
  scrollToBottom() {
    const container = this.elements.messagesContainer;
    container.scrollTop = container.scrollHeight;
  }

  /**
   * 会話履歴保存
   */
  saveConversationHistory() {
    if (!this.config.persistConversation) return;

    const messages = Array.from(this.elements.messagesContainer.children).map(msg => {
      return {
        type: msg.classList.contains('gaap-user-message') ? 'user' : 'ai',
        content: msg.querySelector('p')?.textContent || '',
        timestamp: Date.now()
      };
    });

    const conversationData = {
      id: this.state.currentConversationId,
      messages: messages,
      lastActivity: Date.now()
    };

    GAAPUtils.storage.set(`gaap_conversation_${this.containerId}`, conversationData);
  }

  /**
   * 会話履歴読み込み
   */
  loadConversationHistory() {
    if (!this.config.persistConversation) return;

    const conversationData = GAAPUtils.storage.get(`gaap_conversation_${this.containerId}`);
    
    if (!conversationData || !conversationData.messages) return;

    // 24時間以内の会話のみ復元
    const twentyFourHours = 24 * 60 * 60 * 1000;
    if (Date.now() - conversationData.lastActivity > twentyFourHours) {
      GAAPUtils.storage.remove(`gaap_conversation_${this.containerId}`);
      return;
    }

    this.state.currentConversationId = conversationData.id;

    // メッセージを復元（最大10件）
    const messagesToRestore = conversationData.messages.slice(-10);
    
    messagesToRestore.forEach((msg, index) => {
      setTimeout(() => {
        const messageElement = this.createMessageElement(msg.type, msg.content);
        this.appendMessage(messageElement);
        GAAPAnimations.fadeIn(messageElement, 200);
      }, index * 100);
    });
  }

  /**
   * 接続状態変更処理
   */
  handleConnectionChange(isOnline) {
    this.state.isConnected = isOnline;
    
    if (isOnline) {
      this.updateStatus('ready');
      this.showSystemMessage('✅ インターネット接続が復旧しました');
    } else {
      this.updateStatus('offline');
      this.showSystemMessage('⚠️ インターネット接続が切断されました');
    }

    this.emit('connectionChange', isOnline);
  }

  /**
   * タブが表示された時の処理
   */
  handleTabVisible() {
    // 新しいメッセージがある場合の処理など
    this.scrollToBottom();
  }

  /**
   * コンテナサイズ変更処理
   */
  handleContainerResize() {
    this.scrollToBottom();
  }

  /**
   * システムメッセージ表示
   */
  showSystemMessage(message) {
    const systemElement = document.createElement('div');
    systemElement.className = 'gaap-message gaap-system-message';
    systemElement.innerHTML = `
      <div class="gaap-message-content">
        <p>${GAAPUtils.escapeHtml(message)}</p>
      </div>
    `;

    this.elements.messagesContainer.appendChild(systemElement);
    GAAPAnimations.slideUp(systemElement);
    this.scrollToBottom();

    // 5秒後に自動削除
    setTimeout(() => {
      if (systemElement.parentNode) {
        GAAPAnimations.fadeIn(systemElement, 300);
        setTimeout(() => systemElement.remove(), 300);
      }
    }, 5000);
  }

  /**
   * インタラクション追跡
   */
  trackInteraction(message, response) {
    if (!this.config.analyticsEnabled) return;

    const analyticsData = {
      action: 'gaap_analytics',
      event_type: 'chat_interaction',
      data: {
        message_length: message.length,
        response_time: response.processing_time || 0,
        confidence: response.confidence || 0,
        intent: response.intent || 'unknown',
        grants_count: (response.grants?.match(/gaap-grant-card/g) || []).length,
        timestamp: Date.now(),
        conversation_id: this.state.currentConversationId,
        session_id: this.getSessionId()
      },
      nonce: gaapConfig.nonce
    };

    // 非同期で送信（エラーは無視）
    fetch(gaapConfig.ajaxUrl, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/x-www-form-urlencoded',
      },
      body: new URLSearchParams(analyticsData)
    }).catch(error => {
      console.warn('Analytics tracking failed:', error);
    });
  }

  /**
   * セッションID取得
   */
  getSessionId() {
    let sessionId = GAAPUtils.storage.get('gaap_session_id');
    
    if (!sessionId) {
      sessionId = GAAPUtils.generateId('session', 16);
      GAAPUtils.storage.set('gaap_session_id', sessionId);
    }
    
    return sessionId;
  }

  /**
   * 会話をクリア
   */
  clearConversation() {
    this.elements.messagesContainer.innerHTML = '';
    this.state.currentConversationId = null;
    this.state.messageCount = 0;
    GAAPUtils.storage.remove(`gaap_conversation_${this.containerId}`);
    
    this.showWelcomeMessage();
    this.emit('conversationCleared');
  }

  /**
   * 会話をエクスポート
   */
  exportConversation(format = 'json') {
    const messages = Array.from(this.elements.messagesContainer.children).map(msg => {
      return {
        type: msg.classList.contains('gaap-user-message') ? 'user' : 
              msg.classList.contains('gaap-ai-message') ? 'ai' : 'system',
        content: msg.querySelector('p')?.textContent || '',
        timestamp: msg.querySelector('.gaap-message-timestamp')?.textContent || ''
      };
    });

    const exportData = {
      conversation_id: this.state.currentConversationId,
      messages: messages,
      export_date: new Date().toISOString(),
      total_messages: messages.length
    };

    if (format === 'json') {
      const blob = new Blob([JSON.stringify(exportData, null, 2)], { 
        type: 'application/json' 
      });
      this.downloadFile(blob, 'conversation.json');
    } else if (format === 'txt') {
      const textContent = messages.map(msg => 
        `[${msg.timestamp}] ${msg.type.toUpperCase()}: ${msg.content}`
      ).join('\n\n');
      
      const blob = new Blob([textContent], { type: 'text/plain' });
      this.downloadFile(blob, 'conversation.txt');
    }

    this.emit('conversationExported', { format, data: exportData });
  }

  /**
   * ファイルダウンロード
   */
  downloadFile(blob, filename) {
    const url = URL.createObjectURL(blob);
    const a = document.createElement('a');
    a.href = url;
    a.download = filename;
    document.body.appendChild(a);
    a.click();
    document.body.removeChild(a);
    URL.revokeObjectURL(url);
  }

  /**
   * 統計情報取得
   */
  getStats() {
    return {
      messageCount: this.state.messageCount,
      conversationId: this.state.currentConversationId,
      cacheSize: this.cache.size(),
      isConnected: this.state.isConnected,
      isTyping: this.state.isTyping,
      voiceSupported: this.voiceRecognition.isSupported,
      lastActivity: this.state.lastMessageTime
    };
  }

  /**
   * 破棄処理
   */
  destroy() {
    // イベントリスナー削除
    this.elements.sendButton?.removeEventListener('click', this.handleSendMessage);
    this.elements.inputField?.removeEventListener('keypress', this.handleSendMessage);
    
    // 音声認識停止
    this.voiceRecognition?.stopListening();
    
    // 会話保存
    this.saveConversationHistory();
    
    // キャッシュクリア
    this.cache.clear();
    
    // 要素クリア
    this.elements = {};
    
    this.emit('destroyed');
    console.log('GAAP Pro Chat destroyed');
  }
}

/**
 * グローバル初期化
 */
class GAAPGlobal {
  constructor() {
    this.chatInstances = new Map();
    this.isInitialized = false;
  }

  /**
   * 自動初期化
   */
  init() {
    if (this.isInitialized) return;

    // DOM準備完了まで待機
    if (document.readyState === 'loading') {
      document.addEventListener('DOMContentLoaded', () => this.setupChats());
    } else {
      this.setupChats();
    }

    this.isInitialized = true;
  }

  /**
   * チャットコンテナ自動検出・初期化
   */
  setupChats() {
    const chatContainers = document.querySelectorAll('.gaap-chat-container');
    
    chatContainers.forEach(container => {
      if (container.id && !this.chatInstances.has(container.id)) {
        try {
          const chat = new GAAPProChat(container.id, {
            ...gaapConfig.settings,
            voiceEnabled: 'webkitSpeechRecognition' in window,
          });
          
          this.chatInstances.set(container.id, chat);
          
          console.log(`GAAP Chat initialized for container: ${container.id}`);
          
        } catch (error) {
          console.error(`Failed to initialize chat for ${container.id}:`, error);
        }
      }
    });
  }

  /**
   * 手動初期化
   */
  createChat(containerId, options = {}) {
    if (this.chatInstances.has(containerId)) {
      console.warn(`Chat instance already exists for ${containerId}`);
      return this.chatInstances.get(containerId);
    }

    try {
      const chat = new GAAPProChat(containerId, options);
      this.chatInstances.set(containerId, chat);
      return chat;
    } catch (error) {
      console.error(`Failed to create chat for ${containerId}:`, error);
      return null;
    }
  }

  /**
   * インスタンス取得
   */
  getChat(containerId) {
    return this.chatInstances.get(containerId);
  }

  /**
   * インスタンス削除
   */
  destroyChat(containerId) {
    const chat = this.chatInstances.get(containerId);
    if (chat) {
      chat.destroy();
      this.chatInstances.delete(containerId);
      return true;
    }
    return false;
  }

  /**
   * 全インスタンス取得
   */
  getAllChats() {
    return Array.from(this.chatInstances.values());
  }
}

/**
 * グローバルインスタンス作成
 */
window.GAAP = new GAAPGlobal();

// 自動初期化
window.GAAP.init();

// 既存のヘルパー関数（後方互換性）
window.GAAHelper = {
  scrollToBottom: (container) => {
    if (container && container.length) {
      container.scrollTop(container[0].scrollHeight);
    }
  },
  
  focusInput: (input) => {
    if (input && input.length) {
      setTimeout(() => input.focus(), 100);
    }
  },
  
  escapeHtml: GAAPUtils.escapeHtml,
  
  showLoading: (container) => {
    const chat = window.GAAP.getAllChats().find(c => 
      c.elements.messagesContainer === container[0]
    );
    if (chat) {
      chat.showTypingIndicator();
    }
  },
  
  removeLoading: (container) => {
    const chat = window.GAAP.getAllChats().find(c => 
      c.elements.messagesContainer === container[0]
    );
    if (chat) {
      chat.hideTypingIndicator();
    }
  }
};

// jQuery サポート（オプション）
if (window.jQuery) {
  window.jQuery.fn.gaapChat = function(options) {
    return this.each(function() {
      if (this.id) {
        window.GAAP.createChat(this.id, options);
      }
    });
  };
}

console.log('🚀 GAAP Pro Chat System loaded successfully!');