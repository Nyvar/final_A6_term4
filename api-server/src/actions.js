const {
  hashPassword,
  verifyPassword,
  createToken,
  seedUserCurrencies,
  CURRENCY_META,
} = require('./db');

function fail(message) {
  const err = new Error(message);
  err.payload = { status: 'error', message };
  return err;
}

function ok(data = {}) {
  return { status: 'success', ...data };
}

function requireFields(body, fields) {
  for (const field of fields) {
    if (body[field] === undefined || body[field] === null || body[field] === '') {
      throw fail(`Missing required field: ${field}`);
    }
  }
}

function getUserFromToken(db, token) {
  if (!token || typeof token !== 'string') {
    throw fail('Invalid or expired token');
  }
  const now = new Date().toISOString().slice(0, 19).replace('T', ' ');
  const row = db
    .prepare(
      `SELECT u.id, u.username, u.email
       FROM tokens t
       JOIN users u ON u.id = t.user_id
       WHERE t.token = ? AND t.expires_at > ?`
    )
    .get(token.trim(), now);
  if (!row) {
    throw fail('Invalid or expired token');
  }
  return row;
}

function issueToken(db, userId) {
  const token = createToken();
  const expiresAt = new Date(Date.now() + 60 * 60 * 1000)
    .toISOString()
    .slice(0, 19)
    .replace('T', ' ');
  db.prepare('INSERT INTO tokens (token, user_id, expires_at) VALUES (?, ?, ?)').run(
    token,
    userId,
    expiresAt
  );
  return { token, expires_at: expiresAt };
}

function dateFilterClause(filter, startDate, endDate) {
  if (filter === 'interval' && startDate && endDate) {
    return {
      sql: 'AND date(__COL__) BETWEEN date(?) AND date(?)',
      params: [startDate, endDate],
      text: `${startDate} - ${endDate}`,
    };
  }
  switch (filter) {
    case 'day':
      return {
        sql: "AND date(__COL__) = date('now', 'localtime')",
        params: [],
        text: new Date().toLocaleDateString('en-US', { month: 'long', day: 'numeric', year: 'numeric' }),
      };
    case 'week':
      return {
        sql: "AND strftime('%Y-%W', __COL__, 'localtime') = strftime('%Y-%W', 'now', 'localtime')",
        params: [],
        text: 'This Week',
      };
    case 'year':
      return {
        sql: "AND strftime('%Y', __COL__, 'localtime') = strftime('%Y', 'now', 'localtime')",
        params: [],
        text: String(new Date().getFullYear()),
      };
    case 'month':
      return {
        sql: "AND strftime('%Y-%m', __COL__, 'localtime') = strftime('%Y-%m', 'now', 'localtime')",
        params: [],
        text: new Date().toLocaleDateString('en-US', { month: 'long', year: 'numeric' }),
      };
    default:
      return { sql: '', params: [], text: 'All Time' };
  }
}

function withDateColumn(filter, column) {
  return {
    sql: filter.sql.replace(/__COL__/g, column),
    params: filter.params,
    text: filter.text,
  };
}

function getWallet(db, userId, code) {
  const row = db
    .prepare('SELECT wallet FROM currencies WHERE user_id = ? AND currency_code = ?')
    .get(userId, code);
  return row ? row.wallet : 0;
}

function adjustWallet(db, userId, code, delta) {
  const row = db
    .prepare('SELECT id, wallet FROM currencies WHERE user_id = ? AND currency_code = ?')
    .get(userId, code);
  if (!row) {
    const meta = CURRENCY_META[code] || { currency_name: code, symbol: '$' };
    db.prepare(
      'INSERT INTO currencies (user_id, currency_code, currency_name, symbol, wallet) VALUES (?, ?, ?, ?, ?)'
    ).run(userId, code, meta.currency_name, meta.symbol, delta);
    return delta;
  }
  const next = row.wallet + delta;
  db.prepare('UPDATE currencies SET wallet = ? WHERE id = ?').run(next, row.id);
  return next;
}

function listUserCurrencies(db, userId) {
  return db
    .prepare(
      `SELECT user_id, currency_code, currency_name, symbol,
              printf('%.2f', wallet) AS wallet
       FROM currencies WHERE user_id = ?
       ORDER BY CASE currency_code WHEN 'USD' THEN 1 WHEN 'KHR' THEN 2 WHEN 'EUR' THEN 3 WHEN 'GBP' THEN 4 ELSE 5 END`
    )
    .all(userId);
}

