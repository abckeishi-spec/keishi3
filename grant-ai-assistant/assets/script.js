/*!
 * Grant AI Assistant Pro - Enterprise JavaScript Framework v2.1.0
 * Next-generation AI-powered Grant Search Platform
 * Copyright (c) 2024 Grant Insight Team
 * License: GPL v3 or later
 */

'use strict';

/**
 * Main Grant AI Assistant Pro Class
 * Manages the entire frontend application lifecycle
 */
class GAAPProChat {
    constructor(options = {}) {
        // Configuration
        this.config = {
            apiUrl: window.gaap_ajax?.ajax_url || '/wp-admin/admin-ajax.php',
            nonce: window.gaap_ajax?.nonce || '',
            maxRetries: 3,
            retryDelay: 1000,
            typingSpeed: 50,
            enableVoice: options.enableVoice || false,
            enableAnalytics: options.enableAnalytics || true,
            theme: options.theme || 'default',
            autoReconnect: true,
            offlineSupport: true,
            ...options
        };

        // State management
        this.state = {
            isConnected: true,
            isTyping: false,
            messageQueue: [],
            retryCount: 0,
            conversationId: this.generateConversationId(),
            lastActivity: Date.now(),
            voiceRecording: false,
            currentLanguage: 'ja-JP'
        };

        // Component instances
        this.components = {
            messageRenderer: null,
            voiceHandler: null,
            analyticsTracker: null,
            errorHandler: null,
            cacheManager: null
        };

        // DOM elements cache
        this.elements = {};
        
        // Event listeners registry
        this.eventListeners = new Map();
        
        // Performance monitoring
        this.performance = {
            messageCount: 0,
            startTime: Date.now(),
            responsesTimes: [],
            errors: []
        };

        // Initialize the application
        this.init();
    }

    /**
     * Initialize the application
     */
    async init() {
        try {
            console.log('🚀 Initializing Grant AI Assistant Pro v2.1.0');
            
            // Wait for DOM ready
            if (document.readyState === 'loading') {
                document.addEventListener('DOMContentLoaded', () => this.initComponents());
            } else {
                this.initComponents();
            }

            // Setup global error handling
            this.setupGlobalErrorHandling();
            
            // Setup performance monitoring
            this.setupPerformanceMonitoring();

        } catch (error) {
            console.error('❌ Failed to initialize GAAP Pro:', error);
            this.handleError(error, 'initialization');
        }
    }

    /**
     * Initialize all components
     */
    async initComponents() {
        try {
            // Cache DOM elements
            this.cacheElements();
            
            // Initialize core components
            this.components.errorHandler = new GAAPErrorHandler(this);
            this.components.cacheManager = new GAAPCacheManager();
            this.components.messageRenderer = new GAAPMessageRenderer(this);
            this.components.analyticsTracker = new GAAPAnalyticsTracker(this);
            
            // Initialize optional components
            if (this.config.enableVoice) {
                this.components.voiceHandler = new GAAPVoiceHandler(this);
            }

            // Setup event listeners
            this.setupEventListeners();
            
            // Initialize UI
            this.initializeUI();
            
            // Check API connection
            await this.checkConnection();
            
            // Load conversation history
            this.loadConversationHistory();
            
            // Setup auto-reconnection
            if (this.config.autoReconnect) {
                this.setupAutoReconnect();
            }

            console.log('✅ GAAP Pro initialized successfully');
            
            // Dispatch ready event
            this.dispatchEvent('gaap:ready', { config: this.config, state: this.state });

        } catch (error) {
            console.error('❌ Component initialization failed:', error);
            this.components.errorHandler?.handleError(error, 'component_init');
        }
    }

    /**
     * Cache frequently used DOM elements
     */
    cacheElements() {
        const selectors = {
            container: '.gaap-chat-container',
            messages: '.gaap-chat-messages',
            input: '.gaap-chat-input',
            sendButton: '.gaap-chat-send-btn',
            voiceButton: '.gaap-voice-btn',
            statusIndicator: '.gaap-status-indicator',
            typingIndicator: '.gaap-typing-indicator'
        };

        for (const [key, selector] of Object.entries(selectors)) {
            this.elements[key] = document.querySelector(selector);
        }

        // Validate required elements
        const required = ['container', 'messages', 'input', 'sendButton'];
        const missing = required.filter(key => !this.elements[key]);
        
        if (missing.length > 0) {
            throw new Error(`Missing required elements: ${missing.join(', ')}`);
        }
    }

    /**
     * Setup all event listeners
     */
    setupEventListeners() {
        // Chat input events
        this.addEventListeners(this.elements.input, {
            'keypress': this.handleInputKeyPress.bind(this),
            'input': this.handleInputChange.bind(this),
            'focus': this.handleInputFocus.bind(this),
            'blur': this.handleInputBlur.bind(this)
        });

        // Send button
        this.addEventListener(this.elements.sendButton, 'click', this.sendMessage.bind(this));

        // Voice button (if enabled)
        if (this.elements.voiceButton && this.components.voiceHandler) {
            this.addEventListener(this.elements.voiceButton, 'click', 
                this.components.voiceHandler.toggleRecording.bind(this.components.voiceHandler));
        }

        // Window events
        this.addEventListeners(window, {
            'online': this.handleOnline.bind(this),
            'offline': this.handleOffline.bind(this),
            'beforeunload': this.handleBeforeUnload.bind(this),
            'visibilitychange': this.handleVisibilityChange.bind(this)
        });

        // Custom events
        this.addEventListener(document, 'gaap:message', this.handleCustomMessage.bind(this));
        this.addEventListener(document, 'gaap:error', this.handleCustomError.bind(this));
    }

    /**
     * Add multiple event listeners to an element
     */
    addEventListeners(element, events) {
        for (const [event, handler] of Object.entries(events)) {
            this.addEventListener(element, event, handler);
        }
    }

    /**
     * Add event listener with cleanup tracking
     */
    addEventListener(element, event, handler) {
        element.addEventListener(event, handler);
        
        // Track for cleanup
        if (!this.eventListeners.has(element)) {
            this.eventListeners.set(element, []);
        }
        this.eventListeners.get(element).push({ event, handler });
    }

