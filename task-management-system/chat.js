// ===== ИСПРАВЛЕННАЯ СЕРВЕРНАЯ ЧАСТЬ (добавить в server.js) =====

// Улучшенный ChatManager с корректной обработкой WebRTC
class ChatManager {
    constructor(db, io) {
        this.db = db;
        this.io = io;
        this.userSockets = new Map(); // userId -> Set of socketIds
        this.activeCalls = new Map(); // callId -> call object
        this.socketUsers = new Map(); // socketId -> userId
    }

    // Управление подключениями
    addUserSocket(userId, socketId) {
        if (!this.userSockets.has(userId)) {
            this.userSockets.set(userId, new Set());
        }
        this.userSockets.get(userId).add(socketId);
        this.socketUsers.set(socketId, userId);
        console.log(`User ${userId} connected with socket ${socketId}`);
    }

    removeUserSocket(userId, socketId) {
        const sockets = this.userSockets.get(userId);
        if (sockets) {
            sockets.delete(socketId);
            if (sockets.size === 0) {
                this.userSockets.delete(userId);
            }
        }
        this.socketUsers.delete(socketId);
        console.log(`User ${userId} disconnected socket ${socketId}`);
    }

    getUserIdBySocket(socketId) {
        return this.socketUsers.get(socketId);
    }

    // ===== УПРАВЛЕНИЕ ЗВОНКАМИ =====

    async startCall(chatId, initiatorId, callType) {
        console.log(`Starting ${callType} call in chat ${chatId} by user ${initiatorId}`);
        
        // Найдем чат или создадим приватный
        let chat = this.db.data.chats?.find(c => c.id === chatId);
        if (!chat) {
            // Создаем приватный чат если его нет
            const chatMembers = [initiatorId, parseInt(chatId)]; // Предполагаем что chatId это userId для приватного чата
            chat = {
                id: Date.now().toString(),
                type: 'private',
                members: chatMembers,
                createdAt: new Date().toISOString()
            };
            
            if (!this.db.data.chats) this.db.data.chats = [];
            this.db.data.chats.push(chat);
            await this.db.save();
        }

        const call = {
            id: Date.now().toString(),
            chatId: chat.id,
            initiatorId,
            type: callType,
            status: 'pending',
            participants: [initiatorId],
            createdAt: new Date().toISOString(),
            sdpOffers: {},
            sdpAnswers: {},
            iceCandidates: {}
        };

        this.activeCalls.set(call.id, call);

        // Сохраняем в БД
        if (!this.db.data.calls) this.db.data.calls = [];
        this.db.data.calls.push({ ...call });
        await this.db.save();

        // Уведомляем всех участников чата о звонке (кроме инициатора)
        const recipients = chat.members.filter(id => id !== initiatorId);
        
        for (const recipientId of recipients) {
            this.sendToUser(recipientId, 'incoming_call', {
                callId: call.id,
                initiatorId,
                callType,
                chatName: chat.name || 'Приватный чат',
                chatId: chat.id
            });
        }

        console.log(`Call ${call.id} started, notified ${recipients.length} recipients`);
        return call;
    }

    async acceptCall(callId, userId) {
        const call = this.activeCalls.get(callId);
        if (!call || call.status === 'ended') {
            console.log(`Call ${callId} not found or ended`);
            return null;
        }

        if (!call.participants.includes(userId)) {
            call.participants.push(userId);
        }

        call.status = 'active';
        
        console.log(`User ${userId} accepted call ${callId}`);

        // Уведомляем инициатора о принятии звонка
        this.sendToUser(call.initiatorId, 'call_accepted', {
            callId,
            userId,
            participants: call.participants
        });

        return call;
    }

    async declineCall(callId, userId) {
        const call = this.activeCalls.get(callId);
        if (!call) return null;

        console.log(`User ${userId} declined call ${callId}`);

        // Уведомляем инициатора об отклонении
        this.sendToUser(call.initiatorId, 'call_declined', {
            callId,
            userId
        });

        // Завершаем звонок
        return this.endCall(callId);
    }

    async endCall(callId) {
        const call = this.activeCalls.get(callId);
        if (!call) return null;

        call.status = 'ended';
        call.endedAt = new Date().toISOString();

        console.log(`Call ${callId} ended`);

        // Обновляем в БД
        const dbCall = this.db.data.calls?.find(c => c.id === callId);
        if (dbCall) {
            dbCall.status = 'ended';
            dbCall.endedAt = call.endedAt;
            await this.db.save();
        }

        // Уведомляем всех участников о завершении звонка
        for (const participantId of call.participants) {
            this.sendToUser(participantId, 'call_ended', {
                callId,
                endedAt: call.endedAt
            });
        }

        this.activeCalls.delete(callId);
        return call;
    }

    // ===== WEBRTC СИГНАЛИНГ =====

