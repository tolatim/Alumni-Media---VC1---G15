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

### 4. Node js WebSocket setup (`websocket-server/`)

```bash
cd websocket-server
npm init -y
npm install ws
npm install cors
npm install express
```

members need to create file name .env in folder frontend
then write this
```
VITE_API_URL=http://127.0.0.1:8000/api
```
Vite will print the local URL (commonly `http://localhost:5173`).

## Docker Setup

This repository now includes Docker support for:

- `mysql` on `3307` (container internal port remains `3306`)
- `backend` (Laravel API) on `8000`
- `frontend` (Vue/Vite) on `5173`
- `websocket-server` event/http on `3000` and ws on `8081`

From the project root, run:

```bash
docker compose up --build
```

Then open:

- Frontend: `http://localhost:5173`
- Backend API: `http://localhost:8000`

Useful Docker commands:

```bash
docker compose down
docker compose logs -f backend
docker compose logs -f frontend
docker compose logs -f websocket-server
docker compose exec backend php artisan migrate
```

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

### Websocket

```bash
node server.js           #Start server
```

## Current Status

The repository currently contains starter scaffolding for Laravel and Vue.
As features are added (e.g., Alumni posts/media modules), update this README
with:

- API endpoints
- Database schema notes
- Environment variables
- Deployment instructions

