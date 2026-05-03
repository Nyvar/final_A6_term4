import { Ionicons } from "@expo/vector-icons";
import React, { useState } from "react";
import {
    Image,
    SafeAreaView,
    StyleSheet,
    Text,
    TextInput,
    TouchableOpacity,
    View,
} from "react-native";

export default function App() {
  const [passwordVisible, setPasswordVisible] = useState(false);

  return (
    <SafeAreaView style={styles.container}>
      {/* HEADER */}
      <View style={styles.header}>
        <Text style={styles.title}>moneyfy</Text>
        <Text style={styles.subtitle}>LOGIN</Text>
      </View>

      {/* CONTENT */}
      <View style={styles.content}>
        {/* IMAGE */}
        <Image
          source={require("../assets/images/money.png")} // 👉 replace with your image
          style={styles.image}
          resizeMode="contain"
        />

        {/* EMAIL INPUT */}
        <TextInput
          placeholder="Email"
          placeholderTextColor="#999"
          textAlign="center"
          style={styles.input}
        />

        {/* PASSWORD INPUT */}
        <View style={styles.passwordContainer}>
          <TextInput
            placeholder="Password"
            placeholderTextColor="#999"
            textAlign="center"
            secureTextEntry={!passwordVisible}
            style={styles.passwordInput}
          />
          <TouchableOpacity
            onPress={() => setPasswordVisible(!passwordVisible)}
          >
            <Ionicons
              name={passwordVisible ? "eye" : "eye-off"}
              size={20}
              color="#555"
            />
          </TouchableOpacity>
        </View>

        {/* LOGIN BUTTON */}
        <TouchableOpacity style={styles.button}>
          <Text style={styles.buttonText}>Login</Text>
        </TouchableOpacity>
      </View>

      {/* FOOTER */}
      <Text style={styles.footer}>POWER BY OEUN VICHHEKA</Text>
    </SafeAreaView>
  );
}

const styles = StyleSheet.create({
  container: {
    flex: 1,
    backgroundColor: "#ffffff",
    justifyContent: "space-between",
  },

  header: {
    backgroundColor: "#7BE26D",
    paddingVertical: 40,
    alignItems: "center",
    borderBottomLeftRadius: 20,
    borderBottomRightRadius: 20,
  },

  title: {
    fontSize: 28,
    fontWeight: "bold",
    color: "#fff",
    fontStyle: "italic",
  },

  subtitle: {
    fontSize: 18,
    color: "#fff",
    marginTop: 5,
    letterSpacing: 2,
  },

  content: {
    alignItems: "center",
    paddingHorizontal: 30,
  },

  image: {
    width: 150,
    height: 150,
    marginVertical: 20,
  },

  input: {
    width: "100%",
    backgroundColor: "#e0e0e0",
    borderRadius: 10,
    padding: 12,
    marginBottom: 15,
  },

  passwordContainer: {
    width: "100%",
    flexDirection: "row",
    alignItems: "center",
    backgroundColor: "#e0e0e0",
    borderRadius: 10,
    paddingHorizontal: 10,
    marginBottom: 20,
  },

  passwordInput: {
    flex: 1,
    padding: 12,
  },

  button: {
    backgroundColor: "#7BC96F",
    paddingVertical: 12,
    paddingHorizontal: 40,
    borderRadius: 25,
  },

  buttonText: {
    fontSize: 16,
    fontWeight: "bold",
    color: "#000",
  },

  footer: {
    textAlign: "center",
    marginBottom: 15,
    fontSize: 12,
    color: "#000",
  },
});
