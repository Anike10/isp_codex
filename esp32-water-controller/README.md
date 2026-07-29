# ESP32 Water Tank Controller (RCWL-1655)

A self-contained, non-blocking water-tank and pump controller for a classic ESP32
DevKit. It recreates the supplied dashboard, settings, WiFi, pin, calibration,
log, security, and firmware-update pages without storing eight duplicated HTML
documents in RAM.

## What is included

- RCWL-1655 GPIO-mode driver with interrupt-timed Echo capture
- 12 us Trigger pulse and a hard minimum 60 ms measurement interval
- Odd-sample median filtering, configurable temperature compensation, and
  stale-sensor shutdown
- Automatic start/stop levels and two cross-midnight time windows
- Manual target, hard FORCE timer, maximum runtime, and minimum-off protection
- Reserve-tank, grid-fail, no-echo, and no-level-rise dry-run guards
- Daily scheduled fill, two light schedules, and idle-only scheduled reboot
- Roof/lower-floor normal and force switches plus momentary start/stop inputs
- Pump, auxiliary load, two lights, and buzzer outputs
- WiFi station, optional static IPv4, fallback setup AP, captive DNS, mDNS, NTP,
  and asynchronous WiFi scanning
- Password-hashed login, rate limiting, expiring HttpOnly session cookie, and
  protected commands
- OTA `.bin` upload, fixed-size NVS settings, 32-record NVS event ring, CSV
  export, and pump energy/cost estimate
- Task watchdog and no unbounded wait loops or `pulseIn()`

## RCWL-1655 wiring

The factory/default resistor configuration is GPIO mode. Leave its mode resistor
unchanged.

| RCWL-1655 | ESP32 default | Note |
|---|---:|---|
| VCC | 3V3 | Recommended for direct ESP32 logic |
| Trig | GPIO 5 | Output |
| Echo | GPIO 18 | Interrupt input |
| GND | GND | Common ground |

The module supports a 2.8-5.5 V supply, and Echo follows the module supply
voltage. If VCC is connected to 5 V, **do not connect Echo directly to an
ESP32**. Add a resistor divider or 3.3 V logic-level shifter. Powering the module
from 3.3 V avoids that level mismatch.

The useful near limit is approximately 19-25 cm. Mount the probe perpendicular to
the water and at least 25 cm above the highest possible water surface. Only the
front of the probe is splash-resistant; put the adapter PCB and all connections
inside a dry enclosure.

## Default pin map

| Function | GPIO |
|---|---:|
| RCWL Trigger / Echo | 5 / 18 |
| Pump relay | 23 |
| Auxiliary relay | 22 |
| Light 1 / Light 2 | 25 / 26 |
| Buzzer | 27 |
| Reserve / grid inputs | 19 / 21 |
| Roof normal / force | 13 / 14 |
| Lower normal / force | 16 / 17 |
| Start / stop buttons | 32 / 33 |

Relays default to active LOW and switches/buttons to active LOW. Every pin and
polarity can be changed at `/pins`. The firmware rejects duplicate assignments,
input-only output pins, flash pins, and common boot-strapping pins.

GPIO 16 and 17 may be unavailable on ESP32 modules with PSRAM. Reassign them
before wiring if the selected board reserves those pins.

## First start

1. Build and upload the firmware.
2. Join WiFi `WaterTank-Setup` with password `watertank`.
3. Open `http://192.168.4.1/`.
4. Sign in with the factory password `admin1234`.
5. Change the password, configure router WiFi, verify pin polarity, and calibrate
   FULL and EMPTY distances before enabling a real pump.

## Build

Install PlatformIO, then run from this directory:

```sh
pio run
pio run --target upload
pio device monitor
```

The project pins `espressif32@6.10.0` and uses only libraries included with the
Arduino ESP32 framework. `min_spiffs.csv` provides two OTA application slots; no
filesystem is required.

## Fail-safe behavior

- Outputs are written to OFF before their GPIO mode becomes output.
- Normal, automatic, and scheduled modes require a valid sensor.
- Loss of Echo, reserve water, or grid permission stops the pump when the
  corresponding guard is enabled.
- FORCE has a mandatory 1-3600 second timer and never bypasses the grid guard.
- Maximum runtime applies to every mode.
- Dry-run detection checks for a configurable minimum level rise.
- Network connection and time synchronization never block pump control.
- OTA and reboot paths stop the pump and persist runtime before restarting.
- A watchdog restarts the ESP32 if the main task becomes unresponsive.

Software cannot make mains equipment intrinsically safe. Drive the pump through
a correctly rated contactor/overload system with electrical isolation, fusing,
earthing, manual emergency stop, and a qualified electrician's review.

