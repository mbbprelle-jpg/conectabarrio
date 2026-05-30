-- Método de pago para suscripciones ConectaBarrio
ALTER TABLE payments
ADD COLUMN metodo_pago ENUM('transferencia', 'efectivo', 'webpay') NULL AFTER paid_at;
