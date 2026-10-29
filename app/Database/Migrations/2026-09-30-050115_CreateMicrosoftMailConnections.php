<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateMicrosoftMailConnections extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id' => [
                'type'       => 'INT',
                'unsigned'   => true,
                'constraint' => 11,
            ],
            'tenant_id' => [
                'type'       => 'VARCHAR',
                'constraint' => 36,
            ],
            'client_id' => [
                'type'       => 'VARCHAR',
                'constraint' => 36,
            ],
            'microsoft_user_id' => [
                'type'       => 'VARCHAR',
                'constraint' => 100,
            ],
            'sender_email' => [
                'type'       => 'VARCHAR',
                'constraint' => 254,
            ],
            'token_payload' => [
                'type' => 'MEDIUMTEXT',
            ],
            'created_at' => [
                'type' => 'DATETIME',
            ],
            'updated_at' => [
                'type' => 'DATETIME',
            ],
        ]);

        $this->forge->addKey('id', true);

        $this->forge->createTable('microsoft_mail_connections');
    }

    public function down()
    {
        $this->forge->dropTable('microsoft_mail_connections');
    }
}