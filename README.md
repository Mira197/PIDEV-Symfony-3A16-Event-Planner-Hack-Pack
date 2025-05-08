# 3alaKifi – Event Planner
<!-- PROJECT LOGO -->
<br />
<div align="center">
  <a href="https://github.com/othneildrew/Best-README-Template">
    <img src="public/images/logo2.png" alt="Logo" width="200" height="200">
  </a>
</div>

## 🎯 Overview
**3alaKifi** is an intelligent event planning platform developed using **Symfony** as part of the academic project for the **PIDEV 3A** course at [Esprit School of Engineering](https://esprit.tn). It helps users organize and personalize their events through a unified, intuitive interface, combining intelligent assistance with centralized planning for a smoother, more efficient experience.

> Keywords: Symfony, event-planning, AI, NLP, weather-API, FullCalendar, map-integration, AJAX, PDF export, ICS calendar, QR code, Stripe payment, inventory-management, role-based-access, chatbot, forum, sentiment-analysis, statistics, reCAPTCHA, CSV export, loyalty-system, FrameVR, supplier-tracking, recaptcha


---
## Cross Platform:
1) [Web](https://github.com/Mira197/PIDEV-Symfony-3A16-Event-Planner-Hack-Pack)
2) [Desktop](https://github.com/Mira197/PIDEV-JavaFX-3A16-Event-Planner-Hack-Pack)
   
## 🚀 Features
The platform provides 5 main management modules:
### 1. Event Management 
- 🔍 **Venue suggestion system** powered by AI (based on event date, city, and capacity) [venue suggestion assistant]
- ☁️ **Weather forecast integration** to help users check the weather conditions on the selected date and city for their event.
- 🗺️ **TomTom Map** integration for interactive location previews, showing all available venues on a dynamic map.
- 🕶️ **3D Virtual Venue Tour (FrameVR integration)** Each event includes a unique FrameVR link that offers a fully immersive 3D tour, helping clients visualize and customize their events while giving admins insight into the client’s vision
- 📅 **FullCalendar integration** to create and visualize events on a calendar view with real-time updates.
- 🧾 **PDF & ICS export** for both calendar and booking summaries.
- 🔁 **Dynamic AJAX-based search and filtering**  for events, locations, and bookings for faster and smoother user experience.
- 📊 **Statistics and analytics** with visual insights to help admins track usage and better understand user behavior.
- 📝 **To-do list** integrated in the admin dashboard to manage tasks and event planning steps efficiently.
- 👨‍💼 Admin panel for managing events, bookings, and locations
### 2. Order Management

- 🛒 **Smart order creation** based on the user’s active cart with a smooth and intuitive checkout process.
- 🎟️ **Automatic discount application** using promotional coupons, available wallet credit, and loyalty point conversions.
- 💳 **Secure online payment integration** via Stripe, supporting both full and partial payments.
- 🔐 **Unique QR code generation** for every order to ensure fast and secure validation during delivery or on-site check-in.
- 📦 **Order tracking and history** with a detailed log of all user purchases and associated actions.
- 💼 **Transaction history** including wallet activity and loyalty point usage, accessible per user.
- 🔍 **Advanced admin tools** for dynamic filtering of orders by date, status, or user, and real-time keyword-based search.
- 📤 **CSV data export** for external analysis or integration with third-party systems.
- 📊 **Admin dashboard** offering a monthly overview of orders, with detailed stats on confirmed, pending, and canceled purchases.
- 📈 **AI-powered sales forecasting** to predict the next month’s revenue and order trends, enhancing business planning and decision-making.
### 3. Product Management

- 📦 **Dedicated product interface** for adding, editing, and removing items tied to a specific stock.
- 🗂️ **Dynamic product listing** by category with integrated pagination and real-time currency conversion (multi-currency support).
- 💱 **Currency switcher** to allow users to view prices in different currencies based on their preference or region.
- 🚨 **Smart stock alert system** that triggers automatic email and in-app notifications when stock levels reach a critical threshold.
- 📜 **Action log system** that tracks all inventory changes (additions, updates, deletions) for audit and traceability purposes.
- 📊 **Interactive product analytics** showing distribution by category, price range, and supplier through dynamic charts and graphs.
- 🤝 **Supplier-specific tracking**, enabling each connected supplier to monitor and manage their own product listings and stock levels.

### 4. User Management

- ✅ **User registration, login, and logout** with a smooth and secure authentication flow.
- 📝 **Profile editing and profile picture upload**, allowing users to personalize their account.
- 🎭 **Role-based access control** with distinct interfaces and permissions for admins, clients, and suppliers.
- 🔒 **reCAPTCHA integration** to secure the login process and prevent automated bots.
- ✉️ **Password reset via email** – users receive a verification code to safely recover and reset their forgotten password.
- 🧩 **Post-signup profile completion prompt** to encourage users to provide essential details for a better, more tailored experience.
- 💬 **Integrated chatbot** available across the platform to assist users with navigation, basic questions, and platform usage.

### 5. Forum Management

- 💬 **User-generated discussions** – users can create and publish topics to initiate conversations within the community.
- 🚨 **Bad words detection system** to automatically detect offensive language in posts and comments.
- 🗑️ **Automatic removal of flagged content** – publications reported by users are reviewed and deleted if necessary.
- 🚩 **Report system** allowing users to flag inappropriate or abusive content for review.
- 🧠 **Sentiment analysis** on forum replies to detect tone and help moderators identify potential conflicts or negativity.
- 📊 **Forum statistics** showing activity trends such as number of topics, most active users, and most liked replies.
- 🛡️ **Admin moderation panel** for managing discussions, reviewing reports, and blocking or warning users when necessary.


---

## 🛠️ Tech Stack

### 🌐 Frontend

[![HTML5](https://img.shields.io/badge/HTML5-E34F26?style=for-the-badge&logo=html5&logoColor=white)](https://developer.mozilla.org/en-US/docs/Web/HTML)  
[![CSS3](https://img.shields.io/badge/CSS3-1572B6?style=for-the-badge&logo=css3&logoColor=white)](https://developer.mozilla.org/en-US/docs/Web/CSS)  
[![Bootstrap](https://img.shields.io/badge/Bootstrap-7952B3?style=for-the-badge&logo=bootstrap&logoColor=white)](https://getbootstrap.com/)  
[![Twig](https://img.shields.io/badge/Twig-cccc33?style=for-the-badge&logo=twig&logoColor=white)](https://twig.symfony.com/)  
[![JavaScript](https://img.shields.io/badge/JavaScript-F7DF1E?style=for-the-badge&logo=javascript&logoColor=black)](https://developer.mozilla.org/en-US/docs/Web/JavaScript)  
[![jQuery](https://img.shields.io/badge/jQuery-0769AD?style=for-the-badge&logo=jquery&logoColor=white)](https://jquery.com/)

---

### 🧠 Backend

[![Symfony](https://img.shields.io/badge/Symfony-000000?style=for-the-badge&logo=symfony&logoColor=white)](https://symfony.com/)  
[![Doctrine ORM](https://img.shields.io/badge/Doctrine-FF7043?style=for-the-badge)](https://www.doctrine-project.org/)  
[![MySQL](https://img.shields.io/badge/MySQL-4479A1?style=for-the-badge&logo=mysql&logoColor=white)](https://www.mysql.com/)

---

## 📁 Directory Structure
```
├── config/
├── public/
│ ├── assets/
├── src/
│ ├── Controller/
│ ├── Entity/
│ ├── Repository/
├── templates/
├── migrations/
├── tests/
└── README.md
```

## 📦 Getting Started

### Requirements

- PHP 8+
- Composer
- Symfony CLI
- MySQL

### Installation

1. **Clone the repository**:
   ```sh
   git clone https://github.com/Mira197/PIDEV-Symfony-3A16-Event-Planner-Hack-Pack.git
   ```
   
2. **Install Dependencies**:
   ```sh
   composer install
    npm install
   ```

3. **Build frontend assets:**:
   ```sh
   composer install
   npm install
   ```

4. **Database Migration**:
   ```sh
   symfony console doctrine:database:create
   symfony console doctrine:migrations:migrate
   ```

5. **Run the Server**:
   ```sh
   symfony serve
   ```
6. **Install wkhtmltopdf (for PDF generation)**:
   Download the Windows 64-bit installer from :
    👉 https://wkhtmltopdf.org/downloads.html
<!-- ### 👥 Top Contributors

<a href="https://github.com/Mira197/PIDEV-Symfony-3A16-Event-Planner-Hack-Pack/graphs/contributors">
  <img src="https://contrib.rocks/image?repo=Mira197/PIDEV-Symfony-3A16-Event-Planner-Hack-Pack" alt="Top Contributors" />
</a>-->


## Acknowledgments
This project was completed under the guidance of [Professor: Mme Ameni Rommene]
(mail:ameni.rommene@esprit.tn) at Esprit.

<!-- ## Here are some screenshots of our application:

<p align="center">
 <img src="public/images//img1.jpg">
 <img src="public/images//img2.jpg">
 <img src="public/images//img4.jpg">
 <img src="public/images//img5.jpg">
</p>  -->





