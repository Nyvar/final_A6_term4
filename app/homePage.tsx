import { StyleSheet, Text, View, Image, Dimensions, TouchableOpacity, Modal, ActivityIndicator, ScrollView, RefreshControl, FlatList } from 'react-native'
import React, { useEffect, useState, useCallback } from 'react'
import { router, useFocusEffect } from 'expo-router';
import LeftNavigation from './LeftNavigation';
import RightNavigation from './rightNavigation';
import { getStoredToken } from '@/services/auth';
import { getDashboard } from '@/services/api';


const { width, height } = Dimensions.get("window");

export default function HomePage() {
  const [leftMenuVisible, setLeftMenuVisible] = useState(false);
  const [rightMenuVisible, setRightMenuVisible] = useState(false);
  const [dashboardData, setDashboardData] = useState<any>(null);
  const [loadingDashboard, setLoadingDashboard] = useState(true);
  const [refreshing, setRefreshing] = useState(false);
  const [selectedCurrency, setSelectedCurrency] = useState('USD');
  const [dateFilter, setDateFilter] = useState('month');
  const [transactionsModalVisible, setTransactionsModalVisible] = useState(false);
  
  // Calculated values
  const [totalIncome, setTotalIncome] = useState(0);
  const [totalExpense, setTotalExpense] = useState(0);
  const [totalBalance, setTotalBalance] = useState(0);
  const [currencySymbol, setCurrencySymbol] = useState('$');
  const [allTransactions, setAllTransactions] = useState([]);

  useEffect(() => {
    getStoredToken().then((token) => {
      if (!token) {
        router.replace('/login');
      }
    });
    loadDashboard();
  }, []);


  useFocusEffect(
  useCallback(() => {
    console.log('🏠 HomePage focused - refreshing dashboard data...');
    loadDashboard();
    return () => {};
  }, [selectedCurrency, dateFilter])
);

  const calculateTotalsFromTransactions = (transactions: any[]) => {
    let income = 0;
    let expense = 0;
    
    if (transactions && transactions.length > 0) {
      transactions.forEach((transaction: any) => {
        const amount = parseFloat(transaction.amount) || 0;
        if (transaction.type === 'income') {
          income += amount;
        } else if (transaction.type === 'expense') {
          expense += amount;
        }
      });
    }
    
    const balance = income - expense;
    return { income, expense, balance };
  };

  const loadDashboard = async () => {
    try {
      setLoadingDashboard(true);
      const response = await getDashboard(selectedCurrency, dateFilter);
      
      if (response && response.status === 'success') {
        const transactions = response.transactions || [];
        const { income, expense, balance } = calculateTotalsFromTransactions(transactions);
        const symbol = response.currency_symbol || '$';
        
        setTotalIncome(income);
        setTotalExpense(expense);
        setTotalBalance(balance);
        setCurrencySymbol(symbol);
        setDashboardData(response);
        setAllTransactions(transactions);
      }
    } catch (error) {
      console.error('Error loading dashboard:', error);
    } finally {
      setLoadingDashboard(false);
      setRefreshing(false);
    }
  };

  const onRefresh = async () => {
    setRefreshing(true);
    await loadDashboard();
  };

  const handleIntervalSelect = (interval: string, startDate?: Date, endDate?: Date) => {
    let filter = 'month';
    if (interval === 'Day') filter = 'day';
    else if (interval === 'Week') filter = 'week';
    else if (interval === 'Month') filter = 'month';
    else if (interval === 'Year') filter = 'year';
    setDateFilter(filter);
    loadDashboard();
    setLeftMenuVisible(false);
  };

  const handlePaymentMethodChange = (method: string) => {
    console.log('Payment method changed:', method);
  };

  const handleRightMenuItemPress = (item: string) => {
    console.log('Selected menu item:', item);
    setRightMenuVisible(false);
  };

  const handleBalanceUpdate = () => {
    loadDashboard();
  };

  const formatDate = (dateString: string) => {
    const date = new Date(dateString);
    return date.toLocaleDateString('en-US', { month: 'short', day: 'numeric' });
  };

  const TransactionItem = ({ transaction }: { transaction: any }) => (
    <View style={styles.transactionItem}>
      <View style={[styles.transactionIcon, { backgroundColor: transaction.type === 'income' ? '#E8F5E9' : '#FFEBEE' }]}>
        <Text style={{ fontSize: 20, color: transaction.type === 'income' ? '#4ECDC4' : '#FF6B6B' }}>
          {transaction.type === 'income' ? '↑' : '↓'}
        </Text>
      </View>
      <View style={styles.transactionInfo}>
        <Text style={styles.transactionName}>{transaction.category_name || transaction.category || 'Transaction'}</Text>
        <Text style={styles.transactionDate}>{formatDate(transaction.date)}</Text>
        {transaction.note ? <Text style={styles.transactionNote}>{transaction.note}</Text> : null}
      </View>
      <Text style={[styles.transactionAmount, { color: transaction.type === 'income' ? '#4ECDC4' : '#FF6B6B' }]}>
        {transaction.type === 'income' ? '+' : '-'}{currencySymbol}{Math.abs(parseFloat(transaction.amount)).toFixed(2)}
      </Text>
    </View>
  );

  return (
    <View style={styles.container}>
      {/* Modals */}
      <Modal
        transparent={true}
        visible={leftMenuVisible}
        animationType="fade"
        onRequestClose={() => setLeftMenuVisible(false)}
      >
        <LeftNavigation
          onClose={() => setLeftMenuVisible(false)}
          onSelectInterval={handleIntervalSelect}
          onPaymentMethodChange={handlePaymentMethodChange}
        />
      </Modal>

      <Modal
        transparent={true}
        visible={rightMenuVisible}
        animationType="fade"
        onRequestClose={() => setRightMenuVisible(false)}
      >
        <RightNavigation
          onClose={() => setRightMenuVisible(false)}
          onMenuItemPress={handleRightMenuItemPress}
          onBalanceUpdate={handleBalanceUpdate}
        />
      </Modal>

      {/* Transactions Modal */}
      <Modal
        visible={transactionsModalVisible}
        animationType="slide"
        transparent={true}
        onRequestClose={() => setTransactionsModalVisible(false)}
      >
        <View style={styles.modalOverlay}>
          <View style={styles.modalContent}>
            <View style={styles.modalHeader}>
              <Text style={styles.modalTitle}>Transaction History</Text>
              <TouchableOpacity onPress={() => setTransactionsModalVisible(false)}>
                <Text style={styles.modalCloseText}>✕</Text>
              </TouchableOpacity>
            </View>
            
            <View style={styles.modalSummary}>
              <View style={styles.modalSummaryItem}>
                <Text style={styles.modalSummaryLabel}>Total Income</Text>
                <Text style={[styles.modalSummaryValue, { color: '#4ECDC4' }]}>
                  +{currencySymbol}{totalIncome.toFixed(2)}
                </Text>
              </View>
              <View style={styles.modalSummaryItem}>
                <Text style={styles.modalSummaryLabel}>Total Expense</Text>
                <Text style={[styles.modalSummaryValue, { color: '#FF6B6B' }]}>
                  -{currencySymbol}{totalExpense.toFixed(2)}
                </Text>
              </View>
              <View style={styles.modalSummaryItem}>
                <Text style={styles.modalSummaryLabel}>Balance</Text>
                <Text style={[styles.modalSummaryValue, { color: totalBalance >= 0 ? '#4ECDC4' : '#FF6B6B' }]}>
                  {currencySymbol}{totalBalance.toFixed(2)}
                </Text>
              </View>
            </View>

            <FlatList
              data={allTransactions}
              keyExtractor={(item, index) => index.toString()}
              renderItem={({ item }) => <TransactionItem transaction={item} />}
              showsVerticalScrollIndicator={false}
              ListEmptyComponent={
                <View style={styles.emptyContainer}>
                  <Text style={styles.emptyText}>No transactions yet</Text>
                  <Text style={styles.emptySubText}>Add your first transaction by tapping + or -</Text>
                </View>
              }
              contentContainerStyle={styles.transactionsList}
            />
          </View>
        </View>
      </Modal>

      {/* Header */}
      <View style={styles.header}>
        <View style={styles.headerLeft}>
          <TouchableOpacity onPress={() => setLeftMenuVisible(true)}>
            <Image
              source={require("../assets/images/Vector (1).png")}
              style={styles.icon}
              resizeMode="contain"
            />
          </TouchableOpacity>
          <View>
            <TouchableOpacity onPress={() => router.push('/profile')}>
              <Image
                source={require("../assets/images/monefy.png")}
                style={styles.monefy_icon}
                resizeMode="contain"
              />
            </TouchableOpacity>
            <Text style={styles.headerText}>All accounts</Text>
          </View>
        </View>

        <View style={styles.headerRight}>
          <TouchableOpacity onPress={onRefresh}>
            <Image
              source={require("../assets/images/Search.png")}
              style={styles.icon}
              resizeMode="contain"
            />
          </TouchableOpacity>
          <TouchableOpacity onPress={() => router.push('/AddIncomeScreen')}>
            <View style={styles.arrowContainer}>
              <Image
                source={require("../assets/images/Arrow 1.png")}
                style={styles.icon}
                resizeMode="contain"
              />
              <Image
                source={require("../assets/images/Arrow 2.png")}
                style={styles.icon}
                resizeMode="contain"
              />
            </View>
          </TouchableOpacity>
          <TouchableOpacity onPress={() => setRightMenuVisible(true)}>
            <View style={styles.dotsContainer}>
              <Image
                source={require("../assets/images/Ellipse 4.png")}
                style={styles.ellipse}
                resizeMode="contain"
              />
              <Image
                source={require("../assets/images/Ellipse 4.png")}
                style={styles.ellipse}
                resizeMode="contain"
              />
              <Image
                source={require("../assets/images/Ellipse 4.png")}
                style={styles.ellipse}
                resizeMode="contain"
              />
            </View>
          </TouchableOpacity>
        </View>
      </View>

      {/* Main Content */}
      <View style={styles.content}>
        {/* Top Icons Row */}
        <View style={styles.topIconsRow}>
          <View>
            <TouchableOpacity onPress={() => router.push('/AddIncomeScreen')}>
              <Image source={require("../assets/images/basket.png")} style={styles.icon2} resizeMode="contain" />
            </TouchableOpacity>
            <View style={styles.leftIconsColumn}>
              <TouchableOpacity onPress={() => router.push('/AddIncomeScreen')}>
                <Image source={require("../assets/images/Vector (3).png")} style={styles.icon2} resizeMode="contain" />
              </TouchableOpacity>
              <TouchableOpacity onPress={() => router.push('/AddIncomeScreen')}>
                <Image source={require("../assets/images/cooktail.png")} style={styles.icon2} resizeMode="contain" />
              </TouchableOpacity>
              <TouchableOpacity onPress={() => router.push('/AddIncomeScreen')}>
                <Image source={require("../assets/images/taxi.png")} style={styles.icon2} resizeMode="contain" />
              </TouchableOpacity>
            </View>
          </View>

          <TouchableOpacity onPress={() => router.push('/AddIncomeScreen')}>
            <Image source={require("../assets/images/house.png")} style={styles.icon2} resizeMode="contain" />
          </TouchableOpacity>

          <TouchableOpacity onPress={() => router.push('/AddIncomeScreen')}>
            <Image source={require("../assets/images/Vector (2).png")} style={styles.icon2} resizeMode="contain" />
          </TouchableOpacity>

          <View>
            <TouchableOpacity onPress={() => router.push('/AddIncomeScreen')}>
              <Image source={require("../assets/images/fork.png")} style={styles.icon2} resizeMode="contain" />
            </TouchableOpacity>
            <View style={styles.rightIconsColumn}>
              <TouchableOpacity onPress={() => router.push('/AddIncomeScreen')}>
                <Image source={require("../assets/images/teeth-brush.png")} style={styles.icon2} resizeMode="contain" />
              </TouchableOpacity>
              <TouchableOpacity onPress={() => router.push('/AddIncomeScreen')}>
                <Image source={require("../assets/images/soccer-player.png")} style={styles.icon2} resizeMode="contain" />
              </TouchableOpacity>
              <TouchableOpacity onPress={() => router.push('/AddIncomeScreen')}>
                <Image source={require("../assets/images/thermometer.png")} style={styles.icon2} resizeMode="contain" />
              </TouchableOpacity>
            </View>
          </View>
        </View>

        {/* Bottom Icons Row */}
        <View style={styles.bottomIconsRow}>
          <TouchableOpacity onPress={() => router.push('/AddIncomeScreen')}>
            <Image source={require("../assets/images/shirt.png")} style={styles.icon2} resizeMode="contain" />
          </TouchableOpacity>
          <TouchableOpacity onPress={() => router.push('/AddIncomeScreen')}>
            <Image source={require("../assets/images/telephone.png")} style={styles.icon2} resizeMode="contain" />
          </TouchableOpacity>
          <TouchableOpacity onPress={() => router.push('/AddIncomeScreen')}>
            <Image source={require("../assets/images/gift.png")} style={styles.icon2} resizeMode="contain" />
          </TouchableOpacity>
          <TouchableOpacity onPress={() => router.push('/AddIncomeScreen')}>
            <Image source={require("../assets/images/cat.png")} style={styles.icon2} resizeMode="contain" />
          </TouchableOpacity>
        </View>

        {/* Center Circle Chart */}
        <View style={styles.circleContainer}>
          <View style={styles.outerCircle}>
            <View style={styles.innerCircle}>
              {!loadingDashboard ? (
                <>
                  <Text style={styles.incomeText}>+{currencySymbol}{totalIncome.toFixed(2)}</Text>
                  <Text style={styles.expenseText}>-{currencySymbol}{totalExpense.toFixed(2)}</Text>
                </>
              ) : (
                <ActivityIndicator size="small" color="#51cf66" />
              )}
            </View>
          </View>
        </View>

        {/* Balance Button - Opens Transaction Modal */}
        <TouchableOpacity style={styles.balanceBtn} onPress={() => setTransactionsModalVisible(true)}>
          <Text style={styles.balanceText}>Balance {currencySymbol}{totalBalance.toFixed(2)}</Text>
        </TouchableOpacity>

        {/* Action Buttons */}
        <View style={styles.bottomActions}>
          <TouchableOpacity style={[styles.circleBtn, styles.minus]} onPress={() => router.push('/AddIncomeScreen')}>
            <Text style={styles.redActionText}>-</Text>
          </TouchableOpacity>
          <TouchableOpacity style={[styles.circleBtn, styles.plus]} onPress={() => router.push('/AddIncomeScreen')}>
            <Text style={styles.greenActionText}>+</Text>
          </TouchableOpacity>
        </View>
      </View>
    </View>
  )
}

