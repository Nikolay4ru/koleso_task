// chat.js - Модуль чата для сервера
const crypto = require('crypto');

class ChatManager {
    constructor(db, io) {
        this.db = db;
        this.io = io;
        this.activeCalls = new Map(); // Активные звонки
        this.userSockets = new Map(); // userId -> Set of socketIds
        
        // Инициализация структуры чатов в БД
        if (!this.db.data.chats) {
            this.db.data.chats = [];
        }
        if (!this.db.data.chatMessages) {
            this.db.data.chatMessages = [];
        }
        if (!this.db.data.chatMembers) {
            this.db.data.chatMembers = [];
        }
        if (!this.db.data.calls) {
            this.db.data.calls = [];
        }
    }

    // ==================== УПРАВЛЕНИЕ ЧАТАМИ ====================

    async createChat(chatData) {
        const chat = {
            id: Date.now().toString(),
            ...chatData,
            createdAt: new Date().toISOString(),
            updatedAt: new Date().toISOString(),
            lastMessageAt: null,
            lastMessage: null,
            unreadCounts: {} // userId -> count
        };

        this.db.data.chats.push(chat);

        // Добавляем участников
        for (const userId of chatData.members) {
            await this.addChatMember(chat.id, userId, userId === chatData.creatorId ? 'admin' : 'member');
        }

        await this.db.save();
        return chat;
    }

    async createPrivateChat(userId1, userId2) {
        // Проверяем, существует ли уже приватный чат
        const existingChat = this.db.data.chats.find(chat => 
            chat.type === 'private' && 
            chat.members.includes(userId1) && 
            chat.members.includes(userId2) &&
            chat.members.length === 2
        );

        if (existingChat) {
            return existingChat;
        }

        const user1 = await this.db.findUserById(userId1);
        const user2 = await this.db.findUserById(userId2);

        return this.createChat({
            type: 'private',
            name: null, // В приватных чатах имя генерируется на клиенте
            members: [userId1, userId2],
            creatorId: userId1,
            avatar: null,
            metadata: {
                user1: { id: userId1, name: user1.name, avatar: user1.avatar },
                user2: { id: userId2, name: user2.name, avatar: user2.avatar }
            }
        });
    }

    async createGroupChat(name, members, creatorId, avatar = null) {
        return this.createChat({
            type: 'group',
            name,
            members,
            creatorId,
            avatar,
            metadata: {}
        });
    }

    async createTaskChat(taskId, members, creatorId) {
        const task = this.db.data.tasks.find(t => t.id === taskId);
        if (!task) {
            throw new Error('Task not found');
        }

        return this.createChat({
            type: 'task',
            name: `Задача: ${task.title}`,
            members,
            creatorId,
            avatar: null,
            metadata: { taskId, taskTitle: task.title }
        });
    }

    async getUserChats(userId) {
        // Убеждаемся, что структура существует
        if (!this.db.data.chatMembers) {
            this.db.data.chatMembers = [];
        }
        if (!this.db.data.chats) {
            this.db.data.chats = [];
        }

        const userChatIds = this.db.data.chatMembers
            .filter(m => m.userId === userId && m.status === 'active')
            .map(m => m.chatId);

        const chats = this.db.data.chats.filter(chat => userChatIds.includes(chat.id));
        
        // Добавляем информацию о непрочитанных сообщениях
        for (const chat of chats) {
            chat.unreadCount = chat.unreadCounts && chat.unreadCounts[userId] || 0;
            
            // Для приватных чатов добавляем информацию о собеседнике
            if (chat.type === 'private') {
                const otherUserId = chat.members.find(id => id !== userId);
                const otherUser = await this.db.findUserById(otherUserId);
                if (otherUser) {
                    chat.otherUser = {
                        id: otherUser.id,
                        name: otherUser.name,
                        avatar: otherUser.avatar,
                        online: this.isUserOnline(otherUser.id)
                    };
                }
            }
        }

        return chats.sort((a, b) => 
            new Date(b.lastMessageAt || b.createdAt) - new Date(a.lastMessageAt || a.createdAt)
        );
    }

