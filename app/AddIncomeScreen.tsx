// app/AddIncomeScreen.tsx
import { Ionicons } from "@expo/vector-icons";
import React, { useState, useEffect } from "react";
import {
  StyleSheet,
  Text,
  TextInput,
  TouchableOpacity,
  View,
  ScrollView,
  Alert,
  ActivityIndicator,
} from "react-native";
import { SafeAreaView } from "react-native-safe-area-context";
import { router } from 'expo-router';
import { getCategories, getPaymentMethods, getCurrencies, addExpense, addIncome, addCategory } from '@/services/api';

interface Category {
  id: number;
  category_id?: number;
  name: string;
  category_name?: string;
  color?: string;
  record_type?: string;
}

interface PaymentMethod {
  method_id: number;
  method_name: string;
  icon?: string;
}

interface Currency {
  code: string;
  name: string;
  symbol?: string;
  wallet?: string;
}

export default function AddIncomeScreen() {
  const [amount, setAmount] = useState("");
  const [note, setNote] = useState("");
  const [showCategories, setShowCategories] = useState(false);
  const [category, setCategory] = useState<Category | null>(null);
  const [paymentMethod, setPaymentMethod] = useState<PaymentMethod | null>(null);
  const [showPaymentOptions, setShowPaymentOptions] = useState(false);
  const [showCurrencyOptions, setShowCurrencyOptions] = useState(false);
  
  const [categories, setCategories] = useState<Category[]>([]);
  const [paymentMethods, setPaymentMethods] = useState<PaymentMethod[]>([]);
  const [currencies, setCurrencies] = useState<Currency[]>([]);
  const [selectedCurrency, setSelectedCurrency] = useState<Currency>({ code: "USD", name: "US Dollar", symbol: "$" });
  
  const [loading, setLoading] = useState(false);
  const [loadingData, setLoadingData] = useState(true);
  const [transactionType, setTransactionType] = useState<'income' | 'expense'>('expense');
  
  const [amountError, setAmountError] = useState(false);
  const [noteError, setNoteError] = useState(false);
  const [addingCategory, setAddingCategory] = useState(false);
  const [newCategoryName, setNewCategoryName] = useState("");
  const [newCategoryColor, setNewCategoryColor] = useState("#6dbf8c");
  const [paymentMethodError, setPaymentMethodError] = useState(false);

  useEffect(() => {
    loadInitialData();
  }, []);

  const loadInitialData = async () => {
    try {
      setLoadingData(true);
      const [categoriesRes, paymentMethodsRes, currenciesRes] = await Promise.all([
        getCategories('expense'),
        getPaymentMethods(),
        getCurrencies()
      ]);

      console.log('Categories response:', categoriesRes);
      console.log('Payment methods response:', paymentMethodsRes);
      console.log('Currencies response:', currenciesRes);

      // Handle categories response
      if (categoriesRes && categoriesRes.categories) {
        const normalizedCategories = categoriesRes.categories.map((cat: any) => ({
          id: cat.category_id,
          category_id: cat.category_id,
          name: cat.category_name,
          category_name: cat.category_name,
          color: cat.color || '#6dbf8c',
          record_type: cat.record_type || 'expense'
        }));
        setCategories(normalizedCategories);
      }

      // Handle payment methods response
      if (paymentMethodsRes && paymentMethodsRes.payment_methods) {
        setPaymentMethods(paymentMethodsRes.payment_methods);
      }

      // Handle currencies response
      if (currenciesRes && currenciesRes.currencies) {
        setCurrencies(currenciesRes.currencies);
        const usd = currenciesRes.currencies.find((c: Currency) => c.code === 'USD');
        if (usd) setSelectedCurrency(usd);
      }
    } catch (error) {
      console.error('Error loading data:', error);
      Alert.alert('Error', 'Failed to load data');
    } finally {
      setLoadingData(false);
    }
  };

  const handleKeyPress = (key: string) => {
    if (key === "C") {
      setAmount("");
    } else if (["+", "-", "*", "/"].includes(key)) {
      setAmount(amount + key);
    } else if (key === "=") {
      try {
        const result = Function('"use strict";return (' + amount + ')')();
        setAmount(result.toString());
      } catch {
        setAmount("Error");
      }
    } else {
      setAmount(amount + key);
    }
    if (amount !== "") setAmountError(false);
  };

  const keypad = [
    ["1", "2", "3", "+"],
    ["4", "5", "6", "-"],
    ["7", "8", "9", "*"],
    [".", "0", "=", "/"],
  ];

  const displayAmount = (() => {
    if (amount === "" || amount === "Error") return amount || "0";
    if (/[+\-*/]$/.test(amount)) {
      const parts = amount.split(/[+\-*/]/).filter(Boolean);
      return parts[parts.length - 1] || "0";
    }
    const parts = amount.split(/[+\-*/]/);
    return parts[parts.length - 1] || "0";
  })();

  const today = new Date();
  const options: Intl.DateTimeFormatOptions = {
    weekday: "long",
    day: "numeric",
    month: "long",
  };
  const formattedDate = today.toLocaleDateString("en-US", options);

  const addNewCategory = async () => {
    if (newCategoryName.trim() !== "") {
      try {
        const response = await addCategory(newCategoryName.trim(), 'expense', newCategoryColor);
        if (response && response.success) {
          const categoriesRes = await getCategories('expense');
          if (categoriesRes && categoriesRes.categories) {
            const normalizedCategories = categoriesRes.categories.map((cat: any) => ({
              id: cat.category_id,
              category_id: cat.category_id,
              name: cat.category_name,
              category_name: cat.category_name,
              color: cat.color || '#6dbf8c',
              record_type: cat.record_type || 'expense'
            }));
            setCategories(normalizedCategories);
          }
          setNewCategoryName("");
          setAddingCategory(false);
          Alert.alert('Success', 'Category added successfully');
        } else {
          Alert.alert('Error', response?.message || 'Failed to add category');
        }
      } catch (error) {
        console.error('Error adding category:', error);
        Alert.alert('Error', 'Network error');
      }
    }
  };

  const handleChooseCategory = () => {
    let hasError = false;

    if (!amount || amount === "0" || amount === "Error" || parseFloat(displayAmount) <= 0) {
      setAmountError(true);
      hasError = true;
    } else {
      setAmountError(false);
    }

    if (transactionType === 'expense' && !paymentMethod) {
      setPaymentMethodError(true);
      Alert.alert('Error', 'Please select a payment method');
      hasError = true;
    } else {
      setPaymentMethodError(false);
    }

    if (!hasError) {
      setShowCategories(true);
    }
  };

  const handleSelectCategory = async (cat: Category) => {
    setCategory(cat);
    setLoading(true);
    
    try {
      if (!paymentMethod) {
        Alert.alert('Error', 'Please select a payment method');
        setLoading(false);
        return;
      }

      const amountValue = parseFloat(displayAmount);
      if (isNaN(amountValue) || amountValue <= 0) {
        Alert.alert('Error', 'Please enter a valid amount');
        setLoading(false);
        return;
      }

      const categoryId = cat.id || cat.category_id;
      
      console.log('Adding expense:', {
        amount: amountValue,
        category_id: categoryId,
        payment_method_id: paymentMethod.method_id,
        currency_code: selectedCurrency.code,
        note: note || "Expense"
      });

      const response = await addExpense(
        amountValue,
        categoryId,
        paymentMethod.method_id,
        selectedCurrency.code,
        note || "Expense"
      );
      
      console.log('Add expense response:', response);
      
      if (response && response.success) {
        Alert.alert('Success', 'Expense added successfully');
        // Use replace to go back and trigger refresh on homepage
        router.replace('/homePage');
      } else {
        Alert.alert('Error', response?.message || 'Failed to add expense');
      }
    } catch (error: any) {
      console.error('Error adding expense:', error);
      Alert.alert('Error', error?.message || 'Network error. Please try again.');
    } finally {
      setLoading(false);
    }
  };

  const handleAddIncome = async () => {
    const amountValue = parseFloat(displayAmount);
    if (isNaN(amountValue) || amountValue <= 0) {
      Alert.alert('Error', 'Please enter a valid amount');
      return;
    }

    setLoading(true);
    try {
      console.log('Adding income:', {
        amount: amountValue,
        currency_code: selectedCurrency.code,
        note: note || "Income"
      });

      const response = await addIncome(
        amountValue,
        selectedCurrency.code,
        note || "Income"
      );

      console.log('Add income response:', response);
      
      if (response && response.success) {
        Alert.alert('Success', 'Income added successfully');
        // Use replace to go back and trigger refresh on homepage
        router.replace('/homePage');
      } else {
        Alert.alert('Error', response?.message || 'Failed to add income');
      }
    } catch (error: any) {
      console.error('Error adding income:', error);
      Alert.alert('Error', error?.message || 'Network error. Please try again.');
    } finally {
      setLoading(false);
    }
  };

  if (loadingData) {
    return (
      <SafeAreaView style={styles.container}>
        <View style={styles.loadingContainer}>
          <ActivityIndicator size="large" color="#51d191" />
          <Text style={styles.loadingText}>Loading...</Text>
        </View>
      </SafeAreaView>
    );
  }

  return (
    <SafeAreaView style={styles.container} edges={['top']}>
      <ScrollView 
        showsVerticalScrollIndicator={false}
        keyboardShouldPersistTaps="handled"
      >
        <View style={styles.header}>
          <TouchableOpacity style={styles.cancelButton} onPress={() => router.replace('/homePage')}>
            <Text style={styles.cancelText}>Cancel</Text>
          </TouchableOpacity>
          <Text style={styles.headerTitle}>New transaction</Text>
          <View style={styles.placeholder} />
        </View>

        <View style={styles.typeSelector}>
          <TouchableOpacity 
            style={[styles.typeButton, transactionType === 'income' && styles.typeButtonActive]}
            onPress={() => {
              setTransactionType('income');
              setShowCategories(false);
              setPaymentMethod(null);
            }}
          >
            <Text style={[styles.typeText, transactionType === 'income' && styles.typeTextActive]}>Income</Text>
          </TouchableOpacity>
          <TouchableOpacity 
            style={[styles.typeButton, transactionType === 'expense' && styles.typeButtonActive]}
            onPress={() => {
              setTransactionType('expense');
              setShowCategories(false);
            }}
          >
            <Text style={[styles.typeText, transactionType === 'expense' && styles.typeTextActive]}>Expense</Text>
          </TouchableOpacity>
        </View>

        <Text style={styles.date}>{formattedDate}</Text>

        <View style={styles.amountWrapper}>
          <TouchableOpacity 
            style={styles.currencySelectorMain}
            onPress={() => setShowCurrencyOptions(!showCurrencyOptions)}
          >
            <Text style={styles.currencyText}>{selectedCurrency.code}</Text>
            <Ionicons name="chevron-down" size={16} color="#51d191" />
          </TouchableOpacity>

          {showCurrencyOptions && (
            <View style={styles.currencyOptions}>
              {currencies.map((curr) => (
                <TouchableOpacity
                  key={curr.code}
                  style={styles.currencyOption}
                  onPress={() => {
                    setSelectedCurrency(curr);
                    setShowCurrencyOptions(false);
                  }}
                >
                  <Text style={styles.currencyOptionText}>{curr.code} - {curr.name}</Text>
                </TouchableOpacity>
              ))}
            </View>
          )}

          <View
            style={[
              styles.amountBox,
              amountError && { borderColor: "red", borderWidth: 2 },
            ]}
          >
            <Text style={styles.currencySymbol}>{selectedCurrency.symbol || selectedCurrency.code}</Text>
            <Text style={styles.amount}>{displayAmount}</Text>
            <TouchableOpacity onPress={() => setAmount(amount.slice(0, -1))}>
              <Ionicons name="backspace-outline" size={28} color="#fff" style={styles.backspace} />
            </TouchableOpacity>
          </View>
        </View>

        {/* Payment Method Selector - Only for Expense */}
        {transactionType === 'expense' && (
          <View style={styles.paymentSection}>
            <Text style={styles.paymentLabel}>Payment Method</Text>
            <TouchableOpacity 
              style={[styles.paymentSelectorButton, paymentMethodError && styles.paymentSelectorError]}
              onPress={() => setShowPaymentOptions(!showPaymentOptions)}
              activeOpacity={0.7}
            >
              <View style={styles.paymentSelectorContent}>
                {paymentMethod ? (
                  <>
                    <Ionicons 
                      name={paymentMethod.method_name === 'Cash' ? "cash-outline" : "card-outline"} 
                      size={24} 
                      color="#51d191" 
                    />
                    <View>
                      <Text style={styles.paymentSelectorTitle}>{paymentMethod.method_name}</Text>
                      <Text style={styles.paymentSelectorSubtitle}>Tap to change</Text>
                    </View>
                  </>
                ) : (
                  <>
                    <Ionicons name="card-outline" size={24} color="#999" />
                    <Text style={styles.paymentSelectorPlaceholder}>Select Payment Method</Text>
                  </>
                )}
                <Ionicons name={showPaymentOptions ? "chevron-up" : "chevron-down"} size={20} color="#666" />
              </View>
            </TouchableOpacity>

            {showPaymentOptions && (
              <View style={styles.paymentOptionsContainer}>
                {paymentMethods.map((method) => (
                  <TouchableOpacity
                    key={method.method_id}
                    style={[
                      styles.paymentOptionItem,
                      paymentMethod?.method_id === method.method_id && styles.paymentOptionSelected
                    ]}
                    onPress={() => {
                      setPaymentMethod(method);
                      setPaymentMethodError(false);
                      setShowPaymentOptions(false);
                    }}
                  >
                    <Ionicons 
                      name={method.method_name === 'Cash' ? "cash-outline" : "card-outline"} 
                      size={24} 
                      color={paymentMethod?.method_id === method.method_id ? "#51d191" : "#333"} 
                    />
                    <Text style={[
                      styles.paymentOptionItemText,
                      paymentMethod?.method_id === method.method_id && styles.paymentOptionSelectedText
                    ]}>
                      {method.method_name}
                    </Text>
                    {paymentMethod?.method_id === method.method_id && (
                      <Ionicons name="checkmark-circle" size={20} color="#51d191" />
                    )}
                  </TouchableOpacity>
                ))}
              </View>
            )}
          </View>
        )}

        <View style={styles.noteWrapper}>
          {!addingCategory ? (
            <View style={styles.noteContainer}>
              <TextInput
                style={[
                  styles.noteInput,
                  noteError && { borderColor: "red", borderWidth: 2 },
                ]}
                value={note}
                placeholder="Add note (optional)"
                placeholderTextColor="#999"
                onChangeText={(text) => {
                  setNote(text);
                  if (text !== "") setNoteError(false);
                }}
              />
            </View>
          ) : (
            <View style={styles.noteContainer}>
              <TextInput
                style={styles.noteInput}
                value={newCategoryName}
                onChangeText={setNewCategoryName}
                placeholder="Enter category name"
                placeholderTextColor="#999"
                onSubmitEditing={addNewCategory}
                autoFocus
              />
              <TouchableOpacity onPress={addNewCategory} style={styles.saveCategoryButton}>
                <Text style={styles.saveCategoryText}>Save</Text>
              </TouchableOpacity>
            </View>
          )}
        </View>

        {/* Keypad for Income */}
        {transactionType === 'income' && !showCategories && (
          <View style={styles.keypad}>
            {keypad.map((row, i) => (
              <View key={i} style={styles.keypadRow}>
                {row.map((key) => (
                  <TouchableOpacity key={key} style={styles.key} onPress={() => handleKeyPress(key)}>
                    <Text style={styles.keyText}>{key}</Text>
                  </TouchableOpacity>
                ))}
              </View>
            ))}
            <TouchableOpacity style={[styles.key, styles.submitKey]} onPress={handleAddIncome} disabled={loading}>
              {loading ? <ActivityIndicator size="small" color="#fff" /> : <Text style={styles.submitKeyText}>ADD INCOME</Text>}
            </TouchableOpacity>
          </View>
        )}

        {/* Category Selection for Expense */}
        {transactionType === 'expense' && showCategories && (
          <View style={styles.categorySection}>
            <Text style={styles.categoryTitle}>Select Category</Text>
            <View style={styles.categoryBox}>
              {categories.map((cat) => (
                <TouchableOpacity
                  key={cat.id}
                  style={[
                    styles.categoryOption,
                    category?.id === cat.id && styles.categorySelected,
                  ]}
                  onPress={() => handleSelectCategory(cat)}
                  disabled={loading}
                >
                  <View style={[styles.categoryColor, { backgroundColor: cat.color || '#6dbf8c' }]} />
                  <Text style={styles.categoryText}>{cat.name}</Text>
                </TouchableOpacity>
              ))}
              <TouchableOpacity style={styles.addCategoryButton} onPress={() => setAddingCategory(true)}>
                <Ionicons name="add-circle-outline" size={28} color="#51d191" />
                <Text style={styles.addCategoryText}>Add Category</Text>
              </TouchableOpacity>
            </View>
            {loading && (
              <View style={styles.loadingOverlay}>
                <ActivityIndicator size="large" color="#51d191" />
              </View>
            )}
          </View>
        )}

        {/* Add Expense Button */}
        {transactionType === 'expense' && !showCategories && (
          <View style={styles.categorySection}>
            <TouchableOpacity style={styles.addExpenseButton} onPress={handleChooseCategory}>
              <Text style={styles.addExpenseButtonText}>ADD EXPENSE</Text>
            </TouchableOpacity>
          </View>
        )}
      </ScrollView>
    </SafeAreaView>
  );
}

