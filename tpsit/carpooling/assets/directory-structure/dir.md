# Direcotry

```plain text
carpooling/
├── api/
│   ├── config/
│   │   ├── database.php       # Database connection configuration
│   │   └── config.php         # General API configuration
│   ├── controllers/
│   │   ├── autista.php        # Driver-related API endpoints
│   │   ├── passeggero.php     # Passenger-related API endpoints
│   │   ├── automobile.php     # Car-related API endpoints
│   │   ├── viaggio.php        # Trip-related API endpoints
│   │   └── prenotazione.php   # Booking-related API endpoints
│   ├── middleware/
│   │   ├── auth.php           # Authentication middleware
│   │   └── cors.php           # CORS middleware for API access
│   ├── models/
│   │   ├── Autista.php        # Driver model
│   │   ├── Passeggero.php     # Passenger model
│   │   ├── Automobile.php     # Car model
│   │   ├── Viaggio.php        # Trip model
│   │   └── Prenotazione.php   # Booking model
│   ├── utils/
│   │   ├── response.php       # API response handling
│   │   └── validation.php     # Input validation
│   ├── .htaccess              # Apache configuration for API
│   └── index.php              # API entry point
├── assets/
│   ├── css/
│   ├── js/
│   ├── img/
│   └── uploads/               # For user uploads like profile photos
├── includes/
│   ├── header.php
│   ├── footer.php
│   └── navbar.php
├── pages/
│   ├── autista/
│   │   ├── register.php
│   │   ├── profile.php
│   │   └── trips.php
│   ├── passeggero/
│   │   ├── register.php
│   │   └── search.php
│   └── admin/
│       └── dashboard.php
├── .htaccess                  # Main Apache configuration
├── index.php                  # Main entry point
├── login.php
└── register.php
```
