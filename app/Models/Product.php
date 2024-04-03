<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    use HasFactory;
    protected $table = "table_product";
    protected $fillable  = [
        'title',
        'slug',
        'code',
        'meta_title',
        'meta_description',
        'meta_keyword',
        'description',
        'content',
        'price',
        'size',
        'image',
        'product_specifications',
        'product_features',
        'product_attachments',
    ];
    protected $primaryKey = "id";
}
