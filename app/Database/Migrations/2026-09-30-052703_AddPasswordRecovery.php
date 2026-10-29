<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddPasswordRecovery extends Migration
{
    public function up()
    {
        $this->forge->addColumn('users', [
            'auth_version' => [
                'type'       => 'INT',
                'unsigned'   => true,
                'default'    => 1,
            ],
        ]);

        $this->forge->addField([
            'id' => [
                'type'           => 'INT',
                'unsigned'       => true,
                'auto_increment' => true,
            ],
            'user_id' => [
                'type'     => 'INT',
                'unsigned' => true,
            ],
            'token_hash' => [
                'type'       => 'CHAR',
                'constraint' => 64,
            ],
            'expires_at' => [
                'type' => 'DATETIME',
            ],
            'created_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
            'updated_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
        ]);

        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey('user_id');
        $this->forge->addUniqueKey('token_hash');

        $this->forge->addForeignKey(
            'user_id',
            'users',
            'id',
            'RESTRICT',
            'CASCADE'
        );

        $this->forge->createTable('password_reset_tokens');
    }

    public function down()
    {
        $this->forge->dropTable('password_reset_tokens');
        $this->forge->dropColumn('users', 'auth_version');
    }
}