# 📊 PLO (Precinct-Level Organization) System

A high-performance, responsive, and secure **Precinct-Level Organization (PLO) Database Management Dashboard**. Designed explicitly for multi-tiered voter organization networks, layout management, demographic mapping, and analytics aggregation down to individual cluster jurisdictions.

---

## ⚡ Key Features

*   **👥 Multi-Tiered Organization Tree:** Seamless tracing and indexing of organizational hierarchies (MCE ➔ BCE ➔ House Leaders (HL) ➔ Household Members) using highly optimized query pipelines.
*   **🔍 Advanced Sticky Controls:** Instant lookup filters by Keyword Search, District, Municipality, Barangay, Precinct, 4Ps Membership Status, and Literacy Demographics.
*   **💎 Modern Glassmorphic UI:** A highly intuitive frontend powered by Bootstrap 5 layout standards with custom blur effects, optimized for both desktop views and field tablets.
*   **🚀 Zero-Lag Engine & Production Ready:** Engineered with robust multi-table SQL `LEFT JOIN` operations to completely avoid `1 + N` iteration lags.
*   **🛡️ Cavity-Free Runtime Environment:** Safe payload extractions via type-safe parsing (`intval()`), fallback wrappers (`??`), and output scrubbing to guarantee a warning-free `php-error.log`.
*   **⚡ Edge-Cache Proof Pipeline:** Implements a global `SITE_VERSION` footprint framework and self-destructive error hooks (`this.onerror=null`) to bypass aggressive Cloudflare edge caching or Nginx proxies flawlessly.
*   **🖨️ Professional Reporting Framework:** Dedicated media print stylesheet integration (`d-print-none` and `d-print-block`) that automatically strip management layouts for clean paper or PDF delivery.

---

## 📂 System Architecture & Hierarchy Tree

The application core maps down relationship linkages sequentially:

---

## 📂 System Architecture & Hierarchy Tree

The application core maps down relationship linkages sequentially:

```text
📍 Province Scope
 ┗ 🏢 DISTRICT JURISDICTION
    ┗ 📍 Municipality (City / Town Scope)
       ┗ 🗺️ Barangay (Village Scope)
          ┗ 🗳️ Precinct / Cluster Segment
             ┗ 🛡️ MCE Name Pointer (Voters Link)
                ┗ ⭐ BCE Name Pointer (Voters Link)
                   ┗ 🏠 House Leader (HL)
                      ┗ 👨‍👩‍👧‍👦 Household Members (hl_children)
```

---

## 🛠️ Stack Components

*   **Backend Engine:** PHP (Structured Core / PDO Prepared Statements / Object Mapping)
*   **Database Management:** MySQL / MariaDB (Relational Constraints, Aggregated Groupings)
*   **Frontend UI Layer:** Bootstrap 5, Font Awesome 6 Icons, Custom CSS3 Utilities
*   **Deployment Security:** Cloudflare Proxy Compliant, Session Lifecycle Guard

---

## 🚀 Quick Setup & Local Deployment

### 1. Clone the repository
```bash
git clone https://github.com/McJim69/plo-system
cd plo-system
```

### 2. Database Environment Setup
*   Create a new MySQL database named `plo_db`.
*   Import your schema file configuration:
    ```bash
    mysql -u root -p plo_db < database/schema.sql
    ```
*   Update your connection configurations inside `connect-pdo.php` or `connect-sqli.php` files to match your local credential nodes.

### 3. Setup App Versioning (Cache Buster)
Inside your main environment connector, manage the dynamic target parameter block to enforce layout updates to users instantly on deployment updates:
```php
define('SITE_VERSION', '1.0.1'); // Increment this on code pushes!
```

---

## 📝 Git Commit & Deployment Standard

Every production update adheres to the clean runtime standard framework:
```bash
git status
git add .
git commit -m "Refactor: Fix undefined array keys, optimize loops with SQL JOINs, and implement SITE_VERSION cache buster"
git push origin main
```

---

## ⚖️ License
Distributed under proprietary organizational usage policies. Unauthorized extraction or replication of the mapping engine is strictly managed.

*Developed with 🖥️ and ☕ by McJim Cyberworks.*