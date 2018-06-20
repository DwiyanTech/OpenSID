<script src="<?php echo base_url('assets/js/sweetalert.min.js') ?>"></script>

<style>
	#footer {
		color: #f83535;
		text-shadow: 1px 1px 0.5px #444;
		padding: 8px;
		text-align: center;
		position: relative;
		bottom: 0px;
		width: 100%;
		background: #eaa852;
		height: 34px;
		position: fixed;
	}

</style>

<div id="row">
<div class="col-lg-2">
	<div class="panel panel-default">
		<div class="panel-heading">Menu</div>
		<div class="panel-body">
			<?php
				$data['data'] = 2;
				$this->load->view('inventaris/asset/menu_kiri.php',$data);
			?>
		</div>
	</div>
</div>
<div class="col-lg-10">
	<div id="container">
		<form id="mainform" name="mainform" action="" method="post">
		  <div class="ui-layout-north panel">
				<div class="panel panel-default">
					<div class="panel-heading">
						Mutasi Inventaris Asset Tetap Lainnya
					</div>
					<div class="panel-body">
					</div>
					<div class="panel-body">	
					<table id="example" class="stripe cell-border table" class="grid">
						<thead style="background-color:#f9f9f9;" >
							<tr>
									<th class="text-center">No</th>
									<th class="text-center">Nama Barang</th>
									<th class="text-center">Kode Barang</th>
									<th class="text-center">Tahun Pengadaan</th>
									<th class="text-center">Tanggal Mutasi</th>
									<th class="text-center">Jenis Mutasi</th>
									<th class="text-center" width="300px">Keterangan</th>
									<th class="text-center" width="100px">Aksi</th>
							</tr>
						</thead>
						<tbody>
						<?php
								foreach($main as $data){
							?>
							<tr>
								<td></td>
								<td><?php echo $data->nama_barang;?></td>
								<td><?php echo $data->kode_barang;?></td>
								<td><?php echo $data->tahun_pengadaan;?></td>
								<td><?php echo date('d M Y',strtotime($data->tahun_mutasi));?></td>
								<td><?php echo $data->jenis_mutasi;?></td>
								<td><?php echo $data->keterangan;?></td>
								<td>
									<div class="btn-group" role="group" aria-label="...">
										<a href="<?php echo base_url('index.php/inventaris_asset/view_mutasi/'.$data->id); ?>" title="Lihat Data" type="button" class="btn btn-default btn-sm"><i class="fa fa-eye"></i></a>
										<a href="<?php echo base_url('index.php/inventaris_asset/edit_mutasi/'.$data->id); ?>" title="Edit Data"  type="button" class="btn btn-default btn-sm"><i class="fa fa-edit"></i> </a> 
										<button href="" onclick="deleteItem(<?php echo $data->id; ?>)" title="Hapus Data" type="button" class="btn btn-default btn-sm"><i class="fa fa-trash"></i></button>
									</div>
								</td>
							</tr>
							<?php
								}
							?>
						</tbody>
					</table>
					</div>
					</div>
			</form>
	</div>
</div>	
	
<script  TYPE='text/javascript'>
	$(document).ready(function() {
		
		var t = $('#example').DataTable( {
			scrollY					: '100vh',
			scrollCollapse			: true,
			autoWidth				: true,
        	"columnDefs": [ {
            	"searchable": false,
            	"orderable": false,
            	"targets": 0
        	} ],
        	"order": [[ 1, 'asc' ]]
    	} );
		t.on( 'order.dt search.dt', function () {
			t.column(0, {search:'applied', order:'applied'}).nodes().each( function (cell, i) {
				cell.innerHTML = i+1;
			} );
		} ).draw();

	} );


	function deleteItem($id){
		swal({
				title: "Apakah Anda Yakin?",
				text: "Setelah dihapus, Data hanya dapat dipulihkan di database!!",
				icon: "warning",
				buttons: true,
				dangerMode: true,
			})
			.then((willDelete) => {
				if (willDelete) {
					swal("Data berhasil dihapus!", {
					icon: "success",
					});
					
					window.location = "../api_inventaris_asset/delete_mutasi/" + $id;
				} else {
					swal("Data tidak berhasil dihapus!");
				}
			});

	}
</script>