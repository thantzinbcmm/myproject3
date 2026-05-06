// app/Exceptions/InventoryConflictException.php
<?php

namespace App\Exceptions;

class InventoryConflictException extends BusinessException
{
    public function __construct(string $message = '空室がありません。別の日程または客室タイプをお選びください。')
    {
        parent::__construct('INVENTORY_CONFLICT', $message);
    }
}