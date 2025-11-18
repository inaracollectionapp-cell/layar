# ISOLA SCREEN Cinema Booking System

## Overview

ISOLA SCREEN is a cinema ticket booking system that generates **individual tickets per seat**, each with a unique QR code. The system handles the complete booking workflow from movie selection through payment processing to ticket generation and validation. Built with PHP and designed for Indonesian cinema operations, it supports multiple payment gateways and provides a modern, user-friendly interface for customers.

**Latest Update (Nov 2025)**: Upgraded to individual ticket system where each seat gets its own ticket with unique QR code, replacing the old system of one ticket per booking. Includes beautiful modern design with gradient theme (purple to red) matching website colors.

## User Preferences

Preferred communication style: Simple, everyday language.

## System Architecture

### Frontend Architecture

**Technology Stack**: HTML, CSS (Tailwind CSS), JavaScript
- Responsive design using Tailwind CSS utility classes
- Custom animations and styling in `assets/css/style.css`
- Lazy loading for images using Intersection Observer API
- Client-side formatting utilities (Rupiah currency formatting)

**Design Pattern**: Traditional server-rendered PHP pages with progressive enhancement
- No frontend framework (vanilla JavaScript)
- Form-based interactions with server-side processing
- Gradient theme: Purple (#7c3aed) to Red (#dc2626) for headers
- Gold/amber (#f59e0b) for seat highlighting

### Backend Architecture

**Core Technology**: PHP 7.4+ with procedural and functional programming approach
- File-based routing (no framework)
- Modular function libraries in `includes/` directory
- Session-based user state management
- Direct database queries (no ORM)

**Key Components**:
- `includes/functions.php` - Core booking and database query functions
- `includes/ticket-functions.php` - Individual ticket generation and validation
- `includes/email-functions.php` - Email notification system
- Payment callback handlers (`payment-callback.php`, `tripay-webhook.php`)

**Ticket Generation System**: Individual tickets per seat (✅ UPGRADED NOV 2025)
- Each seat gets a unique ticket with format: `ISOLA-XXXXXXXX-SEAT` (e.g., `ISOLA-5A42BE81-A1`)
- QR codes generated using Endroid QR Code library (stored as PNG files)
- Tickets auto-generated via database triggers when booking is created/paid
- Database triggers ensure ticket creation even if application logic fails
- Backward compatible with legacy booking codes

**File Generation**: (✅ UPGRADED with modern design)
- `download-ticket.php` - Generates beautiful JPG tickets (individual or ZIP for multiple)
- `print-ticket.php` - Generates professional PDF tickets using FPDF
- QR codes stored as PNG files in `qr-codes/` folder
- Design: Gradient header (purple→red), gold seat labels, modern layout

### Data Storage

**Database**: MySQL/MariaDB (schema not provided in repository, but structure inferred)

**Primary Tables**:
- `bookings` - Main booking records with payment status
- `tickets` - Individual ticket records (one per seat)
  - Columns: id, booking_id, booking_code, seat_label, ticket_code, qr_code_path, ticket_status, used_at
  - Status tracking for each individual ticket

**Triggers**:
- `after_booking_insert` - Auto-generates tickets when booking is created
- `after_booking_payment` - Fallback trigger to ensure tickets are created when payment is confirmed

**File Storage**: QR codes and ticket images stored in filesystem
- QR code PNG files generated and cached
- Organized by booking/ticket codes

### Authentication & Authorization

**Pattern**: Session-based authentication (inferred from typical PHP cinema booking systems)
- Customer sessions for booking flow
- Admin panel likely exists for ticket validation and management
- No JWT or token-based auth (traditional PHP sessions)

**Validation**: Ticket QR code validation system (✅ UPGRADED for individual tickets)
- `validateTicketQRCode()` - Supports both individual tickets and legacy booking codes
- `markTicketAsUsed()` - Tracks individual ticket usage with timestamp
- `checkAllTicketsUsed()` - Updates booking status when all tickets validated
- Scan interface: Modern HTML5 QR Scanner with camera support

## External Dependencies

### Third-Party Libraries (Composer)

**PHPMailer** (^6.8) - Email delivery system
- Used for sending booking confirmations and tickets
- SMTP support for reliable email delivery
- Multipart email support for HTML and plain text

**Endroid QR Code** (^4.8) - QR code generation
- Built on Bacon QR Code library
- PNG output for embedded QR codes on tickets
- High error correction level for scanning reliability

**FPDF** (^1.8) - PDF generation
- Generates printable PDF tickets
- Custom styling and layout for professional appearance

**Bacon QR Code** (2.0.8) - QR code matrix generation
- Dependency of Endroid QR Code
- Core QR code encoding logic

### Payment Gateway Integration

**Tripay Webhook** - Indonesian payment gateway
- Webhook handler: `tripay-webhook.php`
- Auto-generates tickets on payment confirmation
- Supports multiple payment methods popular in Indonesia

**Generic Payment Callback** - `payment-callback.php`
- Handles payment confirmations from various gateways
- Triggers ticket generation workflow

### Email Service

**SMTP Integration** via PHPMailer
- Configured for transactional emails
- Sends booking confirmations with embedded ticket information
- Includes QR codes in email body

### Image Processing

**GD Extension** - Required for QR code and ticket image generation
- PNG generation for QR codes
- JPG generation for ticket downloads
- Image manipulation for ticket layouts

### System Requirements

- PHP 7.4 or higher (PHP 8.x compatible)
- MySQL/MariaDB database
- PHP Extensions: GD, iconv, ctype, filter, hash, zlib
- Composer for dependency management
- Web server (Apache/Nginx) with PHP support