<?php
include 'config.php';
if (!isset($_SESSION['user_id'])) {
    header("Location: index.html");
    exit();
}

$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$_SESSION['user_id']]);
$user = $stmt->fetch();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ShoChat</title>
    <link rel="stylesheet" href="style.css">
    <style>
        /* Fixed Chat Layout */
        .chat-interface {
            display: flex;
            height: calc(100vh - 180px); /* Fixed height for chat interface */
            gap: 1.5rem;
            margin-top: 1rem;
        }

        .users-list {
            width: 280px;
            background: var(--surface);
            border-radius: 12px;
            box-shadow: var(--shadow);
            padding: 1rem;
            overflow-y: auto; /* Scrollable user list */
        }

        .main-chat-area {
            flex: 1;
            display: flex;
            flex-direction: column;
            background: var(--surface);
            border-radius: 12px;
            box-shadow: var(--shadow);
            height: calc(100vh - 180px); /* Fixed height for chat area */
        }

        .messages-container {
            flex: 1;
            overflow-y: auto; /* Scrollable messages */
            padding: 1.5rem;
        }

        .message-input-container {
            padding: 1.5rem;
            background: var(--surface);
            border-top: 2px solid var(--border);
        }

        /* Message Styling */
        .message {
            max-width: 70%;
            padding: 1rem;
            margin-bottom: 1rem;
            border-radius: 12px;
            background: var(--background);
        }

        .message.sent {
            background: var(--primary-color);
            color: white;
            margin-left: auto;
        }

        /* Responsive Design */
        @media (max-width: 768px) {
            .chat-interface {
                flex-direction: column;
                display: flex;
                height: calc(100vh - 180px); /* Fixed height for chat interface */
                gap: 1.5rem;
                margin-top: 1rem;
            }

            .users-list {
                width: 100%;
                height: 200px; /* Fixed height for mobile user list */
            }

            .main-chat-area {
                height: 60vh; /* Fixed height for mobile chat area */
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- Welcome Section -->
        <div class="welcome-section">
            <div class="user-avatar">
                <?= strtoupper(substr($user['username'], 0, 1)) ?>
            </div>
            <div class="user-info">
                <h2 id="usernameDisplay"><?= htmlspecialchars($user['username']) ?></h2>
                <p>Joined <?= date('F Y', strtotime($user['created_at'])) ?></p>
            </div>
            <button class="edit-profile-btn" onclick="showProfileEdit()">
                Edit Profile
            </button>
        </div>

        <!-- Chat Interface -->
        <div class="chat-interface">
            <!-- Users List -->
            <div class="users-list">
                <h3>Online Users</h3>
                <div class="users-container" id="userListContent"></div>
            </div>

            <!-- Main Chat Area -->
            <div class="main-chat-area">
                <div class="chat-header" id="chatHeader">
                    <h3>Global Chat</h3>
                </div>
                <div class="messages-container" id="messages">
                    <div class="loading-overlay" id="loadingOverlay">
                        <div class="spinner"></div>
                    </div>
                </div>
                <div class="message-input-container">
                    <input type="text" id="messageInput" placeholder="Type your message...">
                    <button class="send-button" onclick="sendMessage()">Send</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Profile Modal -->
    <div class="modal-overlay" id="profileModal">
        <div class="profile-modal">
            <div class="modal-header">
                <h3>Edit Profile</h3>
                <button class="close-btn" onclick="closeProfileEdit()">&times;</button>
            </div>
            <input type="text" id="newUsername" 
                   value="<?= htmlspecialchars($user['username']) ?>" 
                   class="form-input">
            <div class="modal-actions">
                <button class="btn secondary" onclick="closeProfileEdit()">Cancel</button>
                <button class="btn primary" onclick="updateProfile()">Save</button>
            </div>
        </div>
    </div>

    <script>
        let currentChatType = 'global';
        let selectedUserId = null;
        let autoScroll = true;

        function initializeChat() {
            loadMessages();
            loadUsers();
            setInterval(() => {
                loadMessages();
                loadUsers();
            }, 2000);

            // Detect scroll position
            const messagesContainer = document.getElementById('messages');
            messagesContainer.addEventListener('scroll', () => {
                autoScroll = (messagesContainer.scrollTop + messagesContainer.clientHeight >= messagesContainer.scrollHeight - 50);
            });
        }

        async function loadMessages() {
            try {
                const url = currentChatType === 'global' 
                    ? 'get_messages.php?type=global' 
                    : `get_messages.php?type=private&receiver=${selectedUserId}`;

                const response = await fetch(url);
                const messages = await response.json();
                const container = document.getElementById('messages');
                
                container.innerHTML = messages.map(msg => `
                    <div class="message ${msg.sender_id == <?= $_SESSION['user_id'] ?> ? 'sent' : 'received'}">
                        <div class="message-header">
                            <span>${escapeHTML(msg.sender || msg.username)}</span>
                            <span>${new Date(msg.sent_at).toLocaleTimeString()}</span>
                        </div>
                        <div class="message-body">${escapeHTML(msg.message)}</div>
                    </div>
                `).join('');

                if (autoScroll) {
                    container.scrollTop = container.scrollHeight;
                }
            } catch (error) {
                console.error('Error loading messages:', error);
            }
        }

        async function sendMessage() {
            const messageInput = document.getElementById('messageInput');
            const message = messageInput.value.trim();
            if (!message) return;

            try {
                const payload = {
                    type: currentChatType,
                    message: message
                };

                if (currentChatType === 'private') {
                    payload.receiver = selectedUserId;
                }

                await fetch('send_message.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(payload)
                });

                messageInput.value = '';
                await loadMessages();
            } catch (error) {
                console.error('Error sending message:', error);
            }
        }

        // User Selection
        window.selectUser = function(userId, username) {
            if (selectedUserId === userId) {
                // Switch back to global chat
                currentChatType = 'global';
                selectedUserId = null;
                document.getElementById('chatHeader').innerHTML = '<h3>Global Chat</h3>';
            } else {
                currentChatType = 'private';
                selectedUserId = userId;
                document.getElementById('chatHeader').innerHTML = `
                    <h3>Chat with ${escapeHTML(username)}</h3>
                    <button class="btn secondary" onclick="selectUser(null, null)">Back to Global</button>
                `;
            }
            loadMessages();
        };

        // User list handling
        async function loadUsers() {
            try {
                const response = await fetch('get_users.php');
                const users = await response.json();
                const container = document.getElementById('userListContent');

                container.innerHTML = users.map(user => `
                    <div class="user-item ${selectedUserId === user.id ? 'selected' : ''}" 
                         onclick="selectUser(${user.id}, '${escapeHTML(user.username)}')">
                        ${escapeHTML(user.username)}
                        ${user.id != <?= $_SESSION['user_id'] ?> ? '<span class="online-dot"></span>' : ''}
                    </div>
                `).join('');
            } catch (error) {
                console.error('Error loading users:', error);
            }
        }

        // Profile Modal Functions
        window.showProfileEdit = () => document.getElementById('profileModal').classList.add('active');
        window.closeProfileEdit = () => document.getElementById('profileModal').classList.remove('active');

        window.updateProfile = async function() {
            const newUsername = document.getElementById('newUsername').value.trim();
            if (!newUsername) return;

            try {
                await fetch('update_profile.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: `username=${encodeURIComponent(newUsername)}`
                });

                document.getElementById('usernameDisplay').textContent = newUsername;
                closeProfileEdit();
                loadUsers();
            } catch (error) {
                console.error('Error updating profile:', error);
            }
        };

        // Helper functions
        function escapeHTML(str) {
            return str.replace(/&/g, "&amp;")
                     .replace(/</g, "&lt;")
                     .replace(/>/g, "&gt;")
                     .replace(/"/g, "&quot;")
                     .replace(/'/g, "&#039;");
        }

        document.addEventListener('DOMContentLoaded', initializeChat);
    </script>
</body>
</html>