    /**
     * Initialize UI state
     */
    initializeUI() {
        // Set initial theme
        this.setTheme(this.config.theme);
        
        // Update status indicator
        this.updateConnectionStatus(this.state.isConnected);
        
        // Focus chat input
        this.elements.input.focus();
        
        // Show welcome message if no history
        if (this.elements.messages.children.length === 0) {
            this.showWelcomeMessage();
        }

        // Setup resize observer for responsive design
        if (window.ResizeObserver) {
            this.setupResizeObserver();
        }
    }

    /**
     * Setup resize observer for responsive adjustments
     */
    setupResizeObserver() {
        const resizeObserver = new ResizeObserver(entries => {
            for (const entry of entries) {
                this.handleResize(entry.contentRect);
            }
        });
        
        resizeObserver.observe(this.elements.container);
    }

    /**
     * Handle container resize
     */
    handleResize(rect) {
        const isMobile = rect.width < 768;
        this.elements.container.classList.toggle('gaap-mobile', isMobile);
        
        // Adjust message list height on mobile
        if (isMobile && this.elements.messages) {
            const headerHeight = this.elements.container.querySelector('.gaap-chat-header')?.offsetHeight || 0;
            const inputHeight = this.elements.container.querySelector('.gaap-chat-input-area')?.offsetHeight || 0;
            const availableHeight = rect.height - headerHeight - inputHeight - 20; // 20px padding
            
            this.elements.messages.style.height = `${Math.max(200, availableHeight)}px`;
        }
    }

    /**
     * Handle input key press
     */
    handleInputKeyPress(event) {
        if (event.key === 'Enter') {
            if (event.shiftKey) {
                // Allow new line with Shift+Enter
                return;
            } else {
                event.preventDefault();
                this.sendMessage();
            }
        }

        // Update last activity
        this.state.lastActivity = Date.now();
        
        // Show typing indicator to other users (if multi-user)
        this.throttledTypingIndicator();
    }

    /**
     * Handle input change (typing)
     */
    handleInputChange(event) {
        const value = event.target.value;
        
        // Update send button state
        this.elements.sendButton.disabled = !value.trim();
        
        // Auto-resize textarea
        this.autoResizeTextarea(event.target);
        
        // Track analytics
        if (this.components.analyticsTracker) {
            this.components.analyticsTracker.trackTyping(value.length);
        }
    }

    /**
     * Auto-resize textarea based on content
     */
    autoResizeTextarea(textarea) {
        textarea.style.height = 'auto';
        const newHeight = Math.min(textarea.scrollHeight, 120); // Max 120px
        textarea.style.height = `${newHeight}px`;
    }

    /**
     * Throttled typing indicator (to avoid too many calls)
     */
    throttledTypingIndicator = this.throttle(() => {
        // Send typing indicator to server if needed
        this.sendTypingIndicator();
    }, 1000);

    /**
     * Send typing indicator
     */
    sendTypingIndicator() {
        if (this.config.enableTypingIndicator) {
            // Implementation for typing indicator
            console.log('👤 User is typing...');
        }
    }

    /**
     * Handle input focus
     */
    handleInputFocus() {
        this.elements.container.classList.add('gaap-input-focused');
        
        // Track engagement
        if (this.components.analyticsTracker) {
            this.components.analyticsTracker.trackEngagement('input_focus');
        }
    }

    /**
     * Handle input blur
     */
    handleInputBlur() {
        this.elements.container.classList.remove('gaap-input-focused');
    }

    /**
     * Send message to AI
     */
    async sendMessage() {
        const messageText = this.elements.input.value.trim();
        
        if (!messageText || this.state.isTyping) {
            return;
        }

        try {
            // Clear input immediately
            this.elements.input.value = '';
            this.elements.sendButton.disabled = true;
            this.autoResizeTextarea(this.elements.input);

            // Add user message to UI
            const userMessage = this.components.messageRenderer.addMessage({
                type: 'user',
                content: messageText,
                timestamp: new Date()
            });

            // Show typing indicator
            this.showTypingIndicator();

            // Record performance start
            const startTime = performance.now();

            // Send to AI with retry logic
            const response = await this.sendWithRetry('gaap_chat_message', {
                message: messageText,
                conversation_id: this.state.conversationId,
                nonce: this.config.nonce
            });

            // Record performance end
            const responseTime = performance.now() - startTime;
            this.performance.responsesTimes.push(responseTime);

            // Hide typing indicator
            this.hideTypingIndicator();

            // Process AI response
            await this.processAIResponse(response, responseTime);

            // Update conversation history
            this.updateConversationHistory(messageText, response);

            // Track analytics
            if (this.components.analyticsTracker) {
                this.components.analyticsTracker.trackMessage(messageText, response, responseTime);
            }

            // Auto-scroll to bottom
            this.scrollToBottom();

        } catch (error) {
            console.error('❌ Failed to send message:', error);
            
            this.hideTypingIndicator();
            this.components.errorHandler.handleError(error, 'send_message');
            
            // Re-enable input
            this.elements.input.value = messageText; // Restore message
            this.elements.sendButton.disabled = false;
        }
    }

    /**
     * Send request with retry logic
     */
    async sendWithRetry(action, data, retryCount = 0) {
        try {
            const response = await this.sendAjaxRequest(action, data);
            
            // Reset retry count on success
            this.state.retryCount = 0;
            
            return response;

        } catch (error) {
            if (retryCount < this.config.maxRetries) {
                console.warn(`⚠️ Request failed, retrying (${retryCount + 1}/${this.config.maxRetries})...`);
                
                // Exponential backoff
                const delay = this.config.retryDelay * Math.pow(2, retryCount);
                await this.sleep(delay);
                
                return this.sendWithRetry(action, data, retryCount + 1);
            } else {
                throw error;
            }
        }
    }

    /**
     * Send AJAX request
     */
    async sendAjaxRequest(action, data) {
        const formData = new FormData();
        formData.append('action', action);
        
        for (const [key, value] of Object.entries(data)) {
            formData.append(key, value);
        }

        const response = await fetch(this.config.apiUrl, {
            method: 'POST',
            body: formData,
            credentials: 'same-origin'
        });

        if (!response.ok) {
            throw new Error(`HTTP ${response.status}: ${response.statusText}`);
        }

        const result = await response.json();

        if (!result.success) {
            throw new Error(result.data || 'API request failed');
        }

        return result.data;
    }

