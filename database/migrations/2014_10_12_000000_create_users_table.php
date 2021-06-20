<?php

use Illuminate\Support\Facades\Schema;
use Jenssegers\Mongodb\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateUsersTable extends Migration
{
    private $table = 'users';
    protected $connection = 'mongodb';
    
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        //NOTE this schema must match the user model in the client - ie the user object used in the client end
        Schema::connection($this->connection)->create($this->table, function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('user_id')->unique();//which is essentially the verified E164 mobile phone number
            $table->string('user_type')->nullable();//whether free, silver or gold user
            $table->timestamp('phone_number_verified_at')->nullable();
            $table->string('password')->nullable();//for now we dont use password but we will reserve it here for future consideration e.g we user connect from web site 
            $table->string('api_token')->nullable();
            $table->string('country_code')->nullable();//e.g for nigeria it is NG
            $table->unsignedInteger('dialling_code')->nullable();//e.g for nigeria it is 234
            $table->string('country')->nullable();
            $table->string('first_name')->nullable();
            $table->string('last_name')->nullable();
            $table->string('mobile_phone_no')->nullable();//which is essentially the verified national mobile phone number
            $table->string('work_phone_no')->nullable();
            $table->string('website')->nullable();
            $table->string('profile_photo_url')->nullable();
            $table->string('personal_email')->nullable();
            $table->string('work_email')->nullable();
            $table->text('home_address')->nullable();
            $table->text('office_address')->nullable();
            $table->text('status_message')->nullable();
            $table->text('location_address')->nullable();
            $table->double('latitude')->nullable();
            $table->double('longitude')->nullable();
            $table->timestamp('location_time')->nullable();
            $table->text('contacts')->nullable();//names of contacts in user phone
            $table->date('dob')->nullable();
            $table->timestamp('last_seen')->nullable();
            $table->rememberToken();
            $table->timestamps();//created_at 
            $table->softDeletes();
        });
        
        
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::connection($this->connection)->dropIfExists($this->table);
    }
}
