<?php
class EmailService {
    private $fromEmail;
    private $fromName;
    
    public function __construct($fromEmail = 'noreply@ridetogether.com', $fromName = 'RideTogether') {
        $this->fromEmail = $fromEmail;
        $this->fromName = $fromName;
    }
    
    public function sendBookingNotification($driverEmail, $driverName, $passengerInfo, $tripDetails) {
        $subject = "New booking request - RideTogether";
        
        // Create email body
        $message = "
        <html>
        <head>
            <title>New Booking Request</title>
        </head>
        <body>
            <h2>Hello {$driverName},</h2>
            <p>You have received a new booking request for your trip.</p>
            
            <h3>Trip Details:</h3>
            <p>
                <strong>From:</strong> {$tripDetails['citta_partenza']}<br>
                <strong>To:</strong> {$tripDetails['citta_destinazione']}<br>
                <strong>Date:</strong> " . date('l, F j, Y', strtotime($tripDetails['timestamp_partenza'])) . "<br>
                <strong>Time:</strong> " . date('H:i', strtotime($tripDetails['timestamp_partenza'])) . "
            </p>
            
            <h3>Passenger Information:</h3>
            <p>
                <strong>Name:</strong> {$passengerInfo['nome']} {$passengerInfo['cognome']}<br>
                <strong>Phone:</strong> {$passengerInfo['telefono']}<br>
                <strong>Email:</strong> {$passengerInfo['email']}<br>
                <strong>Seats requested:</strong> {$tripDetails['n_posti']}
            </p>
            
            <p>Please log in to your account to accept or reject this booking.</p>
            
            <p>Thank you for using RideTogether!</p>
        </body>
        </html>
        ";
        
        // Set email headers
        $headers = "From: {$this->fromName} <{$this->fromEmail}>\r\n";
        $headers .= "Reply-To: {$this->fromEmail}\r\n";
        $headers .= "Content-Type: text/html; charset=UTF-8\r\n";
        
        // Send the email
        return mail($driverEmail, $subject, $message, $headers);
    }
    
    public function sendBookingAcceptedNotification($passengerEmail, $passengerName, $driverInfo, $tripDetails, $vehicleInfo) {
        $subject = "Booking Accepted - RideTogether";
        
        // Create email body
        $message = "
        <html>
        <head>
            <title>Booking Accepted</title>
        </head>
        <body>
            <h2>Hello {$passengerName},</h2>
            <p>Great news! Your booking request has been <strong>accepted</strong>.</p>
            
            <h3>Trip Details:</h3>
            <p>
                <strong>From:</strong> {$tripDetails['citta_partenza']}<br>
                <strong>To:</strong> {$tripDetails['citta_destinazione']}<br>
                <strong>Date:</strong> " . date('l, F j, Y', strtotime($tripDetails['timestamp_partenza'])) . "<br>
                <strong>Time:</strong> " . date('H:i', strtotime($tripDetails['timestamp_partenza'])) . "<br>
                <strong>Price per person:</strong> €{$tripDetails['prezzo_cadauno']}<br>
                <strong>Estimated travel time:</strong> {$tripDetails['tempo_stimato']} minutes
            </p>
            
            <h3>Driver Information:</h3>
            <p>
                <strong>Name:</strong> {$driverInfo['nome']} {$driverInfo['cognome']}<br>
                <strong>Phone:</strong> {$driverInfo['telefono']}<br>
                <strong>Email:</strong> {$driverInfo['email']}
            </p>
            
            <h3>Vehicle Information:</h3>
            <p>
                <strong>Make:</strong> {$vehicleInfo['marca']}<br>
                <strong>Model:</strong> {$vehicleInfo['modello']}<br>
                <strong>License Plate:</strong> {$vehicleInfo['targa']}
            </p>
            
            <p>We recommend contacting your driver before the trip to confirm pickup details.</p>
            
            <p>Thank you for using RideTogether!</p>
        </body>
        </html>
        ";
        
        // Set email headers
        $headers = "From: {$this->fromName} <{$this->fromEmail}>\r\n";
        $headers .= "Reply-To: {$this->fromEmail}\r\n";
        $headers .= "Content-Type: text/html; charset=UTF-8\r\n";
        
        // Send the email
        return mail($passengerEmail, $subject, $message, $headers);
    }
    
