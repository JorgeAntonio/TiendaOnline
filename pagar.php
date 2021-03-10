<?php
include 'global/config.php';
include 'global/conexion.php';
include 'carrito.php';
include 'templates/cabecera.php';
?>

<?php
if ($_POST) {
    $total=0;
    $SID=session_id();
    $correo=$_POST['email'];

    foreach ($_SESSION['CARRITO'] as $indice => $producto) {
        $total=$total+($producto['PRECIO']*$producto['CANTIDAD']);
    }

    $sentencia=$pdo->prepare("INSERT INTO `tblventas` (`ID`, `ClaveTransaccion`, `PaypalDatos`, `Fecha`, `Correo`, `Total`, `status`) 
    VALUES (NULL,:ClaveTransaccion, '', NOW(), :Correo, :Total, 'pendiente');");
    $sentencia->bindParam(":ClaveTransaccion", $SID);
    $sentencia->bindParam(":Correo", $correo);
    $sentencia->bindParam(":Total", $total);
    $sentencia->execute();
    $idVenta=$pdo->lastInsertId();

    foreach ($_SESSION['CARRITO'] as $indice => $producto) {

        $sentencia=$pdo->prepare("INSERT INTO 
        `tbldetalleventa` (`ID`, `IDVENTA`, `IDPRODUCTO`, `PRECIOUNITARIO`, `CANTIDAD`, `DESCARGADO`) 
        VALUES (NULL,:IDVENTA,:IDPRODUCTO, :PRECIOUNITARIO, :CANTIDAD, '0');");

        $sentencia->bindParam(":IDVENTA", $idVenta);
        $sentencia->bindParam("IDPRODUCTO", $producto['ID']);
        $sentencia->bindParam(":PRECIOUNITARIO", $producto['PRECIO']);
        $sentencia->bindParam(":CANTIDAD", $producto['CANTIDAD']);
        $sentencia->execute();

    }
    
    //echo "<h3>" .$total. "</h3>";
}
?>

<div class="jumbotron text-center">
    <h1 class="display-4">Paso Final</h1>
    <hr class="my-4">
    <p class="lead">Estas a punto de pagar con Paypal la cantidad de: 
        <h4>$<?php echo number_format($total,2); ?></h4>

        <div id="paypal-button-container"></div>
    </p>
    <p>Los productos podran ser descargados una vez que se procese el pago<br/>
        <strong>(Para aclaraciones:  jorgedelaguila@gmail.com)</strong>
    </p>
</div>

<script src="https://www.paypal.com/sdk/js?client-id=sb&currency=USD"></script>

<!DOCTYPE html>
<html lang="en">

<head>
    <!-- Add meta tags for mobile and IE -->
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <title> PayPal Smart Payment Buttons Integration | Responsive Buttons </title>

    <style>
        /* Media query for mobile viewport */
        @media screen and (max-width: 400px) {
            #paypal-button-container {
                width: 100%;
            }
        }
        
        /* Media query for desktop viewport */
        @media screen and (min-width: 400px) {
            #paypal-button-container {
                width: 250px;
            }
        }
    </style>
</head>

<body>
    <!-- Set up a container element for the button -->
    

    <!-- Include the PayPal JavaScript SDK -->
    

    <script>
        // Render the PayPal button into #paypal-button-container
        paypal.Buttons().render('#paypal-button-container');
    </script>
</body>

</html>

<?php
include "templates/pie.php";
?>