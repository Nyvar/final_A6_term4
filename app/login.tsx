// app/login.tsx
import { router } from 'expo-router';
import React, { useEffect, useState } from 'react';
import { StyleSheet, Text, TextInput, TouchableOpacity, View, Alert, Image, ActivityIndicator } from 'react-native';
import { Ionicons } from "@expo/vector-icons";
import AsyncStorage from '@react-native-async-storage/async-storage';
import { getApiBaseUrl } from '@/constants/api';
import { login, getStoredToken } from '@/services/auth';

export default function LoginScreen() {
    const [username, setUsername] = useState('');
    const [password, setPassword] = useState('');
    const [loading, setLoading] = useState(false);
    const [passwordVisible, setPasswordVisible] = useState(false);
    
    const doLogin = async () => {
        if (!username.trim() || !password) {
            Alert.alert('Error', 'Please enter both username and password.');
            return;
        }

        setLoading(true);
        try {
            const result = await login(username, password);
            
            if (result.ok) {
                Alert.alert('Success', `Login successful! Welcome ${username}`);
                router.replace('/homePage');
            } else {
                Alert.alert('Login Failed', result.message);
            }
        } catch (error) {
            console.error('Login error:', error);
            Alert.alert('Error', 'Network error. Please try again.');
        } finally {
            setLoading(false);
        }
    }

    const handleDemoLogin = async () => {
        setLoading(true);
        try {
            // For demo, you can use a test account
            const result = await login('demo', 'demo123');
            if (result.ok) {
                Alert.alert('Demo Login', 'Logged in as demo user');
                router.replace('/homePage');
            } else {
                Alert.alert('Demo Login Failed', result.message);
            }
        } catch (error) {
            console.error('Demo login error:', error);
            Alert.alert('Demo Login Failed', 'Unable to start demo login.');
        } finally {
            setLoading(false);
        }
    };
    
    useEffect(() => {
        // Check if user is already logged in
        const checkUser = async () => {
            try {
                const token = await getStoredToken();
                if (token) {
                    router.replace('/homePage');
                }
            } catch (error) {
                console.error('Error checking user:', error);
            }
        };
        
        checkUser();
    }, []);
    
    return (
        <View style={styles.container}>
            {/* HEADER */}
            <View style={styles.header}>
                <Text style={styles.title}>moneyfy</Text>
                <Text style={styles.subtitle}>LOGIN</Text>
            </View>

            {/* CONTENT */}
            <View style={styles.content}>
                {/* IMAGE */}
                <Image
                    source={require("../assets/images/flying-money.png")}
                    style={styles.image}
                    resizeMode="contain"
                />

                {/* USERNAME INPUT */}
                <TextInput
                    placeholder="Username"
                    placeholderTextColor="#999"
                    textAlign="center"
                    style={styles.input}
                    value={username}
                    onChangeText={setUsername}
                    autoCapitalize="none"
                    autoCorrect={false}
                    editable={!loading}
                />

                {/* PASSWORD INPUT */}
                <View style={styles.passwordContainer}>
                    <TextInput
                        placeholder="Password"
                        placeholderTextColor="#999"
                        textAlign="center"
                        secureTextEntry={!passwordVisible}
                        style={styles.passwordInput}
                        value={password}
                        onChangeText={setPassword}
                        editable={!loading}
                    />
                    <TouchableOpacity
                        onPress={() => setPasswordVisible(!passwordVisible)}
                        disabled={loading}
                    >
                        <Ionicons
                            name={passwordVisible ? "eye" : "eye-off"}
                            size={20}
                            color="#555"
                        />
                    </TouchableOpacity>
                </View>

                {/* LOGIN BUTTON */}
                <TouchableOpacity 
                    style={[styles.button, loading && styles.buttonDisabled]} 
                    onPress={doLogin}
                    disabled={loading}
                >
                    {loading ? (
                        <ActivityIndicator size="small" color="#fff" />
                    ) : (
                        <Text style={styles.buttonText}>Login</Text>
                    )}
                </TouchableOpacity>
                <TouchableOpacity 
                    style={[styles.demoButton, loading && styles.buttonDisabled]} 
                    onPress={handleDemoLogin}
                    disabled={loading}
                >
                    <Text style={styles.demoButtonText}>Use Demo Account</Text>
                </TouchableOpacity>
            </View>
        </View>
    )
}

const styles = StyleSheet.create({
    container: {
        flex: 1,
        backgroundColor: "#ffffff",
        justifyContent: "space-between",
    },
    centerContent: {
        justifyContent: "center",
        alignItems: "center",
    },
    loadingText: {
        marginTop: 10,
        fontSize: 14,
        color: "#666",
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
        flex: 1,
        alignItems: "center",
        paddingHorizontal: 30,
        justifyContent: "center",
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
        fontSize: 16,
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
        fontSize: 16,
    },
    button: {
        backgroundColor: "#7BC96F",
        paddingVertical: 12,
        paddingHorizontal: 40,
        borderRadius: 25,
        minWidth: 150,
        alignItems: "center",
    },
    buttonDisabled: {
        backgroundColor: "#999",
    },
    buttonText: {
        fontSize: 16,
        fontWeight: "bold",
        color: "#fff",
    },
    demoButton: {
        width: "100%",
        backgroundColor: "#ffffff",
        borderColor: "#7BC96F",
        borderWidth: 2,
        borderRadius: 25,
        paddingVertical: 12,
        paddingHorizontal: 40,
        marginTop: 10,
        alignItems: "center",
    },
    demoButtonText: {
        fontSize: 16,
        fontWeight: "bold",
        color: "#7BC96F",
    },
});