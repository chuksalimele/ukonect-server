<?php

namespace App;

use Jenssegers\Mongodb\Eloquent\Model;

class MongoTest extends Model
{
    
    protected $connection = 'mongodb';
    protected $collection = 'my_laravel_mongo';
}