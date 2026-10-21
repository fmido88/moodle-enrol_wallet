# Auto-Country Detection & Multi-Currency Regional Pricing Plan

## Overview
This technical plan outlines the implementation of automatic country/location detection and multi-currency regional pricing tiers for the Moodle `enrol_wallet` plugin.

Course creators will be able to offer localized pricing packages in the same course (e.g. Egypt: 200, 500, 1000 EGP; Gulf: 100, 250, 500 SAR; USA/International: $10, $25, $50 USD). Visitors will automatically see pricing plans tailored to their detected location, with local payment gateways and an optional manual country switcher.

---

## Architecture & Data Flow

```
Visitor Arrives at Course Enrolment Page (/enrol/index.php?id=X)
      │
      ▼
Check Country Source:
  1. Manual Selection ($SESSION->wallet_selected_country)
  2. Cloudflare Header ($_SERVER['HTTP_CF_IPCOUNTRY'])
  3. Server GeoIP / Proxy (HTTP_X_COUNTRY_CODE)
  4. Moodle User Profile ($USER->country)
  5. GeoIP API Fallback (ip-api.com cached in session)
      │
      ▼
Normalized Country Code (e.g. EG, SA, US)
      │
      ▼
Map to Target Region (EG, GULF, GLOBAL)
      │
      ▼
Filter Course Pricing Plans:
  - If Match Found: Display Regional Plans (e.g. EGP or SAR)
  - If No Match Found: Fallback to GLOBAL Plans (USD)
      │
      ▼
Render Modern Pricing Cards + Region Switcher
```

---

## Detailed Implementation Steps

### 1. Geo-Detection Utility (`classes/local/utils/geo.php`)
Create `enrol_wallet\local\utils\geo` helper:
* `get_user_country()`:
  * Reads `wallet_region` query param -> stores in session `$SESSION->wallet_selected_country`.
  * Checks session override.
  * Checks Cloudflare `HTTP_CF_IPCOUNTRY` header (fastest, 0 network latency).
  * Checks server proxy headers (`HTTP_X_COUNTRY_CODE`, `GEOIP_COUNTRY_CODE`).
  * Checks `$USER->country` if logged in.
  * Checks `$SESSION->wallet_geo_detected` (cached IP lookup).
  * Fallback to `'GLOBAL'`.
* `get_region_for_country(string $code)`:
  * `'EG'` -> `'EG'` (Egypt)
  * `'SA'`, `'AE'`, `'KW'`, `'QA'`, `'BH'`, `'OM'`, `'GULF'` -> `'GULF'` (Gulf Cooperation Council)
  * Any other -> `'GLOBAL'` (Rest of the World)
* `get_region_options()`:
  * Returns localized region options array for forms.
* `unpack_card_meta(?string $customchar1)` & `pack_card_meta(string $region, string $color)`:
  * Manages JSON storage in `customchar1` without modifying DB schema.

### 2. Instance Edit Form & Data Binding (`classes/plugin.php`)
* Add `targetregion` select element in `edit_instance_form()`:
  * 🌐 Global / All Countries (Default)
  * 🇪🇬 Egypt (EG)
  * 🇸🇦 Saudi Arabia & Gulf (GULF)
* In `add_instance()` and `update_instance()`:
  * Pack `targetregion` and `cardcolor` into `customchar1`.
* In `edit_instance_validation()`:
  * Validate `targetregion` and `cardcolor`.

### 3. Site Administration Settings (`classes/local/utils/settings.php` & `classes/local/config.php`)
* Add `enrol_wallet/enablegeopricing` (checkbox, default 1).
* Add `enrol_wallet/showcountryswitcher` (checkbox, default 1).
* Document properties in `config.php`.

### 4. Pricing Table Renderer & Mustache Template (`classes/plugin.php` & `templates/pricing_table.mustache`)
* In `render_pricing_table()`:
  * Detect visitor region via `geo::get_user_country()`.
  * Filter active course instances matching the detected region (fallback to `GLOBAL`).
  * If multiple regions exist in the course, build `regions_switcher` array.
* In `templates/pricing_table.mustache`:
  * Render the Region Switcher pills/buttons in the header so visitors can toggle between countries if using a VPN or traveling.

### 5. Localization (`lang/en/enrol_wallet.php` & `lang/ar/enrol_wallet.php`)
* Add all English and Arabic translation strings for regions, settings, and switchers.

### 6. Version & Package Update
* Bump `version.php` to `2026051005`.
* Update `wallet.zip`.
