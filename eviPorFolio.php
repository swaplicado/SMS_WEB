<?php 
session_start();
echo "<title>";
echo "Evidencias | Folio";
echo "</title>";
include 'header.php';
require 'database.php';
require 'functionsphp.php';
canAccess($_SESSION['loggedin'], 'eviPorFolio.php', $_SESSION['rol']);
	echo "<div class=\"container-fluid\">";

	include 'menu.php';
	include 'logo.php';
	echo "<div class='row'>";
		echo "<div class='col-md-1'></div>";
		echo "<div class='col-md-10 text-right'>";
		echo "<br>";
			echo "<form action='eviPorFolio.php' method='POST' >";
				echo "<p><h2> Filtrar por fecha</h2></p>";
				echo "<input type='text' class='myBtnInputTex' name='daterange' value='' />";
				echo "<input type='submit' class='btn btn-success' value='Aplicar Filtro'>";
			echo "</form>";
		echo "</div>";

		echo "<div class='col-md-1'></div>";
	echo "</div>";
	echo "<div class=\"row\">";
		echo "<div class=\"col-xs-1\">";
		echo "</div>";
		echo "<div class=\"col-xs-10\">";
	if (isset($_REQUEST['daterange'])){
			echo "<div class='text-right'>";
				echo "<br>FILTRO APLICADO:";
				echo "<br>Inicio: <b>" . $startDate = substr($_REQUEST['daterange'],0,10) . "</b>";
				echo "<br>Fin: <b>" . $endDate = substr($_REQUEST['daterange'],-10) . "</b>";
					echo "<form action='eviPorFolio.php' method='POST' >";
						echo "<input type='submit' class='btn btn-danger' value='Eliminar Filtro'>";
					echo "</form>";
			echo "</div>";
			$sql = "
			SELECT
				E.id_evidence,
				SH.number,
				SHR.delivery_number,
				E.file_location,
				E.file_name,
				E.file_location,
				E.file_name,
				E.b_accept,
				SHP.name,
				SH.shipt_date,
				E.ts_usr_upload,
				E.ts_usr_accept,
				E.ts_usr_upd
			FROM
				S_EVIDENCE AS E
				INNER JOIN S_SHIPT AS SH ON E.fk_ship_ship = SH.id_shipt
				INNER JOIN S_SHIPT_ROW AS SHR ON E.fk_ship_row = SHR.id_row
				AND SHR.ID_SHIPT = SH.ID_SHIPT
				INNER JOIN SU_SHIPPER AS SHP ON SHP.id_shipper = SH.fk_shipper
			WHERE
				NOT E.b_del AND (SH.fk_shipt_st=11 OR SH.fk_shipt_st=12) AND SH.shipt_date BETWEEN '$startDate' AND '$endDate'
			GROUP BY E.id_evidence;";
		}
		else{
			$sql = "
			SELECT
				E.id_evidence,
				SH.number,
				SHR.delivery_number,
				E.file_location,
				E.file_name,
				E.file_location,
				E.file_name,
				E.b_accept,
				SHP.name,
				SH.shipt_date,
				E.ts_usr_upload,
				E.ts_usr_accept,
				E.ts_usr_upd
			FROM
				S_EVIDENCE AS E
				INNER JOIN S_SHIPT AS SH ON E.fk_ship_ship = SH.id_shipt
				INNER JOIN S_SHIPT_ROW AS SHR ON E.fk_ship_row = SHR.id_row
				AND SHR.ID_SHIPT = SH.ID_SHIPT
				INNER JOIN SU_SHIPPER AS SHP ON SHP.id_shipper = SH.fk_shipper
			WHERE
				NOT E.b_del AND (SH.fk_shipt_st=11 OR SH.fk_shipt_st=12)
			GROUP BY E.id_evidence;";
		}
		$result = $conexion->query($sql);
	echo "<div class='myScrollH'>";
		echo "<div class='text-right' onclick=\"$('#myInput01').focus();\">
				<label>
				<input type='text' class='myBtnInputTex' id='myInput01' onkeyup='filterTable(1)' placeholder='Buscar por remision...' title='Buscar remision'>
				<span class='glyphicon glyphicon-search'>
				</span>
				</label> 
			</div>";
			echo " <table class='table table-hover table-condensed myTable' id='myTable'>";
					echo " <thead>";
						echo "<tr>";
							echo "<th>FOLIO</th>";
							echo "<th>REMISION</th>";
							echo "<th>IMAGEN</th>";
							echo "<th>ESTATUS</th>";
							echo "<th>TRANSPORTISTA</th>";
							echo "<th>FECHA_EMBARQUE</th>";
							echo "<th>FECHA_ENTREGA</th>";
							echo "<th>FECHA_SUBIDA</th>";
							echo "<th>FECHA_REVISION</th>";
						echo " </tr>";
					echo " </thead>";
					echo "<tbody>";
					$cont=0;
				while($row = $result->fetch_array(MYSQLI_ASSOC)) {
					$cont++;
						echo "<tr>";
							?>
							<form enctype="multipart/form-data" action="changeStatus.php" method="post" onSubmit="if(!confirm('¿Seguro que deseas cambiar el estatus?')){return false;}">
								<?php
								echo "<input type='label' name='evidence' class='hidden' value='" . $row['id_evidence'] ."'>";
								echo "<td>" . $row['number'] . "</td>";
								echo "<td>" . $row['delivery_number'] . "</td>";
								echo "<td><a id='single_image' href='" . $row['file_location'] . $row['file_name'] . "'>
									<img class='img-rounded' src='" . $row['file_location'] . $row['file_name'] . "' style='width:30px;height:30px;border:2px solid transparent;border-color:white'/></a></td>";
								echo "<td>";
								if($row['b_accept'] == false){
									echo "<input type='submit' name='submit' class='btn btn-primary' value='Pendiente'/>";
								}else {
									echo "<label class='label label-warning'>Aprobado</label>";
								}
								echo "</td>";
								echo "<td>" . $row['name'] . "</td>";
								echo "<td>" . $row['shipt_date'] . "</td>";
								echo "<td>" . $row['ts_usr_upload'] . "</td>";
								echo "<td>" . $row['ts_usr_accept'] . "</td>";
								echo "<td>" . $row['ts_usr_upd'] . "</td>";
								?>
							</form>
							<?php
						echo " </tr>";
				}

				echo "</tbody>";
				echo "</table>";
			echo "</div>";
		echo "</div>";
		echo "<div class=\"col-xs-1\">";
		echo "</div>";
		btnBack('eviFolios.php');
	echo "</div>";
	 include 'footer.php';
