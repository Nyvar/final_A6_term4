// app/profile.tsx
import { StyleSheet, Text, View, TouchableOpacity, Image, Alert, ActivityIndicator, TextInput, Modal } from 'react-native'
import React, { useState, useEffect } from 'react'
import { router } from "expo-router";
import { logout } from '@/services/auth';
import { getProfile, updateProfile, getDashboard } from '@/services/api';

export default function Profile() {
  const [userData, setUserData] = useState<any>(null);
  const [loading, setLoading] = useState(true);
  const [editModalVisible, setEditModalVisible] = useState(false);
  const [editUsername, setEditUsername] = useState('');
  const [editEmail, setEditEmail] = useState('');
  const [updating, setUpdating] = useState(false);
  const [dashboardData, setDashboardData] = useState<any>(null);

  useEffect(() => {
    loadUserData();
    loadDashboard();
  }, []);

  const loadUserData = async () => {
    try {
      setLoading(true);
      const response = await getProfile();
      if (response && response.success && response.data) {
        setUserData(response.data);
        setEditUsername(response.data.username || '');
        setEditEmail(response.data.email || '');
      }
    } catch (error) {
      console.error('Error loading user data:', error);
      Alert.alert('Error', 'Failed to load profile data');
    } finally {
      setLoading(false);
    }
  };

  const loadDashboard = async () => {
    try {
      const response = await getDashboard('USD', 'month');
      if (response && response.success && response.data) {
        setDashboardData(response.data);
      }
    } catch (error) {
      console.error('Error loading dashboard:', error);
    }
  };

  const handleUpdateProfile = async () => {
    if (!editUsername.trim()) {
      Alert.alert('Error', 'Username cannot be empty');
      return;
    }

    setUpdating(true);
    try {
      const response = await updateProfile(editUsername.trim(), editEmail.trim());
      if (response && response.success) {
        Alert.alert('Success', 'Profile updated successfully');
        await loadUserData();
        setEditModalVisible(false);
      } else {
        Alert.alert('Error', response?.message || 'Failed to update profile');
      }
    } catch (error) {
      console.error('Error updating profile:', error);
      Alert.alert('Error', 'Network error');
    } finally {
      setUpdating(false);
    }
  };

  const handleLogout = async () => {
    Alert.alert(
      "Logout",
      "Are you sure you want to logout?",
      [
        { text: "Cancel", style: "cancel" },
        {
          text: "Logout",
          onPress: async () => {
            try {
              await logout();
              router.replace('/login');
            } catch (error) {
              console.error('Error during logout:', error);
              Alert.alert('Error', 'Failed to logout. Please try again.');
            }
          },
          style: "destructive"
        }
      ]
    );
  };

  const handleBackToHome = () => {
    router.push('/');
  };

  if (loading) {
    return (
      <View style={styles.loadingContainer}>
        <ActivityIndicator size="large" color="#4CBF72" />
      </View>
    );
  }

  const totalBalance = dashboardData?.total_balance || 0;
  const currencySymbol = dashboardData?.currency_symbol || '$';

  return (
    <View style={styles.container}>
      <View style={styles.container1}>
        {/* Money Image Button - Click to go back to homepage */}
        <TouchableOpacity 
          style={styles.profileContainer}
          onPress={handleBackToHome}
          activeOpacity={0.7}
        >
          <Image
            source={require("../assets/images/flying-money.png")}
            style={styles.avatarIcon}
            resizeMode="contain"
          />
        </TouchableOpacity>
        
        <Text style={styles.userName}>
          {userData?.username || 'User'}
        </Text>
        <Text style={styles.userId}>
          ID: {userData?.user_id || userData?.id || 'N/A'}
        </Text>
        
        <TouchableOpacity 
          style={styles.buttonContainer}
          onPress={() => setEditModalVisible(true)}
        >
          <Text style={styles.buttonText}>Edit Profile</Text>
        </TouchableOpacity>

        <View style={styles.line} />
        <Text style={styles.infoText}>Username: {userData?.username || 'N/A'}</Text>

        <View style={styles.line} />
        <Text style={styles.infoText}>Email: {userData?.email || 'N/A'}</Text>

        <View style={styles.line} />
        <Text style={styles.infoText}>Wallet Balance: {currencySymbol}{totalBalance.toFixed(2)}</Text>

        <View style={styles.line} />
        <Text style={styles.infoText}>Default Currency: USD</Text>
        
        <View style={styles.line} />

        <TouchableOpacity 
          style={styles.logoutButton}
          onPress={handleLogout}
        >
          <Text style={styles.logoutText}>Logout</Text>
        </TouchableOpacity>
      </View>

      {/* Edit Profile Modal */}
      <Modal
        visible={editModalVisible}
        animationType="slide"
        transparent={true}
        onRequestClose={() => setEditModalVisible(false)}
      >
        <View style={styles.modalOverlay}>
          <View style={styles.modalContent}>
            <Text style={styles.modalTitle}>Edit Profile</Text>
            
            <TextInput
              style={styles.modalInput}
              placeholder="Username"
              value={editUsername}
              onChangeText={setEditUsername}
              autoCapitalize="none"
            />
            
            <TextInput
              style={styles.modalInput}
              placeholder="Email"
              value={editEmail}
              onChangeText={setEditEmail}
              keyboardType="email-address"
              autoCapitalize="none"
            />
            
            <View style={styles.modalButtons}>
              <TouchableOpacity 
                style={[styles.modalButton, styles.cancelModalButton]}
                onPress={() => setEditModalVisible(false)}
              >
                <Text style={styles.cancelModalText}>Cancel</Text>
              </TouchableOpacity>
              
              <TouchableOpacity 
                style={[styles.modalButton, styles.saveModalButton]}
                onPress={handleUpdateProfile}
                disabled={updating}
              >
                {updating ? (
                  <ActivityIndicator size="small" color="#fff" />
                ) : (
                  <Text style={styles.saveModalText}>Save</Text>
                )}
              </TouchableOpacity>
            </View>
          </View>
        </View>
      </Modal>
    </View>
  )
}

