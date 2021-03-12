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

<script src="https://www.paypal.com/sdk/js?client-id=AQGVTg53rSTFSKhmZR73u17JhKZpr2VZQTwhbvjTiI-0Yht3x4wbeCsGVdFfLUdbKXRlh2cSS00Fb5kQ&currency=USD"> // Replace YOUR_CLIENT_ID with your sandbox client ID
</script>

<div class="jumbotron text-center">
    <h1 class="display-4">Paso Final</h1>
    <hr class="my-4">
    <p class="lead text-center">Estas a punto de pagar con Paypal la cantidad de: 
        <h4>$<?php echo number_format($total,2); ?></h4>
    
        <div id="paypal-button-container"></div>

    </p>
    <p>Los productos podran ser descargados una vez que se procese el pago<br/>
        <strong>(Para aclaraciones:  jorgedelaguila@gmail.com)</strong>
    </p>
</div>

<?php
include "templates/pie.php";
?>

<!DOCTYPE html>
<html>
  <head>
    <meta name="viewport" content="width=device-width, initial-scale=1"> <!-- Ensures optimal rendering on mobile devices. -->
    <meta http-equiv="X-UA-Compatible" content="IE=edge" /> <!-- Optimal Internet Explorer compatibility -->
  </head>

  <body>

    <!-- Add the checkout buttons, set up the order and approve the order -->
    <script>
      paypal.Buttons({
        createOrder: function(data, actions) {
          return actions.order.create({
            purchase_units: [{
              amount: {
                value: '<?php echo $total;?>'
              },
              description: 'Compra de productos a la tienda por un valor de $ <?php echo number_format($total); ?> COP ',
              reference_id: "<?php echo $SID; ?>#<?php echo openssl_encrypt($idVenta,COD,KEY);?>"
            }]
          });
        },
        onApprove: function(data, actions) {
          return actions.order.capture().then(function(details) {
            alert('Transaccion completada por ' + details.payer.name.given_name);
            console.log(data);
            window.location="verificador.php?facilitatorAccessToken="+data.facilitatorAccessToken+"&orderID="+data.orderID+"&payerID="+data.payerID;
            //window.location="verificador.php?facilitatorAccessToken="+data.facilitatorAccessToken+"&orderID="+data.orderID+"&payerID="+data.payerID
          });
        }
      }).render('#paypal-button-container'); // Display payment options on your web page
    </script>
  </body>
</html>