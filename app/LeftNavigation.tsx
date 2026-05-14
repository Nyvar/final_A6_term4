// LeftNavigation.tsx
import React, { useState } from "react";
import {
  SafeAreaView,
  StyleSheet,
  Text,
  TouchableOpacity,
  View,
  Alert,
} from "react-native";
import { Ionicons } from "@expo/vector-icons";
import DateTimePickerModal from "react-native-modal-datetime-picker";

const intervals = ["Day", "Week", "Month", "Year", "All", "Interval", "Choose date"];
const themeGreen = "#69b578";

export default function LeftNavigation() {
  const [menuOpen, setMenuOpen] = useState(false);
  const [selected, setSelected] = useState<string | null>(null);

  const [showIncomeOptions, setShowIncomeOptions] = useState(false);
  const [paymentMethod, setPaymentMethod] = useState("Add Income");

  // date picker states
  const [showDatePicker, setShowDatePicker] = useState(false);
  const [showIntervalPicker, setShowIntervalPicker] = useState(false);
  const [chooseDate, setChooseDate] = useState<Date | null>(null);
  const [intervalStart, setIntervalStart] = useState<Date | null>(null);
  const [intervalEnd, setIntervalEnd] = useState<Date | null>(null);
  const [pickingStart, setPickingStart] = useState(true);

  const toggleMenu = () => setMenuOpen(!menuOpen);

  const handleConfirmDate = (date: Date) => {
    setChooseDate(date);
    setShowDatePicker(false);
  };

  const handleConfirmInterval = (date: Date) => {
    if (pickingStart) {
      setIntervalStart(date);
      setPickingStart(false); // next pick is end
    } else {
      if (intervalStart && date >= intervalStart) {
        setIntervalEnd(date);
        setShowIntervalPicker(false);
        setPickingStart(true); // reset for next time
      } else {
        Alert.alert("Invalid range", "End date must be after start date");
      }
    }
  };

  const formatDate = (d: Date | null) =>
    d ? d.toLocaleDateString("en-US", { month: "short", day: "numeric", year: "numeric" }) : "";

  return (
    <SafeAreaView style={styles.container}>
      {/* HEADER */}
      <View style={styles.header}>
        <View style={styles.leftHeader}>
          <TouchableOpacity onPress={toggleMenu} style={styles.menuButton}>
            <Ionicons name="menu" size={26} color="white" />
          </TouchableOpacity>

          <View style={styles.logoBlock}>
            <Text style={styles.logo}>Moneyfy</Text>
            <View style={styles.accountRow}>
              <Ionicons name="wallet" size={16} color="#d4ffd9" />
              <Text style={styles.accountText}> All accounts USD</Text>
            </View>
          </View>
        </View>

        <View style={styles.headerIcons}>
          <TouchableOpacity>
            <Ionicons name="search" size={22} color="white" />
          </TouchableOpacity>
          <TouchableOpacity>
            <Ionicons name="swap-horizontal" size={22} color="white" />
          </TouchableOpacity>
          <TouchableOpacity>
            <Ionicons name="ellipsis-vertical" size={22} color="white" />
          </TouchableOpacity>
        </View>
      </View>

      {/* BODY */}
      <View style={styles.body}>
        {menuOpen && (
          <View style={styles.overlay}>
            {/* Add Income button */}
            <View style={styles.addIncomeWrapper}>
              <TouchableOpacity
                style={[styles.intervalButton, styles.addIncomeButton]}
                onPress={() => setShowIncomeOptions(!showIncomeOptions)}
              >
                <Text style={styles.addIncomeText}>{paymentMethod}</Text>
                <Text style={styles.addIncomeSubText}>USD</Text>
              </TouchableOpacity>

              {showIncomeOptions && (
                <View style={styles.incomeOptions}>
                  <TouchableOpacity
                    style={styles.paymentOption}
                    onPress={() => {
                      setPaymentMethod("All Accounts");
                      setShowIncomeOptions(false);
                    }}
                  >
                    <Ionicons name="wallet-outline" size={24} color="#333" />
                    <View style={styles.optionLabel}>
                      <Text style={styles.paymentOptionText}>All Accounts</Text>
                      <Text style={styles.usdLabel}>USD</Text>
                    </View>
                  </TouchableOpacity>

                  <TouchableOpacity
                    style={styles.paymentOption}
                    onPress={() => {
                      setPaymentMethod("Cash");
                      setShowIncomeOptions(false);
                    }}
                  >
                    <Ionicons name="cash-outline" size={24} color="#333" />
                    <View style={styles.optionLabel}>
                      <Text style={styles.paymentOptionText}>Cash</Text>
                      <Text style={styles.usdLabel}>USD</Text>
                    </View>
                  </TouchableOpacity>

                  <TouchableOpacity
                    style={styles.paymentOption}
                    onPress={() => {
                      setPaymentMethod("Payment Card");
                      setShowIncomeOptions(false);
                    }}
                  >
                    <Ionicons name="card-outline" size={24} color="#333" />
                    <View style={styles.optionLabel}>
                      <Text style={styles.paymentOptionText}>Payment Card</Text>
                      <Text style={styles.usdLabel}>USD</Text>
                    </View>
                  </TouchableOpacity>
                </View>
              )}
            </View>

            {/* Interval buttons */}
            <View style={styles.intervalPanel}>
              {intervals.map((item) => (
                <TouchableOpacity
                  key={item}
                  style={[
                    styles.intervalButton,
                    selected === item && styles.intervalButtonSelected,
                  ]}
                  onPress={() => {
                    setSelected(item);
                    if (item === "Choose date") {
                      setShowDatePicker(true);
                    } else if (item === "Interval") {
                      setShowIntervalPicker(true);
                    }
                  }}
                >
                  <Text
                    style={[
                      styles.intervalText,
                      selected === item && styles.intervalTextSelected,
                    ]}
                  >
                    {item === "Choose date" && chooseDate
                      ? formatDate(chooseDate)
                      : item === "Interval" && intervalStart && intervalEnd
                      ? `${formatDate(intervalStart)} - ${formatDate(intervalEnd)}`
                      : item}
                  </Text>
                </TouchableOpacity>
              ))}
            </View>
          </View>
        )}
      </View>

      {/* Single date picker */}
      <DateTimePickerModal
        isVisible={showDatePicker}
        mode="date"
        onConfirm={handleConfirmDate}
        onCancel={() => setShowDatePicker(false)}
      />

      {/* Interval picker (two-step) */}
      <DateTimePickerModal
        isVisible={showIntervalPicker}
        mode="date"
        onConfirm={handleConfirmInterval}
        onCancel={() => {
          setShowIntervalPicker(false);
          setPickingStart(true);
        }}
      />
    </SafeAreaView>
  );
}

