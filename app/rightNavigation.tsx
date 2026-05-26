// rightNavigation.tsx
import React, { useState, useEffect } from "react";
import {
  Dimensions,
  StyleSheet,
  Text,
  TouchableOpacity,
  TouchableWithoutFeedback,
  View,
  TextInput,
  Modal,
  Alert,
  ActivityIndicator,
  ScrollView,
  Animated,
} from "react-native";
import { Ionicons } from "@expo/vector-icons";
import { getCurrencies, updateCurrencyBalance, getDashboard } from '@/services/api';
import { getStoredToken } from '@/services/auth';

const { width, height } = Dimensions.get("window");

interface RightNavigationProps {
  onClose: () => void;
  onMenuItemPress?: (item: string) => void;
  onBalanceUpdate?: () => void;
}

interface Currency {
  code: string;
  name: string;
  symbol: string;
  balance?: number;
}

export default function RightNavigation({ onClose, onMenuItemPress, onBalanceUpdate }: RightNavigationProps) {
  const [selectedItem, setSelectedItem] = useState<string | null>(null);
  const [currencies, setCurrencies] = useState<Currency[]>([]);
  const [loading, setLoading] = useState(true);
  const [selectedCurrency, setSelectedCurrency] = useState<Currency | null>(null);
  const [addMoneyModalVisible, setAddMoneyModalVisible] = useState(false);
  const [amount, setAmount] = useState("");
  const [addingMoney, setAddingMoney] = useState(false);
  const [showAddMoneyOptions, setShowAddMoneyOptions] = useState(false);
  const [dashboardData, setDashboardData] = useState<any>(null);
  
  // Animation for right sidebar
  const slideAnim = useState(new Animated.Value(width))[0];

  useEffect(() => {
    loadData();
    // Animate in from right
    Animated.spring(slideAnim, {
      toValue: 0,
      useNativeDriver: true,
      tension: 65,
      friction: 11,
    }).start();
  }, []);

  const loadData = async () => {
    try {
      setLoading(true);
      const [currenciesRes, dashboardRes] = await Promise.all([
        getCurrencies(),
        getDashboard('USD', 'month')
      ]);
      
      if (currenciesRes && currenciesRes.success && currenciesRes.data) {
        setCurrencies(currenciesRes.data);
      }
      
      if (dashboardRes && dashboardRes.success && dashboardRes.data) {
        setDashboardData(dashboardRes.data);
      }
    } catch (error) {
      console.error('Error loading data:', error);
    } finally {
      setLoading(false);
    }
  };

  const handleClose = () => {
    Animated.timing(slideAnim, {
      toValue: width,
      duration: 250,
      useNativeDriver: true,
    }).start(() => onClose());
  };

  const handleMenuItemPress = (item: string) => {
    setSelectedItem(item);
    if (onMenuItemPress) {
      onMenuItemPress(item);
    }
    handleClose();
  };

  const handleAddMoney = async () => {
    if (!selectedCurrency) {
      Alert.alert('Error', 'Please select a currency');
      return;
    }
    
    const amountNum = parseFloat(amount);
    if (isNaN(amountNum) || amountNum <= 0) {
      Alert.alert('Error', 'Please enter a valid amount');
      return;
    }

    setAddingMoney(true);
    try {
      const token = await getStoredToken();
      
      const response = await updateCurrencyBalance(selectedCurrency.code, amountNum);
      
      if (response && response.success) {
        Alert.alert('Success', `Added ${selectedCurrency.symbol}${amountNum.toFixed(2)} to ${selectedCurrency.name}`);
        setAmount("");
        setAddMoneyModalVisible(false);
        setSelectedCurrency(null);
        await loadData();
        if (onBalanceUpdate) {
          onBalanceUpdate();
        }
      } else {
        Alert.alert('Error', response?.message || 'Failed to add money');
      }
    } catch (error: any) {
      console.error('Error adding money:', error);
      Alert.alert('Error', error?.message || 'Network error. Please try again.');
    } finally {
      setAddingMoney(false);
    }
  };

  const menuItems = [
    { icon: "grid-outline", label: "Categories", key: "categories", color: "#FF6B6B" },
    { icon: "wallet-outline", label: "Accounts", key: "accounts", color: "#4ECDC4" },
    { icon: "cash-outline", label: "Currencies", key: "currencies", color: "#45B7D1" },
    { icon: "settings-outline", label: "Settings", key: "settings", color: "#96CEB4" },
    { icon: "school-outline", label: "Guides", key: "guides", color: "#FFEAA7" },
  ];

  const totalBalance = dashboardData?.total_balance || 0;
  const defaultCurrency = dashboardData?.default_currency || 'USD';

  return (
    <View style={styles.container}>
      <TouchableWithoutFeedback onPress={handleClose}>
        <View style={styles.overlay} />
      </TouchableWithoutFeedback>
      
      <Animated.View style={[styles.sidebar, { transform: [{ translateX: slideAnim }] }]}>
        <ScrollView showsVerticalScrollIndicator={false}>
          <View style={styles.header}>
            <Text style={styles.moneyText}>menu</Text>
            <TouchableOpacity onPress={handleClose} style={styles.closeButton}>
              <Ionicons name="close" size={24} color="#000" />
            </TouchableOpacity>
          </View>

          <View style={styles.allAccountsSection}>
            <TouchableOpacity 
              style={styles.accountContainer}
              onPress={() => setShowAddMoneyOptions(!showAddMoneyOptions)}
            >
              <Ionicons name="wallet-outline" size={24} color="#333" />
              <Text style={styles.accountText}>All accounts</Text>
              <Ionicons 
                name={showAddMoneyOptions ? "chevron-up" : "chevron-down"} 
                size={20} 
                color="#666" 
                style={styles.chevron}
              />
            </TouchableOpacity>
            
            {showAddMoneyOptions && (
              <View style={styles.addMoneyOptions}>
                <Text style={styles.sectionTitle}>Add Money to Account</Text>
                {loading ? (
                  <ActivityIndicator size="small" color="#69b578" />
                ) : currencies.length === 0 ? (
                  <Text style={styles.noDataText}>No currencies available</Text>
                ) : (
                  <ScrollView style={styles.currenciesList}>
                    {currencies.map((currency) => (
                      <TouchableOpacity
                        key={currency.code}
                        style={styles.currencyItem}
                        onPress={() => {
                          setSelectedCurrency(currency);
                          setAddMoneyModalVisible(true);
                          setShowAddMoneyOptions(false);
                        }}
                      >
                        <View style={styles.currencyInfo}>
                          <Text style={styles.currencyCode}>{currency.code}</Text>
                          <Text style={styles.currencyName}>{currency.name}</Text>
                        </View>
                        <View style={styles.currencyBalance}>
                          <Text style={styles.balanceText}>
                            {currency.symbol}{(currency.balance || 0).toFixed(2)}
                          </Text>
                          <Ionicons name="add-circle-outline" size={24} color="#69b578" />
                        </View>
                      </TouchableOpacity>
                    ))}
                  </ScrollView>
                )}
              </View>
            )}
          </View>

          <View style={styles.menuSection}>
            {menuItems.map((item) => (
              <TouchableOpacity
                key={item.key}
                style={[
                  styles.menuItem,
                  selectedItem === item.label && styles.menuItemSelected,
                ]}
                onPress={() => handleMenuItemPress(item.label)}
              >
                <View style={[styles.menuIconWrapper, { backgroundColor: `${item.color}20` }]}>
                  <Ionicons name={item.icon as any} size={22} color={item.color} />
                </View>
                <Text style={[
                  styles.menuText,
                  selectedItem === item.label && styles.menuTextSelected,
                ]}>
                  {item.label}
                </Text>
                {selectedItem === item.label && (
                  <View style={styles.selectedIndicator} />
                )}
              </TouchableOpacity>
            ))}
          </View>
        </ScrollView>

        <View style={styles.balanceSection}>
          <Text style={styles.balanceLabel}>Total Balance</Text>
          <Text style={styles.balanceAmount}>
            ${totalBalance.toFixed(2)} {defaultCurrency}
          </Text>
        </View>
      </Animated.View>

      {/* Add Money Modal */}
      <Modal
        visible={addMoneyModalVisible}
        animationType="slide"
        transparent={true}
        onRequestClose={() => setAddMoneyModalVisible(false)}
      >
        <View style={styles.modalOverlay}>
          <View style={styles.modalContent}>
            <View style={styles.modalHeader}>
              <Text style={styles.modalTitle}>Add Money</Text>
              <TouchableOpacity onPress={() => setAddMoneyModalVisible(false)}>
                <Ionicons name="close" size={24} color="#333" />
              </TouchableOpacity>
            </View>
            
            {selectedCurrency && (
              <>
                <View style={styles.currencyDisplay}>
                  <View style={styles.currencyIcon}>
                    <Text style={styles.currencyIconText}>{selectedCurrency.code}</Text>
                  </View>
                  <Text style={styles.currencyDisplayName}>{selectedCurrency.name}</Text>
                  <Text style={styles.currentBalanceText}>
                    Current: {selectedCurrency.symbol}{(selectedCurrency.balance || 0).toFixed(2)}
                  </Text>
                </View>
                
                <View style={styles.amountInputContainer}>
                  <Text style={styles.currencySymbol}>{selectedCurrency.symbol}</Text>
                  <TextInput
                    style={styles.amountInput}
                    placeholder="0.00"
                    keyboardType="decimal-pad"
                    value={amount}
                    onChangeText={setAmount}
                    autoFocus
                  />
                </View>
                
                <View style={styles.quickAmounts}>
                  {[10, 20, 50, 100, 200, 500].map((amt) => (
                    <TouchableOpacity
                      key={amt}
                      style={styles.quickAmountButton}
                      onPress={() => setAmount(amt.toString())}
                    >
                      <Text style={styles.quickAmountText}>
                        {selectedCurrency.symbol}{amt}
                      </Text>
                    </TouchableOpacity>
                  ))}
                </View>
                
                <View style={styles.modalButtons}>
                  <TouchableOpacity
                    style={[styles.modalButton, styles.cancelButton]}
                    onPress={() => {
                      setAddMoneyModalVisible(false);
                      setAmount("");
                    }}
                  >
                    <Text style={styles.cancelButtonText}>Cancel</Text>
                  </TouchableOpacity>
                  
                  <TouchableOpacity
                    style={[styles.modalButton, styles.addButton]}
                    onPress={handleAddMoney}
                    disabled={addingMoney}
                  >
                    {addingMoney ? (
                      <ActivityIndicator size="small" color="#fff" />
                    ) : (
                      <>
                        <Ionicons name="add-circle-outline" size={20} color="#fff" />
                        <Text style={styles.addButtonText}>Add Money</Text>
                      </>
                    )}
                  </TouchableOpacity>
                </View>
              </>
            )}
          </View>
        </View>
      </Modal>
    </View>
  );
}

