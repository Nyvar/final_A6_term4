// LeftNavigation.tsx
import React, { useState } from "react";
import {
    StyleSheet,
    Text,
    TouchableOpacity,
    TouchableWithoutFeedback,
    View,
    Alert,
    ScrollView,
} from "react-native";
import { Ionicons } from "@expo/vector-icons";
import DateTimePickerModal from "react-native-modal-datetime-picker";

const intervals = ["Day", "Week", "Month", "Year", "All", "Interval", "Choose date"];
const themeGreen = "#69b578";

interface LeftNavigationProps {
    onClose: () => void;
    onSelectInterval?: (interval: string, startDate?: Date, endDate?: Date) => void;
    onPaymentMethodChange?: (method: string) => void;
}

export default function LeftNavigation({ onClose, onSelectInterval, onPaymentMethodChange }: LeftNavigationProps) {
    const [selected, setSelected] = useState<string | null>(null);
    const [showIncomeOptions, setShowIncomeOptions] = useState(false);
    const [paymentMethod, setPaymentMethod] = useState("Add Income");

    const [showDatePicker, setShowDatePicker] = useState(false);
    const [showIntervalPicker, setShowIntervalPicker] = useState(false);
    const [chooseDate, setChooseDate] = useState<Date | null>(null);
    const [intervalStart, setIntervalStart] = useState<Date | null>(null);
    const [intervalEnd, setIntervalEnd] = useState<Date | null>(null);
    const [pickingStart, setPickingStart] = useState(true);

    const handleConfirmDate = (date: Date) => {
        setChooseDate(date);
        setShowDatePicker(false);
        if (onSelectInterval) {
            onSelectInterval("Choose date", date);
        }
        onClose();
    };

    const handleConfirmInterval = (date: Date) => {
        if (pickingStart) {
            setIntervalStart(date);
            setPickingStart(false);
        } else {
            if (intervalStart && date >= intervalStart) {
                setIntervalEnd(date);
                setShowIntervalPicker(false);
                setPickingStart(true);
                if (onSelectInterval) {
                    onSelectInterval("Interval", intervalStart, date);
                }
                onClose();
            } else {
                Alert.alert("Invalid range", "End date must be after start date");
            }
        }
    };

    const formatDate = (d: Date | null) =>
        d ? d.toLocaleDateString("en-US", { month: "short", day: "numeric", year: "numeric" }) : "";

    const handleIntervalPress = (item: string) => {
        setSelected(item);
        
        if (item === "Choose date") {
            setShowDatePicker(true);
        } else if (item === "Interval") {
            setShowIntervalPicker(true);
        } else {
            if (onSelectInterval) {
                onSelectInterval(item);
            }
            onClose();
        }
    };

    const handlePaymentMethodChange = (method: string) => {
        setPaymentMethod(method);
        setShowIncomeOptions(false);
        if (onPaymentMethodChange) {
            onPaymentMethodChange(method);
        }
    };

    return (
        <View style={styles.container}>
            <View style={styles.sidebar}>
                <ScrollView showsVerticalScrollIndicator={false}>
                    {/* Header */}
                    <View style={styles.header}>
                        <Text style={styles.headerTitle}>Filters</Text>
                        <TouchableOpacity onPress={onClose} style={styles.closeButton}>
                            <Text style={styles.closeButtonText}>✕</Text>
                        </TouchableOpacity>
                    </View>

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
                                    onPress={() => handlePaymentMethodChange("All Accounts")}
                                >
                                    <Ionicons name="wallet-outline" size={24} color="#333" />
                                    <View style={styles.optionLabel}>
                                        <Text style={styles.paymentOptionText}>All Accounts</Text>
                                        <Text style={styles.usdLabel}>USD</Text>
                                    </View>
                                </TouchableOpacity>

                                <TouchableOpacity
                                    style={styles.paymentOption}
                                    onPress={() => handlePaymentMethodChange("Cash")}
                                >
                                    <Ionicons name="cash-outline" size={24} color="#333" />
                                    <View style={styles.optionLabel}>
                                        <Text style={styles.paymentOptionText}>Cash</Text>
                                        <Text style={styles.usdLabel}>USD</Text>
                                    </View>
                                </TouchableOpacity>

                                <TouchableOpacity
                                    style={styles.paymentOption}
                                    onPress={() => handlePaymentMethodChange("Payment Card")}
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
                                onPress={() => handleIntervalPress(item)}
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
                </ScrollView>
            </View>

            <TouchableWithoutFeedback onPress={onClose}>
                <View style={styles.overlay} />
            </TouchableWithoutFeedback>

            <DateTimePickerModal
                isVisible={showDatePicker}
                mode="date"
                onConfirm={handleConfirmDate}
                onCancel={() => setShowDatePicker(false)}
            />

            <DateTimePickerModal
                isVisible={showIntervalPicker}
                mode="date"
                onConfirm={handleConfirmInterval}
                onCancel={() => {
                    setShowIntervalPicker(false);
                    setPickingStart(true);
                }}
            />
        </View>
    );
}