    /**
     * Process AI response
     */
    async processAIResponse(response, responseTime) {
        if (!response) {
            throw new Error('Empty AI response');
        }

        // Add AI message with typing animation
        const aiMessage = this.components.messageRenderer.addMessage({
            type: 'assistant',
            content: response.content || '申し訳ございませんが、応答を生成できませんでした。',
            timestamp: new Date(),
            confidence: response.confidence,
            sources: response.sources || [],
            grants: response.grants || [],
            suggestions: response.suggestions || []
        });

        // Animate typing effect
        await this.components.messageRenderer.animateTyping(aiMessage, response.content);

        // Show additional data (grants, sources, etc.)
        if (response.grants && response.grants.length > 0) {
            this.components.messageRenderer.renderGrantsList(response.grants);
        }

        if (response.sources && response.sources.length > 0) {
            this.components.messageRenderer.renderSourcesList(response.sources);
        }

        if (response.suggestions && response.suggestions.length > 0) {
            this.components.messageRenderer.renderSuggestions(response.suggestions);
        }

        // Update performance metrics
        this.updatePerformanceMetrics(responseTime, response);
    }

    /**
     * Show typing indicator
     */
    showTypingIndicator() {
        this.state.isTyping = true;
        
        if (!this.elements.typingIndicator) {
            this.elements.typingIndicator = this.createTypingIndicator();
            this.elements.messages.appendChild(this.elements.typingIndicator);
        }
        
        this.elements.typingIndicator.style.display = 'flex';
        this.scrollToBottom();
    }

    /**
     * Hide typing indicator
     */
    hideTypingIndicator() {
        this.state.isTyping = false;
        
        if (this.elements.typingIndicator) {
            this.elements.typingIndicator.style.display = 'none';
        }
    }

    /**
     * Create typing indicator element
     */
    createTypingIndicator() {
        const indicator = document.createElement('div');
        indicator.className = 'gaap-typing-indicator';
        indicator.innerHTML = `
            <div class="gaap-message gaap-message-assistant">
                <div class="gaap-message-bubble">
                    <div class="gaap-typing-dots">
                        <div class="gaap-typing-dot"></div>
                        <div class="gaap-typing-dot"></div>
                        <div class="gaap-typing-dot"></div>
                    </div>
                </div>
            </div>
        `;
        return indicator;
    }

    /**
     * Show welcome message
     */
    showWelcomeMessage() {
        const welcomeMessage = {
            type: 'assistant',
            content: `こんにちは！Grant AI Assistant Proです。🤖

助成金・補助金に関することなら何でもお気軽にお尋ねください。

例えば：
• 「IT導入補助金について教えて」
• 「創業支援の助成金はありますか？」
• 「小規模事業者持続化補助金の申請条件は？」

どのようなご質問でも、具体的で実用的な情報をお届けします！`,
            timestamp: new Date(),
            isWelcome: true
        };

        this.components.messageRenderer.addMessage(welcomeMessage);
    }

    /**
     * Scroll to bottom of messages
     */
    scrollToBottom(smooth = true) {
        if (this.elements.messages) {
            this.elements.messages.scrollTo({
                top: this.elements.messages.scrollHeight,
                behavior: smooth ? 'smooth' : 'auto'
            });
        }
    }

    /**
     * Update connection status
     */
    updateConnectionStatus(isConnected) {
        this.state.isConnected = isConnected;
        
        if (this.elements.statusIndicator) {
            this.elements.statusIndicator.className = `gaap-status-indicator ${
                isConnected ? 'gaap-status-online' : 'gaap-status-offline'
            }`;
        }

        // Show notification
        if (!isConnected) {
            this.showNotification('接続が切断されました。再接続を試行しています...', 'warning');
        } else {
            this.showNotification('接続が復旧しました。', 'success');
        }
    }

    /**
     * Check API connection
     */
    async checkConnection() {
        try {
            await this.sendAjaxRequest('gaap_system_check', { nonce: this.config.nonce });
            this.updateConnectionStatus(true);
            return true;
        } catch (error) {
            console.warn('⚠️ Connection check failed:', error);
            this.updateConnectionStatus(false);
            return false;
        }
    }

    /**
     * Setup auto-reconnection
     */
    setupAutoReconnect() {
        setInterval(async () => {
            if (!this.state.isConnected) {
                console.log('🔄 Attempting to reconnect...');
                const connected = await this.checkConnection();
                
                if (connected) {
                    // Retry any queued messages
                    this.processMessageQueue();
                }
            }
        }, 10000); // Check every 10 seconds
    }

    /**
     * Process queued messages
     */
    processMessageQueue() {
        if (this.state.messageQueue.length > 0) {
            console.log(`📤 Processing ${this.state.messageQueue.length} queued messages`);
            
            // Process queued messages
            const queue = [...this.state.messageQueue];
            this.state.messageQueue = [];
            
            queue.forEach(async (message) => {
                try {
                    await this.sendMessage(message.content);
                } catch (error) {
                    console.error('❌ Failed to process queued message:', error);
                    this.state.messageQueue.push(message); // Re-queue on failure
                }
            });
        }
    }

    /**
     * Handle online event
     */
    handleOnline() {
        console.log('🌐 Browser is online');
        this.checkConnection();
    }

    /**
     * Handle offline event
     */
    handleOffline() {
        console.log('📵 Browser is offline');
        this.updateConnectionStatus(false);
        
        if (this.config.offlineSupport) {
            this.showNotification('オフラインモードに切り替えました。', 'info');
        }
    }

    /**
     * Handle before unload
     */
    handleBeforeUnload(event) {
        // Save conversation state
        this.saveConversationHistory();
        
        // Clean up resources
        this.cleanup();
    }

    /**
     * Handle visibility change
     */
    handleVisibilityChange() {
        if (document.hidden) {
            // Page is hidden - reduce activity
            this.pauseActivity();
        } else {
            // Page is visible - resume activity
            this.resumeActivity();
        }
    }

    /**
     * Pause activity when page is hidden
     */
    pauseActivity() {
        console.log('⏸️ Pausing activity (page hidden)');
        // Stop any ongoing operations
    }

    /**
     * Resume activity when page becomes visible
     */
    resumeActivity() {
        console.log('▶️ Resuming activity (page visible)');
        // Resume operations and check connection
        this.checkConnection();
    }