const CIRCLE_SIZE = width * 0.45;

const styles = StyleSheet.create({
  container: {
    flex: 1,
    backgroundColor: '#4CBF72',
  },
  header: {
    flexDirection: 'row',
    justifyContent: 'space-between',
    alignItems: 'center',
    paddingHorizontal: 20,
    paddingTop: 50,
    paddingBottom: 20,
  },
  headerLeft: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 20,
  },
  headerRight: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 25,
  },
  icon: {
    width: 30,
    height: 30,
  },
  monefy_icon: {
    width: 70,
    height: 70,
  },
  headerText: {
    color: "#fff",
    fontSize: 12,
    marginTop: -15,
    textAlign: 'center',
  },
  arrowContainer: {
    flexDirection: 'column',
  },
  dotsContainer: {
    gap: 5,
    fontSize:10
  },
  ellipse: {
    width: 7,
    height: 7,
  },
  content: {
    flex: 1,
    backgroundColor: '#dceee4',
    alignItems: 'center',
    justifyContent: 'space-between',
    paddingVertical: 20,
  },
  topIconsRow: {
    flexDirection: "row",
    justifyContent: "space-around",
    width: '100%',
    marginTop:70
  },
  leftIconsColumn: {
    flexDirection: 'column',
    gap: 20,
    marginTop: 50,
  },
  rightIconsColumn: {
    flexDirection: 'column',
    gap: 20,
    marginTop: 50,
  },
  bottomIconsRow: {
    flexDirection: "row",
    justifyContent: 'space-around',
    gap: 20,
    width: '100%',
    paddingHorizontal: 20,
    marginBottom: 20,
  },
  icon2: {
    width: 50,
    height: 50,
  },
  circleContainer: {
    alignItems: 'center',
    justifyContent: 'center',
    marginVertical: 20,
    top: -350,
    left: 0,
    right: 0,
    bottom: 0,
  },
  outerCircle: {
    width: CIRCLE_SIZE,
    height: CIRCLE_SIZE,
    borderRadius: CIRCLE_SIZE / 2,
    backgroundColor: "#E8F8F5",
    justifyContent: "center",
    alignItems: "center",
    shadowColor: "#000",
    shadowOffset: { width: 0, height: 2 },
    shadowOpacity: 0.1,
    shadowRadius: 4,
    elevation: 3,
  },
  innerCircle: {
    width: CIRCLE_SIZE * 0.65,
    height: CIRCLE_SIZE * 0.65,
    borderRadius: (CIRCLE_SIZE * 0.65) / 2,
    backgroundColor: "#dceee4",
    justifyContent: "center",
    alignItems: "center",
  },
  incomeText: {
    color: "#51cf66",
    fontWeight: "bold",
    fontSize: 16,
  },
  expenseText: {
    color: "#ff6b6b",
    fontWeight: "bold",
    marginTop: 5,
    fontSize: 16,
  },
  balanceBtn: {
    backgroundColor: "#4CBF72",
    paddingVertical: 12,
    paddingHorizontal: 30,
    borderRadius: 10,
    alignItems: "center",
    marginTop:-250
  },
  balanceText: {
    color: "#fff",
    fontSize: 18,
    fontWeight: "bold",
  },
  bottomActions: {
    flexDirection: "row",
    justifyContent: "space-around",
    width: '100%',
    paddingHorizontal: 40,
    marginBottom: 30,
  },
  circleBtn: {
    width: 100,
    height: 100,
    borderRadius: 50,
    justifyContent: "center",
    alignItems: "center",
    borderWidth: 3,
    backgroundColor: "#fff",
  },
  minus: {
    borderColor: "#ff6b6b",
  },
  plus: {
    borderColor: "#4caf50",
  },
  redActionText: {
    color: "#ff6b6b",
    fontSize: 35,
    fontWeight: "bold",
  },
  greenActionText: {
    color: "#4caf50",
    fontSize: 35,
    fontWeight: "bold",
  },
  // Modal Styles
  modalOverlay: {
    flex: 1,
    backgroundColor: 'rgba(0,0,0,0.5)',
    justifyContent: 'flex-end',
  },
  modalContent: {
    backgroundColor: '#fff',
    borderTopLeftRadius: 25,
    borderTopRightRadius: 25,
    height: height * 0.8,
  },
  modalHeader: {
    flexDirection: 'row',
    justifyContent: 'space-between',
    alignItems: 'center',
    paddingHorizontal: 20,
    paddingVertical: 20,
    borderBottomWidth: 1,
    borderBottomColor: '#f0f0f0',
  },
  modalTitle: {
    fontSize: 20,
    fontWeight: 'bold',
    color: '#333',
  },
  modalCloseText: {
    fontSize: 20,
    color: '#999',
    fontWeight: 'bold',
  },
  modalSummary: {
    flexDirection: 'row',
    justifyContent: 'space-around',
    paddingVertical: 15,
    backgroundColor: '#F8F9FA',
    marginBottom: 10,
  },
  modalSummaryItem: {
    alignItems: 'center',
  },
  modalSummaryLabel: {
    fontSize: 12,
    color: '#666',
    marginBottom: 5,
  },
  modalSummaryValue: {
    fontSize: 16,
    fontWeight: 'bold',
  },
  transactionsList: {
    paddingHorizontal: 20,
    paddingBottom: 20,
  },
  transactionItem: {
    flexDirection: 'row',
    alignItems: 'center',
    paddingVertical: 12,
    borderBottomWidth: 1,
    borderBottomColor: '#f0f0f0',
  },
  transactionIcon: {
    width: 45,
    height: 45,
    borderRadius: 23,
    justifyContent: 'center',
    alignItems: 'center',
    marginRight: 12,
  },
  transactionInfo: {
    flex: 1,
  },
  transactionName: {
    fontSize: 15,
    fontWeight: '500',
    color: '#333',
  },
  transactionDate: {
    fontSize: 11,
    color: '#999',
    marginTop: 2,
  },
  transactionNote: {
    fontSize: 11,
    color: '#bbb',
    marginTop: 1,
  },
  transactionAmount: {
    fontSize: 15,
    fontWeight: '600',
  },
  emptyContainer: {
    alignItems: 'center',
    paddingVertical: 50,
  },
  emptyText: {
    fontSize: 16,
    color: '#999',
    marginBottom: 8,
  },
  emptySubText: {
    fontSize: 12,
    color: '#bbb',
  },
});