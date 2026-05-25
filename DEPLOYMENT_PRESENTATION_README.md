# StayGrid Deployment Presentation Guide

Use this document as your script and checklist for the final video presentation.

## Submission Checklist

- Public hosted application URL: `____________________`
- YouTube video link: `____________________`
- GitHub repository link: `____________________`

## Project Title

StayGrid

## Short Description

StayGrid is a Symfony-based hotel and room booking system with user authentication, room listings, booking management, staff/admin dashboards, and database-backed CRUD operations.

## Part 1. Introduction

When recording, introduce:

- Your name
- Your section or course
- Your project title
- A brief description of what the system does

Suggested script:

"Good day, I am ____________________. I am from ____________________. My final project is called StayGrid. It is a Symfony web application for hotel and room management with booking, user profiles, admin and staff tools, and database integration."

## Part 2. Source Code Overview

### Project Structure

Explain the main folders and files in the repository:

- `src/` - main application code
- `src/Controller/` - request handling and page logic
- `src/Entity/` - Doctrine entities and database models
- `src/Form/` - Symfony form types
- `src/Repository/` - custom database queries
- `src/DataFixtures/` - seed data for rooms, users, and operational records
- `config/` - framework, security, doctrine, routing, and service configuration
- `templates/` - Twig templates for pages and dashboards
- `public/` - public assets, uploads, images, and the front controller
- `migrations/` - Doctrine migration files
- `docs/` - API and deployment documentation
- `tests/` - automated tests if needed

### Important Files

Show and explain these files:

- `.env` - local environment variables and database connection
- `composer.json` - PHP dependencies and Symfony packages
- `config/packages/doctrine.yaml` - Doctrine database configuration
- `config/packages/security.yaml` - authentication and password hasher setup
- `config/bundles.php` - enabled Symfony and third-party bundles
- `Dockerfile` - production container build
- `entrypoint.sh` - container startup logic and migration handling
- `railway.json` - Railway deployment configuration
- `migrations/` - schema changes applied to the database

### `.env` Configuration

Explain that `.env` stores environment values such as:

- `APP_ENV` - application environment, such as `dev` or `prod`
- `APP_DEBUG` - debug mode on or off
- `APP_URL` or `DEFAULT_URI` - local application URL
- `DATABASE_URL` - database connection string
- `MAILER_DSN` - mailer transport
- `GOOGLE_CLIENT_ID` and `GOOGLE_CLIENT_SECRET` - Google login setup
- `JWT_SECRET_KEY`, `JWT_PUBLIC_KEY`, `JWT_PASSPHRASE` - JWT authentication settings
- `CORS_ALLOW_ORIGIN` - allowed frontend origins

Important note:

- For local development, the app uses a local MySQL connection.
- For Railway deployment, `DATABASE_URL` must point to the Railway database service.
- Do not hardcode `localhost` in production.

### Database Configuration

Explain the database setup in `config/packages/doctrine.yaml`:

- Doctrine reads the database connection from `DATABASE_URL`
- ORM mapping uses PHP attributes in `src/Entity/`
- Doctrine migrations are used for schema updates
- Test databases use a suffix in the `when@test` configuration

Mention the important entities:

- `RoomListing` - room data
- `Booking` - reservations
- `LogInUsers` - authenticated users
- `Transaction` - payment and booking transaction records
- `Feedback` - user feedback
- `ActivityLog` - audit trail of actions

### Important Symfony Components Used

Explain the major Symfony and supporting components in the project:

- `FrameworkBundle` - core Symfony application framework
- `TwigBundle` - server-side templates
- `SecurityBundle` - login and role-based access control
- `DoctrineBundle` - database ORM integration
- `DoctrineMigrationsBundle` - database schema migrations
- `Form` component - structured forms and validation input
- `Validator` component - input and entity validation
- `Serializer` component - API data formatting
- `Api Platform` - REST API endpoints
- `Messenger` - background message transport support
- `Mailer` - email functionality
- `HttpClient` - HTTP requests to external services
- `Asset` and `Webpack Encore` - asset handling
- `Stimulus` and `Turbo` - frontend behavior and faster page updates
- `Monolog` - application logging
- `NelmioCorsBundle` - CORS control for API requests
- `KnpU OAuth2 Client` and Google OAuth - Google sign-in support

## Part 3. GitHub Repository

Show on screen:

- Your GitHub repository page
- Commit history
- That the full source code is uploaded
- Any recent commits related to deployment fixes, migrations, and fixtures

Suggested explanation:

"This repository contains the complete source code, deployment configuration, database migrations, and seed data used for the production deployment."

## Part 4. Deployment Process

Explain or record the actual deployment steps you used.

### Recommended deployment flow for Railway

1. Connect the GitHub repository to Railway.
2. Set the build to use the repository Dockerfile.
3. Add the production environment variables.
4. Connect the Railway MySQL service.
5. Run Doctrine migrations.
6. Verify that the app starts and the database is reachable.
7. Fix any deployment issues such as missing variables, permission problems, or schema mismatches.

### Environment Variables to Show

Use these as examples in the video:

- `APP_ENV=prod`
- `APP_DEBUG=0`
- `APP_URL=https://your-railway-domain`
- `DATABASE_URL` from Railway MySQL
- `MAILER_DSN`
- `GOOGLE_CLIENT_ID`
- `GOOGLE_CLIENT_SECRET`
- `JWT_PASSPHRASE`

### Deployment Notes

Mention the issues you solved during deployment:

- Cache permission problems when Symfony tried to write files
- Running console commands as `www-data` in the container
- Delaying or controlling migrations on boot
- Making migrations idempotent for a remote database
- Loading fixtures safely into the Railway database
- Fixing missing image assets for room listings

### Useful commands

```bash
php bin/console doctrine:migrations:migrate --no-interaction
php bin/console doctrine:fixtures:load --no-interaction --append
php bin/console cache:clear --no-warmup
```

## Part 5. Hosted Application Demonstration

Show the working deployed site and demonstrate:

- Homepage
- Login or registration
- Room listings
- Booking creation and editing
- Staff or admin dashboard
- Forms and validation
- Database-backed changes after submission
- Image display for room listings

Suggested walkthrough:

- Open the homepage
- Show a room page with working images
- Log in as a user or staff account
- Create or edit a booking
- Show that the database updates after the action
- Show an admin or staff panel if available

## Part 6. Reflection

Briefly explain:

- What was difficult during deployment
- How you solved hosting or database issues
- What you learned about Symfony deployment
- What you would improve next time

Suggested reflection points:

- Symfony deployment needs correct environment variables
- Doctrine migrations must match the live schema
- File permissions matter when Symfony writes cache or uploads
- Remote database seeding should be done carefully
- Clear commit history helps during deployment and troubleshooting

## Optional Video Script Outline

1. Introduction
2. Source code overview
3. GitHub repository
4. Deployment process
5. Live demo
6. Reflection

## Final Reminders

Before submitting, make sure:

- The application URL is public and accessible
- The YouTube video is uploaded and playable
- The GitHub repository is public or properly shared
- The deployment guide matches what you actually did
- The video is clear, organized, and professional
