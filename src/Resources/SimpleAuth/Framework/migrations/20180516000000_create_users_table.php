<?php

defined('BASEPATH') OR exit('No direct script access allowed');

class Migration_create_users_table extends CI_Migration
{
    public function up()
    {
        $this->dbforge->add_field([
            'id' =>
                [
                    'type'           => 'INT',
                    'constraint'     => 8,
                    'unsigned'       => TRUE,
                    'auto_increment' => TRUE
                ],
            'first_name' =>
                [
                    'type'       => 'VARCHAR',
                    'constraint' => 65,
                ],
            'last_name' =>
                [
                    'type'       => 'VARCHAR',
                    'constraint' => 65,
                ],
            'username' =>
                [
                    'type'       => 'VARCHAR',
                    'constraint' => 22,
                    'unique'     => TRUE,
                ],
            'gender' =>
                [
                    'type'       => 'VARCHAR',
                    'constraint' => 1,
                ],
            'email' =>
                [
                    'type'       => 'VARCHAR',
                    'constraint' => 255,
                    'unique'     => TRUE,
                ],
            'password' =>
                [
                    'type'       => 'VARCHAR',
                    'constraint' => 255,
                ],
            'role' =>
                [
                    'type'       => 'VARCHAR',
                    'constraint' => 45,
                    'default'    => 'user'
                ],
            'remember_token' =>
                [
                    'type'       => 'VARCHAR',
                    'constraint' => 128,
                ],
            'active' =>
                [
                    'type'       => 'INT',
                    'constraint' => 1,
                    'unsigned'   => TRUE,
                    'default'    => 1,
                ],
            'verified' =>
                [
                    'type'       => 'INT',
                    'constraint' => 1,
                    'unsigned'   => TRUE,
                    'default'    => 0,
                ],
            'created_at' =>
                [
                    'type' => 'DATETIME',
                ],
            'updated_at' =>
                [
                    'type' => 'DATETIME',
                    'null' => TRUE,
                ],
        ]);
        $this->dbforge->add_key('id', TRUE);
        $this->dbforge->create_table('users');
    }

    public function down()
    {
        $this->dbforge->drop_table('users',TRUE);
    }
}