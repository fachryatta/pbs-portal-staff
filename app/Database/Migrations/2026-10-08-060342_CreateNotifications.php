<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateNotifications extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id' => [
                'type' => 'INT',
                'unsigned' => true,
                'auto_increment' => true,
            ],
            'user_id' => [
                'type' => 'INT',
                'unsigned' => true,
            ],
            'actor_id' => [
                'type' => 'INT',
                'unsigned' => true,
                'null' => true,
            ],
            'type' => [
                'type' => 'VARCHAR',
                'constraint' => 50,
            ],
            'title' => [
                'type' => 'VARCHAR',
                'constraint' => 150,
            ],
            'message' => [
                'type' => 'TEXT',
            ],
            'target_path' => [
                'type' => 'VARCHAR',
                'constraint' => 255,
                'null' => true,
            ],
            'read_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
            'created_at' => [
                'type' => 'DATETIME',
            ],
        ]);

        $this->forge->addKey('id', true);
        $this->forge->addKey(['user_id', 'read_at', 'id']);

        $this->forge->addForeignKey(
            'user_id',
            'users',
            'id',
            'RESTRICT',
            'CASCADE'
        );

        $this->forge->addForeignKey(
            'actor_id',
            'users',
            'id',
            'RESTRICT',
            'SET NULL'
        );

        $this->forge->createTable(
            'notifications',
            false,
            ['ENGINE' => 'InnoDB']
        );
    }

    public function down()
    {
        $this->forge->dropTable('notifications');
    }
}