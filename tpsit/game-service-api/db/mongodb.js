const { MongoClient } = require('mongodb');

// Connection URL
const url = process.env.MONGODB_URI || 'mongodb://localhost:27017';
const dbName = process.env.DB_NAME || 'tictactoe';

let client;
let db;

async function connectToDatabase() {
  try {
    client = new MongoClient(url);
    await client.connect();
    console.log('Connected to MongoDB server');
    
    db = client.db(dbName);
    console.log(`Using database: ${dbName}`);
    
    return db;
  } catch (error) {
    console.error('MongoDB connection error:', error);
    throw error;
  }
}

function getDb() {
  if (!db) {
    throw new Error('Database not initialized. Call connectToDatabase first.');
  }
  return db;
}

async function closeConnection() {
  if (client) {
    await client.close();
    console.log('MongoDB connection closed');
  }
}

module.exports = {
  connectToDatabase,
  getDb,
  closeConnection
};