const express = require('express');
const app = express();
const http = require('http').createServer(app);
const io = require('socket.io')(http);

app.use(express.static('public'));

let users = new Set();
let lastMessages = new Set();

io.on('connection', (socket) => {
    let nickname;

    socket.on('join', (username) => {
        nickname = username;
        users.add(username);
        io.emit('userJoined', username);
        io.emit('onlineUsers', Array.from(users));
    });

    socket.on('typing', () => {
        socket.broadcast.emit('userTyping', nickname);
    });

    socket.on('stopTyping', () => {
        socket.broadcast.emit('userStopTyping', nickname);
    });

    socket.on('chatMessage', (message) => {
        if (lastMessages.has(message)) {
            socket.emit('error', 'Questo messaggio è già stato inviato');
            return;
        }
        lastMessages.add(message);
        io.emit('message', {
            user: nickname,
            text: message
        });
    });

    socket.on('disconnect', () => {
        if (nickname) {
            users.delete(nickname);
            io.emit('userLeft', nickname);
            io.emit('onlineUsers', Array.from(users));
        }
    });
});

http.listen(3000, () => {
    console.log('Server running on port 3000');
});