const fs = require('fs');
const path = require('path');
const crypto = require('crypto');
const Database = require('better-sqlite3');

const DATA_DIR = path.join(__dirname, '..', 'data');
const DB_PATH = path.join(DATA_DIR, 'monefy.db');

const CURRENCY_META = {
  USD: { currency_name: 'US Dollar', symbol: '$', rate_to_usd: 1 },
  KHR: { currency_name: 'Cambodian Riel', symbol: '៛', rate_to_usd: 4000 },
  EUR: { currency_name: 'Euro', symbol: '€', rate_to_usd: 0.92 },
  GBP: { currency_name: 'British Pound', symbol: '£', rate_to_usd: 0.79 },
};

const DEFAULT_EXPENSE_CATEGORIES = [
  ['Groceries', '#e07090'],
  ['Housing', '#5b8dd9'],
  ['Car', '#888888'],
  ['Dining', '#d4a017'],
  ['Transit', '#5aaa78'],
  ['Hygiene', '#9b59b6'],
  ['Entertainment', '#c0a0c8'],
  ['Sports', '#5aaa78'],
  ['Taxi', '#e05555'],
  ['Health', '#6dbf8c'],
  ['Clothing', '#9b59b6'],
  ['Phone', '#b784a7'],
  ['Gifts', '#c0a0c8'],
  ['Pets', '#5aaa78'],
];

const DEFAULT_INCOME_CATEGORIES = [
  ['Salary', '#6dbf8c'],
  ['Bonus', '#6dbf8c'],
  ['Investment', '#6dbf8c'],
];

function hashPassword(password) {
  const salt = crypto.randomBytes(16).toString('hex');
  const hash = crypto.scryptSync(password, salt, 64).toString('hex');
  return `${salt}:${hash}`;
}

function verifyPassword(password, stored) {
  const [salt, hash] = stored.split(':');
  if (!salt || !hash) return false;
  const attempt = crypto.scryptSync(password, salt, 64).toString('hex');
  return crypto.timingSafeEqual(Buffer.from(hash, 'hex'), Buffer.from(attempt, 'hex'));
}

function createToken() {
  return crypto.randomBytes(16).toString('hex');
}

function initSchema(db) {
  db.exec(`
    CREATE TABLE IF NOT EXISTS users (
      id INTEGER PRIMARY KEY AUTOINCREMENT,
      username TEXT NOT NULL UNIQUE COLLATE NOCASE,
      email TEXT NOT NULL,
      password_hash TEXT NOT NULL,
      created_at TEXT NOT NULL DEFAULT (datetime('now'))
    );

    CREATE TABLE IF NOT EXISTS tokens (
      token TEXT PRIMARY KEY,
      user_id INTEGER NOT NULL REFERENCES users(id) ON DELETE CASCADE,
      expires_at TEXT NOT NULL,
      created_at TEXT NOT NULL DEFAULT (datetime('now'))
    );

    CREATE TABLE IF NOT EXISTS currencies (
      id INTEGER PRIMARY KEY AUTOINCREMENT,
      user_id INTEGER NOT NULL REFERENCES users(id) ON DELETE CASCADE,
      currency_code TEXT NOT NULL,
      currency_name TEXT NOT NULL,
      symbol TEXT NOT NULL,
      wallet REAL NOT NULL DEFAULT 0,
      UNIQUE(user_id, currency_code)
    );

    CREATE TABLE IF NOT EXISTS categories (
      id INTEGER PRIMARY KEY AUTOINCREMENT,
      category_name TEXT NOT NULL,
      record_type TEXT NOT NULL CHECK (record_type IN ('expense', 'income')),
      color TEXT NOT NULL DEFAULT '#6dbf8c',
      display_order INTEGER NOT NULL DEFAULT 0
    );

    CREATE TABLE IF NOT EXISTS payment_methods (
      id INTEGER PRIMARY KEY AUTOINCREMENT,
      user_id INTEGER,
      method_name TEXT NOT NULL
    );

    CREATE TABLE IF NOT EXISTS expenses (
      id INTEGER PRIMARY KEY AUTOINCREMENT,
      user_id INTEGER NOT NULL REFERENCES users(id) ON DELETE CASCADE,
      amount REAL NOT NULL,
      category_id INTEGER NOT NULL REFERENCES categories(id),
      payment_method_id INTEGER NOT NULL REFERENCES payment_methods(id),
      currency_code TEXT NOT NULL,
      note TEXT NOT NULL DEFAULT '',
      date TEXT NOT NULL DEFAULT (datetime('now', 'localtime'))
    );

    CREATE TABLE IF NOT EXISTS incomes (
      id INTEGER PRIMARY KEY AUTOINCREMENT,
      user_id INTEGER NOT NULL REFERENCES users(id) ON DELETE CASCADE,
      amount REAL NOT NULL,
      currency_code TEXT NOT NULL,
      note TEXT NOT NULL DEFAULT '',
      date TEXT NOT NULL DEFAULT (datetime('now', 'localtime'))
    );

    CREATE TABLE IF NOT EXISTS transfers (
      id INTEGER PRIMARY KEY AUTOINCREMENT,
      user_id INTEGER NOT NULL REFERENCES users(id) ON DELETE CASCADE,
      amount REAL NOT NULL,
      from_currency TEXT NOT NULL,
      to_currency TEXT NOT NULL,
      exchange_rate REAL NOT NULL DEFAULT 1,
      note TEXT NOT NULL DEFAULT '',
      date TEXT NOT NULL DEFAULT (datetime('now', 'localtime'))
    );
  `);
}

