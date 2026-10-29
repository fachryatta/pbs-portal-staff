<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateSalarySlips extends Migration
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
            'period_month' => [
                'type' => 'TINYINT',
                'unsigned' => true,
            ],
            'period_year' => [
                'type' => 'SMALLINT',
                'unsigned' => true,
            ],
            'slip_number' => [
                'type' => 'VARCHAR',
                'constraint' => 40,
                'null' => true,
            ],
            // Salinan identitas untuk arsip slip, bukan relasi profil langsung.
            'staff_name' => [
                'type' => 'VARCHAR',
                'constraint' => 200,
            ],
            'staff_username' => [
                'type' => 'VARCHAR',
                'constraint' => 50,
            ],
            'staff_category' => [
                'type' => 'VARCHAR',
                'constraint' => 30,
                'null' => true,
            ],
            // Nominal rupiah bulat; tidak menggunakan FLOAT/DOUBLE.
            'basic_salary' => [
                'type' => 'DECIMAL',
                'constraint' => '15,0',
                'default' => 0,
            ],
            'notes' => [
                'type' => 'TEXT',
                'null' => true,
            ],
            'status' => [
                'type' => 'ENUM',
                'constraint' => ['draft', 'published'],
                'default' => 'draft',
            ],
            'pdf_filename' => [
                'type' => 'VARCHAR',
                'constraint' => 100,
                'null' => true,
            ],
            'created_by' => [
                'type' => 'INT',
                'unsigned' => true,
                'null' => true,
            ],
            'published_by' => [
                'type' => 'INT',
                'unsigned' => true,
                'null' => true,
            ],
            'published_at' => [
                'type' => 'DATETIME',
                'null' => true,
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
        // Satu slip per staff per bulan, termasuk draft.
        $this->forge->addUniqueKey(
            ['user_id', 'period_year', 'period_month'],
            'salary_slips_user_period_unique'
        );
        $this->forge->addUniqueKey('slip_number');
        $this->forge->addUniqueKey('pdf_filename');
        $this->forge->addKey(
            ['period_year', 'period_month', 'status'],
            false,
            false,
            'salary_slips_period_status_idx'
        );

        // Akun pemilik tidak boleh dihapus selama memiliki arsip slip.
        $this->forge->addForeignKey('user_id', 'users', 'id', 'RESTRICT', 'RESTRICT');
        // Penghapusan akun admin pembuat tidak menghapus slip staff.
        $this->forge->addForeignKey('created_by', 'users', 'id', 'RESTRICT', 'SET NULL');
        $this->forge->addForeignKey('published_by', 'users', 'id', 'RESTRICT', 'SET NULL');
        $this->forge->createTable('salary_slips', false, ['ENGINE' => 'InnoDB']);

        $this->forge->addField([
            'id' => [
                'type' => 'INT',
                'unsigned' => true,
                'auto_increment' => true,
            ],
            'salary_slip_id' => [
                'type' => 'INT',
                'unsigned' => true,
            ],
            'item_type' => [
                'type' => 'ENUM',
                'constraint' => ['earning', 'deduction'],
            ],
            'label' => [
                'type' => 'VARCHAR',
                'constraint' => 100,
            ],
            'amount' => [
                'type' => 'DECIMAL',
                'constraint' => '15,0',
                'default' => 0,
            ],
            'sort_order' => [
                'type' => 'SMALLINT',
                'unsigned' => true,
                'default' => 0,
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
        $this->forge->addKey('salary_slip_id');
        $this->forge->addForeignKey(
            'salary_slip_id', 'salary_slips', 'id', 'RESTRICT', 'CASCADE'
        );
        $this->forge->createTable('salary_slip_items', false, ['ENGINE' => 'InnoDB']);
    }

    public function down()
    {
        $this->forge->dropTable('salary_slip_items');
        $this->forge->dropTable('salary_slips');
    }
}
