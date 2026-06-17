(function () {
    'use strict';

    const API_BASE = '../api';
    const POLL_MS = 8000;

    const listEl = document.getElementById('chatConversationList');
    const listLoading = document.getElementById('chatListLoading');
    const searchInput = document.getElementById('chatSearch');
    const emptyState = document.getElementById('chatEmptyState');
    const thread = document.getElementById('chatThread');
    const threadTitle = document.getElementById('chatThreadTitle');
    const threadSubtitle = document.getElementById('chatThreadSubtitle');
    const messagesEl = document.getElementById('chatMessages');
    const composeForm = document.getElementById('chatComposeForm');
    const messageInput = document.getElementById('chatMessageInput');
    const composeError = document.getElementById('chatComposeError');
    const sendBtn = document.getElementById('chatSendBtn');
    const headerUnread = document.getElementById('chatHeaderUnread');
    const sidebarCol = document.getElementById('chatSidebarCol');
    const threadCol = document.getElementById('chatThreadCol');
    const backToListBtn = document.getElementById('chatBackToList');

    let conversations = [];
    let activeId = '';
    let lastMessageId = 0;
    let pollTimer = null;
    let canSend = true;

    function escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }

    function formatPreview(text) {
        const plain = (text || '').replace(/\s+/g, ' ').trim();
        if (plain.length <= 80) return plain;
        return plain.slice(0, 80) + '…';
    }

    function formatTime(iso) {
        if (!iso) return '';
        const d = new Date(iso.replace(' ', 'T'));
        if (Number.isNaN(d.getTime())) return iso;
        const now = new Date();
        const today = new Date(now.getFullYear(), now.getMonth(), now.getDate());
        const day = new Date(d.getFullYear(), d.getMonth(), d.getDate());
        const hm = d.toLocaleTimeString('ru-RU', { hour: '2-digit', minute: '2-digit' });
        if (+day === +today) return hm;
        if (+day === +today - 86400000) return 'Вчера ' + hm;
        return d.toLocaleDateString('ru-RU') + ' ' + hm;
    }

    function convIcon(type) {
        if (type === 'admin') return 'bi-building';
        if (type === 'group_broadcast' || type === 'club_broadcast') return 'bi-people';
        if (type === 'club_teacher') return 'bi-palette';
        return 'bi-person-badge';
    }

    function updateHeaderUnread(total) {
        if (!headerUnread) return;
        if (total > 0) {
            headerUnread.textContent = String(total);
            headerUnread.classList.remove('d-none');
        } else {
            headerUnread.classList.add('d-none');
        }
    }

    function renderConversationList(filter) {
        const q = (filter || '').trim().toLowerCase();
        const items = conversations.filter(function (c) {
            if (!q) return true;
            return (c.title + ' ' + c.subtitle).toLowerCase().includes(q);
        });

        if (listLoading) listLoading.remove();

        if (items.length === 0) {
            listEl.innerHTML = '<div class="text-center text-muted py-5 px-3">Чатов пока нет</div>';
            return;
        }

        listEl.innerHTML = items.map(function (c) {
            const active = c.id === activeId ? ' active' : '';
            const unread = c.unread_count > 0
                ? '<span class="badge bg-danger rounded-pill chat-unread-badge">' + c.unread_count + '</span>'
                : '';
            const preview = c.last_message
                ? '<div class="chat-conv-preview text-muted">' +
                    (c.last_message.is_mine ? 'Вы: ' : '') +
                    escapeHtml(formatPreview(c.last_message.body)) +
                  '</div>'
                : '<div class="chat-conv-preview text-muted">Нет сообщений</div>';

            return '<button type="button" class="chat-conv-item' + active + '" data-id="' + escapeHtml(c.id) + '">' +
                '<div class="chat-conv-icon"><i class="bi ' + convIcon(c.conv_type) + '"></i></div>' +
                '<div class="chat-conv-body">' +
                    '<div class="d-flex justify-content-between gap-2">' +
                        '<div class="fw-semibold text-truncate">' + escapeHtml(c.title) + '</div>' +
                        unread +
                    '</div>' +
                    (c.subtitle
                        ? '<div class="small text-muted">' + escapeHtml(c.subtitle) + '</div>'
                        : '') +
                    preview +
                '</div>' +
            '</button>';
        }).join('');

        listEl.querySelectorAll('.chat-conv-item').forEach(function (btn) {
            btn.addEventListener('click', function () {
                openConversation(btn.dataset.id);
            });
        });
    }

    function renderMessages(messages, append) {
        if (!append) {
            messagesEl.innerHTML = '';
            lastMessageId = 0;
        }

        messages.forEach(function (m) {
            if (m.id <= lastMessageId && append) return;
            lastMessageId = Math.max(lastMessageId, m.id);

            const mine = m.is_mine ? ' chat-message--mine' : '';
            const bubble = document.createElement('div');
            bubble.className = 'chat-message' + mine;
            bubble.dataset.id = String(m.id);
            bubble.innerHTML =
                '<div class="chat-message-meta">' +
                    (m.is_mine ? '' : '<span class="fw-semibold">' + escapeHtml(m.sender_name) + '</span> · ') +
                    '<span class="text-muted">' + formatTime(m.created_at) + '</span>' +
                '</div>' +
                '<div class="chat-message-bubble">' + escapeHtml(m.body).replace(/\n/g, '<br>') + '</div>';
            messagesEl.appendChild(bubble);
        });

        if (!append || messages.length > 0) {
            messagesEl.scrollTop = messagesEl.scrollHeight;
        }
    }

    function showMobileThread() {
        if (window.innerWidth < 992) {
            sidebarCol.classList.add('d-none');
            threadCol.classList.remove('d-none');
        }
    }

    function showMobileList() {
        if (window.innerWidth < 992) {
            sidebarCol.classList.remove('d-none');
            threadCol.classList.add('d-none');
        }
    }

    async function loadConversations() {
        const res = await fetch(API_BASE + '/chat_conversations.php', { credentials: 'same-origin' });
        const data = await res.json();
        if (!data.success) throw new Error(data.message || 'Ошибка');

        conversations = data.conversations || [];
        updateHeaderUnread(data.unread_total || 0);
        renderConversationList(searchInput ? searchInput.value : '');
    }

    async function loadMessages(conversationId, afterId, append) {
        const url = API_BASE + '/chat_messages.php?conv=' + encodeURIComponent(conversationId) +
            (afterId ? '&after_id=' + afterId : '');
        const res = await fetch(url, { credentials: 'same-origin' });
        const data = await res.json();
        if (!data.success) throw new Error(data.message || 'Ошибка');

        if (!append && data.conversation) {
            threadTitle.textContent = data.conversation.title || '—';
            threadSubtitle.textContent = data.conversation.subtitle || '';
            threadSubtitle.classList.toggle('d-none', !data.conversation.subtitle);
            canSend = !!data.conversation.can_send;
            composeForm.classList.toggle('d-none', !canSend);
        }

        renderMessages(data.messages || [], append);
        return data;
    }

    async function openConversation(id) {
        activeId = id;
        renderConversationList(searchInput ? searchInput.value : '');

        emptyState.classList.add('d-none');
        thread.classList.remove('d-none');
        thread.classList.add('d-flex');
        showMobileThread();

        messagesEl.innerHTML = '<div class="text-center text-muted py-4">Загрузка...</div>';
        lastMessageId = 0;

        try {
            await loadMessages(id, 0, false);
            await loadConversations();
            startPolling();
        } catch (e) {
            messagesEl.innerHTML = '<div class="text-center text-danger py-4">Не удалось загрузить сообщения</div>';
        }
    }

    function startPolling() {
        stopPolling();
        pollTimer = setInterval(async function () {
            if (!activeId) return;
            try {
                await loadMessages(activeId, lastMessageId, true);
                await loadConversations();
            } catch (e) {
                /* ignore transient errors */
            }
        }, POLL_MS);
    }

    function stopPolling() {
        if (pollTimer) {
            clearInterval(pollTimer);
            pollTimer = null;
        }
    }

    composeForm.addEventListener('submit', async function (e) {
        e.preventDefault();
        composeError.classList.add('d-none');

        const body = messageInput.value.trim();
        if (!body || !activeId) return;

        sendBtn.disabled = true;
        try {
            const formData = new FormData();
            formData.append('conv', activeId);
            formData.append('body', body);

            const res = await fetch(API_BASE + '/chat_messages.php', {
                method: 'POST',
                body: formData,
                credentials: 'same-origin',
            });
            const data = await res.json();
            if (!data.success) {
                composeError.textContent = data.message || 'Ошибка отправки';
                composeError.classList.remove('d-none');
                return;
            }

            messageInput.value = '';
            renderMessages([data.message], true);
            await loadConversations();
        } catch (err) {
            composeError.textContent = 'Не удалось отправить сообщение';
            composeError.classList.remove('d-none');
        } finally {
            sendBtn.disabled = false;
            messageInput.focus();
        }
    });

    if (searchInput) {
        searchInput.addEventListener('input', function () {
            renderConversationList(searchInput.value);
        });
    }

    if (backToListBtn) {
        backToListBtn.addEventListener('click', showMobileList);
    }

    messageInput.addEventListener('keydown', function (e) {
        if (e.key === 'Enter' && !e.shiftKey) {
            e.preventDefault();
            composeForm.requestSubmit();
        }
    });

    window.addEventListener('resize', function () {
        if (window.innerWidth >= 992) {
            sidebarCol.classList.remove('d-none');
            threadCol.classList.remove('d-none');
        } else if (activeId) {
            showMobileThread();
        } else {
            showMobileList();
        }
    });

    loadConversations().then(function () {
        const params = new URLSearchParams(window.location.search);
        const initialConv = params.get('conv');
        if (initialConv) {
            openConversation(initialConv);
        } else if (window.innerWidth < 992 && !activeId) {
            showMobileList();
        }
    }).catch(function () {
        if (listLoading) listLoading.textContent = 'Не удалось загрузить чаты';
    });
})();
