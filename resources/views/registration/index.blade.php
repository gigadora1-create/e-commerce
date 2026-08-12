<!DOCTYPE html>
<html>
<head>
	<meta charset="utf-8">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<title>REGISTRO DE MANTENIMIENTO</title>
	<link rel="stylesheet" type="text/css" href="../admin_assets/css/registration.css">
    <link rel="icon" href="LOGO Obscuro.png">
</head>
<body>
	<div class="base">
		<div class="titulo">
			<div class="containerimg"><img src="/images/logogle.png" alt="Logo" class="logo"></div>
			<div class="container1">VERSION: 01</div>
			<div class="container2">FECHA DE EMISIÓN: <br> 22 DE OCTUBRE DE 2020</div>
			REGISTRO DE MANTENIMIENTOS
		</div>
		<div class="container3">DOCUMENTO CONTROLADO</div>
		<div class="cerra"></div>
		<table class="default">
			<tr>
				<th class="registration">N° REGISTRO:</th>
                <th> <input type="text" name="REGISTRO" size="5" value="{{ $registration->maintenance_number ?? '' }}"> </th>
                <th class="codigo">Código Interno del Equipo</th>
                <th> <input type="text" name="CODIGOINTERNO" size="10" value="{{ $registration->internal_code ?? '' }}"> </th>
				<th class="fecha1">Fecha Ultima Intervencion</th>
				<th><input type="text" name="fechaEntrega" id="fechaEntrega"size="5"></th>
				<th class="fecha1">fecha dia de intervencion</th>
				<th><input type="text" name="fechaEntrega" id="fechaEntrega" readonly value="<?php echo date('Y-m-d'); ?>"></div></th>
			</tr>
		</table>
		<table>
			<tr>
				<th class="container4">DESCRIPCION DEL ESTADO DEL EQUIPO</th>
				<th class="container5">NOVEDADES QUE PRESENTA</th>
			</tr>
			<tr>
				
				<td class="container6"> <input type="text" name="estadoequi" size="55"></td>
				<td class="container7"> <input type="text" name="novedadesequi" size="55"> </td>	
			</tr>
		</table>
		<div class="container8">MOTIVO DE LA REVISION</div><br>
		<div class="container9"> <select name="selector" id="selector">
			<option>MANTENIMIENTO PREVENTIVO</option>
			<option>MANTENIMIENTO CORRECTIVO</option>
		</select> </div>
		<table>
			<tr>
				<th class="container10">TIPO DE ACTIVIDAD REQUERIDA</th>
				<th class="container11">DESCRIPCION DE LABOR REALIZADA</th>
			</tr>
			<tr>
				<td class="container6"><b>Restablecimiento de sistema y Configuración.</b></td>
				<td class="container7">se valida las actualizaciones pendientes en el sistema y se realizan, se limpian los archivos temporales ademas de la papelera de reciclaje.</td>
			</tr>
			<tr>
				<td class="container12"><b>actualizaciones de SOFWARE</b></td>
				<td class="container13">se retiran tornillos para acceder a los componentes internos los cuales se retiran y se limpian con espuma asi mismo limpiador de contacto</td>
			</tr>
			<tr>
				<td class="container12"><b>limpieza de teclado y pantalla</b></td>
				<td class="container13">se limpia pantalla, teclado y carcasa</td>
			</tr>
		</table>
		<div class="container8">ESTADO FINAL DEL PROCEDIMIENTO (verificado)</div><br>
		<div class="container9"> <select name="selector" id="selector">
			<option>EQUIPO EN BUEN ESTADO</option>
			<option>FINALIZADO CORRECTAMENTE</option>
		</select> </div>
		<div class="container8">RECOMENDACIONES</div>
		<div class="container14">1.Es deber del responsable del equipo mantenerlo siempre limpio, use paños suaves para evitar rayarlo y utilice productos destinados para eso o en su defecto use alcohol, este procedimiento se debe de hacer mínimo 3 veces por semana.<br><br>
2.Elimine una vez por semana los archivos que se encuentran en la papelera de reciclaje.<br><br>
3.Limpie los archivos temporales del equipo mínimo una vez por semana de la siguiente forma: presione windows+R, en ejecutar escribir  %TEMP% , se abrirá una ventana con diferentes archivos,  selecciónelos todos con ctrl+E  y bórrelos con la tecla supr.<br><br>
4.Cuando la batería llegue a 100% desconectar el cargador y no dejarla descargar menos del 30 %. recuerde que mantener la batería siempre cargada ayuda a un buen rendimiento del equipo..<br><br>
5.No exponer el equipo portátil al sol ya que aumenta su temperatura y puede afectar alguno de sus componentes principales.<br><br>
6.Evitar colocar el equipo portátil sobre la cama o superficies similares ya que no permite la ventilación del equipo.<br><br>
7.Esta totalmente prohibido comer y/o tomar bebidas cerca al equipo portátil o mientras lo usa.<br><br>
8.Antes de apagar el equipo cierre todos los programas y/o archivos.<br><br>
9.Cuando el equipo le avise sobre una actualización ejecútela inmediatamente, si requiere de la contraseña de administrador solicítela a tecnología. es responsabilidad suya mantener el equipo actualizado.<br><br>
10.No descargar programas, archivos y/o multimedia en páginas desconocidas y no confiables ya que aumentan el riesgo de contaminar el equipo con virus y programas maliciosos. esta totalmente prohibido instalar una aplicación y/o programa sin previa autorización por el director de su proceso y aprobación por parte de tecnología.<br><br>
11.No guarde ni deje archivos y carpetas en el escritorio, estos son identificados como accesos directos y consume más rendimiento del equipo haciendo que sea mas lento.</div>
	<table>
		<tr>
			<th class="container10">RESPONSABLE DEL EQUIPO</th>
			<td> <input type="text" name="" size="80"> </td>
		</tr>
		<tr>
			<th class="container10">MANTENIMIENTO ASISITIDO POR</th>
			<td class="container13"> Victor Alexis Bautista </td>
		</tr>
	</table>
	</div>
</body>
</html>