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