# Laravel Microservices Demo

A learning project that splits a parking booking system into independent services: authentication, parking bookings, and notifications. Built with Laravel, Docker Compose, Nginx, MySQL, Redis, and JWT.

## Architecture

````
Client
  │
  ▼
Nginx API Gateway (localhost:8088)
  ├── /auth/*    → auth-service    → auth-db (MySQL)
  └── /parking/* → parking-service → parking-db (MySQL)
                        │
                        ▼ (JSON message)
                   Redis queue
                        │
                        ▼
               notification-service
````

## Services

| Service | Responsibility |
|---|---|
| gateway | Single entry point; routes requests to the right service |
| auth-service | Register and login; issues JWT tokens |
| parking-service | Creates and lists bookings; verifies JWT; publishes booking events |
| notification-service | Consumes booking events from Redis and sends confirmations (logged) |
| auth-db / parking-db | Separate MySQL database per service |
| redis | Message queue between parking and notification services |

## Key design decisions

- **Database per service:** each service owns its data. `bookings.user_id` has no foreign key because users live in a different database.
- **Stateless JWT auth:** the parking service verifies tokens with a shared secret, without calling the auth service on every request.
- **API gateway:** clients use one URL; services are not exposed directly.
- **Asynchronous messaging:** bookings succeed even when the notification service is down; messages wait in Redis.

## Known limitations

- A Redis list is a simple queue with no delivery acknowledgement. Production systems would use RabbitMQ, Kafka, or Redis Streams.
- JWT tokens cannot be revoked before they expire (1 hour).
- `php artisan serve` is used for simplicity; production would use PHP-FPM.

## Running locally

Requirements: Docker and Docker Compose.

1. Install dependencies for each service:
````bash
   for s in auth-service parking-service notification-service; do
     docker run --rm -u $(id -u):$(id -g) -v "$(pwd)/$s":/app -w /app composer install --ignore-platform-reqs
     cp $s/.env.example $s/.env
   done
````
2. Configure each `.env`:
   - **auth-service:** `DB_CONNECTION=mysql`, `DB_HOST=auth-db`, `DB_DATABASE=auth`, `DB_PASSWORD=root`, plus `JWT_SECRET` and `JWT_TTL=3600`
   - **parking-service:** `DB_CONNECTION=mysql`, `DB_HOST=parking-db`, `DB_DATABASE=parking`, `DB_PASSWORD=root`, the same `JWT_SECRET`, `REDIS_CLIENT=predis`, `REDIS_HOST=redis`, `REDIS_PREFIX=`
   - **notification-service:** `REDIS_CLIENT=predis`, `REDIS_HOST=redis`, `REDIS_PREFIX=`
3. Generate the app keys and start everything:
````bash
   docker compose up -d --build
   for s in auth-service parking-service notification-service; do
     docker compose exec $s php artisan key:generate
   done
   docker compose exec auth-service php artisan migrate
   docker compose exec parking-service php artisan migrate
````

## API examples

````bash
# Register
curl -X POST http://localhost:8088/auth/register -H "Content-Type: application/json" -H "Accept: application/json" \
  -d '{"name":"Test","email":"test@example.com","password":"password123"}'

# Login (returns a JWT token)
curl -X POST http://localhost:8088/auth/login -H "Content-Type: application/json" -H "Accept: application/json" \
  -d '{"email":"test@example.com","password":"password123"}'

# Create a booking
curl -X POST http://localhost:8088/parking/bookings -H "Content-Type: application/json" -H "Accept: application/json" \
  -H "Authorization: Bearer <token>" -d '{"slot_number":"A-12","vehicle_number":"KL07AB1234"}'

# Check notifications
docker compose logs notification-service
````