function seedGlobalData(db) {
  const categoryCount = db.prepare('SELECT COUNT(*) AS c FROM categories').get().c;
  if (categoryCount === 0) {
    const insert = db.prepare(
      'INSERT INTO categories (category_name, record_type, color, display_order) VALUES (?, ?, ?, ?)'
    );
    DEFAULT_EXPENSE_CATEGORIES.forEach(([name, color], i) => {
      insert.run(name, 'expense', color, i + 1);
    });
    DEFAULT_INCOME_CATEGORIES.forEach(([name, color], i) => {
      insert.run(name, 'income', color, i + 1);
    });
  }

  const pmCount = db.prepare('SELECT COUNT(*) AS c FROM payment_methods').get().c;
  if (pmCount === 0) {
    db.prepare('INSERT INTO payment_methods (user_id, method_name) VALUES (NULL, ?)').run('Cash');
    db.prepare('INSERT INTO payment_methods (user_id, method_name) VALUES (NULL, ?)').run('Card');
  }
}

function seedUserCurrencies(db, userId, wallets = {}) {
  const insert = db.prepare(
    `INSERT OR IGNORE INTO currencies (user_id, currency_code, currency_name, symbol, wallet)
     VALUES (?, ?, ?, ?, ?)`
  );
  for (const [code, meta] of Object.entries(CURRENCY_META)) {
    insert.run(userId, code, meta.currency_name, meta.symbol, wallets[code] ?? 0);
  }
}

const DEMO_USER = {
  username: 'kim',
  email: 'kim@example.com',
  password: '12345',
};

function seedDemoUser(db) {
  const passwordHash = hashPassword(DEMO_USER.password);
  const existing = db
    .prepare('SELECT id FROM users WHERE username = ? COLLATE NOCASE')
    .get(DEMO_USER.username);

  if (existing) {
    db.prepare('UPDATE users SET password_hash = ?, email = ? WHERE id = ?').run(
      passwordHash,
      DEMO_USER.email,
      existing.id
    );
    return;
  }

  const result = db
    .prepare('INSERT INTO users (username, email, password_hash) VALUES (?, ?, ?)')
    .run(DEMO_USER.username, DEMO_USER.email, passwordHash);

  const userId = result.lastInsertRowid;
  seedUserCurrencies(db, userId, { USD: 1000, KHR: 0, EUR: 0, GBP: 0 });
}

function openDatabase() {
  if (!fs.existsSync(DATA_DIR)) {
    fs.mkdirSync(DATA_DIR, { recursive: true });
  }

  const db = new Database(DB_PATH);
  db.pragma('journal_mode = WAL');
  db.pragma('foreign_keys = ON');
  initSchema(db);
  seedGlobalData(db);
  seedDemoUser(db);
  return db;
}

module.exports = {
  openDatabase,
  hashPassword,
  verifyPassword,
  createToken,
  seedUserCurrencies,
  CURRENCY_META,
  DB_PATH,
};