const styles = StyleSheet.create({
  container: {
    flex: 1,
    backgroundColor: "#4CBF72",
  },
  loadingContainer: {
    flex: 1,
    justifyContent: 'center',
    alignItems: 'center',
    backgroundColor: '#fff',
  },
  container1: {
    flex: 1,
    backgroundColor: '#ffff',
    marginTop: 60,
    borderTopLeftRadius: 30,
    borderTopRightRadius: 30,
  },
  profileContainer: {
    alignItems: 'center',
    justifyContent: 'center',
    marginTop: -40,
    width: 100,
    height: 100,
    borderRadius: 50,
    backgroundColor: '#4CBF72',
    alignSelf: 'center',
  },
  avatarIcon: {
    width: 60,
    height: 60,
  },
  userName: {
    fontSize: 24,
    fontWeight: 'bold',
    marginTop: 15,
    alignSelf: 'center',
    color: '#333',
  },
  userId: {
    fontSize: 14,
    color: '#666',
    marginTop: 5,
    alignSelf: 'center',
  },
  line: {
    height: 1,
    width: '90%',
    backgroundColor: '#4CBF72',
    marginVertical: 15,
    alignSelf: 'center',
  },
  infoText: {
    fontSize: 16,
    color: '#333',
    fontWeight: '500',
    marginLeft: 20,
  },
  buttonContainer: {
    height: 40,
    width: 120,
    borderRadius: 20,
    backgroundColor: '#4CBF72',
    alignItems: 'center',
    justifyContent: 'center',
    alignSelf: 'center',
    marginTop: 20,
  },
  buttonText: {
    fontSize: 14,
    color: '#fff',
    fontWeight: 'bold'
  },
  logoutButton: {
    height: 50,
    width: 200,
    borderRadius: 25,
    backgroundColor: '#ff6b6b',
    alignItems: 'center',
    justifyContent: 'center',
    alignSelf: 'center',
    marginTop: 30,
    marginBottom: 30,
  },
  logoutText: {
    fontSize: 16,
    color: '#fff',
    fontWeight: 'bold'
  },
  modalOverlay: {
    flex: 1,
    backgroundColor: 'rgba(0,0,0,0.5)',
    justifyContent: 'center',
    alignItems: 'center',
  },
  modalContent: {
    backgroundColor: '#fff',
    borderRadius: 20,
    padding: 20,
    width: '85%',
    alignItems: 'center',
  },
  modalTitle: {
    fontSize: 20,
    fontWeight: 'bold',
    marginBottom: 20,
    color: '#333',
  },
  modalInput: {
    width: '100%',
    borderWidth: 1,
    borderColor: '#ddd',
    borderRadius: 10,
    padding: 12,
    marginBottom: 15,
    fontSize: 16,
  },
  modalButtons: {
    flexDirection: 'row',
    gap: 10,
    marginTop: 10,
  },
  modalButton: {
    flex: 1,
    paddingVertical: 12,
    borderRadius: 10,
    alignItems: 'center',
  },
  cancelModalButton: {
    backgroundColor: '#f0f0f0',
  },
  cancelModalText: {
    color: '#666',
    fontWeight: '600',
  },
  saveModalButton: {
    backgroundColor: '#4CBF72',
  },
  saveModalText: {
    color: '#fff',
    fontWeight: '600',
  },
});