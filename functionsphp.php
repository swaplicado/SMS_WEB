<?php

###AUTOR: ALPHALAPZ AND DEINIS AND GIL ENOC
include 'const.php';
	/*
	 * Redirect according to the type of user.
	 */
	function checkRol($rol){
		switch($rol){
			case ROL_ADMIN:
				// echo "Rol admin <br>";
				redirectPHP('panel-control.php');
				break;
			case ROL_CREDIT:
				// echo "Rol Crédito y cobranza<br>";
				redirectPHP('indexCredito.php');
				break;
			case ROL_TRANS:
				// echo "Rol Transportista <br>";
				redirectPHP('indexTransportista.php');
				break;
			default:
				break;
			}
	}
	
	/*
	 *	Check the value of the initial row for the queries
	 */
	function pagerStartRow(){
		if (!isset($_GET['startrow']) or !is_numeric($_GET['startrow'])) {
		  return DEF_START_ROW_VALUE;
		} else {
		  return (int)$_GET['startrow'];
		}
	}
	
	/*
	 *	Check the value of the range values for the paginate.
	 */
	function pagerNumOfRows(){
		if (!isset($_GET['range']) or !is_numeric($_GET['range'])) {
		  return DEF_RANGE_VALUE;
		} else {
		  return (int)$_GET['range'];
		}
	}
	
	/*
	 *	Print all images of a specific directory, include this function the fancyBox JS
	 *	$dir = myDir/subdir/
	 */
	function printAllImg($dir){
		// $directory = "./"; #Directorio raíz
		$directory = "./$dir"; 

		$images = glob($directory . "*.{jpg,png,gif}", GLOB_BRACE);

		foreach($images as $image)
		{
			?>
			<form action="deleteFile.php" method="post" onSubmit="if(!confirm('¿Seguro que deseas eliminar el archivo?')){return false;}"> 
				<a id="single-image" href="<?php echo $image; ?>"> <img style='width:50px;height:50px;' src="<?php echo $image; ?>"></a>
				<input type="text" class="hidden" name="MyFile" id="MyFile" value="<?php echo $image; ?>"/>
				<input type="submit" name='submit' id="btn" name="btn" class='btn btn-danger' value='ELIMINAR'/>
			</form>
			<?php 
		}
		
	}
	
	function btnBack($url){
		echo "<br>";
		echo "<div class='row'>";
		echo "<div class='col-xs-4'>";
		echo "</div>";
		echo "<div class='col-xs-4'>";
			echo "<div class='text-right'>";
				echo "<form action='$url' metod='POST'>";
			foreach($_REQUEST as $req){
				echo "<input type='text' class='hidden' value='" . $req . "'/>";
			}
				echo "<button type='submit' onclick=\"window.location='$url';\"; class='btn btn-danger btn-lg'><span class='glyphicon glyphicon-triangle-left'></span>&nbsp;Volver";
			echo "</div>";
		echo "</div>";
		echo "<div class='col-xs-4'>";
		echo "</div>";
		echo "</div>";
	}
	
	/*
	 *	Delete the file
	 *	$full_path = Full URL and file name including the extension of the file
	 *	$dir = the path of the file to delete but excluding the name of the file and its extension
	 *	$file_name = The name of the file including its extension.
	 */
	function deleteFile($full_path, $dir, $file_name){
		if (file_exists($full_path)){
			if (!unlink($full_path)){
				echo ("Error borrando: $full_path");
			}
			else {
				echo ("Se borro: $full_path");
				require 'database.php';
				$sql="UPDATE S_EVIDENCE as EVI SET EVI.b_del = 1 WHERE EVI.file_name='" . $file_name . "' AND EVI.file_location='" . $dir . "';";
				$result = $conexion->query($sql);
				echo $sql;
			}
		} else {
			echo "EL ARCHIVO $full_path NO EXISTE!";			
		}
	}

	/*
	 *	redirect to the URL
	 *	$url = the url to redirect the current page.
	 */
	function redirectPHP($url){
		?>
			<script>
			window.location.replace('<?php echo $url;?>');
			</script>
		<?php
		header('Location:' . $url);
	}

	/*
	 *	Verify if the current user can access to the current view.
	 */
	function canAccess($Session, $url, $rol){
		if ($Session == 1) {
			$Session = true;
		}
		//Check if can view the views
		if (isset($Session) && $Session == true) {
			if (!canView($url, $rol)){
				echo "DEBE REDIRECCIONAR!";
				redirectPHP('noPermission.php');
			}
		}
		else{
			redirectPHP('noLogin.php');
		}
	}

	/*
	 * Verify in the file urls.json if the current user can view the requested view. 
	 */
	function canView($url, $rol_Session){
		$string = file_get_contents("urls.json");
		$json_a = json_decode($string, true);

		$encontrado = false;

		foreach($json_a as $item){
			foreach ($item as $key){
				if(is_array($key)) {
					foreach ($key as $val){
						if ($encontrado && $val == $rol_Session && $item['url'] == $url){
							return true;
						}
					}
				}
				else{
					if ($key == $url){
						$encontrado = true;
					}
				}
			}
		}	
		return false;
	}

	/*
	 *	Apply the date filter for carrier view
	 *	$type = The button pressed
	 *	$starDate = The initial date range
	 *	$endDate = The final date range
	 *	$type = 1 ::::: Evidence for uploading
	 *  $type = 2 ::::: Evidence for accepting
	 *  $type = 3 ::::: Accepted evidence
	 *  $type = 4 ::::: All the evidences
	 */
	function applyFiltersTransDate($type, $starDate, $endDate){
		$sql = "
		SELECT
			SHR.ID_ROW as REMISION,
			SH.ID_SHIPT AS FOLIO_EMBARQUE,
			SHR.delivery_number as Remision,
			sh.web_key as web_key,
			SH.NUMBER AS FOLIO,
			SH.DRIVER_NAME AS NOMBRE_CHOFER,
			SH.TS_USR_RELEASE AS FECHA_LIBERACION,
			SH.SHIPT_DATE AS FECHA_CREACION,
			
			SHS.NAME AS ESTATUS,
			SHP.NAME AS FLETE,
			sum(SHR.ORDERS) AS N_ORDENES,
			SH.m2 AS M2,			
			SH.kg AS KILOGRAMOS
		FROM S_SHIPT AS SH
			INNER JOIN SS_SHIPT_ST AS SHS ON SHS.ID_SHIPT_ST = SH.FK_SHIPT_ST
			INNER JOIN SU_SHIPT_TP AS SHP ON SHP.ID_SHIPT_TP = SH.FK_SHIPT_TP
			INNER JOIN SU_CARGO_TP AS CA ON CA.ID_CARGO_TP = SH.FK_CARGO_TP
			INNER JOIN SU_HANDG_TP AS HA ON HA.ID_HANDG_TP = SH.FK_HANDG_TP
			INNER JOIN SU_VEHIC_TP AS VE ON VE.ID_VEHIC_TP = SH.FK_VEHIC_TP
			INNER JOIN S_SHIPT_ROW AS SHR ON SHR.ID_SHIPT = SH.ID_SHIPT
			INNER JOIN AU_CUS AS CU ON CU.ID_CUS = SHR.FK_CUSTOMER
			INNER JOIN SU_DESTIN AS DE ON DE.ID_DESTIN = SHR.FK_DESTIN
			INNER JOIN SU_SHIPPER AS SHIP ON SHIP.ID_SHIPPER = SH.FK_SHIPPER ";
		switch ($type) {
			case 1:
			echo "<div class='hidden-md-up'>";
			echo "<h1 class='text-center'>Evidencias por subir:</h1><br>
					</div>";
			echo "<div class='hidden-md-down'>";
			echo "<h3 class='text-center'>Evidencias por subir:</h3><br>
					</div>";
				$sql = $sql . "
				WHERE
					SHIP.fk_usr = " . $_SESSION['user_id'] . " AND SHS.id_shipt_st=" . S_ST_LIBERADO . " AND SH.shipt_date BETWEEN '$starDate' AND '$endDate'
				GROUP BY SH.ID_SHIPT;";
				break;
			case 2:
			echo "<h1 class='text-center'>Evidencias por aceptar:</h1><br>";
				$sql = $sql . "
				WHERE
					SHIP.fk_usr = " . $_SESSION['user_id'] . " AND SHS.id_shipt_st=" . S_ST_POR_ACEPTAR . " AND SH.shipt_date BETWEEN '$starDate' AND '$endDate'
				GROUP BY SH.ID_SHIPT;";
				break;
			case 3:
			echo "<h1 class='text-center'>Evidencias aceptadas:</h1><br>";
				$sql = $sql . "
				WHERE
					SHIP.fk_usr = " . $_SESSION['user_id'] . " AND SHS.id_shipt_st=" . S_ST_ACEPTADO . " AND SH.shipt_date BETWEEN '$starDate' AND '$endDate'
				GROUP BY SH.ID_SHIPT;";
				break;
			case 4:
			echo "<h1 class='text-center'>Todas las evidencias:</h1><br>";
				$sql = $sql . "
				WHERE
					SHIP.fk_usr = " . $_SESSION['user_id'] . " AND (SHS.id_shipt_st=" . S_ST_ACEPTADO . " OR SHS.id_shipt_st=" . S_ST_POR_ACEPTAR . " OR SHS.id_shipt_st=" . S_ST_LIBERADO . ") AND SH.shipt_date BETWEEN '$starDate' AND '$endDate'
				GROUP BY SH.ID_SHIPT;";
				break;
			default:
			break;
		}
		return $sql;
	}

	/*
	 *	Show the data using the selected filter view carrier
	 *	$type = The button pressed
	 *  $type = 1 ::::: Evidence for uploading
	 *  $type = 2 ::::: Evidence for accepting
	 *  $type = 3 ::::: Accepted evidence
	 *  $type = 4 ::::: All the evidences
	 */
	function applyFiltersTrans($type){
		$sql = "
		SELECT
			SHR.ID_ROW as REMISION,
			SH.ID_SHIPT AS FOLIO_EMBARQUE,
			SHR.delivery_number as Remision,
			sh.web_key as web_key,
			SH.NUMBER AS FOLIO,
			SH.SHIPT_DATE AS FECHA_CREACION,
			SH.TS_USR_RELEASE AS FECHA_LIBERACION,
			SH.DRIVER_NAME AS NOMBRE_CHOFER,
			SHS.NAME AS ESTATUS,
			SHP.NAME AS FLETE,
			sum(SHR.ORDERS) AS N_ORDENES,
			SH.m2 AS M2,			
			SH.kg AS KILOGRAMOS
		FROM S_SHIPT AS SH
			INNER JOIN SS_SHIPT_ST AS SHS ON SHS.ID_SHIPT_ST = SH.FK_SHIPT_ST
			INNER JOIN SU_SHIPT_TP AS SHP ON SHP.ID_SHIPT_TP = SH.FK_SHIPT_TP
			INNER JOIN SU_CARGO_TP AS CA ON CA.ID_CARGO_TP = SH.FK_CARGO_TP
			INNER JOIN SU_HANDG_TP AS HA ON HA.ID_HANDG_TP = SH.FK_HANDG_TP
			INNER JOIN SU_VEHIC_TP AS VE ON VE.ID_VEHIC_TP = SH.FK_VEHIC_TP
			INNER JOIN S_SHIPT_ROW AS SHR ON SHR.ID_SHIPT = SH.ID_SHIPT
			INNER JOIN AU_CUS AS CU ON CU.ID_CUS = SHR.FK_CUSTOMER
			INNER JOIN SU_DESTIN AS DE ON DE.ID_DESTIN = SHR.FK_DESTIN
			INNER JOIN SU_SHIPPER AS SHIP ON SHIP.ID_SHIPPER = SH.FK_SHIPPER ";
		switch ($type) {
			case 1:
			echo "<div class='hidden-xs'>";
			echo "<h1 class='text-center'>Evidencias por subir:</h1><br>
					</div>";
			echo "<div class='visible-xs'>";
			echo "<h3 class='text-center'>Evidencias por subir:</h3><br>
					</div>";
				$sql = $sql . "
				WHERE
					SHIP.fk_usr = " . $_SESSION['user_id'] . " AND SHS.id_shipt_st=" . S_ST_LIBERADO . " GROUP BY SH.ID_SHIPT;";
				break;
			case 2:
			echo "<h1 class='text-center'>Evidencias por aceptar:</h1><br>";
				$sql = $sql . "
				WHERE
					SHIP.fk_usr = " . $_SESSION['user_id'] . " AND SHS.id_shipt_st=" . S_ST_POR_ACEPTAR . " GROUP BY SH.ID_SHIPT;";
				break;
			case 3:
			echo "<h1 class='text-center'>Evidencias aceptadas:</h1><br>";
				$sql = $sql . "
				WHERE
					SHIP.fk_usr = " . $_SESSION['user_id'] . " AND SHS.id_shipt_st=" . S_ST_ACEPTADO . " GROUP BY SH.ID_SHIPT;";
				break;
			case 4:
			echo "<h1 class='text-center'>Todas las evidencias:</h1><br>";
				$sql = $sql . "
				WHERE
					SHIP.fk_usr = " . $_SESSION['user_id'] . " AND (SHS.id_shipt_st=" . S_ST_ACEPTADO . " OR SHS.id_shipt_st=" . S_ST_POR_ACEPTAR . " OR SHS.id_shipt_st=" . S_ST_LIBERADO . ") GROUP BY SH.ID_SHIPT;";
				break;
			default:
			break;
		}
		return $sql;
	}

	/*
	 *	Apply the date filter for Credit and collection view
	 *	$type = The button pressed
	 *	$starDate = The initial date range
	 *	$endDate = The final date range
	 *	$type = 1 ::::: Evidence for uploading
	 *  $type = 2 ::::: Evidence for accepting
	 *  $type = 3 ::::: Accepted evidence
	 *  $type = 4 ::::: All the evidences
	 */
	function applyFiltersCoDate($type, $starDate, $endDate){

		$starDate = str_replace('-', ':', $starDate);
		$endDate = str_replace('-', ':', $endDate);
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
				INNER JOIN SU_SHIPPER AS SHP ON SHP.id_shipper = SH.fk_shipper ";
		switch ($type) {
			case 1:
			echo "<h1 class='text-center'>Evidencias por aprobar:</h1><br>";
				$sql = $sql . "
				WHERE
					NOT E.b_del AND SH.fk_shipt_st=" . S_ST_POR_ACEPTAR . " AND SH.shipt_date BETWEEN '$starDate' AND '$endDate'
				GROUP BY E.id_evidence;";
				break;
			case 2:
			echo "<h1 class='text-center'>Evidencias aprobadas:</h1><br>";
				$sql = $sql . "
				WHERE
					NOT E.b_del AND SH.fk_shipt_st=" . S_ST_ACEPTADO . " AND SH.shipt_date BETWEEN '$starDate' AND '$endDate'
				GROUP BY E.id_evidence;";
				break;
			case 3:
			echo "<h1 class='text-center'>Todas las evidencias:</h1><br>";
				$sql = $sql . "
				WHERE
					NOT E.b_del AND (SH.fk_shipt_st=" . S_ST_POR_ACEPTAR . " OR SH.fk_shipt_st=" . S_ST_ACEPTADO . ") AND SH.shipt_date BETWEEN '$starDate' AND '$endDate'
				GROUP BY E.id_evidence ";
				break;
			default:
				$sql = $sql . "GROUP BY E.id_evidence;";
				break;
		}
		$sql = $sql . "ORDER BY delivery_number";
		return $sql;
	}

	/*
	 *	Show the data using the selected filter view Credit and collection
	 *	$type = The button pressed
	 *  $type = 1 ::::: Evidence for uploading
	 *  $type = 2 ::::: Evidence for accepting
	 *  $type = 3 ::::: Accepted evidence
	 *  $type = 4 ::::: All the evidences
	 */
	function applyFiltersCo($type){
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
				INNER JOIN SU_SHIPPER AS SHP ON SHP.id_shipper = SH.fk_shipper ";
		switch ($type) {
			case 1:
			echo "<h1 class='text-center'>Evidencias por aprobar:</h1><br>";
				$sql = $sql . "
				WHERE
					NOT E.b_del AND SH.fk_shipt_st=" . S_ST_POR_ACEPTAR . "
				GROUP BY E.id_evidence ";
				break;
			case 2:
			echo "<h1 class='text-center'>Evidencias aprobadas:</h1><br>";
				$sql = $sql . "
				WHERE
					NOT E.b_del AND SH.fk_shipt_st=" . S_ST_ACEPTADO . "
				GROUP BY E.id_evidence ";
				break;
			case 3:
			echo "<h1 class='text-center'>Todas las evidencias:</h1><br>";
				$sql = $sql . "
				WHERE
					NOT E.b_del AND (SH.fk_shipt_st=" . S_ST_POR_ACEPTAR . " OR SH.fk_shipt_st=" . S_ST_ACEPTADO . ")
				GROUP BY E.id_evidence ";
				break;
			default:
				break;
		}
		$sql = $sql . "ORDER BY delivery_number";
		return $sql;
	}

	/*
	 *	Print table from a query
	 */
	function printTable($result){
		$info_field = $result->fetch_fields();
		
		echo " <table class='table table-hover myTable'>";
			echo " <thead>";
				echo "<tr>";
			$cont = 0;
		foreach ($info_field as $valor) {
			$cont++;
			echo "<th>" . $valor->name . "</th>";
		}
		echo " </tr>";
		echo " </thead>";
		echo "<tbody>";
		while($row = $result->fetch_array(MYSQLI_NUM)) {
			echo "<tr>";
			for ($i = 0; $i < $cont; $i++){
				echo "<td>" . $row[$i] . "</td>";
			}
			echo "</tr>";
		}
		echo "</table>";
	}

	//$buttons must be and array of arrays
	//i.e: printableB
	// $buttons = array(
	// 		array('Texto del boton', 'btn btn-warning') 
	// );
	// $form = array('test.php','¿Seguro?');
	function printTableB($result, $buttons, $form){
		$aNames = array();
		$info_field = $result->fetch_fields();
		echo "<div class='myScrollH'>";
		echo " <table class='table table-hover table-condensed myTable'>";
			echo " <thead>";
				echo "<tr>";
			$cont = 0;			
		foreach ($info_field as $valor) {
			$cont++;
			if ($cont <= 4){
				echo "<th class='hidden'>" . $valor->name . "</th>";
			}else{
				echo "<th>" . $valor->name . "</th>";
			}
				array_push($aNames, $valor->name);
		}
		echo "<th>Acciones</th>";
		echo " </tr>";
		echo " </thead>";
		echo "<tbody>";
		while($row = $result->fetch_array(MYSQLI_NUM)) {
			echo "<tr>";
				$text = $form[1] == '' ? ">" : "onsubmit=\"if(!confirm('Ver remisiones para el embarque $row[1]?')){return false;}\" >";
				echo "<form class='form-control' action='$form[0]' method='POST' " . $text;
				$names = array_reverse($aNames);
			for ($i = 0; $i < $cont; $i++){
				if ($i < 4){
					echo "<input type='text' class='hidden' name='" . array_pop($names) . "' value='$row[$i]'/>";
				} else{
				echo "<td>";
					echo $row[$i];
					echo "<input type='text' class='hidden' name='" . array_pop($names) . "' value='$row[$i]'/>";
				echo "</td>";
				}
			}
			foreach($buttons as $btn){
				echo "<td><input type='submit' value='$btn[0]' class='$btn[1]'/></td>";
			}
				echo "</form>";
			echo "</tr>";
		}
		echo "</tbody>";
		echo "</table>";
		echo "</div>";
	}
	
	//	$buttons must be and array of arrays
	//	i.e: printableB
	//		$buttons = array(
	//			array('Texto del boton', 'btn btn-warning') 
	//		);
	//		$form = array('test.php','¿Seguro?');
	//		$hidden = Integer for hide the columns (initial column = 1) 
	##	IF YOU DONT NEED TO HIDE ELEMENTS, CAN USE THE printTableB FUNCTION OR SET $HIDDEN TO ZERO
	function printTableC($result, $buttons, $form, $hidden, $index){
		$aNames = array();
		$formAction = $hidden + 2;
		$info_field = $result->fetch_fields();
		echo " <table id='myTable' class='table table-hover table-condensed myTable tablesorter'>";
			echo " <thead>";
				echo "<tr>";
			$cont = 0;			

		foreach ($info_field as $valor) {
			$cont++;
			if ($cont <= $hidden){
				echo "<th class='hidden'>" . $valor->name . "</th>";
			}else{
				echo "<th>" . $valor->name . "</th>";
			}
			if ($cont == $formAction){
				echo "<th>ACCION</th>";
			}
				array_push($aNames, $valor->name);
		}
		echo " </tr>";
		echo " </thead>";
		echo "<tbody>";

		while($row = $result->fetch_array(MYSQLI_NUM)) {
			echo "<tr>";
				// $text = $row[1] == '' ? ">" : "onsubmit=\"if(!confirm('Ver remisiones para el embarque $row[1]?')){return false;}\" >";
				$text = ">";
				echo "<form class='form-control' action='$form[0]' method='POST' " . $text;
				$names = array_reverse($aNames);

			for ($i = 0; $i < $cont; $i++){

				##ACTION FORM ADDED HERE
				if ($i==$formAction){
					foreach($buttons as $btn){
						echo "<td><button type='submit' class='$btn[1]'>$btn[2]</button></td>";
					}
				}
				## FORMAT FIELD WHEN IS NUMBER (FLOAT)
				// TAKE the $index value for
				if (in_array($i,$index, true)){
					$row[$i] = strcmp('double',getType($row[$i])) ? number_format($row[$i], 2, ".", ",") : $row[$i];
				} 

				if ($i < $hidden){
					echo "<input type='text' class='hidden' name='" . array_pop($names) . "' value='$row[$i]'/>";
				} else{
				echo "<td>";
					echo $row[$i];
					echo "<input type='text' class='hidden' name='" . array_pop($names) . "' value='$row[$i]'/>";
				echo "</td>";
				}

			}
				echo "</form>";
			echo "</tr>";
		}

		echo "</tbody>";
		echo "</table>";

	}

	/*
	 *Validate if all remissions have at least one evidence
	 */
	 ###############################################
	 ## $folio = id_shipt of the table S_SHIPT_ROW #
	 ###############################################
	function validateIfAllRemissionsHadEvidence($folio){
		require 'database.php';
		$sql = "
			SELECT id_row 
			FROM S_SHIPT_ROW 
			WHERE id_shipt =" . $folio ." 
			GROUP BY id_row;";
		$result = $conexion->query($sql);
		$sql2 = "SELECT fk_ship_row FROM S_EVIDENCE WHERE NOT b_del;";
		$result2 = $conexion->query($sql2);
		$save = $result2;
		$completo = true;
		$evidenceArray = array();
		while ($row2 = $result2->fetch_array(MYSQLI_NUM)){
			array_push($evidenceArray,$row2[0]);
		}
		while ($row = $result->fetch_array(MYSQLI_NUM)){
			$found = false;
			if(in_array($row[0],$evidenceArray)){
				$found = true;
			}
			else{
				echo "<br> El folio # : " . $row[0] . " esta pendiente de que se suban evidencias :(";
				$completo = false;
				break;
			}
		}
		if($completo){
			$sql = "UPDATE S_SHIPT SET fk_shipt_st = " . S_ST_POR_ACEPTAR . " WHERE id_shipt=$folio";
			$result = $conexion->query($sql);
			echo "<br>EL FOLIO ESTA COMPLETO,<br> TODAS LAS REMISIONES POSEEN AL MENOS UNA EVIDENCIA.<br>Favor de esperar respuesta por parte de crédito y cobranza.";
		}
		else{
			$sql = "UPDATE S_SHIPT SET fk_shipt_st = " . S_ST_LIBERADO . " WHERE id_shipt=$folio";
			$result = $conexion->query($sql);
		}
	}
	/*
	 *	This function is called when some evidence status change or if some evidence is deleted
	 */
	function ifNecesaryChangeStatus($del_id) {
		require 'database.php';
		$sql = "
		SELECT SH.id_shipt as folio, 
			SHR.id_row as remision, 
			SH.fk_shipt_st as status
		FROM S_EVIDENCE AS EVI
			INNER JOIN S_SHIPT_ROW AS SHR ON EVI.fk_ship_row = SHR.id_row
			INNER JOIN S_SHIPT AS SH ON EVI.fk_ship_ship = SH.id_shipt
		WHERE EVI.id_evidence=$del_id 
			
			AND NOT SH.b_del
		GROUP BY EVI.id_evidence;";

		$result = $conexion->query($sql);

		$row = $result->fetch_array(MYSQLI_NUM);
		$folio = $row[0];
		$remision = $row[1];
		$status = $row[2];

		$sql = "
		SELECT b_accept 
		FROM S_EVIDENCE 
		WHERE NOT b_del 
			AND fk_ship_ship=$folio;";

		$result = $conexion->query($sql);
		$evidenceArray = array();
		while ($row2 = $result->fetch_array(MYSQLI_NUM)){
			array_push($evidenceArray,$row2[0]);
		}

		if(!in_array(0, $evidenceArray) && $status == S_ST_POR_ACEPTAR){
			$completo = false;
			echo "<br>Folio completado.";
			$sql = "UPDATE S_SHIPT SET fk_shipt_st = " . S_ST_ACEPTADO . " WHERE id_shipt=$folio";
			$result = $conexion->query($sql);		
		}
	}

	/*
	 *	Change Status of Shipping order when Remission Evidence is Delete
	 */
	function changeStatusRemisionEvidenceDelete($id){
		require 'database.php';
		$sql = "SELECT EVI.fk_ship_ship FROM S_EVIDENCE AS EVI WHERE id_evidence = $id;";
		$result = $conexion->query($sql);
		$row = $result->fetch_array(MYSQLI_NUM);
		validateIfAllRemissionsHadEvidence($row[0]);
	}

	//Validate the json 'urls.json'
	##PRINT ALL JSON WITH THE ACCESS PERMISSIONS
	function printJson(){
		$string = file_get_contents("urls.json");
		$json = json_decode($string, true);
		foreach($json_a as $item){
			foreach ($item as $key){
				if(is_array($key)) {
					foreach ($key as $val){
						echo $val . "<br>";
					}
				}
				else{
					echo $key . "<br>";
				}
			}
		}
	}
?>