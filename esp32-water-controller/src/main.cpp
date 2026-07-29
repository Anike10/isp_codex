#include <Arduino.h>
#include <DNSServer.h>
#include <ESPmDNS.h>
#include <Preferences.h>
#include <Update.h>
#include <WebServer.h>
#include <WiFi.h>
#include <driver/gpio.h>
#include <esp_idf_version.h>
#include <esp_system.h>
#include <esp_task_wdt.h>
#include <esp_timer.h>
#include <mbedtls/sha256.h>
#include <stdarg.h>
#include <stddef.h>
#include <time.h>

#include "ui.h"

namespace {

constexpr char FIRMWARE_VERSION[] = "5.0.0";
constexpr uint32_t CONFIG_MAGIC = 0x57415452;
constexpr uint16_t CONFIG_VERSION = 1;
constexpr uint32_t LOG_MAGIC = 0x4C4F4753;
constexpr uint8_t MAX_LOGS = 32;
constexpr uint8_t MAX_SENSOR_SAMPLES = 9;
constexpr uint8_t HISTORY_SIZE = 12;
constexpr uint32_t SESSION_HARD_LIMIT_MS = 24UL * 60UL * 60UL * 1000UL;
constexpr uint16_t DNS_PORT = 53;

enum class PumpMode : uint8_t { Idle, Auto, Manual, Force, Scheduled };

struct TimeWindow {
  uint8_t enabled;
  uint16_t startMinute;
  uint16_t endMinute;
  uint8_t startLevel;
  uint8_t stopLevel;
};

struct OutputTimer {
  uint8_t enabled;
  uint16_t onMinute;
  uint16_t offMinute;
};

struct PinMap {
  int8_t trigger;
  int8_t echo;
  int8_t pump;
  int8_t aux;
  int8_t light1;
  int8_t light2;
  int8_t buzzer;
  int8_t reserve;
  int8_t grid;
  int8_t roofNormal;
  int8_t roofForce;
  int8_t lowerNormal;
  int8_t lowerForce;
  int8_t startButton;
  int8_t stopButton;
};

struct Config {
  uint32_t magic;
  uint16_t version;
  uint16_t size;

  char hostname[24];
  char staSsid[33];
  char staPass[65];
  char apSsid[33];
  char apPass[65];
  char ntpServer[64];
  uint8_t apAlwaysOn;
  uint8_t useStaticIp;
  uint8_t staticIp[4];
  uint8_t gateway[4];
  uint8_t subnet[4];
  uint8_t dns[4];
  int16_t utcOffsetMin;

  float fullCm;
  float emptyCm;
  float minValidCm;
  float maxValidCm;
  float airTemperatureC;
  uint16_t readActiveMs;
  uint16_t readIdleMs;
  uint16_t sensorStaleSec;
  uint8_t medianSamples;

  uint8_t automationEnabled;
  uint8_t autoStartLevel;
  uint8_t autoStopLevel;
  TimeWindow windows[2];
  uint8_t manualTarget;
  uint16_t manualMaxMin;
  uint16_t forceLimitSec;
  uint16_t minOffSec;
  uint16_t maxRunMin;
  uint8_t forceBypassReserve;
  uint8_t dryRunEnabled;
  uint8_t dryRunMinRise;
  uint16_t dryRunWindowSec;
  uint8_t scheduledFillEnabled;
  uint8_t scheduledFillTarget;
  uint16_t scheduledFillMinute;

  uint8_t reserveGuard;
  uint8_t gridGuard;
  uint8_t reserveAvailableHigh;
  uint8_t gridPresentHigh;
  uint8_t relayActiveHigh;
  uint8_t inputActiveLow;
  uint16_t beepMs;

  OutputTimer lightTimers[2];
  uint16_t pumpWatts;
  float unitPrice;

  uint8_t logEnabled;
  uint8_t logLimit;
  uint16_t logRetentionDays;

  uint8_t rebootEnabled;
  uint16_t rebootMinute;
  uint16_t rebootEveryDays;

  uint8_t authEnabled;
  uint8_t protectDashboard;
  uint8_t protectPump;
  uint8_t protectOutputs;
  uint16_t sessionTimeoutMin;
  uint8_t passwordSalt[16];
  uint8_t passwordHash[32];

