# doliAgroPass

An advanced **Agroecological Assessment Tool** designed as a modular extension for the **doliFarm** (Dolibarr) ecosystem. This module enables data-driven evaluation of a farm's ecological performance through a standardized indexing system.

## 🌟 Overview

**doliAgroPass** acts as a digital passport for sustainable agriculture. It quantifies complex environmental metrics into a readable **Agroecological Index**, allowing farmers to monitor biodiversity, soil health, and resource management directly within their ERP/CRM.

### Key Features
- **AgroScore Calculator**: Core PHP logic for calculating sustainability scores based on field audits.
- **PWA Integration**: A Progressive Web App (`/pwa`) for mobile-first data collection even in areas with low connectivity.
- **Dolibarr Native**: Full integration with Dolibarr hooks, triggers, and PDF/ODT generation.
- **Smart Widgets**: Real-time dashboard indicators for low-score alerts and recent audits.

---

## 🏗 Project Structure

The repository follows the standard Dolibarr module architecture:

* `/class`: Business logic and the `AgroScoreCalculator`.
* `/core`: Module descriptors, PDF/ODT templates, and widgets.
* `/pwa`: Mobile-friendly interface for field assessments.
* `/sql`: Database schema and default indicator data.
* `/langs`: Internationalization support (IT, EN, FR).
* `/lib`: Helper libraries for score management.

---

## 🚀 Installation & Deployment

### Prerequisites
- A working instance of **Dolibarr** (v18+ recommended).
- PHP 8.1+

### Steps
1. Clone the repository into your Dolibarr custom directory:
   ```bash
   cd /path/to/dolibarr/htdocs/custom
   git clone [https://github.com/doliFarm/custom_doliAgropass.git](https://github.com/doliFarm/custom_doliAgropass.git) doliagropass
