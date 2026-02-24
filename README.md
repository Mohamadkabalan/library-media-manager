# 📚🎬 AI-Powered Library & Media Management System

A modern, full-featured **Laravel 12 + MySQL** application that combines:

* 📚 **Library Management System (Version 1)**
* 🎬 **Personal Media Collection Tracker (Version 2)**

All enhanced with **OpenAI GPT-powered intelligence** for smart automation, recommendations, and conversational discovery.

This project demonstrates real-world architecture, AI integration, role-based access control, and production-ready Laravel practices.

---

# 🌟 Core Features

---

## 📚 Version 1 — Library Management System

A complete mini-library solution with advanced tracking and automation.

### 📖 Book Management

* Full CRUD operations
* Fields include: title, author, ISBN, genre, description, publisher, year, pages, language, location
* Cover image upload or auto-fetch via ISBN (Open Library)
* Multiple copies per book with automatic availability tracking

### 🔄 Borrowing System

* 14-day default loan period
* Member check-out
* Librarian-assisted check-in
* Maximum 3 renewals per checkout
* Real-time availability updates

### ⏰ Overdue Tracking

* Automatic overdue detection
* Admin overdue reports
* Dashboard due-date indicators

### 📌 Reservations

* Reserve unavailable books
* Automatic availability management

### ⭐ Ratings & Reviews

* 1–5 star ratings
* Text reviews per book
* Aggregate rating display

### 🔎 Smart Search

* Full-text search
* AI-powered semantic search
* Natural language queries like:

  > “Books like Harry Potter for adults”

---

## 🎬 Version 2 — Personal Media Collection Tracker

Track and organize your entire digital life.

### 🎭 Supported Media Types

* Movies 🎬
* Music 🎵
* Games 🎮
* TV Shows 📺
* Podcasts 🎙️
* Books 📚

### 📊 Status Tracking

* Owned
* Wishlist
* Currently Using
* Completed
* Dropped

### 🧠 Rich Metadata

* Creator
* Genre
* Release year
* Platform
* Duration
* Personal notes

### ❤️ Personal Ratings

* 1–10 rating system
* Favorite flag
* Play count tracking
* Started & completed dates

### 📁 Collections

* Organize items into custom named collections
* Group by themes, franchises, or personal categories

---

# 🤖 AI-Powered Features (OpenAI GPT)

All AI functionality is powered by **GPT-4o-mini** for fast and cost-efficient performance.

### 📚 Library AI

* AI-generated professional book summaries
* Automatic topic tags (5–8 keywords)
* AI metadata auto-fill (title + author → full details)
* Personalized reading recommendations
* Natural language search

### 🎬 Media AI

* Smart media recommendations based on your collection
* AI metadata auto-fill (title + creator → full metadata)
* Conversational AI assistant for discovery & discussion

### 💬 AI Media Assistant

A built-in chatbot that:

* Suggests books and media
* Answers questions
* Discusses recommendations
* Understands conversational intent

### ⚡ Smart Caching

* Summaries cached for 30 days
* Recommendations cached for 6 hours
* Reduces API cost and improves performance

**Estimated cost:** Typically less than $0.01/day with GPT-4o-mini.

---

# 🔐 Authentication & Authorization

### Authentication

* Email & password registration
* Google OAuth (Laravel Socialite)
* GitHub OAuth

### Role-Based Access (Spatie Permission)

| Role          | Permissions                           |
| ------------- | ------------------------------------- |
| **Admin**     | Full system control                   |
| **Librarian** | Book management + borrowing           |
| **Member**    | Browse, borrow, manage personal media |

Additional capabilities:

* Admin role management panel
* Enable/disable accounts
* Role switching

---

# ✨ Additional Features

* 🌙 Dark mode (persisted via localStorage)
* 📊 Analytics dashboard (borrowing stats, genre breakdown)
* 🔔 Overdue notifications
* ⚡ AJAX-powered instant status updates
* 📱 Fully responsive (mobile-first Tailwind CSS)
* 🖼️ Image storage (local/S3 compatible)

---

# 🚀 Installation & Setup

## Requirements