    /**
     * Handle custom message events
     */
    handleCustomMessage(event) {
        console.log('📨 Custom message received:', event.detail);
        // Handle custom messages from other components
    }

    /**
     * Handle custom error events
     */
    handleCustomError(event) {
        console.error('⚠️ Custom error received:', event.detail);
        this.components.errorHandler?.handleError(event.detail.error, event.detail.context);
    }

    /**
     * Load conversation history from localStorage
     */
    loadConversationHistory() {
        try {
            const historyKey = `gaap_conversation_${this.state.conversationId}`;
            const history = localStorage.getItem(historyKey);
            
            if (history) {
                const messages = JSON.parse(history);
                messages.forEach(message => {
                    this.components.messageRenderer.addMessage(message, false); // Don't animate
                });
                
                console.log(`📜 Loaded ${messages.length} messages from history`);
            }
        } catch (error) {
            console.warn('⚠️ Failed to load conversation history:', error);
        }
    }

    /**
     * Update conversation history
     */
    updateConversationHistory(userMessage, aiResponse) {
        try {
            const historyKey = `gaap_conversation_${this.state.conversationId}`;
            let history = [];
            
            try {
                history = JSON.parse(localStorage.getItem(historyKey) || '[]');
            } catch (e) {
                history = [];
            }
            
            // Add user message
            history.push({
                type: 'user',
                content: userMessage,
                timestamp: new Date()
            });
            
            // Add AI response
            history.push({
                type: 'assistant',
                content: aiResponse.content,
                timestamp: new Date(),
                confidence: aiResponse.confidence,
                grants: aiResponse.grants,
                sources: aiResponse.sources
            });
            
            // Keep only last 50 messages
            if (history.length > 50) {
                history = history.slice(-50);
            }
            
            localStorage.setItem(historyKey, JSON.stringify(history));
            
        } catch (error) {
            console.warn('⚠️ Failed to update conversation history:', error);
        }
    }

    /**
     * Save conversation history
     */
    saveConversationHistory() {
        // Already handled in updateConversationHistory
        console.log('💾 Conversation history saved');
    }

    /**
     * Set theme
     */
    setTheme(theme) {
        this.config.theme = theme;
        this.elements.container.setAttribute('data-theme', theme);
        
        // Save theme preference
        localStorage.setItem('gaap_theme', theme);
    }

    /**
     * Show notification
     */
    showNotification(message, type = 'info', duration = 5000) {
        // Create notification element
        const notification = document.createElement('div');
        notification.className = `gaap-notification gaap-notification-${type}`;
        notification.textContent = message;
        
        // Add to DOM
        document.body.appendChild(notification);
        
        // Animate in
        requestAnimationFrame(() => {
            notification.classList.add('gaap-notification-show');
        });
        
        // Auto remove
        setTimeout(() => {
            notification.classList.add('gaap-notification-hide');
            setTimeout(() => {
                if (notification.parentNode) {
                    notification.parentNode.removeChild(notification);
                }
            }, 300);
        }, duration);
    }

    /**
     * Update performance metrics
     */
    updatePerformanceMetrics(responseTime, response) {
        this.performance.messageCount++;
        
        // Calculate average response time
        const avgResponseTime = this.performance.responsesTimes.reduce((sum, time) => sum + time, 0) / this.performance.responsesTimes.length;
        
        // Log performance data
        console.log(`📊 Performance - Messages: ${this.performance.messageCount}, Avg Response: ${avgResponseTime.toFixed(2)}ms`);
        
        // Track slow responses
        if (responseTime > 5000) {
            console.warn(`⚠️ Slow response detected: ${responseTime}ms`);
        }
        
        // Send performance data to analytics
        if (this.components.analyticsTracker) {
            this.components.analyticsTracker.trackPerformance({
                responseTime,
                avgResponseTime,
                messageCount: this.performance.messageCount
            });
        }
    }

    /**
     * Setup global error handling
     */
    setupGlobalErrorHandling() {
        // Catch unhandled errors
        window.addEventListener('error', (event) => {
            if (event.filename && event.filename.includes('grant-ai-assistant')) {
                console.error('🚨 Global error in GAAP:', event.error);
                this.components.errorHandler?.handleError(event.error, 'global');
            }
        });

        // Catch unhandled promise rejections
        window.addEventListener('unhandledrejection', (event) => {
            console.error('🚨 Unhandled promise rejection in GAAP:', event.reason);
            this.components.errorHandler?.handleError(event.reason, 'promise_rejection');
        });
    }

    /**
     * Setup performance monitoring
     */
    setupPerformanceMonitoring() {
        // Monitor page performance
        if (window.PerformanceObserver) {
            try {
                const perfObserver = new PerformanceObserver((list) => {
                    for (const entry of list.getEntries()) {
                        if (entry.name.includes('gaap')) {
                            console.log(`⚡ Performance: ${entry.name} - ${entry.duration}ms`);
                        }
                    }
                });
                
                perfObserver.observe({ entryTypes: ['measure', 'navigation'] });
            } catch (error) {
                console.warn('Performance monitoring not available:', error);
            }
        }
    }

    /**
     * Generate unique conversation ID
     */
    generateConversationId() {
        return `conv_${Date.now()}_${Math.random().toString(36).substr(2, 9)}`;
    }

    /**
     * Dispatch custom event
     */
    dispatchEvent(eventName, detail) {
        const event = new CustomEvent(eventName, { detail });
        document.dispatchEvent(event);
    }

    /**
     * Utility: Sleep function
     */
    sleep(ms) {
        return new Promise(resolve => setTimeout(resolve, ms));
    }

    /**
     * Utility: Throttle function
     */
    throttle(func, delay) {
        let timeoutId;
        let lastExecTime = 0;
        return function (...args) {
            const currentTime = Date.now();
            
            if (currentTime - lastExecTime > delay) {
                func.apply(this, args);
                lastExecTime = currentTime;
            } else {
                clearTimeout(timeoutId);
                timeoutId = setTimeout(() => {
                    func.apply(this, args);
                    lastExecTime = Date.now();
                }, delay);
            }
        };
    }

    /**
     * Utility: Debounce function
     */
    debounce(func, delay) {
        let timeoutId;
        return function (...args) {
            clearTimeout(timeoutId);
            timeoutId = setTimeout(() => func.apply(this, args), delay);
        };
    }

