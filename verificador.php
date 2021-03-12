<?php
include 'global/config.php';
include 'global/conexion.php';
include 'carrito.php';
include 'templates/cabecera.php';
?>

<?php

//print_r($_GET);

//$ClienteID = "AQGVTg53rSTFSKhmZR73u17JhKZpr2VZQTwhbvjTiI-0Yht3x4wbeCsGVdFfLUdbKXRlh2cSS00Fb5kQ";
//$Secret = "EPm_FjZNS_6lm59Vp5vaGMUyjmINNdpetVpA5EYHnfa8IO_RtKpoi8L1mWIQZ41-CEqw0bWMktERIHov";

    $Login = curl_init(LINKAPI."/v1/oauth2/token");

    curl_setopt($Login,CURLOPT_SSL_VERIFYPEER, false);

    curl_setopt($Login, CURLOPT_RETURNTRANSFER, TRUE);

    curl_setopt($Login, CURLOPT_USERPWD, CLIENTID.":".SECRET);

    curl_setopt($Login, CURLOPT_POSTFIELDS, "grant_type=client_credentials");

    $Respuesta = curl_exec($Login);

    $objRespuesta = json_decode($Respuesta);

    $AccesToken = $objRespuesta->access_token;

    //print_r($AccesToken);

    $venta = curl_init(LINKAPI."/v2/checkout/orders/".$_GET['orderID']);
    //".$_GET['paymentID']
    //https://api-m.sandbox.paypal.com/v2/payments/authorizations/0VF52814937998046 \
    //https://api.sandbox.paypal.com/v2/checkout/orders/".$_GET['orderID']   ///order opcion 2
    //https://api-m.sandbox.paypal.com/v2/checkout/orders/".$_GET['orderID']   ///order opcion 2
    //https://api-m.sandbox.paypal.com/v1/payments/payment/   --- DEPRECATED
    curl_setopt($venta,CURLOPT_HTTPHEADER,array("Content-Type: application/json","Authorization: Bearer ".$AccesToken));
    curl_setopt($venta, CURLOPT_RETURNTRANSFER, TRUE);
    curl_setopt($venta, CURLOPT_POST, false);

    curl_setopt($venta,CURLOPT_SSL_VERIFYPEER,FALSE);
    


    $RespuestaVenta = curl_exec($venta);

    //print_r($RespuestaVenta);

    $objDatosTransaccion = json_decode($RespuestaVenta);

    //print_r($objDatosTransaccion->purchase_units[0]->reference_id);

    $status = $objDatosTransaccion->status;
    $email = $objDatosTransaccion->payer->email_address;
    $total = $objDatosTransaccion->purchase_units[0]->amount->value;
    $currency = $objDatosTransaccion->purchase_units[0]->amount->currency_code;
    $reference_id = $objDatosTransaccion->purchase_units[0]->reference_id;

    // ---- aqui imprimimos los datos recuperados del objeto que paypal nos retorna -----
    /*echo $status."<br>";
    echo $email."<br>";
    echo $total."<br>";
    echo $currency."<br>";
    echo $reference_id;*/

    $clave = explode("#", $reference_id);

    $SID = $clave[0];
    $claveVenta = openssl_decrypt($clave[1], COD, KEY);

    //print_r($claveVenta);

    curl_close($venta);
    curl_close($Login);

    //echo $claveVenta;

    if ($status=="COMPLETED") {
        $mensajePaypal = "<h3>Pago aprobado</h3>";

        $sentencia = $pdo->prepare("UPDATE `tblventas`
        SET `PaypalDatos` =:PaypalDatos, `status` = 'aprobado' 
        WHERE `tblventas`.`ID` =:ID;");

        $sentencia->bindParam(":ID", $claveVenta);
        $sentencia->bindParam(":PaypalDatos", $RespuestaVenta);
        $sentencia->execute();

        $sentencia = $pdo->prepare("UPDATE `tblventas`
        SET status = 'completo' 
        WHERE ClaveTransaccion=:ClaveTransaccion
        AND Total=:Total
        AND ID=:ID");

        $sentencia->bindParam(":ClaveTransaccion", $SID);
        $sentencia->bindParam(":Total", $total);
        $sentencia->bindParam(":ID", $claveVenta);
        $sentencia->execute();

        $completado=$sentencia->rowCount();

        session_destroy();

    } else {
        $mensajePaypal = "<h3>Error en el pago</h3>";
    }

    //echo $mensajePaypal;    
    
?>  

<div class="jumbotron">
    <h1 class="display-4">¡ Listo !</h1>
    <hr class="my-4">

    <p class="lead"><?php echo $mensajePaypal; ?></p>

    <p>
    <?php 
    
    if ($completado>=1) {

        $sentencia = $pdo->prepare("SELECT * FROM tbldetalleventa, tblproductos WHERE tbldetalleventa.IDPRODUCTO=tblproductos.ID AND tbldetalleventa.IDVENTA=:ID");

        $sentencia->bindParam(":ID", $claveVenta);
        $sentencia->execute();

        $listaProductos = $sentencia->fetchAll(PDO::FETCH_ASSOC);

        //print_r($listaProductos);

    }
    
    ?>

    <div class="row">
        <?php foreach ($listaProductos as $producto) { ?>
        <div class="col-2">
            <div class="card">
            <img class="card-img-top" src="<?php echo $producto['Imagen']; ?>" height="180px">
                <div class="card-body">

                <p class="card-text"><?php echo $producto['Nombre']; ?></p>
                    
                    <?php if ($producto['DESCARGADO']<DESCARGASPERMITIDAS) { ?>

                    <form action="descargas.php" method="post">

                        <input type="hidden" name="IDVENTA" id="" value="<?php echo openssl_encrypt($claveVenta, COD, KEY); ?>">
                        <input type="hidden" name="IDPRODUCTO" id="" value="<?php echo openssl_encrypt($producto['IDPRODUCTO'], COD, KEY); ?>">

                        <button class="btn btn-success" type="submit">Descargar</button>

                    </form>

                    <?php }else { ?>
                        <button class="btn btn-success" type="button" disabled >Descargar</button>
                    <?php } ?>

                </div>
            </div>
        </div>
        <?php } ?>
    </div>

    </p>

</div>

<?php
include "templates/pie.php";
?>