    async getChatById(chatId, userId) {
        const chat = this.db.data.chats.find(c => c.id === chatId);
        if (!chat) return null;

        // Проверяем, является ли пользователь участником
        const isMember = this.db.data.chatMembers.some(
            m => m.chatId === chatId && m.userId === userId && m.status === 'active'
        );

        if (!isMember) return null;

        // Добавляем информацию об участниках
        chat.membersInfo = await Promise.all(
            chat.members.map(async memberId => {
                const user = await this.db.findUserById(memberId);
                const member = this.db.data.chatMembers.find(
                    m => m.chatId === chatId && m.userId === memberId
                );
                return {
                    id: user.id,
                    name: user.name,
                    avatar: user.avatar,
                    role: member ? member.role : 'member',
                    online: this.isUserOnline(user.id),
                    lastSeen: user.lastSeen
                };
            })
        );

        return chat;
    }

    // ==================== УПРАВЛЕНИЕ УЧАСТНИКАМИ ====================

    async addChatMember(chatId, userId, role = 'member') {
        const member = {
            id: Date.now().toString(),
            chatId,
            userId,
            role,
            joinedAt: new Date().toISOString(),
            status: 'active',
            notifications: true,
            lastReadMessageId: null
        };

        this.db.data.chatMembers.push(member);
        
        const chat = this.db.data.chats.find(c => c.id === chatId);
        if (chat && !chat.members.includes(userId)) {
            chat.members.push(userId);
        }

        await this.db.save();
        return member;
    }

    async removeChatMember(chatId, userId) {
        const member = this.db.data.chatMembers.find(
            m => m.chatId === chatId && m.userId === userId
        );

        if (member) {
            member.status = 'removed';
            member.leftAt = new Date().toISOString();
        }

        const chat = this.db.data.chats.find(c => c.id === chatId);
        if (chat) {
            chat.members = chat.members.filter(id => id !== userId);
        }

        await this.db.save();
        return true;
    }

    async updateChatMemberRole(chatId, userId, newRole) {
        const member = this.db.data.chatMembers.find(
            m => m.chatId === chatId && m.userId === userId
        );

        if (member) {
            member.role = newRole;
            await this.db.save();
        }

        return member;
    }

    // ==================== СООБЩЕНИЯ ====================

    async sendMessage(messageData) {
        const message = {
            id: Date.now().toString(),
            ...messageData,
            createdAt: new Date().toISOString(),
            edited: false,
            editedAt: null,
            deleted: false,
            reactions: {},
            readBy: [messageData.senderId],
            attachments: messageData.attachments || []
        };

        this.db.data.chatMessages.push(message);

        // Обновляем информацию о последнем сообщении в чате
        const chat = this.db.data.chats.find(c => c.id === messageData.chatId);
        if (chat) {
            chat.lastMessage = {
                id: message.id,
                text: message.text,
                senderId: message.senderId,
                senderName: message.senderName,
                createdAt: message.createdAt
            };
            chat.lastMessageAt = message.createdAt;
            chat.updatedAt = message.createdAt;

            // Инициализируем unreadCounts если его нет
            if (!chat.unreadCounts) {
                chat.unreadCounts = {};
            }

            // Увеличиваем счетчики непрочитанных для всех, кроме отправителя
            for (const memberId of chat.members) {
                if (memberId !== message.senderId) {
                    chat.unreadCounts[memberId] = (chat.unreadCounts[memberId] || 0) + 1;
                }
            }
        }

        await this.db.save();

        // Отправляем сообщение через WebSocket всем участникам чата
        this.broadcastToChat(messageData.chatId, 'new_message', message);

        return message;
    }