echo "</div>";
?>

<script type="text/javascript">
	$(function() {
		$('input[name="daterange"]').daterangepicker({
			"ranges": {
			"Hoy": [
				new Date(),
				new Date(),
			],
			"Ultims 7 dias": [
				moment().subtract('days', 7), moment(),
				new Date(),
			],
			"Ultimos 30 dias": [
				moment().subtract('months', 1), moment(),
				new Date(),
			],
			"Ultimo año": [
				moment().subtract('years', 1), moment(),
				new Date(),
			],
			},
			"alwaysShowCalendars": false,
			"startDate": new Date(),
			"endDate": new Date(),
			"opens": "left",

			locale: {
				format: 'YYYY-MM-DD'
			},
		});
	});
	function filterTable(col) {
		var input, filter, table, tr, td, i;
		input = document.getElementById("myInput01");
		filter = input.value.toUpperCase();
		table = document.getElementById("myTable");
		tr = table.getElementsByTagName("tr");
		for (i = 0; i < tr.length; i++) {
			td = tr[i].getElementsByTagName("td")[col];
			if (td) {
				if (td.innerHTML.toUpperCase().indexOf(filter) > -1) {
					tr[i].style.display = "";
				} else {
					tr[i].style.display = "none";
				}
			}       
		}
	}
</script>