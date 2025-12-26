
<?php

class Database {

    public function connect() {
        try {
            // Create a connection to the SQLite database
            $dbHandle = new PDO("sqlite:ecoBuddy.sqlite");
            $dbHandle->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION); // Set error mode to exception
            return $dbHandle; // Return the PDO connection object
        } catch (PDOException $e) {
            // Catch any exceptions (errors) and display the message
            echo "Database connection failed: " . $e->getMessage();
        }
    }
}