    async getChatMessages(chatId, userId, limit = 50, before = null) {
        // Убеждаемся, что структура существует
        if (!this.db.data.chatMessages) {
            this.db.data.chatMessages = [];
        }
        if (!this.db.data.chatMembers) {
            this.db.data.chatMembers = [];
        }

        // Проверяем доступ
        const isMember = this.db.data.chatMembers.some(
            m => m.chatId === chatId && m.userId === userId && m.status === 'active'
        );

        if (!isMember) return [];

        let messages = this.db.data.chatMessages
            .filter(m => m.chatId === chatId && !m.deleted)
            .sort((a, b) => new Date(b.createdAt) - new Date(a.createdAt));

        if (before) {
            const beforeIndex = messages.findIndex(m => m.id === before);
            if (beforeIndex !== -1) {
                messages = messages.slice(beforeIndex + 1);
            }
        }

        messages = messages.slice(0, limit);

        // Добавляем информацию об отправителях и инициализируем readBy
        for (const message of messages) {
            // Инициализируем readBy если его нет
            if (!message.readBy) {
                message.readBy = [];
                // Если сообщение от текущего пользователя, помечаем как прочитанное им
                if (message.senderId === userId) {
                    message.readBy.push(userId);
                }
            }

            const sender = await this.db.findUserById(message.senderId);
            if (sender) {
                message.sender = {
                    id: sender.id,
                    name: sender.name,
                    avatar: sender.avatar
                };
            }
        }

        return messages.reverse();
    }

    async editMessage(messageId, userId, newText) {
        const message = this.db.data.chatMessages.find(m => m.id === messageId);
        
        if (!message || message.senderId !== userId || message.deleted) {
            return null;
        }

        message.text = newText;
        message.edited = true;
        message.editedAt = new Date().toISOString();

        await this.db.save();

        // Уведомляем участников чата об изменении
        this.broadcastToChat(message.chatId, 'message_edited', {
            messageId: message.id,
            newText: message.text,
            editedAt: message.editedAt
        });

        return message;
    }

    async deleteMessage(messageId, userId) {
        const message = this.db.data.chatMessages.find(m => m.id === messageId);
        
        if (!message || message.senderId !== userId) {
            return false;
        }

        message.deleted = true;
        message.deletedAt = new Date().toISOString();

        await this.db.save();

        // Уведомляем участников чата об удалении
        this.broadcastToChat(message.chatId, 'message_deleted', {
            messageId: message.id
        });

        return true;
    }

    async markMessagesAsRead(chatId, userId, messageIds) {
        const chat = this.db.data.chats.find(c => c.id === chatId);
        if (!chat) return;

        // Инициализируем unreadCounts если его нет
        if (!chat.unreadCounts) {
            chat.unreadCounts = {};
        }

        // Сбрасываем счетчик непрочитанных
        chat.unreadCounts[userId] = 0;

        // Отмечаем сообщения как прочитанные
        for (const messageId of messageIds) {
            const message = this.db.data.chatMessages.find(m => m.id === messageId);
            if (message) {
                // Инициализируем readBy если его нет
                if (!message.readBy) {
                    message.readBy = [];
                }
                // Добавляем пользователя если его еще нет в списке прочитавших
                if (!message.readBy.includes(userId)) {
                    message.readBy.push(userId);
                }
            }
        }

        // Обновляем последнее прочитанное сообщение для участника
        const member = this.db.data.chatMembers.find(
            m => m.chatId === chatId && m.userId === userId
        );
        if (member && messageIds.length > 0) {
            member.lastReadMessageId = messageIds[messageIds.length - 1];
        }

        await this.db.save();

        // Уведомляем других участников о прочтении
        this.broadcastToChat(chatId, 'messages_read', {
            userId,
            messageIds
        }, userId);

        return true;
    }

    async addReaction(messageId, userId, emoji) {
        const message = this.db.data.chatMessages.find(m => m.id === messageId);
        if (!message) return null;

        if (!message.reactions[emoji]) {
            message.reactions[emoji] = [];
        }

        if (!message.reactions[emoji].includes(userId)) {
            message.reactions[emoji].push(userId);
        }

        await this.db.save();

        // Уведомляем участников чата о реакции
        this.broadcastToChat(message.chatId, 'reaction_added', {
            messageId: message.id,
            userId,
            emoji
        });

        return message;
    }

