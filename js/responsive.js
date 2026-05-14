// script.js - Complete JavaScript for Monefy App

// Calculator variables
let calcExpression = '0';
let calcWaitingForOperand = false;

// Separate calculator state inside add expense / add income modals (same behavior as main keypad)
const modalCalcState = {
    expense: { expr: '0', waiting: false },
    income: { expr: '0', waiting: false }
};

function parseCalcExpression(expr) {
    try {
        const e = String(expr).replace(/×/g, '*').replace(/÷/g, '/');
        const result = eval(e);
        if (isNaN(result) || !isFinite(result)) {
            return null;
        }
        return parseFloat(result);
    } catch (err) {
        return null;
    }
}

function modalCalcDisplayId(which) {
    return which === 'expense' ? 'expenseModalCalcValue' : 'incomeModalCalcValue';
}

function updateModalCalcDisplay(which) {
    const el = document.getElementById(modalCalcDisplayId(which));
    if (el) {
        el.textContent = modalCalcState[which].expr;
    }
}

function modalCalcInput(which, value) {
    const s = modalCalcState[which];
    if (s.waiting) {
        s.expr = value;
        s.waiting = false;
    } else {
        if (s.expr === '0' && value !== '.') {
            s.expr = value;
        } else {
            s.expr += value;
        }
    }
    updateModalCalcDisplay(which);
}

function modalCalcClear(which) {
    const s = modalCalcState[which];
    s.expr = '0';
    s.waiting = false;
    updateModalCalcDisplay(which);
}

function modalCalcEquals(which) {
    const s = modalCalcState[which];
    const result = parseCalcExpression(s.expr);
    if (result === null) {
        return null;
    }
    s.expr = result.toString();
    s.waiting = true;
    updateModalCalcDisplay(which);
    const inputId = which === 'expense' ? 'expenseAmount' : 'incomeAmount';
    const inp = document.getElementById(inputId);
    if (inp) {
        inp.value = result.toFixed(2);
    }
    return result;
}

function initModalCalcFromInput(which) {
    const s = modalCalcState[which];
    const inputId = which === 'expense' ? 'expenseAmount' : 'incomeAmount';
    const inp = document.getElementById(inputId);
    const raw = inp && inp.value !== '' ? parseFloat(inp.value) : NaN;
    if (!isNaN(raw) && raw > 0) {
        s.expr = raw.toString();
        s.waiting = true;
    } else {
        s.expr = '0';
        s.waiting = false;
    }
    updateModalCalcDisplay(which);
}

function ensureModalAmountCommitted(which) {
    const s = modalCalcState[which];
    const v = parseCalcExpression(s.expr);
    if (v === null || v <= 0) {
        return;
    }
    const inputId = which === 'expense' ? 'expenseAmount' : 'incomeAmount';
    const inp = document.getElementById(inputId);
    if (inp) {
        inp.value = v.toFixed(2);
    }
    s.expr = v.toString();
    s.waiting = true;
    updateModalCalcDisplay(which);
}

function resetModalCalculator(which) {
    modalCalcState[which].expr = '0';
    modalCalcState[which].waiting = false;
    updateModalCalcDisplay(which);
}

// Calculator functions
function calculatorInput(value) {
    if (calcWaitingForOperand) {
        calcExpression = value;
        calcWaitingForOperand = false;
    } else {
        if (calcExpression === '0' && value !== '.') {
            calcExpression = value;
        } else {
            calcExpression += value;
        }
    }
    updateCalcDisplay();
}

function calculatorClear() {
    calcExpression = '0';
    calcWaitingForOperand = false;
    updateCalcDisplay();
}

function updateCalcDisplay() {
    const el = document.getElementById('calcValue');
    if (el) {
        el.textContent = calcExpression;
    }
}

function evaluateExpression() {
    try {
        let expr = calcExpression.replace(/×/g, '*').replace(/÷/g, '/');
        let result = eval(expr);
        if (isNaN(result) || !isFinite(result)) {
            return null;
        }
        calcExpression = result.toString();
        calcWaitingForOperand = true;
        updateCalcDisplay();
        return parseFloat(result);
    } catch(e) {
        return null;
    }
}

// Legacy: old topbar dropdown (optional)
function toggleDropdown() {
    const menu = document.getElementById('dropdownMenu');
    if (menu) toggleLeftDrawer();
}

function toggleLeftDrawer() {
    const drawer = document.getElementById('leftDrawer');
    const backdrop = document.getElementById('leftDrawerBackdrop');
    if (!drawer || !backdrop) return;
    closeRightNavDropdown();
    const open = drawer.classList.toggle('open');
    backdrop.classList.toggle('open', open);
    drawer.setAttribute('aria-hidden', open ? 'false' : 'true');
}