    /**
     * Cleanup resources
     */
    cleanup() {
        console.log('🧹 Cleaning up GAAP resources');
        
        // Remove event listeners
        for (const [element, listeners] of this.eventListeners.entries()) {
            for (const { event, handler } of listeners) {
                element.removeEventListener(event, handler);
            }
        }
        this.eventListeners.clear();
        
        // Cleanup components
        Object.values(this.components).forEach(component => {
            if (component && typeof component.cleanup === 'function') {
                component.cleanup();
            }
        });
        
        // Clear any intervals/timeouts
        // (Add specific cleanup as needed)
        
        console.log('✅ GAAP cleanup completed');
    }

    /**
     * Public API methods
     */
    
    /**
     * Send a message programmatically
     */
    async sendProgrammaticMessage(message) {
        this.elements.input.value = message;
        return this.sendMessage();
    }

    /**
     * Clear conversation
     */
    clearConversation() {
        // Clear UI
        this.elements.messages.innerHTML = '';
        
        // Clear history
        const historyKey = `gaap_conversation_${this.state.conversationId}`;
        localStorage.removeItem(historyKey);
        
        // Generate new conversation ID
        this.state.conversationId = this.generateConversationId();
        
        // Show welcome message
        this.showWelcomeMessage();
        
        console.log('🗑️ Conversation cleared');
    }

    /**
     * Export conversation
     */
    exportConversation(format = 'json') {
        const historyKey = `gaap_conversation_${this.state.conversationId}`;
        const history = JSON.parse(localStorage.getItem(historyKey) || '[]');
        
        if (format === 'json') {
            return JSON.stringify(history, null, 2);
        } else if (format === 'text') {
            return history.map(msg => 
                `[${msg.timestamp}] ${msg.type === 'user' ? 'You' : 'AI'}: ${msg.content}`
            ).join('\n');
        }
        
        return history;
    }

    /**
     * Get current statistics
     */
    getStatistics() {
        const avgResponseTime = this.performance.responsesTimes.length > 0
            ? this.performance.responsesTimes.reduce((sum, time) => sum + time, 0) / this.performance.responsesTimes.length
            : 0;

        return {
            messageCount: this.performance.messageCount,
            averageResponseTime: avgResponseTime,
            conversationId: this.state.conversationId,
            uptime: Date.now() - this.performance.startTime,
            errorCount: this.performance.errors.length,
            isConnected: this.state.isConnected
        };
    }
}

/**
 * Message Renderer Component
 * Handles message display and animations
 */
class GAAPMessageRenderer {
    constructor(app) {
        this.app = app;
    }

    /**
     * Add message to UI
     */
    addMessage(messageData, animate = true) {
        const messageElement = this.createMessageElement(messageData);
        
        if (animate) {
            messageElement.style.opacity = '0';
            messageElement.style.transform = 'translateY(20px)';
        }
        
        this.app.elements.messages.appendChild(messageElement);
        
        if (animate) {
            requestAnimationFrame(() => {
                messageElement.style.transition = 'all 0.3s ease-out';
                messageElement.style.opacity = '1';
                messageElement.style.transform = 'translateY(0)';
            });
        }
        
        // Auto-scroll to new message
        this.app.scrollToBottom();
        
        return messageElement;
    }

    /**
     * Create message element
     */
    createMessageElement(messageData) {
        const messageDiv = document.createElement('div');
        messageDiv.className = `gaap-message gaap-message-${messageData.type}`;
        
        const bubble = document.createElement('div');
        bubble.className = 'gaap-message-bubble';
        
        // Message content
        const content = document.createElement('div');
        content.className = 'gaap-message-content';
        content.innerHTML = this.formatMessageContent(messageData.content, messageData.type);
        bubble.appendChild(content);
        
        // Add metadata for AI messages
        if (messageData.type === 'assistant' && messageData.confidence) {
            const metadata = this.createMessageMetadata(messageData);
            bubble.appendChild(metadata);
        }
        
        // Timestamp
        const timestamp = document.createElement('div');
        timestamp.className = 'gaap-message-time';
        timestamp.textContent = this.formatTimestamp(messageData.timestamp);
        bubble.appendChild(timestamp);
        
        messageDiv.appendChild(bubble);
        return messageDiv;
    }

    /**
     * Format message content
     */
    formatMessageContent(content, type) {
        // Sanitize content
        const div = document.createElement('div');
        div.textContent = content;
        let sanitized = div.innerHTML;
        
        // Convert line breaks to <br>
        sanitized = sanitized.replace(/\n/g, '<br>');
        
        // Convert URLs to links
        sanitized = sanitized.replace(
            /(https?:\/\/[^\s]+)/g,
            '<a href="$1" target="_blank" rel="noopener noreferrer">$1</a>'
        );
        
        // For AI messages, handle special formatting
        if (type === 'assistant') {
            // Convert **bold** to <strong>
            sanitized = sanitized.replace(/\*\*(.*?)\*\*/g, '<strong>$1</strong>');
            
            // Convert *italic* to <em>
            sanitized = sanitized.replace(/\*(.*?)\*/g, '<em>$1</em>');
            
            // Convert bullet points
            sanitized = sanitized.replace(/^• (.+)$/gm, '<li>$1</li>');
            
            // Wrap lists
            if (sanitized.includes('<li>')) {
                sanitized = sanitized.replace(/(<li>.*<\/li>)/s, '<ul>$1</ul>');
            }
        }
        
        return sanitized;
    }

    /**
     * Create message metadata (confidence, etc.)
     */
    createMessageMetadata(messageData) {
        const metadata = document.createElement('div');
        metadata.className = 'gaap-message-metadata';
        
        if (messageData.confidence) {
            const confidence = document.createElement('span');
            confidence.className = 'gaap-confidence-score';
            confidence.innerHTML = `<i class="fas fa-brain"></i> 信頼度: ${Math.round(messageData.confidence * 100)}%`;
            metadata.appendChild(confidence);
        }
        
        return metadata;
    }

    /**
     * Animate typing effect
     */
    async animateTyping(messageElement, content) {
        const contentDiv = messageElement.querySelector('.gaap-message-content');
        contentDiv.innerHTML = '';
        
        const formattedContent = this.formatMessageContent(content, 'assistant');
        
        // Simple typing animation (character by character)
        for (let i = 0; i < content.length; i++) {
            contentDiv.textContent += content[i];
            
            if (i % 5 === 0) { // Update every 5 characters for performance
                await this.app.sleep(this.app.config.typingSpeed);
            }
        }
        
        // Replace with formatted content after typing
        contentDiv.innerHTML = formattedContent;
    }