    async removeReaction(messageId, userId, emoji) {
        const message = this.db.data.chatMessages.find(m => m.id === messageId);
        if (!message || !message.reactions[emoji]) return null;

        message.reactions[emoji] = message.reactions[emoji].filter(id => id !== userId);
        
        if (message.reactions[emoji].length === 0) {
            delete message.reactions[emoji];
        }

        await this.db.save();

        // Уведомляем участников чата об удалении реакции
        this.broadcastToChat(message.chatId, 'reaction_removed', {
            messageId: message.id,
            userId,
            emoji
        });

        return message;
    }

    // ==================== ВИДЕО/АУДИО ЗВОНКИ ====================

    async startCall(chatId, initiatorId, callType = 'video') {
        const chat = this.db.data.chats.find(c => c.id === chatId);
        if (!chat) return null;

        const call = {
            id: Date.now().toString(),
            chatId,
            initiatorId,
            type: callType,
            status: 'pending',
            participants: [initiatorId],
            startedAt: new Date().toISOString(),
            endedAt: null,
            sdpOffers: {},
            iceCandiates: {}
        };

        this.db.data.calls.push(call);
        this.activeCalls.set(call.id, call);
        await this.db.save();

        // Уведомляем всех участников чата о звонке
        this.broadcastToChat(chatId, 'incoming_call', {
            callId: call.id,
            initiatorId,
            callType,
            chatName: chat.name || 'Приватный чат'
        }, initiatorId);

        return call;
    }

    async joinCall(callId, userId) {
        const call = this.activeCalls.get(callId);
        if (!call || call.status === 'ended') return null;

        if (!call.participants.includes(userId)) {
            call.participants.push(userId);
        }

        if (call.status === 'pending') {
            call.status = 'active';
        }

        // Уведомляем других участников о присоединении
        this.broadcastToCall(callId, 'participant_joined', {
            userId,
            participants: call.participants
        }, userId);

        return call;
    }

    async leaveCall(callId, userId) {
        const call = this.activeCalls.get(callId);
        if (!call) return null;

        call.participants = call.participants.filter(id => id !== userId);

        // Если больше нет участников, завершаем звонок
        if (call.participants.length === 0) {
            return this.endCall(callId);
        }

        // Уведомляем других участников об уходе
        this.broadcastToCall(callId, 'participant_left', {
            userId,
            participants: call.participants
        }, userId);

        return call;
    }

    async endCall(callId) {
        const call = this.activeCalls.get(callId);
        if (!call) return null;

        call.status = 'ended';
        call.endedAt = new Date().toISOString();

        const dbCall = this.db.data.calls.find(c => c.id === callId);
        if (dbCall) {
            dbCall.status = 'ended';
            dbCall.endedAt = call.endedAt;
            await this.db.save();
        }

        // Уведомляем всех участников о завершении звонка
        this.broadcastToCall(callId, 'call_ended', {
            endedAt: call.endedAt
        });

        this.activeCalls.delete(callId);
        return call;
    }

    // WebRTC сигналинг
    async handleSignaling(callId, userId, signal) {
        const call = this.activeCalls.get(callId);
        if (!call || !call.participants.includes(userId)) return;

        switch (signal.type) {
            case 'offer':
                call.sdpOffers[userId] = signal.offer;
                this.broadcastToCall(callId, 'sdp_offer', {
                    userId,
                    offer: signal.offer
                }, userId);
                break;

            case 'answer':
                this.sendToUser(signal.targetUserId, 'sdp_answer', {
                    userId,
                    answer: signal.answer
                });
                break;

            case 'ice-candidate':
                if (!call.iceCandiates[userId]) {
                    call.iceCandiates[userId] = [];
                }
                call.iceCandiates[userId].push(signal.candidate);
                
                this.broadcastToCall(callId, 'ice_candidate', {
                    userId,
                    candidate: signal.candidate
                }, userId);
                break;
        }
    }

