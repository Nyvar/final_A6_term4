import React, { useState } from "react";

import {
  Dimensions,
  Image,
  SafeAreaView,
  StyleSheet,
  Text,
  TouchableOpacity,
  View,
} from "react-native";

import { FontAwesome5, Ionicons, MaterialIcons } from "@expo/vector-icons";

const { width, height } = Dimensions.get("window");

export default function App() {
  const [menuOpen, setMenuOpen] = useState(false);

  const toggleMenu = () => {
    setMenuOpen(!menuOpen);
  };

  return (
    <SafeAreaView style={styles.container}>
      {/* HEADER */}
      <View style={styles.header}>
        <View>
          <Text style={styles.logo}>moneyfy</Text>
          <View style={styles.accountRow}>
            <Ionicons name="wallet" size={16} color="#d4ffd9" />
            <Text style={styles.accountText}> All accounts</Text>
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
        {/* LEFT SIDE - The main radial area */}
        <View style={styles.leftPanel}>
          {/* THE DISK (Using your Ellipse 70.png) */}
          <Image
            source={require("../assets/images/Ellipse70.png")}
            style={{ width: 250, height: 500, marginTop: 150, margin: -40 }}
          />

          {/* ICONS SURROUNDING THE DISK */}
          {/* Positioned manually to follow the curve */}
          <TouchableOpacity
            style={[styles.categoryIcon, { top: "10%", left: "10%" }]}
          >
            <FontAwesome5 name="car" size={26} color="#444" />
          </TouchableOpacity>

          <TouchableOpacity
            style={[styles.categoryIcon, { top: "22%", left: "45%" }]}
          >
            <Ionicons name="restaurant" size={26} color="#444" />
          </TouchableOpacity>

          <TouchableOpacity
            style={[styles.categoryIcon, { top: "45%", left: "60%" }]}
          >
            <MaterialIcons name="sports-soccer" size={26} color="#444" />
          </TouchableOpacity>

          <TouchableOpacity
            style={[styles.categoryIcon, { top: "68%", left: "45%" }]}
          >
            <MaterialIcons name="pets" size={26} color="#444" />
          </TouchableOpacity>

          <TouchableOpacity
            style={[styles.categoryIcon, { top: "80%", left: "10%" }]}
          >
            <Ionicons name="gift" size={26} color="#444" />
          </TouchableOpacity>

          {/* THE CENTRAL ADD BUTTON */}
          <TouchableOpacity style={styles.fab}>
            <Ionicons name="add" size={40} color="#6aa97a" />
          </TouchableOpacity>

          {/* BOTTOM BAR CONTROLS */}
          <View style={styles.bottomLeft}>
            <View style={styles.balanceDisplay}>
              <Text style={styles.balanceText}>$ 0.00</Text>
            </View>
            <TouchableOpacity onPress={toggleMenu}>
              <Ionicons name="menu" size={30} color="#6aa97a" />
            </TouchableOpacity>
          </View>
        </View>

        {/* RIGHT DRAWER MENU */}
        {menuOpen && (
          <View style={styles.rightPanel}>
            <MenuItem icon="grid-outline" label="Categories" />
            <MenuItem icon="wallet-outline" label="Accounts" />
            <MenuItem icon="cash-outline" label="Currencies" />
            <MenuItem icon="settings-outline" label="Setting" />
            <MenuItem icon="school-outline" label="Guides" />
          </View>
        )}
      </View>
    </SafeAreaView>
  );
}

function MenuItem({ icon, label }) {
  return (
    <TouchableOpacity style={styles.menuItem}>
      <Ionicons name={icon} size={28} color="#6aa97a" />
      <Text style={styles.menuText}>{label}</Text>
    </TouchableOpacity>
  );
}

const styles = StyleSheet.create({
  container: {
    flex: 1,
    backgroundColor: "#fff",
  },
  header: {
    backgroundColor: "#69b578",
    padding: 20,
    flexDirection: "row",
    justifyContent: "space-between",
    alignItems: "center",
  },
  logo: {
    color: "white",
    fontSize: 22,
    fontWeight: "bold",
  },
  accountRow: {
    flexDirection: "row",
    marginTop: 4,
  },
  accountText: {
    color: "#d4ffd9",
    fontSize: 14,
  },
  headerIcons: {
    flexDirection: "row",
    gap: 15,
  },
  body: {
    flex: 1,
    flexDirection: "row",
  },
  leftPanel: {
    flex: 1,
    backgroundColor: "#f5f5f5",
    position: "relative",
  },
  ellipseBg: {
    position: "absolute",
    right: -width * 0.45, // Moves the "Ellipse 70.png" to the center-right
    top: "5%",
    bottom: "5%",
    width: width,
    height: "90%",
  },
  categoryIcon: {
    position: "absolute",
    width: 50,
    height: 50,
    backgroundColor: "white",
    borderRadius: 25,
    justifyContent: "center",
    alignItems: "center",
    elevation: 4,
    shadowColor: "#000",
    shadowOffset: { width: 0, height: 2 },
    shadowOpacity: 0.2,
    shadowRadius: 3,
  },
  fab: {
    position: "absolute",
    right: -10, // Positioned on the edge of the disk
    top: "45%",
    width: 70,
    height: 70,
    borderRadius: 35,
    backgroundColor: "white",
    justifyContent: "center",
    alignItems: "center",
    borderWidth: 4,
    borderColor: "#e8f5e9",
    elevation: 8,
  },
  bottomLeft: {
    position: "absolute",
    bottom: 25,
    left: 20,
    right: 20,
    flexDirection: "row",
    justifyContent: "space-between",
    alignItems: "center",
  },
  balanceDisplay: {
    backgroundColor: "#9ac7a5",
    paddingVertical: 8,
    paddingHorizontal: 20,
    borderRadius: 8,
  },
  balanceText: {
    color: "white",
    fontWeight: "bold",
  },
  rightPanel: {
    width: 120,
    backgroundColor: "#fafafa",
    paddingTop: 30,
    borderLeftWidth: 1,
    borderLeftColor: "#eee",
  },
  menuItem: {
    alignItems: "center",
    marginBottom: 30,
  },
  menuText: {
    marginTop: 5,
    fontSize: 12,
    color: "#333",
  },
});
