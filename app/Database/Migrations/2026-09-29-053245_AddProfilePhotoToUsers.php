<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddProfilePhotoToUsers extends Migration
{
    public function up()
    {
        $this->forge->addColumn('users', [
            'profile_photo' => [
                'type'       => 'VARCHAR',
                'constraint' => 100,
                'null'       => true,
                'after'      => 'full_name',
            ],
        ]);
    }

    public function down()
    {
        $this->forge->dropColumn('users', 'profile_photo');
    }
}