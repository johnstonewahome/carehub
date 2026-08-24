# CareHub clinic design

Date: 2026-08-24

## Subject

A one-clinician consulting room. Audience: a solo clinician between patients. Single job: find a patient, open the chart, record today’s visit and the medicines used.

## Product

PHP 8 + MySQL, server-rendered, no Composer. One login. Longitudinal patient charts. Medicine inventory with stock in, adjustments, and deduction when dispensed on a visit. Low-stock alerts. Shared-hosting deploy via `schema.sql` or `install.php`.

## Visual tokens

Ground: porcelain tray, steel filing cabinet, iodine bottle, hanging-file tabs.

- Porcelain `#F5F3EE` — open folder / paper
- Steel `#3D4A55` — cabinet rail
- Steel dark `#2A333C` — room behind the cabinet
- Iodine `#C45C26` — the pulled tab, primary actions
- Ink `#1C2328` — text
- Low-stock `#9B2F2F`
- Ok-stock `#2F6F4F`

Type: **Fraunces** for patient names and chart numbers only. **Sora** for UI. **IBM Plex Mono** for doses, quantities, chart IDs.

## Layout

Left rail is a steel cabinet of hanging-file tabs (Home, Patients, Medicines). Main pane is the open folder. Signature: the patient chart as a hanging folder with a colored tab and a visit timeline in the gutter — not a card grid.

## Stock rule

Dispensing on a visit writes `visit_medications` and a `stock_movements` row (`out`) and decrements `quantity_on_hand` in one transaction. Block if quantity exceeds on-hand. Receive is `in`. Adjust is `adjust` with a reason. Low stock when `quantity_on_hand <= reorder_level`.