  PinMap pins;
  uint32_t checksum;
};

struct EventRecord {
  uint32_t epoch;
  uint32_t uptimeSec;
  int16_t levelTenths;
  uint8_t mode;
  uint8_t pumpOn;
  char source[18];
  char reason[58];
};

struct LogStore {
  uint32_t magic;
  uint8_t head;
  uint8_t count;
  uint16_t reserved;
  EventRecord records[MAX_LOGS];
  uint32_t checksum;
};

struct DebouncedInput {
  int8_t pin;
  bool raw;
  bool stable;
  uint32_t changedAt;
};

Config config;
LogStore logStore;
Preferences preferences;
WebServer server(80);
DNSServer dnsServer;

bool apRunning = false;
bool mdnsRunning = false;
bool wifiWasConnected = false;
uint32_t nextWifiAttemptMs = 0;
uint32_t wifiConnectedAtMs = 0;
uint32_t restartAtMs = 0;

char sessionToken[33] = {};
uint32_t sessionLastSeenMs = 0;
uint8_t loginFailures = 0;
uint32_t loginLockedUntilMs = 0;

bool pumpOn = false;
PumpMode pumpMode = PumpMode::Idle;
uint32_t pumpStartedMs = 0;
uint32_t pumpStoppedMs = 0;
uint32_t nextAutomaticAttemptMs = 0;
uint32_t nextDemandAttemptMs = 0;
float pumpStartLevel = 0.0f;
float dryRunBaseline = 0.0f;
uint32_t dryRunBaselineMs = 0;
uint8_t runningTarget = 100;
bool webNormalDemand = false;
bool webForceDemand = false;
bool physicalStopLatched = false;

bool auxOn = false;
bool lightsOn[2] = {false, false};
bool lightOverride[2] = {false, false};
bool lastScheduleState[2] = {false, false};
bool scheduleStateKnown[2] = {false, false};
uint32_t buzzerUntilMs = 0;

char lastAction[82] = "Controller started";
char lastSource[22] = "System";
uint32_t lastActionId = 1;

uint64_t totalPumpSeconds = 0;
uint32_t lastRuntimeTickMs = 0;
uint32_t runtimeDirtySeconds = 0;

volatile uint32_t echoRiseUs = 0;
volatile uint32_t echoPulseUs = 0;
volatile bool echoReady = false;
volatile int8_t echoIsrPin = -1;
portMUX_TYPE echoMux = portMUX_INITIALIZER_UNLOCKED;
bool pingInFlight = false;
uint32_t pingStartedUs = 0;
uint32_t nextPingMs = 0;
uint32_t lastGoodSensorMs = 0;
float currentDistanceCm = 0.0f;
float currentLevel = 0.0f;
float sensorSamples[MAX_SENSOR_SAMPLES] = {};
uint8_t sampleCount = 0;
uint8_t sampleWrite = 0;
float sensorHistory[HISTORY_SIZE] = {};
uint8_t historyCount = 0;
uint8_t historyWrite = 0;
bool historyFrozen = false;

DebouncedInput switchInputs[6];
DebouncedInput buttonInputs[2];
uint32_t nextInputPollMs = 0;

int32_t lastScheduledFillDay = -1;
int32_t lastScheduledRebootDay = -1;
uint32_t nextScheduleAttemptMs = 0;
uint32_t nextRetentionCheckMs = 0;

bool otaAllowed = false;
bool otaSucceeded = false;

bool due(uint32_t now, uint32_t deadline) {
  return static_cast<int32_t>(now - deadline) >= 0;
}

template <size_t N>
void copyText(char (&destination)[N], const char* source) {
  if (!source) {
    destination[0] = '\0';
    return;
  }
  strlcpy(destination, source, N);
}

void copyBounded(char* destination, size_t size, const char* source) {
  if (!destination || size == 0) return;
  strlcpy(destination, source ? source : "", size);
}

uint32_t checksumBytes(const void* data, size_t length) {
  const uint8_t* bytes = static_cast<const uint8_t*>(data);
  uint32_t hash = 2166136261UL;
  for (size_t i = 0; i < length; ++i) {
    hash ^= bytes[i];
    hash *= 16777619UL;
  }
  return hash;
}

void passwordDigest(const char* password, const uint8_t salt[16], uint8_t output[32]) {
  mbedtls_sha256_context context;
  mbedtls_sha256_init(&context);
  mbedtls_sha256_starts_ret(&context, 0);
  mbedtls_sha256_update_ret(&context, salt, 16);
  mbedtls_sha256_update_ret(
      &context, reinterpret_cast<const unsigned char*>(password), strlen(password));
  mbedtls_sha256_finish_ret(&context, output);
  mbedtls_sha256_free(&context);
}

bool constantTimeEqual(const uint8_t* left, const uint8_t* right, size_t size) {
  uint8_t difference = 0;
  for (size_t i = 0; i < size; ++i) difference |= left[i] ^ right[i];
  return difference == 0;
}

void setPassword(Config& target, const char* password) {
  esp_fill_random(target.passwordSalt, sizeof(target.passwordSalt));
  passwordDigest(password, target.passwordSalt, target.passwordHash);
}

bool passwordMatches(const char* password) {
  uint8_t digest[32];
  passwordDigest(password, config.passwordSalt, digest);
  return constantTimeEqual(digest, config.passwordHash, sizeof(digest));
}

void setDefaultConfig() {
  memset(&config, 0, sizeof(config));
  config.magic = CONFIG_MAGIC;
  config.version = CONFIG_VERSION;
  config.size = sizeof(config);

  copyText(config.hostname, "water-tank");
  copyText(config.apSsid, "WaterTank-Setup");
  copyText(config.apPass, "watertank");
  copyText(config.ntpServer, "pool.ntp.org");
  config.apAlwaysOn = 1;
  config.utcOffsetMin = 360;
  const uint8_t defaultIp[4] = {192, 168, 1, 90};
  const uint8_t defaultGateway[4] = {192, 168, 1, 1};
  const uint8_t defaultSubnet[4] = {255, 255, 255, 0};
  memcpy(config.staticIp, defaultIp, 4);
  memcpy(config.gateway, defaultGateway, 4);
  memcpy(config.subnet, defaultSubnet, 4);
  memcpy(config.dns, defaultGateway, 4);

  config.fullCm = 25.0f;
  config.emptyCm = 180.0f;
  config.minValidCm = 19.0f;
  config.maxValidCm = 450.0f;
  config.airTemperatureC = 28.0f;
  config.readActiveMs = 250;
  config.readIdleMs = 1000;
  config.sensorStaleSec = 15;
  config.medianSamples = 5;

  config.automationEnabled = 1;
  config.autoStartLevel = 25;
  config.autoStopLevel = 90;
  config.windows[0] = {0, 6 * 60, 11 * 60, 25, 90};
  config.windows[1] = {0, 16 * 60, 23 * 60, 25, 90};
  config.manualTarget = 90;
  config.manualMaxMin = 30;
  config.forceLimitSec = 10;
  config.minOffSec = 120;
  config.maxRunMin = 45;
  config.forceBypassReserve = 0;
  config.dryRunEnabled = 1;
  config.dryRunMinRise = 1;
  config.dryRunWindowSec = 300;
  config.scheduledFillEnabled = 0;
  config.scheduledFillTarget = 90;
  config.scheduledFillMinute = 6 * 60;

  config.reserveGuard = 0;
  config.gridGuard = 0;
  config.reserveAvailableHigh = 1;
  config.gridPresentHigh = 1;
  config.relayActiveHigh = 0;
  config.inputActiveLow = 1;
  config.beepMs = 120;

  config.lightTimers[0] = {0, 18 * 60, 23 * 60};
  config.lightTimers[1] = {0, 18 * 60, 23 * 60};
  config.pumpWatts = 750;
  config.unitPrice = 10.0f;

  config.logEnabled = 1;
  config.logLimit = 24;
  config.logRetentionDays = 30;
  config.rebootEnabled = 0;
  config.rebootMinute = 4 * 60;
  config.rebootEveryDays = 1;

  config.authEnabled = 1;
  config.protectDashboard = 0;
  config.protectPump = 1;
  config.protectOutputs = 1;
  config.sessionTimeoutMin = 30;
  setPassword(config, "admin1234");

  config.pins = {5, 18, 23, 22, 25, 26, 27, 19, 21, 13, 14, 16, 17, 32, 33};
  config.checksum = checksumBytes(&config, offsetof(Config, checksum));
}

void saveConfig() {
  config.magic = CONFIG_MAGIC;
  config.version = CONFIG_VERSION;
  config.size = sizeof(config);
  config.checksum = checksumBytes(&config, offsetof(Config, checksum));
  preferences.putBytes("cfg", &config, sizeof(config));
}

void loadConfig() {
  bool valid = preferences.getBytesLength("cfg") == sizeof(config);
  if (valid) {
    preferences.getBytes("cfg", &config, sizeof(config));
    valid = config.magic == CONFIG_MAGIC && config.version == CONFIG_VERSION &&
            config.size == sizeof(config) &&
            config.checksum == checksumBytes(&config, offsetof(Config, checksum));
  }
  if (!valid) {
    setDefaultConfig();
    saveConfig();
  }
}

void resetLogStore() {
  memset(&logStore, 0, sizeof(logStore));
  logStore.magic = LOG_MAGIC;
  logStore.checksum = checksumBytes(&logStore, offsetof(LogStore, checksum));
}

void saveLogs() {
  logStore.checksum = checksumBytes(&logStore, offsetof(LogStore, checksum));
  preferences.putBytes("logs", &logStore, sizeof(logStore));
}

void loadLogs() {
  bool valid = preferences.getBytesLength("logs") == sizeof(logStore);
  if (valid) {
    preferences.getBytes("logs", &logStore, sizeof(logStore));
    valid = logStore.magic == LOG_MAGIC && logStore.head < MAX_LOGS &&
            logStore.count <= MAX_LOGS &&
            logStore.checksum == checksumBytes(&logStore, offsetof(LogStore, checksum));
  }
  if (!valid) {
    resetLogStore();
    saveLogs();
  }
  lastScheduledFillDay = preferences.getInt("fillDay", -1);
  lastScheduledRebootDay = preferences.getInt("rebootDay", -1);
  if (preferences.getBytesLength("runtime") == sizeof(totalPumpSeconds)) {
    preferences.getBytes("runtime", &totalPumpSeconds, sizeof(totalPumpSeconds));
  }
}

const char* modeText(PumpMode mode) {
  switch (mode) {
    case PumpMode::Auto: return "AUTO";
    case PumpMode::Manual: return "MANUAL";
    case PumpMode::Force: return "FORCE";
    case PumpMode::Scheduled: return "SCHEDULED";
    default: return "IDLE";
  }
}

void recordAction(const char* source, const char* action) {
  copyBounded(lastSource, sizeof(lastSource), source);
  copyBounded(lastAction, sizeof(lastAction), action);
  ++lastActionId;
}

bool clockNow(tm& value) {
  const time_t now = time(nullptr);
  if (now < 1700000000) return false;
  localtime_r(&now, &value);
  return true;
}

int32_t localDaySerial(const tm& value) {
  const int32_t year = value.tm_year + 1900;
  const int32_t previousYear = year - 1;
  return previousYear * 365 + previousYear / 4 - previousYear / 100 +
         previousYear / 400 + value.tm_yday;
}

uint16_t minuteOfDay(const tm& value) {
  return value.tm_hour * 60U + value.tm_min;
}

bool minuteInside(uint16_t minute, uint16_t start, uint16_t end) {
  if (start == end) return true;
  if (start < end) return minute >= start && minute < end;
  return minute >= start || minute < end;
}

uint32_t uptimeSeconds() {
  return static_cast<uint64_t>(esp_timer_get_time()) / 1000000ULL;
}

void addLog(const char* source, const char* reason, bool isOn, PumpMode mode) {
  if (!config.logEnabled) return;
  EventRecord& event = logStore.records[logStore.head];
  memset(&event, 0, sizeof(event));
  const time_t now = time(nullptr);
  event.epoch = now >= 1700000000 ? static_cast<uint32_t>(now) : 0;
  event.uptimeSec = uptimeSeconds();
  event.levelTenths = lastGoodSensorMs ? lroundf(currentLevel * 10.0f) : -1;
  event.mode = static_cast<uint8_t>(mode);
  event.pumpOn = isOn;
  copyBounded(event.source, sizeof(event.source), source);
  copyBounded(event.reason, sizeof(event.reason), reason);
  logStore.head = (logStore.head + 1) % MAX_LOGS;
  if (logStore.count < MAX_LOGS) ++logStore.count;
  if (logStore.count > config.logLimit) logStore.count = config.logLimit;
  saveLogs();
}

void saveRuntime() {
  preferences.putBytes("runtime", &totalPumpSeconds, sizeof(totalPumpSeconds));
  runtimeDirtySeconds = 0;
}

void writeOutput(int8_t pin, bool on) {
  if (pin < 0) return;
  digitalWrite(pin, config.relayActiveHigh ? on : !on);
}

bool readActive(int8_t pin, bool activeHigh) {
  if (pin < 0) return false;
  return digitalRead(pin) == (activeHigh ? HIGH : LOW);
}

bool reserveAvailable() {
  if (!config.reserveGuard || config.pins.reserve < 0) return true;
  return readActive(config.pins.reserve, config.reserveAvailableHigh);
}

bool gridAvailable() {
  if (!config.gridGuard || config.pins.grid < 0) return true;
  return readActive(config.pins.grid, config.gridPresentHigh);
}

bool sensorValid() {
  return lastGoodSensorMs != 0 &&
         static_cast<uint32_t>(millis() - lastGoodSensorMs) <=
             static_cast<uint32_t>(config.sensorStaleSec) * 1000UL;
}

void beep(uint16_t durationMs = 0) {
  if (config.pins.buzzer < 0) return;
  const uint16_t duration = durationMs ? durationMs : config.beepMs;
  if (!duration) return;
  writeOutput(config.pins.buzzer, true);
  buzzerUntilMs = millis() + duration;
}

void tickBuzzer(uint32_t now) {
  if (buzzerUntilMs && due(now, buzzerUntilMs)) {
    writeOutput(config.pins.buzzer, false);
    buzzerUntilMs = 0;
  }
}

void stopPump(const char* source, const char* reason) {
  if (!pumpOn) {
    recordAction(source, reason);
    return;
  }
  const PumpMode stoppedMode = pumpMode;
  writeOutput(config.pins.pump, false);
  pumpOn = false;
  pumpMode = PumpMode::Idle;
  pumpStoppedMs = millis();
  webNormalDemand = false;
  webForceDemand = false;
  recordAction(source, reason);
  addLog(source, reason, false, stoppedMode);
  saveRuntime();
  beep();
}

bool startGuardAllows(PumpMode requested, char* reason, size_t reasonSize) {
  if (physicalStopLatched) {
    copyBounded(reason, reasonSize, "Blocked: physical STOP latch is active");
    return false;
  }
  if (config.gridGuard && !gridAvailable()) {
    copyBounded(reason, reasonSize, "Blocked: grid power is unavailable");
    return false;
  }
  if (config.reserveGuard && !reserveAvailable() &&
      !(requested == PumpMode::Force && config.forceBypassReserve)) {
    copyBounded(reason, reasonSize, "Blocked: reserve tank is empty");
    return false;
  }
  if (requested != PumpMode::Force && !sensorValid()) {
    copyBounded(reason, reasonSize, "Blocked: RCWL-1655 has no valid echo");
    return false;
  }
  if (pumpStoppedMs && config.minOffSec &&
      static_cast<uint32_t>(millis() - pumpStoppedMs) <
          static_cast<uint32_t>(config.minOffSec) * 1000UL) {
    copyBounded(reason, reasonSize, "Blocked: restart protection is active");
    return false;
  }
  return true;
}

bool startPump(PumpMode requested, const char* source, uint8_t target, bool announceBlocked) {
  if (pumpOn) {
    if (requested == PumpMode::Force && pumpMode != PumpMode::Force) {
      pumpMode = PumpMode::Force;
      pumpStartedMs = millis();
      runningTarget = 100;
      recordAction(source, "Pump changed to FORCE mode");
      addLog(source, "Changed to FORCE mode", true, PumpMode::Force);
      beep();
      return true;
    }
    if (announceBlocked) recordAction(source, "Pump is already running");
    return true;
  }

  char reason[72];
  if (!startGuardAllows(requested, reason, sizeof(reason))) {
    if (announceBlocked) {
      recordAction(source, reason);
      beep(config.beepMs * 2U);
    }
    return false;
  }

  writeOutput(config.pins.pump, true);
  pumpOn = true;
  pumpMode = requested;
  pumpStartedMs = millis();
  pumpStartLevel = currentLevel;
  dryRunBaseline = currentLevel;
  dryRunBaselineMs = pumpStartedMs;
  runningTarget = target;
  char action[80];
  snprintf(action, sizeof(action), "Pump started in %s mode", modeText(requested));
  recordAction(source, action);
  addLog(source, action, true, requested);
  beep();
  return true;
}

bool activeAutoTargets(uint8_t& startLevel, uint8_t& stopLevel, char* status,
                       size_t statusSize) {
  bool hasWindow = config.windows[0].enabled || config.windows[1].enabled;
  if (!hasWindow) {
    startLevel = config.autoStartLevel;
    stopLevel = config.autoStopLevel;
    copyBounded(status, statusSize, "Always active");
    return true;
  }

  tm now;
  if (!clockNow(now)) {
    copyBounded(status, statusSize, "Waiting for synchronized time");
    return false;
  }
  const uint16_t minute = minuteOfDay(now);
  for (uint8_t i = 0; i < 2; ++i) {
    const TimeWindow& window = config.windows[i];
    if (window.enabled && minuteInside(minute, window.startMinute, window.endMinute)) {
      startLevel = window.startLevel;
      stopLevel = window.stopLevel;
      snprintf(status, statusSize, "Window %u active", i + 1);
      return true;
    }
  }
  copyBounded(status, statusSize, "Outside automatic windows");
  return false;
}

void tickPumpRuntime(uint32_t now) {
  if (!lastRuntimeTickMs) lastRuntimeTickMs = now;
  while (pumpOn && static_cast<uint32_t>(now - lastRuntimeTickMs) >= 1000UL) {
    lastRuntimeTickMs += 1000UL;
    ++totalPumpSeconds;
    ++runtimeDirtySeconds;
  }
  if (!pumpOn) lastRuntimeTickMs = now;
  if (runtimeDirtySeconds >= 900) saveRuntime();
}

void tickPump(uint32_t now) {
  tickPumpRuntime(now);

  if (pumpOn) {
    if (config.gridGuard && !gridAvailable()) {
      stopPump("Safety", "Pump stopped: grid power lost");
      return;
    }
    if (config.reserveGuard && !reserveAvailable() &&
        !(pumpMode == PumpMode::Force && config.forceBypassReserve)) {
      stopPump("Safety", "Pump stopped: reserve tank empty");
      return;
    }
    if (config.maxRunMin &&
        static_cast<uint32_t>(now - pumpStartedMs) >=
            static_cast<uint32_t>(config.maxRunMin) * 60000UL) {
      stopPump("Safety", "Pump stopped: maximum runtime reached");
      return;
    }
    if (pumpMode != PumpMode::Force && !sensorValid()) {
      stopPump("Safety", "Pump stopped: RCWL-1655 echo lost");
      return;
    }
    if (pumpMode == PumpMode::Force &&
        static_cast<uint32_t>(now - pumpStartedMs) >=
            static_cast<uint32_t>(config.forceLimitSec) * 1000UL) {
      stopPump("Safety", "Pump stopped: force timer expired");
      return;
    }
    if (pumpMode == PumpMode::Manual &&
        (currentLevel >= runningTarget ||
         static_cast<uint32_t>(now - pumpStartedMs) >=
             static_cast<uint32_t>(config.manualMaxMin) * 60000UL)) {
      stopPump("Manual", currentLevel >= runningTarget ? "Manual target reached"
                                                       : "Manual runtime expired");
      return;
    }
    if (pumpMode == PumpMode::Scheduled && currentLevel >= runningTarget) {
      stopPump("Schedule", "Scheduled fill target reached");
      return;
    }
    if (pumpMode == PumpMode::Auto) {
      uint8_t startLevel = 0, stopLevel = config.autoStopLevel;
      char status[54];
      if (!activeAutoTargets(startLevel, stopLevel, status, sizeof(status))) {
        stopPump("Automation", "Pump stopped: automatic window ended");
        return;
      }
      if (currentLevel >= stopLevel) {
        stopPump("Automation", "Automatic stop level reached");
        return;
      }
    }
    if (config.dryRunEnabled && pumpMode != PumpMode::Force && sensorValid() &&
        static_cast<uint32_t>(now - dryRunBaselineMs) >=
            static_cast<uint32_t>(config.dryRunWindowSec) * 1000UL) {
      if (currentLevel - dryRunBaseline < config.dryRunMinRise) {
        stopPump("Safety", "Dry-run protection: water level did not rise");
        return;
      }
      dryRunBaseline = currentLevel;
      dryRunBaselineMs = now;
    }
  }

  if (!pumpOn && config.automationEnabled && due(now, nextAutomaticAttemptMs)) {
    nextAutomaticAttemptMs = now + 15000UL;
    uint8_t startLevel = 0, stopLevel = 0;
    char status[54];
    if (sensorValid() &&
        activeAutoTargets(startLevel, stopLevel, status, sizeof(status)) &&
        currentLevel <= startLevel) {
      startPump(PumpMode::Auto, "Automation", stopLevel, false);
    }
  }
}

void IRAM_ATTR echoInterrupt() {
  const uint32_t now = micros();
  const bool high = gpio_get_level(static_cast<gpio_num_t>(echoIsrPin));
  portENTER_CRITICAL_ISR(&echoMux);
  if (high) {
    echoRiseUs = now;
  } else if (echoRiseUs) {
    echoPulseUs = now - echoRiseUs;
    echoReady = true;
    echoRiseUs = 0;
  }
  portEXIT_CRITICAL_ISR(&echoMux);
}

float medianDistance() {
  float sorted[MAX_SENSOR_SAMPLES];
  const uint8_t count = min(sampleCount, config.medianSamples);
  for (uint8_t i = 0; i < count; ++i) sorted[i] = sensorSamples[i];
  for (uint8_t i = 1; i < count; ++i) {
    const float value = sorted[i];
    int8_t j = i - 1;
    while (j >= 0 && sorted[j] > value) {
      sorted[j + 1] = sorted[j];
      --j;
    }
    sorted[j + 1] = value;
  }
  return sorted[count / 2];
}

void acceptDistance(float distance) {
  sensorSamples[sampleWrite] = distance;
  sampleWrite = (sampleWrite + 1) % config.medianSamples;
  if (sampleCount < config.medianSamples) ++sampleCount;
  if (sampleCount < config.medianSamples) return;

  currentDistanceCm = medianDistance();
  const float span = config.emptyCm - config.fullCm;
  currentLevel = span > 1.0f ? (config.emptyCm - currentDistanceCm) * 100.0f / span : 0;
  currentLevel = constrain(currentLevel, 0.0f, 100.0f);
  lastGoodSensorMs = millis();

  if (!historyFrozen) {
    sensorHistory[historyWrite] = currentDistanceCm;
    historyWrite = (historyWrite + 1) % HISTORY_SIZE;
    if (historyCount < HISTORY_SIZE) ++historyCount;
  }
}

void triggerRcwl1655() {
  portENTER_CRITICAL(&echoMux);
  echoReady = false;
  echoRiseUs = 0;
  portEXIT_CRITICAL(&echoMux);
  digitalWrite(config.pins.trigger, LOW);
  delayMicroseconds(2);
  digitalWrite(config.pins.trigger, HIGH);
  delayMicroseconds(12);
  digitalWrite(config.pins.trigger, LOW);
  pingStartedUs = micros();
  pingInFlight = true;
}

void tickSensor(uint32_t now) {
  bool ready;
  uint32_t pulse;
  portENTER_CRITICAL(&echoMux);
  ready = echoReady;
  pulse = echoPulseUs;
  if (ready) echoReady = false;
  portEXIT_CRITICAL(&echoMux);

  if (ready && pingInFlight) {
    pingInFlight = false;
    const float speedMetersPerSecond = 331.45f + 0.61f * config.airTemperatureC;
    const float distance = pulse * speedMetersPerSecond / 20000.0f;
    if (distance >= config.minValidCm && distance <= config.maxValidCm) {
      acceptDistance(distance);
    }
  }

  if (pingInFlight && static_cast<uint32_t>(micros() - pingStartedUs) > 38000UL) {
    pingInFlight = false;
  }

  if (!pingInFlight && due(now, nextPingMs)) {
    triggerRcwl1655();
    const uint16_t interval = pumpOn ? config.readActiveMs : config.readIdleMs;
    nextPingMs = now + (interval < 60 ? 60 : interval);
  }
}

bool inputState(int8_t pin) {
  return readActive(pin, !config.inputActiveLow);
}

void initDebouncedInput(DebouncedInput& input, int8_t pin) {
  input.pin = pin;
  input.raw = pin >= 0 ? inputState(pin) : false;
  input.stable = input.raw;
  input.changedAt = millis();
}

bool anyNormalDemand() {
  return webNormalDemand || switchInputs[0].stable || switchInputs[2].stable;
}

bool anyForceDemand() {
  return webForceDemand || switchInputs[1].stable || switchInputs[3].stable;
}

void evaluatePhysicalDemand(uint32_t now) {
  if (!due(now, nextDemandAttemptMs)) return;
  nextDemandAttemptMs = now + 3000UL;
  if (physicalStopLatched) return;
  if (anyForceDemand()) {
    startPump(PumpMode::Force, "Physical switch", 100, false);
  } else if (anyNormalDemand()) {
    startPump(PumpMode::Manual, "Physical switch", config.manualTarget, false);
  } else if (pumpOn && (pumpMode == PumpMode::Force || pumpMode == PumpMode::Manual)) {
    stopPump("Physical switch", "Pump demand removed");
  }
}

void onSwitchChange(uint8_t index, bool active) {
  const bool force = index == 1 || index == 3;
  if (active) physicalStopLatched = false;
  recordAction(force ? "Force switch" : "Normal switch", active ? "Switch ON" : "Switch OFF");
  nextDemandAttemptMs = 0;
  evaluatePhysicalDemand(millis());
}

void onButtonPress(uint8_t index) {
  if (index == 0) {
    physicalStopLatched = false;
    webNormalDemand = true;
    startPump(PumpMode::Manual, "Start button", config.manualTarget, true);
  } else {
    physicalStopLatched = true;
    webNormalDemand = false;
    webForceDemand = false;
    stopPump("Stop button", "Physical STOP pressed");
  }
}

void pollInput(DebouncedInput& input, uint32_t now, uint8_t index, bool isButton) {
  if (input.pin < 0) return;
  const bool value = inputState(input.pin);
  if (value != input.raw) {
    input.raw = value;
    input.changedAt = now;
  }
  if (value != input.stable && static_cast<uint32_t>(now - input.changedAt) >= 40UL) {
    input.stable = value;
    if (isButton) {
      if (value) onButtonPress(index);
    } else {
      onSwitchChange(index, value);
    }
  }
}

void tickInputs(uint32_t now) {
  if (!due(now, nextInputPollMs)) return;
  nextInputPollMs = now + 10UL;
  for (uint8_t i = 0; i < 6; ++i) pollInput(switchInputs[i], now, i, false);
  for (uint8_t i = 0; i < 2; ++i) pollInput(buttonInputs[i], now, i, true);
  evaluatePhysicalDemand(now);
}

void configureInputPin(int8_t pin) {
  if (pin < 0) return;
  if (pin >= 34) pinMode(pin, INPUT);
  else pinMode(pin, config.inputActiveLow ? INPUT_PULLUP : INPUT_PULLDOWN);
}

void initializeHardware() {
  const int8_t outputs[] = {config.pins.pump, config.pins.aux, config.pins.light1,
                            config.pins.light2, config.pins.buzzer};
  for (int8_t pin : outputs) {
    if (pin < 0) continue;
    digitalWrite(pin, config.relayActiveHigh ? LOW : HIGH);
    pinMode(pin, OUTPUT);
  }
  pinMode(config.pins.trigger, OUTPUT);
  digitalWrite(config.pins.trigger, LOW);
  pinMode(config.pins.echo, INPUT);

  const int8_t inputs[] = {
      config.pins.reserve,     config.pins.grid,       config.pins.roofNormal,
      config.pins.roofForce,  config.pins.lowerNormal, config.pins.lowerForce,
      config.pins.startButton, config.pins.stopButton};
  for (int8_t pin : inputs) configureInputPin(pin);

  echoIsrPin = config.pins.echo;
  attachInterrupt(digitalPinToInterrupt(config.pins.echo), echoInterrupt, CHANGE);

  initDebouncedInput(switchInputs[0], config.pins.roofNormal);
  initDebouncedInput(switchInputs[1], config.pins.roofForce);
  initDebouncedInput(switchInputs[2], config.pins.lowerNormal);
  initDebouncedInput(switchInputs[3], config.pins.lowerForce);
  initDebouncedInput(switchInputs[4], -1);
  initDebouncedInput(switchInputs[5], -1);
  initDebouncedInput(buttonInputs[0], config.pins.startButton);
  initDebouncedInput(buttonInputs[1], config.pins.stopButton);
  physicalStopLatched = buttonInputs[1].stable;
}

bool scheduledOutputState(const OutputTimer& timer, uint16_t minute) {
  return minuteInside(minute, timer.onMinute, timer.offMinute);
}

void tickLightSchedules() {
  tm now;
  if (!clockNow(now)) return;
  const uint16_t minute = minuteOfDay(now);
  for (uint8_t i = 0; i < 2; ++i) {
    const OutputTimer& timer = config.lightTimers[i];
    if (!timer.enabled) {
      scheduleStateKnown[i] = false;
      continue;
    }
    const bool desired = scheduledOutputState(timer, minute);
    if (!scheduleStateKnown[i] || desired != lastScheduleState[i]) {
      scheduleStateKnown[i] = true;
      lastScheduleState[i] = desired;
      lightOverride[i] = false;
    }
    if (!lightOverride[i] && lightsOn[i] != desired) {
      lightsOn[i] = desired;
      writeOutput(i == 0 ? config.pins.light1 : config.pins.light2, desired);
    }
  }
}

void tickScheduledFill(uint32_t now) {
  if (!config.scheduledFillEnabled || !due(now, nextScheduleAttemptMs)) return;
  nextScheduleAttemptMs = now + 30000UL;
  tm local;
  if (!clockNow(local)) return;
  const int32_t day = localDaySerial(local);
  if (day == lastScheduledFillDay || minuteOfDay(local) < config.scheduledFillMinute) return;
  if (!sensorValid() || pumpOn) return;
  if (currentLevel >= config.scheduledFillTarget) {
    lastScheduledFillDay = day;
    preferences.putInt("fillDay", day);
    return;
  }
  if (startPump(PumpMode::Scheduled, "Schedule", config.scheduledFillTarget, false)) {
    lastScheduledFillDay = day;
    preferences.putInt("fillDay", day);
  }
}

void scheduleRestart(uint32_t delayMs) {
  restartAtMs = millis() + delayMs;
}

void tickScheduledReboot(uint32_t now) {
  if (!config.rebootEnabled) return;
  tm local;
  if (!clockNow(local)) return;
  const int32_t day = localDaySerial(local);
  if (day == lastScheduledRebootDay) return;
  if (lastScheduledRebootDay >= 0 &&
      day - lastScheduledRebootDay < config.rebootEveryDays) return;
  if (minuteOfDay(local) < config.rebootMinute || pumpOn) return;
  lastScheduledRebootDay = day;
  preferences.putInt("rebootDay", day);
  recordAction("Schedule", "Scheduled reboot");
  scheduleRestart(1200);
}

void pruneOldLogs(uint32_t nowMs) {
  if (!due(nowMs, nextRetentionCheckMs)) return;
  nextRetentionCheckMs = nowMs + 3600000UL;
  const time_t now = time(nullptr);
  if (now < 1700000000 || !config.logRetentionDays) return;
  bool changed = false;
  while (logStore.count) {
    const uint8_t oldest =
        (logStore.head + MAX_LOGS - logStore.count) % MAX_LOGS;
    const EventRecord& event = logStore.records[oldest];
    if (!event.epoch ||
        static_cast<uint32_t>(now - event.epoch) <=
            static_cast<uint32_t>(config.logRetentionDays) * 86400UL) break;
    --logStore.count;
    changed = true;
  }
  if (changed) saveLogs();
}

void ipToText(const uint8_t value[4], char output[16]) {
  snprintf(output, 16, "%u.%u.%u.%u", value[0], value[1], value[2], value[3]);
}

void startAccessPoint() {
  if (apRunning) return;
  WiFi.mode(WIFI_AP_STA);
  const bool secured = strlen(config.apPass) >= 8;
  apRunning = secured ? WiFi.softAP(config.apSsid, config.apPass)
                      : WiFi.softAP(config.apSsid);
  if (apRunning) dnsServer.start(DNS_PORT, "*", WiFi.softAPIP());
}

void beginStation() {
  if (!strlen(config.staSsid)) return;
  if (config.useStaticIp) {
    WiFi.config(IPAddress(config.staticIp), IPAddress(config.gateway),
                IPAddress(config.subnet), IPAddress(config.dns));
  } else {
    WiFi.config(INADDR_NONE, INADDR_NONE, INADDR_NONE);
  }
  WiFi.begin(config.staSsid, config.staPass);
}

void initializeNetwork() {
  WiFi.persistent(false);
  WiFi.setAutoReconnect(true);
  WiFi.setSleep(true);
  WiFi.setHostname(config.hostname);
  WiFi.mode(WIFI_STA);
  beginStation();
  if (config.apAlwaysOn || !strlen(config.staSsid)) startAccessPoint();
  nextWifiAttemptMs = millis() + 15000UL;
}

void tickNetwork(uint32_t now) {
  const bool connected = WiFi.status() == WL_CONNECTED;
  if (connected && !wifiWasConnected) {
    wifiConnectedAtMs = now;
    configTime(config.utcOffsetMin * 60, 0, config.ntpServer);
    if (!mdnsRunning) {
      mdnsRunning = MDNS.begin(config.hostname);
      if (mdnsRunning) MDNS.addService("http", "tcp", 80);
    }
  }
  if (!connected && due(now, nextWifiAttemptMs)) {
    nextWifiAttemptMs = now + 15000UL;
    beginStation();
    if (!apRunning) startAccessPoint();
  }
  if (connected && apRunning && !config.apAlwaysOn &&
      static_cast<uint32_t>(now - wifiConnectedAtMs) > 60000UL) {
    dnsServer.stop();
    WiFi.softAPdisconnect(true);
    apRunning = false;
    WiFi.mode(WIFI_STA);
  }
  wifiWasConnected = connected;
  if (apRunning) dnsServer.processNextRequest();
}

void createSession() {
  uint8_t randomBytes[16];
  esp_fill_random(randomBytes, sizeof(randomBytes));
  static constexpr char HEX[] = "0123456789abcdef";
  for (uint8_t i = 0; i < sizeof(randomBytes); ++i) {
    sessionToken[i * 2] = HEX[randomBytes[i] >> 4];
    sessionToken[i * 2 + 1] = HEX[randomBytes[i] & 0x0F];
  }
  sessionToken[32] = '\0';
  sessionLastSeenMs = millis();
}

bool authenticated() {
  if (!config.authEnabled) return true;
  if (!sessionToken[0]) return false;
  uint32_t limit = static_cast<uint32_t>(config.sessionTimeoutMin) * 60000UL;
  if (limit > SESSION_HARD_LIMIT_MS) limit = SESSION_HARD_LIMIT_MS;
  if (static_cast<uint32_t>(millis() - sessionLastSeenMs) > limit) {
    sessionToken[0] = '\0';
    return false;
  }
  const String cookie = server.header("Cookie");
  char expected[48];
  snprintf(expected, sizeof(expected), "ESPSESSION=%s", sessionToken);
  if (cookie.indexOf(expected) < 0) return false;
  sessionLastSeenMs = millis();
  return true;
}

void sendSecurityHeaders() {
  server.sendHeader("Cache-Control", "no-store");
  server.sendHeader("X-Content-Type-Options", "nosniff");
  server.sendHeader("X-Frame-Options", "DENY");
  server.sendHeader("Referrer-Policy", "no-referrer");
  server.sendHeader("Content-Security-Policy",
                    "default-src 'self'; style-src 'unsafe-inline'; "
                    "script-src 'unsafe-inline'; img-src 'self' data:");
}

void beginChunked(const char* mime = "text/html; charset=utf-8") {
  sendSecurityHeaders();
  server.setContentLength(CONTENT_LENGTH_UNKNOWN);
  server.send(200, mime, "");
}

void endChunked() {
  server.sendContent("");
}

void chunkP(PGM_P value) {
  server.sendContent_P(value);
}

void chunk(const char* value) {
  server.sendContent(value);
}

void chunkf(const char* format, ...) {
  char buffer[768];
  va_list arguments;
  va_start(arguments, format);
  vsnprintf(buffer, sizeof(buffer), format, arguments);
  va_end(arguments);
  server.sendContent(buffer);
}

void chunkEscaped(const char* value, bool json = false) {
  char output[160];
  size_t used = 0;
  const auto flush = [&]() {
    if (!used) return;
    output[used] = '\0';
    server.sendContent(output);
    used = 0;
  };
  for (const unsigned char* cursor =
           reinterpret_cast<const unsigned char*>(value ? value : "");
       *cursor; ++cursor) {
    const char* replacement = nullptr;
    if (json) {
      switch (*cursor) {
        case '"': replacement = "\\\""; break;
        case '\\': replacement = "\\\\"; break;
        case '\n': replacement = "\\n"; break;
        case '\r': replacement = "\\r"; break;
        case '\t': replacement = "\\t"; break;
        default: break;
      }
    } else {
      switch (*cursor) {
        case '&': replacement = "&amp;"; break;
        case '<': replacement = "&lt;"; break;
        case '>': replacement = "&gt;"; break;
        case '"': replacement = "&quot;"; break;
        case '\'': replacement = "&#39;"; break;
        default: break;
      }
    }
    if (replacement) {
      const size_t length = strlen(replacement);
      if (used + length >= sizeof(output) - 1) flush();
      memcpy(output + used, replacement, length);
      used += length;
    } else if (*cursor >= 0x20) {
      if (used + 1 >= sizeof(output) - 1) flush();
      output[used++] = *cursor;
    }
  }
  flush();
}

void beginPage(const char* title, bool navigation = true) {
  beginChunked();
  chunkP(PSTR("<!doctype html><html><head><meta charset=\"utf-8\"><meta "
              "name=\"viewport\" content=\"width=device-width,initial-scale=1\">"
              "<title>"));
  chunkEscaped(title);
  chunkP(PSTR("</title>"));
  chunkP(UI_STYLE);
  chunkP(PSTR("</head><body><div id=\"toast\" class=\"toast\"></div><main class=\"shell\">"));
  if (navigation) chunkP(UI_NAV);
  chunkP(PSTR("<header class=\"page-head\"><div><div class=\"eyebrow\">ESP32 / RCWL-1655</div><h1>"));
  chunkEscaped(title);
  chunkf("</h1></div><div class=\"version\">Firmware %s</div></header>", FIRMWARE_VERSION);
  if (server.hasArg("saved")) chunkP(PSTR("<div class=\"notice\">Settings saved.</div><br>"));
}

void endPage() {
  chunkP(PSTR("<footer class=\"footer\">FAIL-SAFE WATER CONTROL / ESP32 / RCWL-1655</footer>"
              "</main></body></html>"));
  endChunked();
}

void redirectTo(const char* location) {
  server.sendHeader("Location", location);
  server.send(303, "text/plain", "");
}

void sendJsonError(uint16_t status, const char* message) {
  sendSecurityHeaders();
  char body[180];
  snprintf(body, sizeof(body), "{\"ok\":false,\"message\":\"%s\"}", message);
  server.send(status, "application/json", body);
}

bool requireAuthentication(bool json = false) {
  if (authenticated()) return true;
  if (json) sendJsonError(401, "Login required");
  else redirectTo("/login");
  return false;
}

bool requireProtectedAction(bool protectionEnabled) {
  if (!config.authEnabled || !protectionEnabled || authenticated()) return true;
  sendJsonError(401, "Login required");
  return false;
}

void sendTimeInput(const char* name, uint16_t minute) {
  chunkf("<input type=\"time\" name=\"%s\" value=\"%02u:%02u\" required>",
         name, minute / 60, minute % 60);
}

void sendIpInput(const char* label, const char* name, const uint8_t ip[4]) {
  char value[16];
  ipToText(ip, value);
  chunkf("<label>%s<input name=\"%s\" value=\"%s\" inputmode=\"decimal\"></label>",
         label, name, value);
}

void sendTextInput(const char* label, const char* name, const char* value,
                   uint8_t maxLength = 64) {
  chunkf("<label>%s<input name=\"%s\" maxlength=\"%u\" value=\"", label, name,
         maxLength);
  chunkEscaped(value);
  chunkP(PSTR("\"></label>"));
}

void sendDashboard() {
  if (config.protectDashboard && !requireAuthentication()) return;
  beginPage("Tank Command Center");
  chunkP(PSTR(
      "<section class=\"grid\">"
      "<article class=\"card hero span-8\"><div class=\"clock\" id=\"clock\">--:--:--</div>"
      "<div class=\"date\" id=\"date\">Waiting for network time</div>"
      "<div class=\"controls\"><button id=\"normalButton\" onclick=\"command('normal')\">NORMAL PUMP</button>"
      "<button id=\"forceButton\" class=\"danger\" onclick=\"command('force')\">FORCE ON / OFF</button></div>"
      "<div class=\"stage\" id=\"stage\">Controller is starting...</div></article>"
      "<article class=\"card span-4\"><h3>Live system</h3><div class=\"quick\">"
      "<div class=\"quick-row\"><span>ROUTER</span><span id=\"wifiChip\" class=\"chip off\">OFFLINE</span></div>"
      "<div class=\"quick-row\"><span>PUMP</span><span id=\"pumpChip\" class=\"chip off\">STOPPED</span></div>"
      "<div class=\"quick-row\"><span>TIME</span><span id=\"timeChip\" class=\"chip warn\">WAITING</span></div>"
      "<div class=\"quick-row\"><span>IP</span><b id=\"ip\">--</b></div>"
      "<div class=\"quick-row\"><span>UPTIME</span><b id=\"uptime\">--</b></div>"
      "</div></article>"
      "<article class=\"card span-7\"><div class=\"tank-zone\"><div class=\"tank\">"
      "<div id=\"drop\" class=\"pump-drop\"></div><div id=\"water\" class=\"water\"></div>"
      "<div class=\"tank-copy\"><strong id=\"level\">No echo</strong><span id=\"distance\">Waiting for RCWL-1655</span></div>"
      "</div></div></article>"
      "<article class=\"card span-5\"><h2>Controller telemetry</h2><div class=\"metric-grid\">"
      "<div class=\"metric\"><small>Mode</small><strong id=\"mode\">IDLE</strong></div>"
      "<div class=\"metric\"><small>WiFi RSSI</small><strong id=\"rssi\">--</strong></div>"
      "<div class=\"metric\"><small>Free heap</small><strong id=\"heap\">--</strong></div>"
      "<div class=\"metric\"><small>Energy</small><strong id=\"energy\">--</strong></div>"
      "<div class=\"metric\"><small>Estimated cost</small><strong id=\"cost\">--</strong></div>"
      "<div class=\"metric\"><small>Sensor</small><strong>RCWL-1655</strong></div></div><br>"
      "<div class=\"status-list\"><div class=\"status-line\"><b>Last action:</b> <span id=\"lastAction\">--</span></div>"
      "<div class=\"status-line\"><b>Automation:</b> <span id=\"autoStatus\">--</span></div>"
      "<div class=\"status-line\"><b>Safety:</b> <span id=\"guards\">--</span></div></div></article>"
      "<article class=\"card span-12\"><h2>Auxiliary outputs</h2><div class=\"output-grid\">"
      "<button id=\"auxButton\" onclick=\"command('aux')\">AUX LOAD <span id=\"auxState\">OFF</span></button>"
      "<button id=\"light1Button\" onclick=\"command('light1')\">LIGHT 1 <span id=\"light1State\">OFF</span></button>"
      "<button id=\"light2Button\" onclick=\"command('light2')\">LIGHT 2 <span id=\"light2State\">OFF</span></button>"
      "</div></article></section>"));
  chunkP(UI_DASH_SCRIPT);
  endPage();
}

long argLong(const char* name, long current, long minimum, long maximum) {
  if (!server.hasArg(name)) return current;
  return constrain(server.arg(name).toInt(), minimum, maximum);
}

float argFloat(const char* name, float current, float minimum, float maximum) {
  if (!server.hasArg(name)) return current;
  return constrain(server.arg(name).toFloat(), minimum, maximum);
}

bool parseMinute(const String& value, uint16_t& output) {
  int hour = -1, minute = -1;
  if (sscanf(value.c_str(), "%d:%d", &hour, &minute) != 2 ||
      hour < 0 || hour > 23 || minute < 0 || minute > 59) return false;
  output = hour * 60 + minute;
  return true;
}

void updateMinuteArg(const char* name, uint16_t& target) {
  if (server.hasArg(name)) parseMinute(server.arg(name), target);
}

void sendSettingsPage() {
  if (!requireAuthentication()) return;
  beginPage("Controller Settings");
  chunkP(PSTR("<section class=\"grid\"><article class=\"card span-12\"><h2>Pump automation</h2>"
              "<form method=\"post\" action=\"/save_settings\">"
              "<label class=\"check\"><input type=\"checkbox\" name=\"automation\""));
  if (config.automationEnabled) chunk(" checked");
  chunkP(PSTR("> Enable automatic filling</label><div class=\"form-grid three\">"));
  chunkf("<label>Start level %%<input type=\"number\" name=\"auto_start\" min=\"1\" max=\"90\" value=\"%u\"></label>",
         config.autoStartLevel);
  chunkf("<label>Stop level %%<input type=\"number\" name=\"auto_stop\" min=\"10\" max=\"100\" value=\"%u\"></label>",
         config.autoStopLevel);
  chunkf("<label>Minimum OFF seconds<input type=\"number\" name=\"min_off\" min=\"0\" max=\"3600\" value=\"%u\"></label>",
         config.minOffSec);
  chunkf("<label>Maximum run minutes<input type=\"number\" name=\"max_run\" min=\"1\" max=\"720\" value=\"%u\"></label>",
         config.maxRunMin);
  chunkf("<label>Manual target %%<input type=\"number\" name=\"manual_target\" min=\"5\" max=\"100\" value=\"%u\"></label>",
         config.manualTarget);
  chunkf("<label>Manual max minutes<input type=\"number\" name=\"manual_max\" min=\"1\" max=\"720\" value=\"%u\"></label>",
         config.manualMaxMin);
  chunkf("<label>Force limit seconds<input type=\"number\" name=\"force_limit\" min=\"1\" max=\"3600\" value=\"%u\"></label>",
         config.forceLimitSec);
  chunkf("<label>Alarm beep ms<input type=\"number\" name=\"beep_ms\" min=\"0\" max=\"5000\" value=\"%u\"></label>",
         config.beepMs);
  chunkP(PSTR("</div><label class=\"check\"><input type=\"checkbox\" name=\"force_bypass\""));
  if (config.forceBypassReserve) chunk(" checked");
  chunkP(PSTR("> FORCE may bypass reserve guard (grid guard still applies)</label>"
              "<h3>Dry-run protection</h3><div class=\"form-grid three\">"
              "<label class=\"check\"><input type=\"checkbox\" name=\"dry_run\""));
  if (config.dryRunEnabled) chunk(" checked");
  chunkf("> Enable level-rise check</label>"
         "<label>Check window seconds<input type=\"number\" name=\"dry_window\" min=\"60\" max=\"3600\" value=\"%u\"></label>"
         "<label>Minimum rise %%<input type=\"number\" name=\"dry_rise\" min=\"1\" max=\"20\" value=\"%u\"></label></div>",
         config.dryRunWindowSec, config.dryRunMinRise);

  for (uint8_t i = 0; i < 2; ++i) {
    const TimeWindow& window = config.windows[i];
    chunkf("<h3>Automatic window %u</h3><div class=\"form-grid three\">"
           "<label class=\"check\"><input type=\"checkbox\" name=\"window%u_on\"%s> Enabled</label>"
           "<label>Start time", i + 1, i + 1, window.enabled ? " checked" : "");
    char name[20];
    snprintf(name, sizeof(name), "window%u_start", i + 1);
    sendTimeInput(name, window.startMinute);
    chunkP(PSTR("</label><label>End time"));
    snprintf(name, sizeof(name), "window%u_end", i + 1);
    sendTimeInput(name, window.endMinute);
    chunkf("</label><label>Start level %%<input type=\"number\" name=\"window%u_low\" min=\"1\" max=\"90\" value=\"%u\"></label>"
           "<label>Stop level %%<input type=\"number\" name=\"window%u_high\" min=\"10\" max=\"100\" value=\"%u\"></label></div>",
           i + 1, window.startLevel, i + 1, window.stopLevel);
  }

  chunkP(PSTR("<h3>Daily scheduled fill</h3><div class=\"form-grid three\"><label class=\"check\">"
              "<input type=\"checkbox\" name=\"scheduled_fill\""));
  if (config.scheduledFillEnabled) chunk(" checked");
  chunkP(PSTR("> Enabled</label><label>Start time"));
  sendTimeInput("fill_time", config.scheduledFillMinute);
  chunkf("</label><label>Target %%<input type=\"number\" name=\"fill_target\" min=\"5\" max=\"100\" value=\"%u\"></label></div>",
         config.scheduledFillTarget);

  chunkP(PSTR("<h3>Safety inputs</h3><div class=\"form-grid\"><label class=\"check\">"
              "<input type=\"checkbox\" name=\"reserve_guard\""));
  if (config.reserveGuard) chunk(" checked");
  chunkP(PSTR("> Stop/block when reserve tank is empty</label><label class=\"check\">"
              "<input type=\"checkbox\" name=\"grid_guard\""));
  if (config.gridGuard) chunk(" checked");
  chunkP(PSTR("> Stop/block when grid input is absent</label></div>"
              "<h3>Light schedules</h3><div class=\"form-grid\">"));
  for (uint8_t i = 0; i < 2; ++i) {
    const OutputTimer& timer = config.lightTimers[i];
    chunkf("<div class=\"metric\"><label class=\"check\"><input type=\"checkbox\" name=\"light%u_timer\"%s> Light %u timer</label>"
           "<label>ON time", i + 1, timer.enabled ? " checked" : "", i + 1);
    char name[18];
    snprintf(name, sizeof(name), "light%u_on", i + 1);
    sendTimeInput(name, timer.onMinute);
    chunkP(PSTR("</label><label>OFF time"));
    snprintf(name, sizeof(name), "light%u_off", i + 1);
    sendTimeInput(name, timer.offMinute);
    chunkP(PSTR("</label></div>"));
  }
  chunkP(PSTR("</div><h3>Energy estimate</h3><div class=\"form-grid\">"));
  chunkf("<label>Pump power (watts)<input type=\"number\" name=\"pump_watts\" min=\"1\" max=\"20000\" value=\"%u\"></label>",
         config.pumpWatts);
  chunkf("<label>Price per kWh<input type=\"number\" step=\"0.01\" name=\"unit_price\" min=\"0\" max=\"10000\" value=\"%.2f\"></label>",
         config.unitPrice);
  chunkP(PSTR("</div><div class=\"actions\"><button type=\"submit\">SAVE CONTROLLER SETTINGS</button>"
              "<button type=\"button\" class=\"secondary\" onclick=\"command('reset-energy')\">RESET ENERGY TOTAL</button>"
              "</div></form></article></section>"));
  chunkP(UI_DASH_SCRIPT);
  endPage();
}

void handleSaveSettings() {
  if (!requireAuthentication()) return;
  Config next = config;
  next.automationEnabled = server.hasArg("automation");
  next.autoStartLevel = argLong("auto_start", next.autoStartLevel, 1, 90);
  next.autoStopLevel = argLong("auto_stop", next.autoStopLevel, 10, 100);
  if (next.autoStopLevel <= next.autoStartLevel)
    next.autoStopLevel =
        next.autoStartLevel > 95 ? 100 : static_cast<uint8_t>(next.autoStartLevel + 5);
  next.minOffSec = argLong("min_off", next.minOffSec, 0, 3600);
  next.maxRunMin = argLong("max_run", next.maxRunMin, 1, 720);
  next.manualTarget = argLong("manual_target", next.manualTarget, 5, 100);
  next.manualMaxMin = argLong("manual_max", next.manualMaxMin, 1, 720);
  next.forceLimitSec = argLong("force_limit", next.forceLimitSec, 1, 3600);
  next.beepMs = argLong("beep_ms", next.beepMs, 0, 5000);
  next.forceBypassReserve = server.hasArg("force_bypass");
  next.dryRunEnabled = server.hasArg("dry_run");
  next.dryRunWindowSec = argLong("dry_window", next.dryRunWindowSec, 60, 3600);
  next.dryRunMinRise = argLong("dry_rise", next.dryRunMinRise, 1, 20);
  for (uint8_t i = 0; i < 2; ++i) {
    char name[20];
    snprintf(name, sizeof(name), "window%u_on", i + 1);
    next.windows[i].enabled = server.hasArg(name);
    snprintf(name, sizeof(name), "window%u_start", i + 1);
    updateMinuteArg(name, next.windows[i].startMinute);
    snprintf(name, sizeof(name), "window%u_end", i + 1);
    updateMinuteArg(name, next.windows[i].endMinute);
    snprintf(name, sizeof(name), "window%u_low", i + 1);
    next.windows[i].startLevel = argLong(name, next.windows[i].startLevel, 1, 90);
    snprintf(name, sizeof(name), "window%u_high", i + 1);
    next.windows[i].stopLevel = argLong(name, next.windows[i].stopLevel, 10, 100);
    if (next.windows[i].stopLevel <= next.windows[i].startLevel)
      next.windows[i].stopLevel =
          next.windows[i].startLevel > 95
              ? 100
              : static_cast<uint8_t>(next.windows[i].startLevel + 5);
  }
  next.scheduledFillEnabled = server.hasArg("scheduled_fill");
  updateMinuteArg("fill_time", next.scheduledFillMinute);
  next.scheduledFillTarget = argLong("fill_target", next.scheduledFillTarget, 5, 100);
  next.reserveGuard = server.hasArg("reserve_guard");
  next.gridGuard = server.hasArg("grid_guard");
  for (uint8_t i = 0; i < 2; ++i) {
    char name[18];
    snprintf(name, sizeof(name), "light%u_timer", i + 1);
    next.lightTimers[i].enabled = server.hasArg(name);
    snprintf(name, sizeof(name), "light%u_on", i + 1);
    updateMinuteArg(name, next.lightTimers[i].onMinute);
    snprintf(name, sizeof(name), "light%u_off", i + 1);
    updateMinuteArg(name, next.lightTimers[i].offMinute);
  }
  next.pumpWatts = argLong("pump_watts", next.pumpWatts, 1, 20000);
  next.unitPrice = argFloat("unit_price", next.unitPrice, 0, 10000);
  config = next;
  saveConfig();
  recordAction("Settings", "Controller settings saved");
  redirectTo("/settings?saved=1");
}

void sendWifiPage() {
  if (!requireAuthentication()) return;
  beginPage("WiFi & Time");
  chunkP(PSTR("<section class=\"grid\"><article class=\"card span-7\"><h2>Station connection</h2>"
              "<form method=\"post\" action=\"/save_wifi\">"));
  sendTextInput("Device hostname", "hostname", config.hostname, 23);
  sendTextInput("Router SSID", "sta_ssid", config.staSsid, 32);
  chunkP(PSTR("<label>Router password<input type=\"password\" name=\"sta_pass\" maxlength=\"64\" "
              "placeholder=\"Leave blank to keep current password\"></label>"
              "<div class=\"form-grid\"><label class=\"check\"><input type=\"checkbox\" name=\"static_ip\""));
  if (config.useStaticIp) chunk(" checked");
  chunkP(PSTR("> Use static IPv4</label><label>UTC offset minutes"
              "<input type=\"number\" name=\"utc_offset\" min=\"-720\" max=\"840\" value=\""));
  chunkf("%d", config.utcOffsetMin);
  chunkP(PSTR("\"></label></div>"));
  sendTextInput("NTP server", "ntp_server", config.ntpServer, 63);
  chunkP(PSTR("<div class=\"form-grid\">"));
  sendIpInput("Static IP", "static_value", config.staticIp);
  sendIpInput("Gateway", "gateway", config.gateway);
  sendIpInput("Subnet", "subnet", config.subnet);
  sendIpInput("DNS", "dns", config.dns);
  chunkP(PSTR("</div><h3>Fallback access point</h3><label class=\"check\">"
              "<input type=\"checkbox\" name=\"ap_on\""));
  if (config.apAlwaysOn) chunk(" checked");
  chunkP(PSTR("> Keep setup AP active after router connects</label>"));
  sendTextInput("Access-point SSID", "ap_ssid", config.apSsid, 32);
  chunkP(PSTR("<label>Access-point password<input type=\"password\" name=\"ap_pass\" maxlength=\"64\" "
              "placeholder=\"Leave blank to keep current password\"></label>"
              "<div class=\"notice warn\">Saving network settings restarts the ESP32. An AP is started automatically if the router cannot be reached.</div>"
              "<div class=\"actions\"><button type=\"submit\">SAVE & RESTART</button></div></form></article>"
              "<article class=\"card span-5\"><h2>Nearby networks</h2>"
              "<button id=\"scanButton\" type=\"button\" onclick=\"scanWifi()\">SCAN WIFI</button>"
              "<div id=\"scanList\" class=\"wifi-list\"></div></article></section>"));
  chunkP(UI_SCAN_SCRIPT);
  endPage();
}

bool parseIpArg(const char* name, uint8_t target[4]) {
  IPAddress parsed;
  if (!server.hasArg(name) || !parsed.fromString(server.arg(name))) return false;
  for (uint8_t i = 0; i < 4; ++i) target[i] = parsed[i];
  return true;
}

void sendSimplePage(const char* title, const char* message, bool bad,
                    const char* returnPath) {
  beginPage(title);
  chunkf("<div class=\"notice%s\">", bad ? " bad" : "");
  chunkEscaped(message);
  chunkf("</div><br><a class=\"button\" href=\"%s\">RETURN</a>", returnPath);
  endPage();
}

bool validHostname(const String& hostname) {
  if (!hostname.length() || hostname.length() > 23) return false;
  for (size_t i = 0; i < hostname.length(); ++i) {
    const char c = hostname[i];
    if (!(isalnum(static_cast<unsigned char>(c)) || c == '-')) return false;
  }
  return hostname[0] != '-' && hostname[hostname.length() - 1] != '-';
}

void handleSaveWifi() {
  if (!requireAuthentication()) return;
  Config next = config;
  const String hostname = server.arg("hostname");
  if (!validHostname(hostname)) {
    sendSimplePage("Invalid hostname", "Use only letters, digits, and hyphens.", true, "/wifi");
    return;
  }
  copyBounded(next.hostname, sizeof(next.hostname), hostname.c_str());
  copyBounded(next.staSsid, sizeof(next.staSsid), server.arg("sta_ssid").c_str());
  if (server.arg("sta_pass").length())
    copyBounded(next.staPass, sizeof(next.staPass), server.arg("sta_pass").c_str());
  copyBounded(next.apSsid, sizeof(next.apSsid), server.arg("ap_ssid").c_str());
  if (server.arg("ap_pass").length()) {
    if (server.arg("ap_pass").length() < 8) {
      sendSimplePage("Invalid AP password", "The AP password must have at least 8 characters.",
                     true, "/wifi");
      return;
    }
    copyBounded(next.apPass, sizeof(next.apPass), server.arg("ap_pass").c_str());
  }
  copyBounded(next.ntpServer, sizeof(next.ntpServer), server.arg("ntp_server").c_str());
  next.apAlwaysOn = server.hasArg("ap_on");
  next.useStaticIp = server.hasArg("static_ip");
  next.utcOffsetMin = argLong("utc_offset", next.utcOffsetMin, -720, 840);
  if (next.useStaticIp &&
      (!parseIpArg("static_value", next.staticIp) ||
       !parseIpArg("gateway", next.gateway) ||
       !parseIpArg("subnet", next.subnet) ||
       !parseIpArg("dns", next.dns))) {
    sendSimplePage("Invalid network address", "Check all four IPv4 fields.", true, "/wifi");
    return;
  }
  config = next;
  saveConfig();
  recordAction("WiFi", "Network settings saved; restarting");
  scheduleRestart(2500);
  sendSimplePage("WiFi saved", "The controller is restarting with the new network settings.",
                 false, "/");
}

bool outputCapablePin(int pin) {
  if (pin < 0 || pin > 33) return false;
  if (pin >= 6 && pin <= 11) return false;
  return pin != 0 && pin != 2 && pin != 12 && pin != 15;
}

bool usableInputPin(int pin) {
  if (pin == -1) return true;
  if (pin < 0 || pin > 39 || (pin >= 6 && pin <= 11)) return false;
  return pin != 0 && pin != 2 && pin != 12 && pin != 15;
}

bool validatePinMap(const PinMap& pins, char* reason, size_t reasonSize) {
  const int8_t requiredOutputs[] = {pins.trigger, pins.pump};
  for (int8_t pin : requiredOutputs) {
    if (!outputCapablePin(pin)) {
      copyBounded(reason, reasonSize, "Trigger and pump require safe output-capable GPIO pins");
      return false;
    }
  }
  const int8_t optionalOutputs[] = {pins.aux, pins.light1, pins.light2, pins.buzzer};
  for (int8_t pin : optionalOutputs) {
    if (pin != -1 && !outputCapablePin(pin)) {
      copyBounded(reason, reasonSize, "An output uses an unsafe or input-only GPIO pin");
      return false;
    }
  }
  const int8_t inputs[] = {pins.echo,       pins.reserve,    pins.grid,
                           pins.roofNormal, pins.roofForce,  pins.lowerNormal,
                           pins.lowerForce, pins.startButton, pins.stopButton};
  if (pins.echo < 0) {
    copyBounded(reason, reasonSize, "RCWL-1655 Echo pin is required");
    return false;
  }
  for (int8_t pin : inputs) {
    if (!usableInputPin(pin)) {
      copyBounded(reason, reasonSize, "An input uses an unsafe GPIO pin");
      return false;
    }
  }
  const int8_t all[] = {pins.trigger,    pins.echo,       pins.pump,
                        pins.aux,        pins.light1,     pins.light2,
                        pins.buzzer,     pins.reserve,    pins.grid,
                        pins.roofNormal, pins.roofForce,  pins.lowerNormal,
                        pins.lowerForce, pins.startButton, pins.stopButton};
  const size_t pinCount = sizeof(all) / sizeof(all[0]);
  for (size_t i = 0; i < pinCount; ++i) {
    if (all[i] < 0) continue;
    for (size_t j = i + 1; j < pinCount; ++j) {
      if (all[i] == all[j]) {
        copyBounded(reason, reasonSize, "GPIO assignments must be unique");
        return false;
      }
    }
  }
  return true;
}

void sendPinField(const char* label, const char* name, int8_t pin, bool optional) {
  chunkf("<label>%s<input type=\"number\" name=\"%s\" min=\"%d\" max=\"39\" value=\"%d\"></label>",
         label, name, optional ? -1 : 0, pin);
}

void sendPinsPage() {
  if (!requireAuthentication()) return;
  beginPage("Pin Configuration");
  chunkP(PSTR("<section class=\"grid\"><article class=\"card span-12\"><div class=\"notice warn\">"
              "Classic ESP32 DevKit mapping. GPIO 0, 2, 6-12 and 15 are rejected to prevent boot/flash conflicts. "
              "Use -1 to disable an optional pin. Saving restarts the controller.</div><br>"
              "<form method=\"post\" action=\"/save_pins\"><div class=\"form-grid three\">"));
  sendPinField("RCWL Trigger", "trigger", config.pins.trigger, false);
  sendPinField("RCWL Echo", "echo", config.pins.echo, false);
  sendPinField("Pump relay", "pump", config.pins.pump, false);
  sendPinField("Aux relay", "aux", config.pins.aux, true);
  sendPinField("Light 1 relay", "light1", config.pins.light1, true);
  sendPinField("Light 2 relay", "light2", config.pins.light2, true);
  sendPinField("Buzzer", "buzzer", config.pins.buzzer, true);
  sendPinField("Reserve input", "reserve", config.pins.reserve, true);
  sendPinField("Grid input", "grid", config.pins.grid, true);
  sendPinField("Roof normal", "roof_normal", config.pins.roofNormal, true);
  sendPinField("Roof force", "roof_force", config.pins.roofForce, true);
  sendPinField("Lower normal", "lower_normal", config.pins.lowerNormal, true);
  sendPinField("Lower force", "lower_force", config.pins.lowerForce, true);
  sendPinField("Start button", "start_button", config.pins.startButton, true);
  sendPinField("Stop button", "stop_button", config.pins.stopButton, true);
  chunkP(PSTR("</div><h3>Electrical polarity</h3><div class=\"form-grid\">"
              "<label class=\"check\"><input type=\"checkbox\" name=\"relay_high\""));
  if (config.relayActiveHigh) chunk(" checked");
  chunkP(PSTR("> Relays/buzzer are active HIGH</label><label class=\"check\">"
              "<input type=\"checkbox\" name=\"input_low\""));
  if (config.inputActiveLow) chunk(" checked");
  chunkP(PSTR("> Switches/buttons are active LOW</label><label class=\"check\">"
              "<input type=\"checkbox\" name=\"reserve_high\""));
  if (config.reserveAvailableHigh) chunk(" checked");
  chunkP(PSTR("> Reserve AVAILABLE is HIGH</label><label class=\"check\">"
              "<input type=\"checkbox\" name=\"grid_high\""));
  if (config.gridPresentHigh) chunk(" checked");
  chunkP(PSTR("> Grid PRESENT is HIGH</label></div><div class=\"actions\">"
              "<button type=\"submit\">VALIDATE, SAVE & RESTART</button></div></form>"
              "</article></section>"));
  endPage();
}

int8_t pinArg(const char* name, int8_t current, bool optional) {
  return argLong(name, current, optional ? -1 : 0, 39);
}

void handleSavePins() {
  if (!requireAuthentication()) return;
  Config next = config;
  next.pins.trigger = pinArg("trigger", next.pins.trigger, false);
  next.pins.echo = pinArg("echo", next.pins.echo, false);
  next.pins.pump = pinArg("pump", next.pins.pump, false);
  next.pins.aux = pinArg("aux", next.pins.aux, true);
  next.pins.light1 = pinArg("light1", next.pins.light1, true);
  next.pins.light2 = pinArg("light2", next.pins.light2, true);
  next.pins.buzzer = pinArg("buzzer", next.pins.buzzer, true);
  next.pins.reserve = pinArg("reserve", next.pins.reserve, true);
  next.pins.grid = pinArg("grid", next.pins.grid, true);
  next.pins.roofNormal = pinArg("roof_normal", next.pins.roofNormal, true);
  next.pins.roofForce = pinArg("roof_force", next.pins.roofForce, true);
  next.pins.lowerNormal = pinArg("lower_normal", next.pins.lowerNormal, true);
  next.pins.lowerForce = pinArg("lower_force", next.pins.lowerForce, true);
  next.pins.startButton = pinArg("start_button", next.pins.startButton, true);
  next.pins.stopButton = pinArg("stop_button", next.pins.stopButton, true);
  next.relayActiveHigh = server.hasArg("relay_high");
  next.inputActiveLow = server.hasArg("input_low");
  next.reserveAvailableHigh = server.hasArg("reserve_high");
  next.gridPresentHigh = server.hasArg("grid_high");
  char reason[100];
  if (!validatePinMap(next.pins, reason, sizeof(reason))) {
    sendSimplePage("Pin validation failed", reason, true, "/pins");
    return;
  }
  stopPump("Pins", "Pin configuration update");
  config = next;
  saveConfig();
  scheduleRestart(2200);
  sendSimplePage("Pins saved", "Validated pin configuration saved. Restarting now.", false, "/");
}

void sendCalibrationPage() {
  if (!requireAuthentication()) return;
  beginPage("RCWL-1655 Calibration");
  chunkP(PSTR("<section class=\"grid\"><article class=\"card span-7\"><h2>Distance calibration</h2>"
              "<div class=\"notice\">The RCWL-1655 default GPIO mode requires at least 50 ms between measurements. "
              "Its blind zone is about 19-25 cm; mount the probe above that distance at the FULL water level.</div><br>"
              "<div class=\"metric-grid\">"));
  chunkf("<div class=\"metric\"><small>Live distance</small><strong>%.1f cm</strong></div>"
         "<div class=\"metric\"><small>Calculated level</small><strong>%.1f%%</strong></div></div><br>"
         "<form method=\"post\" action=\"/save_calibration\"><div class=\"form-grid\">"
         "<label>FULL distance cm<input type=\"number\" step=\"0.1\" name=\"full_cm\" min=\"19\" max=\"440\" value=\"%.1f\"></label>"
         "<label>EMPTY distance cm<input type=\"number\" step=\"0.1\" name=\"empty_cm\" min=\"24\" max=\"500\" value=\"%.1f\"></label>"
         "<label>Minimum valid cm<input type=\"number\" step=\"0.1\" name=\"min_cm\" min=\"19\" max=\"100\" value=\"%.1f\"></label>"
         "<label>Maximum valid cm<input type=\"number\" step=\"0.1\" name=\"max_cm\" min=\"100\" max=\"500\" value=\"%.1f\"></label>"
         "<label>Air temperature C<input type=\"number\" step=\"0.1\" name=\"air_temp\" min=\"-20\" max=\"70\" value=\"%.1f\"></label>"
         "<label>Median samples<input type=\"number\" step=\"2\" name=\"median\" min=\"3\" max=\"9\" value=\"%u\"></label>"
         "<label>Read interval while pumping ms<input type=\"number\" name=\"active_ms\" min=\"60\" max=\"5000\" value=\"%u\"></label>"
         "<label>Read interval while idle ms<input type=\"number\" name=\"idle_ms\" min=\"60\" max=\"10000\" value=\"%u\"></label>"
         "<label>Sensor stale shutdown seconds<input type=\"number\" name=\"stale_sec\" min=\"3\" max=\"120\" value=\"%u\"></label>"
         "</div><div class=\"actions\"><button type=\"submit\">SAVE CALIBRATION</button></div></form>"
         "<form method=\"post\" action=\"/capture_calibration\"><div class=\"actions\">"
         "<button name=\"point\" value=\"full\" type=\"submit\">CAPTURE LIVE AS FULL</button>"
         "<button name=\"point\" value=\"empty\" type=\"submit\" class=\"secondary\">CAPTURE LIVE AS EMPTY</button>"
         "</div></form></article>",
         currentDistanceCm, currentLevel, config.fullCm, config.emptyCm,
         config.minValidCm, config.maxValidCm, config.airTemperatureC,
         config.medianSamples, config.readActiveMs, config.readIdleMs,
         config.sensorStaleSec);
  chunkP(PSTR("<article class=\"card span-5\"><h2>Filtered history</h2><div class=\"history\">"));
  if (!historyCount) {
    chunkP(PSTR("<span>No valid samples yet.</span>"));
  } else {
    float minimum = config.fullCm, maximum = config.emptyCm;
    const float span = max(1.0f, maximum - minimum);
    for (uint8_t i = 0; i < historyCount; ++i) {
      const uint8_t index = (historyWrite + HISTORY_SIZE - historyCount + i) % HISTORY_SIZE;
      const float normalized = constrain((maximum - sensorHistory[index]) / span, 0.05f, 1.0f);
      chunkf("<i title=\"%.1f cm\" style=\"height:%.0f%%\"></i>",
             sensorHistory[index], normalized * 100.0f);
    }
  }
  chunkP(PSTR("</div><br><form method=\"post\" action=\"/toggle_history\"><button type=\"submit\" class=\"secondary\">"));
  chunk(historyFrozen ? "UNFREEZE HISTORY" : "FREEZE HISTORY");
  chunkP(PSTR("</button></form></article></section>"));
  endPage();
}

void handleSaveCalibration() {
  if (!requireAuthentication()) return;
  Config next = config;
  next.fullCm = argFloat("full_cm", next.fullCm, 19, 440);
  next.emptyCm = argFloat("empty_cm", next.emptyCm, 24, 500);
  next.minValidCm = argFloat("min_cm", next.minValidCm, 19, 100);
  next.maxValidCm = argFloat("max_cm", next.maxValidCm, 100, 500);
  next.airTemperatureC = argFloat("air_temp", next.airTemperatureC, -20, 70);
  next.medianSamples = argLong("median", next.medianSamples, 3, 9);
  if (!(next.medianSamples & 1)) ++next.medianSamples;
  next.readActiveMs = argLong("active_ms", next.readActiveMs, 60, 5000);
  next.readIdleMs = argLong("idle_ms", next.readIdleMs, 60, 10000);
  next.sensorStaleSec = argLong("stale_sec", next.sensorStaleSec, 3, 120);
  if (next.emptyCm - next.fullCm < 5.0f ||
      next.minValidCm >= next.maxValidCm) {
    sendSimplePage("Calibration rejected",
                   "EMPTY must be at least 5 cm farther than FULL, and valid range must increase.",
                   true, "/calibrate");
    return;
  }
  config = next;
  sampleCount = 0;
  sampleWrite = 0;
  saveConfig();
  recordAction("Calibration", "RCWL-1655 calibration saved");
  redirectTo("/calibrate?saved=1");
}

void handleCaptureCalibration() {
  if (!requireAuthentication()) return;
  if (!sensorValid()) {
    sendSimplePage("No valid echo", "Cannot capture until the RCWL-1655 has a stable reading.",
                   true, "/calibrate");
    return;
  }
  if (server.arg("point") == "full") config.fullCm = currentDistanceCm;
  else if (server.arg("point") == "empty") config.emptyCm = currentDistanceCm;
  if (config.emptyCm - config.fullCm < 5.0f) {
    sendSimplePage("Capture rejected", "FULL and EMPTY points must differ by at least 5 cm.",
                   true, "/calibrate");
    return;
  }
  saveConfig();
  sampleCount = 0;
  recordAction("Calibration", "Live distance captured");
  redirectTo("/calibrate?saved=1");
}

void formatEventTime(const EventRecord& event, char output[24]) {
  if (event.epoch) {
    time_t epoch = event.epoch;
    tm local;
    localtime_r(&epoch, &local);
    strftime(output, 24, "%Y-%m-%d %H:%M:%S", &local);
  } else {
    snprintf(output, 24, "Uptime %lus", static_cast<unsigned long>(event.uptimeSec));
  }
}

void sendLogsPage() {
  if (!requireAuthentication()) return;
  beginPage("Pump Event Logs");
  chunkP(PSTR("<section class=\"grid\"><article class=\"card span-12\"><div class=\"actions\">"
              "<a class=\"button secondary\" href=\"/logs.csv\">DOWNLOAD CSV</a>"
              "<form method=\"post\" action=\"/clear_logs\" onsubmit=\"return confirm('Clear all logs?')\">"
              "<button class=\"danger\" type=\"submit\">CLEAR LOGS</button></form></div><br>"
              "<div class=\"table-wrap\"><table><thead><tr><th>Time</th><th>State</th><th>Mode</th>"
              "<th>Level</th><th>Source</th><th>Reason</th></tr></thead><tbody>"));
  const uint8_t count = min(logStore.count, config.logLimit);
  if (!count) chunkP(PSTR("<tr><td colspan=\"6\">No pump events recorded.</td></tr>"));
  for (uint8_t i = 0; i < count; ++i) {
    const uint8_t index = (logStore.head + MAX_LOGS - 1 - i) % MAX_LOGS;
    const EventRecord& event = logStore.records[index];
    char timestamp[24];
    formatEventTime(event, timestamp);
    chunkf("<tr><td>%s</td><td>%s</td><td>%s</td><td>",
           timestamp, event.pumpOn ? "ON" : "OFF",
           modeText(static_cast<PumpMode>(event.mode)));
    if (event.levelTenths >= 0) chunkf("%.1f%%", event.levelTenths / 10.0f);
    else chunk("--");
    chunkP(PSTR("</td><td>"));
    chunkEscaped(event.source);
    chunkP(PSTR("</td><td class=\"wrap\">"));
    chunkEscaped(event.reason);
    chunkP(PSTR("</td></tr>"));
  }
  chunkP(PSTR("</tbody></table></div></article><article class=\"card span-12\"><h2>Log policy</h2>"
              "<form method=\"post\" action=\"/save_logs\"><div class=\"form-grid three\">"
              "<label class=\"check\"><input type=\"checkbox\" name=\"log_on\""));
  if (config.logEnabled) chunk(" checked");
  chunkf("> Enable pump event logging</label>"
         "<label>Maximum records<input type=\"number\" name=\"log_limit\" min=\"5\" max=\"32\" value=\"%u\"></label>"
         "<label>Retention days<input type=\"number\" name=\"log_days\" min=\"1\" max=\"365\" value=\"%u\"></label>"
         "</div><div class=\"actions\"><button type=\"submit\">SAVE LOG POLICY</button></div></form>"
         "</article></section>", config.logLimit, config.logRetentionDays);
  endPage();
}

void handleLogsCsv() {
  if (!requireAuthentication()) return;
  server.sendHeader("Content-Disposition", "attachment; filename=tank-events.csv");
  beginChunked("text/csv; charset=utf-8");
  chunk("time,state,mode,level_percent,source,reason\r\n");
  const uint8_t count = min(logStore.count, config.logLimit);
  for (uint8_t i = 0; i < count; ++i) {
    const uint8_t index = (logStore.head + MAX_LOGS - 1 - i) % MAX_LOGS;
    const EventRecord& event = logStore.records[index];
    char timestamp[24];
    formatEventTime(event, timestamp);
    chunkf("\"%s\",%s,%s,", timestamp, event.pumpOn ? "ON" : "OFF",
           modeText(static_cast<PumpMode>(event.mode)));
    if (event.levelTenths >= 0) chunkf("%.1f", event.levelTenths / 10.0f);
    chunk(",\"");
    chunkEscaped(event.source, true);
    chunk("\",\"");
    chunkEscaped(event.reason, true);
    chunk("\"\r\n");
  }
  endChunked();
}

void handleSaveLogs() {
  if (!requireAuthentication()) return;
  config.logEnabled = server.hasArg("log_on");
  config.logLimit = argLong("log_limit", config.logLimit, 5, MAX_LOGS);
  config.logRetentionDays = argLong("log_days", config.logRetentionDays, 1, 365);
  if (logStore.count > config.logLimit) {
    logStore.count = config.logLimit;
    saveLogs();
  }
  saveConfig();
  redirectTo("/logs?saved=1");
}

bool usingDefaultPassword() {
  uint8_t digest[32];
  passwordDigest("admin1234", config.passwordSalt, digest);
  return constantTimeEqual(digest, config.passwordHash, sizeof(digest));
}

void sendSecurityPage() {
  if (!requireAuthentication()) return;
  beginPage("Security & Password");
  if (usingDefaultPassword())
    chunkP(PSTR("<div class=\"notice bad\">Default password is active. Change it before connecting this controller to an untrusted network.</div><br>"));
  chunkP(PSTR("<section class=\"grid\"><article class=\"card span-6\"><h2>Access policy</h2>"
              "<form method=\"post\" action=\"/save_security\"><label class=\"check\">"
              "<input type=\"checkbox\" name=\"auth_on\""));
  if (config.authEnabled) chunk(" checked");
  chunkP(PSTR("> Enable login protection</label><label class=\"check\">"
              "<input type=\"checkbox\" name=\"protect_dashboard\""));
  if (config.protectDashboard) chunk(" checked");
  chunkP(PSTR("> Protect dashboard and telemetry</label><label class=\"check\">"
              "<input type=\"checkbox\" name=\"protect_pump\""));
  if (config.protectPump) chunk(" checked");
  chunkP(PSTR("> Require login for pump commands</label><label class=\"check\">"
              "<input type=\"checkbox\" name=\"protect_outputs\""));
  if (config.protectOutputs) chunk(" checked");
  chunkf("> Require login for auxiliary outputs</label>"
         "<label>Session timeout minutes<input type=\"number\" name=\"session_minutes\" min=\"5\" max=\"1440\" value=\"%u\"></label>"
         "<div class=\"actions\"><button type=\"submit\">SAVE SECURITY POLICY</button></div></form></article>",
         config.sessionTimeoutMin);
  chunkP(PSTR("<article class=\"card span-6\"><h2>Change password</h2>"
              "<form method=\"post\" action=\"/change_password\">"
              "<label>Current password<input type=\"password\" name=\"current_password\" maxlength=\"64\" required></label>"
              "<label>New password<input type=\"password\" name=\"new_password\" minlength=\"8\" maxlength=\"64\" required></label>"
              "<label>Confirm new password<input type=\"password\" name=\"confirm_password\" minlength=\"8\" maxlength=\"64\" required></label>"
              "<div class=\"actions\"><button type=\"submit\">CHANGE PASSWORD</button></div></form></article>"
              "<article class=\"card span-12\"><h2>Factory reset</h2>"
              "<div class=\"notice warn\">Erases WiFi, calibration, logs, runtime, password, and all controller settings.</div><br>"
              "<form method=\"post\" action=\"/factory_reset\" onsubmit=\"return confirm('Erase all controller data?')\">"
              "<label>Current password<input type=\"password\" name=\"password\" maxlength=\"64\" required></label>"
              "<div class=\"actions\"><button type=\"submit\" class=\"danger\">ERASE & RESTART</button></div>"
              "</form></article></section>"));
  endPage();
}

void handleSaveSecurity() {
  if (!requireAuthentication()) return;
  config.authEnabled = server.hasArg("auth_on");
  config.protectDashboard = server.hasArg("protect_dashboard");
  config.protectPump = server.hasArg("protect_pump");
  config.protectOutputs = server.hasArg("protect_outputs");
  config.sessionTimeoutMin =
      argLong("session_minutes", config.sessionTimeoutMin, 5, 1440);
  saveConfig();
  recordAction("Security", "Access policy saved");
  redirectTo("/security?saved=1");
}

void handleChangePassword() {
  if (!requireAuthentication()) return;
  const String current = server.arg("current_password");
  const String next = server.arg("new_password");
  if (!passwordMatches(current.c_str())) {
    sendSimplePage("Password not changed", "Current password is incorrect.", true,
                   "/security");
    return;
  }
  if (next.length() < 8 || next.length() > 64 ||
      next != server.arg("confirm_password")) {
    sendSimplePage("Password not changed",
                   "Use 8-64 matching characters for the new password.", true,
                   "/security");
    return;
  }
  setPassword(config, next.c_str());
  saveConfig();
  createSession();
  char cookie[160];
  snprintf(cookie, sizeof(cookie),
           "ESPSESSION=%s; Path=/; HttpOnly; SameSite=Strict; Max-Age=%lu",
           sessionToken,
           static_cast<unsigned long>(config.sessionTimeoutMin) * 60UL);
  server.sendHeader("Set-Cookie", cookie);
  recordAction("Security", "Administrator password changed");
  redirectTo("/security?saved=1");
}

void sendUpdatePage() {
  if (!requireAuthentication()) return;
  beginPage("Firmware & Reboot");
  chunkP(PSTR("<section class=\"grid\"><article class=\"card span-7\"><h2>Signed-in OTA upload</h2>"
              "<div class=\"notice warn\">Upload a compiled .bin for this exact board and partition layout. "
              "The pump is switched off before flashing. Keep mains and low-voltage power stable.</div><br>"
              "<form method=\"post\" action=\"/update\" enctype=\"multipart/form-data\">"
              "<label>Firmware binary<input type=\"file\" name=\"firmware\" accept=\".bin\" required></label>"
              "<div class=\"actions\"><button type=\"submit\">UPLOAD & INSTALL</button></div></form></article>"
              "<article class=\"card span-5\"><h2>Manual reboot</h2>"
              "<form method=\"post\" action=\"/reboot_now\" onsubmit=\"return confirm('Reboot controller now?')\">"
              "<button type=\"submit\" class=\"danger\">SAFE REBOOT</button></form></article>"
              "<article class=\"card span-12\"><h2>Scheduled reboot</h2>"
              "<form method=\"post\" action=\"/save_reboot\"><div class=\"form-grid three\">"
              "<label class=\"check\"><input type=\"checkbox\" name=\"reboot_on\""));
  if (config.rebootEnabled) chunk(" checked");
  chunkP(PSTR("> Enable idle-only reboot</label><label>Reboot time"));
  sendTimeInput("reboot_time", config.rebootMinute);
  chunkf("</label><label>Repeat every days<input type=\"number\" name=\"reboot_days\" min=\"1\" max=\"365\" value=\"%u\"></label>"
         "</div><div class=\"actions\"><button type=\"submit\">SAVE REBOOT SCHEDULE</button></div></form>"
         "</article></section>", config.rebootEveryDays);
  endPage();
}

void handleSaveReboot() {
  if (!requireAuthentication()) return;
  config.rebootEnabled = server.hasArg("reboot_on");
  updateMinuteArg("reboot_time", config.rebootMinute);
  config.rebootEveryDays = argLong("reboot_days", config.rebootEveryDays, 1, 365);
  saveConfig();
  redirectTo("/update?saved=1");
}

void sendLoginPage(const char* error = nullptr) {
  beginPage("Controller Login", false);
  chunkP(PSTR("<section class=\"login\"><article class=\"card\"><h2>Administrator access</h2>"));
  if (error) {
    chunkP(PSTR("<div class=\"notice bad\">"));
    chunkEscaped(error);
    chunkP(PSTR("</div><br>"));
  }
  chunkP(PSTR("<form method=\"post\" action=\"/login\"><label>Password"
              "<input type=\"password\" name=\"password\" maxlength=\"64\" autofocus required></label>"
              "<button type=\"submit\">SIGN IN</button></form></article></section>"));
  endPage();
}

void handleLogin() {
  const uint32_t now = millis();
  if (!due(now, loginLockedUntilMs)) {
    sendLoginPage("Too many attempts. Wait 30 seconds.");
    return;
  }
  const String password = server.arg("password");
  if (password.length() <= 64 && passwordMatches(password.c_str())) {
    loginFailures = 0;
    createSession();
    char cookie[160];
    snprintf(cookie, sizeof(cookie),
             "ESPSESSION=%s; Path=/; HttpOnly; SameSite=Strict; Max-Age=%lu",
             sessionToken,
             static_cast<unsigned long>(config.sessionTimeoutMin) * 60UL);
    server.sendHeader("Set-Cookie", cookie);
    redirectTo("/");
    return;
  }
  if (++loginFailures >= 5) {
    loginFailures = 0;
    loginLockedUntilMs = now + 30000UL;
  }
  sendLoginPage("Incorrect password.");
}

void formatUptime(char output[24]) {
  uint32_t seconds = uptimeSeconds();
  const uint16_t days = seconds / 86400UL;
  seconds %= 86400UL;
  const uint8_t hours = seconds / 3600UL;
  seconds %= 3600UL;
  const uint8_t minutes = seconds / 60UL;
  if (days) snprintf(output, 24, "%ud %02uh %02um", days, hours, minutes);
  else snprintf(output, 24, "%02uh %02um %02lus", hours, minutes,
                static_cast<unsigned long>(seconds % 60UL));
}

void formatClock(char timeText[12], char dateText[28]) {
  tm local;
  if (!clockNow(local)) {
    copyBounded(timeText, 12, "--:--:--");
    copyBounded(dateText, 28, "Time not synchronized");
    return;
  }
  strftime(timeText, 12, "%H:%M:%S", &local);
  strftime(dateText, 28, "%a, %d %b %Y", &local);
}

void automaticStatus(char output[72]) {
  if (!config.automationEnabled) {
    copyBounded(output, 72, "Disabled");
    return;
  }
  uint8_t startLevel, stopLevel;
  char window[45];
  if (activeAutoTargets(startLevel, stopLevel, window, sizeof(window)))
    snprintf(output, 72, "%s / start %u%% / stop %u%%", window, startLevel, stopLevel);
  else copyBounded(output, 72, window);
}

void stageText(char output[150]) {
  if (pumpOn) {
    const uint32_t age = (millis() - pumpStartedMs) / 1000UL;
    if (pumpMode == PumpMode::Force) {
      const uint32_t remaining =
          age >= config.forceLimitSec ? 0 : config.forceLimitSec - age;
      snprintf(output, 150, "%s running for %lum %lus / force remaining %lus",
               modeText(pumpMode), static_cast<unsigned long>(age / 60),
               static_cast<unsigned long>(age % 60),
               static_cast<unsigned long>(remaining));
    } else {
      snprintf(output, 150, "%s running for %lum %lus / target %u%%",
               modeText(pumpMode), static_cast<unsigned long>(age / 60),
               static_cast<unsigned long>(age % 60), runningTarget);
    }
  } else {
    const uint32_t age = pumpStoppedMs ? (millis() - pumpStoppedMs) / 1000UL : 0;
    snprintf(output, 150, "IDLE for %lum %lus / %s: %s",
             static_cast<unsigned long>(age / 60),
             static_cast<unsigned long>(age % 60), lastSource, lastAction);
  }
}

void sendData() {
  if (config.protectDashboard && !requireAuthentication(true)) return;
  char timeText[12], dateText[28], uptimeText[24], automatic[72], stage[150];
  formatClock(timeText, dateText);
  formatUptime(uptimeText);
  automaticStatus(automatic);
  stageText(stage);
  tm timeProbe;
  const bool timeSynced = clockNow(timeProbe);
  const bool connected = WiFi.status() == WL_CONNECTED;
  char ip[16] = "--";
  if (connected || apRunning) {
    const IPAddress address = connected ? WiFi.localIP() : WiFi.softAPIP();
    snprintf(ip, sizeof(ip), "%u.%u.%u.%u", address[0], address[1], address[2],
             address[3]);
  }
  const double energy = totalPumpSeconds * static_cast<double>(config.pumpWatts) / 3600000.0;
  beginChunked("application/json");
  chunkf("{\"time\":\"%s\",\"date\":\"%s\",\"uptime\":\"%s\","
         "\"wifi\":%s,\"ip\":\"%s\",\"rssi\":%d,\"heap\":%u,"
         "\"time_synced\":%s,\"sensor_valid\":%s,\"sensor_status\":\"%s\","
         "\"distance\":%.2f,\"level\":%.2f,\"pump\":%s,\"mode\":\"%s\","
         "\"normal_demand\":%s,\"force_demand\":%s,\"aux\":%s,\"light1\":%s,"
         "\"light2\":%s,\"grid_ok\":%s,\"reserve_ok\":%s,"
         "\"energy_kwh\":%.5f,\"energy_cost\":%.4f,\"last_action_id\":%lu,"
         "\"stage\":\"",
         timeText, dateText, uptimeText, connected ? "true" : "false", ip,
         connected ? WiFi.RSSI() : 0, ESP.getFreeHeap() / 1024,
         timeSynced ? "true" : "false",
         sensorValid() ? "true" : "false",
         sensorValid() ? "OK" : "No valid echo",
         currentDistanceCm, currentLevel, pumpOn ? "true" : "false",
         modeText(pumpMode), anyNormalDemand() ? "true" : "false",
         anyForceDemand() ? "true" : "false", auxOn ? "true" : "false",
         lightsOn[0] ? "true" : "false", lightsOn[1] ? "true" : "false",
         gridAvailable() ? "true" : "false", reserveAvailable() ? "true" : "false",
         energy, energy * config.unitPrice, static_cast<unsigned long>(lastActionId));
  chunkEscaped(stage, true);
  chunk("\",\"auto_status\":\"");
  chunkEscaped(automatic, true);
  chunk("\",\"last_source\":\"");
  chunkEscaped(lastSource, true);
  chunk("\",\"last_action\":\"");
  chunkEscaped(lastAction, true);
  chunk("\"}");
  endChunked();
}

void sendActionResult(bool ok, const char* message) {
  beginChunked("application/json");
  chunk(ok ? "{\"ok\":true,\"message\":\"" : "{\"ok\":false,\"message\":\"");
  chunkEscaped(message, true);
  chunk("\"}");
  endChunked();
}

void handleAction() {
  const String action = server.arg("action");
  const bool pumpAction = action == "normal" || action == "force";
  const bool outputAction =
      action == "aux" || action == "light1" || action == "light2" ||
      action == "reset-energy";
  if (pumpAction && !requireProtectedAction(config.protectPump)) return;
  if (outputAction && !requireProtectedAction(config.protectOutputs)) return;
  if (!pumpAction && !outputAction) {
    sendActionResult(false, "Unknown command");
    return;
  }
  if (pumpAction && buttonInputs[1].stable) {
    sendActionResult(false, "Physical STOP button is held");
    return;
  }

  bool actionOk = true;
  if (action == "normal") {
    webNormalDemand = !webNormalDemand;
    webForceDemand = false;
    if (webNormalDemand) {
      physicalStopLatched = false;
      actionOk =
          startPump(PumpMode::Manual, "Web normal", config.manualTarget, true);
    }
    else if (pumpOn && pumpMode == PumpMode::Manual)
      stopPump("Web normal", "Normal demand switched OFF");
  } else if (action == "force") {
    webForceDemand = !webForceDemand;
    webNormalDemand = false;
    if (webForceDemand) {
      physicalStopLatched = false;
      actionOk = startPump(PumpMode::Force, "Web force", 100, true);
    }
    else if (pumpOn && pumpMode == PumpMode::Force)
      stopPump("Web force", "Force demand switched OFF");
  } else if (action == "aux") {
    auxOn = !auxOn;
    writeOutput(config.pins.aux, auxOn);
    recordAction("Web output", auxOn ? "Aux load ON" : "Aux load OFF");
  } else if (action == "light1" || action == "light2") {
    const uint8_t index = action == "light1" ? 0 : 1;
    lightsOn[index] = !lightsOn[index];
    lightOverride[index] = config.lightTimers[index].enabled;
    writeOutput(index ? config.pins.light2 : config.pins.light1, lightsOn[index]);
    recordAction("Web output", lightsOn[index] ? "Light ON" : "Light OFF");
  } else if (action == "reset-energy") {
    totalPumpSeconds = 0;
    saveRuntime();
    recordAction("Energy", "Pump energy total reset");
  }
  sendActionResult(actionOk, lastAction);
}

void handleWifiScan() {
  if (!requireAuthentication(true)) return;
  int16_t count = WiFi.scanComplete();
  if (count == WIFI_SCAN_FAILED) {
    WiFi.scanNetworks(true, true);
    server.send(202, "application/json", "{\"pending\":true}");
    return;
  }
  if (count == WIFI_SCAN_RUNNING) {
    server.send(202, "application/json", "{\"pending\":true}");
    return;
  }
  beginChunked("application/json");
  chunk("[");
  for (int16_t i = 0; i < count; ++i) {
    if (i) chunk(",");
    chunk("{\"ssid\":\"");
    chunkEscaped(WiFi.SSID(i).c_str(), true);
    chunkf("\",\"rssi\":%d,\"open\":%s}", WiFi.RSSI(i),
           WiFi.encryptionType(i) == WIFI_AUTH_OPEN ? "true" : "false");
  }
  chunk("]");
  endChunked();
  WiFi.scanDelete();
}

void handleFactoryReset() {
  if (!requireAuthentication()) return;
  if (!passwordMatches(server.arg("password").c_str())) {
    sendSimplePage("Reset rejected", "Password is incorrect.", true, "/security");
    return;
  }
  stopPump("Security", "Factory reset requested");
  preferences.clear();
  sendSimplePage("Factory reset", "All controller data erased. Restarting.", false, "/");
  scheduleRestart(1800);
}

void handleNotFound() {
  if (apRunning) {
    String location = "http://" + WiFi.softAPIP().toString() + "/";
    server.sendHeader("Location", location);
    server.send(302, "text/plain", "");
  } else {
    server.send(404, "text/plain", "Not found");
  }
}

void initializeWebServer() {
  static const char* headers[] = {"Cookie"};
  server.collectHeaders(headers, 1);

  server.on("/", HTTP_GET, sendDashboard);
  server.on("/data", HTTP_GET, sendData);
  server.on("/settings", HTTP_GET, sendSettingsPage);
  server.on("/save_settings", HTTP_POST, handleSaveSettings);
  server.on("/wifi", HTTP_GET, sendWifiPage);
  server.on("/save_wifi", HTTP_POST, handleSaveWifi);
  server.on("/api/scan", HTTP_GET, handleWifiScan);
  server.on("/pins", HTTP_GET, sendPinsPage);
  server.on("/save_pins", HTTP_POST, handleSavePins);
  server.on("/calibrate", HTTP_GET, sendCalibrationPage);
  server.on("/save_calibration", HTTP_POST, handleSaveCalibration);
  server.on("/capture_calibration", HTTP_POST, handleCaptureCalibration);
  server.on("/toggle_history", HTTP_POST, []() {
    if (!requireAuthentication()) return;
    historyFrozen = !historyFrozen;
    redirectTo("/calibrate");
  });
  server.on("/logs", HTTP_GET, sendLogsPage);
  server.on("/logs.csv", HTTP_GET, handleLogsCsv);
  server.on("/save_logs", HTTP_POST, handleSaveLogs);
  server.on("/clear_logs", HTTP_POST, []() {
    if (!requireAuthentication()) return;
    resetLogStore();
    saveLogs();
    redirectTo("/logs?saved=1");
  });
  server.on("/security", HTTP_GET, sendSecurityPage);
  server.on("/save_security", HTTP_POST, handleSaveSecurity);
  server.on("/change_password", HTTP_POST, handleChangePassword);
  server.on("/factory_reset", HTTP_POST, handleFactoryReset);
  server.on("/login", HTTP_GET, []() {
    if (!config.authEnabled || authenticated()) redirectTo("/");
    else sendLoginPage();
  });
  server.on("/login", HTTP_POST, handleLogin);
  server.on("/logout", HTTP_GET, []() {
    sessionToken[0] = '\0';
    server.sendHeader("Set-Cookie",
                      "ESPSESSION=; Path=/; HttpOnly; SameSite=Strict; Max-Age=0");
    redirectTo("/login");
  });
  server.on("/api/action", HTTP_POST, handleAction);
  server.on("/update", HTTP_GET, sendUpdatePage);
  server.on("/save_reboot", HTTP_POST, handleSaveReboot);
  server.on("/reboot_now", HTTP_POST, []() {
    if (!requireAuthentication()) return;
    stopPump("System", "Manual reboot requested");
    sendSimplePage("Rebooting", "The controller is restarting safely.", false, "/");
    scheduleRestart(1500);
  });
  server.on(
      "/update", HTTP_POST,
      []() {
        if (!otaAllowed) {
          if (!server.client().connected()) return;
          sendJsonError(401, "Login required");
          return;
        }
        if (otaSucceeded && !Update.hasError()) {
          sendSimplePage("Update installed", "Firmware accepted. Restarting now.", false, "/");
          scheduleRestart(1600);
        } else {
          sendSimplePage("Update failed", "The firmware image was rejected or incomplete.",
                         true, "/update");
        }
        otaAllowed = false;
        otaSucceeded = false;
      },
      []() {
        HTTPUpload& upload = server.upload();
        if (upload.status == UPLOAD_FILE_START) {
          otaAllowed = authenticated();
          otaSucceeded = false;
          if (!otaAllowed) return;
          stopPump("Firmware", "Pump stopped for firmware update");
          saveRuntime();
          otaSucceeded = Update.begin(UPDATE_SIZE_UNKNOWN);
        } else if (upload.status == UPLOAD_FILE_WRITE) {
          if (otaAllowed && otaSucceeded &&
              Update.write(upload.buf, upload.currentSize) != upload.currentSize)
            otaSucceeded = false;
        } else if (upload.status == UPLOAD_FILE_END) {
          if (otaAllowed && otaSucceeded) otaSucceeded = Update.end(true);
        } else if (upload.status == UPLOAD_FILE_ABORTED) {
          otaSucceeded = false;
        }
        esp_task_wdt_reset();
        yield();
      });
  server.onNotFound(handleNotFound);
  server.begin();
}

void initializeWatchdog() {
#if ESP_IDF_VERSION_MAJOR >= 5
  esp_task_wdt_config_t watchdog = {
      .timeout_ms = 8000,
      .idle_core_mask = (1U << portNUM_PROCESSORS) - 1,
      .trigger_panic = true,
  };
  esp_task_wdt_init(&watchdog);
#else
  esp_task_wdt_init(8, true);
#endif
  esp_task_wdt_add(nullptr);
}

}  // namespace

void setup() {
  Serial.begin(115200);
  preferences.begin("tankctl", false);
  loadConfig();
  loadLogs();
  initializeHardware();
  initializeNetwork();
  initializeWebServer();
  initializeWatchdog();

  Serial.println();
  Serial.printf("Water controller %s\n", FIRMWARE_VERSION);
  Serial.printf("AP: %s / http://%s\n", config.apSsid,
                WiFi.softAPIP().toString().c_str());
  Serial.println("Default login after factory reset: admin1234");
}

void loop() {
  const uint32_t now = millis();
  server.handleClient();
  tickNetwork(now);
  tickSensor(now);
  tickInputs(now);
  tickPump(now);
  tickBuzzer(now);
  tickLightSchedules();
  tickScheduledFill(now);
  tickScheduledReboot(now);
  pruneOldLogs(now);

  if (restartAtMs && due(now, restartAtMs) && !pumpOn) {
    saveRuntime();
    delay(50);
    ESP.restart();
  }

  esp_task_wdt_reset();
  delay(1);
}
