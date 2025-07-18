#include <Wire.h>
#include <LiquidCrystal_I2C.h>
#include <SoftwareSerial.h>
#include <ESP8266WiFi.h>
#include <ESP8266HTTPClient.h>

// LCD Configuration
LiquidCrystal_I2C lcd(0x27, 16, 2);

// SIM800L Configuration
SoftwareSerial sim800(D6, D5); // RX, TX

// Sensor Pins
const int sensorIn = D7;
const int sensorOut = D4;

// WiFi Configuration
const char* ssid = "HUAWEI-B310-68AD";
const char* password = "YALJG3Y7FH6";
const String serverURL = "http://192.168.10.110/smart-bus/api/hardware_api.php";

// System Variables
int count = 0;
const int maxCount = 18;
String lastStatus = "normal";  // Track last status

// LED and Buzzer Pins
const int led1 = D3;
const int buzzer = D8;
const int led2 = D0;

// Timing and flags
unsigned long lastDebounceTime = 0;
const unsigned long debounceDelay = 500;

unsigned long lastSMSTime = 0;
const unsigned long smsInterval = 10000;

bool notifiedFull = false;
bool buzzerOffAfterFull = false;
unsigned long fullTime = 0;

// Plate number (should exist in DB)
String plate_number = "RAC123B";

void setup() {
  Serial.begin(115200);
  lcd.init(); lcd.backlight();
  lcd.print("System Starting");
  delay(2000);

  pinMode(sensorIn, INPUT_PULLUP);
  pinMode(sensorOut, INPUT_PULLUP);
  pinMode(led1, OUTPUT); pinMode(led2, OUTPUT); pinMode(buzzer, OUTPUT);

  digitalWrite(led1, LOW); digitalWrite(led2, LOW); digitalWrite(buzzer, LOW);

  sim800.begin(9600);
  delay(1000); sim800.println("AT");
  waitForResponse(1000);

  connectToWiFi();
  lcd.clear(); lcd.print("Ready");
  Serial.println("System Ready");
}

void loop() {
  digitalWrite(led1, HIGH);

  // Entry detection
  if (digitalRead(sensorIn) == LOW && millis() - lastDebounceTime > debounceDelay) {
    count++;

    String status = getStatus();
    updateDisplay("Passenger In");
    sendToServer("entry", count, status);
    lastDebounceTime = millis();
  }

  // Exit detection
  if (digitalRead(sensorOut) == LOW && count > 0 && millis() - lastDebounceTime > debounceDelay) {
    count--;

    String status = getStatus();
    updateDisplay("Passenger Out");
    sendToServer("exit", count, status);
    lastDebounceTime = millis();
  }

  // Determine current status
  String currentStatus = getStatus();

  // Send SMS once when status changes into full or overloading
  if ((currentStatus == "full" || currentStatus == "overloading") && currentStatus != lastStatus) {
    sendSMS();
    lastSMSTime = millis();
  }

  // Alert logic
  if (currentStatus == "full") {
    digitalWrite(led2, HIGH);

    if (!notifiedFull) {
      digitalWrite(buzzer, HIGH);  // Buzz for 3 seconds
      fullTime = millis();
      notifiedFull = true;
      buzzerOffAfterFull = false;
      lcd.setCursor(0, 0); lcd.print("Vehicle is full. ");
    }

    if (!buzzerOffAfterFull && millis() - fullTime >= 3000) {
      digitalWrite(buzzer, LOW);
      buzzerOffAfterFull = true;
    }

  } else if (currentStatus == "overloading") {
    digitalWrite(led2, HIGH);
    digitalWrite(buzzer, LOW);   // Don't buzz when overloaded
    lcd.setCursor(0, 0); lcd.print("Overloading!!    ");

  } else { // normal
    digitalWrite(led2, LOW);
    digitalWrite(buzzer, LOW);
    lcd.setCursor(0, 0); lcd.print("Passenger Count ");
    notifiedFull = false;
    buzzerOffAfterFull = false;
  }

  // Update last status
  lastStatus = currentStatus;

  delay(100);
}

String getStatus() {
  if (count == maxCount) return "full";
  else if (count > maxCount) return "overloading";
  else return "normal";
}

void sendToServer(String event, int count, String status) {
  if (WiFi.status() != WL_CONNECTED) { connectToWiFi(); }

  HTTPClient http;
  WiFiClient client;

  String url = serverURL + "?event=" + event + "&count=" + String(count)
              + "&status=" + status + "&plate_number=" + plate_number;

  Serial.println("HTTP GET: " + url);

  if (http.begin(client, url)) {
    int httpCode = http.GET();
    if (httpCode > 0) {
      String payload = http.getString();
      Serial.printf("HTTP %d: %s\n", httpCode, payload.c_str());
    } else {
      Serial.printf("HTTP request failed: %s\n", http.errorToString(httpCode).c_str());
    }
    http.end();
  } else {
    Serial.println("Unable to start HTTP connection");
  }
}

void updateDisplay(String message) {
  lcd.clear();
  lcd.setCursor(0, 0); lcd.print(message);
  lcd.setCursor(0, 1); lcd.print("Total: "); lcd.print(count);
  Serial.println(message + " - Count: " + String(count));
}

void sendSMS() {
  Serial.println("Sending SMS...");
  sim800.println("AT+CMGF=1"); waitForResponse(1000);
  sim800.println("AT+CMGS=\"+250780830355\""); waitForResponse(1000);
  sim800.println("Alert: Vehicle status changed. Count: " + String(count));
  delay(100); sim800.write(26);
  waitForResponse(2000);
}

void connectToWiFi() {
  WiFi.begin(ssid, password);
  lcd.clear(); lcd.print("Connecting WiFi...");
  unsigned long timeout = millis() + 15000;
  while (WiFi.status() != WL_CONNECTED && millis() < timeout) {
    delay(500); Serial.print(".");
  }
  if (WiFi.status() == WL_CONNECTED) {
    lcd.clear(); lcd.print("WiFi Connected"); delay(1000);
    Serial.println("WiFi connected! IP: " + WiFi.localIP().toString());
  } else {
    lcd.clear(); lcd.print("WiFi Failed"); delay(1000);
  }
}

void waitForResponse(unsigned long timeout) {
  unsigned long start = millis();
  while (millis() - start < timeout) {
    while (sim800.available()) {
      char c = sim800.read(); Serial.write(c);
    }
  }
}
