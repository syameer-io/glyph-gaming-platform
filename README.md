<div align="center">

# Glyph

### A Modern Gaming Community Platform

[![Laravel](https://img.shields.io/badge/Laravel-12.x-FF2D20?style=for-the-badge&logo=laravel&logoColor=white)](https://laravel.com)
[![PHP](https://img.shields.io/badge/PHP-8.2+-777BB4?style=for-the-badge&logo=php&logoColor=white)](https://php.net)
[![Tailwind CSS](https://img.shields.io/badge/Tailwind-4.0-38B2AC?style=for-the-badge&logo=tailwind-css&logoColor=white)](https://tailwindcss.com)
[![MySQL](https://img.shields.io/badge/MySQL-8.0-4479A1?style=for-the-badge&logo=mysql&logoColor=white)](https://mysql.com)

*Connect, compete, and collaborate with gamers worldwide*

[Features](#features) • [Tech Stack](#tech-stack) • [Installation](#installation) • [Configuration](#configuration) • [License](#license)

</div>

---

## Overview

**Glyph** is a feature-rich gaming community platform inspired by Discord, designed specifically for gamers. It combines real-time communication, Steam integration, intelligent matchmaking, and community management tools into a seamless experience.

Whether you're looking to find teammates for competitive matches, manage your gaming community, or just hang out with friends in voice chat - Glyph has you covered.

## Features

### Core Platform

| Feature | Description |
|---------|-------------|
| **Server Communities** | Create and manage gaming communities with invite codes, custom roles, and permission hierarchies |
| **Text Channels** | Real-time messaging with edit/delete, typing indicators, and message history |
| **Voice Chat** | Crystal-clear WebRTC voice communication powered by Agora.io |
| **Direct Messages** | Private 1-on-1 conversations with friends |
| **Friend System** | Send requests, manage friends, and see who's online |

### Gaming Integration

| Feature | Description |
|---------|-------------|
| **Steam Integration** | Link your Steam account to sync profile, games, achievements, and playtime |
| **Game Lobbies** | Create and share game lobbies for CS2, Dota 2, Apex Legends, and more |
| **Currently Playing** | Show what you're playing in real-time to your community |

### Advanced Features

| Feature | Description |
|---------|-------------|
| **Intelligent Matchmaking** | Find compatible teammates based on skill level, region, schedule, and play style |
| **Team Management** | Create teams with recruitment status, join requests, and member roles |
| **Server Goals** | Set community challenges and track collective progress |
| **Server Discovery** | Get personalized server recommendations based on your gaming preferences |
| **Telegram Integration** | Receive server notifications directly in Telegram |

### Role & Permission System

- **Hierarchical Roles** - Position-based authority (higher position = more power)
- **Granular Permissions** - Fine-tune what each role can do
- **Channel Overrides** - Set permission exceptions per channel
- **Protected Defaults** - Server Admin and Member roles can't be deleted

## Tech Stack

### Backend
- **Framework:** Laravel 12.x
- **Language:** PHP 8.2+
- **Database:** MySQL 8.0
- **Queue:** Database driver (Redis-ready)
- **Cache:** Database/Redis

### Real-Time
- **WebSocket:** Laravel Reverb
- **Voice Chat:** Agora.io WebRTC SDK
- **Broadcasting:** Laravel Echo + Pusher.js

### Frontend
- **CSS:** Tailwind CSS 4.0
- **JavaScript:** Alpine.js 3.x
- **Charts:** Chart.js
- **Build:** Vite 6.x

### External Services
- **Steam API** - Authentication & game data
- **Agora.io** - Voice communication
- **Telegram Bot API** - Notifications (optional)

## Installation

### Prerequisites

- PHP 8.2 or higher
- Composer 2.x
- Node.js 18+ & npm
- MySQL 8.0+

### Quick Start

```bash
# Clone the repository
git clone https://github.com/syameer-io/glyph-gaming-platform.git
cd glyph-gaming-platform

# Install PHP dependencies
composer install

# Install Node dependencies
npm install

# Environment setup
cp .env.example .env
php artisan key:generate

# Database setup
php artisan migrate
php artisan db:seed  # Optional: seed sample data

# Build assets
npm run build

# Start the development server
composer run dev
```

The `composer run dev` command starts all required services:
- Laravel development server
- Queue worker
- Laravel Reverb (WebSocket)
- Vite (hot reload)

Visit `http://localhost:8000` to access the application.

## Configuration

### Required Environment Variables

```env
# Database
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=glyph
DB_USERNAME=your_username
DB_PASSWORD=your_password

# Steam Integration (https://steamcommunity.com/dev/apikey)
STEAM_API_KEY=your_steam_api_key

# Laravel Reverb (WebSocket)
REVERB_APP_ID=your_app_id
REVERB_APP_KEY=your_app_key
REVERB_APP_SECRET=your_app_secret
BROADCAST_CONNECTION=reverb
```

### Optional Services

```env
# Voice Chat - Agora.io (https://console.agora.io)
AGORA_APP_ID=your_agora_app_id
AGORA_APP_CERTIFICATE=your_agora_certificate

# Telegram Notifications
TELEGRAM_BOT_TOKEN=your_bot_token
```

### Mail Configuration

For OTP authentication, configure your mail provider:

```env
MAIL_MAILER=smtp
MAIL_HOST=smtp.gmail.com
MAIL_PORT=587
MAIL_USERNAME=your_email@gmail.com
MAIL_PASSWORD=your_app_password
MAIL_FROM_ADDRESS=noreply@yourdomain.com
```

## Project Structure

```
glyph/
├── app/
│   ├── Events/          # Broadcasting events (Voice, DM, Messages)
│   ├── Http/Controllers/  # API & web controllers
│   ├── Models/          # Eloquent models
│   └── Services/        # Business logic (Matchmaking, Agora, Steam)
├── config/
│   └── services.php     # External service configuration
├── database/
│   └── migrations/      # Database schema
├── resources/
│   ├── css/            # Tailwind styles
│   ├── js/             # Alpine.js & voice chat
│   └── views/          # Blade templates
├── routes/
│   ├── web.php         # Web routes
│   ├── api.php         # API routes
│   └── channels.php    # Broadcasting channels
└── docs/               # Documentation
```

## Supported Games

Glyph currently supports lobby creation and matchmaking for:

| Game | Steam App ID | Join Methods |
|------|--------------|--------------|
| Counter-Strike 2 | 730 | Steam Lobby, Server Address |
| Dota 2 | 570 | Steam Lobby, Lobby Code |
| Apex Legends | 1172470 | Lobby Code |
| Rust | 252490 | Server Address, Steam Connect |
| PUBG | 578080 | Lobby Code |
| Rainbow Six Siege | 359550 | Lobby Code |
| Warframe | 230410 | Invite Code |
| Fall Guys | 1097150 | Lobby Code |

## Artisan Commands

```bash
# Clear all caches
php artisan config:clear && php artisan route:clear && php artisan view:clear

# Clear permission caches
php artisan permissions:clear-cache

# Clean up stale voice sessions
php artisan voice:cleanup-stale

# Expire old lobbies
php artisan lobbies:expire

# Run tests
php artisan test
```

## Contributing

Contributions are welcome! Please feel free to submit a Pull Request.

1. Fork the repository
2. Create your feature branch (`git checkout -b feature/amazing-feature`)
3. Commit your changes (`git commit -m 'Add some amazing feature'`)
4. Push to the branch (`git push origin feature/amazing-feature`)
5. Open a Pull Request

## License

This project is licensed under the MIT License - see the [LICENSE](LICENSE) file for details.

---

<div align="center">

**Built with passion for the gaming community**

Made by [Syameer Anwari](https://github.com/syameer-io)

</div>