    /**
     * Render grants list
     */
    renderGrantsList(grants) {
        if (!grants || grants.length === 0) return;
        
        const grantsContainer = document.createElement('div');
        grantsContainer.className = 'gaap-grants-list gaap-message gaap-message-assistant';
        
        const bubble = document.createElement('div');
        bubble.className = 'gaap-message-bubble';
        
        const title = document.createElement('h4');
        title.textContent = '💰 関連する助成金・補助金';
        title.className = 'gaap-grants-title';
        bubble.appendChild(title);
        
        grants.forEach(grant => {
            const grantItem = this.createGrantItem(grant);
            bubble.appendChild(grantItem);
        });
        
        grantsContainer.appendChild(bubble);
        this.app.elements.messages.appendChild(grantsContainer);
        this.app.scrollToBottom();
    }

    /**
     * Create grant item element
     */
    createGrantItem(grant) {
        const item = document.createElement('div');
        item.className = 'gaap-grant-item';
        
        item.innerHTML = `
            <div class="gaap-grant-header">
                <h5 class="gaap-grant-name">${this.escapeHtml(grant.name)}</h5>
                <span class="gaap-grant-amount">${this.escapeHtml(grant.amount)}</span>
            </div>
            <div class="gaap-grant-details">
                <p><strong>実施機関:</strong> ${this.escapeHtml(grant.agency)}</p>
                <p><strong>締切日:</strong> ${this.escapeHtml(grant.deadline)}</p>
                <p><strong>条件:</strong> ${this.escapeHtml(grant.conditions)}</p>
                ${grant.url ? `<p><a href="${this.escapeHtml(grant.url)}" target="_blank" rel="noopener noreferrer">詳細情報 <i class="fas fa-external-link-alt"></i></a></p>` : ''}
            </div>
        `;
        
        return item;
    }

    /**
     * Render sources list
     */
    renderSourcesList(sources) {
        if (!sources || sources.length === 0) return;
        
        const sourcesContainer = document.createElement('div');
        sourcesContainer.className = 'gaap-sources-list gaap-message gaap-message-assistant';
        
        const bubble = document.createElement('div');
        bubble.className = 'gaap-message-bubble';
        
        const title = document.createElement('h4');
        title.textContent = '📚 参考情報';
        title.className = 'gaap-sources-title';
        bubble.appendChild(title);
        
        const sourcesList = document.createElement('ul');
        sourcesList.className = 'gaap-sources';
        
        sources.forEach(source => {
            const sourceItem = document.createElement('li');
            sourceItem.innerHTML = `<a href="${this.escapeHtml(source.url)}" target="_blank" rel="noopener noreferrer">${this.escapeHtml(source.title)} <i class="fas fa-external-link-alt"></i></a>`;
            sourcesList.appendChild(sourceItem);
        });
        
        bubble.appendChild(sourcesList);
        sourcesContainer.appendChild(bubble);
        this.app.elements.messages.appendChild(sourcesContainer);
        this.app.scrollToBottom();
    }

    /**
     * Render suggestions
     */
    renderSuggestions(suggestions) {
        if (!suggestions || suggestions.length === 0) return;
        
        const suggestionsContainer = document.createElement('div');
        suggestionsContainer.className = 'gaap-suggestions gaap-message gaap-message-assistant';
        
        const bubble = document.createElement('div');
        bubble.className = 'gaap-message-bubble';
        
        const title = document.createElement('h4');
        title.textContent = '💡 おすすめの質問';
        title.className = 'gaap-suggestions-title';
        bubble.appendChild(title);
        
        suggestions.forEach(suggestion => {
            const suggestionBtn = document.createElement('button');
            suggestionBtn.className = 'gaap-suggestion-btn gaap-btn gaap-btn-secondary gaap-btn-sm';
            suggestionBtn.textContent = suggestion;
            suggestionBtn.onclick = () => this.app.sendProgrammaticMessage(suggestion);
            bubble.appendChild(suggestionBtn);
        });
        
        suggestionsContainer.appendChild(bubble);
        this.app.elements.messages.appendChild(suggestionsContainer);
        this.app.scrollToBottom();
    }

    /**
     * Format timestamp
     */
    formatTimestamp(date) {
        if (!(date instanceof Date)) {
            date = new Date(date);
        }
        
        return date.toLocaleTimeString('ja-JP', {
            hour: '2-digit',
            minute: '2-digit'
        });
    }

    /**
     * Escape HTML
     */
    escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }
}

/**
 * Voice Handler Component
 * Handles speech recognition and synthesis
 */
class GAAPVoiceHandler {
    constructor(app) {
        this.app = app;
        this.recognition = null;
        this.synthesis = window.speechSynthesis;
        this.isRecording = false;
        this.initializeVoiceRecognition();
    }

    /**
     * Initialize speech recognition
     */
    initializeVoiceRecognition() {
        if (!('webkitSpeechRecognition' in window) && !('SpeechRecognition' in window)) {
            console.warn('⚠️ Speech recognition not supported');
            return;
        }

        const SpeechRecognition = window.SpeechRecognition || window.webkitSpeechRecognition;
        this.recognition = new SpeechRecognition();
        
        this.recognition.continuous = false;
        this.recognition.interimResults = false;
        this.recognition.lang = this.app.state.currentLanguage;

        this.recognition.onstart = () => {
            console.log('🎤 Voice recognition started');
            this.isRecording = true;
            this.updateVoiceButtonState(true);
        };

        this.recognition.onresult = (event) => {
            const transcript = event.results[0][0].transcript;
            console.log('🎤 Voice input:', transcript);
            
            // Set the transcript in the input field
            this.app.elements.input.value = transcript;
            
            // Trigger input change event
            const inputEvent = new Event('input', { bubbles: true });
            this.app.elements.input.dispatchEvent(inputEvent);
            
            // Auto-send if configured
            if (this.app.config.autoSendVoice) {
                this.app.sendMessage();
            }
        };

        this.recognition.onerror = (event) => {
            console.error('❌ Voice recognition error:', event.error);
            this.stopRecording();
        };

        this.recognition.onend = () => {
            console.log('🎤 Voice recognition ended');
            this.stopRecording();
        };
    }

