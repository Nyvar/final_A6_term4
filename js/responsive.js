// script.js - Complete JavaScript for Monefy App

// Calculator variables
let calcExpression = '0';
let calcWaitingForOperand = false;

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
    document.getElementById('calcValue').textContent = calcExpression;
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

// Dropdown menu
function toggleDropdown() {
    const menu = document.getElementById('dropdownMenu');
    menu.classList.toggle('open');
    
    setTimeout(() => {
        document.addEventListener('click', function closeDropdown(e) {
            if (!menu.contains(e.target) && !e.target.closest('.topbar-left')) {
                menu.classList.remove('open');
                document.removeEventListener('click', closeDropdown);
            }
        });
    }, 10);
}

// Sidebar functions
function openSidebar() {
    document.getElementById('rightSidebar').classList.add('open');
    document.getElementById('sidebarOverlay').classList.add('open');
}

function closeSidebar() {
    document.getElementById('rightSidebar').classList.remove('open');
    document.getElementById('sidebarOverlay').classList.remove('open');
}

// Modal functions
function openExpenseModal(catId, catName) {
    if (catId) {
        document.getElementById('expenseCategoryId').value = catId;
        document.querySelectorAll('#expenseCategoryGrid .category-option').forEach(opt => {
            opt.classList.toggle('selected', opt.dataset.catId == catId);
        });
    }
    
    const amount = evaluateExpression();
    if (amount && amount > 0) {
        document.getElementById('expenseAmount').value = amount.toFixed(2);
    } else {
        document.getElementById('expenseAmount').value = '';
    }
    
    document.getElementById('expenseNote').value = '';
    document.getElementById('expenseModal').classList.add('open');
}

function openIncomeModal() {
    const amount = evaluateExpression();
    if (amount && amount > 0) {
        document.getElementById('incomeAmount').value = amount.toFixed(2);
    } else {
        document.getElementById('incomeAmount').value = '';
    }
    document.getElementById('incomeNote').value = '';
    document.getElementById('incomeModal').classList.add('open');
}

function openTransferModal() {
    document.getElementById('transferModal').classList.add('open');
    document.getElementById('transferAmount').value = '';
    document.getElementById('transferNote').value = '';
}

function closeModal(modalId) {
    document.getElementById(modalId).classList.remove('open');
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
    closeSidebar();
}

// Form submission functions
function addExpense() {
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
    closeSidebar();
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
    if (key >= '0' && key <= '9') {
        calculatorInput(key);
    } else if (key === '.') {
        calculatorInput('.');
    } else if (key === '+' || key === '-' || key === '*' || key === '/') {
        calculatorInput(key);
    } else if (key === 'Enter' || key === '=') {
        evaluateExpression();
        if (!document.getElementById('expenseModal').classList.contains('open')) {
            openExpenseModal();
        }
    } else if (key === 'Escape') {
        document.querySelectorAll('.modal-overlay.open').forEach(modal => {
            modal.classList.remove('open');
        });
        document.getElementById('dropdownMenu').classList.remove('open');
        closeSidebar();
        closeHistory();
    } else if (key === 'c' || key === 'C') {
        calculatorClear();
    }
});

// Close dropdown when clicking outside
document.addEventListener('click', function(e) {
    const menu = document.getElementById('dropdownMenu');
    if (menu && menu.classList.contains('open') && 
        !menu.contains(e.target) && 
        !e.target.closest('.topbar-left')) {
        menu.classList.remove('open');
    }
});

// Auto-focus amount input when modal opens
const observer = new MutationObserver(function(mutations) {
    mutations.forEach(function(mutation) {
        if (mutation.attributeName === 'class') {
            const modal = mutation.target;
            if (modal.classList.contains('open')) {
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