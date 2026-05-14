import React from "react";
import {
  View,
  Text,
  StyleSheet,
  TouchableOpacity,
  SafeAreaView,
  ScrollView,
} from "react-native";
import { Ionicons } from "@expo/vector-icons";
import { Svg, Circle } from "react-native-svg";

const categories = [
  { name: "Groceries", icon: "cart" },
  { name: "Home", icon: "home" },
  { name: "Car", icon: "car" },
  { name: "Dining", icon: "restaurant" },
  { name: "Transport", icon: "bus" },
  { name: "Drinks", icon: "beer" },
  { name: "Entertainment", icon: "game-controller" },
  { name: "Clothing", icon: "shirt" },
  { name: "Communication", icon: "call" },
  { name: "Gifts", icon: "gift" },
  { name: "Pets", icon: "paw" },
];

const HomeScreen: React.FC = () => {
  return (
    <ScrollView style={styles.container}>
      {/* Header */}
      <View style={styles.header}>
        <Ionicons name="filter" size={24} color="#fff" />
        <Text style={styles.headerTitle}>Moneyfy</Text>
        <View style={styles.headerIcons}>
          <Ionicons name="search" size={24} color="#fff" style={styles.icon} />
          <Ionicons name="person-circle" size={28} color="#fff" />
        </View>
      </View>

      {/* Month */}
      <Text style={styles.month}>April</Text>

      {/* Circular Chart */}
      <View style={styles.chartContainer}>
        <Svg height="200" width="200">
          <Circle
            cx="100"
            cy="100"
            r="90"
            stroke="#69b578"
            strokeWidth="15"
            fill="none"
          />
        </Svg>
        <View style={styles.chartCenter}>
          <Text style={styles.chartText}>$0.00</Text>
          <Text style={styles.chartSubText}>0.00</Text>
        </View>
      </View>

      {/* Categories */}
      {/* Categories Grid */}
        <View style={styles.categoriesGrid}>
        {categories.map((cat, index) => (
            <View key={index} style={styles.categoryItem}>
            <Ionicons name={cat.icon as any} size={28} color="#69b578" />
            <Text style={styles.categoryText}>{cat.name}</Text>
            </View>
        ))}
        </View>


      {/* Balance */}
      <Text style={styles.balance}>Balance: $0.00</Text>

      {/* Action Buttons */}
      <View style={styles.actions}>
        <TouchableOpacity style={[styles.actionBtn, styles.expenseBtn]}>
          <Ionicons name="remove-circle" size={32} color="#fff" />
          <Text style={styles.actionText}>Expense</Text>
        </TouchableOpacity>
        <TouchableOpacity style={[styles.actionBtn, styles.incomeBtn]}>
          <Ionicons name="add-circle" size={32} color="#fff" />
          <Text style={styles.actionText}>Income</Text>
        </TouchableOpacity>
      </View>
    </ScrollView>
  );
};

export default HomeScreen;

const styles = StyleSheet.create({
  container: { flex: 1, backgroundColor: "#f5f5f5" },
  header: {
    flexDirection: "row",
    justifyContent: "space-between",
    alignItems: "center",
    backgroundColor: "#69b578",
    paddingHorizontal: 15,
    paddingVertical: 10,
  },
  headerTitle: { color: "#fff", fontSize: 18, fontWeight: "bold" },
  headerIcons: { flexDirection: "row", alignItems: "center" },
  icon: { marginRight: 10 },
  month: {
    fontSize: 20,
    fontWeight: "600",
    textAlign: "center",
    marginVertical: 10,
    color: "#333",
  },
  chartContainer: { alignItems: "center", justifyContent: "center" },
  chartCenter: {
    position: "absolute",
    alignItems: "center",
    justifyContent: "center",
  },
  chartText: { fontSize: 22, fontWeight: "bold", color: "#333" },
  chartSubText: { fontSize: 16, color: "#666" },
  categories: { marginVertical: 20, paddingHorizontal: 10 },
  categoryItem: {
    width: "25%",       // 4 items per row
    alignItems: "center",
    marginVertical: 15,
    },
  categoriesGrid: {
    flexDirection: "row",
    flexWrap: "wrap",   // allows multiple rows
    justifyContent: "center",
    marginVertical: 20,
    },
  categoryText: { fontSize: 12, marginTop: 5, color: "#333" },
  balance: {
    textAlign: "center",
    fontSize: 16,
    fontWeight: "600",
    marginVertical: 10,
    color: "#333",
  },
  actions: {
    flexDirection: "row",
    justifyContent: "space-around",
    marginTop: 20,
  },
  actionBtn: {
    flexDirection: "row",
    alignItems: "center",
    padding: 15,
    borderRadius: 10,
  },
  expenseBtn: { backgroundColor: "#e74c3c" },
  incomeBtn: { backgroundColor: "#69b578" },
  actionText: { color: "#fff", marginLeft: 8, fontSize: 16 },
});
