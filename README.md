# VC1-G15 Alumni Media

Monorepo for the Alumni Media project with:

- `backend`: Laravel 10 API/backend services
- `frontend`: Vue 3 + Vite client app

## Tech Stack

- Backend: PHP 8.1+, Laravel 10, Sanctum, MySQL (default in `.env.example`)
- Frontend: Vue 3, Vite
- Tooling: Composer, npm

## Project Structure

```text
.
|-- backend/    # Laravel application
|-- frontend/   # Vue application
`-- .gitignore
```

## Prerequisites

- PHP `^8.1`
- Composer
- Node.js `^20.19.0` or `>=22.12.0` (frontend requirement)
- npm
- MySQL (or adjust Laravel DB settings for your preferred database)

## Setup

### 1. Clone and enter project

```bash
git clone <your-repo-url>
cd VC1-G15\ Alumni\ Media
```

### 2. Backend setup (`backend/`)

```bash
cd backend
composer install
cp .env.example .env
php artisan key:generate
```

Update database values in `backend/.env`, then run:

```bash
php artisan migrate
```

Start backend services:

```bash
php artisan serve
npm install
npm run dev
```

By default, Laravel serves at `http://127.0.0.1:8000`.

### 3. Frontend setup (`frontend/`)

In a new terminal:

```bash
cd frontend
npm install
npm run dev
```

.Env
member need to create file name .env in folder frontend
then write this
```
VITE_API_URL=http://127.0.0.1:8000/api
```
Vite will print the local URL (commonly `http://localhost:5173`).

## Useful Commands

### Backend

```bash
php artisan serve        # Start Laravel dev server
php artisan migrate      # Run migrations
php artisan test         # Run tests
npm run dev              # Run Vite for Laravel assets
npm run build            # Build Laravel frontend assets
```

### Frontend

```bash
npm run dev              # Start Vue dev server
npm run build            # Production build
npm run preview          # Preview production build
```

## Current Status

The repository currently contains starter scaffolding for Laravel and Vue.
As features are added (e.g., Alumni posts/media modules), update this README
with:

- API endpoints
- Database schema notes
- Environment variables
- Deployment instructions

