<?php

namespace App;

use Illuminate\Notifications\Notifiable;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Foundation\Auth\User as Authenticatable;
use App\Notifications\CustomResetPasswordNotification;
use App\Notifications\CustomEmailVerificationNotification;

class User extends Authenticatable implements MustVerifyEmail {

    use Notifiable;

    /**
     * The primary key associated with the table.
     *
     * @var string
     */
    protected $primaryKey = 'user_id';

    /**
     * Indicates if the IDs are auto-incrementing.
     *
     * @var bool
     */
    public $incrementing = false;
/**
     * The "type" of the auto-incrementing ID.
     *
     * @var string
     */
    protected $keyType = 'string';
    
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'user_id',
        'email',
        'first_name',
        'last_name',
        'phone_no'
    ];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
        'password', 'remember_token',
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
    ];

    /**
     * Send the password reset notification.
     *
     * @param  string  $token
     * @return void
     */
    public function sendPasswordResetNotification($token) {
        $this->notify(new CustomResetPasswordNotification($token));
    }

    public function sendEmailVerificationNotification() {
        $this->notify(new CustomEmailVerificationNotification);
    }

    /**
     * A user have one subscription
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function subscription() {
        $foreignKey = $this->primaryKey; //this column name will be used in message table
        $localKey = $this->primaryKey;
        return $this->hasOne(Subscription::class, $foreignKey, $localKey);
    }

    /**
     * A user can have many messages
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function messages() {
        $foreignKey = $this->primaryKey; //this column name will be used in message table
        $localKey = $this->primaryKey;
        return $this->hasMany(Message::class, $foreignKey, $localKey);
    }

}
