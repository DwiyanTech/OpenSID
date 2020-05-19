<?php
class Migrasi_2005_ke_2006 extends CI_model {

	public function up()
	{
		$this->ubah_data_persil();
		$this->grup_akses_covid19();
		$this->load->model('migrations/migrasi_2004_ke_2005');
		$this->migrasi_2004_ke_2005->up(); // untuk yang sudah terlanjur mengkosongkan DB sebelum kosongkan_db diperbaiki

		// Ubah nama kode status penduduk
		$this->db->where('id', 2)
			->update('tweb_penduduk_status', array('nama' => 'TIDAK TETAP'));

		//Ganti nama folder widget menjadi widgets
		rename('desa/widget', 'desa/widgets');
		rename('desa/upload/widget', 'desa/upload/widgets');
		// Arahkan semua widget statis ubahan desa ke folder desa/widgets
		$list_widgets = $this->db->where('jenis_widget', 2)->get('widget')->result_array();
		foreach ($list_widgets as $widgets)
		{
			$ganti = str_replace('desa/widget', 'desa/widgets', $widgets['isi']); // Untuk versi 20.04-pasca ke atas
			$cek = explode('/', $ganti); // Untuk versi 20.04 ke bawah
			if ($cek[0] !== 'desa' AND $cek[1] === NULL)
			{ // agar migrasi bisa dijalankan berulang kali
				$this->db->where('id', $widgets['id'])->update('widget', array('isi' => 'desa/widgets/'.$widgets['isi']));
			}
		}
		// Sesuaikan dengan sql_mode STRICT_TRANS_TABLES
		$this->db->query("ALTER TABLE outbox MODIFY COLUMN CreatorID text NULL");
		// Hapus field sasaran
		if ($this->db->field_exists('sasaran', 'program_peserta'))
			$this->db->query('ALTER TABLE `program_peserta` DROP COLUMN `sasaran`');
		//tambah kolom email di tabel tweb_penduduk
		if (!$this->db->field_exists('email', 'tweb_penduduk'))
			$this->dbforge->add_column('tweb_penduduk', array(
				'email' => array(
				'type' => 'VARCHAR',
				'constraint' => 50,
				'null' => TRUE,
				),
			));
	}

	private function grup_akses_covid19()
	{
		// Menambahkan menu 'Group / Hak Akses' covid19 table 'user_grup'
		$data[] = array(
			'id'=>'5',
			'nama' => 'Satgas Covid-19',
		);

		foreach ($data as $grup)
		{
			$sql = $this->db->insert_string('user_grup', $grup);
			$sql .= " ON DUPLICATE KEY UPDATE
			id = VALUES(id),
			nama = VALUES(nama)";
			$this->db->query($sql);
		}
	}

	private function ubah_data_persil()
	{
		// Buat tabel baru
		$this->buat_ref_persil_kelas();
		$this->buat_ref_persil_mutasi();
		$this->buat_ref_persil_jenis_mutasi();
		$this->buat_cdesa();
		$this->buat_cdesa_penduduk();
		$this->buat_persil();
		$this->buat_mutasi_cdesa();
		// Tambah controller
		$this->tambah_modul();
		// Pindahkan data lama ke tabel baru

	}

	private function tambah_modul()
	{
		$this->db->where('id', 7)
			->update('setting_modul', array('url' => 'cdesa/clear'));
		// Tambah Modul Cdesa
		$submodul_cdesa = array('209'=>'data_persil', '210'=>'bidang_persil');
		foreach ($submodul_cdesa as $key => $submodul)
		{
			$modul_nonmenu = array(
				'id' => $key,
				'modul' => $submodul,
				'url' => $submodul,
				'aktif' => '1',
				'ikon' => '',
				'urut' => 0,
				'level' => 2,
				'parent' => '7',
				'hidden' => '2',
				'ikon_kecil' => ''
			);
			$sql = $this->db->insert_string('setting_modul', $modul_nonmenu) . " ON DUPLICATE KEY UPDATE modul = VALUES(modul), url = VALUES(url), parent = VALUES(parent)";
			$this->db->query($sql);
		}



		// $data = array(
		// 		'id' => 209,
		// 		'modul' => 'Persil',
		// 		'url' => 'data_persil',
		// 		'aktif' => 1,
		// 		'ikon' => '',
		// 		'urut' => 10,
		// 		'level' => 4,
		// 		'hidden' => 2,
		// 		'ikon_kecil' => '',
		// 		'parent' => 7
		// 		);
		// $sql = $this->db->insert_string('setting_modul', $data);
		// $sql .= " ON DUPLICATE KEY UPDATE
		// 		modul = VALUES(modul),
		// 		url = VALUES(url),
		// 		aktif = VALUES(aktif),
		// 		ikon = VALUES(ikon),
		// 		urut = VALUES(urut),
		// 		level = VALUES(level),
		// 		hidden = VALUES(hidden),
		// 		ikon_kecil = VALUES(ikon_kecil),
		// 		parent = VALUES(parent)
		// 		";
		// $this->db->query($sql);
	}