const styles = StyleSheet.create({
  container: { flex: 1, backgroundColor: "#e7f0e9" },
  loadingContainer: { flex: 1, justifyContent: 'center', alignItems: 'center' },
  loadingText: { marginTop: 10, color: '#666' },
  header: { flexDirection: "row", justifyContent: "space-between", alignItems: "center", paddingHorizontal: 20, paddingVertical: 15, borderBottomWidth: 1, borderBottomColor: "rgba(255,255,255,0.3)", backgroundColor: "#51d191" },
  cancelButton: { padding: 5 },
  cancelText: { fontSize: 17, color: "#fff" },
  headerTitle: { fontSize: 17, fontWeight: "600", color: "#fff" },
  placeholder: { width: 50 },
  typeSelector: { flexDirection: 'row', marginHorizontal: 20, marginTop: 20, backgroundColor: '#fff', borderRadius: 10, padding: 4 },
  typeButton: { flex: 1, paddingVertical: 10, alignItems: 'center', borderRadius: 8 },
  typeButtonActive: { backgroundColor: '#51d191' },
  typeText: { fontSize: 16, fontWeight: '600', color: '#666' },
  typeTextActive: { color: '#fff' },
  date: { fontSize: 15, color: "#666", textAlign: "center", marginTop: 20, marginBottom: 30 },
  amountWrapper: { paddingHorizontal: 20 },
  currencySelectorMain: { flexDirection: 'row', alignItems: 'center', justifyContent: 'flex-end', marginBottom: 10, gap: 5 },
  currencyText: { fontSize: 14, color: '#51d191', fontWeight: '600' },
  currencyOptions: { position: 'absolute', top: 50, right: 20, backgroundColor: '#fff', borderRadius: 10, padding: 10, zIndex: 20, elevation: 5, shadowColor: '#000', shadowOffset: { width: 0, height: 2 }, shadowOpacity: 0.1, shadowRadius: 4 },
  currencyOption: { paddingVertical: 8, paddingHorizontal: 12 },
  currencyOptionText: { fontSize: 14, color: '#333' },
  amountBox: { flexDirection: "row", alignItems: "center", backgroundColor: "#51d191", paddingHorizontal: 20, borderRadius: 12, height: 80 },
  currencySymbol: { fontSize: 28, color: "#fff", fontWeight: "bold", marginRight: 8 },
  amount: { fontSize: 32, color: "#fff", fontWeight: "bold", flex: 1 },
  backspace: { marginLeft: 8 },
  paymentSection: { paddingHorizontal: 20, marginBottom: 20 },
  paymentLabel: { fontSize: 14, fontWeight: '600', color: '#666', marginBottom: 8 },
  paymentSelectorButton: { backgroundColor: '#fff', borderRadius: 12, borderWidth: 1, borderColor: '#ddd', padding: 12 },
  paymentSelectorError: { borderColor: 'red', borderWidth: 2 },
  paymentSelectorContent: { flexDirection: 'row', alignItems: 'center', justifyContent: 'space-between' },
  paymentSelectorTitle: { fontSize: 16, fontWeight: '500', color: '#333', marginLeft: 12 },
  paymentSelectorSubtitle: { fontSize: 12, color: '#999', marginLeft: 12 },
  paymentSelectorPlaceholder: { flex: 1, fontSize: 16, color: '#999', marginLeft: 12 },
  paymentOptionsContainer: { backgroundColor: '#fff', borderRadius: 12, marginTop: 8, borderWidth: 1, borderColor: '#ddd', overflow: 'hidden' },
  paymentOptionItem: { flexDirection: 'row', alignItems: 'center', gap: 12, padding: 12, borderBottomWidth: 1, borderBottomColor: '#f0f0f0' },
  paymentOptionSelected: { backgroundColor: '#F0F9F8' },
  paymentOptionItemText: { flex: 1, fontSize: 16, color: '#333' },
  paymentOptionSelectedText: { color: '#51d191', fontWeight: '600' },
  noteWrapper: { marginTop: 10, marginBottom: 20, paddingHorizontal: 20 },
  noteContainer: { position: "relative" },
  noteInput: { borderWidth: 1, borderColor: "#ccc", padding: 12, borderRadius: 10, color: "#333", fontSize: 16, backgroundColor: "#fff" },
  saveCategoryButton: { position: "absolute", right: 12, top: 10 },
  saveCategoryText: { color: "#51d191", fontSize: 16, fontWeight: "600" },
  keypad: { paddingHorizontal: 20, marginBottom: 20 },
  keypadRow: { flexDirection: "row", justifyContent: "space-between", marginBottom: 12 },
  key: { width: 80, height: 70, justifyContent: "center", alignItems: "center", borderRadius: 12, backgroundColor: "#fff", shadowColor: "#000", shadowOffset: { width: 0, height: 1 }, shadowOpacity: 0.05, shadowRadius: 2, elevation: 1 },
  keyText: { fontSize: 28, color: "#333", fontWeight: "500" },
  submitKey: { backgroundColor: "#51d191", width: '100%', marginTop: 12 },
  submitKeyText: { fontSize: 18, color: "#fff", fontWeight: "bold" },
  categorySection: { paddingHorizontal: 20, marginBottom: 30 },
  categoryTitle: { fontSize: 18, fontWeight: '600', color: '#333', marginBottom: 15 },
  categoryBox: { flexDirection: "row", flexWrap: "wrap", gap: 12 },
  categoryOption: { flexDirection: 'row', alignItems: 'center', padding: 12, borderWidth: 1, borderColor: "#ddd", borderRadius: 10, minWidth: 100, gap: 8, backgroundColor: "#fff" },
  categorySelected: { backgroundColor: "#e8f5e9", borderColor: "#51d191" },
  categoryColor: { width: 12, height: 12, borderRadius: 6 },
  categoryText: { color: "#333", fontSize: 14 },
  addCategoryButton: { flexDirection: 'row', alignItems: 'center', padding: 12, gap: 8 },
  addCategoryText: { color: '#51d191', fontSize: 14 },
  addExpenseButton: { backgroundColor: "#ff6b6b", padding: 18, borderRadius: 12, alignItems: 'center' },
  addExpenseButtonText: { fontSize: 18, fontWeight: 'bold', color: '#fff' },
  loadingOverlay: { position: 'absolute', top: 0, left: 0, right: 0, bottom: 0, backgroundColor: 'rgba(255,255,255,0.8)', justifyContent: 'center', alignItems: 'center' },
});