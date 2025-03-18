const dgram = require('dgram');
const server = dgram.createSocket('udp4');

const devices = [];

const areas = [
    {
        name: "linea di produzione",
        frequency: 300
    },

    {
        name: "ufficio",
        frequency: 1000
    }

]

server.on('message', (msg, info) => {
    console.log(`${msg} ricevuto da: ${info.address}:${info.port}`);
    const message = JSON.parse(msg);

    if(!devices.some(device => device.address === info.address && device.port === info.port)){
        devices.push(info);
        areas.some(area => {
            if(area.name === message.area){
                server.send(JSON.stringify({frequency: area.frequency}), info.port, info.address);
            } 
        });
    }
    else{
        if(message.temp > ((message.utilyTemp) + (message.utilyTemp / 10))){
            server.send(JSON.stringify({heater: false}), info.port, info.address);
        }
        else if(message.temp < (message.utilyTemp) - (message.utilyTemp * 5 / 100)){
            server.send(JSON.stringify({heater: true}), info.port, info.address);
        }
    }

})

server.on('listening', () => {
    const address = server.address();
    console.log(`Server UDP in ascolto su ${address.address}:${address.port}`);
});

server.bind(12345);