const styles = StyleSheet.create({
  container: { flex: 1, backgroundColor: "#f3f1f1" },
  header: {
    backgroundColor: themeGreen,
    paddingHorizontal: 16,
    paddingVertical: 20,
    flexDirection: "row",
    justifyContent: "space-between",
    alignItems: "center",
  },
  leftHeader: { flexDirection: "row", alignItems: "center" },
  menuButton: { marginRight: 6 },
  logoBlock: { flexDirection: "column" },
  logo: { color: "white", fontSize: 22, fontWeight: "bold" },
  accountRow: { flexDirection: "row", marginTop: 2 },
  accountText: { color: "#d4ffd9", fontSize: 14 },
  headerIcons: { flexDirection: "row", gap: 15 },

  body: { flex: 1 },

  overlay: {
    position: "absolute",
    top: 0,
    left: 0,
    width: "50%",
    height: "100%",
    backgroundColor: "#fafafa",
    paddingTop: 20,
    paddingHorizontal: 10,
    borderRightWidth: 1,
    borderRightColor: "#eee",
  },

  addIncomeWrapper: { marginBottom: 40 },

  intervalPanel: { flexDirection: "column" },

  intervalButton: {
    paddingVertical: 8,
    paddingHorizontal: 16,
    borderRadius: 6,
    borderWidth: 1,
    borderColor: themeGreen,
    marginBottom: 10,
    backgroundColor: "#fbfafa",
    alignItems: "center",
  },
  intervalButtonSelected: { backgroundColor: "#e8f5e9" },

  intervalText: { fontSize: 15, color: "#333" },
  intervalTextSelected: { color: themeGreen, fontWeight: "bold" },

  addIncomeButton: {},
  addIncomeText: { fontSize: 16, color: "#333", fontWeight: "bold" },
  addIncomeSubText: { fontSize: 13, color: themeGreen, marginTop: 2 },

  incomeOptions: {
    backgroundColor: "#fff",
    borderRadius: 6,
    padding: 10,
    marginTop: 6,
    elevation: 4,
  },
  paymentOption: {
    flexDirection: "row",
    alignItems: "center",
    paddingVertical: 8,
  },
  optionLabel: { flexDirection: "column", marginLeft: 8 },
  paymentOptionText: { fontSize: 16, color: "#333" },
  usdLabel: { fontSize: 11, color: "#666" },
});
