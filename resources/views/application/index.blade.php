<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Solicitud Suministros</title>
    <style>
        @page {
            size: portrait;
        }
        
        .contenedorPrincipal {
            width: 100%; 
            margin: 0 auto;
            padding: 1%; 
        }

      
        .contenedorDatos {
            width: 100%;
            margin-bottom: 2%;
            font-size: 10px;
            overflow: hidden;
        }

      
         table {
            width: 100%;
            border-collapse: collapse;
        }

        tr, td {
            border: px solid rgb(0, 0, 0);
            padding: 1%;
        }

            .resaltado {
            background-color: rgb(248, 247, 247);
            text-align: center;
            font-weight: bold;
        }


            .contenedorFirmas {
            width: 50%;
        }
            .contenedorFirmas .solicitante-table {
            width: 200%;
        }
      
            .contenedorFirmas {
            float: left; 
            width: 47%; 
        }
            .firmas_autoriza {
            float: right; 
            width: 47%; 
        }
            .footer{            
            position: absolute;
            bottom: -5%;
            width: 85%;
            text-align: center;
            background-color: white; 
            padding: 10px 0; 
        }
            .titulosDetalles {
            height: 0%; /
        }

            .titulosDetalles td, .subtitulosDetalles {
            font-size: 10px; 
        }

            .centrar-texto {
            text-align: center;
        }

            .espacio-en-blanco {
            height: 20px; 
        }

    </style>