function closeLeftDrawer() {
    const drawer = document.getElementById('leftDrawer');
    const backdrop = document.getElementById('leftDrawerBackdrop');
    if (drawer) {
        drawer.classList.remove('open');
        drawer.setAttribute('aria-hidden', 'true');
    }
    if (backdrop) backdrop.classList.remove('open');
}

function toggleRightNavDropdown() {
    const panel = document.getElementById('rightNavDropdown');
    const backdrop = document.getElementById('rightNavBackdrop');
    if (!panel || !backdrop) return;
    closeLeftDrawer();
    const open = panel.classList.toggle('open');
    backdrop.classList.toggle('open', open);
    panel.setAttribute('aria-hidden', open ? 'false' : 'true');
}

function closeRightNavDropdown() {
    const panel = document.getElementById('rightNavDropdown');
    const backdrop = document.getElementById('rightNavBackdrop');
    if (panel) {
        panel.classList.remove('open');
        panel.setAttribute('aria-hidden', 'true');
    }
    if (backdrop) backdrop.classList.remove('open');
}

function openSidebar() {
    toggleRightNavDropdown();
}

function closeSidebar() {
    closeRightNavDropdown();
    closeLeftDrawer();
}

// Modal functions
function openExpenseModal(catId, catName) {
    if (catId) {
        document.getElementById('expenseCategoryId').value = catId;
        document.querySelectorAll('#expenseCategoryGrid .category-option').forEach(opt => {
            opt.classList.toggle('selected', opt.dataset.catId == catId);
        });
    } else {
        const first = document.querySelector('#expenseCategoryGrid .category-option.selected')
            || document.querySelector('#expenseCategoryGrid .category-option');
        if (first) {
            selectCategory(first);
        }
    }
    
    document.getElementById('expenseAmount').value = '';
    document.getElementById('expenseNote').value = '';
    initModalCalcFromInput('expense');
    document.getElementById('expenseModal').classList.add('open');
}

function openIncomeModal(catId, catName) {
    document.getElementById('incomeAmount').value = '';
    if (arguments.length >= 2 && catName !== undefined && catName !== null && catName !== '') {
        document.getElementById('incomeNote').value = String(catName);
    } else {
        document.getElementById('incomeNote').value = '';
    }
    initModalCalcFromInput('income');
    document.getElementById('incomeModal').classList.add('open');
}

function openTransferModal() {
    closeRightNavDropdown();
    closeLeftDrawer();
    document.getElementById('transferModal').classList.add('open');
    document.getElementById('transferAmount').value = '';
    document.getElementById('transferNote').value = '';
}

function closeModal(modalId) {
    document.getElementById(modalId).classList.remove('open');
    if (modalId === 'expenseModal') {
        resetModalCalculator('expense');
    }
    if (modalId === 'incomeModal') {
        resetModalCalculator('income');
    }
}

function selectCategory(element) {
    document.querySelectorAll('#expenseCategoryGrid .category-option').forEach(opt => {
        opt.classList.remove('selected');
    });
    element.classList.add('selected');
    document.getElementById('expenseCategoryId').value = element.dataset.catId;
}

// Transfer account selection
let transferFromCurrency = 'USD';
let transferToCurrency = 'KHR';

function selectTransferAccount(type, currency) {
    if (type === 'from') {
        transferFromCurrency = currency;
        document.querySelectorAll('[data-account-type="from"]').forEach(el => {
            el.classList.toggle('selected', el.dataset.currency === currency);
        });
    } else {
        transferToCurrency = currency;
        document.querySelectorAll('[data-account-type="to"]').forEach(el => {
            el.classList.toggle('selected', el.dataset.currency === currency);
        });
    }
    
    if (exchangeRates) {
        const fromRate = exchangeRates[transferFromCurrency] || 1;
        const toRate = exchangeRates[transferToCurrency] || 1;
        const rate = toRate / fromRate;
        document.getElementById('exchangeRate').value = rate.toFixed(6);
    }
}

// Date filter functions
function setDateFilter(filter) {
    const url = new URL(window.location.href);
    url.searchParams.set('date_filter', filter);
    url.searchParams.delete('start_date');
    url.searchParams.delete('end_date');
    window.location.href = url.toString();
}

function openDateRangeModal() {
    closeRightNavDropdown();
    closeLeftDrawer();
    document.getElementById('dateRangeModal').classList.add('open');
}

