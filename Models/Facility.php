<?php
require_once('Models/Database.php');

class Facility {
    private $conn;

    // Constructor: Connects to the database when the object is created
    public function __construct() {
        $db = new Database();
        $this->conn = $db->connect();
    }

    // Common method to prepare and execute SQL safely using prepared statements
    private function prepareAndExecuteQuery($query, $params = []) {
        try {
            $stmt = $this->conn->prepare($query);
            foreach ($params as $key => $value) {
                $stmt->bindValue($key, $value);
            }
            $stmt->execute();
            return $stmt;
        } catch (PDOException $e) {
            throw new Exception("Database error: " . $e->getMessage());
        }
    }

    // Fetch a limited number of facilities (with optional pagination)
    public function getFacilities($start = 0, $limit = 10) {
        $query = "SELECT * FROM ecoFacilities LIMIT :start, :limit";
        $params = [':start' => (int)$start, ':limit' => (int)$limit];
        $stmt = $this->prepareAndExecuteQuery($query, $params);
        $facilities = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Add edit URL for each facility (for admin panel)
        foreach ($facilities as &$facility) {
            $facility['edit_url'] = isset($facility['id']) ? "admin_edit_facility.php?id=" . $facility['id'] : '';
        }

        return $facilities;
    }

    // Fetch all facilities without limit
    public function getAllFacilities() {
        $query = "SELECT * FROM ecoFacilities";
        $stmt = $this->prepareAndExecuteQuery($query);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Get total number of facilities (for pagination)
    public function getTotalFacilities() {
        $query = "SELECT COUNT(*) as total FROM ecoFacilities";
        $stmt = $this->prepareAndExecuteQuery($query);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result['total'] ?? 0;
    }

    // Get a specific facility by its ID
    public function getFacilityById($id) {
        if (!$id) {
            throw new InvalidArgumentException("No facility ID provided.");
        }

        $query = "SELECT * FROM ecoFacilities WHERE id = :id";
        $params = [':id' => $id];
        $stmt = $this->prepareAndExecuteQuery($query, $params);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    // Add a new facility to the database
    public function addFacility($title, $category, $description, $houseNumber, $streetName, $town, $county, $lat, $lng, $contributor) {
        $query = "INSERT INTO ecoFacilities (title, category, description, houseNumber, streetName, town, county, lat, lng, contributor) 
                  VALUES (:title, :category, :description, :houseNumber, :streetName, :town, :county, :lat, :lng, :contributor)";

        $params = compact('title', 'category', 'description', 'houseNumber', 'streetName', 'town', 'county', 'lat', 'lng', 'contributor');
        $this->prepareAndExecuteQuery($query, $params);
        return $this->conn->lastInsertId(); // Return the ID of the new facility
    }

    // Update details of an existing facility
    public function updateFacility($id, $title, $category, $description, $houseNumber, $streetName, $town, $county, $lat, $lng) {
        $query = "UPDATE ecoFacilities SET title = :title, category = :category, description = :description, 
                  houseNumber = :houseNumber, streetName = :streetName, town = :town, county = :county, lat = :lat, lng = :lng 
                  WHERE id = :id";

        $params = compact('id', 'title', 'category', 'description', 'houseNumber', 'streetName', 'town', 'county', 'lat', 'lng');
        $this->prepareAndExecuteQuery($query, $params);
        return true;
    }

    // Delete a facility by ID
    public function deleteFacility($id) {
        if (!$id) {
            throw new InvalidArgumentException("No facility ID provided.");
        }

        $query = "DELETE FROM ecoFacilities WHERE id = :id";
        $params = [':id' => $id];
        $this->prepareAndExecuteQuery($query, $params);
        return true;
    }

    // Search for facilities by keyword (used for live search)
    public function searchFacilities($keyword, $start = 0, $limit = 10) {
        if (empty($keyword)) {
            throw new InvalidArgumentException("Search keyword cannot be empty.");
        }

        $query = "SELECT * FROM ecoFacilities WHERE 
                  title LIKE :keyword OR 
                  category LIKE :keyword OR 
                  description LIKE :keyword 
                  LIMIT :start, :limit";

        $params = [
            ':keyword' => "%" . htmlspecialchars($keyword) . "%",
            ':start' => (int)$start,
            ':limit' => (int)$limit
        ];

        $stmt = $this->prepareAndExecuteQuery($query, $params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Get distinct categories by joining facilities and category tables
    public function getAllCategories() {
        $query = "SELECT DISTINCT c.id, c.name 
                  FROM ecoFacilities f
                  JOIN ecoCategories c ON f.category = c.id
                  ORDER BY c.name ASC";

        $stmt = $this->prepareAndExecuteQuery($query);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Get all unique town names (for filtering dropdown)
    public function getAllTowns() {
        $query = "SELECT DISTINCT town FROM ecoFacilities WHERE town IS NOT NULL AND town != '' ORDER BY town ASC";
        $stmt = $this->prepareAndExecuteQuery($query);
        return $stmt->fetchAll(PDO::FETCH_COLUMN);
    }

    // Search with filters (keyword, category, town) used in live search
    public function searchFacilitiesWithFilters($keyword, $category = '', $town = '', $start = 0, $limit = 10) {
        $query = "SELECT * FROM ecoFacilities WHERE 
                  (title LIKE :keyword OR category LIKE :keyword OR description LIKE :keyword)";

        $params = [':keyword' => "%" . htmlspecialchars($keyword) . "%"];

        if (!empty($category)) {
            $query .= " AND category = :category";
            $params[':category'] = (int)$category;
        }

        if (!empty($town)) {
            $query .= " AND town = :town";
            $params[':town'] = $town;
        }

        $query .= " LIMIT :start, :limit";
        $params[':start'] = (int)$start;
        $params[':limit'] = (int)$limit;

        $stmt = $this->prepareAndExecuteQuery($query, $params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Update the status comment for a specific facility (used from dashboard dropdown)
    public function updateFacilityComment($facilityId, $comment) {
        if (empty($facilityId) || empty($comment)) {
            throw new InvalidArgumentException("Facility ID and comment cannot be empty.");
        }

        $query = "UPDATE ecoFacilities SET comments = :comments WHERE id = :id";
        $params = [
            ':comments' => $comment,
            ':id' => $facilityId
        ];

        $stmt = $this->prepareAndExecuteQuery($query, $params);
        return $stmt->rowCount() > 0; // Returns true if update was successful
    }
}