    /**
     * Toggle recording
     */
    toggleRecording() {
        if (!this.recognition) {
            this.app.showNotification('音声認識はサポートされていません。', 'error');
            return;
        }

        if (this.isRecording) {
            this.stopRecording();
        } else {
            this.startRecording();
        }
    }

    /**
     * Start recording
     */
    startRecording() {
        try {
            this.recognition.start();
        } catch (error) {
            console.error('❌ Failed to start voice recognition:', error);
            this.app.showNotification('音声認識の開始に失敗しました。', 'error');
        }
    }

    /**
     * Stop recording
     */
    stopRecording() {
        if (this.recognition && this.isRecording) {
            this.recognition.stop();
        }
        this.isRecording = false;
        this.updateVoiceButtonState(false);
    }

    /**
     * Update voice button state
     */
    updateVoiceButtonState(isRecording) {
        if (this.app.elements.voiceButton) {
            this.app.elements.voiceButton.classList.toggle('recording', isRecording);
            this.app.elements.voiceButton.title = isRecording ? '録音中...' : '音声入力';
        }
    }

    /**
     * Speak text (Text-to-Speech)
     */
    speak(text, options = {}) {
        if (!this.synthesis) {
            console.warn('⚠️ Speech synthesis not supported');
            return;
        }

        // Cancel any ongoing speech
        this.synthesis.cancel();

        const utterance = new SpeechSynthesisUtterance(text);
        utterance.lang = options.lang || this.app.state.currentLanguage;
        utterance.rate = options.rate || 1.0;
        utterance.pitch = options.pitch || 1.0;
        utterance.volume = options.volume || 1.0;

        utterance.onstart = () => console.log('🔊 Speech synthesis started');
        utterance.onend = () => console.log('🔊 Speech synthesis ended');
        utterance.onerror = (event) => console.error('❌ Speech synthesis error:', event.error);

        this.synthesis.speak(utterance);
    }

    /**
     * Cleanup
     */
    cleanup() {
        if (this.isRecording) {
            this.stopRecording();
        }
        if (this.synthesis) {
            this.synthesis.cancel();
        }
    }
}

/**
 * Analytics Tracker Component
 * Tracks user interactions and performance
 */
class GAAPAnalyticsTracker {
    constructor(app) {
        this.app = app;
        this.events = [];
        this.sessionId = this.generateSessionId();
        this.startTime = Date.now();
    }

    /**
     * Track message interaction
     */
    trackMessage(userMessage, aiResponse, responseTime) {
        this.trackEvent('message_sent', {
            messageLength: userMessage.length,
            responseTime: responseTime,
            aiConfidence: aiResponse.confidence,
            hasGrants: (aiResponse.grants || []).length > 0,
            hasSources: (aiResponse.sources || []).length > 0,
            timestamp: Date.now()
        });
    }

    /**
     * Track typing behavior
     */
    trackTyping(inputLength) {
        this.trackEvent('typing', {
            inputLength: inputLength,
            timestamp: Date.now()
        }, false); // Don't send immediately
    }

    /**
     * Track engagement
     */
    trackEngagement(type, data = {}) {
        this.trackEvent('engagement', {
            type: type,
            ...data,
            timestamp: Date.now()
        });
    }

    /**
     * Track performance metrics
     */
    trackPerformance(metrics) {
        this.trackEvent('performance', {
            ...metrics,
            sessionDuration: Date.now() - this.startTime,
            timestamp: Date.now()
        });
    }

    /**
     * Track error
     */
    trackError(error, context) {
        this.trackEvent('error', {
            error: error.message || error,
            context: context,
            userAgent: navigator.userAgent,
            timestamp: Date.now()
        });
    }

    /**
     * Track generic event
     */
    trackEvent(eventType, data, sendImmediately = true) {
        const event = {
            type: eventType,
            sessionId: this.sessionId,
            conversationId: this.app.state.conversationId,
            data: data
        };
        
        this.events.push(event);
        
        console.log(`📊 Analytics: ${eventType}`, data);
        
        if (sendImmediately) {
            this.sendEvents();
        }
    }

    /**
     * Send events to server
     */
    async sendEvents() {
        if (this.events.length === 0) return;
        
        const eventsToSend = [...this.events];
        this.events = [];
        
        try {
            await this.app.sendAjaxRequest('gaap_track_analytics', {
                events: JSON.stringify(eventsToSend),
                nonce: this.app.config.nonce
            });
        } catch (error) {
            console.warn('⚠️ Failed to send analytics:', error);
            // Re-add events to queue for retry
            this.events.unshift(...eventsToSend);
        }
    }

    /**
     * Generate session ID
     */
    generateSessionId() {
        return `session_${Date.now()}_${Math.random().toString(36).substr(2, 9)}`;
    }

    /**
     * Get session statistics
     */
    getSessionStats() {
        return {
            sessionId: this.sessionId,
            duration: Date.now() - this.startTime,
            eventCount: this.events.length,
            conversationId: this.app.state.conversationId
        };
    }

    /**
     * Cleanup
     */
    cleanup() {
        // Send any remaining events
        this.sendEvents();
    }
}

/**
 * Error Handler Component
 * Centralized error handling and reporting
 */
class GAAPErrorHandler {
    constructor(app) {
        this.app = app;
        this.errorCount = 0;
        this.maxErrors = 10;
    }

    /**
     * Handle error
     */
    handleError(error, context = 'unknown') {
        this.errorCount++;
        
        console.error(`❌ GAAP Error [${context}]:`, error);
        
        // Track error analytics
        if (this.app.components.analyticsTracker) {
            this.app.components.analyticsTracker.trackError(error, context);
        }
        
        // Store error for debugging
        this.app.performance.errors.push({
            error: error.message || error,
            context: context,
            timestamp: Date.now()
        });
        
        // Determine error severity and response
        const severity = this.getErrorSeverity(error, context);
        this.handleBySeverity(error, context, severity);
        
        // Emergency mode if too many errors
        if (this.errorCount > this.maxErrors) {
            this.activateEmergencyMode();
        }
    }