function applyDateRangeFilter() {
    const startDate = document.getElementById('rangeStartDate').value;
    const endDate = document.getElementById('rangeEndDate').value;
    
    if (startDate && endDate) {
        const url = new URL(window.location.href);
        url.searchParams.set('date_filter', 'interval');
        url.searchParams.set('start_date', startDate);
        url.searchParams.set('end_date', endDate);
        window.location.href = url.toString();
    } else {
        alert('Please select both start and end dates');
    }
}

// Show Transit function for dropdown
function showTransit() {
    const url = new URL(window.location.href);
    url.searchParams.set('type_filter', 'transfer');
    window.location.href = url.toString();
    closeRightNavDropdown();
    closeLeftDrawer();
}

// Form submission functions
function addExpense() {
    ensureModalAmountCommitted('expense');
    const amount = document.getElementById('expenseAmount').value;
    const categoryId = document.getElementById('expenseCategoryId').value;
    const paymentMethodId = document.getElementById('paymentMethodId').value;
    const note = document.getElementById('expenseNote').value;
    
    if (!amount || parseFloat(amount) <= 0) {
        alert('Please enter a valid amount');
        document.getElementById('expenseAmount').focus();
        return;
    }
    
    const form = document.createElement('form');
    form.method = 'POST';
    form.style.display = 'none';
    form.innerHTML = `
        <input type="hidden" name="action" value="add_expense">
        <input type="hidden" name="amount" value="${parseFloat(amount).toFixed(2)}">
        <input type="hidden" name="category_id" value="${categoryId}">
        <input type="hidden" name="payment_method_id" value="${paymentMethodId}">
        <input type="hidden" name="note" value="${escapeHtml(note)}">
    `;
    document.body.appendChild(form);
    form.submit();
}

function addIncome() {
    ensureModalAmountCommitted('income');
    const amount = document.getElementById('incomeAmount').value;
    const note = document.getElementById('incomeNote').value;
    
    if (!amount || parseFloat(amount) <= 0) {
        alert('Please enter a valid amount');
        document.getElementById('incomeAmount').focus();
        return;
    }
    
    const form = document.createElement('form');
    form.method = 'POST';
    form.style.display = 'none';
    form.innerHTML = `
        <input type="hidden" name="action" value="add_income">
        <input type="hidden" name="amount" value="${parseFloat(amount).toFixed(2)}">
        <input type="hidden" name="note" value="${escapeHtml(note)}">
    `;
    document.body.appendChild(form);
    form.submit();
}

function processTransfer() {
    const amount = document.getElementById('transferAmount').value;
    const exchangeRate = document.getElementById('exchangeRate').value;
    const note = document.getElementById('transferNote').value;
    
    if (!amount || parseFloat(amount) <= 0) {
        alert('Please enter a valid amount');
        document.getElementById('transferAmount').focus();
        return;
    }
    
    const form = document.createElement('form');
    form.method = 'POST';
    form.style.display = 'none';
    form.innerHTML = `
        <input type="hidden" name="action" value="transfer">
        <input type="hidden" name="amount" value="${parseFloat(amount).toFixed(2)}">
        <input type="hidden" name="from_currency" value="${transferFromCurrency}">
        <input type="hidden" name="to_currency" value="${transferToCurrency}">
        <input type="hidden" name="exchange_rate" value="${exchangeRate}">
        <input type="hidden" name="note" value="${escapeHtml(note)}">
    `;
    document.body.appendChild(form);
    form.submit();
}

function changeCurrency(currency) {
    const url = new URL(window.location.href);
    url.searchParams.set('currency', currency);
    window.location.href = url.toString();
}

function changeCurrencyPrompt() {
    const newCurrency = prompt('Enter currency code (USD, KHR, EUR, GBP):', activeCurrency);
    if (newCurrency && ['USD', 'KHR', 'EUR', 'GBP'].includes(newCurrency.toUpperCase())) {
        changeCurrency(newCurrency.toUpperCase());
    } else if (newCurrency) {
        alert('Please enter a valid currency: USD, KHR, EUR, or GBP');
    }
    closeSidebar();
}

// History functions
function openHistory() {
    document.getElementById('historyPanel').classList.add('open');
    closeRightNavDropdown();
    closeLeftDrawer();
}

function closeHistory() {
    document.getElementById('historyPanel').classList.remove('open');
}

