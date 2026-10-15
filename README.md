# Housing4Raajje Setup Guide

Follow these steps to set up and run the local development environment.

## Initial Setup Steps

Run these commands from your local terminal:

```bash
# Clone the repository
git clone https://github.com/Lunacy-simplified/housing4raajje
cd housing4raajje

# Create your local environment configuration
cp .env.example .env

# Build and start the Docker containers in the background
docker compose up -d --build

# Access the application container's terminal
docker compose exec app bash
```

## Inside the Container

Once you are inside the application container, run these initialization commands:

```bash
# Install PHP dependencies
composer install

# Generate the application encryption key
php artisan key:generate

# Run the database migrations
php artisan migrate

# Exit the container
exit
```

---

## Environment Changes

Whenever you make changes to your local configuration files (like the `.env` file) or Docker settings, you need to wipe the old state and rebuild the environment to apply the updates (2 hours of debugging bruh):

```bash
# Completely drop containers and wipe old data volumes
docker compose down -v

# Rebuild and start fresh containers
docker compose up -d --build

# Run a fresh database migration and re-populate development seeders
docker compose exec app php artisan migrate:fresh --seed
```

---

### Testing Environment Setup

Before executing the test suite for the first time, initialize the localized PostgreSQL testing database:

```bash
docker compose exec db sh -c 'createdb -U "$POSTGRES_USER" h4r_testing'
```

Run the validation matrix using:

```bash
docker compose exec app php artisan test
```
---

### Local Environment Mock Accounts

When working in a local workspace environment (`APP_ENV=local`), the database provides 6 pre-configured user profiles covering all architectural permission access matrix layouts:

| App Role                | Authentication Login Email | Testing Plaintext Password | Link Profile Extension          |
| :---------------------- | :------------------------- | :------------------------- | :------------------------------ |
| **System Admin**        | `admin@h4r.local`          | `SecretAdmin123!`          | `staff_profiles` (Global Scope) |
| **Branch Manager**      | `manager@h4r.local`        | `SecretManager123!`        | `staff_profiles` (MLE Branch)   |
| **Property Specialist** | `specialist@h4r.local`     | `SecretSpecialist123!`     | `staff_profiles` (MLE Branch)   |
| **Receptionist**        | `reception@h4r.local`      | `SecretReception123!`      | `staff_profiles` (MLE Branch)   |
| **Asset Owner**         | `owner@h4r.local`          | `SecretOwner123!`          | `owner_profiles`                |
| **Rental Client**       | `client@h4r.local`         | `SecretClient123!`         | `client_profiles`               |
