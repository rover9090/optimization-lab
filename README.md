
# Cross-Database E-commerce Reporting System

A high-performance reporting module built with Laravel, designed to handle large-scale datasets (100,000+ records) across distributed database architectures. This project demonstrates the evolution from traditional SQL joins to scalable **Application-side Joins**.

## 🚀 Architectural Overview

In this project, I manage data across two distinct database instances:

1. **Orders Database**: Stores transactional data (`orders`, `order_lines`, `product_data`).
2. **Middleware Database**: Stores global configurations (`website_config`) including regional locales and country mappings.

### The Challenge

Performing standard SQL `JOIN` operations across different database connections (or physical servers) introduces significant bottlenecks, including high latency, tight coupling, and database CPU contention.

---

## 🛠 Optimization Strategy

I implemented two distinct phases of optimization to demonstrate technical maturity and scalability planning.

### Phase 1: The Baseline (Cross-Database Join)

Initially, the report was generated using a standard SQL Join. While functional, this approach:

* Constrains the databases to the same physical host.
* Increases row scan counts significantly during aggregation.
* Couplings the "Orders" service directly to the "Middleware" configuration schema.

### Phase 2: Application-side Join (Current Implementation)

To prepare for a distributed/microservices environment, I refactored the logic to use an **Application-side Join**.

* **Logic**: Fetch configuration metadata into a keyed collection first, then query the transaction data using a `whereIn` filter based on the retrieved locales.
* **Benefits**:
* **Decoupling**: The databases can now live on separate servers.
* **Performance**: Replaced expensive SQL join logic with  memory lookups in PHP.
* **Stability**: Avoids long-running table locks on the configuration database.

* **✅ Pros:**
* **Zero Schema Changes**: No risk of table locks; 100% safe to deploy on legacy production databases.
* **High Flexibility**: Easily handles changes in business logic (e.g., re-mapping countries) without migrating millions of rows.
* **Distributed Ready**: The `Orders` and `Middleware` databases can be physically separated, making this architecture **Microservices-ready**.


* **❌ Cons:**
* **Memory Usage**: Requires holding a small configuration mapping in PHP memory (negligible for country/locale data).
* **Slightly Higher Latency**: Requires two separate database queries instead of one.


---

## 📈 Performance & Scalability

| Metric | Original (SQL Join) | Optimized (App-side Join) |
| --- | --- | --- |
| **Complexity** |  (DB Level) |  (Memory Level) |
| **DB Coupling** | Tight (Same Host Required) | Loose (Distributed Friendly) |
| **Scalability** | Limited by DB CPU | Horizontally Scalable (App Level) |

### Future Evolution (Phase 3: Denormalization)

For systems exceeding 10M+ rows, I have designed a **Data Patching Migration**. This phase would involve denormalizing `country_code` directly into the `orders` table. While this introduces data redundancy, it provides the ultimate performance by enabling pure single-table indexing.

*Note: This was intentionally kept as a "Phase 3" plan to avoid premature optimization and maintain data integrity at current scales.*

* **✅ Pros:**
* **Maximum Query Performance**: Enables pure single-table scans with optimized indexes.
* **Simplified SQL**: Eliminates all logic-based filtering in PHP.


* **❌ Cons:**
* **Data Integrity Risks**: Requires complex observers or triggers to keep redundant data synced across tables (e.g., if a locale’s country changes).
* **Storage Overhead**: Increases database size as strings are duplicated millions of times.
* **Deployment Risks**: Adding columns to a table with 10M+ rows causes **long table locks**, potentially leading to production downtime.

---

## 💻 Technical Stack

* **Framework**: Laravel 11
* **Database**: MySQL (Dual-connection setup)
* **Techniques**: Eloquent Relationships, Custom Artisan Commands, Data Patching Migrations, Defensive Programming.

## 🏃 How to Run

1. Ensure both database connections are configured in `.env`.
2. Run migrations: `php artisan migrate`.
3. Generate the report:
```bash
# Standard Report (Original Logic)
php artisan report:regional-sales ca

# Optimized Report (Application-side Join)
php artisan report:regional-sales ca --optimized
```