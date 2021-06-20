<?php

use Illuminate\Support\Facades\Schema;
use Jenssegers\Mongodb\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateSubscriptionsTable extends Migration
{
    private $table = 'subscriptions';
    protected $connection = 'mongodb';
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::connection($this->connection)->create($this->table, function (Blueprint $table) {
            
            $table->bigIncrements('id');
            $table->string('user_id')->index();//foreign key - we will use this column name as the primary key of the user table
            $table->string('type')->index(); //whether silver or gold
            $table->double('amount')->nullable();  
            $table->string('payment_method')->index();          
            $table->timestamps();//created_at - date of subscription
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