const styles = StyleSheet.create({
  container: {
    flex: 1,
    flexDirection: "row",
    backgroundColor: "rgba(0,0,0,0.5)",
  },
  overlay: {
    flex: 1,
    backgroundColor: "transparent",
  },
  sidebar: {
    width: width * 0.85,
    maxWidth: 340,
    backgroundColor: "#fff",
    height: "100%",
    shadowColor: "#000",
    shadowOffset: { width: -2, height: 0 },
    shadowOpacity: 0.1,
    shadowRadius: 10,
    elevation: 5,
  },
  header: {
    flexDirection: "row",
    justifyContent: "space-between",
    alignItems: "center",
    paddingHorizontal: 20,
    paddingTop: 60,
    paddingBottom: 20,
    borderBottomWidth: 1,
    borderBottomColor: "#f0f0f0",
  },
  moneyText: {
    fontSize: 24,
    fontWeight: "bold",
    color: "#000",
  },
  closeButton: {
    padding: 5,
  },
  allAccountsSection: {
    paddingHorizontal: 20,
    paddingVertical: 20,
    borderBottomWidth: 1,
    borderBottomColor: "#f0f0f0",
  },
  accountContainer: {
    flexDirection: "row",
    alignItems: "center",
    gap: 12,
  },
  accountText: {
    fontSize: 16,
    color: "#333",
    fontWeight: "500",
    flex: 1,
  },
  chevron: {
    marginLeft: "auto",
  },
  addMoneyOptions: {
    marginTop: 15,
    paddingTop: 15,
    borderTopWidth: 1,
    borderTopColor: "#f0f0f0",
  },
  sectionTitle: {
    fontSize: 14,
    fontWeight: "600",
    color: "#666",
    marginBottom: 12,
  },
  noDataText: {
    textAlign: 'center',
    color: '#999',
    padding: 20,
  },
  currenciesList: {
    maxHeight: 300,
  },
  currencyItem: {
    flexDirection: "row",
    justifyContent: "space-between",
    alignItems: "center",
    paddingVertical: 12,
    borderBottomWidth: 1,
    borderBottomColor: "#f0f0f0",
  },
  currencyInfo: {
    flex: 1,
  },
  currencyCode: {
    fontSize: 16,
    fontWeight: "600",
    color: "#333",
  },
  currencyName: {
    fontSize: 12,
    color: "#666",
    marginTop: 2,
  },
  currencyBalance: {
    flexDirection: "row",
    alignItems: "center",
    gap: 10,
  },
  balanceText: {
    fontSize: 14,
    fontWeight: "500",
    color: "#69b578",
  },
  menuSection: {
    paddingHorizontal: 20,
    paddingVertical: 10,
  },
  menuItem: {
    flexDirection: "row",
    alignItems: "center",
    paddingVertical: 14,
    gap: 15,
  },
  menuItemSelected: {
    backgroundColor: "#e8f5e9",
    marginHorizontal: -20,
    paddingHorizontal: 20,
    borderRadius: 10,
  },
  menuIconWrapper: {
    width: 38,
    height: 38,
    borderRadius: 10,
    justifyContent: "center",
    alignItems: "center",
  },
  menuText: {
    fontSize: 16,
    color: "#333",
    flex: 1,
  },
  menuTextSelected: {
    color: "#69b578",
    fontWeight: "600",
  },
  selectedIndicator: {
    width: 3,
    height: 20,
    backgroundColor: "#69b578",
    borderRadius: 2,
  },
  balanceSection: {
    marginTop: 'auto',
    padding: 20,
    borderTopWidth: 1,
    borderTopColor: "#f0f0f0",
    backgroundColor: "#f8f9fa",
  },
  balanceLabel: {
    fontSize: 12,
    color: "#666",
    marginBottom: 5,
  },
  balanceAmount: {
    fontSize: 20,
    fontWeight: "bold",
    color: "#69b578",
  },
  modalOverlay: {
    flex: 1,
    backgroundColor: "rgba(0,0,0,0.5)",
    justifyContent: "center",
    alignItems: "center",
  },
  modalContent: {
    backgroundColor: "#fff",
    borderRadius: 20,
    padding: 20,
    width: "85%",
    maxWidth: 400,
  },
  modalHeader: {
    flexDirection: "row",
    justifyContent: "space-between",
    alignItems: "center",
    marginBottom: 20,
  },
  modalTitle: {
    fontSize: 20,
    fontWeight: "bold",
    color: "#333",
  },
  currencyDisplay: {
    alignItems: "center",
    marginBottom: 20,
  },
  currencyIcon: {
    width: 60,
    height: 60,
    borderRadius: 30,
    backgroundColor: "#e8f5e9",
    justifyContent: "center",
    alignItems: "center",
    marginBottom: 10,
  },
  currencyIconText: {
    fontSize: 18,
    fontWeight: "bold",
    color: "#69b578",
  },
  currencyDisplayName: {
    fontSize: 16,
    color: "#666",
  },
  currentBalanceText: {
    fontSize: 12,
    color: "#999",
    marginTop: 5,
  },
  amountInputContainer: {
    flexDirection: "row",
    alignItems: "center",
    borderWidth: 1,
    borderColor: "#ddd",
    borderRadius: 12,
    padding: 15,
    marginBottom: 20,
  },
  currencySymbol: {
    fontSize: 24,
    fontWeight: "bold",
    color: "#333",
    marginRight: 10,
  },
  amountInput: {
    flex: 1,
    fontSize: 24,
    fontWeight: "bold",
    color: "#333",
  },
  quickAmounts: {
    flexDirection: "row",
    flexWrap: "wrap",
    gap: 10,
    marginBottom: 20,
  },
  quickAmountButton: {
    backgroundColor: "#f0f0f0",
    paddingHorizontal: 15,
    paddingVertical: 8,
    borderRadius: 20,
  },
  quickAmountText: {
    fontSize: 14,
    color: "#333",
  },
  modalButtons: {
    flexDirection: "row",
    gap: 10,
  },
  modalButton: {
    flex: 1,
    flexDirection: "row",
    alignItems: "center",
    justifyContent: "center",
    paddingVertical: 12,
    borderRadius: 10,
    gap: 8,
  },
  cancelButton: {
    backgroundColor: "#f0f0f0",
  },
  cancelButtonText: {
    fontSize: 16,
    color: "#666",
    fontWeight: "600",
  },
  addButton: {
    backgroundColor: "#69b578",
  },
  addButtonText: {
    fontSize: 16,
    color: "#fff",
    fontWeight: "bold",
  },
});