* PHP 8.2+
* Composer 2.x
* MySQL 8.0+
* Node.js 18+ (optional – CDN used by default)
* Docker (recommended)

---

## 1️⃣ Clone Repository

```bash
git clone https://github.com/Mohamadkabalan/library-media-manager.git
cd library-media-manager
cp .env.example .env
```

---

## 2️⃣ Start Docker Containers

```bash
docker-compose up -d --build
```

Create database:

```bash
docker-compose exec mysql mysql -u root -p -e "CREATE DATABASE aspire CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
# password: root
```

---

## 3️⃣ Configure Environment

Make sure the below is configured `.env`:

```env


OPENAI_API_KEY=
OPENAI_REQUEST_TIMEOUT=

# Google OAuth (SSO)
GOOGLE_CLIENT_ID=
GOOGLE_CLIENT_SECRET=
GOOGLE_REDIRECT_URL=

```

---

## 4️⃣ Run Laravel Setup

```bash
docker-compose exec app php artisan key:generate
docker-compose exec app php artisan migrate
docker-compose exec app php artisan db:seed
docker-compose exec app php artisan config:clear
docker-compose exec app php artisan cache:clear
```

Visit:

```
http://localhost:8000
```

---

# 👤 Demo Accounts

After seeding:

| Role      | Email                                                     | Password |
| --------- | --------------------------------------------------------- | -------- |
| Admin     | [admin@library.local](mailto:admin@library.local)         | password |
| Librarian | [librarian@library.local](mailto:librarian@library.local) | password |
| Member    | [member@library.local](mailto:member@library.local)       | password |

---

# 🏗️ Project Structure

```
app/
├── Http/Controllers/
│   ├── Auth/AuthController.php
│   ├── BookController.php
│   ├── MediaItemController.php
│   ├── DashboardController.php
│   ├── AiChatController.php
│   └── AdminController.php
├── Models/
│   ├── User.php
│   ├── Book.php
│   ├── BookCheckout.php
│   ├── MediaItem.php
│   └── MediaCollection.php
├── Services/
│   └── AiService.php
└── Policies/
    └── BookPolicy.php

resources/views/
├── layouts/app.blade.php
├── auth/{login,register}.blade.php
├── dashboard.blade.php
├── books/
├── media/
├── ai/chat.blade.php
└── admin/users.blade.php
```

---

# 🔌 API Endpoints (AJAX)

| Method | Endpoint                        | Description                  |
| ------ | ------------------------------- | ---------------------------- |
| POST   | `/books/ai-enrich`              | AI book metadata enrichment  |
| POST   | `/media/ai-enrich`              | AI media metadata enrichment |
| POST   | `/ai-assistant/chat`            | AI chatbot interaction       |
| GET    | `/ai-assistant/recommendations` | Get AI suggestions           |
| PATCH  | `/media/{id}/status`            | Quick status update          |

---

# 🛠️ Tech Stack

| Layer    | Technology              |
| -------- | ----------------------- |
| Backend  | Laravel 12, PHP 8.2     |
| Database | MySQL 8                 |
| Auth     | Sanctum + Socialite     |
| Roles    | Spatie Permission       |
| AI       | OpenAI GPT-4o-mini      |
| Frontend | Tailwind CSS, Alpine.js |
| Icons    | Font Awesome            |
| Storage  | Laravel Filesystem      |

---

# 💡 Architectural Decisions

* **Unified architecture** — Shared auth & UI across both systems
* **AI abstraction layer** — All GPT logic centralized in `AiService`
* **Caching strategy** — Reduces API cost and improves speed
* **Graceful degradation** — App fully functional without OpenAI key
* **Lightweight frontend** — Alpine.js for simplicity
* **Clean role hierarchy** — Admin ⊃ Librarian ⊃ Member

---

# 🚢 Production Deployment Notes

Ensure production environment variables:

* `APP_KEY`
* `DB_*`
* `OPENAI_API_KEY`
* `SESSION_DRIVER=database`
* `CACHE_STORE=database`

Generate key:

```bash
php artisan key:generate --show
```

---

# 📄 License

MIT License — free to use, modify, and extend.
