<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Message extends Model {

    /**
     * Fields that are mass assignable
     *
     * @var array
     */
    protected $fillable = ['message'];

    /**
     * A message belong to a user
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function user() {
        $key = (new User)->primaryKey;
        $foreignKey = $key;
        $ownerKey = $key;
        return $this->belongsTo(User::class, $foreignKey, $ownerKey);
    }

}
