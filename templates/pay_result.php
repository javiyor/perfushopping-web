<?php
use Perfushopping\Web\Support\Env;
$mode = $mode ?? 'pending';
$order = (string)($_GET['order'] ?? '');
$transfer = (string)($_GET['mode'] ?? '') === 'transfer';
?>

<div class="page">
  <h2 style="margin:0 0 10px"><?= htmlspecialchars((string)($title ?? 'Estado')) ?></h2>
  <?php if ($transfer): ?>
    <div class="notice ok">
      Pedido <?= htmlspecialchars($order) ?> generado. Pago por transferencia.
    </div>
    <div class="notice" style="white-space:pre-wrap">
Transferencia bancaria via Mercado Pago
Alias MP: perfushopping.mp
    </div>
    <div class="notice" style="white-space:pre-wrap;margin-top:10px">
O tambien via banco:
Banco Santa Fe
Titular: Feresin Natalia Gabriela
CUIT: 27-24898238-7
Alias: perfushopping.sf
CBU: 3300000620000444439097
    </div>
  <?php else: ?>
    <?php if ($mode === 'success'): ?>
      <div class="notice ok">Gracias. Si el pago ya fue aprobado, el pedido se confirma por webhook.</div>
    <?php elseif ($mode === 'failure'): ?>
      <div class="notice danger">Pago rechazado. Podes intentar nuevamente.</div>
    <?php else: ?>
      <div class="notice">Pago pendiente. Te vamos a confirmar cuando Mercado Pago lo apruebe.</div>
    <?php endif; ?>
  <?php endif; ?>

  <a class="btn" href="/" style="margin-top:12px">Volver al catalogo</a>
</div>
