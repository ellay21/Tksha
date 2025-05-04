# 💘 Tksha – Match. Chat. Connect.

**Tksha** is a modern dating platform that helps people connect through meaningful interactions. With an intuitive matching system and real-time messaging, Tksha makes it easy to find and talk to people who share your interests. Built with a robust PHP backend and a smooth frontend, it's where digital chemistry begins.

---

## 🚀 Features

- 🔐 **User Authentication** – Secure signup and login with encrypted credentials.
- 🧑‍🤝‍🧑 **Profile Management** – Upload photos, edit bios, and personalize your profile.
- ❤️ **Smart Matching** – Like, pass, and match with users who like you back.
- 💬 **Real-Time Messaging** – Seamless chat experience after a match is made.
- 🌍 **Location Awareness** – Optionally match users nearby (for local vibes).
- 🛡️ **Privacy & Safety** – Block/report features and secure data handling.

---

## 🛠 Tech Stack

| Layer       | Technology         |
|-------------|--------------------|
| Backend     | PHP (RESTful APIs) |
| Database    | MySQL              |
| Frontend    | HTML, CSS, JavaScript (React/Next.js optional) |
| API Format  | JSON               |
| Auth        | Sessions or JWT    |

---

## 📁 Folder Structure
Tksha/backend
├── api/
│   ├── auth/
│   │   ├── signup
│   │   ├── login
│   │   └── logout
│   ├── users/
│   │   ├── match
│   │   ├── message
│   │   ├── update_profile
│   │   └── profile
├── config/
│   └── database/
│       └── db
├── swagger/
│   ├── swagger.yaml
│   └── index.html
└── uploads/
    └── profile_pics

## ⚙️ Getting Started

### 🧩 Prerequisites
- PHP ≥ 7.4
- MySQL/MariaDB
- Apache or Nginx
- Composer (optional)
- Node.js & npm (for frontend)

### 🔧 Backend Setup
1. Clone the repository:
   ```
   git clone https://github.com/ellay21/Tksha.git
   cd /backend
   ```
3. Create a database and import schema.sql (if available).

4. Configure database in /config/db.php:

```
$host = "localhost";
$dbname = "matchmate";
$username = "root";
$password = "";
```
4. Run the server:

Localhost: use XAMPP, WAMP, or built-in PHP server:
```
php -S localhost:8000
```
🤝 Contributing
We welcome all contributions! Whether you're fixing a bug, adding a feature, or improving documentation — your help is appreciated.
Fork the repository
Create a new branch (git checkout -b feature/amazing-feature)
Commit your changes (git commit -m 'Add amazing feature')
Push your branch (git push origin feature/amazing-feature)
Open a pull request
🖼️ Demo & Preview
🚧 Live demo and screenshots coming soon!
📬 Contact
Questions? Suggestions? Reach us at mesudmelaku1@gmail.com
