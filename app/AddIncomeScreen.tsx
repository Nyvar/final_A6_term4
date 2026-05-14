// app/AddIncomeScreen.tsx
import { Ionicons } from "@expo/vector-icons";
import React, { useState } from "react";
import {
  StyleSheet,
  Text,
  TextInput,
  TouchableOpacity,
  View,
} from "react-native";

export default function AddIncomeScreen() {
  const [amount, setAmount] = useState("");
  const [note, setNote] = useState("");
  const [showCategories, setShowCategories] = useState(false);
  const [category, setCategory] = useState("");
  const [paymentMethod, setPaymentMethod] = useState("");
  const [showPaymentOptions, setShowPaymentOptions] = useState(false);

  const [categories, setCategories] = useState(["Salary", "Deposit", "Saving"]);

  const [amountError, setAmountError] = useState(false);
  const [noteError, setNoteError] = useState(false);

  const [addingCategory, setAddingCategory] = useState(false);
  const [newCategoryName, setNewCategoryName] = useState("");

  const handleKeyPress = (key: string) => {
    if (["+", "-", "*", "/"].includes(key)) {
      setAmount(amount + key);
    } else if (key === "=") {
      try {
        setAmount(eval(amount).toString());
      } catch {
        setAmount("Error");
      }
    } else if (key === "C") {
      setAmount("");
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
    year: "numeric",
  };
  const formattedDate = today.toLocaleDateString("en-US", options);

  const addCategory = () => {
    if (newCategoryName.trim() !== "") {
      setCategories([...categories, newCategoryName.trim()]);
      setNewCategoryName("");
      setAddingCategory(false); // return to normal note box
    }
  };

  const handleChooseCategory = () => {
    let hasError = false;

    if (!amount || amount === "0") {
      setAmountError(true);
      hasError = true;
    } else {
      setAmountError(false);
    }

    if (!note) {
      setNoteError(true);
      hasError = true;
    } else {
      setNoteError(false);
    }

    if (!hasError) {
      setShowCategories(true);
    }
  };

  const handleSelectCategory = (cat: string) => {
    setCategory(cat);
    setAmount("");
    setNote("");
    setShowCategories(false);
  };

  return (
    <View style={styles.container}>
      <Text style={styles.date}>{formattedDate}</Text>

      <View style={styles.amountWrapper}>
        {showPaymentOptions && (
          <View style={styles.paymentOptions}>
            <TouchableOpacity
              style={styles.paymentOption}
              onPress={() => {
                setPaymentMethod("cash");
                setShowPaymentOptions(false);
              }}
            >
              <Ionicons name="cash-outline" size={20} color="#333" />
              <Text style={styles.paymentOptionText}>Cash USD</Text>
            </TouchableOpacity>

            <TouchableOpacity
              style={styles.paymentOption}
              onPress={() => {
                setPaymentMethod("card");
                setShowPaymentOptions(false);
              }}
            >
              <Ionicons name="card-outline" size={20} color="#333" />
              <Text style={styles.paymentOptionText}>Payment Card USD</Text>
            </TouchableOpacity>
          </View>
        )}


        <View
          style={[
            styles.amountBox,
            amountError && { borderColor: "red", borderWidth: 2 },
          ]}
        >
          <TouchableOpacity onPress={() => setShowPaymentOptions(!showPaymentOptions)}>
            <View style={styles.iconWithLabel}>
              {paymentMethod === "" && (
                <Ionicons name="cash-outline" size={28} color="#fff" />
              )}
              {paymentMethod === "cash" && (
                <Ionicons name="cash-outline" size={28} color="#fff" />
              )}
              {paymentMethod === "card" && (
                <Ionicons name="card-outline" size={28} color="#fff" />
              )}
              <Text style={styles.usdLabel}>USD</Text>
            </View>
          </TouchableOpacity>

          <Text style={styles.divider}>|</Text>

          <Text style={styles.amount}>{displayAmount}</Text>
          <TouchableOpacity onPress={() => setAmount(amount.slice(0, -1))}>
            <Ionicons
              name="backspace-outline"
              size={28}
              color="#fff"
              style={{ marginLeft: 8 }}
            />
          </TouchableOpacity>
        </View>
      </View>

      {/* Note Input OR Enter Category Name */}
      <View style={styles.noteWrapper}>
        {!addingCategory ? (
          <>
            <TextInput
              style={[
                styles.noteInput,
                noteError && { borderColor: "red", borderWidth: 2 },
              ]}
              value={note}
              onChangeText={(text) => {
                setNote(text);
                if (text !== "") setNoteError(false);
              }}
            />
            {note.length === 0 && (
              <View style={styles.notePlaceholder}>
                <Ionicons name="create-outline" size={20} color="#999" />
                <Text style={styles.notePlaceholderText}>Add Note</Text>
              </View>
            )}
          </>
        ) : (
          <TextInput
            style={styles.noteInput}
            value={newCategoryName}
            onChangeText={setNewCategoryName}
            placeholder="Enter category name"
            placeholderTextColor="#999"
            onSubmitEditing={addCategory}
          />
        )}
      </View>

      {!showCategories && (
        <View style={styles.keypad}>
          {keypad.map((row, i) => (
            <View key={i} style={styles.keypadRow}>
              {row.map((key) => (
                <TouchableOpacity
                  key={key}
                  style={styles.key}
                  onPress={() => handleKeyPress(key)}
                >
                  <Text style={styles.keyText}>{key}</Text>
                </TouchableOpacity>
              ))}
            </View>
          ))}
        </View>
      )}

      <View style={styles.categorySection}>
        {!showCategories && (
          <TouchableOpacity
            style={styles.categoryButton}
            onPress={handleChooseCategory}
          >
            <Text style={styles.categoryButtonText}>CHOOSE CATEGORY</Text>
          </TouchableOpacity>
        )}

        {showCategories && (
          <View style={styles.categoryBox}>
            {categories.map((cat) => (
              <TouchableOpacity
                key={cat}
                style={[
                  styles.categoryOption,
                  category === cat && styles.categorySelected,
                ]}
                onPress={() => handleSelectCategory(cat)}
              >
                <View style={styles.categoryItem}>
                  <Ionicons name="albums-outline" size={20} color="#333" />
                  <Text style={styles.categoryText}>{cat}</Text>
                </View>
              </TouchableOpacity>
            ))}


            {/* Add new category button */}
            <TouchableOpacity
              style={styles.addCategoryButton}
              onPress={() => setAddingCategory(true)}
            >
              <Ionicons name="add-circle-outline" size={28} color="#51d191" />
            </TouchableOpacity>
          </View>
        )}
      </View>
    </View>
  );
}

const styles = StyleSheet.create({
  container: { flex: 1, padding: 20, backgroundColor: "#e7f0e9" },
  date: { fontSize: 16, color: "#555", textAlign: "center", marginBottom: 60 },
  amountWrapper: { position: "relative" },
  amountBox: {
    flexDirection: "row",
    alignItems: "center",
    marginBottom: 20,
    backgroundColor: "#51d191",
    paddingHorizontal: 20,
    borderRadius: 3,
    height: 80,
  },
  amount: { fontSize: 28, color: "#fff", fontWeight: "bold", flex: 1 },
  divider: { fontSize: 28, color: "#fff", marginHorizontal: 8 },
  iconWithLabel: { alignItems: "center", marginRight: 8 },
  usdLabel: { fontSize: 12, color: "#fff", marginTop: 2 },
  paymentOptions: {
    position: "absolute",
    bottom: "100%", 
    left: 20, 
    backgroundColor: "#fff",
    borderRadius: 6,
    padding: 10,
    marginBottom: 6,
    zIndex: 10,
    alignSelf: "flex-start", 
  },
  paymentOption: {
    flexDirection: "row",
    alignItems: "center",
    paddingVertical: 8,
  },
  paymentOptionText: {
    marginLeft: 8,
    fontSize: 16,
    color: "#333",
  },
  noteWrapper: {
    position: "relative",
    marginBottom: 100,
  },
  noteInput: {
    borderWidth: 1,
    borderColor: "#ccc",
    padding: 10,
    borderRadius: 3,
    color: "#333",
  },
  notePlaceholder: {
    position: "absolute",
    left: 12,
    top: 10,
    flexDirection: "row",
    alignItems: "center",
  },
  notePlaceholderText: {
    marginLeft: 6,
    color: "#999",
  },
  keypad: { marginBottom: 10 },
  keypadRow: {
    flexDirection: "row",
    justifyContent: "space-around",
    marginBottom: 5,
  },
  key: {
    width: 80,
    height: 60,
    justifyContent: "center",
    alignItems: "center",
    borderWidth: 2,
    borderColor: "#51d191",
    borderRadius: 6,
    backgroundColor: "#f2f2f2",
  },
  keyText: { fontSize: 24, color: "#333" },
  categorySection: { marginTop: 10 },
  categoryButton: {
    padding: 18,
    backgroundColor: "#51d191",
    alignItems: "center",
    borderRadius: 6,
  },
  categoryButtonText: { fontSize: 16, fontWeight: "bold", color: "#fff" },
  categoryBox: {
    flexDirection: "row",
    flexWrap: "wrap",
    justifyContent: "space-around",
    marginTop: 8,
  },
  categoryOption: {
    padding: 10,
    borderWidth: 1,
    borderColor: "#ccc",
    borderRadius: 6,
    minWidth: 100,
    alignItems: "center",
    marginBottom: 10,
  },
  categorySelected: { backgroundColor: "#51d191" },
  categoryItem: {
    flexDirection: "row",
    alignItems: "center",
  },
  categoryText: { marginLeft: 6, color: "#000" },
  addCategoryButton: {
    justifyContent: "center",
    alignItems: "center",
    marginLeft: 10,
  },
});