const styles = StyleSheet.create({
    container: {
        flex: 1,
        flexDirection: "row",
        backgroundColor: "rgba(0,0,0,0.5)",
    },
    sidebar: {
        width: 280,
        backgroundColor: "#fff",
        height: "100%",
    },
    header: {
        flexDirection: "row",
        justifyContent: "space-between",
        alignItems: "center",
        padding: 20,
        paddingTop: 60,
        backgroundColor: themeGreen,
        borderBottomWidth: 1,
        borderBottomColor: "#eee",
    },
    headerTitle: {
        fontSize: 20,
        fontWeight: "bold",
        color: "#fff",
    },
    closeButton: {
        width: 30,
        height: 30,
        borderRadius: 15,
        backgroundColor: "rgba(255,255,255,0.2)",
        justifyContent: "center",
        alignItems: "center",
    },
    closeButtonText: {
        fontSize: 18,
        color: "#fff",
        fontWeight: "bold",
    },
    addIncomeWrapper: {
        paddingHorizontal: 15,
        paddingTop: 20,
        marginBottom: 20,
    },
    intervalPanel: {
        paddingHorizontal: 15,
    },
    intervalButton: {
        paddingVertical: 12,
        paddingHorizontal: 16,
        borderRadius: 8,
        borderWidth: 1,
        borderColor: themeGreen,
        marginBottom: 12,
        backgroundColor: "#fff",
        alignItems: "center",
    },
    intervalButtonSelected: {
        backgroundColor: "#e8f5e9",
        borderWidth: 2,
    },
    intervalText: {
        fontSize: 16,
        color: "#333",
        fontWeight: "500",
    },
    intervalTextSelected: {
        color: themeGreen,
        fontWeight: "bold",
    },
    addIncomeButton: {
        backgroundColor: "#fff",
    },
    addIncomeText: {
        fontSize: 18,
        color: "#333",
        fontWeight: "bold",
    },
    addIncomeSubText: {
        fontSize: 14,
        color: themeGreen,
        marginTop: 4,
        fontWeight: "500",
    },
    incomeOptions: {
        backgroundColor: "#fff",
        borderRadius: 10,
        padding: 12,
        marginTop: 8,
        elevation: 5,
        shadowColor: "#000",
        shadowOffset: { width: 0, height: 2 },
        shadowOpacity: 0.1,
        shadowRadius: 4,
    },
    paymentOption: {
        flexDirection: "row",
        alignItems: "center",
        paddingVertical: 10,
        borderBottomWidth: 1,
        borderBottomColor: "#f0f0f0",
    },
    optionLabel: {
        flexDirection: "column",
        marginLeft: 12,
    },
    paymentOptionText: {
        fontSize: 16,
        color: "#333",
        fontWeight: "500",
    },
    usdLabel: {
        fontSize: 12,
        color: "#666",
        marginTop: 2,
    },
    overlay: {
        flex: 1,
        backgroundColor: "transparent",
    },
});