    // ==================== ВСПОМОГАТЕЛЬНЫЕ МЕТОДЫ ====================

    isUserOnline(userId) {
        return this.userSockets.has(userId) && this.userSockets.get(userId).size > 0;
    }

    getUserSocketIds(userId) {
        return Array.from(this.userSockets.get(userId) || []);
    }

    addUserSocket(userId, socketId) {
        if (!this.userSockets.has(userId)) {
            this.userSockets.set(userId, new Set());
        }
        this.userSockets.get(userId).add(socketId);
    }

    removeUserSocket(userId, socketId) {
        const sockets = this.userSockets.get(userId);
        if (sockets) {
            sockets.delete(socketId);
            if (sockets.size === 0) {
                this.userSockets.delete(userId);
            }
        }
    }

    broadcastToChat(chatId, event, data, excludeUserId = null) {
        const chat = this.db.data.chats.find(c => c.id === chatId);
        if (!chat) return;

        for (const memberId of chat.members) {
            if (memberId !== excludeUserId) {
                this.sendToUser(memberId, event, data);
            }
        }
    }

    broadcastToCall(callId, event, data, excludeUserId = null) {
        const call = this.activeCalls.get(callId);
        if (!call) return;

        for (const participantId of call.participants) {
            if (participantId !== excludeUserId) {
                this.sendToUser(participantId, event, data);
            }
        }
    }

    sendToUser(userId, event, data) {
        const socketIds = this.getUserSocketIds(userId);
        for (const socketId of socketIds) {
            this.io.to(socketId).emit(event, data);
        }
    }

    // ==================== ТИПИЗАЦИЯ ====================

    async typingStarted(chatId, userId) {
        this.broadcastToChat(chatId, 'user_typing', {
            chatId,
            userId,
            isTyping: true
        }, userId);
    }

    async typingStopped(chatId, userId) {
        this.broadcastToChat(chatId, 'user_typing', {
            chatId,
            userId,
            isTyping: false
        }, userId);
    }

    // ==================== ПОИСК ====================

    async searchMessages(userId, query, chatId = null) {
        // Убеждаемся, что структура существует
        if (!this.db.data.chatMessages) {
            this.db.data.chatMessages = [];
        }
        if (!this.db.data.chatMembers) {
            this.db.data.chatMembers = [];
        }

        let messages = this.db.data.chatMessages.filter(m => !m.deleted);

        // Фильтруем по доступным чатам пользователя
        const userChatIds = this.db.data.chatMembers
            .filter(m => m.userId === userId && m.status === 'active')
            .map(m => m.chatId);

        messages = messages.filter(m => userChatIds.includes(m.chatId));

        // Если указан конкретный чат
        if (chatId) {
            messages = messages.filter(m => m.chatId === chatId);
        }

        // Поиск по тексту
        const searchQuery = query.toLowerCase();
        messages = messages.filter(m => 
            m.text && m.text.toLowerCase().includes(searchQuery) ||
            m.senderName && m.senderName.toLowerCase().includes(searchQuery)
        );

        // Сортируем по дате
        messages.sort((a, b) => new Date(b.createdAt) - new Date(a.createdAt));

        // Ограничиваем результаты
        return messages.slice(0, 50);
    }

    async searchChats(userId, query) {
        const userChats = await this.getUserChats(userId);
        const searchQuery = query.toLowerCase();

        return userChats.filter(chat => {
            // Поиск по имени чата
            if (chat.name && chat.name.toLowerCase().includes(searchQuery)) {
                return true;
            }

            // Для приватных чатов - поиск по имени собеседника
            if (chat.type === 'private' && chat.otherUser) {
                return chat.otherUser.name.toLowerCase().includes(searchQuery);
            }

            // Поиск по последнему сообщению
            if (chat.lastMessage && chat.lastMessage.text && chat.lastMessage.text.toLowerCase().includes(searchQuery)) {
                return true;
            }

            return false;
        });
    }
}

module.exports = ChatManager;