</head>
<body>
        <div class="contenedorPrincipal">
            <div class="contenedorTitulos">
                <table style="text-align: center;" class="titulos">
                    <tr style="height: 45%;">
                        <td rowspan="2" style="width: 23%;">
                            <div class="containerimg"><img src="images/logogle.png" alt="Logo" class="logo"></div>
                    </td>
                        <td rowspan="2" style="width: 55%;"><strong>SOLICITUD SUMINISTROS<strong>,<br><strong>SERVICIOS Y ENTREGA</strong></td>
                        <td style="width: 200px; font-size: 11px; "><strong>VERSIÓN: 03</strong></td>
                    </tr >
                    <tr style="height: 45%; font-size: 11px;">
                        <td><strong>FECHA DE EMISIÓN:</strong><br><strong>21 DE SEPTIEMBRE DE 2021</strong></td>
                    </tr>
                   
                        <td colspan="3">
                            <div class="resaltado">
                                <span>DOCUMENTO CONTROLADO</span>
                            </div>
                        </td>
                    
                    
                </table>
            </div> 

            <div class="contenedorDatos">
                <table>
                    <tr style="height: 50%;">
                        <td style="font-weight: bold; font-size: 12px;"><strong>PROCESO:</strong></td>
                        <td colspan="7"></td>
                    </tr>
                    <tr style="height: 50%;">
                        <td class="subtitulos" style="width: 16%; font-size: 12px;"><strong>Fecha de solicitud:</strong></td>
                        <td style="width: 13%;"></td>
                        <td class="subtitulos" style="width: 9%; font-size: 12px;"><strong>No. de</strong><br><strong>solicitud:</strong></td>
                        <td style="width: 8%;"></td>
                        <td class="subtitulos" style="width: 22%; font-size: 12px;"><strong>Fecha requerida de entrega:</strong></td>
                        <td style="width: 13%;"></td>
                        <td class="subtitulos" style="width: 12%; font-size: 12px;"><strong>N° de Orden</strong><br><strong>de Compra:</strong></td>
                        <td style="width: 7%;";></td>
                    </tr>
                </table>
            </div>
            

            <div class="contenedorDetalles">
                <table>
                    <tr class="titulosDetalles" style="height: 20px;">
                        <td rowspan="2" style="width: 6%;" class="centrar-texto"><strong>Cant</strong></td>
                        <td rowspan="2" style="width: 46%;" class="centrar-texto">
                            <p style="font-size: 11px;"><strong>DESCRIPCIÓN<br>Especificar: Color, material, referencia, marca, modelo, unidad de medida y todas las especificaciones necesarias.</strong></p>
                        </td>
                        <td colspan="3" style="width: 8%; font-size: 12px;" class="centrar-texto"><strong>Forma de compra</strong></td>
                        <td colspan="3" style="width: 11%; font-size: 12px;" class="centrar-texto"><strong>Existencias</strong></td>
                        <td rowspan="2" style="width: 10%; font-size: 12px;" class="centrar-texto"><strong>Fecha de Entrega</strong></td>
                        <td rowspan="2" style="width: 10%; font-size: 12px;" class="centrar-texto"><strong>Firma o Evidencia de recibo</strong></td>
                    </tr>
                    <tr class="subtitulosDetalles" style="height: 20px;">
                        <td style="width: 6%; font-size: 8px;" class="centrar-texto"><strong>Caja<br>Menor</strong></td>
                        <td style="width: 6%; font-size: 8px;" class="centrar-texto"><strong>Cotizar</strong></td>
                        <td style="width: 6%; font-size: 8px;" class="centrar-texto"><strong>Autoriz<br>ado<br>Si / No</strong></td>
                        <td style="width: 3%; font-size: 8px;" class="centrar-texto"><strong>Si</strong></td>
                        <td style="width: 4%; font-size: 8px;" class="centrar-texto"><strong>Cant.</strong></td>
                        <td style="width: 3%; font-size: 8px;" class="centrar-texto"><strong>No</strong></td>
                    </tr>
                </table>
            </div>
            <div class="contenedorDetalles">
                <table>
                    <tr>
                        <td class="ajustable" style="width: 8%; height: 18px;"></td>
                        <td class="ajustable" style="width: 61.5%;"></td>
                        <td class="ajustable" style="width: 8%;"></td>
                        <td class="ajustable" style="width: 8%;"></td>
                        <td class="ajustable" style="width: 8%;"></td>
                        <td class="ajustable" style="width: 4%;"></td>
                        <td class="ajustable" style="width: 5.5%;"></td>
                        <td class="ajustable" style="width: 4%;"></td>
                        <td class="ajustable" style="width: 13.5%;"></td>
                        <td class="ajustable" style="width: 13.3%;"></td>
                    </tr>
                    <tr>
                        <td class="ajustable" style="width: 8%; height: 18px;"></td>
                        <td class="ajustable" style="width: 61.5%;"></td>
                        <td class="ajustable" style="width: 8%;"></td>
                        <td class="ajustable" style="width: 8%;"></td>
                        <td class="ajustable" style="width: 8%;"></td>
                        <td class="ajustable" style="width: 4%;"></td>
                        <td class="ajustable" style="width: 5.5%;"></td>
                        <td class="ajustable" style="width: 4%;"></td>
                        <td class="ajustable" style="width: 13.5%;"></td>
                        <td class="ajustable" style="width: 13.3%;"></td>
                    </tr>
                    <tr>
                        <td class="ajustable" style="width: 8%; height: 18px;"></td>
                        <td class="ajustable" style="width: 61.5%;"></td>
                        <td class="ajustable" style="width: 8%;"></td>
                        <td class="ajustable" style="width: 8%;"></td>
                        <td class="ajustable" style="width: 8%;"></td>
                        <td class="ajustable" style="width: 4%;"></td>
                        <td class="ajustable" style="width: 5.5%;"></td>
                        <td class="ajustable" style="width: 4%;"></td>
                        <td class="ajustable" style="width: 13.5%;"></td>
                        <td class="ajustable" style="width: 13.3%;"></td>
                    </tr>
                    <tr>
                        <td class="ajustable" style="width: 8%; height: 18px;"></td>
                        <td class="ajustable" style="width: 61.5%;"></td>
                        <td class="ajustable" style="width: 8%;"></td>
                        <td class="ajustable" style="width: 8%;"></td>
                        <td class="ajustable" style="width: 8%;"></td>
                        <td class="ajustable" style="width: 4%;"></td>
                        <td class="ajustable" style="width: 5.5%;"></td>
                        <td class="ajustable" style="width: 4%;"></td>
                        <td class="ajustable" style="width: 13.5%;"></td>
                        <td class="ajustable" style="width: 13.3%;"></td>
                    </tr>
                    <tr>
                        <td class="ajustable" style="width: 8%; height: 18px;"></td>
                        <td class="ajustable" style="width: 61.5%;"></td>
                        <td class="ajustable" style="width: 8%;"></td>
                        <td class="ajustable" style="width: 8%;"></td>
                        <td class="ajustable" style="width: 8%;"></td>
                        <td class="ajustable" style="width: 4%;"></td>
                        <td class="ajustable" style="width: 5.5%;"></td>
                        <td class="ajustable" style="width: 4%;"></td>
                        <td class="ajustable" style="width: 13.5%;"></td>
                        <td class="ajustable" style="width: 13.3%;"></td>
                    </tr>
                    <tr>
                        <td class="ajustable" style="width: 8%; height: 18px;"></td>
                        <td class="ajustable" style="width: 61.5%;"></td>
                        <td class="ajustable" style="width: 8%;"></td>
                        <td class="ajustable" style="width: 8%;"></td>
                        <td class="ajustable" style="width: 8%;"></td>
                        <td class="ajustable" style="width: 4%;"></td>
                        <td class="ajustable" style="width: 5.5%;"></td>
                        <td class="ajustable" style="width: 4%;"></td>
                        <td class="ajustable" style="width: 13.5%;"></td>
                        <td class="ajustable" style="width: 13.3%;"></td>
                    </tr>
                    <tr>
                        <td class="ajustable" style="width: 8%; height: 18px;"></td>
                        <td class="ajustable" style="width: 61.5%;"></td>
                        <td class="ajustable" style="width: 8%;"></td>
                        <td class="ajustable" style="width: 8%;"></td>
                        <td class="ajustable" style="width: 8%;"></td>
                        <td class="ajustable" style="width: 4%;"></td>
                        <td class="ajustable" style="width: 5.5%;"></td>
                        <td class="ajustable" style="width: 4%;"></td>
                        <td class="ajustable" style="width: 13.5%;"></td>
                        <td class="ajustable" style="width: 13.3%;"></td>
                    </tr>
                    <tr>
                        <td class="ajustable" style="width: 8%; height: 18px;"></td>
                        <td class="ajustable" style="width: 61.5%;"></td>
                        <td class="ajustable" style="width: 8%;"></td>
                        <td class="ajustable" style="width: 8%;"></td>
                        <td class="ajustable" style="width: 8%;"></td>
                        <td class="ajustable" style="width: 4%;"></td>
                        <td class="ajustable" style="width: 5.5%;"></td>
                        <td class="ajustable" style="width: 4%;"></td>
                        <td class="ajustable" style="width: 13.5%;"></td>
                        <td class="ajustable" style="width: 13.3%;"></td>
                    </tr>
                    <tr>
                        <td class="ajustable" style="width: 8%; height: 18px;"></td>
                        <td class="ajustable" style="width: 61.5%;"></td>
                        <td class="ajustable" style="width: 8%;"></td>
                        <td class="ajustable" style="width: 8%;"></td>
                        <td class="ajustable" style="width: 8%;"></td>
                        <td class="ajustable" style="width: 4%;"></td>
                        <td class="ajustable" style="width: 5.5%;"></td>
                        <td class="ajustable" style="width: 4%;"></td>
                        <td class="ajustable" style="width: 13.5%;"></td>
                        <td class="ajustable" style="width: 13.3%;"></td>
                    </tr>
                    <tr>
                        <td class="ajustable" style="width: 8%; height: 18px;"></td>
                        <td class="ajustable" style="width: 61.5%;"></td>
                        <td class="ajustable" style="width: 8%;"></td>
                        <td class="ajustable" style="width: 8%;"></td>
                        <td class="ajustable" style="width: 8%;"></td>
                        <td class="ajustable" style="width: 4%;"></td>
                        <td class="ajustable" style="width: 5.5%;"></td>
                        <td class="ajustable" style="width: 4%;"></td>
                        <td class="ajustable" style="width: 13.5%;"></td>
                        <td class="ajustable" style="width: 13.3%;"></td>
                    </tr>
                    <tr>
                        <td class="ajustable" style="width: 8%; height: 18px;"></td>
                        <td class="ajustable" style="width: 61.5%;"></td>
                        <td class="ajustable" style="width: 8%;"></td>
                        <td class="ajustable" style="width: 8%;"></td>
                        <td class="ajustable" style="width: 8%;"></td>
                        <td class="ajustable" style="width: 4%;"></td>
                        <td class="ajustable" style="width: 5.5%;"></td>
                        <td class="ajustable" style="width: 4%;"></td>
                        <td class="ajustable" style="width: 13.5%;"></td>
                        <td class="ajustable" style="width: 13.3%;"></td>
                    </tr>
                    <tr>
                        <td class="ajustable" style="width: 8%; height: 18px;"></td>
                        <td class="ajustable" style="width: 61.5%;"></td>
                        <td class="ajustable" style="width: 8%;"></td>
                        <td class="ajustable" style="width: 8%;"></td>
                        <td class="ajustable" style="width: 8%;"></td>
                        <td class="ajustable" style="width: 4%;"></td>
                        <td class="ajustable" style="width: 5.5%;"></td>
                        <td class="ajustable" style="width: 4%;"></td>
                        <td class="ajustable" style="width: 13.5%;"></td>
                        <td class="ajustable" style="width: 13.3%;"></td>
                    </tr>
                </table>
            </div>
            
                <div class="espacio-en-blanco"></div>
            
            <div class="contenedorFirmas">
                <div class="contenedorFirmas">
                    <table class="firmas solicitante-table">
                        <tr style="height: 10%;">
                            <th colspan="2" class="resaltado">SOLICITANTE</th>
                        </tr>
                        <tr style="height: 46%;">
                            <td style="width: 25%;">Firma:</td>
                            <td></td>
                        </tr>
                        <tr>
                            <td style="width: 25%;">Nombre:</td>
                            <td></td>
                        </tr>
                        <tr>
                            <td style="width: 25%;">Proceso:</td>
                            <td></td>
                        </tr>
                    </table>
                </div>
            </div>
            
        
                <div class="firmas_autoriza">
                    <table class="firmas autoriza-table">
                        <tr style="height: 10%;">
                            <th colspan="2" class="resaltado">AUTORIZA LA COMPRA</th>
                        </tr>
                        <tr style="height: 45%;">
                            <td style="width: 25%;">Firma:</td>
                            <td></td>
                        </tr>
                        <tr>
                            <td style="width: 25%;">Nombre:</td>
                            <td></td>
                        </tr>
                        <tr>
                            <td style="width: 25%;">Cargo:</td>
                            <td></td>
                        </tr>
                    </table>
                  </div>
            </div>
        </div>
    </div>
    <div class="footer">
        Página 1 de 1
    </div>
</body>
</html>
