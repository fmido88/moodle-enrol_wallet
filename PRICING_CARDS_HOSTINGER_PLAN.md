# Hostinger-Style Pricing Cards & Course-Level Rich Text Plan Details

## 1. Overview
The goal is to transform the course enrolment pricing cards into modern, SaaS-grade pricing tables (modeled after Hostinger's renowned design), while providing course administrators with a **Moodle Rich Text Editor** directly on the enrolment instance settings form (immediately below the **Cost** field) to define plan features, durations, exams, and deliverables.

---

## 2. Key Requirements & Architecture Decisions (from Interview)
1. **Editor Type:** Full Moodle Rich Text Editor (supporting HTML, bold text, custom colors, badges, and bullet lists).
2. **Form Placement:** Positioned directly under the **Cost** field (above Payment Account) in the `enrol_wallet` course enrolment instance settings.
3. **Storage:** Stored in Moodle's native `customtext4` TEXT column on the `mdl_enrol` table (no database migration or schema modification required).
4. **Theme Adaptability:** Full support for both **Dark Mode** and **Light Mode**, specifically aligned with the **SmartLearn** theme (`[data-bs-theme="dark"]`, `.theme-dark`, `html[data-theme="dark"]`, `.smartlearn-dark`).
5. **Feature List Styling (Hostinger Look):** Unordered lists (`<ul><li>...</li></ul>`) produced by the rich editor are automatically styled into modern Hostinger feature rows with SVG checkmark icons (✔), while preserving any custom inline formatting, bold tags, or badges.
6. **Featured / "Most Popular" Plan:** Any plan with a badge (e.g., "Most Popular", "Best Value") or accent color automatically receives the elevated Hostinger card treatment: prominent top header badge, glowing border, elevated hover effect, and a colored action button.

---

## 3. Implementation Steps

### Step 1: Course Enrolment Instance Settings Form (`classes/plugin.php`)
- Relocate the plan features editor so it appears **immediately beneath the Cost field** in `edit_instance_form()`.
- Upgrade the field from a plain textarea to a rich text editor:
  ```php
  $mform->addElement('editor', 'customtext4_editor', get_string('cardfeatures', 'enrol_wallet'), ['rows' => 6], $editoroptions);
  ```
- In `edit_instance_form()`:
  - Populate `$instance->customtext4_editor = ['text' => $instance->customtext4 ?? '', 'format' => FORMAT_HTML];`
- In `edit_instance_validation()`:
  - Validate editor input appropriately.
- In `update_instance()` & `add_instance()`:
  - Extract HTML content: `$data->customtext4 = is_array($data->customtext4_editor) ? $data->customtext4_editor['text'] : ($data->customtext4 ?? '');`

### Step 2: Enrolment Page Data Preparation (`classes/plugin.php`)
- In `show_enrol_pricing_table()` / `get_enrol_pricing_cards()`:
  - Process `customtext4` using Moodle's `format_text($helper->customtext4, FORMAT_HTML, ['context' => $coursecontext])`.
  - Check whether the content is rich HTML (containing tags like `<ul>`, `<p>`, `<div>`) or legacy line-separated plain text.
  - If legacy line-separated text, format lines into bullet checkmarks.
  - If rich HTML, output the formatted HTML directly inside the `.wallet-pricing-features-wrap` container, which applies Hostinger checklist styling to all `<ul><li>` items automatically.

### Step 3: Mustache Template Enhancements (`templates/pricing_table.mustache`)
- Adopt the Hostinger card hierarchy:
  1. **Top Badge / Header:** Elevated ribbon or full-width banner ("MOST POPULAR", "Special offer • 64% off") positioned seamlessly at the top edge.
  2. **Plan Title & Duration:** Instance name (`egy1`, `2-Week Access`, `Semester Pass`) with duration badge (`⏱️ 20 days`).
  3. **Price Display:**
     - Strikethrough original price if discounted (`<del>100 EGP</del>`).
     - Large bold current price with currency code (`20 EGP / 20 days`).
  4. **Call to Action Button:**
     - Full-width high-contrast button ("Enrol with Wallet" or "Pay with Card/Gateway").
     - Distinct accent styling for the featured plan.
  5. **Plan Details & Features Checklist:**
     - Rendered rich HTML with modern checkmark icons for list items.
     - Support for notes / descriptions below the button or inside features.

### Step 4: CSS & Theme Styling (`styles.css`)
- **Hostinger Dark Mode:**
  - Card background: `#16171d` / `#1c1e24`.
  - Border: `1px solid rgba(255, 255, 255, 0.08)`.
  - Featured card glowing border: `2px solid var(--card-accent-color, #6f42c1)` with `box-shadow: 0 0 24px rgba(111, 66, 193, 0.3)`.
  - Text colors: Headings `#ffffff`, feature text `#e2e8f0`, icons `#10b981`.
- **SmartLearn Light Mode:**
  - Card background: `#ffffff`.
  - Border: `1px solid #e2e8f0`.
  - Featured card: `2px solid var(--card-accent-color, #0d6efd)` with subtle soft shadow.
  - Text colors: Headings `#0f172a`, feature text `#334155`.
- **Hostinger Checklist Styles:**
  - Convert `ul` into a reset list (`padding-left: 0; list-style: none`).
  - Add an SVG checkmark icon before each `li`:
    ```css
    .wallet-pricing-features-wrap ul li::before {
        content: "";
        display: inline-block;
        width: 18px;
        height: 18px;
        background-repeat: no-repeat;
        background-position: center;
        background-size: contain;
        background-image: url("data:image/svg+xml,..."); /* emerald checkmark */
        margin-right: 10px;
    }
    ```
  - Support RTL direction (Arabic site `eslamacademy.com` / `Water Academy`): `margin-left: 10px;` in RTL mode.

### Step 5: Verification & Testing
- Validate PHP syntax on all modified files (`php -l`).
- Test saving rich text in `customtext4_editor` on an enrolment instance.
- Verify rendering on course enrolment page in both Light and Dark modes.
- Bump version in `version.php` and rebuild `wallet.zip`.
