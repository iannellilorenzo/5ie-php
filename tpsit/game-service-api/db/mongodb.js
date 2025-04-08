const { MongoClient } = require('mongodb');
require('dotenv').config();

const uri = process.env.MONGODB_URI || 'mongodb://localhost:27017';
const dbName = process.env.DB_NAME || 'game_service_db';

let client;
let db;

async function connectToDatabase() {
  if (db) return db;
  
  try {
    client = new MongoClient(uri);
    await client.connect();
    console.log('Connected to MongoDB');
    
    db = client.db(dbName);
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

function closeConnection() {
  if (client) {
    client.close();
    console.log('MongoDB connection closed');
  }
}

module.exports = {
  connectToDatabase,
  getDb,
  closeConnection
};