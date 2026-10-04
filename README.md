# Doctors Clinics API

Doctors Clinics is a Laravel-based REST API for managing doctors, clinics, schedules, patients, and medical appointments. It provides the backend services needed to discover doctors, check their availability, and book appointment slots.

## Overview

### Problem

Patients need a simple way to find doctors, understand where and when they work, and book appointments without relying on manual coordination. Doctors and clinics also need structured schedules and appointment records.

### Solution

This project centralizes doctor and clinic information in one API. Patients can browse doctors, view available appointment types and time slots, and confirm bookings. Doctors can create their professional profile, add schedules, and review appointments.

## Features

- Patient and doctor registration with role validation.
- JWT-based login authentication.
- Temporary protection after repeated failed login attempts.
- Doctor profiles with specialization, biography, and experience.
- Clinic and medical specialization management.
- Doctor schedules linked to clinics and days of the week.
- Conflict detection for overlapping doctor and clinic schedules.
- Appointment types with configurable durations.
- Available-slot generation based on doctor schedules, appointment duration, and existing bookings.
- Appointment confirmation with overlap prevention.
- Doctor appointment filtering by status.

## Architecture / How It Works

The application follows Laravel's MVC structure:

1. HTTP requests enter through `routes/api.php`.
2. Controllers validate input and coordinate the application logic.
3. Eloquent models represent users, doctors, clinics, schedules, appointment types, and appointments.
4. Migrations define the relational database schema.
5. JWT tokens authenticate users after registration or login.

The appointment flow is:

1. Register or log in as a patient or doctor.
2. Browse doctors and inspect a doctor's schedules.
3. Request available slots for a doctor, clinic, appointment type, and date.
4. Confirm a selected slot.
5. The API checks the doctor's schedule and existing appointments before creating a pending appointment.


## Tech Stack

- PHP 8.2+
- Laravel 12
- Laravel Eloquent ORM
- MySQL, SQLite, or another Laravel-supported database
- `tymon/jwt-auth` for JWT authentication
- Composer for PHP dependencies
- PHPUnit for testing

## Prerequisites

Install the following before running the project:

- PHP 8.2 or newer
- Composer
- A configured database (SQLite is suitable for local development)
- Git, if cloning the repository

## Installation

1. Clone the repository and enter the project directory:

	```bash
	git clone <repository-url>
	cd DoctorsClinics
	```

2. Install PHP dependencies:

	```bash
	composer install
	```

3. Create the environment file:

	```bash
	cp .env.example .env
	```

	On Windows PowerShell, use:

	```powershell
	Copy-Item .env.example .env
	```

4. Configure the database values in `.env`. For SQLite, create the database file and set:

	```env
	DB_CONNECTION=sqlite
	DB_DATABASE=/absolute/path/to/database/database.sqlite
	```

5. Generate the application key and run migrations:

	```bash
	php artisan key:generate
	php artisan migrate
	```

6. Start the Laravel API:

	```bash
	php artisan serve
	```

The API is available at `http://127.0.0.1:8000/api` by default.

## API Documentation

All API routes are prefixed with `/api`. Requests containing JSON bodies should use:

```http
Content-Type: application/json
Accept: application/json
```

### Authentication

#### Register

```http
POST /api/regist
```

Request body:

```json
{
  "name": "Jane Doe",
  "email": "jane@gmail.com",
  "password": "secret123",
  "role": "patient"
}
```

`role` must be either `patient` or `doctor`. The email must be unique and use the `gmail.com` domain.

#### Login

```http
POST /api/login
```

Request body:

```json
{
  "email": "jane@gmail.com",
  "password": "secret123"
}
```

Successful responses include a JWT token. Send it on protected requests as:

```http
Authorization: Bearer <token>
```

### Doctors and Setup Data

| Method | Endpoint | Purpose |
| --- | --- | --- |
| `GET` | `/api/doctors` | List all doctors with their specializations. |
| `GET` | `/api/doctors/{id}` | Get a doctor, specialization, and schedules. |
| `POST` | `/api/doctorInformation/{user_id}` | Create a doctor's profile. Requires `specialization_id`, `bio`, and `experience_years`. |
| `POST` | `/api/addClinic` | Add a clinic with `name`, `address`, and a seven-digit `phone`. |
| `POST` | `/api/addSpecialization` | Add a specialization with `name`. |
| `POST` | `/api/schedules` | Add a doctor's clinic schedule. |
| `POST` | `/api/doctors/available-times` | Get hourly times for a doctor on a weekday. |
| `POST` | `/api/doctor/appointments` | List a doctor's appointments by status. |

Schedule request example:

```json
{
  "doctor_profile_id": 1,
  "clinic_id": 1,
  "day_of_week": "Monday",
  "start_time": "09:00",
  "end_time": "15:00"
}
```

For appointment filtering, `status` accepts `all`, `pending`, `confirmed`, `cancelled`, or `completed`.

### Appointments

| Method | Endpoint | Purpose |
| --- | --- | --- |
| `GET` | `/api/appointment-types` | List available appointment types and durations. |
| `POST` | `/api/doctor-schedules` | Return unbooked slots for a doctor, clinic, appointment type, and date. |
| `POST` | `/api/appointments/confirm` | Create an appointment for a selected slot. |

Available-slots request example:

```json
{
  "doctor_profile_id": 1,
  "clinic_id": 1,
  "appointment_type_id": 2,
  "date": "2026-10-12"
}
```

Confirm-appointment request example:

```json
{
  "user_id": 5,
  "doctor_profile_id": 1,
  "clinic_id": 1,
  "appointment_type_id": 2,
  "date": "2026-10-12",
  "start_time": "10:00"
}
```

When a booking is created successfully, the API returns HTTP `201` and stores the appointment with an initial `pending` status. Validation errors return HTTP `422`; missing resources return HTTP `404`; conflicting bookings or schedules return HTTP `409` where applicable.

## Testing

Run the automated test suite with:

```bash
php artisan test
```
