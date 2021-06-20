<?php

use Illuminate\Support\Facades\Schema;
use Jenssegers\Mongodb\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateMessagesTable extends Migration {

    private $table = 'messages';
    protected $connection = 'mongodb';
    
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up() {
        Schema::connection($this->connection)->create($this->table, function (Blueprint $table) {
            $table->increments('id');
            $table->string('user_id')->index();//foreign key - we will use this column name as the primary key of the user table
            $table->string('chatmate_id')->index();
            $table->text('message');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down() {
        Schema::connection($this->connection)->dropIfExists($this->table);
    }

}