    public function sendBookingRejectedNotification($passengerEmail, $passengerName, $tripDetails) {
        $subject = "Booking Rejected - RideTogether";
        
        // Create email body
        $message = "
        <html>
        <head>
            <title>Booking Rejected</title>
        </head>
        <body>
            <h2>Hello {$passengerName},</h2>
            <p>We're sorry to inform you that your booking request has been rejected by the driver.</p>
            
            <h3>Trip Details:</h3>
            <p>
                <strong>From:</strong> {$tripDetails['citta_partenza']}<br>
                <strong>To:</strong> {$tripDetails['citta_destinazione']}<br>
                <strong>Date:</strong> " . date('l, F j, Y', strtotime($tripDetails['timestamp_partenza'])) . "<br>
                <strong>Time:</strong> " . date('H:i', strtotime($tripDetails['timestamp_partenza'])) . "
            </p>
            
            <p>Don't worry! There are many other drivers offering similar trips. Try searching for another ride.</p>
            
            <p>Thank you for using RideTogether!</p>
        </body>
        </html>
        ";
        
        // Set email headers
        $headers = "From: {$this->fromName} <{$this->fromEmail}>\r\n";
        $headers .= "Reply-To: {$this->fromEmail}\r\n";
        $headers .= "Content-Type: text/html; charset=UTF-8\r\n";
        
        // Send the email
        return mail($passengerEmail, $subject, $message, $headers);
    }
    
    public function sendBookingClosedNotification($driverEmail, $driverName, $tripDetails) {
        $subject = "Bookings Closed - RideTogether";
        
        // Create email body
        $message = "
        <html>
        <head>
            <title>Bookings Closed</title>
        </head>
        <body>
            <h2>Hello {$driverName},</h2>
            <p>You have successfully closed bookings for your trip:</p>
            
            <h3>Trip Details:</h3>
            <p>
                <strong>From:</strong> {$tripDetails['citta_partenza']}<br>
                <strong>To:</strong> {$tripDetails['citta_destinazione']}<br>
                <strong>Date:</strong> " . date('l, F j, Y', strtotime($tripDetails['timestamp_partenza'])) . "<br>
                <strong>Time:</strong> " . date('H:i', strtotime($tripDetails['timestamp_partenza'])) . "
            </p>
            
            <p>No new passengers will be able to book this trip.</p>
            
            <p>Thank you for using RideTogether!</p>
        </body>
        </html>
        ";
        
        // Set email headers
        $headers = "From: {$this->fromName} <{$this->fromEmail}>\r\n";
        $headers .= "Reply-To: {$this->fromEmail}\r\n";
        $headers .= "Content-Type: text/html; charset=UTF-8\r\n";
        
        // Send the email
        return mail($driverEmail, $subject, $message, $headers);
    }
    
    public function sendTripCompletionNotification($userEmail, $userName, $tripDetails, $otherUserName, $isDriver) {
        $subject = "Trip completed - Please rate your " . ($isDriver ? "passenger" : "driver");
        
        // Create email body
        $message = "
        <html>
        <head>
            <title>Trip Completed - Rating Required</title>
        </head>
        <body>
            <h2>Hello {$userName},</h2>
            <p>Your trip has been marked as completed!</p>
            
            <h3>Trip Details:</h3>
            <p>
                <strong>From:</strong> {$tripDetails['citta_partenza']}<br>
                <strong>To:</strong> {$tripDetails['citta_destinazione']}<br>
                <strong>Date:</strong> " . date('l, F j, Y', strtotime($tripDetails['timestamp_partenza'])) . "
            </p>
            
            <p><strong>Please rate your " . ($isDriver ? "passenger" : "driver") . " {$otherUserName}.</strong></p>
            
            <p>Log in to your account and go to your trip history to provide a rating (1-5 stars).</p>
            
            <p>Thank you for using RideTogether!</p>
        </body>
        </html>
        ";
        
        // Set email headers
        $headers = "From: {$this->fromName} <{$this->fromEmail}>\r\n";
        $headers .= "Reply-To: {$this->fromEmail}\r\n";
        $headers .= "Content-Type: text/html; charset=UTF-8\r\n";
        
        // Send the email
        return mail($userEmail, $subject, $message, $headers);
    }
}
?>