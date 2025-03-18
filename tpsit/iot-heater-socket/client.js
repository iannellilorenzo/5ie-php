const {error} = require('console');
const dgram = require('dgram');
const readLinePromise = require('readline-promise').default;

const rl = readLinePromise.createInterface({
    input: process.stdin,
    output: process.stdout
})

const client = dgram.createSocket('udp4');

const serverAddress = '127.0.0.1';
const serverPort = 12345;

const device = {
    name: "",
    area: "",
    temp: 0.0,
    frequency: -1, //frequenza in millisecondi
    heater: true,

}

const Main = async () => {
    device.name = await rl.questionAsync('Inserisci nome dispositivo: ');
    device.area = await rl.questionAsync('Inserisci nome area: ');
    device.utilyTemp =  parseFloat(await rl.questionAsync('Inserisci temperatura di utilizzo: '));
    client.send(JSON.stringify(device), serverPort, serverAddress);
}

Main().catch((error) => {
    console.log(error);
})

client.on('message', (msg) => {
    msg = JSON.parse(msg);
    if(msg?.frequency != undefined){
        device.frequency = msg.frequency;
        setInterval(updateTemp, device.frequency);
    }
    else if(msg?.heater != undefined){
        device.heater = msg.heater;
    }
})

function updateTemp() {
    if(device.heater == true){
        device.temp++;
    }
    else{
        device.temp--;
    }

    //console.log(device);
    client.send(JSON.stringify(device), serverPort, serverAddress);
}