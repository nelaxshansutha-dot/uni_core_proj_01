<?php
require_once __DIR__ . '/../config/Database.php';

class Marketplace {
    private $conn;
    private $table = "marketplace";

    public function __construct() {
        $database = new Database();
        $this->conn = $database->getConnection();
    }

    public function create($data) {
        $query = "INSERT INTO " . $this->table . " 
            (seller_id, item_name, description, price, condition_type, location, phone_number, usage_duration, image_url, image_url2, image_url3, image_url4, status) 
            VALUES (:seller_id, :item_name, :description, :price, :condition_type, :location, :phone_number, :usage_duration, :image_url, :image_url2, :image_url3, :image_url4, :status)";
        $stmt = $this->conn->prepare($query);

        $stmt->bindParam(':seller_id',      $data['seller_id']);
        $stmt->bindParam(':item_name',      $data['item_name']);
        $stmt->bindParam(':description',    $data['description']);
        $stmt->bindParam(':price',          $data['price']);
        $stmt->bindParam(':condition_type', $data['condition_type']);
        $stmt->bindParam(':location',       $data['location']);
        $stmt->bindParam(':phone_number',   $data['phone_number']);
        $stmt->bindParam(':usage_duration', $data['usage_duration']);
        $stmt->bindParam(':image_url',      $data['image_url']);
        $stmt->bindParam(':image_url2',     $data['image_url2']);
        $stmt->bindParam(':image_url3',     $data['image_url3']);
        $stmt->bindParam(':image_url4',     $data['image_url4']);
        $stmt->bindParam(':status',         $data['status']);

        return $stmt->execute();
    }

    public function getAll() {
        $query = "SELECT m.*, u.enrollment_no, 
                         CONCAT(u.fname, ' ', u.lname) AS seller_name
                  FROM " . $this->table . " m 
                  JOIN Users u ON m.seller_id = u.userID
                  ORDER BY m.created_at DESC";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function updateStatus($id, $seller_id, $status) {
        $query = "UPDATE " . $this->table . " SET status = :status WHERE id = :id AND seller_id = :seller_id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':status',    $status);
        $stmt->bindParam(':id',        $id);
        $stmt->bindParam(':seller_id', $seller_id);
        return $stmt->execute();
    }

    public function update($data, $seller_id) {
        $query = "UPDATE " . $this->table . " 
            SET item_name = :item_name, 
                description = :description, 
                price = :price, 
                condition_type = :condition_type, 
                location = :location, 
                phone_number = :phone_number, 
                usage_duration = :usage_duration, 
                image_url = :image_url, 
                image_url2 = :image_url2, 
                image_url3 = :image_url3, 
                image_url4 = :image_url4
            WHERE id = :id AND seller_id = :seller_id";
        
        $stmt = $this->conn->prepare($query);

        $stmt->bindParam(':item_name',      $data['item_name']);
        $stmt->bindParam(':description',    $data['description']);
        $stmt->bindParam(':price',          $data['price']);
        $stmt->bindParam(':condition_type', $data['condition_type']);
        $stmt->bindParam(':location',       $data['location']);
        $stmt->bindParam(':phone_number',   $data['phone_number']);
        $stmt->bindParam(':usage_duration', $data['usage_duration']);
        $stmt->bindParam(':image_url',      $data['image_url']);
        $stmt->bindParam(':image_url2',     $data['image_url2']);
        $stmt->bindParam(':image_url3',     $data['image_url3']);
        $stmt->bindParam(':image_url4',     $data['image_url4']);
        $stmt->bindParam(':id',             $data['id']);
        $stmt->bindParam(':seller_id',      $seller_id);

        return $stmt->execute();
    }

    public function delete($id, $seller_id) {
        $query = "DELETE FROM " . $this->table . " WHERE id = :id AND seller_id = :seller_id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id',        $id);
        $stmt->bindParam(':seller_id', $seller_id);
        return $stmt->execute();
    }
}
?>

<!-- இதன் எளிமையான தமிழ் விளக்கம் இதோ:

---

### 1. `execute()` என்றால் என்ன?
`execute` என்ற ஆங்கிலச் சொல்லுக்கு **"செயல்படுத்து"** அல்லது **"இயக்கு"** என்று பொருள். 

இதற்கு முந்தைய வரிகளில் நாம் என்ன செய்தோம்?
1. `prepare()` மூலமாக ஒரு வெற்று வடிவக் குறியீட்டைத் தயார் செய்தோம்.
2. `bindParam()` மூலமாக எந்தெந்தக் கட்டத்தில் என்னென்ன தரவுகள் (Data) அமர வேண்டும் என்று முடிச்சுப் போட்டோம்.

இப்போது இந்த `execute()` வரியை PHP படிக்கும் போதுதான், அதுவரை நாம் சேர்த்து வைத்த அனைத்துத் தரவுகளையும் (விற்பனையாளர் ஐடி, பொருளின் பெயர், விலை போன்ற அனைத்தையும்) ஒன்றாகத் திரட்டி, **நிஜமாகவே தரவுத்தளத்திற்குள் (Database) அனுப்பி, அங்கு ஒரு புதிய பதிவாகச் சேமிக்கும்.** ---

### 2. `return` ஏன் பயன்படுத்தப்படுகிறது?
`return` என்பது இந்த Function-ஐப் பயன்படுத்தும் கோப்பிற்கு (உதாரணமாக உங்கள் Controller) ஒரு பதிலை அல்லது சமிக்ஞையைத் திருப்பி அனுப்பப் பயன்படுகிறது.

`$stmt->execute()` இயங்கி முடித்ததும் அது தரவுத்தளத்திடம் இருந்து ஒரு பதிலைப் பெற்று, இரண்டில் ஒன்றை நமக்குத் திருப்பித் தரும் (Boolean Value):

* **`true` (உண்மை):** தரவுகள் எந்தப் பிரச்சினையும் இல்லாமல், வெற்றிகரமாகத் தரவுத்தளத்தில் சேமிக்கப்பட்டு விட்டால் `true` என்று பதில் வரும்.
* **`false` (தவறு):** தரவுத்தளத்தில் இடம் பற்றாக்குறை, அல்லது ஏதேனும் தொழில்நுட்பக் கோளாறு காரணமாகத் தரவு சேமிக்கப்படவில்லை என்றால் `false` என்று பதில் வரும்.

இதைத்தான் இந்த Function அப்படியே `return` செய்கிறது. இதை வைத்து உங்கள் Controller கோப்பில், *"விளம்பரம் வெற்றிகரமாகப் பதிவிடப்பட்டது"* என்றோ அல்லது *"ஏதோ தவறு நடந்துவிட்டது"* என்றோ பயனருக்குத் திரையில் காட்ட முடியும்.

---

### முழுச் சங்கிலித் தொடர் (The Whole Process Summary)

நாம் பார்த்த அனைத்து வரிகளையும் ஒன்றாகச் சேர்த்துப் பார்த்தால் இப்படித்தான் இருக்கும்:

```php
public function create($data) {
    // 1. திட்டமிடுதல் (SQL Query-ஐ எழுதுதல்)
    $query = "INSERT INTO ... VALUES (:item_name, :price...)";
    
    // 2. சமையல் பாத்திரத்தை அடுப்பில் வைத்தல் (தரவுத்தளத்தை தயார் செய்தல்)
    $stmt = $this->conn->prepare($query);
    
    // 3. பொருட்களைப் பாத்திரத்திற்கு அருகே கொண்டு வருதல் (தரவுகளை பிணைத்தல்)
    $stmt->bindParam(':item_name', $data['item_name']);
    $stmt->bindParam(':price',     $data['price']);
    // ... மற்ற Bind வரிகள்
    
    // 4. அடுப்பை எரியூட்டிச் சமைத்தல் மற்றும் முடிவை அறிவித்தல் (இயக்கி, பதிலை அனுப்புதல்)
    return $stmt->execute(); 
} -->