function totalNetWorthUsd(currencies) {
  let total = 0;
  for (const c of currencies) {
    const rate = CURRENCY_META[c.currency_code]?.rate_to_usd ?? 1;
    total += Number(c.wallet) / rate;
  }
  return Math.round(total * 100) / 100;
}

const handlers = {
  register(db, body) {
    requireFields(body, ['username', 'email', 'password', 'confirm_password']);
    const username = String(body.username).trim();
    const email = String(body.email).trim();
    const password = String(body.password);

    if (username.length < 3) throw fail('Username must be at least 3 characters');
    if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) throw fail('Please enter a valid email address');
    if (password.length < 6) throw fail('Password must be at least 6 characters');
    if (password !== String(body.confirm_password)) throw fail('Passwords do not match');

    if (db.prepare('SELECT id FROM users WHERE username = ? COLLATE NOCASE').get(username)) {
      throw fail('Username already taken');
    }
    if (db.prepare('SELECT id FROM users WHERE email = ?').get(email)) {
      throw fail('Email already registered');
    }

    const result = db
      .prepare('INSERT INTO users (username, email, password_hash) VALUES (?, ?, ?)')
      .run(username, email, hashPassword(password));

    const userId = result.lastInsertRowid;
    seedUserCurrencies(db, userId, { USD: 5000, KHR: 20000000, EUR: 0, GBP: 0 });
    const session = issueToken(db, userId);

    return ok({
      message: 'Registration successful',
      ...session,
      user_id: userId,
      username,
      email,
    });
  },

  login(db, body) {
    requireFields(body, ['username', 'password']);
    const username = String(body.username).trim();
    const password = String(body.password);

    if (username === '__ping__') {
      throw fail('Invalid username or password');
    }

    const user = db
      .prepare(
        'SELECT id, username, email, password_hash FROM users WHERE username = ? COLLATE NOCASE OR email = ? COLLATE NOCASE'
      )
      .get(username, username);

    if (!user || !verifyPassword(password, user.password_hash)) {
      throw fail('Invalid username or password');
    }

    const session = issueToken(db, user.id);
    return ok({
      message: 'Login successful',
      ...session,
      user_id: user.id,
      username: user.username,
      email: user.email,
    });
  },

  login_with_token(db, body) {
    requireFields(body, ['token']);
    const user = getUserFromToken(db, body.token);
    const session = issueToken(db, user.id);
    db.prepare('DELETE FROM tokens WHERE token = ?').run(String(body.token).trim());
    return ok({
      message: 'Session restored',
      ...session,
      user_id: user.id,
      username: user.username,
      email: user.email,
    });
  },

  get_dashboard(db, body) {
    const user = getUserFromToken(db, body.token);
    const currency = (body.currency || 'USD').toString().toUpperCase();
    const dateFilter = (body.date_filter || 'month').toString();
    const baseFilter = dateFilterClause(dateFilter, body.start_date, body.end_date);
    const expenseFilter = withDateColumn(baseFilter, 'date');
    const incomeFilter = withDateColumn(baseFilter, 'date');
    const eFilter = withDateColumn(baseFilter, 'e.date');
    const iFilter = withDateColumn(baseFilter, 'i.date');
    const tFilter = withDateColumn(baseFilter, 't.date');

    const currencies = listUserCurrencies(db, user.id);
    const active = currencies.find((c) => c.currency_code === currency);
    const walletBalance = active ? Number(active.wallet) : 0;
    const currencySymbol = active?.symbol ?? '$';

    const expenseRow = db
      .prepare(
        `SELECT COALESCE(SUM(amount), 0) AS total FROM expenses
         WHERE user_id = ? AND currency_code = ? ${expenseFilter.sql}`
      )
      .get(user.id, currency, ...expenseFilter.params);

    const incomeRow = db
      .prepare(
        `SELECT COALESCE(SUM(amount), 0) AS total FROM incomes
         WHERE user_id = ? AND currency_code = ? ${incomeFilter.sql}`
      )
      .get(user.id, currency, ...incomeFilter.params);

    const expenseByCategory = db
      .prepare(
        `SELECT c.category_name, c.color, printf('%.2f', COALESCE(SUM(e.amount), 0)) AS total
         FROM expenses e
         LEFT JOIN categories c ON e.category_id = c.id
         WHERE e.user_id = ? AND e.currency_code = ? ${eFilter.sql}
         GROUP BY e.category_id
         ORDER BY total DESC
         LIMIT 5`
      )
      .all(user.id, currency, ...eFilter.params);

    const expenses = db
      .prepare(
        `SELECT 'expense' AS type, e.id, printf('%.2f', e.amount) AS amount, e.date, e.note,
                c.category_name, p.method_name AS payment_method, e.currency_code
         FROM expenses e
         LEFT JOIN categories c ON e.category_id = c.id
         LEFT JOIN payment_methods p ON e.payment_method_id = p.id
         WHERE e.user_id = ? AND e.currency_code = ? ${eFilter.sql}
         ORDER BY e.date DESC LIMIT 50`
      )
      .all(user.id, currency, ...eFilter.params);

    const incomes = db
      .prepare(
        `SELECT 'income' AS type, i.id, printf('%.2f', i.amount) AS amount, i.date, i.note,
                'Income' AS category_name, NULL AS payment_method, i.currency_code
         FROM incomes i
         WHERE i.user_id = ? AND i.currency_code = ? ${iFilter.sql}
         ORDER BY i.date DESC LIMIT 50`
      )
      .all(user.id, currency, ...iFilter.params);

    const transfers = db
      .prepare(
        `SELECT 'transfer' AS type, t.id, printf('%.2f', t.amount) AS amount, t.date, t.note,
                'Transfer: ' || t.from_currency || ' → ' || t.to_currency AS category_name,
                NULL AS payment_method, t.from_currency AS currency_code
         FROM transfers t
         WHERE t.user_id = ? ${tFilter.sql}
         ORDER BY t.date DESC LIMIT 50`
      )
      .all(user.id, ...tFilter.params);

    const transactions = [...expenses, ...incomes, ...transfers]
      .sort((a, b) => new Date(b.date) - new Date(a.date))
      .slice(0, 100);

    return ok({
      currency,
      currency_symbol: currencySymbol,
      date_filter: dateFilter,
      date_range_text: baseFilter.text,
      wallet_balance: walletBalance,
      total_expense: Number(expenseRow?.total ?? 0),
      total_income: Number(incomeRow?.total ?? 0),
      total_net_worth_usd: totalNetWorthUsd(currencies),
      expense_by_category: expenseByCategory,
      currencies,
      transactions,
    });
  },

  get_currencies(db, body) {
    const user = getUserFromToken(db, body.token);
    const currencies = listUserCurrencies(db, user.id);
    return ok({
      currencies,
      total_net_worth_usd: totalNetWorthUsd(currencies),
    });
  },

  update_currency_balance(db, body) {
    const user = getUserFromToken(db, body.token);
    requireFields(body, ['currency_code', 'amount']);
    const code = String(body.currency_code).toUpperCase();
    const amount = Number(body.amount);
    if (Number.isNaN(amount)) throw fail('amount must be a number');
    const wallet = adjustWallet(db, user.id, code, amount);
    return ok({ message: 'Balance updated', currency_code: code, wallet });
  },

  get_categories(db, body) {
    getUserFromToken(db, body.token);
    const recordType = body.record_type;
    let categories;
    if (recordType) {
      categories = db
        .prepare(
          `SELECT id AS category_id, category_name, record_type, color, display_order
           FROM categories WHERE record_type = ? ORDER BY display_order`
        )
        .all(String(recordType));
    } else {
      categories = db
        .prepare(
          `SELECT id AS category_id, category_name, record_type, color, display_order
           FROM categories ORDER BY record_type, display_order`
        )
        .all();
    }
    return ok({ categories });
  },

  add_category(db, body) {
    getUserFromToken(db, body.token);
    requireFields(body, ['category_name', 'record_type']);
    const recordType = String(body.record_type);
    if (!['expense', 'income'].includes(recordType)) {
      throw fail('record_type must be expense or income');
    }
    const maxOrder = db
      .prepare('SELECT COALESCE(MAX(display_order), 0) AS m FROM categories WHERE record_type = ?')
      .get(recordType);
    const result = db
      .prepare(
        'INSERT INTO categories (category_name, record_type, color, display_order) VALUES (?, ?, ?, ?)'
      )
      .run(
        String(body.category_name).trim(),
        recordType,
        body.color || '#6dbf8c',
        (maxOrder?.m ?? 0) + 1
      );
    return ok({ category_id: result.lastInsertRowid, message: 'Category added' });
  },

  delete_category(db, body) {
    getUserFromToken(db, body.token);
    requireFields(body, ['category_id']);
    const categoryId = Number(body.category_id);
    const used = db.prepare('SELECT id FROM expenses WHERE category_id = ? LIMIT 1').get(categoryId);
    if (used) throw fail('Category is used by expenses and cannot be deleted');
    const result = db.prepare('DELETE FROM categories WHERE id = ?').run(categoryId);
    if (result.changes === 0) throw fail('Category not found');
    return ok({ message: 'Category deleted' });
  },

  get_payment_methods(db, body) {
    const user = getUserFromToken(db, body.token);
    const payment_methods = db
      .prepare(
        `SELECT id AS method_id, user_id, method_name
         FROM payment_methods
         WHERE user_id IS NULL OR user_id = ?
         ORDER BY id`
      )
      .all(user.id);
    return ok({ payment_methods });
  },

  add_expense(db, body) {
    const user = getUserFromToken(db, body.token);
    const amount = Number(body.amount);
    let categoryId = Number(body.category_id ?? body.categoryId ?? 0);
    const categoryName = String(body.category_name ?? body.categoryName ?? '').trim();
    let paymentMethodId = Number(body.payment_method_id ?? body.paymentMethodId ?? 1);
    const currency = (body.currency_code || 'USD').toString().toUpperCase();
    const note = body.note ? String(body.note) : '';

    if (Number.isNaN(amount) || amount <= 0) {
      throw fail('Please enter a valid expense amount');
    }

    if (categoryId <= 0 && categoryName) {
      const row = db
        .prepare(
          "SELECT id FROM categories WHERE record_type = 'expense' AND category_name = ? LIMIT 1"
        )
        .get(categoryName);
      if (row) categoryId = row.id;
    }

    if (categoryId <= 0) {
      throw fail(
        'A valid expense category is required. Call get_categories with record_type "expense" and send category_id (or category_name).'
      );
    }

    const category = db
      .prepare("SELECT id FROM categories WHERE id = ? AND record_type = 'expense'")
      .get(categoryId);
    if (!category) {
      const expense_categories = db
        .prepare(
          "SELECT id AS category_id, category_name FROM categories WHERE record_type = 'expense' ORDER BY display_order LIMIT 30"
        )
        .all();
      return {
        status: 'error',
        message:
          'Invalid category_id for an expense. Ids are not always 1 — call get_categories with record_type "expense" and use a category_id from the response (or send category_name).',
        received_category_id: categoryId,
        expense_categories,
      };
    }

    let payment = db
      .prepare('SELECT id FROM payment_methods WHERE id = ? AND (user_id IS NULL OR user_id = ?)')
      .get(paymentMethodId, user.id);
    if (!payment) {
      payment = db
        .prepare('SELECT id FROM payment_methods WHERE user_id IS NULL OR user_id = ? ORDER BY id LIMIT 1')
        .get(user.id);
      if (!payment) throw fail('No payment methods available. Seed Payment_methods or call get_payment_methods.');
      paymentMethodId = payment.id;
    }

    const now = new Date().toISOString().slice(0, 19).replace('T', ' ');
    const result = db
      .prepare(
        `INSERT INTO expenses (user_id, amount, date, currency_code, category_id, payment_method_id, note)
         VALUES (?, ?, ?, ?, ?, ?, ?)`
      )
      .run(user.id, amount, now, currency, categoryId, paymentMethodId, note);

    adjustWallet(db, user.id, currency, -amount);

    return ok({
      message: 'Expense added successfully',
      expense_id: result.lastInsertRowid,
    });
  },

  add_income(db, body) {
    const user = getUserFromToken(db, body.token);
    const amount = Number(body.amount);
    const currency = (body.currency_code || 'USD').toString().toUpperCase();
    const note = body.note ? String(body.note) : '';

    if (Number.isNaN(amount) || amount <= 0) {
      throw fail('Please enter a valid income amount');
    }

    const now = new Date().toISOString().slice(0, 19).replace('T', ' ');
    const result = db
      .prepare('INSERT INTO incomes (user_id, amount, date, currency_code, note) VALUES (?, ?, ?, ?, ?)')
      .run(user.id, amount, now, currency, note);

    adjustWallet(db, user.id, currency, amount);

    return ok({
      message: 'Income added successfully',
      income_id: result.lastInsertRowid,
    });
  },

  transfer(db, body) {
    const user = getUserFromToken(db, body.token);
    requireFields(body, ['amount', 'from_currency', 'to_currency']);
    const amount = Number(body.amount);
    const exchangeRate = Number(body.exchange_rate ?? 1);
    if (Number.isNaN(amount) || amount <= 0) throw fail('Please enter a valid transfer amount');
    if (Number.isNaN(exchangeRate) || exchangeRate <= 0) throw fail('exchange_rate must be positive');

    const from = String(body.from_currency).toUpperCase();
    const to = String(body.to_currency).toUpperCase();
    const note = body.note ? String(body.note) : '';

    if (getWallet(db, user.id, from) < amount) {
      throw fail('Insufficient balance in source currency');
    }

    const converted = amount * exchangeRate;
    const now = new Date().toISOString().slice(0, 19).replace('T', ' ');
    adjustWallet(db, user.id, from, -amount);
    adjustWallet(db, user.id, to, converted);

    const result = db
      .prepare(
        `INSERT INTO transfers (user_id, amount, from_currency, to_currency, exchange_rate, note, date)
         VALUES (?, ?, ?, ?, ?, ?, ?)`
      )
      .run(user.id, amount, from, to, exchangeRate, note, now);

    return ok({
      message: 'Transfer completed',
      transfer_id: result.lastInsertRowid,
      converted_amount: converted,
    });
  },

  delete_transaction(db, body) {
    const user = getUserFromToken(db, body.token);
    requireFields(body, ['record_type', 'record_id']);
    const recordType = String(body.record_type);
    const recordId = Number(body.record_id);

    if (recordType === 'expense') {
      const row = db
        .prepare('SELECT amount, currency_code FROM expenses WHERE id = ? AND user_id = ?')
        .get(recordId, user.id);
      if (!row) throw fail('Transaction not found');
      db.prepare('DELETE FROM expenses WHERE id = ?').run(recordId);
      adjustWallet(db, user.id, row.currency_code, row.amount);
    } else if (recordType === 'income') {
      const row = db
        .prepare('SELECT amount, currency_code FROM incomes WHERE id = ? AND user_id = ?')
        .get(recordId, user.id);
      if (!row) throw fail('Transaction not found');
      db.prepare('DELETE FROM incomes WHERE id = ?').run(recordId);
      adjustWallet(db, user.id, row.currency_code, -row.amount);
    } else {
      throw fail('record_type must be expense or income');
    }
    return ok({ message: 'Transaction deleted' });
  },

  get_profile(db, body) {
    const user = getUserFromToken(db, body.token);
    const currencies = listUserCurrencies(db, user.id);
    return ok({
      user_id: user.id,
      username: user.username,
      email: user.email,
      currencies,
    });
  },

  update_profile(db, body) {
    const user = getUserFromToken(db, body.token);
    const username = body.username ? String(body.username).trim() : user.username;
    const email = body.email ? String(body.email).trim() : user.email;
    if (db.prepare('SELECT id FROM users WHERE username = ? COLLATE NOCASE AND id != ?').get(username, user.id)) {
      throw fail('Username already taken');
    }
    db.prepare('UPDATE users SET username = ?, email = ? WHERE id = ?').run(username, email, user.id);
    return ok({ message: 'Profile updated', user_id: user.id, username, email });
  },

  change_password(db, body) {
    const userRow = getUserFromToken(db, body.token);
    requireFields(body, ['current_password', 'new_password', 'confirm_password']);
    const current = String(body.current_password);
    const next = String(body.new_password);
    const confirm = String(body.confirm_password);
    if (next !== confirm) throw fail('New passwords do not match');
    if (next.length < 6) throw fail('New password must be at least 6 characters');
    const stored = db.prepare('SELECT password_hash FROM users WHERE id = ?').get(userRow.id);
    if (!verifyPassword(current, stored.password_hash)) {
      throw fail('Current password is incorrect');
    }
    db.prepare('UPDATE users SET password_hash = ? WHERE id = ?').run(hashPassword(next), userRow.id);
    return ok({ message: 'Password changed successfully' });
  },

  export_transactions(db, body) {
    const user = getUserFromToken(db, body.token);
    const expenses = db
      .prepare(
        `SELECT e.id, e.amount, e.currency_code, e.note, e.date, c.category_name, p.method_name AS payment_method
         FROM expenses e
         JOIN categories c ON c.id = e.category_id
         JOIN payment_methods p ON p.id = e.payment_method_id
         WHERE e.user_id = ? ORDER BY e.date DESC`
      )
      .all(user.id);
    const incomes = db
      .prepare('SELECT id, amount, currency_code, note, date FROM incomes WHERE user_id = ? ORDER BY date DESC')
      .all(user.id);
    const transfers = db
      .prepare(
        'SELECT id, amount, from_currency, to_currency, exchange_rate, note, date FROM transfers WHERE user_id = ? ORDER BY date DESC'
      )
      .all(user.id);
    return ok({ exported_at: new Date().toISOString(), expenses, incomes, transfers });
  },
};

function handleAction(db, action, body) {
  const handler = handlers[action];
  if (!handler) {
    throw fail(`Invalid action`);
  }
  return handler(db, body || {});
}

module.exports = { handleAction, fail };
