const express = require('express');
const cors = require('cors');
const { openDatabase, DB_PATH } = require('./db');
const { handleAction } = require('./actions');

const PORT = Number(process.env.PORT) || 8888;
const API_PATH = '/final_A6_term4/api';

const app = express();
const db = openDatabase();

app.use(cors());
app.use(express.json({ limit: '1mb' }));

function dispatch(req, res) {
  const action = (req.query.action || '').toString().trim();
  const body = req.body && typeof req.body === 'object' ? req.body : {};

  if (!action) {
    return res.status(200).json({ status: 'error', message: 'Missing ?action= parameter.' });
  }

  try {
    const result = handleAction(db, action, body);
    return res.status(200).json(result);
  } catch (error) {
    const payload = error.payload || {
      status: 'error',
      message: error.message || 'Internal server error.',
    };
    console.error(`[${action}]`, error.message || error);
    return res.status(200).json(payload);
  }
}

app.get(API_PATH, (_req, res) => {
  res.json({
    status: 'success',
    message: 'Monefy API is running.',
    usage: `POST ${API_PATH}/?action=<action_name> with JSON body`,
    actions: [
      'register',
      'login',
      'login_with_token',
      'get_dashboard',
      'get_currencies',
      'update_currency_balance',
      'get_categories',
      'add_category',
      'delete_category',
      'get_payment_methods',
      'add_expense',
      'add_income',
      'transfer',
      'delete_transaction',
      'get_profile',
      'update_profile',
      'change_password',
      'export_transactions',
    ],
  });
});

app.post([API_PATH, `${API_PATH}/`], dispatch);

app.listen(PORT, '0.0.0.0', () => {
  console.log(`Monefy API listening on http://0.0.0.0:${PORT}${API_PATH}`);
  console.log(`Database: ${DB_PATH}`);
  console.log('Demo user: kim / 12345');
});
