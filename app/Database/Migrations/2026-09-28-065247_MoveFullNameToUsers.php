<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;
use RuntimeException;

class MoveFullNameToUsers extends Migration
{
    public function up()
    {
        // 1. Tambahkan tempat penyimpanan nama pada akun.
        $this->forge->addColumn('users', [
            'full_name' => [
                'type'       => 'VARCHAR',
                'constraint' => 200,
                'null'       => false,
                'default'    => '',
                'after'      => 'id',
            ],
        ]);

        // 2. Salin nama profil yang sudah ada ke akun pemiliknya.
        $this->db->query(
            'UPDATE users
             INNER JOIN staff_profiles
                 ON staff_profiles.user_id = users.id
             SET users.full_name = staff_profiles.full_name'
        );

        // 3. Hapus kolom lama setelah nama selesai disalin.
        $this->forge->dropColumn('staff_profiles', 'full_name');
    }

    public function down()
    {
        // Struktur lama tidak bisa menyimpan nama akun tanpa profil.
        $accountWithoutProfile = $this->db->query(
            'SELECT users.id
             FROM users
             LEFT JOIN staff_profiles
                 ON staff_profiles.user_id = users.id
             WHERE staff_profiles.id IS NULL
             LIMIT 1'
        )->getRow();

        if ($accountWithoutProfile !== null) {
            throw new RuntimeException(
                'Rollback dihentikan: ada akun tanpa profil staff.'
            );
        }

        $this->forge->addColumn('staff_profiles', [
            'full_name' => [
                'type'       => 'VARCHAR',
                'constraint' => 200,
                'null'       => false,
                'default'    => '',
                'after'      => 'user_id',
            ],
        ]);

        $this->db->query(
            'UPDATE staff_profiles
             INNER JOIN users
                 ON users.id = staff_profiles.user_id
             SET staff_profiles.full_name = users.full_name'
        );

        $this->forge->dropColumn('users', 'full_name');
    }
}