	private function buat_ref_persil_kelas()
	{
		// Buat tabel jenis Kelas Persil
		if (!$this->db->table_exists('ref_persil_kelas'))
		{
			$fields = array(
				'id' => array(
					'type' => 'INT',
					'constraint' => 5,
					'unsigned' => TRUE,
					'auto_increment' => TRUE
				),
				'tipe' => array(
					'type' => 'VARCHAR',
					'constraint' => 20
				),
				'kode' => array(
					'type' => 'VARCHAR',
					'constraint' => 20
				),
				'ndesc' => array(
					'type' => 'text',
					'null' => TRUE
				)
			);
			$this->dbforge->add_key('id', TRUE);
			$this->dbforge->add_field($fields);
			$this->dbforge->create_table('ref_persil_kelas');
		}
		else
		{
			$this->db->truncate('ref_persil_kelas');
		}

		$data = [
			['kode' => 'S-I', 'tipe' => 'BASAH', 'ndesc' => 'Persawahan Dekat dengan Pemukiman'],
			['kode' => 'S-II', 'tipe' => 'BASAH', 'ndesc' => 'Persawahan Agak Dekat dengan Pemukiman'],
			['kode' => 'S-III', 'tipe' => 'BASAH', 'ndesc' => 'Persawahan Jauh dengan Pemukiman'],
			['kode' => 'S-IV', 'tipe' => 'BASAH', 'ndesc' => 'Persawahan Sangat Jauh dengan Pemukiman'],
			['kode' => 'D-I', 'tipe' => 'KERING', 'ndesc' => 'Lahan Kering Dekat dengan Pemukiman'],
			['kode' => 'D-II', 'tipe' => 'KERING', 'ndesc' => 'Lahan Kering Agak Dekat dengan Pemukiman'],
			['kode' => 'D-III', 'tipe' => 'KERING', 'ndesc' => 'Lahan Kering Jauh dengan Pemukiman'],
			['kode' => 'D-IV', 'tipe' => 'KERING', 'ndesc' => 'Lahan Kering Sanga Jauh dengan Pemukiman'],
			];
		$this->db->insert_batch('ref_persil_kelas', $data);
	}

	private function buat_ref_persil_mutasi()
	{
		// Buat tabel ref Mutasi Persil
		if (!$this->db->table_exists('ref_persil_mutasi'))
		{
			$fields = array(
				'id' => array(
					'type' => 'TINYINT',
					'constraint' => 5,
					'unsigned' => TRUE,
					'auto_increment' => TRUE
				),
				'nama' => array(
					'type' => 'VARCHAR',
					'constraint' => 20
				),
				'ndesc' => array(
					'type' => 'text',
					'null' => TRUE
				)
			);
			$this->dbforge->add_key('id', TRUE);
			$this->dbforge->add_field($fields);
			$this->dbforge->create_table('ref_persil_mutasi');
		}
		else
		{
			$this->db->truncate('ref_persil_mutasi');
		}

		$data = [
			['nama' => 'Jual Beli', 'ndesc' => 'Didapat dari proses Jual Beli'],
			['nama' => 'Hibah', 'ndesc' => 'Didapat dari proses Hibah'],
			['nama' => 'Waris', 'ndesc' => 'Didapat dari proses Waris'],
			];
		$this->db->insert_batch('ref_persil_mutasi', $data);
	}

	private function buat_ref_persil_jenis_mutasi()
	{
		// Buat tabel Jenis Mutasi Persil
		if (!$this->db->table_exists('ref_persil_jenis_mutasi'))
		{
			$fields = array(
				'id' => array(
					'type' => 'TINYINT',
					'constraint' => 5,
					'unsigned' => TRUE,
					'auto_increment' => TRUE
				),
				'nama' => array(
					'type' => 'VARCHAR',
					'constraint' => 20
				)
			);
			$this->dbforge->add_key('id', TRUE);
			$this->dbforge->add_field($fields);
			$this->dbforge->create_table('ref_persil_jenis_mutasi');
		}
		else
		{
			$this->db->truncate('ref_persil_jenis_mutasi');
		}

		$data = [
			['nama' => 'Penambahan'],
			['nama' => 'Pemecahan'],
			];
		$this->db->insert_batch('ref_persil_jenis_mutasi', $data);
	}

	private function buat_cdesa()
	{
		// Buat tabel C-DESA
		if (!$this->db->table_exists('cdesa') )
		{
			$fields = array(
				'id' => array(
					'type' => 'INT',
					'constraint' => 5,
					'unsigned' => TRUE,
					'auto_increment' => TRUE
				),
				'nomor' => array(
					'type' => 'VARCHAR',
					'constraint' => 20,
					'unique' => TRUE
				),
				'nama_kepemilikan' => array(
					'type' => 'VARCHAR',
					'constraint' => 100,
					'unique' => TRUE
				),
				'jenis_pemilik' => array(
					'type' => 'TINYINT',
					'constraint' => 1,
					'default' => 0
				),
				'nama_pemilik_luar' => array(
					'type' => 'VARCHAR',
					'constraint' => 100,
					'null' => true,
					'default' => null
				),
				'alamat_pemilik_luar' => array(
					'type' => 'VARCHAR',
					'constraint' => 200,
					'null' => true,
					'default' => null
				)
			);
			$this->dbforge->add_key('id', TRUE);
			$this->dbforge->add_field($fields);
			$this->dbforge->add_field("created_at timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP");
			$this->dbforge->add_field("created_by int(11) NOT NULL");
			$this->dbforge->add_field("updated_at timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP");
			$this->dbforge->add_field("updated_by int(11) NOT NULL");
			$this->dbforge->create_table('cdesa');
		}
	}

