<?php
namespace Models\V1;

use Models\BaseModel;

class CartItem extends BaseModel
{
    protected $table = 'cart_items';
    protected $primaryKey = 'id';
    protected $timestamps = true;


    //get item with product details
    public function getWithProduct($itemId)
    {
        $sql = "SELECT ci.*, p.*
                FROM {$this->table} ci
                JOIN products p ON ci.product_id = p.id
                WHERE ci.id = " . (int)$itemId;
        
        return $this->fetchOne($sql);
    }


    //check if user owns this cart item (for V2 ownership checks)
    public function belongsToUser($itemId, $userId)
    {
        $sql = "SELECT ci.id
                FROM {$this->table} ci
                JOIN carts c ON ci.cart_id = c.id
                WHERE ci.id = " . (int)$itemId . "
                AND c.user_id = " . (int)$userId;
        
        $result = $this->fetchOne($sql);
        
        return $result !== null;
    }
}