    /**
     * Get error severity
     */
    getErrorSeverity(error, context) {
        const criticalContexts = ['initialization', 'component_init'];
        const networkErrors = ['Network error', 'Failed to fetch'];
        
        if (criticalContexts.includes(context)) {
            return 'critical';
        }
        
        if (networkErrors.some(ne => error.message.includes(ne))) {
            return 'network';
        }
        
        if (error.name === 'TypeError' || error.name === 'ReferenceError') {
            return 'high';
        }
        
        return 'low';
    }

    /**
     * Handle error by severity
     */
    handleBySeverity(error, context, severity) {
        switch (severity) {
            case 'critical':
                this.handleCriticalError(error, context);
                break;
            case 'network':
                this.handleNetworkError(error, context);
                break;
            case 'high':
                this.handleHighSeverityError(error, context);
                break;
            case 'low':
            default:
                this.handleLowSeverityError(error, context);
                break;
        }
    }

    /**
     * Handle critical error
     */
    handleCriticalError(error, context) {
        this.app.showNotification(
            'システムに重大なエラーが発生しました。ページを再読み込みしてください。',
            'error',
            0 // Don't auto-hide
        );
        
        // Disable interface
        if (this.app.elements.input) {
            this.app.elements.input.disabled = true;
        }
        if (this.app.elements.sendButton) {
            this.app.elements.sendButton.disabled = true;
        }
        
        // Try to save conversation
        try {
            this.app.saveConversationHistory();
        } catch (e) {
            console.error('Failed to save conversation on critical error:', e);
        }
    }

    /**
     * Handle network error
     */
    handleNetworkError(error, context) {
        this.app.updateConnectionStatus(false);
        
        this.app.showNotification(
            'ネットワークエラーが発生しました。接続を確認してください。',
            'warning',
            8000
        );
    }

    /**
     * Handle high severity error
     */
    handleHighSeverityError(error, context) {
        this.app.showNotification(
            'エラーが発生しました。しばらくしてからお試しください。',
            'error',
            5000
        );
        
        // Reset typing state
        this.app.hideTypingIndicator();
        
        // Re-enable interface
        if (this.app.elements.sendButton) {
            this.app.elements.sendButton.disabled = false;
        }
    }

    /**
     * Handle low severity error
     */
    handleLowSeverityError(error, context) {
        // Just log for now, don't show notification unless in debug mode
        if (this.app.config.debug) {
            this.app.showNotification(
                `軽微なエラーが発生しました: ${error.message}`,
                'info',
                3000
            );
        }
    }

    /**
     * Activate emergency mode
     */
    activateEmergencyMode() {
        console.error('🚨 Activating emergency mode due to excessive errors');
        
        this.app.showNotification(
            'エラーが多発しています。エマージェンシーモードに切り替えました。',
            'error',
            0
        );
        
        // Disable advanced features
        this.app.config.enableVoice = false;
        this.app.config.enableAnalytics = false;
        
        // Show emergency reset button
        this.showEmergencyResetButton();
    }

    /**
     * Show emergency reset button
     */
    showEmergencyResetButton() {
        const resetBtn = document.createElement('button');
        resetBtn.textContent = '🔄 システムリセット';
        resetBtn.className = 'gaap-btn gaap-btn-danger gaap-emergency-reset';
        resetBtn.onclick = () => this.performEmergencyReset();
        
        this.app.elements.container.appendChild(resetBtn);
    }

    /**
     * Perform emergency reset
     */
    async performEmergencyReset() {
        try {
            // Clear local storage
            Object.keys(localStorage).forEach(key => {
                if (key.startsWith('gaap_')) {
                    localStorage.removeItem(key);
                }
            });
            
            // Call server-side reset
            await this.app.sendAjaxRequest('gaap_emergency_reset', {
                nonce: this.app.config.nonce
            });
            
            // Reload page
            window.location.reload();
            
        } catch (error) {
            console.error('Emergency reset failed:', error);
            this.app.showNotification('リセットに失敗しました。手動でページを再読み込みしてください。', 'error');
        }
    }
}

/**
 * Cache Manager Component
 * Handles client-side caching
 */
class GAAPCacheManager {
    constructor() {
        this.cache = new Map();
        this.maxSize = 100;
        this.ttl = 30 * 60 * 1000; // 30 minutes
    }

    /**
     * Get item from cache
     */
    get(key) {
        const item = this.cache.get(key);
        
        if (!item) {
            return null;
        }
        
        if (Date.now() > item.expiry) {
            this.cache.delete(key);
            return null;
        }
        
        return item.data;
    }

    /**
     * Set item in cache
     */
    set(key, data, customTtl) {
        // Remove oldest items if cache is full
        if (this.cache.size >= this.maxSize) {
            const firstKey = this.cache.keys().next().value;
            this.cache.delete(firstKey);
        }
        
        const expiry = Date.now() + (customTtl || this.ttl);
        this.cache.set(key, { data, expiry });
    }

    /**
     * Clear cache
     */
    clear() {
        this.cache.clear();
    }

    /**
     * Get cache statistics
     */
    getStats() {
        return {
            size: this.cache.size,
            maxSize: this.maxSize,
            usage: (this.cache.size / this.maxSize) * 100
        };
    }
}

// Global initialization when DOM is ready
document.addEventListener('DOMContentLoaded', function() {
    // Check if GAAP chat interface exists on page
    if (document.querySelector('.gaap-chat-container')) {
        console.log('🚀 Starting Grant AI Assistant Pro...');
        
        // Initialize GAAP
        window.GAAP = new GAAPProChat({
            enableVoice: true,
            enableAnalytics: true,
            theme: localStorage.getItem('gaap_theme') || 'default'
        });
        
        // Make GAAP available globally for debugging
        if (window.console && typeof window.console.log === 'function') {
            console.log('🔧 GAAP instance available at window.GAAP');
            console.log('🔧 Try: GAAP.getStatistics()');
        }
    }
});

// Export for module systems
if (typeof module !== 'undefined' && module.exports) {
    module.exports = { GAAPProChat, GAAPMessageRenderer, GAAPVoiceHandler, GAAPAnalyticsTracker, GAAPErrorHandler, GAAPCacheManager };
}

// AMD support
if (typeof define === 'function' && define.amd) {
    define([], function() {
        return { GAAPProChat, GAAPMessageRenderer, GAAPVoiceHandler, GAAPAnalyticsTracker, GAAPErrorHandler, GAAPCacheManager };
    });
}

/* End of Enterprise JavaScript Framework */