<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateStaffLeaveRequests extends Migration
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
            'request_type' => [
                'type' => 'VARCHAR',
                'constraint' => 20,
            ],
            'leave_category' => [
                'type' => 'VARCHAR',
                'constraint' => 50,
                'null' => true,
            ],
            'start_date' => [
                'type' => 'DATE',
            ],
            'end_date' => [
                'type' => 'DATE',
            ],
            'reason' => [
                'type' => 'TEXT',
            ],
            'status' => [
                'type' => 'VARCHAR',
                'constraint' => 20,
                'default' => 'pending',
            ],
            'admin_note' => [
                'type' => 'TEXT',
                'null' => true,
            ],
            'rejection_reason' => [
                'type' => 'TEXT',
                'null' => true,
            ],
            'reviewed_by' => [
                'type' => 'INT',
                'unsigned' => true,
                'null' => true,
            ],
            'reviewed_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
            'created_at' => [
                'type' => 'DATETIME',
            ],
            'updated_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
        ]);

        $this->forge->addKey('id', true);
        $this->forge->addKey(['user_id', 'status']);
        $this->forge->addKey(['status', 'created_at']);

        $this->forge->addForeignKey(
            'user_id',
            'users',
            'id',
            'RESTRICT',
            'CASCADE'
        );

        $this->forge->addForeignKey(
            'reviewed_by',
            'users',
            'id',
            'RESTRICT',
            'SET NULL'
        );

        $this->forge->createTable(
            'staff_leave_requests',
            false,
            ['ENGINE' => 'InnoDB']
        );
    }

    public function down()
    {
        $this->forge->dropTable('staff_leave_requests');
    }
}