	private function buat_cdesa_penduduk()
	{
		// Buat tabel C-DESA
		if (!$this->db->table_exists('cdesa_penduduk') )
		{
			$fields = array(
				'id' => array(
					'type' => 'INT',
					'constraint' => 11,
					'unsigned' => TRUE,
					'auto_increment' => TRUE
				),
				'id_cdesa' => array(
					'type' => 'INT',
					'unsigned' => TRUE,
					'constraint' => 5,
				),
				'id_pend' => array(
					'type' => 'INT',
					'constraint' => 11
				),
			);
			$this->dbforge->add_key('id', TRUE);
			$this->dbforge->add_field($fields);
			$this->dbforge->create_table('cdesa_penduduk');
			$this->db->query("ALTER TABLE `cdesa_penduduk` ADD INDEX `id_cdesa` (`id_cdesa`)");
			$this->dbforge->add_column('cdesa_penduduk', array(
	    	'CONSTRAINT `cdesa_penduduk_fk` FOREIGN KEY (`id_cdesa`) REFERENCES `cdesa` (`id`) ON DELETE CASCADE ON UPDATE CASCADE'
			));
		}
	}

	private function buat_persil()
	{
		//tambahkan kolom untuk beberapa data persil
		if (!$this->db->table_exists('persil'))
		{
			$fields = array(
				'id' => array(
					'type' => 'INT',
					'constraint' => 11,
					'unsigned' => TRUE,
					'auto_increment' => TRUE
				),
				'nomor' => array(
					'type' => 'VARCHAR',
					'constraint' => 20,
					'unique' => TRUE,
				),
				'kelas' => array(
					'type' => 'INT',
					'constraint' => 5
				),
				'id_wilayah' => array(
					'type' => 'INT',
					'constraint' => 11,
					'null' =>TRUE
				),
				'lokasi' => array(
					'type' => 'TEXT',
					'null' => TRUE
				),
				'path' => array(
					'type' => 'TEXT',
					'null' => TRUE
				)
			);
			$this->dbforge->add_key('id', TRUE);
			$this->dbforge->add_field($fields);
			$this->dbforge->create_table('persil');
		}
	}

	private function buat_mutasi_cdesa()
	{
		// Buat tabel mutasi Persil
		if (!$this->db->table_exists('mutasi_cdesa'))
		{
			$fields = array(
				'id' => array(
					'type' => 'INT',
					'constraint' => 11,
					'unsigned' => TRUE,
					'auto_increment' => TRUE
				),
				'id_cdesa_masuk' => array(
					'type' => 'INT',
					'unsigned' => TRUE,
					'constraint' => 5,
					'null' => TRUE
				),
				'id_cdesa_keluar' => array(
					'type' => 'INT',
					'unsigned' => TRUE,
					'constraint' => 5,
					'null' => TRUE
				),
				'jenis_mutasi' => array(
					'type' => 'TINYINT',
					'constraint' => 2,
					'null' => TRUe
				),
				'tanggal_mutasi' => array(
					'type' => 'DATE',
					'null' => TRUE
				),
				'keterangan' => array(
					'type' => 'TEXT',
					'null' => TRUE
				),
				'id_persil' => array(
					'type' => 'INT',
					'constraint' => 11
				),
				'no_bidang_persil' => array(
					'type' => 'TINYINT',
					'constraint' => 3
				),
				'luas' => array(
					'type' => 'decimal',
					'constraint' => 7,
					'null' => TRUE
				),
				'jenis_bidang_persil' => array(
					'type' => 'INT',
					'constraint' => 11
				),
				'peruntukan' => array(
					'type' => 'INT',
					'constraint' => 11
				),
				'no_objek_pajak' => array(
					'type' => 'VARCHAR',
					'constraint' => 30,
					'null' => TRUE
				),
				'no_sppt_pbb' => array(
					'type' => 'VARCHAR',
					'constraint' => 30,
					'null' => TRUE
				),
				'path' => array(
					'type' => 'TEXT',
					'null' => TRUE
				)
			);
			$this->dbforge->add_key('id', TRUE);
			$this->dbforge->add_field($fields);
			$this->dbforge->create_table('mutasi_cdesa');
			$this->dbforge->add_column('mutasi_cdesa', array(
	    	'CONSTRAINT `cdesa_mutasi_fk` FOREIGN KEY (`id_cdesa_masuk`) REFERENCES `cdesa` (`id`) ON DELETE CASCADE ON UPDATE CASCADE'
			));
		}
	}

}