    async handleSignaling(callId, fromUserId, signal) {
        const call = this.activeCalls.get(callId);
        if (!call) {
            console.log(`Call ${callId} not found for signaling`);
            return;
        }

        console.log(`Handling ${signal.type} signal for call ${callId} from user ${fromUserId}`);

        switch (signal.type) {
            case 'offer':
                // Сохраняем offer
                call.sdpOffers[fromUserId] = signal.offer;
                
                // Отправляем offer всем участникам кроме отправителя
                for (const participantId of call.participants) {
                    if (participantId !== fromUserId) {
                        this.sendToUser(participantId, 'sdp_offer', {
                            callId,
                            userId: fromUserId,
                            offer: signal.offer
                        });
                    }
                }
                break;

            case 'answer':
                // Сохраняем answer
                call.sdpAnswers[fromUserId] = signal.answer;
                
                // Отправляем answer инициатору (или всем участникам)
                for (const participantId of call.participants) {
                    if (participantId !== fromUserId) {
                        this.sendToUser(participantId, 'sdp_answer', {
                            callId,
                            userId: fromUserId,
                            answer: signal.answer
                        });
                    }
                }
                break;

            case 'ice-candidate':
                // Сохраняем ICE candidate
                if (!call.iceCandidates[fromUserId]) {
                    call.iceCandidates[fromUserId] = [];
                }
                call.iceCandidates[fromUserId].push(signal.candidate);
                
                // Передаем ICE candidate всем участникам кроме отправителя
                for (const participantId of call.participants) {
                    if (participantId !== fromUserId) {
                        this.sendToUser(participantId, 'ice_candidate', {
                            callId,
                            userId: fromUserId,
                            candidate: signal.candidate
                        });
                    }
                }
                break;

            default:
                console.log(`Unknown signal type: ${signal.type}`);
        }
    }

    // ===== ВСПОМОГАТЕЛЬНЫЕ МЕТОДЫ =====

    sendToUser(userId, event, data) {
        const socketIds = Array.from(this.userSockets.get(userId) || []);
        for (const socketId of socketIds) {
            this.io.to(socketId).emit(event, data);
        }
    }

    isUserOnline(userId) {
        return this.userSockets.has(userId);
    }
}

// Инициализация менеджера чата
const chatManager = new ChatManager(db, io);

// ===== WEBSOCKET ОБРАБОТЧИКИ ДЛЯ ЗВОНКОВ =====

io.on('connection', (socket) => {
    console.log('WebSocket подключен:', socket.id);
    
    // Аутентификация
    socket.on('authenticate', async (token) => {
        try {
            const decoded = jwt.verify(token, config.jwtSecret);
            const user = await db.findUserById(decoded.userId);
            
            if (user) {
                socket.userId = user.id;
                socket.user = user;
                
                // Добавляем пользователя в менеджер
                chatManager.addUserSocket(user.id, socket.id);
                
                socket.emit('authenticated', { success: true, user });
                console.log(`User ${user.id} (${user.name}) authenticated`);
            } else {
                socket.emit('authenticated', { success: false, error: 'User not found' });
            }
        } catch (error) {
            console.error('Authentication error:', error);
            socket.emit('authenticated', { success: false, error: 'Invalid token' });
        }
    });

    // ===== ОБРАБОТЧИКИ ЗВОНКОВ =====

    // Начало звонка
    socket.on('call_start', async ({ chatId, type }) => {
        if (!socket.userId) {
            socket.emit('call_error', { error: 'Not authenticated' });
            return;
        }

        try {
            console.log(`User ${socket.userId} starting ${type} call in chat ${chatId}`);
            const call = await chatManager.startCall(chatId, socket.userId, type);
            socket.emit('call_started', call);
        } catch (error) {
            console.error('Error starting call:', error);
            socket.emit('call_error', { error: error.message });
        }
    });

    // Принятие звонка
    socket.on('call_accepted', async ({ callId }) => {
        if (!socket.userId) return;

        try {
            console.log(`User ${socket.userId} accepting call ${callId}`);
            const call = await chatManager.acceptCall(callId, socket.userId);
            socket.emit('call_joined', call);
        } catch (error) {
            console.error('Error accepting call:', error);
            socket.emit('call_error', { error: error.message });
        }
    });

    // Отклонение звонка
    socket.on('call_declined', async ({ callId }) => {
        if (!socket.userId) return;

        try {
            console.log(`User ${socket.userId} declining call ${callId}`);
            await chatManager.declineCall(callId, socket.userId);
        } catch (error) {
            console.error('Error declining call:', error);
            socket.emit('call_error', { error: error.message });
        }
    });

    // Завершение звонка
    socket.on('call_end', async ({ callId }) => {
        if (!socket.userId) return;

        try {
            console.log(`User ${socket.userId} ending call ${callId}`);
            await chatManager.endCall(callId);
        } catch (error) {
            console.error('Error ending call:', error);
        }
    });

    // WebRTC сигналинг
    socket.on('call_signal', async ({ callId, signal }) => {
        if (!socket.userId) return;

        try {
            await chatManager.handleSignaling(callId, socket.userId, signal);
        } catch (error) {
            console.error('Error handling signaling:', error);
            socket.emit('call_error', { error: error.message });
        }
    });

    // Отключение пользователя
    socket.on('disconnect', () => {
        console.log('WebSocket отключен:', socket.id);
        
        if (socket.userId) {
            chatManager.removeUserSocket(socket.userId, socket.id);
            
            // Если пользователь отключился во время звонка, завершаем все его активные звонки
            for (const [callId, call] of chatManager.activeCalls) {
                if (call.participants.includes(socket.userId)) {
                    console.log(`Ending call ${callId} due to user ${socket.userId} disconnect`);
                    chatManager.endCall(callId);
                }
            }
        }
    });
});