// Helper function to escape HTML
function escapeHtml(text) {
    if (!text) return '';
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

// Initialize date range picker values
if (typeof startDateParam !== 'undefined' && startDateParam) {
    const startInput = document.getElementById('rangeStartDate');
    if (startInput) startInput.value = startDateParam;
}
if (typeof endDateParam !== 'undefined' && endDateParam) {
    const endInput = document.getElementById('rangeEndDate');
    if (endInput) endInput.value = endDateParam;
}

// Keyboard support for calculator
document.addEventListener('keydown', function(e) {
    const key = e.key;
    const expModal = document.getElementById('expenseModal');
    const incModal = document.getElementById('incomeModal');
    const expOpen = expModal && expModal.classList.contains('open');
    const incOpen = incModal && incModal.classList.contains('open');
    const t = e.target;
    const passThroughInput = t && t.tagName === 'INPUT' && (
        ['text', 'search', 'number', 'email', 'tel', 'password'].includes(t.type)
    );
    const passThroughTextArea = t && t.tagName === 'TEXTAREA';

    if ((expOpen || incOpen) && !passThroughInput && !passThroughTextArea) {
        const which = incOpen ? 'income' : 'expense';
        if (key >= '0' && key <= '9') {
            e.preventDefault();
            modalCalcInput(which, key);
            return;
        }
        if (key === '.') {
            e.preventDefault();
            modalCalcInput(which, '.');
            return;
        }
        if (key === '+' || key === '-' || key === '*' || key === '/') {
            e.preventDefault();
            modalCalcInput(which, key);
            return;
        }
        if (key === 'c' || key === 'C') {
            e.preventDefault();
            modalCalcClear(which);
            return;
        }
    }

    const hasMainCalc = !!document.getElementById('calcValue');

    if (key >= '0' && key <= '9') {
        if (passThroughInput || passThroughTextArea) {
            return;
        }
        if (hasMainCalc) {
            calculatorInput(key);
        }
    } else if (key === '.') {
        if (passThroughInput || passThroughTextArea) {
            return;
        }
        if (hasMainCalc) {
            calculatorInput('.');
        }
    } else if (key === '+' || key === '-' || key === '*' || key === '/') {
        if (passThroughInput || passThroughTextArea) {
            return;
        }
        if (hasMainCalc) {
            calculatorInput(key);
        }
    } else if (key === 'Enter' || key === '=') {
        if (incOpen) {
            modalCalcEquals('income');
        } else if (expOpen) {
            modalCalcEquals('expense');
        } else if (hasMainCalc) {
            evaluateExpression();
            openExpenseModal();
        } else {
            openExpenseModal();
        }
    } else if (key === 'Escape') {
        document.querySelectorAll('.modal-overlay.open').forEach(modal => {
            modal.classList.remove('open');
            if (modal.id === 'expenseModal') {
                resetModalCalculator('expense');
            }
            if (modal.id === 'incomeModal') {
                resetModalCalculator('income');
            }
        });
        const dm = document.getElementById('dropdownMenu');
        if (dm) dm.classList.remove('open');
        closeRightNavDropdown();
        closeLeftDrawer();
        closeHistory();
    } else if (key === 'c' || key === 'C') {
        if (passThroughInput || passThroughTextArea) {
            return;
        }
        if (!expOpen && !incOpen && hasMainCalc) {
            calculatorClear();
        }
    }
});

// Close menus when clicking outside
document.addEventListener('click', function(e) {
    const menu = document.getElementById('dropdownMenu');
    if (menu && menu.classList.contains('open') &&
        !menu.contains(e.target) &&
        !e.target.closest('.topbar-left')) {
        menu.classList.remove('open');
    }
    const rightNav = document.getElementById('rightNavDropdown');
    if (rightNav && rightNav.classList.contains('open') &&
        !rightNav.contains(e.target) &&
        !e.target.closest('.topbar-right-menu-trigger')) {
        closeRightNavDropdown();
    }
    const leftDr = document.getElementById('leftDrawer');
    if (leftDr && leftDr.classList.contains('open') &&
        !leftDr.contains(e.target) &&
        !e.target.closest('.topbar-left-menu-trigger') &&
        !e.target.closest('.topbar-user')) {
        closeLeftDrawer();
    }
});

// Auto-focus amount input when modal opens
const observer = new MutationObserver(function(mutations) {
    mutations.forEach(function(mutation) {
        if (mutation.attributeName === 'class') {
            const modal = mutation.target;
            if (modal.classList.contains('open')) {
                if (modal.id === 'expenseModal' || modal.id === 'incomeModal') {
                    return;
                }
                const amountInput = modal.querySelector('input[type="number"]');
                if (amountInput) {
                    setTimeout(() => amountInput.focus(), 100);
                }
            }
        }
    });
});

document.querySelectorAll('.modal-overlay').forEach(modal => {
    observer.observe(modal, { attributes: true });
});