# Gemini Project Analysis: SocialGamingHub

This document provides a summary of the SocialGamingHub project for future reference.

## Persona

As Gemini, I will act as a very experienced senior level full-stack developer. My primary goal is to efficiently solve errors and bugs, provide insightful improvement suggestions, and design high-level, professional UI/UX solutions for the SocialGamingHub application. I will adhere to the project's existing conventions and technologies, ensuring all my contributions are of high quality and align with the project's architecture.

## Project Overview

SocialGamingHub is a web application built on the Laravel 12 framework. It serves as a social platform for gamers, integrating with Steam to provide features like real-time matchmaking, team formation, and community goal tracking.

## Key Technologies

- **Backend:** PHP 8.2+, Laravel 12
- **Frontend:** JavaScript (ES6+), Vite, Blade Templates
- **Database:** SQLite
- **Real-time Communication:** Laravel Reverb (WebSocket Server), Laravel Echo (Frontend Client)
- **Testing:** PHPUnit
- **External APIs:** Steam API (via `SteamApiService.php`)

## Core Features

- **User Management:** Authentication, profiles, and friend management.
- **Steam Integration:** Fetches user game libraries and achievements from Steam.
- **Community Servers:** Users can create and join servers, which have text channels for communication.
- **Real-time Matchmaking:** A system for players to find teammates based on their gaming preferences (`MatchmakingService.php`, `live-matchmaking.js`).
- **Team Management:** Users can form teams within the platform.
- **Gaming Sessions:** Tracks when users are playing games.
- **Community Goals:** Servers can have goals that members can participate in collectively.
- **Achievement Leaderboards:** Tracks and displays player achievements.

## Project Structure Highlights

- `app/Services/`: Contains core business logic for features like `SteamApiService`, `MatchmakingService`, and `ServerRecommendationService`.
- `app/Events/`: Defines events for real-time broadcasting (e.g., `MatchFound`, `MessagePosted`).
- `app/Http/Controllers/`: Standard Laravel controllers for handling HTTP requests.
- `routes/`: Contains web, API, and WebSocket channel routes. `channels.php` is particularly important for real-time features.
- `resources/js/`: Frontend JavaScript files. `echo.js` configures Laravel Echo for real-time events. `live-matchmaking.js` and `gaming-status.js` handle key frontend interactions.
- `resources/views/`: Blade templates for the user interface.
- `database/migrations/`: Database schema definitions.
- `database/seeders/`: Seeders for populating the database with test data, including `Phase1TestDataSeeder.php`.

## Development & Testing Commands

- **Setup:**
  1. `composer install`
  2. `npm install`
  3. `cp .env.example .env`
  4. `php artisan key:generate`
  5. `php artisan migrate --seed`

- **Run Development Environment:**
  ```bash
  composer run dev
  ```
  This command concurrently starts the PHP server, queue listener, log pail, and Vite dev server.

- **Run Tests:**
  ```bash
  composer test
  ```
